import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { ToggleControl, BaseControl, Notice } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';
import { applyFilters, doAction, addAction } from '@wordpress/hooks';
import { compose, pure } from '@wordpress/compose';
import { Select, AsyncSelect } from 'toolset/block/control';
import { has, isEmpty, cloneDeep, find, filter, includes, get, isUndefined } from 'lodash';

import { fetchDynamicContent, searchPost, loadSourceFromCustomPost } from './utils/fetchData';
const { toolsetDynamicSourcesScriptData: i18n } = window;

class DynamicSourceUnifiedClass extends Component {
	DEFAULT_POST_PROVIDER = null;

	ungroupedSources = [];

	constructor( props ) {
		super( props );

		this.DEFAULT_POST_PROVIDER = i18n.postProviders[ 0 ].value; // Current post.

		const {
			postProvider,
			source,
			field,
			customPost,
		} = props.dynamicSourcesEligibleAttribute;

		const fieldWasSelected = Boolean( postProvider && source && field );

		this.state = {
			infoMessageQueue: {},
			postProvider: postProvider ? postProvider : this.DEFAULT_POST_PROVIDER,
			customPost: customPost,
			source: source,
			field: field,
			fieldWasSelected: fieldWasSelected,
		};

		addAction(
			'tb.dynamicSources.actions.dynamicSourceComponentDidMount',
			'toolset-blocks',
			( clientId ) => {
				// This action callback should be triggered only for the block that has just mounted the component.
				if ( clientId !== this.props.clientId ) {
					return;
				}

				const { dynamicSourcesEligibleAttribute } = this.props;
				const selectedSource = find( this.ungroupedSources, { value: dynamicSourcesEligibleAttribute.sourceObject } ) || null;
				if ( selectedSource ) {
					doAction( 'tb.dynamicSources.actions.source.changed', selectedSource );

					const selectedField = selectedSource.fields.length > 0 ? find( selectedSource.fields, { value: dynamicSourcesEligibleAttribute.fieldObject } ) : null;
					if ( selectedField ) {
						doAction( 'tb.dynamicSources.actions.field.changed', selectedField );
					}
				}
			}
		);
	}

	componentDidMount() {
		addAction(
			'tb.dynamicSources.actions.field.changed',
			'toolset-blocks',
			value => {
				const newInfoMessageQueue = this.state.infoMessageQueue;
				if ( value && 'rfg' === value.type ) {
					newInfoMessageQueue.rfg = (
						<Fragment>
							<p
								dangerouslySetInnerHTML={
									{
										__html: sprintf(
											__( '%1$s is a Repeating Field Group. If you want to display its fields here, put another View block into this Loop item and choose %1$s in the Content Selection step of the View wizard.', 'wpv-views' ),
											`<strong>${ value.label }</strong>`
										),
									}
								}
							/>
						</Fragment>
					);
				} else {
					delete newInfoMessageQueue.rfg;
				}

				this.setState( { infoMessageQueue: newInfoMessageQueue } );
			}
		);

		doAction( 'tb.dynamicSources.actions.dynamicSourceComponentDidMount', this.props.clientId );

		// Loads custom post sources if needed
		if ( !! this.state.customPost && ! i18n.dynamicSources[ this.state.customPost.value ] ) {
			this.loadSourceFromCustomPost( this.state.customPost );
		}
	}

