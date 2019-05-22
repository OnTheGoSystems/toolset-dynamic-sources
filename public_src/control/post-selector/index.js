import { Component, render } from '@wordpress/element';
import { select, dispatch } from '@wordpress/data';
import { find, isEqual, has, get, partial } from 'lodash';

import PostSelectorComponent from './post-selector';

const { toolsetDynamicSourcesScriptData: i18n } = window;

class PostSelector extends Component {
	PLACEHOLDER_ID = 'toolset-blocks-post-selector';

	constructor( props = {} ) {
		super( props );

		if ( ! select( i18n.dynamicSourcesStore ).getPreviewPost() ) {
			const previewPost = find(
				i18n.previewPosts,
				[ 'value', parseInt( i18n.postPreview ) ]
			);

			if ( !! previewPost && has( previewPost, 'value' ) ) {
				dispatch( i18n.dynamicSourcesStore ).setPreviewPost( get( previewPost, 'value' ) );
			}
		}

		this.deferAddingPostSelector( this.deferAddingPostSelector );
	}

	deferAddingPostSelector = ( deferCallback ) => {
		const { getCurrentPostType } = select( 'core/editor' );

		const $body = jQuery( 'body' );
		if ( $body.length === 0 ) {
			setTimeout( partial( deferCallback, deferCallback ), 1 );
			return;
		}

		const $editor = jQuery( '#editor.block-editor__container' );

		if ( $editor.length === 0 ) {
			// This most probably means we're in the classic editor already.
			return;
		}

		const $editorToolbar = $editor.find( '.edit-post-header-toolbar' );
		if ( $editorToolbar.length === 0 ) {
			setTimeout( partial( deferCallback, deferCallback ), 1 );
			return;
		}

		// The post selector will be added only in Content Templates edited by the new editor (Gutenberg)
		if ( ! isEqual( 'view-template', getCurrentPostType() ) ) {
			return;
		}

		this.addPostSelector();
	};

	addPostSelector = () => {
		const $editorToolbar = jQuery( '#editor.block-editor__container .edit-post-header-toolbar' );

		if ( $editorToolbar.length === 0 ) {
			return;
		}

		// Manually append the placeholder for our component.
		$editorToolbar.append( `<div id="${ this.PLACEHOLDER_ID }"></div>` );

		render(
			<PostSelectorComponent />,
			document.querySelector( '#' + this.PLACEHOLDER_ID )
		);
	};
}

export default PostSelector;
