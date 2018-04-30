<?php

class SearchResultsTreeView extends SearchResultsView
{
    protected $db;
    protected $singleEntryId;
    protected $treeFieldElementId;
    protected $treeFieldName;

    function __construct()
    {
        parent::__construct();
        $this->db = get_db();
    }

    private function ancestorExistsInHierarchy($hierarchy, $ancestorName)
    {
        $index = count($hierarchy);
        while ($index && $ancestorName != $hierarchy[$index - 1])
        {
            $index--;
        }
        return $index > 0;
    }

    protected function convertHierarchyToTree($hierarchy)
    {
        $levels = array();
        $nodes = array();

        foreach ($hierarchy as $key => $node) {
            $depth = 0;
            $i = strpos($node, ',');

            while ($i !== false) {
                $depth++;
                $node = substr($node, $i + 1);
                $i = strpos($node, ',');
            }
            $level = 0;
            if ($depth > 0) {
                while ($depth--) {
                    $level++;
                }
            }
            $levels[$key] = $level;
            $nodes[$key] = $node;
        }
        return array('levels' => $levels, 'nodes' => $nodes);
    }

    public function generateTree()
    {
        $elementId = $this->getTreeFieldElementId();
        $this->treeFieldName = ItemMetadata::getElementNameFromId($elementId);

        $tableName =  $this->db->ElementText;
        $sql = "SELECT DISTINCT `text` from `$tableName` WHERE `element_id` = $elementId order by text";
        $hierarchy =  $this->db->fetchCol($sql);

        // Resort the locations hierarchy to obey tree rules and ensure that children sort immediately after their parent.
        // We only have to do this for locations because we have no control over the hierarchy structure like we do for
        // hand-coded hierarchies that are created using Simple Vocab.
        $hierarchy = $this->resortHierarchy($hierarchy);

        $tree = $this->generateTreeFromHierarchy($hierarchy);

        return $tree;
    }

    protected function generateTreeFromHierarchy($hierarchy)
    {
        if (empty($hierarchy))
        {
            $levels = array();
            $html = '';
        }
        else
        {
            $hierarchy = $this->insertPseudoAncestors($hierarchy);

            $tree = $this->convertHierarchyToTree($hierarchy);
            $nodes = $tree['nodes'];
            $levels = $tree['levels'];

            $html = $this->generateTreeNodeHtml($hierarchy, $nodes);
        }

        return array('levels' => $levels, 'html' => $html);
    }

    protected function generateTreeNodeHtml($hierarchy, $nodes)
    {
        $nodeHtml = array();
        foreach ($nodes as $key => $node)
        {
            $startsWithCount = $this->getStartsWithItemCountFor($hierarchy[$key]);
            $exactCount = $this->getExactItemCountFor($hierarchy[$key]);
            $text = $hierarchy[$key];

            if ($startsWithCount != $exactCount)
            {
                // This text appears as an ancestor in a hierarchy, but might also appear alone. For example, it might
                // appear as 'Boston, Roxbury' and also as just 'Boston'. Combine both counts to get the total since
                // that's the number that appears in the tree. That's also how many hits someone will get when they
                // they click on 'Boston' and the search returns all instances that start with 'Boston'.
                $startsWithCount += $exactCount;
            }

            if ($startsWithCount || $exactCount)
            {
                if ($exactCount == 1 && $startsWithCount == 1)
                {
                    $itemId = $this->singleEntryId;
                    $url = url("items/show/$itemId");
                }
                else
                {
                    $condition = $exactCount == $startsWithCount ? 'is exactly' : 'starts with';
                    $url = $this->emitIndexEntryUrl($text, $this->treeFieldElementId, $condition);
                }

                $html = '<a href="' . $url . '">' . $node . '</a>';

                if ($startsWithCount > 0)
                {
                    $html .= ' <span class="tree-node-count">(' . $startsWithCount . ')</span>';
                }
            }
            else
            {
                $html = $node;
            }
            $nodeHtml[$key]['entry'] = $html;
            $nodeHtml[$key]['count'] = $startsWithCount;
        }
        return $nodeHtml;
    }

    protected function getExactItemCountFor($value)
    {
        foreach ($this->results as $result)
        {
            $entry = $result['text'];
            if ($entry == $value)
            {
                $count = $result['count'];
                if ($count == 1)
                    $this->singleEntryId = $result['id'];
                return $count;
            }
        }
        return 0;
    }

