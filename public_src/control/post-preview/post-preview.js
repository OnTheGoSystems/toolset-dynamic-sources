import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect, select } from '@wordpress/data';
import { doAction } from '@wordpress/hooks';

const { toolsetDynamicSourcesScriptData: i18n } = window;

class PostPreviewControl extends Component {
	componentDidUpdate = ( prevProps ) => {
		if ( this.props.previewPost !== prevProps.previewPost ) {
			if ( ! select( i18n.dynamicSourcesStore ).getCacheUpdating() ) {
				doAction( 'tb.dynamicSources.actions.cache.initiateFetching' );
			}
		}
	};

	render = () => {
		return null;
	}
}

const PostPreview = compose( [
	withSelect(
		( selectStore ) => {
			const { getPreviewPost } = selectStore( i18n.dynamicSourcesStore );

			return {
				previewPost: getPreviewPost(),
			};
		}
	),
] )( PostPreviewControl );

export default PostPreview;
