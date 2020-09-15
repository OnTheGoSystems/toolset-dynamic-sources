import { pure } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import {
	Component,
	Fragment,
} from '@wordpress/element';
import {
	RadioControl,
	TextControl,
} from '@wordpress/components';
import { fetchDynamicContent } from '../utils/fetchData';

class Taxonomy extends Component {
	FORMAT_NAME = 'name';
	FORMAT_LINK = 'link';
	FORMAT_URL = 'url';
	FORMAT_DESCRIPTION = 'description';
	FORMAT_SLUG = 'slug';
	FORMAT_COUNT = 'count';

	SHOW_NAME = 'name';
	SHOW_DESCRIPTION = 'description';
	SHOW_SLUG = 'slug';
	SHOW_COUNT = 'count';

	ORDER_ASC = 'asc';
	ORDER_DESC = 'desc';

	constructor( props ) {
		super( props );

		const defaultState = {
			separator: ', ',
			format: this.FORMAT_NAME,
			show: this.SHOW_NAME,
			order: this.ORDER_ASC,
		};

		this.state = props.modifiedContent ?
			{ ...defaultState, ...props.modifiedContent.shortcodeAttributes } :
			defaultState;
	}

	componentDidMount() {
		// Apply format settings immediately
		this.modifyContent( {} );
	}

	/**
	 * @param {string} slug
	 * @param {string} text
	 * @returns {string}
	 */
	makeTagLink = ( slug, text ) => {
		return `<a href="//?tag=${ slug }">${ text }</a>`;
	};

	/**
	 * @returns {string}
	 */
	getOrigin = () => new URL( window.location.href ).origin;

	/**
	 * Turns taxonomy content and format options state into a formatted taxonomy string.
	 * @return {string}
	 */
	formatTaxonomy = async() => {
		const state = this.state;
		const {
			separator,
			format,
			show,
			order,
		} = state;

		let content = Array.isArray( this.props.content ) ?
			this.props.content :
			this.props.content.split( ',' );

		// But if format is anything else than FORMAT_NAME, we have to get richer data ourselves...
		if ( format !== this.FORMAT_NAME ) {
			const richContent = await fetchDynamicContent(
				this.props.postProvider,
				this.props.postId,
				'post-taxonomies-rich',
				this.props.postField.value
			);
			content = richContent.sourceContent;
		}

		// Display format
		switch ( format ) {
			case this.FORMAT_LINK:
				switch ( show ) {
					case this.SHOW_NAME:
						content	= content.map( term => this.makeTagLink( term.slug, term.name ) );
						break;
					case this.SHOW_DESCRIPTION:
						content	= content.map( term => this.makeTagLink( term.slug, term.description ) );
						break;
					case this.SHOW_SLUG:
						content	= content.map( term => this.makeTagLink( term.slug, term.slug ) );
						break;
					case this.SHOW_COUNT:
						content	= content.map( term => this.makeTagLink( term.slug, term.count ) );
						break;
				}
				break;
			case this.FORMAT_URL:
				content = content.map( term => `${ this.getOrigin() }/?tag=${ term.slug }` );
				break;
			case this.FORMAT_COUNT:
				content = content.map( term => term.count );
				break;
			case this.FORMAT_SLUG:
				content = content.map( term => term.slug );
				break;
			case this.FORMAT_DESCRIPTION:
				content = content.map( term => term.description );
				break;
			case this.FORMAT_NAME:
			default:
				break;
		}

		// Order
		if ( order === this.ORDER_DESC ) {
			content = content.reverse();
		}

		// Join data with separator
		return content.join( separator );
	};

	/**
	 * Given a new control state, updates component state, applies the state to content and calls back with it.
	 * @param {Object} newState
	 * @return {void}
	 */
	modifyContent = async newState => {
		const { onModifyContent } = this.props;

		await this.setState( newState );

		onModifyContent( {
			content: await this.formatTaxonomy(),
			shortcodeAttributes: this.state,
		} );
	};

	render() {
		const {
			separator,
			format,
			show,
			order,
		} = this.state;

		return <Fragment>
			<RadioControl
				label={ __( 'Display Format', 'wpv-views' ) }
				selected={ format }
				options={ [
					{ label: __( 'Link to term archive page', 'wpv-views' ), value: this.FORMAT_LINK },
					{ label: __( 'URL of term archive page', 'wpv-views' ), value: this.FORMAT_URL },
					{ label: __( 'Term name', 'wpv-views' ), value: this.FORMAT_NAME },
					{ label: __( 'Term description', 'wpv-views' ), value: this.FORMAT_DESCRIPTION },
					{ label: __( 'Term slug', 'wpv-views' ), value: this.FORMAT_SLUG },
					{ label: __( 'Term post count', 'wpv-views' ), value: this.FORMAT_COUNT },
				] }
				onChange={ value => this.modifyContent( { format: value } ) }
			/>
			{ format === this.FORMAT_LINK &&
			<RadioControl
				label={ __( 'Anchor Text When Linking to the Term Archive Page', 'wpv-views' ) }
				selected={ show }
				options={ [
					{ label: __( 'Term name', 'wpv-views' ), value: this.SHOW_NAME },
					{ label: __( 'Term description', 'wpv-views' ), value: this.SHOW_DESCRIPTION },
					{ label: __( 'Term slug', 'wpv-views' ), value: this.SHOW_SLUG },
					{ label: __( 'Term post count', 'wpv-views' ), value: this.SHOW_COUNT },
				] }
				onChange={ value => this.modifyContent( { show: value } ) }
			/>
			}
			<TextControl
				label={ __( 'Separator Between Terms', 'wpv-views' ) }
				value={ separator }
				onChange={ value => this.modifyContent( { separator: value } ) }
			/>
			<RadioControl
				label={ __( 'Order', 'wpv-views' ) }
				selected={ order }
				options={ [
					{ label: __( 'Ascending', 'wpv-views' ), value: this.ORDER_ASC },
					{ label: __( 'Descending', 'wpv-views' ), value: this.ORDER_DESC },
				] }
				onChange={ value => this.modifyContent( { order: value } ) }
			/>
		</Fragment>;
	}
}

export default pure( Taxonomy, 'Taxonomy' );