    protected function getStartsWithItemCountFor($value)
    {
        // Look for instances where the value followed by a comma exists. This will find that 'Boston,Roxbury'
        // starts with 'Boston,', but won't find that 'Blue Hill Bay' starts with 'Blue Hill'. First strip out all
        // spaces so that inconsistent spacing won't affect this logic. A return count of 0 means that value is
        // not an ancestor in any hierarchy (because ancestors are always followed by a comma).

        $count = 0;
        $value = str_replace(' ', '', $value);
        $value .= ',';
        foreach ($this->results as $result)
        {
            $entry = $result['text'];
            $entry = str_replace(' ', '', $entry);
            if (strpos($entry, $value) === 0)
                $count += $result['count'];
        }
        return $count;
    }

    public function getTreeFieldElementId()
    {
        if (isset($this->treeFieldElementId))
            return $this->treeFieldElementId;

        $this->treeFieldElementId = isset($_GET['tree']) ? intval($_GET['tree']) : 0;

        $options = $this->getTreeFieldOptions();
        if (!array_key_exists($this->treeFieldElementId, $options))
        {
            // The Id is invalid. Use the first option as a default.
            $this->treeFieldElementId = key($options);
        }

        return $this->treeFieldElementId;
    }

    public function getTreeFieldName()
    {
        return $this->treeFieldName;
    }

    public static function getTreeFieldOptions()
    {
        $treeViewData = SearchConfig::getOptionDataForTreeView();
        $options = array();
        foreach ($treeViewData as $elementId => $elementName)
        {
            $options[$elementId] = $elementName;
        }
        return $options;
    }

    protected function insertPseudoAncestors($hierarchy)
    {
        // Insert ancestors where they are missing from the hierarchy. This is needed because in the Simple Vocab
        // hierarchies we don't allow certain values. For example, an item can be a base map or an annotated map,
        // but not just a map. This function inserts ancestors to create a well-formed tree. E.g. it changes
        //    Map, Base
        //    Map, Annotated
        // To
        //    Map
        //       Map, Base
        //       Map, Annotated

        // Create a new hierarchy to contain the original tree with pseudo ancestors.
        $extendedHierarchy = array();

        // Examine every row in the original hierarchy.
        foreach ($hierarchy as $row) {
            // Split the row into an array of the nodes for this branch in the hierarchy tree.
            // For example, "Map, Base" gets split into an array with [0] = "Map" and [1] = "Base".
            $nodes = explode(', ', $row);

            // Form the row's parent name from this row's nodes minus the leaf node.
            $parentNodes = $nodes;
            unset($parentNodes[count($nodes) - 1]);
            $parentName = implode(', ', $parentNodes);

            // Determine if one or more of this row's ancestors are missing from the hierarchy.
            if (!$this->ancestorExistsInHierarchy($extendedHierarchy, $parentName))
            {
                $ancestorName = "";
                $depth = count($nodes) - 1;

                // Parse this row's nodes from root to leaf.
                for ($level = 0; $level < $depth; $level++)
                {
                    // Form the ancestor name at this level.
                    if (!empty($ancestorName))
                        $ancestorName .= ', ';
                    $ancestorName .= $nodes[$level];

                    // If the ancestor does not exist in the hierarchy, add it.
                    if (!$this->ancestorExistsInHierarchy($extendedHierarchy, $ancestorName))
                    {
                        $extendedHierarchy[] = $ancestorName;
                    }
                }
            }

            // Always copy this row to the extended hierarchy.
            $extendedHierarchy[] = $row;
        }
        return $extendedHierarchy;
    }

    protected function resortHierarchy($hierarchy)
    {
        // Resort so that children appear immediately after their parents. This is to address a case like
        //    Mount Desert Rock
        //    Mount Desert
        //    Mount Desert, Somesville
        // Which normally sorts as shown below because spaces sort before commas.
        //    Mount Desert
        //    Mount Desert Rock
        //    Mount Desert, Somesville
        // But the sort above makes "Mount Desert, Somesville" a child of " Mount Desert Rock" which is wrong.
        // To account for how spaces sort, append a comma to every node and then resort. This guarantee that children
        // like "Mount Desert, Somesville" will sort immediately after their parent "Mount Desert,".
        // Then remove the trailing commas.
        foreach ($hierarchy as $key => $node) {
            $hierarchy[$key] = $hierarchy[$key] . ',';
        }
        asort($hierarchy);
        foreach ($hierarchy as $key => $node) {
            $value = $hierarchy[$key];
            $value = substr($value, 0, strlen($value) - 1);
            $hierarchy[$key] = $value;
        }
        return $hierarchy;
    }
}