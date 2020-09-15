import { pure } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import {
	Component,
	Fragment,
} from '@wordpress/element';
import {
	RadioControl,
	TextControl,
	ToggleControl,
	ExternalLink,
} from '@wordpress/components';
import 'date_format';

class DateTime extends Component {
	FORMAT_DEFAULT = 'Y-m-d H:i:s';
	FORMAT_FJY = 'F j, Y';
	FORMAT_FJYGIA = 'F j, Y g:i a';
	FORMAT_DMY = 'd/m/y';

	PREDEFINED_FORMATS = [ this.FORMAT_DEFAULT, this.FORMAT_FJY, this.FORMAT_FJYGIA, this.FORMAT_DMY ];

	constructor( props ) {
		super( props );

		if ( props.modifiedContent ) {
			this.state = {
				format: props.modifiedContent.shortcodeAttributes.format,
				customFormat: ! this.PREDEFINED_FORMATS.includes(
					props.modifiedContent.shortcodeAttributes.format
				),
			};
		} else {
			this.state = {
				format: this.FORMAT_DEFAULT,
				customFormat: false,
			};
		}
	}

	modifyFormat = format => {
		const { onModifyContent } = this.props;

		const formatted = window.date_format( new Date( this.props.content ), format );

		this.setState( { format: format } );

		onModifyContent( {
			content: formatted,
			shortcodeAttributes: { format: format },
		} );
	};

	render() {
		const {
			format,
			customFormat,
		} = this.state;

		return <Fragment>
			<ToggleControl
				label={ __( 'Custom format', 'wpv-views' ) }
				checked={ customFormat }
				onChange={ () => this.setState( { customFormat: ! customFormat } ) }
			/>
			{ ! customFormat &&
			<RadioControl
				label={ __( 'Date Format', 'wpv-views' ) }
				selected={ format }
				options={ [
					{ label: this.FORMAT_DEFAULT, value: this.FORMAT_DEFAULT },
					{ label: this.FORMAT_FJY, value: this.FORMAT_FJY },
					{ label: this.FORMAT_FJYGIA, value: this.FORMAT_FJYGIA },
					{ label: this.FORMAT_DMY, value: this.FORMAT_DMY },
				] }
				onChange={ option => this.modifyFormat( option ) }
			/>
			}
			{ customFormat &&
			<Fragment>
				<TextControl
					value={ format }
					onChange={ value => this.modifyFormat( value ) }
				/>
				<p>
					<span>{ __( 'Specific documentation: ', 'wpv-views' ) }</span>
					<ExternalLink href="https://wordpress.org/support/article/formatting-date-and-time/" >
						{ __( 'WordPress Formatting Date and Time', 'wpv-views' ) }
					</ExternalLink>
				</p>
			</Fragment>
			}
		</Fragment>;
	}
}

export default pure( DateTime, 'DateTime' );
