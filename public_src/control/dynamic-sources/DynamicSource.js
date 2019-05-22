import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { ToggleControl, BaseControl } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import Select from 'react-select';
import AsyncSelect from 'react-select/lib/Async';
import { has, isEmpty, cloneDeep, find, filter, includes } from 'lodash';

import { fetchDynamicContent, searchPost, loadSourceFromCustomPost } from './utils/fetchData';
const { toolsetDynamicSourcesScriptData: i18n } = window;

class DynamicSourceClass extends Component {
	DEFAULT_POST_PROVIDER = null;

	ungroupedSources = [];

	constructor( props ) {
		super( props );

		this.DEFAULT_POST_PROVIDER = i18n.postProviders[ 0 ].value; // Current post.
	}

	/**
	 * Loads custom post sources if needed
	 *
	 * @param {object} props React props
	 */
	async componentWillMount() {
		const { dynamicSourcesEligibleAttribute } = this.props;
		if ( !! dynamicSourcesEligibleAttribute.customPostObject && ! i18n.dynamicSources[ dynamicSourcesEligibleAttribute.customPostObject.value ] ) {
			await this.loadSourceFromCustomPost( dynamicSourcesEligibleAttribute.customPostObject );
		}
	}

	componentDidUpdate = ( prevProps ) => {
		if (
			this.props.previewPost !== prevProps.previewPost &&
			this.ungroupedSources.length &&
			this.props.dynamicSourcesEligibleAttribute.sourceObject
		) {
			const selectedSource = find( this.ungroupedSources, { value: this.props.dynamicSourcesEligibleAttribute.sourceObject } );
			const selectedField = selectedSource.fields.length > 0 ? find( selectedSource.fields, { value: this.props.dynamicSourcesEligibleAttribute.fieldObject } ) : null;
			this.dynamicSourceSelectChanged( selectedSource );
			this.dynamicSourceFieldSelectChanged( selectedField );
		}
	};

	customStyles = {
		menu: ( styles ) => {
			return { ...styles, position: 'relative' };
		},
		input: ( base, state ) => ( {
			...base,
			'& input:focus': {
				boxShadow: state.isFocused ? 'none !important' : 'none !important',
			},
		} ),
	};

	filterSources = () => {
		const { dynamicSourcesEligibleAttribute, postType } = this.props;
		const getProvider = () => {
			if ( dynamicSourcesEligibleAttribute.postProviderObject === '__custom_post' ) {
				if ( !! dynamicSourcesEligibleAttribute.customPostObject ) {
					return dynamicSourcesEligibleAttribute.customPostObject.value;
				}
			}
			return dynamicSourcesEligibleAttribute.postProviderObject || this.DEFAULT_POST_PROVIDER;
		};
		const provider = getProvider();

		// The array (i18n.dynamicSources[ provider ]) needs to be deep cloned because otherwise when it is filtered
		// for attributes that receive a type of content that doesn't support all the sources, the ommited sources will
		// never be offered again, no matter the type of content the attribute can receive.
		const dynamicSources = cloneDeep( i18n.dynamicSources[ provider ] );
		dynamicSources.forEach(
			element => {
				element.options = element.options.filter(
					option => {
						const isCategoryBanned = ( categoryList, category ) => {
							if ( ! category ) {
								return true;
							}
							const categories = Array.isArray( category ) ? category : [ category ];
							const difference = categoryList.filter( item => categories.includes( item ) );
							if ( difference.length ) {
								return false;
							}
							return true;
						};
						if ( option.value === 'post-content' && postType !== 'view-template' ) {
							return false;
						}
						this.ungroupedSources.push( option );
						if ( has( option, 'fields' ) && ! isEmpty( option.fields ) ) {
							const fieldsOfCategory = find( option.fields, ( field ) => {
								if ( ! isCategoryBanned( field.categories, dynamicSourcesEligibleAttribute.bannedCategories ) ) {
									return false;
								}
								return includes( field.categories, dynamicSourcesEligibleAttribute.category );
							} );
							return ! isEmpty( fieldsOfCategory );
						}
						if ( ! isCategoryBanned( option.categories, dynamicSourcesEligibleAttribute.bannedCategories ) ) {
							return false;
						}

						return includes( option.categories, dynamicSourcesEligibleAttribute.category );
					}
				);
			}
		);
		return dynamicSources;
	};

	renderDynamicPostProviderSelect = () => {
		const { dynamicSourcesEligibleAttribute } = this.props;

		if ( ! dynamicSourcesEligibleAttribute.postProviderObject ) {
			dynamicSourcesEligibleAttribute.selectPostProviderChangedCallback( this.DEFAULT_POST_PROVIDER );
		}

		const selectedPostProvider = find( i18n.postProviders, { value: dynamicSourcesEligibleAttribute.postProviderObject } );

		return <Fragment key="post-provider-select">
			<BaseControl label={ __( 'Post Source', 'toolset-blocks' ) } >
				<Select
					options={ i18n.postProviders }
					styles={ this.customStyles }
					value={ selectedPostProvider }
					onChange={
						value => {
							dynamicSourcesEligibleAttribute.selectPostProviderChangedCallback( value.value );
							dynamicSourcesEligibleAttribute.selectCustomPostChangedCallback( null );
							dynamicSourcesEligibleAttribute.selectSourceChangedCallback( null );
							dynamicSourcesEligibleAttribute.selectFieldChangedCallback( null );
							dynamicSourcesEligibleAttribute.sourceContentFetchedCallback( '' );
						}
					}
				/>
			</BaseControl>
		</Fragment>;
	};

