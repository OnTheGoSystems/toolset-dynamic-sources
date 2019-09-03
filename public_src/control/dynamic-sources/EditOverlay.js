import { Component } from '@wordpress/element';
import { Tooltip } from '@wordpress/components';

import EditTooltip from './EditTooltip';

class EditOverlay extends Component {
	render() {
		const { hasDynamicSource, children } = this.props;

		if ( ! hasDynamicSource ) {
			return null;
		}

		let overlay = <div className="tb-field__overlay" />;

		if ( children ) {
			overlay = (
				<div className="tb-field__overlay">
					{ children }
				</div>
			);
		}

		return (
			<Tooltip text={ <EditTooltip /> }>
				{ overlay }
			</Tooltip>
		);
	}
}

export { EditOverlay };
