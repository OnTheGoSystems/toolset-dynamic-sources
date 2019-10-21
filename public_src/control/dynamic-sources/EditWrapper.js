import { __ } from '@wordpress/i18n';
import { Fragment, Component } from '@wordpress/element';
import { Toolbar, Spinner } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { Tooltip } from 'toolset/block/control';

const { toolsetDynamicSourcesScriptData: i18n } = window;

// Internal Dependencies
import './scss/edit.scss';
import EditTooltip from './EditTooltip';

class EditWrapperClass extends Component {
	maybeRenderLoadingSpinner = () => {
		let spinner = null;
		if ( this.props.loading ) {
			spinner = <Spinner key="tb-spinner" />;
		}
		return spinner;
	};

	renderOverlayWithSpiner = () => {
		return (
			<div className="tb-overlay wp-block-embed is-loading" key="tb-overlay">
				<Spinner />
			</div>
		);
	};

	renderEditWrapperContent = () => {
		const { children } = this.props;
		const editWrapper = [ children ];

		if ( this.props.loading ) {
			editWrapper.push( this.renderOverlayWithSpiner() );
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
						<span className="breadcrumb-label">{ __( 'Dynamic Content', 'wpv-views' ) }</span>
					</Fragment>
				</Toolbar>
			</div>
		);
	};

	renderEditWrapper = () => {
		const { hasDynamicSource } = this.props;

		const className = 'wp-block-toolset-blocks-wrapper';

		return (
			<div className={ hasDynamicSource ? `${ className } dynamic` : className }>
				{ this.renderEditWrapperContent() }
			</div>
		);
	};

	render() {
		const { hasDynamicSource, disableTooltip = false } = this.props;
		let editWrapper = this.renderEditWrapper();

		if ( hasDynamicSource && ! disableTooltip ) {
			editWrapper = (
				<Tooltip
					text={ <EditTooltip /> }
					trigger="mouseenter focus click"
					maxWidth="500"
				>
					{ editWrapper }
				</Tooltip>
			);
		}

		return editWrapper;
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
