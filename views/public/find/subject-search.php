<?php
$advancedFormAttributes['id'] = 'search-filter-form';
$advancedFormAttributes['action'] = url('find');
$advancedFormAttributes['method'] = 'GET';
$advancedSubmitButtonText = __('Search');

// Instantiate search results objects needed to get option values.
$searchResults = new SearchResultsView();
$searchResultsTable = new SearchResultsTableView();
$searchResultsIndex = new SearchResultsIndexView();
$searchResultsTree = new SearchResultsTreeView();

// Get the query string either from the Advanced Search keywords or the Simple Search query.
$keywords = empty($_GET['keywords']) ? '' : $_GET['keywords'];
if (empty($keywords))
    $keywords = empty($_GET['query']) ? '' : $_GET['query'];

$subjectElementId = SearchResultsView::getElementId('Dublin Core,Subject');
$typeElementId = SearchResultsView::getElementId('Dublin Core,Type');
$titleElementId = SearchResultsView::getElementId('Dublin Core,Title');

$search = empty($_GET['advanced']) ? array() : $_GET['advanced'];
$subjectValue = __('People');
$typeValue = '*';

foreach ($search as $field)
{
    if (isset($field['element_id']) && $field['element_id'] == $subjectElementId)
        $subjectValue = isset($field['terms']) ? $field['terms'] : $subjectValue;
    if (isset($field['element_id']) && $field['element_id'] == $typeElementId)
        $typeValue = isset($field['terms']) ? $field['terms'] : $typeValue;
}

$searchTitlesOnly = $searchResults->getSearchTitles();

queue_js_file('js.cookie');
$pageTitle = __('Subject Search');
echo head(array('title' => $pageTitle, 'bodyclass' => 'subject-search'));
?>

<h1><?php echo $pageTitle; ?></h1>
<form <?php echo tag_attributes($advancedFormAttributes); ?>>

	<!-- Left Panel -->
	<div id="primary">
		<div class="search-form-section">
            <div>
                <div class="two columns">
                    <?php echo $this->formLabel('keywords', __('Keywords')); ?><br>
                </div>
                <div class="five columns inputs">
                    <?php echo $this->formText('keywords', $keywords, array('id' => 'keywords')); ?>
                    <?php echo $this->formHidden('condition', SearchResultsView::KEYWORD_CONDITION_ALL_WORDS, array('id' => 'condition')); ?>
                </div>
                <div id="search-title-only">
                    <div class="two columns">
                        <?php echo $this->formLabel('title-only', __('Search in')); ?><br>
                    </div>
                    <div class="five columns inputs">
                        <div class="search-radio-buttons">
                            <?php echo $this->formRadio('titles', $searchTitlesOnly, array(), $searchResults->getKeywordSearchTitlesOptions()); ?>
                        </div>
                    </div>
                </div>
            </div>
			<div>
				<div class="two columns">
					<label><?php echo __('Subject'); ?></label>
				</div>
				<div class="five columns inputs">
                    <div class="search-field-container">
                        <?php
                        echo $this->formHidden('advanced[0][element_id]', $subjectElementId, array('id' => 'subject-id'));
                        echo $this->formHidden('advanced[0][type]', 'starts with', array('id' => 'subject-choice'));
                        $subjectOptions = array(
                            'Businesses' => __('Businesses'),
                            'Events' => __('Events'),
                            'Organizations' => __('Organizations'),
                            'Other' => __('Other'),
                            'People' => __('People'),
                            'Places' => __('Places'),
                            'Structures' => __('Structures'),
                            'Transporation' => __('Transporation'),
                            'Vessels' => __('Vessels'),
                        );
                        echo $this->formSelect('advanced[0][terms]', $subjectValue, array(), $subjectOptions);
                        ?>
                    </div>
                </div>
			</div>
            <div>
                <div class="two columns">
                    <label><?php echo __('Item Type'); ?></label>
                </div>
                <div class="five columns inputs">
                    <div class="search-field-container">
                        <?php
                        echo $this->formHidden('advanced[1][element_id]', $typeElementId, array('id' => 'type-id'));
                        echo $this->formHidden('advanced[1][type]', 'starts with', array('id' => 'type-choice'));
                        $typeOptions = array(
                            '*' => __('Any Type'),
                            'Article' => __('Article'),
                            'Document' => __('Document'),
                            'Gallery' => __('Gallery'),
                            'Image' => __('Image'),
                            'Map' => __('Map'),
                            'Publication' => __('Publication')
                        );
                        echo $this->formSelect('advanced[1][terms]', $typeValue, array(), $typeOptions);
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php echo $this->formHidden('subjects', '1', array('id' => 'subjects')); ?>
        <?php echo $this->formHidden('index', $titleElementId, array('id' => 'index')); ?>
	</div>
    <!-- Right Panel -->
    <div id="secondary">
        <div id="search-button" class="panel">
            <input type="submit" class="submit button" value="<?php echo $advancedSubmitButtonText; ?>">
        </div>

        <?php echo $this->formLabel('view-label', __('Show search results in:')); ?>
        <div class="search-radio-buttons">
            <?php
            $viewOptions = $searchResults->getViewOptions();
            unset($viewOptions[SearchResultsViewFactory::TREE_VIEW_ID]);
            unset($viewOptions[SearchResultsViewFactory::RELATIONSHIPS_VIEW_ID]);
            echo $this->formRadio('view', $searchResults->getViewId(), null, $viewOptions);
            ?>
        </div>
    </div>
</form>

<script type="text/javascript">
    jQuery(document).ready(function () {

        jQuery('#search-filter-form').submit(function()
        {
            var keywordsField = jQuery('#keywords');
            var subjectSelector = jQuery("#subject-id");
            var typeSelector = jQuery("#type-id");
            var typeValue = jQuery("select[name='advanced[1][terms]'");
            var disableSubjectSelector = false;
            var disableTypeSelector = false;
            var disableKeywordsField = false;

            if (keywordsField.val().trim().length === 0)
            {
                // When no keywords, disable all the filters.
                disableKeywordsField = true;
                disableTypeSelector = true;
                disableSubjectSelector = true;
            }
            else if (typeValue.val() === '*')
            {
                // The '*' value is for the 'Any Type' selection. Disable the Type filter.
                disableTypeSelector = true;
            }

            // Disable any fields that should not get posted.
            keywordsField.prop("disabled", disableKeywordsField);
            subjectSelector.prop("disabled", disableSubjectSelector);
            typeSelector.prop("disabled", disableTypeSelector);
        });
    });
</script>

<?php echo foot(); ?>