	dynamicSourceSelectChanged = async( value ) => {
		const { dynamicSourcesEligibleAttribute } = this.props;
		const selectedSource = null !== value ? value.value : null;

		dynamicSourcesEligibleAttribute.selectSourceChangedCallback( selectedSource );
		dynamicSourcesEligibleAttribute.selectFieldChangedCallback( null );
		if (
			value &&
			0 === value.fields.length
		) {
			const { clientId, setLoading, post, previewPost } = this.props;
			const provider = dynamicSourcesEligibleAttribute.postProviderObject || this.DEFAULT_POST_PROVIDER;

			setLoading( clientId, true );

			const previewPostID = previewPost || post;

			const response = await this.fetchDynamicContent( provider, previewPostID, selectedSource );

			if ( response === null ) {
				// fetchDynamicContent returns null if something wents wrong on the apiFetch
				// clear out content
				dynamicSourcesEligibleAttribute.sourceContentFetchedCallback( '' );
			} else {
				// pass new data
				dynamicSourcesEligibleAttribute.sourceContentFetchedCallback( response );
			}

			setLoading( clientId, false );
		}
	};

	renderDynamicSourceSelect = () => {
		const { dynamicSourcesEligibleAttribute } = this.props;
		const sources = this.filterSources();
		const selectedSource = find( this.ungroupedSources, { value: dynamicSourcesEligibleAttribute.sourceObject } ) || null;

		if ( dynamicSourcesEligibleAttribute.postProviderObject === '__custom_post' && ! dynamicSourcesEligibleAttribute.customPostObject ) {
			return null;
		}

		return <Fragment key="dynamic-source-select">
			<BaseControl label={ __( 'Source', 'toolset-blocks' ) } >
				<Select
					isClearable
					options={ sources }
					styles={ this.customStyles }
					value={ selectedSource }
					onChange={ this.dynamicSourceSelectChanged }
				/>
			</BaseControl>
		</Fragment>;
	};

	dynamicSourceFieldSelectChanged = async( value ) => {
		const { dynamicSourcesEligibleAttribute } = this.props;
		const selectedField = null !== value ? value.value : null;

		dynamicSourcesEligibleAttribute.selectFieldChangedCallback( selectedField );
		if ( selectedField ) {
			const { clientId, setLoading, post, previewPost } = this.props;
			setLoading( clientId, true );
			const provider = dynamicSourcesEligibleAttribute.postProviderObject;
			const source = dynamicSourcesEligibleAttribute.sourceObject;

			dynamicSourcesEligibleAttribute.sourceContentFetchedCallback( '' );
			const previewPostID = previewPost || post;
			const response = await this.fetchDynamicContent( provider, previewPostID, source, selectedField );
			dynamicSourcesEligibleAttribute.sourceContentFetchedCallback( response );
			setLoading( clientId, false );
		}
	};

	/**
	 * It renders a select to find a post
	 *
	 * @returns {JSX}
	 */
	renderDynamicSourceSearchPost = () => {
		const { dynamicSourcesEligibleAttribute } = this.props;

		if ( dynamicSourcesEligibleAttribute.postProviderObject !== '__custom_post' ) {
			return null;
		}

		return <Fragment key="dynamic-custom-post-select">
			<BaseControl label={ __( 'Post Name', 'toolset-blocks' ) } >
				<AsyncSelect
					cacheOptions
					loadOptions={ searchPost.bind( this ) }
					onChange={ this.customPostSelectChanged }
					styles={ this.customStyles }
					value={ dynamicSourcesEligibleAttribute.customPostObject }
				/>
			</BaseControl>
		</Fragment>;
	};

	/**
	 * Loads the source depending on custom post data
	 *
	 * @param {object} data Select data
	 */
	async loadSourceFromCustomPost( data ) {
		const {
			clientId,
			dynamicSourcesEligibleAttribute,
			setLoading,
		} = this.props;

		setLoading( clientId, true );
		await loadSourceFromCustomPost( data );

		const providerId = data.value;
		dynamicSourcesEligibleAttribute.postProviderObject = providerId;
		setLoading( clientId, false );
	}

	/**
	 * Actions for changing custom post
	 *
	 * @param {object} data Select data
	 */
	customPostSelectChanged = async( data ) => {
		const {
			dynamicSourcesEligibleAttribute,
		} = this.props;

		await this.loadSourceFromCustomPost( data );

		dynamicSourcesEligibleAttribute.selectCustomPostChangedCallback( data );
		dynamicSourcesEligibleAttribute.selectSourceChangedCallback( null );
		dynamicSourcesEligibleAttribute.selectFieldChangedCallback( null );
		dynamicSourcesEligibleAttribute.sourceContentFetchedCallback( '' );
	};

