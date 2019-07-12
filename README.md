# dolibarrMediaWikiMigration
Some maintenance scripts and extensions useful for conducting MediaWiki migration

This repo contain 
* 2 migration scripts
* 1 maintenance script
* 1 extension

## Scripts

### Migration scripts

#### maintenance/migrateFromMetaKeywordsTagToAddHTMLMetaAndTitle.php

This script search for pages that includes pattern like `<keywords content` and replace with `<seo metak`.    
This allow to migrate from old **MetaKeywordsTag** extension to new **AddHTMLMetaAndTitle** extension.
Edit are conducted by a bot user named TaggerArtistBot and marked as minor.

#### maintenance/migrateFromMultiLanguageManagerToInterlanguagesPageLinks.php

This script use data in tables from old extension **Multi Language Manager** to transition to MediaWiki native Interlanguage links.    
Links to the translated pages are added on all source (English pages) as well as on the translated pages.    
Edit are conducted by a bot user named PolyglotBot and marked as minor.

### Maintenance script

#### maintenance/SyncPageLangFieldWithLanglinksTable.php

This script use data in the **langlinks** MediaWiki table (interlanguage links) to see if some translated pages are marked erroneously as English language. If this is the case, the script change the language of the page to the one used on the interlanguage links.

## Extension

### extensions/PolyglotBot

This extension keep the interlangage links from English pages in sync with the translated pages.    
When interlangage links are changed on English pages, updated links are reflected on the translated pages.   

#### How does it work ?

* When a user save a page, MediaWiki `PageContentSaveComplete` hook is triggered.
* PolyglotBot extension listen to this hook and get the latest edited content from the page.
* Using a regular expression, PolyglotBot tries to find the block
```
<!-- BEGIN origin interlang links -->
.*
<!-- END interlang links -->
```
* If the extension finds the block, it deduce that an English page has been changed.
* Extracting the different linked translated pages, PolyglotBot build the new block below for each translation
```
<!-- BEGIN interlang links -->
.*
<!-- END interlang links -->
```
* The extension then compare for each translated page if the block has been changed on the source page and needs to be updated.
* If this is the case, the block is updated.

NB : English pages interlang links comment starts with <pre><!-- BEGIN <strong>origin</strong> interlang</pre> whereas translated page starts with <pre><!-- BEGIN interlang</pre> This allow PolyglotBot to distinguish English origin content from translated content.

NB : Interlangage links on each translated page are updated using MediaWiki Job queue mecanism to avoid adding burden to page edits : refer to https://www.mediawiki.org/wiki/Manual:Job_queue/For_developers for more info.
```
