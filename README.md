# AvantSearch

The AvantSearch plugin provides extended searching and search results capabilities for the public interface of an
Omeka site. User documentation describing this plugin's extensive functionality is located in the Digital
Archive topic [Searching the Digital Archive](http://swhplibrary.net/searching/).

> Release 1.1 of AvantSearch is specific to the Southwest Harbor Public Library's
[Digital Archive](http://swhplibrary.net/archive) and therefore **this plugin is not yet usable for
another Omeka installation**. Please wait for the 2.0 release before using the plugin with your own installation. 

This plugin was developed for the [Southwest Harbor Public Library](http://www.swhplibrary.org/), in Southwest Harbor, Maine. Funding was provided in part by the [John S. and James L. Knight Foundation](https://knightfoundation.org/).

A screen shot of the search results produced by AvantSearch appears below.
<hr/>

![Example](http://swhplibrary.net/wp/wp-content/uploads/2017/05/simple-search-results.jpg)

<hr/>

#### Differences from Omeka search:

AvantSearch completely overrides Omeka's public search features. It provides its own
[Advanced Search](http://swhplibrary.net/searching/advanced-search/) page
and presents search results in an entirely different way. It does not affect Omeka's admin search other than
by controlling which content gets stored in the search_texts table as explained later in the sections 
for the Titles Only and Private Elements configuration options.

Here are some of the basic differences between AvantSearch and Omeka's native search.

Feature | AvantSearch | Omeka Search
--------|------------ | ------------
Simple search |  Looks for all keywords to return more relevant results. | Looks for any of the keywords.
Search in Titles only | Yes | No
Search only items with images or files | Yes | No
[Date range search](http://swhplibrary.net/searching/advanced-search/) | Yes | No
User can specify number of results | Yes, from the Advanced Search page. | No. Set by the administrator.
[Tabular results](http://swhplibrary.net/searching/search-results-table-view/) | Results are presented in Excel-like tables with sortable columns. | Results are returned as a list with options to sort by Title, Creator, or Date Added.
Customize results | Admin can configure custom result layouts. | No configuration.
Private data | Private elements of public items can be hidden from pubic users. | No hiding of private data.
Address sorting | Sorts first on street name, then on street number | Can only sort on Title, Creator, and Date Added.
[Subject Search](http://swhplibrary.net/searching/subject-search/) | Yes | No
[Image View](http://swhplibrary.net/searching/search-results-image-view/) | Yes| No
[Index View](http://swhplibrary.net/searching/search-results-index-view/) | Yes| No
[Tree View](http://swhplibrary.net/searching/search-results-tree-view/) | Yes | No
[Relationships View](http://swhplibrary.net/searching/search-results-relationships-view/) | Yes | No
Search by File, Collection, Features | Not in the current release. | Yes

## Dependencies
The AvantSearch plugin requires that the [AvantCommon](https://github.com/gsoules/AvantCommon) plugin be installed and activated.
AvantCommon contains common logic used by [AvantSearch](https://github.com/gsoules/AvantSearch) and
[AvantRelationships](https://github.com/gsoules/AvantRelationships).

## Installation

To install the AvantSearch plugin, follow these steps:

1. First install and activate the [AvantCommon](https://github.com/gsoules/AvantCommon) plugin.
1. Unzip the AvantSearch-master file into your Omeka installation's plugin directory.
1. Rename the folder to AvantSearch.
1. Activate the plugin from the Admin → Settings → Plugins page.
1. Configure the AvantCommon plugin to specify your item identifier and title elements.
1. Configure the AvantSearch plugin as decribed in the Configuration Options section below.
1. Edit your theme so that it displays the AvantSearch search box and Advanced Search link instead
of the native Omeka search. Instructions for editing your theme are below.

In your theme's header.php file,

Replace this code

```
<div id="search-container" role="search">
    <?php if (get_theme_option('use_advanced_search') === null || get_theme_option('use_advanced_search')): ?>
    <?php echo search_form(array('show_advanced' => true)); ?>
    <?php else: ?>
    <?php echo search_form(); ?>
    <?php endif; ?>
</div>
```
with this code
```
<div id="search-container" role="search">
    <?php
    if (plugin_is_active('AvantSearch'))
    {
        echo AvantSearchPlugin::emitSearchForm();
    }
    else
    {
        $advancedOption = get_theme_option('use_advanced_search');
        echo search_form(array('show_advanced' => $advancedOption === null || $advancedOption == true));
    }
    ?>
</div>
```
The new code will replace the native Omeka simple and advanced search controls with the AvantSearch controls, but
will automatically revert to the native controls if the AvantSearch plugin is deactivated.

## Improving Search Results

The AvantSearch plugin will work without any modifications to your database. However, please read this section to
learn how you can improve search results by changing just one setting.

Like Omeka's native search, AvantSearch performs keyword searches using the Omeka `search_texts` table. The Omeka installer creates this table
using the MyISAM storage engine. You will get much better results from keyword searches by changing the table to use the InnoDB
storage engine because MyISAM negatively affects keyword searches in two ways:
 
* MyISAM uses a very long list of [stopwords](https://dev.mysql.com/doc/refman/5.7/en/fulltext-stopwords.html).
* MyISAM's default settings ignores keywords of three characters or less (ft_min_word_len).
 
With MyISAM a search for "road+ map+" will ignore 'map' and thus return all items containing 'road' instead of only
those items containing 'road' AND 'map'. Additionally, the MyISAM stopword list contains so many words that people
commonly search for that users are often surprised when items don't appear in search results.
 
In contrast, InnoDB has a very short list of stopwords and only ignores keywords that are two characters or less
(innodb_ft_min_token_size). Although you can change the value of ft_min_word_len to 3, this variable
can only be set at the MySQL server level and a server restart is required to change them. If you are
using a shared server, you probably don't have the option to change this value.

Follow these steps to change your search_texts table from MyISAM to InnoDB:
 
* In phpAdmin, click on your database to see its tables
* Click on the search_texts table (probably called omeka_search_texts or something similar)
* Click on the Operations tab
* In the Table options section, change Storage Engine from MyISAM to InnoDB
* Click Go

## Usage
Once installed, AvantSearch entirely overrides Omeka's native user interface for public search (Omeka's native admin
search is still available from admin pages). There are several configuration options available on the plugin's
configuration page.


#### Configuration Options
The table below lists the options available on the AvantSearch plugin configuration page. To help get you started using AvantSearch, the installer provides some
default option values using commonly used Dublin Core elements.

Option | Description
----------------- | -----------
Titles Only | Show the Advanced Search option to limit keyword searching to Title text.
Date Range | Show the Advanced Search option to search within a range of years.
Relationships View | Show the option to show search results in Relationships View.
Address Sorting | Sort street addresses first by street name, then by street number.
Private Elements | Elements that should not be searched by public users.
Result Elements | Names and labels of elements that can appear in search results.
Layouts | Layout definitions.
Detail Layout | Detail layout elements.
Index View | Elements that can be used as the Index View field.
Tree View | Elements that can be used as the Tree View field
Subject Search | Subjects and Types that appear on the Subject Search page.

The subsections that follow explain the options listed in the table above. Some options require that you specify
formatted list of information using commas, semicolons or other characters as separators. For these options, spaces
around separators are ignored. For example "a,b,c" is treated the same as "a, b, c".

<hr/>

#### Titles Only
When this option is checked, radio buttons will appear under the keywords text box on the Advanced Search page to let the user choose
to search in all fields or in titles only. This feature is very helpful for narrowing search results down
to only the most relevant items.

The Titles Only option requires that a FULLTEXT index be set on the `title` column of the `search_text` table.
This is easily done using phpMyAdmin by following these steps:
1. Select the 'search_texts' table
1. Click the Structure tab
1. On the row for the `title` column, click Fulltext among the actions at the far right
1. Click OK on the dialog confirming that you want to add FULLTEXT to the column
1. The `title` column will now appear in the Indexes section showing its type as FULLTEXT

<hr/>

#### Date Range
When this option is checked, Date Start and Date End text boxes will appear as filters at the bottom of the
Advanced Search page.

The Date Range option requires that your Omeka installation has elements named `Date Start` and `Date End` and elements
and that these elements are used exclusively to store four digit years.

A user can provide values for both Date Start and Date End to limit search results to
items in that range inclusive. For example if you specify 1900 for Date Start and 1940 for Date End, the
search will find items with Date Start greater than or equal to 1900 and less than or equal to 1940. If you only
provide a value for Date Start, the search will find items where Date Start is that date or more recent.
If you only provide a value for End, the filter will find items where Date End is that date or older.

[Learn more about Date Filters](http://swhplibrary.net/searching/advanced-search/) (see the Date Filters section).

<hr/>

#### Relationships View

When this option is checked, an option to show search results in Relationships View will appear on the Advanced
Search page. This option can only be used when the [AvantRelationships](https://github.com/gsoules/AvantRelationships)
plugin is installed and activated.

[Learn more about Relationships View](http://swhplibrary.net/searching/search-results-relationships-view/). 

<hr/>

#### Address Sorting (requires MariaDB)
Address sorting improves search results by sorting addresses first on the street name and then by the street number as an integer.
Normally addresses are sorted in a database, or in an Excel spreadsheet, as ordinary text where numbers sort before
letters. Furthermore, numbers are normally sorted as text, rather than as integers such that `10` appears before `9`.

Without address sorting:
* 10 Main Street
* 72 Pleasant Lane
* 9 Main Street

With address sorting:
* 9 Main Street
* 10 Main Street
* 72 Pleasant Lane

The Address Sorting option requires that your Omeka installation has an element named `Address` that is used to store
street addresses which typically begin with a number, followed by a street name.

> **IMPORTANT**: Address sorting uses a SQL function called REGEXP_REPLACE that is only supported by [MariaDB](https://mariadb.org/).
If your server is running [MySQL](https://www.mysql.com/), do NOT select this option or you will get an Omeka error that
will prevent your site from working.

<hr/>

#### Private Elements
This option lets you specify a semicolon-separated list of element names that should not be visible to public users for searching
purposes. For example, you might have elements used to record internal information such as notes and item status that
contain information meant only for administrators. You can specify "Notes, Status" in the Private Elements text box to
prevent this information from being searched by the public.

* Private elements will not appear as field selections on the Advanced Search page unless you are logged
in as an administrator.
* The text of private elements will not be recorded in the search_texts table, and therefore will not be searched when
performing a keyword search. This is true whether or not you are logged in as an administrator.
* To search for text in private elements, and administrator can do a field search in those fields, either through the public
Advanced Search page or using the native Omeka Advanced Search page.
* If you add an existing element to the private elements list, that element's text will still be contained in the
search_texts table and therefore be found via a keyword search. To hide the element's content, you
must reindex your Omeka database to force the search_texts table to be rebuilt without the private element text.
You do this by clicking the Index Records button on the Omeka
[Search Settings](https://omeka.org/classic/docs/Admin/Settings/Search_Settings/) page.
 
This features solves a problem in Omeka's native search whereby the text of all elements are searched, including
information that is hidden from public users by the [Hide Elements](http://omeka.org/classic/plugins/HideElements/)
plugin. This can produce keyword search results containing items that match the
search criteria, but that don't display the elements that resulted in the hit. For example, the search might
find keywords that appear in an item's hidden Notes element, but in no other public elements for that item. The user
then gets a search result that appears to contain none of the keywords they were looking for.

Below is an example specification of the Private Elements option.

```
Notes;
Status;
```

<hr/>

#### Result Elements

Use this option to specify which elements will appear in a search result layout.
AvantSearch uses this information to associate labels with element names. For example,
you might have an element named "Object ID" but want it to appear in search results as "Item" or "Catalog #".
The label value will appear in place of the element name in search results and in options of the Advanced Search page.

###### Format:
* Specify each element on a separate row ending with a semicolon.
* On each row, type the element name optionally followed by a colon and the element's label. If you don't specify
a label, the element name will be used as its label.
* Use the aliases `<identifier>` and `<title>`to specify the elements that your installation uses for the item
identifier and title. You must use these even if you use the Dublin Core names Identifier and
Title. You specify your installation's identifier and title elements on the configuration page for the
[AvantCommon](https://github.com/gsoules/AvantCommon) plugin. 
* There are two pseudo-element names: `<image>`, and `<tags>` that refer to values for which there is no element. Use
them to show an item's image and tags in search results.

> **IMPORTANT**: You must specify every element that you want reference in the Layouts and Detail Layout options.
An element specified in those options, but not in Results Elements, will be ignored.

> **IMPORTANT**: The order in which you specify elements is the order in which the columns for the elements willl
appear in table layouts. This enforces consistent presentation of results which means that you cannot have one
element appear before another in one layout, and after it in another.

Below is an example specification of Result Elements. Note the order of elements. Because Type appears before Subject,
a layout that displays both, will always put the Type column before the Subject column even if you specified
Subject followed by Type in the layout definition.

```
<identifier>: Item;
<image>: Image;
<title>:Title;
Type;
Subject;
Accession Number;
Creator;
Publisher;
Date;
Medium;
Number of Pages: Pages;
Condition;
Status;
<tags>: Tags;
Description;
```

<hr/>

#### Layouts

The Layouts option lets you specify different ways to present search results in table view. The layouts you define
here will appear as Table Layout options on the Advanced Search page, and in the green Change Layout list that appears on
at the top of search results to the left of the blue Modify Search button.

###### Format:
* Specify each layout on a separate row ending with a semicolon.
* Each row must contain: *ID*, *Access*, *Name*: *Columns*;
* The layout Name must be followed by a colon.
* The *ID* specifier must start with 'L' followed by an integer >= 2.  The numbers do not have to be consecutive.
The layout ID L1 is reserved for the Detail layout.
* The *Access* specifier must be either `public` or `admin` to indicate if anyone can see the layout or only someone
logged in as an administrator.
* The *Name* specifier is text that briefly describes the layout.
* The *Columns* specified is a comma-separated list of element names chosen from those in the Result Element list.
If you decided to add a new column to a layout, make sure to add it to the Result Element list if it's not already
there, otherwise that column won't appear in the layout.

Below is an example specification of Layouts. Note that each layout uses the aliases `<identifier>` and `<title>`.
They are not required, but it's helpful to users to always see this information on each layout. Remember also
that the order in which columns appear in a layout is the order in which they appear in the Search Results list. For instance,
in the example below, you could list Publisher before Creator, but if Creator precedes Publisher in the Search Results
list, that will be the order of the columns regardless of the order in the layout specification.

```
L1, public, Summary;
L2, public, Creator/Publisher: <identifier>, <title>, Creator, Publisher, Date;
L3, public, Type/Subject: <identifier>, <title>, Subject, Type;
L6, admin, Admin Info: <identifier>, <title>, Status, Medium, Condition;
```

Notice that the specification above includes L1 in the first row, but that row does not list any columns. You must specify
L1 in order to give it a name which in this example is Summary. If you omit L1, the detail layout will not appear as
a table view option.

> **TIP**: If a layout is not displaying as you expect, carefully look at the layout definition and at the rows in the
Results Element option. If an element name is misspelled in either place, the element will be ignored in the layout.
Also, if you have a syntax error in either location, for example a row ending in a colon instead of a semicolon, or
a mising colon after the *Name* specifier, the affected elements will be ignored.

[Learn more about layouts](http://swhplibrary.net/searching/search-results-table-view/).

<hr/>

#### Detail Layout

Use the Detail Layout to specify the elements which appear in the three columns of the L1 detail element. A screen shot
of the detail layout appears at the top of this documentation.

You can specify elements for column one and column two, but column three is always the items Dublin Core Description
element. In the screen shot, the last row shows Type and Subject in column one, and Address and Location in column two.
If an element has no text, it will not appear in the Detail layout. In the screen shot, the first row shows Date in
column one, but Date does not appear in the other rows because those items have no date information.


###### Format:
* Specify the column one elements in the first row ending with a semicolon.
* Specify the column two elements on the second row ending with a semicolon.
* Specify the elements as a comma-separated list of element names chosen from those in the Result Element list.
If you decided to add a new element to a column, make sure to add it to the Result Element list if it's not already
there, otherwise that element won't appear in the column.
* Unlike the other layouts, the top to bottom order of elements in the Detail layout columns is not dictated by
the order of elements in the Results List. The top to bottom order is the order in the comma-separated lists.

Below is an example specification of the Detail Layout option.

```
Type, Accession Number, Subject, Date, <tags>;
Creator, Publisher, Medium, Condition, Number of Pages;
```

If you prefer to have only two detail columns, specify only a semicolon on the second line above, and add the
following to your theme's CSS. Doing so will display element values in column one and the description in column two.

```
.search-results-detail-col2 {
	display: none;
}

.search-results-detail-col3 {
	width: 70%;
}
```
<hr/>

#### Index View

The Index View option lets you specify a semicolon-separated list of elements that can be used as the Index Field when choosing Index View from
the Advanced Search page. If you leave this option blank, Index View will not appear as an option on the Advanced
Search page.

Below is an example specification of the Index View option.

```
<title>;
Creator;
Publisher;
Type;
```

[Learn more about Index View.](http://swhplibrary.net/searching/search-results-index-view/)


<hr/>

#### Treeview

The Tree View option lets you specify a semicolon-separated list of elements that can used as the Tree Field when choosing Tree View from
the Advanced Search page. If you leave this option blank, Tree View will not appear as an option on the Advanced
Search page.

Below is an example specification of the Subject Search option.

```
Subject;
Type;
```

[Learn more about Tree View.](http://swhplibrary.net/searching/search-results-tree-view/)


<hr/>

#### Subject Search

Use this option to specify the Type and Subject lists that appear on the Subject Search page. If you leave this option
blank, the Subject Search link will not appear next to the Advanced Search link below the simple search text box.

###### Format:
* Specify types in the first row ending with a semicolon.
* Specify subjects on the second row ending with a semicolon.

Below is an example specification of the Subject Search option.

```
People, Places, Structures;
Document, Image, Map;
```

[Learn more about Subject Search.](http://swhplibrary.net/searching/subject-search/)

## CSS

You can override Search Results styling in your theme. Using Developer tools in your browser,
you can see that AvantSearch specifies unique classes to the HTML tags in search results. Find the classes you
are interested in and override them in your theme's style.css file.

The examples below show how to use CSS to set the column width the Identifier element.

```
.search-th-identifier {
	min-width: 100px;
}

.search-td-identifier {
	text-align: left;
	width: 100px;
}
```

Notes:
* A space in an Omeka element name will appear as a hyphen in the CSS class name. For example
the header class name for an element named "First Name" will be 'search-th-first-name' not 'search-th-first name'.
* '#' used in an Omeka element name will not appear in the corresponding CSS class name. For example
the header class name for an element named "Catalog #" will be 'search-th-catalog-' not 'search-th-catalog-#'.

##  License

This plugin is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

## Copyright

* Created by [gsoules](https://github.com/gsoules) for the Southwest Harbor Public Library's [Digital Archive](http://swhplibrary.net/archive)
* Copyright George Soules, 2016-2018.
* See [LICENSE](https://github.com/gsoules/AvantRelationships/blob/master/LICENSE) for more information.

Inspiration for the [Index View](http://swhplibrary.net/searching/search-results-index-view/) and [Tree View](http://swhplibrary.net/searching/search-results-tree-view/) search results came from the alphabetized index and hierarchical list features in the [Daniel-KM / Reference](https://github.com/Daniel-KM/Reference) plugin.




