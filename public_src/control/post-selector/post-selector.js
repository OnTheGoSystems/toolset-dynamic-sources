import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { withDispatch, withSelect } from '@wordpress/data';
import { compose, withInstanceId } from '@wordpress/compose';
import { addQueryArgs } from '@wordpress/url';
import { Select } from 'toolset/block/control';
import { get, find, maxBy } from 'lodash';
import { addAction, doAction } from '@wordpress/hooks';
import './scss/edit.scss';
import ViewService from './httpService';

const { toolsetDynamicSourcesScriptData: i18n } = window;

class PostSelectorComponentClass extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			selectedPost: find(
				i18n.previewPosts,
				[ 'value', parseInt( this.props.previewPost ) ]
			),
			postUrl: i18n.cache.__current_post[ 'post-url' ],
			postId: i18n.cache.__current_post[ 'post-id' ],
		};

		if ( ! get( this.props.currentPost, 'meta.tb_preview_post', false ) ) {
			this.savePreviewPostId( this.props.currentPostId, this.props.previewPost );
		}
	}

	componentDidMount() {
		addAction(
			'tb.dynamicSources.actions.cache.updated',
			'dynamic-source-post-selector',
			this.updateBlockContentFromCache
		);
	}

	savePreviewPostId = ( ctId, previewPostId ) => {
		const service = new ViewService();
		service.savePreviewPostId( ctId, previewPostId );
	}

	updateBlockContentFromCache = () => {
		this.setState( {
			postUrl: i18n.cache.__current_post[ 'post-url' ],
			postId: i18n.cache.__current_post[ 'post-id' ],
		} );
	};

	maybeRenderUpdatingCacheSpinner = () => {
		let spinner = null;

		if ( this.props.isCacheUpdating ) {
			spinner = (
				<div className="spinner-control">
					<Spinner />
				</div>
			);
		}

		return spinner;
	};

	makePreviewUrl = () => {
		let url = this.state.postUrl;

		// If the CT doesn't contain any dynamic sources, changing the preview will not trigger a DS update and so
		// this.state.postUrl will not be updated. However, we still need to update the preview link, so we'll use the
		// guid link instead.
		if ( this.state.postId !== this.state.selectedPost.value ) {
			url = this.state.selectedPost.guid;
		}

		return addQueryArgs( url, { 'content-template-id': this.props.currentPostId } );
	};

	maybeRenderPreviewLink = () => {
		return <span className="editor-post-view">
			<a
				target={ 'wp-preview-' + this.props.currentPostId }
				rel="noopener noreferrer"
				href={ this.makePreviewUrl() }
			>
				{ __( 'View on front-end', 'wpv-views' ) }
			</a>
			{ !! this.props.hasChangedContent &&
			<small>
				{ __( '(Click Update button first to view the recent changes)', 'wpv-views' ) }
			</small>
			}
		</span>;
	};

	/**
	 * Updates dynamic source for the post type selected
	 *
	 * @param {int} postId Post ID.
	 */
	async updateDynamicSourceFromPostId( postId ) {
		const postData = await apiFetch( { path: `/toolset-dynamic-sources/v1/search-post?id=${ postId }` } );
		if ( postData && postData.length ) {
			const source = await apiFetch( { path: `toolset-dynamic-sources/v1/get-source?post_type=${ postData[ 0 ].post_type }&post_id=${ postData[ 0 ].id }` } );
			i18n.dynamicSources.__current_post = source.__current_post;
		}
	}

	render() {
		if ( ! i18n.previewPosts || i18n.previewPosts.length < 1 ) {
			return null;
		}

		const onPostSelectorChanged = ( previewPost ) => {
			this.updateDynamicSourceFromPostId( previewPost.value );
			this.setState( { selectedPost: previewPost } );
			this.props.setPreviewPost( previewPost.value );
			this.props.editPost(
				{
					meta: {
						tb_preview_post: previewPost.value,
					},
				}
			);
			this.savePreviewPostId( this.props.currentPostId, previewPost.value );

			// Trigger the updating of the Dynamic Sources cache to match the new preview post.
			doAction( 'tb.dynamicSources.actions.cache.initiateFetching', true );
		};

		const previewPostWithLargestLabel = maxBy(
			i18n.previewPosts,
			( item ) => item.label.length
		);

		const postSelectorWidth = ( i18n.previewPosts && i18n.previewPosts.length > 0 ) ?
			( 5 * previewPostWithLargestLabel.label.length ) + 120 :
			0;

		const postSelectorStyles = {
			container: styles => ( { ...styles, width: '180px' } ),
			menu: styles => ( { ...styles, width: `${ postSelectorWidth }px` } ),
		};

		return (
			<Fragment>
				<label htmlFor={ `post-selector-${ this.props.instanceId }` }>{ __( 'View with', 'wpv-views' ) }: </label>
				<Select
					id={ `post-selector-${ this.props.instanceId }` }
					value={ this.state.selectedPost }
					defaultOptions={ i18n.previewPosts }
					restInfo={
						{
							base: '/wp/v2/search',
							args: {
								search: '%s',
								type: 'post',
								// The subtype parameter is needed here in order to narrow the results down to posts of
								// the post type the Content Template is assigned to.
								subtype: i18n.previewPostTypes,
							},
						}
					}
					onChange={ onPostSelectorChanged }
					className="toolset-blocks-post-selector-control"
					styles={ postSelectorStyles }
				/>
				{ this.maybeRenderPreviewLink() }
				{ this.maybeRenderUpdatingCacheSpinner() }
			</Fragment>
		);
	}
}

const PostSelectorComponent = compose( [
	withSelect(
		( select ) => {
			const { getPreviewPost, getCacheUpdating } = select( i18n.dynamicSourcesStore );
			const { getCurrentPost, getCurrentPostId, hasChangedContent } = select( 'core/editor' );

			return {
				previewPost: getPreviewPost(),
				isCacheUpdating: getCacheUpdating(),
				currentPostId: getCurrentPostId(),
				hasChangedContent: hasChangedContent(),
				currentPost: getCurrentPost(),
			};
		}
	),
	withDispatch(
		( dispatch ) => {
			const { editPost } = dispatch( 'core/editor' );
			const { setPreviewPost } = dispatch( i18n.dynamicSourcesStore );
			return {
				setPreviewPost,
				editPost,
			};
		}
	),
	withInstanceId,
] )( PostSelectorComponentClass );
export default PostSelectorComponent;
