<?php

class SearchQueryBuilder
{
    const MIN_KEYWORD_LENGTH = 3;

    protected $db;
    protected $integerSortElements;
    protected $select;
    protected $smartSortingEnabled;

    function __construct()
    {
        $this->db = get_db();
    }

    public function buildAdvancedSearchQuery($args)
    {
        $this->select = $args['select'];
        $this->smartSortingEnabled = get_option(SearchConfig::OPTION_ADDRESS_SORTING) == true;
        $this->integerSortElements = SearchConfig::getOptionDataForIntegerSorting();

        /* @var $searchResults SearchResultsView */
        $searchResults = $args['params']['results'];

        $keywords = $searchResults->getKeywords();
        $viewId = $searchResults->getViewId();
        $condition = $searchResults->getKeywordsCondition();
        $sortOrder = $searchResults->getSortOrder() == 'a' ? 'ASC' : ' DESC';
        $isKeywordQuery = !empty($keywords);
        $titleOnly = $searchResults->getSearchTitles();
        $isIndexQuery = $viewId == SearchResultsViewFactory::INDEX_VIEW_ID || $viewId == SearchResultsViewFactory::TREE_VIEW_ID;
        $isFilesOnlyQuery = $searchResults->getSearchFiles();

        if ($isIndexQuery)
        {
            if ($viewId == SearchResultsViewFactory::INDEX_VIEW_ID)
                $primaryField =  $searchResults->getIndexFieldElementId();
            else
                $primaryField =  $searchResults->getTreeFieldElementId();
        }
        else
        {
            $primaryField = $searchResults->getSortField();
        }

        // Construct the query.
        $this->buildQuery($primaryField, $isKeywordQuery, $isFilesOnlyQuery);

        if ($isKeywordQuery)
            $this->buildKeywordWhere($keywords, $condition, $titleOnly);

        $this->buildSortOrder($primaryField, $sortOrder, $isIndexQuery);
        $this->buildWhereDateRange();

        // Circumvent a bug in Table_Item::applySearchFilters which groups by items.id twice.
        $this->select->reset(Zend_Db_Select::GROUP);

        // Determine if any filters have gotten applied.
        $searchHasFilters = $this->searchHasFilters();

        // Group the results to eliminate duplicates.
        if ($isIndexQuery)
        {
            // Prevent index queries from returning null records;
            $this->select->where('_primary_column.text IS NOT NULL');

            // Group records that got returned more than once and get the group count. For example, when
            // indexing by subject, the query returns the same item three times if the item has three subjects.
            $this->select->columns('COUNT(*) AS count');
            $this->select->columns('_primary_column.text AS text');
            $this->select->group('text');
        }
        else
        {
            // Group by the record id to eliminate duplicates that can result from joining elements.
            $this->select->group('items.id');
        }

        $preventUnfilteredSearch = false;
        if ($preventUnfilteredSearch && !$searchHasFilters && !$isIndexQuery)
        {
            // The user did not specify any filters. Force the query to return zero results.
            $this->select->where("items.id = 0");
        }

        $sql = (string)$this->select; // For debugging only
    }

    protected function searchHasFilters()
    {
        $where = $this->select->getPart(Zend_Db_Select::WHERE);

        // Find out how many WHERE clauses the query has.
        $count = count($where);

        if ($count == 1 && $where[0] == '(items.public = 1)')
        {
            // Don't count the restriction to not show public items when no user is logged in.
            $count = 0;
        }
        return $count >= 1;
    }

