import { __ } from '@wordpress/i18n';
import { Fragment, Component } from '@wordpress/element';
import { Toolbar, Spinner } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';

const { toolsetDynamicSourcesScriptData: i18n } = window;

// Internal Dependencies
import './scss/edit.scss';

class EditWrapperClass extends Component {
	maybeRenderLoadingSpinner = () => {
		let spinner = null;
		if ( this.props.loading ) {
			spinner = <Spinner key="tb-spinner" />;
		}
		return spinner;
	};

	renderEditWrapperContent = () => {
		const { children } = this.props;

		const editWrapper = [ children ];
		if ( this.props.loading ) {
			editWrapper.push( <div className="tb-overlay" key="tb-overlay" /> );
		}

		return (
			<Fragment>
				{ editWrapper }
			</Fragment>
		);
	};

	renderDynamicContentBreadcrumb = () => {
		const { isSelected, hasDynamicSource } = this.props;

		if (
			! isSelected ||
			! hasDynamicSource
		) {
			return;
		}

		return (
			<div className={ 'editor-block-list__breadcrumb block-editor-block-list__breadcrumb' }>
				<Toolbar>
					<Fragment>
						{ this.maybeRenderLoadingSpinner() }
						<span className="breadcrumb-label">{ __( 'Dynamic Content', 'toolset-blocks' ) }</span>
					</Fragment>
				</Toolbar>
			</div>
		);
	};

	render() {
		const { hasDynamicSource } = this.props;

		const className = 'wp-block-toolset-blocks-wrapper';

		return (
			<div className={ hasDynamicSource ? `${ className } dynamic` : className }>
				{ this.renderDynamicContentBreadcrumb() }
				{ this.renderEditWrapperContent() }
			</div>
		);
	}
}
const EditWrapper = compose( [
	withSelect(
		( select, props ) => {
			const { getLoading } = select( i18n.dynamicSourcesStore );

			return {
				loading: getLoading( props.clientId ),
			};
		}
	),
] )( EditWrapperClass );
export { EditWrapper };
