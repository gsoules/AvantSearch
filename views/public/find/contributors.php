<?php
$useElasticsearch = AvantSearch::useElasticsearch();

$stats = '';

if ($useElasticsearch)
{
    $stats = AvantElasticsearch::generateContributorStatistics();
}

$pageTitle = __('Contributors');
echo head(array('title' => $pageTitle, 'bodyclass' => 'avantsearch-contributors'));
echo "<h1>$pageTitle</h1>";
?>
<div>

<?php if (!empty($stats)): ?>
    <div class="search-form-section">
        <?php echo $stats; ?>
    </div>
<?php endif; ?>

</div>

<?php echo foot(); ?>