    protected function buildQuery($primaryField, $isKeywordQuery, $isFilesOnlyQuery)
    {
        // This method join the search_texts table which is used for keyword searches
        // with the items and elements tables which are used for field searches.

        // Get the names of the tables we'll be working with.
        $searchTextTable = $this->db->SearchText;
        $itemsTable = $this->db->Items;
        $filesTable = $this->db->Files;
        $elementTextTable = $this->db->ElementText;

        // To make this work we first have to delete the From and Column parts of the query and then put the
        // query back together the way we need it. First save the From part so that we can  use it later to
        // reconstruct the advanced field joins.
        $from = $this->select->getPart(Zend_Db_Select::FROM);

        // Remove the existing columns and joins because we are going to create our own.
        $this->select->reset(Zend_Db_Select::COLUMNS);
        $this->select->reset(Zend_Db_Select::FROM);

        if ($isKeywordQuery)
        {
            // Keyword queries need the search_texts table.
            $this->select->from(array('search_texts' => $searchTextTable));
        }

        // Join the items table to allow field queries.
        $this->select->joinInner(array('items' => $itemsTable), 'items.id = search_texts.record_id');

        if ($isFilesOnlyQuery)
        {
            // Join the files table to limit results to those having a file attachement.
            $this->select->joinInner(array('files' => $filesTable), 'items.id = files.item_id');
        }

        // Join the element-text table to bring in the value of the primary field. For Table View, the
        // primary field is the sort field. For Index View and Tree View, it's the field being viewed.
        $this->select->joinLeft(array('_primary_column' => $elementTextTable),
            "_primary_column.record_id = items.id AND _primary_column.record_type = 'item' AND _primary_column.element_id = $primaryField");

        // Everything is in place. Reconstruct the advanced joins, if any, for field queries.
        foreach ($from as $alias => $table)
        {
            if (strpos($alias, '_advanced') === false)
                continue;
            $this->select->joinLeft(array($alias => $table['tableName']), $table['joinCondition']);
        }

        // Remove unneeded columns that got added automatically while adding joins. We only need items.id.
        $this->select->reset(Zend_Db_Select::COLUMNS);
        $this->select->columns('items.id');
        $this->select->columns('items.public');
    }

    protected function buildKeywordWhere($query, $queryType, $titleOnly)
    {
        $searchColumn = $titleOnly ? 'title' : 'text';
        switch ($queryType)
        {
            case SearchResultsView::KEYWORD_CONDITION_CONTAINS :
                $query = "%$query%";
                $where = "`search_texts`.`$searchColumn` LIKE ?";
                break;

            case SearchResultsView::KEYWORD_CONDITION_BOOLEAN :
            case SearchResultsView::KEYWORD_CONDITION_ALL_WORDS:
            default:
                // Parse and filter the query into words that can be used in a MATCH without triggering a SQL error.
                $words = explode(' ', $query);
                $words = array_map('trim', $words);
                $query = '';
                foreach ($words as $word)
                {
                    if (empty($word) || self::isStopWord($word))
                        continue;
                    if (!empty($query))
                        $query .= ' ';
                    if ($queryType == SearchResultsView::KEYWORD_CONDITION_ALL_WORDS)
                    {
                        $query .= "+$word";
                        $wordLen = strlen($word);
                        if ($wordLen >= self::MIN_KEYWORD_LENGTH)
                        {
                            // Only add wildcard operator onto words that meet the minimum word length, otherwise the
                            // MATCH will treat the word as a prefix. E.g. +of* will find 'office' but not 'of'.
                            // Also, don't append '*' if word ends in a double quote.
                            $query .= '*';
                        }
                    }
                    else
                    {
                        $query .= "$word";
                    }
                }
                $where = "`search_texts`.`record_type` = 'Item' AND ";
                $where .= "MATCH (`search_texts`.`$searchColumn`) AGAINST " . '(? IN BOOLEAN MODE)';
                break;
        }

        $this->select->where($where, $query);
    }

