<!-- Simple searchbar  -->

<search id=simple-search>
    <form id="search-form" class="advanced-search-form" action="find" aria-label="Search">
        <input id="query" class="advanced-search-form input" name="keywords" type="search" placeholder="Search Items" value="<?php echo $_REQUEST["keywords"] ?>">
        <input type="hidden" name="tags" value="<?php echo $_REQUEST["tags"] ?>">
        <input type="hidden" name="year_start" value="<?php echo $_REQUEST["year_start"] ?>">
        <input type="hidden" name="year_end" value="<?php echo $_REQUEST["year_end"] ?>">
        <button id="submit_search" class="advanced-search-form button" type="submit"></button>
        <a href="find/advanced" id="advanced-search-link">→ Advanced Search</a>
    </form>
</search>

<style>
    #simple-search {
        margin-top: 3rem;
        margin-bottom: 3rem;
    }

    #simple-search input[type="search"]#query {
        margin-bottom: 0px;
    }

     #search-form {
        box-shadow: none;
    }

    #submit_search {
        padding-right: 10px;
        margin-left: 10px;
        margin-bottom: 0px;
    }

    #advanced-search-link {
        margin-left: 20px;
        margin-right: 20px;
        margin-bottom: 0px;
        white-space: nowrap;
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>