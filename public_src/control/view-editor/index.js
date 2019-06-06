import { Component } from '@wordpress/element';
import { addAction, addFilter, doAction } from '@wordpress/hooks';
import { select, subscribe } from '@wordpress/data';

const { toolsetDynamicSourcesScriptData: i18n, Toolset } = window;

class ViewEditor extends Component {
	postTypes = [];
	previewPostId = null;
	viewStoreIds = [];
	random = null;

	constructor( props = {} ) {
		super( props );

		addAction(
			'tb.dynamicSources.actions.cache.initiateFetching',
			'toolset-blocks',
			() => {
				const editorBlocks = select( 'core/editor' ).getBlocks();
				editorBlocks
					.filter( block => block.name === 'toolset-views/view-editor' )
					.forEach(
						( viewEditorBlock ) => {
							const viewReduxStoreId = viewEditorBlock.attributes.reduxStoreId;
							const viewGeneralSettings = select( viewReduxStoreId ).getGeneral();
							const viewContentSelectionSettings = select( viewReduxStoreId ).getContentSelection();
							if ( 'posts' === viewContentSelectionSettings.query_type ) {
								doAction( 'tb.dynamicSources.action.updateSources', viewContentSelectionSettings.post_type, viewGeneralSettings.preview_post_id, viewReduxStoreId );
							}
						}
					);
			}
		);

		addFilter(
			'tb.dynamicSources.filters.adjustPreviewPostID',
			'toolset-blocks',
			( previewPostID, clientId ) => {
				const parentViewBlock = this.maybeGetParentViewBlock( clientId );
				if ( !! parentViewBlock ) {
					const viewGeneralSettings = select( parentViewBlock.attributes.reduxStoreId ).getGeneral();
					return viewGeneralSettings.preview_post_id;
				}
				return previewPostID;
			}
		);

		addFilter(
			'tb.dynamicSources.filters.adjustSourcesForBlocksInsideViews',
			'toolset-blocks',
			( dynamicSources, clientId ) => {
				return this.blockInsideViewAdjustmentCallback( dynamicSources, clientId, 'dynamicSources' );
			}
		);

		addFilter(
			'tb.dynamicSources.filters.adjustProvidersForBlocksInsideViews',
			'toolset-blocks',
			( providers, clientId ) => {
				return this.blockInsideViewAdjustmentCallback( providers, clientId, 'postProviders' );
			}
		);

		addAction(
			'tb.dynamicSources.action.updateSources',
			'toolset-blocks',
			( postTypes, previewPostId, viewStoreId ) => {
				this.postTypes = postTypes;
				this.random = Math.random();
				this.previewPostId = previewPostId;
				// There might be cases where inside the same page, multiple Views listing the same post type (and having the
				// same "previewPostId", this is why they viewStoreIds are saved in an array classified by previewPostId.
				if ( !! this.viewStoreIds[ previewPostId ] && ! this.viewStoreIds[ previewPostId ].includes( viewStoreId ) ) {
					this.viewStoreIds[ previewPostId ] = [ ...this.viewStoreIds[ previewPostId ], viewStoreId ];
				} else {
					this.viewStoreIds[ previewPostId ] = [ viewStoreId ];
				}

				// A random value is always passed as an argument to the store's selector in order to always invoke the
				// relevant resolver. The "data" package seems to be doing some kind of caching when it comes to resolvers
				// with argument values that have already been resolved, To bypass this a random argument needs to be fed in,
				// to fake an argument set change.
				select( i18n.dynamicSourcesStore ).getDynamicSources( this.postTypes, this.previewPostId, this.random );
			}
		);

		Toolset.hooks.addFilter(
			'wpv-filter-wpv-shortcodes-gui-post-id-for-dialog-create',
			( postId ) => {
				return this.previewPostId || postId;
			}
		);

		subscribe( () => {
			if ( this.postTypes.length <= 0 ) {
				return;
			}

			const dynamicSourcesInfo = select( i18n.dynamicSourcesStore ).getDynamicSources( this.postTypes, this.previewPostId, this.random );

			if ( ! dynamicSourcesInfo ) {
				return;
			}

			if ( !! dynamicSourcesInfo.previewPostId ) {
				const dynamicSourcesForViews = {},
					postProvidersForViews = {},
					cacheForViews = {};

				postProvidersForViews[ dynamicSourcesInfo.previewPostId ] = dynamicSourcesInfo.postProviders;
				dynamicSourcesForViews[ dynamicSourcesInfo.previewPostId ] = dynamicSourcesInfo.dynamicSources;
				cacheForViews[ dynamicSourcesInfo.previewPostId ] = dynamicSourcesInfo.cache;

				i18n.postProvidersForViews = { ...i18n.postProvidersForViews, ...postProvidersForViews };
				i18n.dynamicSourcesForViews = { ...i18n.dynamicSourcesForViews, ...dynamicSourcesForViews };

				const newCacheForViews = { ...i18n.cacheForViews, ...cacheForViews };
				if ( JSON.stringify( newCacheForViews ) !== JSON.stringify( i18n.cacheForViews ) ) {
					doAction( 'tb.dynamicSources.actions.cache.tick' );
					i18n.cacheForViews = newCacheForViews;

					// Triggering the View preview update for all the Views which have the same "previewPostId".
					this.viewStoreIds[ dynamicSourcesInfo.previewPostId ].forEach(
						function( storeId ) {
							doAction( 'toolset.views.updatePreview', storeId );
						}
					);
				}
			}
		} );
	}

	blockInsideViewAdjustmentCallback = ( value, clientId, subject ) => {
		const parentViewBlock = this.maybeGetParentViewBlock( clientId );

		if ( !! parentViewBlock ) {
			const viewGeneralSettings = select( parentViewBlock.attributes.reduxStoreId ).getGeneral();
			const previewPostId = viewGeneralSettings.preview_post_id;

			if (
				!! i18n[ `${ subject }ForViews` ] &&
				!! i18n[ `${ subject }ForViews` ][ previewPostId ]
			) {
				return i18n[ `${ subject }ForViews` ][ previewPostId ];
			}
		}

		return value;
	};

	maybeGetParentViewBlock = ( blockId ) => {
		const { getBlock, getBlockRootClientId } = select( 'core/editor' );
		let result = null,
			currentBlockId = blockId;

		do {
			currentBlockId = getBlockRootClientId( currentBlockId );
			if ( !! currentBlockId ) {
				const block = getBlock( currentBlockId );
				if ( block.name === 'toolset-views/view-editor' ) {
					result = block;
				}
			}
		} while ( !! currentBlockId );

		return result;
	};
}

export default ViewEditor;
