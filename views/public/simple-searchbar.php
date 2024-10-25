<!-- Simple searchbar  -->

<search id="simple-search">
    <form id="search-form" class="advanced-search-form" action="find" aria-label="Search">
        <input id="query" class="advanced-search-form input" name="keywords" type="search" placeholder="Search Items" value="<?php echo htmlspecialchars($_REQUEST["keywords"] ?? "", ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (isset($_REQUEST["tags"])) : ?> <input type="hidden" name="tags" value="<?php echo htmlspecialchars($_REQUEST["tags"], ENT_QUOTES, 'UTF-8'); ?>"> <?php endif; ?>
        <?php if (isset($_REQUEST["year_start"])) : ?> <input type="hidden" name="year_start" value="<?php echo htmlspecialchars($_REQUEST["year_start"], ENT_QUOTES, 'UTF-8'); ?>"> <?php endif; ?>
        <?php if (isset($_REQUEST["year_end"])) : ?> <input type="hidden" name="year_end" value="<?php echo htmlspecialchars($_REQUEST["year_end"], ENT_QUOTES, 'UTF-8'); ?>"> <?php endif; ?>
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