import { select, dispatch } from '@wordpress/data';
import { addAction, doAction } from '@wordpress/hooks';
import { fetchCache } from './fetchData';
import { isEqual } from 'lodash';

const { toolsetDynamicSourcesScriptData: i18n } = window;

const INTERVAL = 30000; // in milliseconds/

const initiateCacheFetching = async( force ) => {
	const coreEditorStore = select( 'core/editor' );
	const coreBlockEditorStore = select( 'core/block-editor' );
	// The core editor blocks object is getting flattened and on the generated sting, instances of the "toolset-blocks/"
	// substring are being tracked to indicate the any Toolset Block is used in the current instance of the editor.
	const editorBlocksStringified = JSON.stringify( coreBlockEditorStore.getBlocks() );
	const editorHasToolsetBlocks = !! editorBlocksStringified.includes( 'dynamic' );
	const editorHasFieldBlocks = !! editorBlocksStringified.includes( 'toolset-blocks/field' );
	const editorHasRepeatingFieldBlocks = !! editorBlocksStringified.includes( 'toolset-blocks/repeating-field' );
	const editorHasFieldsAndTextBlocks = !! editorBlocksStringified.includes( 'toolset-blocks/fields-and-text' );

	// If no Toolset Blocks are included in the editor, there is no reason to update tha cache.
	if (
		! (
			force ||
			editorHasToolsetBlocks ||
			editorHasFieldsAndTextBlocks ||
			editorHasFieldBlocks ||
			editorHasRepeatingFieldBlocks
		) ||
		select( i18n.dynamicSourcesStore ).getCacheUpdating()
	) {
		return;
	}

	const postID = select( i18n.dynamicSourcesStore ).getPreviewPost() || coreEditorStore.getCurrentPostId();
	dispatch( i18n.dynamicSourcesStore ).setCacheUpdating( true );
	const post = await fetchCache( postID );
	dispatch( i18n.dynamicSourcesStore ).setCacheUpdating( false );
	doAction( 'tb.dynamicSources.actions.cache.tick', post );
};

const addCacheUpdateHook = () => {
	addAction(
		'tb.dynamicSources.actions.cache.tick',
		'toolset-blocks',
		cache => {
			let cacheShouldUpdate = false;
			if ( !! cache && ! isEqual( cache, i18n.cache ) ) {
				// Post selector cache is updated in another action, so cache can't be assigned but merged
				i18n.cache = Object.assign( {}, i18n.cache, cache );
				cacheShouldUpdate = true;
			}

			if ( !! i18n.cacheForViews ) {
				cacheShouldUpdate = true;
			}

			if ( !! cacheShouldUpdate ) {
				doAction( 'tb.dynamicSources.actions.cache.updated' );
			}
		}
	);
};

const addCacheFetchingInitiationHook = () => {
	addAction(
		'tb.dynamicSources.actions.cache.initiateFetching',
		'toolset-blocks',
		( force = false ) => {
			initiateCacheFetching( force );
		}
	);
};

const initCache = () => {
	addCacheUpdateHook();

	addCacheFetchingInitiationHook();

	window.setInterval(
		() => {
			doAction( 'tb.dynamicSources.actions.cache.initiateFetching' );
		},
		INTERVAL
	);
};

export { initCache };