    protected function buildSortOrder($sortField, $sortOrder, $isIndexQuery)
    {
        $primaryColumnName = "_primary_column";
        $secondaryColumnName = null;
        $secondaryColumnSortOrder = '';

        if (array_key_exists($sortField, $this->integerSortElements))
        {
            // When sorting integer fields, replace the default text sorting with a signed integer sort.
            $this->select->reset(Zend_Db_Select::ORDER);
            $order[] = "CAST(_primary_column.text AS SIGNED INTEGER) $sortOrder";
            $this->select->order($order);
            return;
        }

        $addressFieldElementId = ItemMetadata::getElementIdForElementName('Address');
        $titleFieldElementId = ItemMetadata::getTitleElementId();

        $sortByAddress = $sortField == $addressFieldElementId;
        $sortAsHierarchy = SearchConfig::isHierarchyElementThatDisplaysAs($sortField, 'leaf');

        $sortByTitle = $sortField == $titleFieldElementId;

        $performSecondarySort = !$isIndexQuery && !$sortByTitle;

        if ($isIndexQuery && !$sortByTitle)
        {
            // When Index View and Tree View results are not ordered by title, the primary column is
            // the primary element's column. When sorting by title, the title code below will sort
            // title without any leading quotes.
            $primaryColumnName = 'text';
        }

        if ($sortByTitle)
        {
            // Sort the title on a virtual column that does not have leading double quote as the first
            // character. We do this so titles like "Fox Dens" don't sort above titles starting with 'A'.
            $primaryColumnName .= '_exp';
            $this->select->columns($this->columnValueForTitleSort("_primary_column.text", $primaryColumnName));
        }
        elseif ($sortByAddress)
        {
            // Sort by address using a virtual column that does not contain leading street number like 27, or 30 - 32.
            // We do this so you can sort addresses by street names instead of street numbers. The regular expression
            // looks first for a group of non-alpha characters (the street number), then for a group of alpha
            // characters or spaces (the street name). It then replaces the entire address value with just the value
            // of the second group (the street name) The use of '\\\\2' escapes backslashes in the string which results
            // in '\\2' which in turn escapes the backslash in the SQL so that the regular expression processor sees '\2'
            // which refers to the match on the second group.
            $primaryColumnName .= '_exp';
            $this->select->columns($this->columnValueForStreetNameSort("_primary_column.text", $primaryColumnName));
        }
        elseif ($sortAsHierarchy)
        {
            $primaryColumnName .= '_exp';
            $this->select->columns($this->columnValueForHierarchySort("_primary_column.text", $primaryColumnName));
        }
        else
        {
            $primaryColumnName = '_primary_column.text';
        }

        if ($performSecondarySort)
        {
            $secondaryColumnName = "_secondary_column_exp";

            if ($sortByAddress)
            {
                // See comments above explaining how the regular expression used for primary sort by address causes
                // the rows to sort by street name. That works well except that it leaves the street numbers for the
                // same street unsorted. The regular expression for the secondary sort isolates the street number
                // and casts it to an integer so that rows for the same street get sorted numerically by street number.
                // Note that the regex operates on _primary_column.text to create the secondary street number column.
                $exp = $this->columnValueForStreetNumberSort("_primary_column.text", $secondaryColumnName);
                $secondaryColumnSortOrder = $sortOrder;
            }
            else
            {
                // Use the title column for the secondary sort and always sort ascending.
                $exp = $this->columnValueForTitleSort("$secondaryColumnName.text", $secondaryColumnName);
                $secondaryColumnSortOrder = 'ASC';
            }

            $this->select->joinLeft(array($secondaryColumnName => $this->db->ElementText),
                "$secondaryColumnName.record_id = items.id AND $secondaryColumnName.record_type = 'Item' AND $secondaryColumnName.element_id = $titleFieldElementId", array());
            $this->select->columns($exp);
        }

        // Change the order to sort first by the user-chosen sort field and second by the Title field.
        $this->setSelectOrder($primaryColumnName, $sortOrder, !$isIndexQuery, $secondaryColumnName, $secondaryColumnSortOrder);
    }

    protected function buildWhereDateRange()
    {
        $yearStartElementName = CommonConfig::getOptionTextForYearStart();
        $yearEndElementName = CommonConfig::getOptionTextForYearEnd();

        if (!empty($_GET['year_start']))
        {
            $yearStart = intval(trim($_GET['year_start']));

            $element = $this->db->getTable('Element')->findByElementSetNameAndElementName('Item Type Metadata', $yearStartElementName);
            $this->select->joinLeft(array('_year_start' => $this->db->ElementText),
                "_year_start.record_id = items.id AND _year_start.record_type = 'Item' AND _year_start.element_id = $element->id", array());

            $this->select->where("_year_start.text >= '$yearStart'");
        }

        if (!empty($_GET['year_end']))
        {
            $yearEnd = intval(trim($_GET['year_end']));

            $element = $this->db->getTable('Element')->findByElementSetNameAndElementName('Item Type Metadata', $yearEndElementName);
            $this->select->joinLeft(array('_year_end' => $this->db->ElementText),
                "_year_end.record_id = items.id AND _year_end.record_type = 'Item' AND _year_end.element_id = $element->id", array());

            $this->select->where("_year_end.text <= '$yearEnd'");
        }
    }

    protected function columnValueForHierarchySort($columnName, $alias)
    {
        return "TRIM(SUBSTRING_INDEX($columnName, ',', -1)) AS $alias";
    }

