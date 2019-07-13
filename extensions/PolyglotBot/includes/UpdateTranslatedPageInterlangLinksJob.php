<?php

use MediaWiki\Revision\SlotRecord;

class UpdateTranslatedPageInterlangLinksJob extends Job
{
    public function __construct($title, $params)
    {
        parent::__construct('updateTranslatedPageInterlangLinks', $title, $params);
    }

    /**
     * Execute the job
     *
     * @return bool
     */
    public function run()
    {
        global $wgUser;

        // Load data from $this->params and $this->title
        $interlangLinks = $this->params['interlangLinks'];
        $sourcePageTitle = $this->params['sourcePageTitle'];

        $article = new Article( $this->title, 0 );
        $text = $article->getPage()->getContent()->getText();

        preg_match("/<!-- BEGIN interlang links -->.*<!-- END interlang links -->/sm", $text, $matches);

        if(count($matches) !== 0 && $matches[0] !== $interlangLinks) {
            $updatedPageText = str_replace($matches[0], $interlangLinks, $text);

            $wgUser = User::newSystemUser( 'PolyglotBot', [ 'steal' => true ] );
            $content = ContentHandler::makeContent( $updatedPageText, $this->title );

            $page = WikiPage::factory( $this->title );
            $updater = $page->newPageUpdater( $wgUser );

            $slot = SlotRecord::MAIN;
            $summary = "Update interlang links (links to translated versions of this page in other languages) ";
            $summary .= "triggered by origin English page \"" . $sourcePageTitle . "\" update.";

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
        }

        return true;
    }
}
