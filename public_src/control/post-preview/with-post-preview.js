import { Component, Fragment } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import PostPreview from './post-preview';

export default createHigherOrderComponent( ( WrappedComponent ) => {
	class ComponentWithPostPreview extends Component {
		render() {
			return (
				<Fragment>
					<PostPreview { ...this.props.ownProps } { ...this.mergeProps } />
					<WrappedComponent { ...this.props.ownProps } { ...this.mergeProps } />
				</Fragment>
			);
		}
	}

	return ( ownProps ) => (
		<ComponentWithPostPreview
			ownProps={ ownProps }
		/>
	);
}, 'withPostPreview' );