    protected function columnValueForStreetNameSort($columnName, $alias)
    {
        if ($this->smartSortingEnabled)
        {
            // Replace an address with just the street name portion of the address. Assume that
            // a street name starts with an alphabetic character and consider anything before the
            // street name to be the street number. Examples of valid street numbers are:
            //    123
            //    123 - 124
            //    123, 124
            // Group 1:
            //   ([^a-zA-Z]+) matche a group of one or more characters that are not a-z or A-Z.
            //   ? at the end of group 1 means match zero or one of the group (the street number).
            // Group 2: (.*)
            //   (.*) matche a group of zero or more of any character (the street name).
            // Substitution:
            //    \2 means replace the original string with the match on group 2 (the street name)
            //    In SQL, the regular expression syntax for \2 must be written with the backslash escaped as \\2
            //    To get \\2 in a PHP string in double quotes, both slashes must be escaped as \\\\2
            return "REGEXP_REPLACE($columnName, '([^a-zA-Z]+)?(.*)', '\\\\2') AS  $alias";
        }
        else
        {
            return "$columnName AS $alias";
        }
    }

    protected function columnValueForStreetNumberSort($columnName, $alias)
    {
        if ($this->smartSortingEnabled)
        {
            // Replace an address with the first integer in the street number. Cast that number to an
            // integer so that the street address will sort numerically. Without the cast, 123 sorts before 45.
            // If the address has no street number, the empty street number will cast to 0 and sort highest so
            // that the address "Clark Point" will sort above "50 Clark Point".
            // Group 1:
            //   ((^[\\\\d]+) matche a group of one or more digits.
            //   ? at the end of group 1 means match zero or one of the group (the first integer of the street number).
            // Group 2: (.*)
            //   (.*) matche a group of zero or more of any character (the rest of the address after the first integer).
            // Substitution:
            //    \1 means replace the original string with the match on group 1 (the first integer of the street number)/
            //    In SQL, the regular expression syntax for \2 must be written with the backslash escaped as \\2
            //    To get \\2 in a PHP string in double quotes, both slashes must be escaped as \\\\2
            return "CAST(REGEXP_REPLACE($columnName, '(^[\\\\d]+)?(.*)', '\\\\1') AS SIGNED INTEGER) AS $alias";
        }
        else
        {
            return "$columnName AS $alias";
        }
    }

    protected function columnValueForTitleSort($columnName, $alias)
    {
        if ($this->smartSortingEnabled)
        {
            // Replace double quote at the beginning of a title with an empty string.
            return "REGEXP_REPLACE($columnName, '^\"', '') AS $alias";
        }
        else
        {
            // Replace any double quote in the title with an empty string.
            return "REPLACE($columnName, '\"', '') AS $alias";
        }
    }

    public static function isStopWord($word)
    {
        if (!ctype_alnum($word))
        {
            // Treat non alphanumeric text as a stop word.
            // This logic was intended to prevent the return of meaningless search results, but is proving
            // to be too restrictive e.g. it won't let you find a catalogue number of the form 2017.123.4567.
            // For now always return false instead of true.
            return false;
        }

        $stopwords = array(
            'a',
            'about',
            'an',
            'are',
            'as',
            'at',
            'be',
            'by',
            'com',
            'de',
            'en',
            'for',
            'from',
            'how',
            'i',
            'in',
            'is',
            'it',
            'la',
            'of',
            'on',
            'or',
            'that',
            'the',
            'this',
            'to',
            'was',
            'what',
            'when',
            'where',
            'who',
            'will',
            'with',
            'und',
            'the',
            'www');

        return in_array($word, $stopwords);
    }

    protected function setSelectOrder($primaryColumnName, $primaryColumnSortOrder, $sortNullElementsLast, $secondaryColumnName, $secondaryColumnSortOrder)
    {
        // Remove the current order.
        $this->select->reset(Zend_Db_Select::ORDER);

        // Create a new sort order sorted by the primary column and then by an optional secondary column.

        if ($sortNullElementsLast)
        {
            // To force empty primary values to sort at the bottom, Use the Omeka trick of inserting an initial
            // virtual column containing 1 for empty primary values and 0 for non-empty primary values.
            $order[] = "IF(ISNULL($primaryColumnName), 1, 0) ASC";
        }

        $order[] = "$primaryColumnName $primaryColumnSortOrder";
        if (!empty($secondaryColumnName))
            $order[] = "$secondaryColumnName $secondaryColumnSortOrder";

        // Reestablish a complete sort order.
        $this->select->order($order);
    }
}