	componentDidUpdate = ( prevProps, prevState ) => {
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

		if (
			prevState.postProvider === this.state.postProvider &&
			prevState.source === this.state.source &&
			prevState.field === this.state.field
		) {
			return;
		}

		// Loads custom post sources if needed
		if ( !! this.state.customPost && ! i18n.dynamicSources[ this.state.customPost.value ] ) {
			this.loadSourceFromCustomPost( this.state.customPost );
		}

		// If we now have all 3 parts of field definition, call back.
		if ( this.state.postProvider && this.state.source && this.state.field ) {
			this.props.dynamicSourcesEligibleAttribute.fieldSelectedCallback( {
				postProvider: this.state.postProvider,
				source: this.state.source,
				field: this.state.field,
				customPost: this.state.customPost,
			} );
			this.setState( { fieldWasSelected: true } );
		// If source is already a field, we only need 2 parts.
		} else if ( this.state.postProvider && this.state.source && ! this.state.source.includes( '|' ) ) {
			this.props.dynamicSourcesEligibleAttribute.fieldSelectedCallback( {
				postProvider: this.state.postProvider,
				source: this.state.source,
				field: null,
				customPost: this.state.customPost,
			} );
			this.setState( { fieldWasSelected: true } );
		// And also call back if field was previously selected, but isn't any more.
		} else if ( this.state.fieldWasSelected ) {
			this.props.dynamicSourcesEligibleAttribute.fieldSelectedCallback( null );
			this.setState( { fieldWasSelected: false } );
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
		const { dynamicSourcesEligibleAttribute, postType, clientId, componentId } = this.props;
		const getProvider = () => {
			if ( this.state.postProvider === '__custom_post' ) {
				if ( !! this.state.customPost ) {
					return this.state.customPost.value;
				}
			}
			return this.state.postProvider || this.DEFAULT_POST_PROVIDER;
		};
		const provider = getProvider();

		/*
		 * Filters the Dynamic Sources offered by the relevant control. It adjust the control offered values for the
		 * cases when a block that uses Dynamic Sources is inside a View.
		 *
		 * @param array  i18n.dynamicSources The current set of Dynamic Sources.
		 * @param string clientId            The client ID assigned to the block
		 */
		const viewsFilteredSources = applyFilters(
			'tb.dynamicSources.filters.adjustSourcesForBlocksInsideViews',
			i18n.dynamicSources,
			clientId
		);

		/*
		 * A block can have indepent DynamicSources like in Conditional Blocks
		 * Filter using the `componentId` is needed
		 *
		 * @param array  i18n.dynamicSources The current set of Dynamic Sources.
		 * @param string componentId         The ID of the <DynamicSource> component
		 */
		const filteredSources = componentId ?
			applyFilters( 'tb.dynamicSources.filters.adjustSourcesForBlocks', viewsFilteredSources, componentId ) :
			viewsFilteredSources;

		// The array (i18n.dynamicSources[ provider ]) needs to be deep cloned because otherwise when it is filtered
		// for attributes that receive a type of content that doesn't support all the sources, the omitted sources will
		// never be offered again, no matter the type of content the attribute can receive.
		const dynamicSources = cloneDeep( filteredSources[ provider ] );

		if ( ! dynamicSources ) {
			return [];
		}

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

						const maybeViewBLock = applyFilters( 'toolsetViews.filters.getParentViewBlock', null, this.props.clientId );
						const blockTypes = applyFilters( 'toolsetViews.filters.getViewEditorBlockTypes', null );
						if (
							option.value === 'post-content' &&
							postType !== 'view-template' &&
							! isUndefined( blockTypes.BLOCK_ID_EDITOR ) &&
							blockTypes.BLOCK_ID_EDITOR !== get( maybeViewBLock, 'name', null )
						) {
							return false;
						}

						this.ungroupedSources.push( option );
						if ( has( option, 'fields' ) && ! isEmpty( option.fields ) ) {
							const fieldsOfCategory = find( option.fields, ( field ) => {
								if ( !! dynamicSourcesEligibleAttribute.forceRepeatable && ! field.is_repetitive ) {
									return false;
								}
								if ( ! isCategoryBanned( field.categories, dynamicSourcesEligibleAttribute.bannedCategories ) ) {
									return false;
								}
								return includes( field.categories, dynamicSourcesEligibleAttribute.category );
							} );
							return ! isEmpty( fieldsOfCategory );
						}
						if ( !! dynamicSourcesEligibleAttribute.forceRepeatable && !! option.fields && ! option.fields.length ) {
							return false;
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
		const { clientId } = this.props;
		const hideLabels = this.props.hideLabels || true;

		/*
		 * Filters the Post providers offered by the relevant control. It adjust the control offered values for the
		 * cases when a block that uses Dynamic Sources is inside a View.
		 *
		 * @param array  i18n.postProviders The current set of Post providers.
		 * @param string clientId           The client ID assigned to the block
		 */
		const filteredPostProviders = applyFilters(
			'tb.dynamicSources.filters.adjustProvidersForBlocksInsideViews',
			i18n.postProviders,
			clientId
		);

		const selectedPostProvider = find( filteredPostProviders, { value: this.state.postProvider } );
		const placeholder = ! hideLabels ? {} : { placeholder: __( 'Select a Post Source', 'wpv-views' ) };

		return <Fragment key="post-provider-select">
			<BaseControl label={ ! hideLabels ? false : __( 'Post Source', 'wpv-views' ) } >
				<Select
					options={ filteredPostProviders }
					styles={ this.customStyles }
					{ ... placeholder }
					value={ selectedPostProvider }
					onChange={ value => this.setState( {
						postProvider: value.value,
						customPost: null,
						source: null,
						field: null,
					} ) }
				/>
			</BaseControl>
		</Fragment>;
	};

	dynamicSourceSelectChanged = async( value ) => {
		doAction( 'tb.dynamicSources.actions.source.changed', value );

		const selectedSource = null !== value ? value.value : null;

		this.setState( {
			source: selectedSource,
			field: null,
		} );
	};

	renderDynamicSourceSelect = () => {
		const hideLabels = this.props.hideLabels || true;
		const sources = this.filterSources();
		const selectedSource = find(
			this.ungroupedSources,
			{ value: this.state.source }
		) || null;

		if (
			this.state.postProvider === '__custom_post' &&
			! this.state.customPost
		) {
			return null;
		}
		const placeholder = ! hideLabels ? {} : { placeholder: __( 'Select a Source', 'wpv-views' ) };

		return <Fragment key="dynamic-source-select">
			<BaseControl label={ ! hideLabels ? false : __( 'Source', 'wpv-views' ) } >
				<Select
					isClearable
					options={ sources }
					styles={ this.customStyles }
					{ ... placeholder }
					value={ selectedSource }
					onChange={ this.dynamicSourceSelectChanged }
				/>
			</BaseControl>
		</Fragment>;
	};

	dynamicSourceFieldSelectChanged = async( value ) => {
		doAction( 'tb.dynamicSources.actions.field.changed', value );

		const selectedField = null !== value ? value.value : null;

		this.setState( { field: selectedField } );
	};

	/**
	 * It renders a select to find a post
	 *
	 * @returns {JSX}
	 */
	renderDynamicSourceSearchPost = () => {
		const hideLabels = this.props.hideLabels || true;

		if ( this.state.postProvider !== '__custom_post' ) {
			return null;
		}
		const placeholder = ! hideLabels ? {} : { placeholder: __( 'Start typing to search...', 'wpv-views' ) };

		return <Fragment key="dynamic-custom-post-select">
			<BaseControl label={ ! hideLabels ? false : __( 'Post', 'wpv-views' ) } >
				<AsyncSelect
					isClearable
					defaultOptions
					cacheOptions
					loadOptions={ searchPost.bind( this ) }
					onChange={ this.customPostSelectChanged }
					styles={ this.customStyles }
					{ ... placeholder }
					value={ this.state.customPost }
				/>
			</BaseControl>
		</Fragment>;
	};

	/**
	 * Loads the source depending on custom post data
	 *
	 * @param {object} data Select data
	 * @returns {void}
	 */
	async loadSourceFromCustomPost( data ) {
		await loadSourceFromCustomPost( data ); // From fetchData.js

		const providerId = data.value;
		this.setState( { postProvider: providerId } );
	}

	/**
	 * Actions for changing custom post
	 *
	 * @param {object} data Select data
	 */
	customPostSelectChanged = async( data ) => {
		await this.loadSourceFromCustomPost( data );

		this.setState( {
			customPost: data,
			source: null,
			field: null,
		} );
	};

	renderDynamicSourceFieldsSelect = () => {
		const { dynamicSourcesEligibleAttribute } = this.props;
		const hideLabels = this.props.hideLabels || true;

		if ( this.state.source ) {
			// Getting the latest instance of fields from the JS object "ungroupedSources" created when filtering the
			// dynamic sources.
			const source = find(
				this.ungroupedSources,
				[ 'value', this.state.source ]
			);
			let fields = !! source ? source.fields : [];

			fields = filter(
				fields,
				( field ) => {
					if ( !! dynamicSourcesEligibleAttribute.forceRepeatable && ! field.is_repetitive ) {
						return false;
					}
					return includes( field.categories, dynamicSourcesEligibleAttribute.category );
				}
			);

			if ( isEmpty( fields ) ) {
				return;
			}

			const singleField = Object.keys( fields ).length === 1 ? fields : null;

			const selectedField = [ 'image', 'video', 'audio' ].includes( dynamicSourcesEligibleAttribute.category ) ?
				find( fields, { value: this.state.field } ) :
				find( fields, { value: this.state.field } ) || singleField;

			if ( !! selectedField && selectedField === singleField ) {
				this.dynamicSourceFieldSelectChanged( selectedField[ 0 ] );
			}

			const placeholder = ! hideLabels ? {} : { placeholder: __( 'Select a Field', 'wpv-views' ) };

			return <BaseControl label={ ! hideLabels ? false : __( 'Field', 'wpv-views' ) } key="dynamic-source-fields-select">
				<Select
					isClearable
					options={ fields }
					styles={ this.customStyles }
					{ ... placeholder }
					value={ selectedField }
					onChange={ this.dynamicSourceFieldSelectChanged }
				/>
			</BaseControl>;
		}
	};

	renderDynamicSourcesInfoMessages = () => {
		if ( Object.keys( this.state.infoMessageQueue ).length <= 0 ) {
			return null;
		}

		const messages = Object.keys( this.state.infoMessageQueue ).map(
			messageKey => {
				return (
					<Notice status="info" key={ messageKey } isDismissible={ false }>
						{ this.state.infoMessageQueue[ messageKey ] }
					</Notice>
				);
			}
		);

		return messages;
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
					{ this.renderDynamicSourcesInfoMessages() }
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
		if ( ! this.state.customPost ) {
			return fetchDynamicContent( provider, previewPostID, source, selectedField );
		}
		// If custom post is selected, the ID is different than the current post or the preview post
		const isCustomPost = this.isCustomPostProvider( provider );
		const [ , customPostId ] = ( isCustomPost ? this.state.customPost.value : '' ).split( '|' );
		const postId = isCustomPost ? customPostId : previewPostID;
		const finalProvider = isCustomPost ? this.state.customPost.value : provider;

		return fetchDynamicContent( finalProvider, postId, source, selectedField );
	}

	render() {
		return this.renderDynamicSourceControls();
	}
}

const DynamicSourceUnified = compose( [
	pure,
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
			const { setLoading, setPreviewPost } = dispatch( i18n.dynamicSourcesStore );
			return {
				createErrorNotice,
				setLoading,
				setPreviewPost,
			};
		}
	),
] )( DynamicSourceUnifiedClass );
export { DynamicSourceUnified };
