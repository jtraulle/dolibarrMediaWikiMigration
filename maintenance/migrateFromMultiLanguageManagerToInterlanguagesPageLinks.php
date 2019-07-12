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


class AddInterlangLinks extends Maintenance {
    public function execute() {
        global $wgUser;

        // We get the existing links from Multi Language Manager extension table
        $sqlQuery = "SELECT 
                     sp.page_title AS sp_title, 
                     spt.old_text AS sp_text, 
                     lang AS dp_lang, 
                     dp.page_lang AS dp_effective_lang, 
                     dp.page_title AS dp_title, 
                     dpt.old_text AS dp_text	
                     FROM page_translation
                     INNER JOIN page_language ON page_translation.translate = page_language.page_id
                     INNER JOIN page sp ON page_translation.source = sp.page_id
                     INNER JOIN page dp ON page_language.page_id = dp.page_id
                     INNER JOIN revision spr ON sp.page_latest = spr.rev_id INNER JOIN text spt ON spr.rev_text_id = spt.old_id
                     INNER JOIN revision dpr ON dp.page_latest = dpr.rev_id INNER JOIN text dpt ON dpr.rev_text_id = dpt.old_id
                     WHERE spt.old_text NOT LIKE '%<!-- BEGIN interlang links -->%';";
        $this->output( "Searching for pages that needs to be updated ...\n" );
                exec("php maintenance/sql.php --json --query \"$sqlQuery\"", $output);
                $arrayOutput = json_decode(implode($output, PHP_EOL), TRUE);

        $pagesToProcess = array();
        foreach( $arrayOutput as $record) {
            $pagesToProcess[$record['sp_title']]['sp_title'] = $record['sp_title'];
            $pagesToProcess[$record['sp_title']]['sp_text'] = $record['sp_text'];
            $pagesToProcess[$record['sp_title']]['languages'][$record['dp_lang']]['dp_effective_lang'] = $record['dp_effective_lang'];
            $pagesToProcess[$record['sp_title']]['languages'][$record['dp_lang']]['dp_title'] = $record['dp_title'];
            $pagesToProcess[$record['sp_title']]['languages'][$record['dp_lang']]['dp_text'] = $record['dp_text'];
        }

        if (count($pagesToProcess) !== 0) {
            foreach ($pagesToProcess as $pageInfo) {
                $this->updatePage($pageInfo, 'en', $pageInfo['sp_title'], $pageInfo['sp_text']);

                foreach ($pageInfo['languages'] as $lang_key => $subPageInfo) {
                    $this->updatePage($pageInfo, $lang_key, $subPageInfo['dp_title'], $subPageInfo['dp_text']);
                }
            }

            $this->output( "Done. \n" );
        } else {
            $this->output( "Done (nothing to do). \n" );
        }
    }

    private function updatePage($data, $lang, $pageTitle, $originalPageText)
    {
        $updatedPageText = AddInterlangLinks::prepareLinksForLang($data, $lang) . $originalPageText;
        $wgUser = User::newSystemUser( 'PolyglotBot', [ 'steal' => true ] );
        $title = Title::newFromText($pageTitle);
        $content = ContentHandler::makeContent( $updatedPageText, $title );

        $page = WikiPage::factory( $title );
        $updater = $page->newPageUpdater( $wgUser );

        $slot = SlotRecord::MAIN;
        $summary = "Import interlang links (links to translated versions of this page in other languages) ";

        if ($lang === 'en') {
            $summary .= 'from Multi Language Manager table.';
        } else {
            var_dump($content);
            $summary .= "from origin English page \"" . $data['sp_title'] . "\".";
        }

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

        if ($lang === 'en') {
            $this->output( "Original English page \"" . $pageTitle . "\" processed. \n" );
        } else {
            $this->output( "  └── " . $lang . " translated page \"" . $pageTitle . "\" processed. \n" );
        }
    }

    static function prepareLinksForLang($data, $lang)
    {
        $builtInterlangLinks = "";

        if ($lang !== 'en') {
            $builtInterlangLinks .= '<!-- BEGIN interlang links -->' . PHP_EOL;
            $builtInterlangLinks .= '<!-- Do NOT edit this section' . PHP_EOL;
            $builtInterlangLinks .= '     Links below are automatically managed by PolyglotBot' . PHP_EOL;
            $builtInterlangLinks .= '     You can edit links on the English source page : ' . $data['sp_title'] . ' -->' . PHP_EOL;
            $en = array(
                'en' => array(
                    'dp_effective_lang' => 'en',
                    'dp_title' => $data['sp_title']
                )
            );

            $data['languages'] = $en + $data['languages'];
            unset($data['languages'][$lang]);
        } else {
            $builtInterlangLinks .= '<!-- BEGIN interlang links -->' . PHP_EOL;
            $builtInterlangLinks .= '<!-- You can edit this section but do NOT remove these comments' . PHP_EOL;
            $builtInterlangLinks .= '     Links below will be automatically replicated on translated pages by PolyglotBot -->' . PHP_EOL;
        }

        foreach ($data['languages'] as $lang_key => $fields) {
            $builtInterlangLinks .= '[[' . $lang_key . ':' . $fields['dp_title'] . ']]' .PHP_EOL;
        }

        $builtInterlangLinks .= '<!-- END interlang links -->' . PHP_EOL . PHP_EOL;
        return $builtInterlangLinks;
    }
}

$maintClass = AddInterlangLinks::class;

require_once RUN_MAINTENANCE_IF_MAIN;
