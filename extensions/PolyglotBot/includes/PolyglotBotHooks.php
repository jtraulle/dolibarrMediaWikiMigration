<?php /** @noinspection PhpUnusedParameterInspection */

class PolyglotBotHooks
{

    /**
     * Handler for PageContentSaveComplete hook
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
     *
     * @param WikiPage $wikiPage modified WikiPage
     * @param User &$user User who edited
     * @param Content $content New article text
     * @param string $summary Edit summary
     * @param bool $minoredit Minor edit or not
     * @param bool $watchthis Watch this article?
     * @param string $sectionanchor Section that was edited
     * @param int &$flags Edit flags
     * @param Revision $revision Revision that was created
     * @param Status &$status
     * @param int $baseRevId
     * @param int $undidRevId
     *
     * @return bool true in all cases
     */
    public static function onPageContentSaveComplete(
        WikiPage $wikiPage,
        &$user,
        $content,
        $summary,
        $minoredit,
        $watchthis,
        $sectionanchor,
        &$flags,
        $revision,
        &$status,
        $baseRevId,
        $undidRevId = 0
    )
    {
        global $wgEchoNotifications;
        if (!$revision) {
            return true;
        }
        // unless status is "good" (not only ok, also no warnings or errors), we
        // probably shouldn't process it at all (e.g. null edits)
        if (!$status->isGood()) {
            return true;
        }
        $spTitle = $wikiPage->getTitle()->getDBkey();

        preg_match("/<!-- BEGIN origin interlang links -->.*<!-- END interlang links -->/sm", $content->getText(), $matches);

        if (count($matches) !== 0) {

            preg_match("/\[\[.*\]\]/sm", $matches[0], $links);

            $langLinks = explode(PHP_EOL, $links[0]);

            $langsAndTitles = array();
            foreach ($langLinks as $langLink) {
                $langAndTitle = explode(':', rtrim(ltrim($langLink, '['), ']'));
                $langsAndTitles[$langAndTitle[0]] = $langAndTitle[1];
            }

            foreach ($langsAndTitles as $langKey => $dpTitle) {
                $interlangLinks = PolyglotBotHooks::prepareLinksForLang($langsAndTitles, $langKey, $spTitle);

                $jobParams = [ 'interlangLinks' => $interlangLinks, 'sourcePageTitle' => $spTitle ];
                $title = Title::newFromText( $dpTitle );
                $job = new UpdateTranslatedPageInterlangLinksJob( $title, $jobParams );
                JobQueueGroup::singleton()->push( $job );
            }
        }

        return true;
    }

    private static function prepareLinksForLang($data, $lang, $sourcePageTitle)
    {
        $builtInterlangLinks = '<!-- BEGIN interlang links -->' . PHP_EOL;
        $builtInterlangLinks .= '<!-- Do NOT edit this section' . PHP_EOL;
        $builtInterlangLinks .= '     Links below are automatically managed by PolyglotBot' . PHP_EOL;
        $builtInterlangLinks .= '     You can edit links on the English source page : ' . $sourcePageTitle . ' -->' . PHP_EOL;
        $en = array(
            'en' => $sourcePageTitle
        );

        $data = $en + $data;
        unset($data[$lang]);


        foreach ($data as $lang_key => $title) {
            $builtInterlangLinks .= '[[' . $lang_key . ':' . $title . ']]' .PHP_EOL;
        }

        $builtInterlangLinks .= '<!-- END interlang links -->';
        return $builtInterlangLinks;
    }
}
