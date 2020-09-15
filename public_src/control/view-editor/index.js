// External dependencies
import { Component } from '@wordpress/element';
import { addAction, addFilter, doAction } from '@wordpress/hooks';
import { select, subscribe } from '@wordpress/data';
import { includes, merge, isEqual } from 'lodash';

// Internal dependencies
const { toolsetDynamicSourcesScriptData: i18n, Toolset } = window;
import isCustomPostProvider from '../../utils/is-custom-post-provider';

class ViewEditor extends Component {
	postTypes = [];
	previewPostId = null;
	viewStoreIds = [];
	random = null;
	viewId = null;

	constructor( props = {} ) {
		super( props );

		addAction(
			'tb.dynamicSources.actions.cache.initiateFetching',
			'toolset-blocks',
			() => {
				const editorBlocks = select( 'core/block-editor' ).getBlocks();
				editorBlocks
					.filter( block => block.name === 'toolset-views/view-editor' )
					.forEach(
						( viewEditorBlock ) => {
							const viewReduxStoreId = viewEditorBlock.attributes.reduxStoreId;
							const viewGeneralSettings = select( viewReduxStoreId ).getGeneral();
							const viewContentSelectionSettings = select( viewReduxStoreId ).getContentSelection();
							if ( 'posts' === viewContentSelectionSettings.query_type ) {
								doAction( 'tb.dynamicSources.action.updateSources', viewContentSelectionSettings.post_type, viewGeneralSettings.preview_post_id, viewReduxStoreId );
								doAction( 'toolset.views.updatePreview', viewReduxStoreId, true );
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
				this.viewId = select( viewStoreId ).getGeneral().id;

				// A random value is always passed as an argument to the store's selector in order to always invoke the
				// relevant resolver. The "data" package seems to be doing some kind of caching when it comes to resolvers
				// with argument values that have already been resolved, To bypass this a random argument needs to be fed in,
				// to fake an argument set change.
				select( i18n.dynamicSourcesStore ).getDynamicSources(
					this.postTypes,
					this.previewPostId,
					this.random,
					this.viewId
				);
			}
		);

		addAction(
			'tb.dynamicSources.actions.providers.customPostLoaded',
			'toolset-blocks',
			( providerId, providerLabel ) => {
				if ( !! this.previewPostId ) {
					if ( ! i18n.postProvidersForViews[ this.previewPostId ] ) {
						i18n.postProvidersForViews[ this.previewPostId ] = [];
					}

					if ( ! i18n.postProvidersForViews[ this.previewPostId ].find( item => item.value === providerId ) ) {
						i18n.postProvidersForViews[ this.previewPostId ].push( { value: providerId, label: providerLabel } );
					}
				}
			}, 10, 3
		);

		addAction(
			'tb.dynamicSources.actions.source.customPostLoaded',
			'toolset-blocks',
			( providerId, source ) => {
				if ( !! this.previewPostId ) {
					if ( ! i18n.dynamicSourcesForViews[ this.previewPostId ] ) {
						i18n.dynamicSourcesForViews[ this.previewPostId ] = {};
					}

					if ( ! i18n.dynamicSourcesForViews[ this.previewPostId ][ providerId ] ) {
						i18n.dynamicSourcesForViews[ this.previewPostId ][ providerId ] = source.__current_post;
					}
				}
			}, 10, 2
		);

		addAction(
			'tb.dynamicSources.actions.cache.customPostLoaded',
			'toolset-blocks',
			( providerId, cache ) => {
				if ( !! this.previewPostId ) {
					if ( ! i18n.cacheForViews[ this.previewPostId ] ) {
						i18n.cacheForViews[ this.previewPostId ] = {};
					}

					if ( ! i18n.cacheForViews[ this.previewPostId ][ providerId ] ) {
						i18n.cacheForViews[ this.previewPostId ][ providerId ] = cache.__current_post;
					}
				}
			}, 10, 2
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

			const dynamicSourcesInfo = select( i18n.dynamicSourcesStore ).getDynamicSources(
				this.postTypes,
				this.previewPostId,
				this.random,
				this.viewId
			);

			if ( ! dynamicSourcesInfo ) {
				return;
			}

			if ( !! dynamicSourcesInfo.previewPostId ) {
				const dynamicSourcesForViews = {},
					postProvidersForViews = {},
					cacheForViews = {};

				postProvidersForViews[ dynamicSourcesInfo.previewPostId ] = this.maybeAppendPostProvidersForCustomPost( dynamicSourcesInfo.postProviders );
				dynamicSourcesForViews[ dynamicSourcesInfo.previewPostId ] = this.maybeAppendDynamicSourcesForCustomPost( dynamicSourcesInfo.dynamicSources );
				cacheForViews[ dynamicSourcesInfo.previewPostId ] = this.maybeAppendCacheForCustomPost( dynamicSourcesInfo.cache );

				if (
					! isEqual( i18n.postProvidersForViews, postProvidersForViews ) ||
					! isEqual( i18n.dynamicSourcesForViews, dynamicSourcesForViews )
				) {
					i18n.postProvidersForViews = merge( i18n.postProvidersForViews, postProvidersForViews );
					i18n.dynamicSourcesForViews = merge( i18n.dynamicSourcesForViews, dynamicSourcesForViews );

					doAction( 'tb.dynamicSources.actions.sourceAndProvidersForViewsUpdated' );
				}

				let shouldSendCacheTick = false;
				const newCacheForViews = { ...i18n.cacheForViews, ...cacheForViews };
				if ( JSON.stringify( newCacheForViews ) !== JSON.stringify( i18n.cacheForViews ) ) {
					shouldSendCacheTick = true;
					i18n.cacheForViews = newCacheForViews;

					// Triggering the View preview update for all the Views which have the same "previewPostId".
					this.viewStoreIds[ dynamicSourcesInfo.previewPostId ].forEach(
						function( storeId ) {
							doAction( 'toolset.views.updatePreview', storeId );
						}
					);
				}

				if ( this.previewPostId !== dynamicSourcesInfo.previewPostId ) {
					// This is not a solution, it is a workaround until it is really fixed
					// shouldSendCacheTick = true;
				}

				if ( shouldSendCacheTick ) {
					doAction( 'tb.dynamicSources.actions.cache.tick' );
				}
			}
		} );
	}

	maybeAppendPostProvidersForCustomPost = ( postProviders ) => {
		const customPostProviders = i18n.postProviders.filter(
			postProvider => {
				return isCustomPostProvider( postProvider.value ) && '__custom_post' !== postProvider.value;
			}
		);

		const newPostProviders = [ ...postProviders, ...customPostProviders ];
		// Removing duplicate entries...
		return newPostProviders.filter(
			( item, index ) => newPostProviders.indexOf( item ) === index
		);
	};

	maybeAppendDynamicSourcesForCustomPost = ( dynamicSources ) => {
		const customPostDynamicSources = Object.keys( i18n.dynamicSources )
			.filter(
				dynamicSourceKey => {
					return isCustomPostProvider( dynamicSourceKey ) && '__custom_post' !== dynamicSourceKey;
				}
			)
			.reduce(
				( obj, key ) => {
					obj[ key ] = i18n.dynamicSources[ key ];
					return obj;
				},
				{}
			);

		return merge( dynamicSources, customPostDynamicSources );
	};

	maybeAppendCacheForCustomPost = ( cache ) => {
		const customPostCache = Object.keys( i18n.cache )
			.filter(
				cacheKey => {
					return isCustomPostProvider( cacheKey ) && '__custom_post' !== cacheKey;
				}
			)
			.reduce(
				( obj, key ) => {
					obj[ key ] = i18n.cache[ key ];
					return obj;
				},
				{}
			);
		return merge( cache, customPostCache );
	};

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
		const { getBlock, getBlockRootClientId } = select( 'core/block-editor' );
		let result = null,
			currentBlockId = blockId;

		do {
			currentBlockId = getBlockRootClientId( currentBlockId );
			if ( !! currentBlockId ) {
				const block = getBlock( currentBlockId );
				if ( includes( [ 'toolset-views/view-editor', 'toolset-views/wpa-editor' ], block.name ) ) {
					result = block;
				}
			}
		} while ( !! currentBlockId );

		return result;
	};
}

export default ViewEditor;
