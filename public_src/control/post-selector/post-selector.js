import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { compose, withInstanceId } from '@wordpress/compose';
import { addQueryArgs } from '@wordpress/url';
import Select from 'react-select';
import { find, maxBy } from 'lodash';
import { addAction } from '@wordpress/hooks';
import './scss/edit.scss';

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
	}

	componentDidMount() {
		addAction(
			'tb.dynamicSources.actions.cache.updated',
			'toolset-blocks',
			this.updateBlockContentFromCache
		);
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
				{ __( 'View on front-end', 'toolset-blocks' ) }
			</a>
			{ !! this.props.hasChangedContent &&
			<small>
				{ __( '(Click Update button first to view the recent changes)', 'toolset-blocks' ) }
			</small>
			}
		</span>;
	};

	render() {
		if ( ! i18n.previewPosts || i18n.previewPosts.length < 1 ) {
			return null;
		}

		const onPostSelectorChanged = ( previewPost ) => {
			this.setState( { selectedPost: previewPost } );
			this.props.setPreviewPost( previewPost.value );
			this.props.editPost(
				{
					meta: {
						tb_preview_post: previewPost.value,
					},
				}
			);
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
				<label htmlFor={ `post-selector-${ this.props.instanceId }` }>{ __( 'View with: ', 'toolset-blocks' ) }</label>
				<Select
					id={ `post-selector-${ this.props.instanceId }` }
					value={ this.state.selectedPost }
					options={ i18n.previewPosts }
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
			const { getCurrentPostId, hasChangedContent } = select( 'core/editor' );

			return {
				previewPost: getPreviewPost(),
				isCacheUpdating: getCacheUpdating(),
				currentPostId: getCurrentPostId(),
				hasChangedContent: hasChangedContent(),
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
