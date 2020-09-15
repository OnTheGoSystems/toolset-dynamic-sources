import { pure } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import {
	Component,
	Fragment,
} from '@wordpress/element';
import {
	RadioControl,
	RangeControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

class Excerpt extends Component {
	COUNTBY_CHAR = 'char';
	COUNTBY_WORD = 'word';

	constructor( props ) {
		super( props );

		const defaultState = {
			length: props.content ? 30 : 0,
			maxLength: props.content ? props.content.split( ' ' ).length : 0,
			countBy: this.COUNTBY_WORD,
			renderEllipsis: true,
			ellipsisText: __( '...', 'wpv-views' ),
		};

		this.state = props.modifiedContent ?
			{ ...defaultState, ...props.modifiedContent.shortcodeAttributes } :
			defaultState;
	}

	componentDidUpdate( prevProps ) {
		const { props } = this;

		// It's likely that on first render the content will be null, and we need to update when it first becomes
		// non-null.
		if (
			prevProps.content === null &&
			props.content !== null
		) {
			this.modifyContent( {
				length: props.content ? 30 : 0,
				maxLength: props.content ? props.content.split( ' ' ).length : 0,
			} );
		}
	}

	/**
	 * Cuts the excerpt and possibly adds ellipsis text.
	 * @param {Object} newState
	 * @return {String}
	 */
	makeExcerpt = newState => {
		const state = { ...this.state, ...newState };
		let excerpt = '';

		if ( state.countBy === this.COUNTBY_CHAR ) {
			excerpt = this.props.content.substring( 0, state.length );
		} else {
			excerpt = this.props.content
				.split( ' ' )
				.splice( 0, state.length )
				.join( ' ' );
		}

		if (
			state.renderEllipsis &&
			excerpt.length < this.props.content.length &&
			excerpt.length !== 0
		) {
			excerpt += state.ellipsisText;
		}

		return excerpt;
	};

	/**
	 * Given a new control state, updates component state, applies the state to content and calls back with it.
	 * @param {Object} newState
	 */
	modifyContent = newState => {
		const { onModifyContent } = this.props;

		if ( 'countBy' in newState ) {
			if ( newState.countBy === this.COUNTBY_WORD ) {
				newState.maxLength = this.props.content.split( ' ' ).length;
			} else {
				newState.maxLength = this.props.content.length;
			}

			if ( newState.maxLength < this.state.length ) {
				newState.length = newState.maxLength;
			}
		}

		this.setState( newState );

		// setState() is async, so just in case it didn't update this.state yet...
		const shortcodeAttributes = { ...this.state, ...newState };

		onModifyContent( {
			content: this.makeExcerpt( newState ),
			shortcodeAttributes: shortcodeAttributes,
		} );
	};

	render() {
		const {
			length,
			maxLength,
			countBy,
			renderEllipsis,
			ellipsisText,
		} = this.state;

		return <Fragment>
			<RangeControl
				label={ __( 'Length', 'wpv-views' ) }
				value={ length }
				onChange={ newLength => this.modifyContent( { length: newLength } ) }
				min={ 0 }
				max={ maxLength }
			/>
			<RadioControl
				label={ __( 'Count Length By', 'wpv-views' ) }
				selected={ countBy }
				options={ [
					{ label: __( 'Characters', 'wpv-views' ), value: this.COUNTBY_CHAR },
					{ label: __( 'Words', 'wpv-views' ), value: this.COUNTBY_WORD },
				] }
				onChange={ option => this.modifyContent( { countBy: option } ) }
			/>
			<ToggleControl
				label={ __( 'Show Ellipsis', 'wpv-views' ) }
				checked={ renderEllipsis }
				onChange={ () => this.modifyContent( { renderEllipsis: ! renderEllipsis } ) }
			/>
			{ renderEllipsis &&
			<TextControl
				label={ __( 'Ellipsis Text', 'wpv-views' ) }
				value={ ellipsisText }
				onChange={ newEllipsisText => this.modifyContent( { ellipsisText: newEllipsisText } ) }
			/>
			}
		</Fragment>;
	}
}

export default pure( Excerpt, 'Excerpt' );
