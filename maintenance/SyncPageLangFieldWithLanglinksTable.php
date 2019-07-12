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

require_once __DIR__ . '/Maintenance.php';

class SyncPageLangFieldWithLanglinksTable extends Maintenance {
    public function execute() {
        global $wgUser;

        // We get the existing links from Multi Language Manager extension table
        $sqlQuery = "SELECT DISTINCT ll_lang, ll_title, page_lang
                     FROM langlinks
                     INNER JOIN page ON langlinks.ll_title = page.page_title AND page.page_namespace = 0
                     WHERE page_lang IS NULL;";
        $this->output( "Searching for pages where page_lang needs to be updated ...\n" );
        exec("php maintenance/sql.php --json --query \"$sqlQuery\"", $output);
        $arrayOutput = json_decode(implode($output, PHP_EOL), TRUE);

        if (count($arrayOutput) !== 0) {
            foreach ($arrayOutput as $record) {
                var_dump(SpecialPageLanguage::changePageLanguage(
                    new ApiMain(),
                    Title::newFromText($record['ll_title']),
                    $record['ll_lang'],
                    $params['reason'] ?? '',
                    $params['tags'] ?: []
                ));
            }

            $this->output( "Done. \n" );
        } else {
            $this->output( "Done (nothing to do). \n" );
        }
    }
}

$maintClass = SyncPageLangFieldWithLanglinksTable::class;

require_once RUN_MAINTENANCE_IF_MAIN;
