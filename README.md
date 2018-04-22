# AvantSearch

The AvantSearch plugin provides extended searching and search results capabilities for the public interface of an
Omeka site. User documentation describing this plugin's extensive functionality is located in the Digital
Archive topic [Searching the Digital Archive](http://swhplibrary.net/searching/).

> **This plugin is under development**. Please wait for the 2.0 release before using with your own installation. 

This plugin was originally developed for the [Southwest Harbor Public Library](http://www.swhplibrary.org/), in Southwest Harbor, Maine. Funding was provided in part by the [John S. and James L. Knight Foundation](https://knightfoundation.org/).

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
Quick search |  Displays an item (instead of search results) when its Identifier value is typed in the public Simple search box. | Displays search results for all items containing the identifier.
Simple search |  Looks for all keywords to return more relevant results. | Looks for any of the keywords.
[Search in Titles only](https://github.com/gsoules/AvantSearch#titles-only) | Yes | No
Search only items with images or files | Yes | No
[Date range search](http://swhplibrary.net/searching/advanced-search/) | Yes | No
User can specify number of results | Yes, from the Advanced Search page. | No. Set by the administrator.
[Tabular results](http://swhplibrary.net/searching/search-results-table-view/) | Results are presented in Excel-like tables with sortable columns. | Results are returned as a list with options to sort by Title, Creator, or Date Added.
Customize results | Admin can configure custom result layouts. | No configuration.
Private data | Private elements of public items can be hidden from pubic users. | No hiding of private data.
Integer sorting | Sorts integer element values numerically instead of alphabetically. | No. All data is sorted alphabetically.
Address sorting | Sorts first on street name, then on street number | Can only sort on Title, Creator, and Date Added.
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

When this plugin is activated, it dynamically overrides the native Omeka search box (located in the page
header) with the version used by AvantSearch.

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
Private Elements | Elements that should not be searched by public users.
Columns | Customization of columns in Table View search results.
Layouts | Layout definitions.
Layout Selector Width | Specifies the width of the layout selector dropdown that appears on search results pages.
Detail Layout | Detail layout elements.
Index View | Elements that can be used as the Index View field.
Tree View | Elements that can be used as the Tree View field.
Integer Sorting | Columns that should be sorted as integers.
Address Sorting | Sort street addresses first by street name, then by street number.
Date Range | Show the Advanced Search option to search within a range of years.
Relationships View | Show the option to show search results in Relationships View.

The subsections that follow explain the options listed in the table above. Some options require that you specify
formatted list of information using commas or other characters as separators. For these options, spaces
around separators are ignored. For example "a, b , c" is treated the same as "a,b,c".

<hr/>

#### Titles Only
When this option is checked, radio buttons will appear under the keywords text box on the Advanced Search page to let the user choose
to search in all fields or in titles only. This feature is very helpful for narrowing search results down
to only the most relevant items because titles often contain the most important keywords.

**NOTE:** If you want to use this option, but the configuration page says it's not available for your installation, you'll need to add a FULLTEXT
index to the `title` column of the `search_text` table. This is easily done using phpMyAdmin by following these steps:
1. Select the 'search_texts' table
1. Click the Structure tab
1. On the row for the `title` column, click Fulltext among the actions at the far right
1. Click OK on the dialog confirming that you want to add FULLTEXT to the column
1. The `title` column will now appear in the Indexes section showing its type as FULLTEXT (expand the Indexes
section if it's not visible)

<hr/>

#### Private Elements
This option lets you specify a list of element names, one per row, that:
* Should not be searchable via a keyword search
* Don't appear to public uses in the Fields dropdown on the Advanced Search page (they will appear to a logged in administrator)

For example, you might have elements used to record internal information such as notes and item status that
contain information meant only for administrators. You can specify "Notes" and "Status" in the Private Elements text box to
prevent this information from being searched by the public.

Here are key points regarding private elements:

* Private elements will not appear as field selections on the Advanced Search page unless you are logged
in as an administrator.
* The text of private elements will not be recorded in the search_texts table, and therefore will not be searched when
performing a keyword search. This is true whether or not you are logged in as an administrator.
* To search for text in private elements, an administrator can do a field search in those fields, either through the public
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
Notes
Status
```

<hr/>

#### Columns

Use the Columns option specify:
* The order of columns from left to right in search results Table View
* An alias for an elements name e.g. 'Catalog #' for the Identifier element
* The width of a column
* The alignment of column text (left, center, or right)

###### Format:
* Specify each element on a separate row.
* To specify an alias, follow the element name with a colon and then the alias name e.g. `Identifier: ID`.
* To specify a width in pixels, follow the element name and optional alias with a comma and then a number
e.g. `Identifier: ID, 120` to specify a width of 120px. 
* To specify alignment, follow the width with a comma and then the alignment e.g. `Identifier: ID, 120, right`. 

###### Column Order:

The order of columns from left to right in search results Table View is determined as follows:
* The order, first to last, in which you specify elements with the Columns option.
* For elements that are not specified in the Columns option, the order in which column names appear, top to bottom, and
left to right, in the Detail Layout option.

Note that because of the order precedence above, you cannot have columns appear in a specific order in one layout
and in a different order in another layout. The reason for this restriction is because the content for all columns
is contained in the HTML for the search results Table View; however, only the columns for the selected
layout are visible. When you select another layout, the previous layout's columns are hidden and the new layout's
columns are made visible. This is what allows instantaneous switching between layouts.

Below is an example specification for the Columns option.

```
Identifier: ID, 120, right
Title, 300, center
Type, 250, right
Subject,
Archive Volume: Volume
```

<hr/>

#### Layout Selector Width
Use this option to specify an integer indicating the width of the layout selector that appears on Table View search
results. For example, specify 250 to mean 250px. This option saves you from having to code CSS to adjust the
width to a size that is appropriate for your layout options and your theme's styling. Experiment to find a value that
makes the selector just wide enough to accommodate the longest layout you defined in the Layouts option described below.

<hr/>

#### Layouts

The Layouts option lets you specify different ways to present search results in Table Vew. The layouts you define
here will appear in the Layout Selector and on the Advanced Search page.

###### Format:
* Specify each layout on a separate row
* On each row specify: *ID*, *Name*, *'admin'* (optional), a colon, comma-separated list of columns
* The *ID* must start with 'L' followed by an integer e.g. 'L3'.  The numbers do not have to be consecutive.
* The ID 'L1' is reserved for the Detail layout described in the next section.
* Specify `admin` after the *Name* to indicate that only a logged in administrator can see and use the layout.

The purpose of the ID is to uniquely identify a layout in the query string for Table View page. You can use this query string as a link
on web pages to display search results in a specific layout. The ID ensures that those results will appear using the correct
layout even if you change the layout's *Name* or its position in the Layouts list.

Below is an example specification of Layouts.

```
L1, Summary
L2, Creator/Publisher: Identifier, Title, Creator, Publisher, Date
L3, Type/Subject: Identifier, Title, Subject, Type
L6, Confidential, admin: Identifier, Title, Status, Notes;
```

Notes about the example above:
* Each layout begins with an *ID* and *Name*
* The fourth row also specifies *'admin'*
* You don't specify columns for the L1 Layout (described in the next section), but you do specify its *Name*.
* In the example, the columns for the other layouts always begin with "Identifier, Title" so that users see those
values on every layout. Repeating these columns is a convention, but is not required.

[Learn more about layouts](http://swhplibrary.net/searching/search-results-table-view/).

<hr/>

#### L1 Detail Layout

L1 is a special layout referred to as the Detail Layout because it presents a lot of information about an item,
including a thumbnail, in a single row. Use the Detail Layout option to specify the elements which appear in the
first two columns of this layout. The third column is reserved for the Description element. A screen shot
of the detail layout appears at the top of this documentation.

In the screen shot, the last row shows Type and Subject in column one, and Address and Location in column two.
If an element has no text, it will not appear in the Detail layout. In the screen shot, the first row shows Date in
column one, but Date does not appear in the other rows because those items have no date information.


###### Format:
* Specify the column one elements in the first row
* Specify the column two elements on the second row
* Specify the elements as a comma-separated list of element names
* Use the pseudo-element `<tags>` to display an item's tags

Below is an example specification of the Detail Layout option.

```
Type, Accession Number, Subject, Date, <tags>
Creator, Publisher, Medium, Condition, Number of Pages
```

If you prefer to have only one detail column plus the Description column, specify only one row of elements.
<hr/>

#### Index View

The Index View option lets you specify a list of elements that can be used as the Index Field when choosing Index View from
the Advanced Search page. If you leave this option blank, Index View will not appear as an option on the Advanced
Search page.

Below is an example specification of the Index View option.

```
Title
Creator
Publisher
Type
```

By default, the Index View displays results in two columns. You can change it to show one column by placing the 
following CSS in your theme's style.css file. To show three columns, specify 3 instead of 1.

```
#search-index-view-headings {
	column-count: 1;
}
```

[Learn more about Index View.](http://swhplibrary.net/searching/search-results-index-view/)

<hr/>

#### Treeview

The Tree View option lets you specify a list of elements that can used as the Tree Field when choosing Tree View from
the Advanced Search page. If you leave this option blank, Tree View will not appear as an option on the Advanced
Search page.

Below is an example specification of the Tree View option.

```
Subject
Type
```

[Learn more about Tree View.](http://swhplibrary.net/searching/search-results-tree-view/)

<hr/>

#### Integer Sorting

The Integer Sorting option lets you specify a list of elements for columns that should be sorted as integers instead
of as text. This option ensures that the data in these column is sorted numerically instead of alphabetically.
For example, an alphabetic sort of `14, 116, 127, 1102` results in `1102, 116, 127, 14` because alphabetically,
the *character sequence* `1102` precedes `14`, `116`, and `127`. Likewise, the characters `14` are greater than the
first two characters in the other three numbers and thus `14` sorts last. The values of elements specified with this
option are converted to integers for sorting purposes.

Below is an example specification of the Integer Sorting option.

```
Identifier
Box #
```

Note that you can use the Integer Sorting option for elements with values that only contain integers and also for
elements with values that start with integers, but are followed by text. In that case, the text is ignored and the
sort is performed only on the integer portion of the value.

<hr/>

#### Address Sorting

> This option is only supported by the [MariaDB](https://mariadb.org/) database.
If your server is running [MySQL](https://www.mysql.com/), the AvantSearch configuration page will say the option
is not available for your installation. If you want to use this option, contact your web host to ask
about moving to a server that has MariaDB. If your server is running MariaDB and you are seeing the message
that the option is not available for your installation, you'll have to add an element named Address.

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

NOTE: If your installation does not support Address Sorting, you might consider using the Integer Sort option
so that addresses are sorted numerically.

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

<hr/>

#### Date Range
When this option is checked, Date Start and Date End text boxes will appear as filters at the bottom of the
Advanced Search page.

A user can provide values for both Date Start and Date End to limit search results to
items in that range inclusive. For example if you specify 1900 for Date Start and 1940 for Date End, the
search will find items with Date Start greater than or equal to 1900 and less than or equal to 1940. If you only
provide a value for Date Start, the search will find items where Date Start is that date or more recent.
If you only provide a value for End, the filter will find items where Date End is that date or older.

**NOTE:** If you want to use this option, but the configuration page says it's not available for your installation,
you'll need to add `Date Start` and `Date End` elements and use them exclusively to store four digit years.

[Learn more about Date Filters](http://swhplibrary.net/searching/advanced-search/) (see the Date Filters section).

<hr/>

#### Relationships View

When this option is checked, an option to show search results in Relationships View will appear on the Advanced
Search page.

**NOTE:** If you want to use this option, but the configuration page says it's not available for your installation,
you'll need to install and activate the [AvantRelationships](https://github.com/gsoules/AvantRelationships) plugin.

[Learn more about Relationships View](http://swhplibrary.net/searching/search-results-relationships-view/). 

## Copyright

* Created by [gsoules](https://github.com/gsoules) for the Southwest Harbor Public Library's [Digital Archive](http://swhplibrary.net/archive)
* Copyright George Soules, 2016-2018.
* See [LICENSE](https://github.com/gsoules/AvantRelationships/blob/master/LICENSE) for more information.

Inspiration for the [Index View](http://swhplibrary.net/searching/search-results-index-view/) and [Tree View](http://swhplibrary.net/searching/search-results-tree-view/) search results came from the alphabetized index and hierarchical list features in the [Daniel-KM / Reference](https://github.com/Daniel-KM/Reference) plugin.




