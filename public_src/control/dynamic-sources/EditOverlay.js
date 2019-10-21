import { Component } from '@wordpress/element';
import { Tooltip } from 'toolset/block/control';

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
			<Tooltip
				text={ <EditTooltip /> }
				trigger="mouseenter focus click"
				maxWidth="500"
			>
				{ overlay }
			</Tooltip>
		);
	}
}

export { EditOverlay };
