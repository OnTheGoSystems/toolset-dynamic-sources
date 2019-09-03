import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

class EditTooltip extends Component {
	render() {
		return (
			<Fragment>
				{ __( 'This content is coming from a Dynamic Source. Use the Settings Sidebar to change its source.', 'wpv-views' ) }
			</Fragment>
		);
	}
}

export default EditTooltip;
