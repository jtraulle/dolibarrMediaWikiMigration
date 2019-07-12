<?php

/**
 * To the extent possible under law,  I, Jean Traullé, have waived all copyright and
 * related or neighboring rights to Hello World. This work is published from the
 * United States.
 *
 * @copyright CC0 http://creativecommons.org/publicdomain/zero/1.0/
 * @author Jean Traullé   <jean@opencomp.fr>
 * @ingroup Maintenance
 */

use MediaWiki\Revision\SlotRecord;

require_once __DIR__ . '/Maintenance.php';


class MigrateFromMetaKeywordsTagToAddHTMLMetaAndTitle extends Maintenance {
        public function execute() {
                global $wgUser;

                // We get the page ids of pages where the latest page content match the pattern <keywords content
                $sqlQuery = "SELECT page_title, old_text \
                             FROM page \
                             INNER JOIN revision ON page.page_latest = revision.rev_id \
                             INNER JOIN text ON revision.rev_text_id = text.old_id \
                             WHERE text.old_text LIKE '%<keywords content%' \
			     AND page.page_namespace = 0;";
		$this->output( "Searching for pages that needs to be updated ..." );
                $jsonPageIdsToAlter = exec("php maintenance/sql.php --json --query \"$sqlQuery\"", $output);
                $sqlResultAsArray = json_decode(implode($output), TRUE);
                $pageIdsAsArrayKeys = array_column($sqlResultAsArray, 'page_title');
                $textAsArrayValues = array_column($sqlResultAsArray, 'old_text');
                $pagesToProcess = array_combine($pageIdsAsArrayKeys, $textAsArrayValues);

		if(count($pagesToProcess) === 0) {
			$this->output( "Done (nothing to do). \n" );
		} else {
		        foreach ($pagesToProcess as $pageTitle => $pageText) {
		                $updatedPageText = str_replace("<keywords content", "<seo metak", $pageText);
		                $wgUser = User::newSystemUser( 'TaggerArtistBot', [ 'steal' => true ] );
		                $title = Title::newFromText($pageTitle);
		                $content = ContentHandler::makeContent( $updatedPageText, $title );

		                $page = WikiPage::factory( $title );
		                $updater = $page->newPageUpdater( $wgUser );

		                $slot = SlotRecord::MAIN;
				$summary = "Replacing \"<keywords content\" tag by new \"<seo metak\" tag after migrating from MetaKeywordsTag extension to AddHTMLMetaAndTitle extension.";
		                $minor = TRUE;
		                $bot = TRUE;
		                $autoSummary = FALSE;
		                $noRC = FALSE;

		                $flags = ( $minor ? EDIT_MINOR : 0 ) |
		                        ( $bot ? EDIT_FORCE_BOT : 0 ) |
		                        ( $autoSummary ? EDIT_AUTOSUMMARY : 0 ) |
		                        ( $noRC ? EDIT_SUPPRESS_RC : 0 );

		                $updater->setContent( $slot, $content );
		                $updater->saveRevision( CommentStoreComment::newUnsavedComment( $summary ), $flags );
		                $this->output( "Page \"" . $pageTitle . "\" processed. \n" );
		        }
		}
        }
}

$maintClass = MigrateFromMetaKeywordsTagToAddHTMLMetaAndTitle::class;

require_once RUN_MAINTENANCE_IF_MAIN;
