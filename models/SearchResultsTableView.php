<?php
class SearchResultsTableView extends SearchResultsView
{
    const DEFAULT_LAYOUT = 1;
    const FIRST_LAYOUT = 1;
    const SUMMARY_LAYOUT = 1;
    const ADDRESS_LAYOUT = 2;
    const SUBJECT_LAYOUT = 3;
    const CREATOR_LAYOUT = 4;
    const COMPACT_LAYOUT = 5;
    const RELATIONSHIPS_LAYOUT = 6;
    const ADMIN_LAYOUT_1 = 7;
    const ADMIN_LAYOUT_2 = 8;
    const LAST_LAYOUT = 8;

    protected $layoutId;
    protected $limit;
    protected $showRelationships;

    function __construct()
    {
        parent::__construct();

        $this->showRelationships = isset($_GET['relationships']) ? intval($_GET['relationships']) == '1' : false;
    }

    public function getLayoutId()
    {
        if (isset($this->layoutId))
            return $this->layoutId;

        $id = isset($_GET['layout']) ? intval($_GET['layout']) : self::DEFAULT_LAYOUT;

        // Make sure that the layout Id is value.
        if ($id < self::FIRST_LAYOUT || $id > self::LAST_LAYOUT)
            $id = self::DEFAULT_LAYOUT;

        // See if the query requests that Table View show relationships. If not, make sure Relationships layout is not selected.
        if (!$this->showRelationships && $id == self::RELATIONSHIPS_LAYOUT)
            $id = self::DEFAULT_LAYOUT;

        if (($id == self::ADMIN_LAYOUT_1 || $id == self::ADMIN_LAYOUT_2) && !$this->isAdmin)
            $id = self::DEFAULT_LAYOUT;

        $this->layoutId = $id;
        return $this->layoutId;
    }

    public function getLayoutOptions()
    {
        $options = array(
            self::SUMMARY_LAYOUT => __('Summary'),
            self::SUBJECT_LAYOUT => __('Subject / Type'),
            self::CREATOR_LAYOUT => __('Creator / Publisher'),
            self::ADDRESS_LAYOUT => __('Address / Location'),
            self::COMPACT_LAYOUT => __('Compact'));

        if ($this->isAdmin)
        {
            $options[self::ADMIN_LAYOUT_1] = __('Admin 1');
            $options[self::ADMIN_LAYOUT_2] = __('Admin 2');
        }

        return $options;
    }

    public static function getLimitOptions()
    {
        return array(
            '10' => '10',
            '25' => '25',
            '50' => '50',
            '100' => '100',
            '200' => '200');
    }

    public function getShowRelationships()
    {
        return $this->showRelationships;
    }
}