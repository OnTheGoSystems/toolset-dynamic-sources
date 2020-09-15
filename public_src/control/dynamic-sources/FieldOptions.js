import { pure } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { PanelBody } from '@wordpress/components';

import Excerpt from './FieldOptions/Excerpt';
import Taxonomy from './FieldOptions/Taxonomy';
import DateTime from './FieldOptions/DateTime';

class FieldOptions extends Component {
	getFieldOption = () => {
		const { props } = this;
		const {
			postField,
			fieldType,
			content,
		} = props;

		if (
			fieldType &&
			fieldType === 'taxonomy' &&
			content
		) {
			return <Taxonomy { ...props } />;
		}
		if (
			postField &&
			'post-excerpt' === postField.value
		) {
			return <Excerpt { ...props } />;
		}
		if (
			postField &&
			[ 'post-date', 'post-date-gmt', 'post-date-modified', 'post-date-modified-gmt' ].includes( postField.value )
		) {
			return <DateTime { ...props } />;
		}
		return null;
	};

	render() {
		const fieldOptions = this.getFieldOption();

		if ( ! fieldOptions ) {
			return '';
		}

		return <PanelBody
			title={ __( 'Field Options', 'wpv-views' ) }
			initialOpen={ true }
		>
			{ fieldOptions }
		</PanelBody>;
	}
}

export default pure( FieldOptions, 'FieldOptions' );
