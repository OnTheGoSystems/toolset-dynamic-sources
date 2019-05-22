import { Fragment, Component } from '@wordpress/element';

const scName = 'tb-dynamic-container';
const scValueSeparator = '#+*#';

export class SaveWrapper extends Component {
	filterUnusedDynamics( dynamics ) {
		if ( ! Array.isArray( dynamics ) ) {
			dynamics = [ dynamics ];
		}

		const usedSources = [];

		dynamics.map( dynamic => {
			if ( dynamic.source && dynamic.provider ) {
				usedSources.push( dynamic );
			}
		} );

		return usedSources;
	}

	getShortcodeStart( usedDynamics ) {
		if ( usedDynamics.length === 0 ) {
			return '';
		}

		let shortcode = `[${ scName }`;

		const providers = [];
		const sources = [];
		const fields = [];

		usedDynamics.map( dynamic => {
			if ( !! dynamic.customPost ) {
				providers.push( dynamic.customPost.value );
			} else {
				providers.push( dynamic.provider );
			}
			sources.push( dynamic.source );
			fields.push( !! dynamic.field ? dynamic.field : '' );
		} );

		shortcode += ` provider='${ providers.join( scValueSeparator ) }'`;
		shortcode += ` source='${ sources.join( scValueSeparator ) }'`;
		shortcode += ` field='${ fields.join( scValueSeparator ) }'`;

		shortcode += ']';

		return shortcode;
	}

	getShortcodeEnd( usedDynamics ) {
		if ( usedDynamics.length === 0 ) {
			return '';
		}

		return `[/${ scName }]`;
	}

	render() {
		const { children, requiredDynamics } = this.props;

		const usedDynamics = this.filterUnusedDynamics( requiredDynamics );

		return (
			<Fragment>
				{ this.getShortcodeStart( usedDynamics ) }
				{ children }
				{ this.getShortcodeEnd( usedDynamics ) }
			</Fragment>
		);
	}
}