	renderDynamicSourceFieldsSelect = () => {
		const { dynamicSourcesEligibleAttribute } = this.props;

		if ( dynamicSourcesEligibleAttribute.sourceObject ) {
			// Getting the latest instance of fields from the JS object "ungroupedSources" created when filtering the
			// dynamic sources.
			const source = find(
				this.ungroupedSources,
				[ 'value', dynamicSourcesEligibleAttribute.sourceObject ]
			);
			let fields = !! source ? source.fields : [];

			fields = filter(
				fields,
				( field ) => includes( field.categories, dynamicSourcesEligibleAttribute.category )
			);

			if ( isEmpty( fields ) ) {
				return;
			}

			const singleField = Object.keys( fields ).length === 1 ? fields : null;

			const selectedField = [ 'image', 'video', 'audio' ].includes( dynamicSourcesEligibleAttribute.category ) ?
				find( fields, { value: dynamicSourcesEligibleAttribute.fieldObject } ) :
				find( fields, { value: dynamicSourcesEligibleAttribute.fieldObject } ) || singleField;

			if ( !! selectedField && selectedField === singleField ) {
				this.dynamicSourceFieldSelectChanged( selectedField[ 0 ] );
			}
			return <BaseControl label={ __( 'Field', 'toolset-blocks' ) } key="dynamic-source-fields-select">
				<Select
					isClearable
					options={ fields }
					styles={ this.customStyles }
					value={ selectedField }
					onChange={ this.dynamicSourceFieldSelectChanged }
				/>
			</BaseControl>;
		}
	};

	renderDynamicSourceSelectControls = () => {
		const { dynamicSourcesEligibleAttribute } = this.props;

		if ( dynamicSourcesEligibleAttribute.condition ) {
			return [
				this.renderDynamicPostProviderSelect(),
				this.renderDynamicSourceSearchPost(),
				this.renderDynamicSourceSelect(),
				this.renderDynamicSourceFieldsSelect(),
			];
		}
	};

	renderDynamicSourceToggleControl = () => {
		const { dynamicSourcesEligibleAttribute } = this.props;

		const maybeHideDynamicSourceToggle = dynamicSourcesEligibleAttribute.toggleHide || false;

		if ( !! maybeHideDynamicSourceToggle ) {
			return null;
		}

		return (
			<ToggleControl
				label={ dynamicSourcesEligibleAttribute.label }
				checked={ dynamicSourcesEligibleAttribute.condition }
				onChange={ dynamicSourcesEligibleAttribute.toggleChangedCallback }
			/>
		);
	};

	renderDynamicSourceControls = () => {
		const { clientId, dynamicSourcesEligibleAttribute } = this.props;

		const attributeKey = dynamicSourcesEligibleAttribute.attributeKey ?
			'-' + dynamicSourcesEligibleAttribute.attributeKey :
			'';

		if ( dynamicSourcesEligibleAttribute ) {
			return (
				<div className={ clientId + attributeKey }>
					{ this.renderDynamicSourceToggleControl() }
					{ this.renderDynamicSourceSelectControls() }
				</div>
			);
		}
	};

	/**
	 * Checks if the provider is a custom post provider
	 *
	 * @param {string} provider
	 * @returns {boolean}
	 */
	isCustomPostProvider( provider ) {
		return provider.match( /^custom_post_type\|[^\|]+\|\d+$/ ) || provider === '__custom_post';
	}

	fetchDynamicContent( provider, previewPostID, source, selectedField ) {
		const { dynamicSourcesEligibleAttribute } = this.props;
		if ( ! dynamicSourcesEligibleAttribute.customPostObject ) {
			return fetchDynamicContent( provider, previewPostID, source, selectedField );
		}
		// If custom post is selected, the ID is different than the current post or the preview post
		const isCustomPost = this.isCustomPostProvider( provider );
		const [ , customPostId ] = ( isCustomPost ? dynamicSourcesEligibleAttribute.customPostObject.value : '' ).split( '|' );
		const postId = isCustomPost ? customPostId : previewPostID;
		const finalProvider = isCustomPost ? dynamicSourcesEligibleAttribute.customPostObject.value : provider;

		return fetchDynamicContent( finalProvider, postId, source, selectedField );
	}

	render() {
		return this.renderDynamicSourceControls();
	}
}

const DynamicSource = compose( [
	withSelect(
		( select ) => {
			const { getCurrentPostId, getCurrentPostType } = select( 'core/editor' );
			const { getPreviewPost } = select( i18n.dynamicSourcesStore );

			return {
				post: getCurrentPostId(),
				postType: getCurrentPostType(),
				previewPost: getPreviewPost(),
			};
		}
	),
	withDispatch(
		( dispatch ) => {
			const { createErrorNotice } = dispatch( 'core/notices' );
			const { setLoading } = dispatch( i18n.dynamicSourcesStore );
			return {
				createErrorNotice,
				setLoading,
			};
		}
	),
] )( DynamicSourceClass );
export { DynamicSource };
