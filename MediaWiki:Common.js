/* Any JavaScript here will be loaded for all users on every page load. */

var customizeToolbar = function () {
	$( '#wpTextbox1' ).wikiEditor( 'addToToolbar', {
	'section': 'advanced',
	'group': 'insert',
	'tools': {
		'interlang': {
			label: 'Insert interlang template.', // or use labelMsg for a localized label, see above
			type: 'button',
			icon: 'textLanguage',
			action: {
				type: 'encapsulate',
				options: {
					pre: "<!-- BEGIN origin interlang links -->\n\
<!-- You can edit this section but do NOT remove these comments\n\
     Links below will be automatically replicated on translated pages by PolyglotBot -->\n\
[[lang:Page_title]]\n\
<!-- END interlang links -->" // text to be inserted
				}
			}
		}
	}
} );
};

function makeInsertInterlangLinksTool() {
	ve.ui.commandRegistry.register( new ve.ui.Command(
		// Command name
		'insertInterlangLinks',
		// Type and name of the action to execute
		'content', 'insert', // Calls the ve.ui.ContentAction#insert method
		{
			// Extra arguments for the action
			args: [
				// Content to insert
				"<!-- BEGIN origin interlang links -->\n\
<!-- You can edit this section but do NOT remove these comments\n\
     Links below will be automatically replicated on translated pages by PolyglotBot -->\n\
[[lang:Page_title]]\n\
<!-- END interlang links -->",
				// Annotate content to match surrounding annotations?
				true,
				// Move cursor to after the new content? (otherwise - select it)
				true
			],
			supportedSelections: [ 'linear' ]
		}
	));

	ve.ui.InsertInterlangLinksTool = function VeUiInsertInterlangLinksTool() {
		ve.ui.InsertInterlangLinksTool.super.apply( this, arguments );
	};
	OO.inheritClass( ve.ui.InsertInterlangLinksTool, ve.ui.Tool );
	
	var title = "Interlanguage links template";
	if (OO.ui.getUserLanguages()[0] === 'fr') {
		title = "Modèle liens interlangues";
	}

	ve.ui.InsertInterlangLinksTool.static.name = 'insertInterlangLinks';
	ve.ui.InsertInterlangLinksTool.static.group = 'insert';
	ve.ui.InsertInterlangLinksTool.static.title = title;
	ve.ui.InsertInterlangLinksTool.static.icon = 'textLanguage';
	ve.ui.InsertInterlangLinksTool.static.commandName = 'insertInterlangLinks';
	ve.ui.toolFactory.register( ve.ui.InsertInterlangLinksTool );
}

// Initialize
mw.loader.using( 'ext.visualEditor.desktopArticleTarget.init' ).then( function () {
	mw.libs.ve.addPlugin( function () {
		return mw.loader.using( [ 'ext.visualEditor.core' ] )
			.then( function () {
				makeInsertInterlangLinksTool();
			} );
	} );
} );
