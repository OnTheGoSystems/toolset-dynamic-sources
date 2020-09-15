import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { ToggleControl, BaseControl, Notice } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';
import { applyFilters, doAction, addAction } from '@wordpress/hooks';
import { compose } from '@wordpress/compose';
import { Select, AsyncSelect } from 'toolset/block/control';
import { has, isEmpty, cloneDeep, find, filter, includes, get, isUndefined } from 'lodash';

import { fetchDynamicContent, searchPost, loadSourceFromCustomPost } from './utils/fetchData';
const { toolsetDynamicSourcesScriptData: i18n } = window;

class DynamicSourceClass extends Component {
	DEFAULT_POST_PROVIDER = null;

	ungroupedSources = [];

	constructor( props ) {
		super( props );

		this.DEFAULT_POST_PROVIDER = i18n.postProviders[ 0 ].value; // Current post.

		this.state = {
			infoMessageQueue: {},
		};

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
		doAction( 'tb.dynamicSources.actions.dynamicSourceComponentDidMount', this.props.clientId );
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
		const { dynamicSourcesEligibleAttribute, postType, clientId, componentId } = this.props;
		const getProvider = () => {
			if ( dynamicSourcesEligibleAttribute.postProviderObject === '__custom_post' ) {
				if ( !! dynamicSourcesEligibleAttribute.customPostObject ) {
					return dynamicSourcesEligibleAttribute.customPostObject.value;
				}
			}
			return dynamicSourcesEligibleAttribute.postProviderObject || this.DEFAULT_POST_PROVIDER;
		};
		const provider = getProvider();

		/*
		 * Filters the Dynamic Sources offered by the relevant control. It adjust the control offered values for the
		 * cases when a block that uses Dynamic Sources is inside a View.
		 *
		 * @param array  i18n.dynamicSources The current set of Dynamic Sources.
		 * @param string clientId            The client ID assigned to the block
		 */
		const viewsFilteredSources = applyFilters( 'tb.dynamicSources.filters.adjustSourcesForBlocksInsideViews', i18n.dynamicSources, clientId );

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
		// for attributes that receive a type of content that doesn't support all the sources, the ommited sources will
		// never be offered again, no matter the type of content the attribute can receive.
		const dynamicSources = cloneDeep( filteredSources[ provider ] );
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
		const { dynamicSourcesEligibleAttribute, clientId } = this.props;

		const hideLabels = this.props.hideLabels || true;

		if ( ! dynamicSourcesEligibleAttribute.postProviderObject ) {
			dynamicSourcesEligibleAttribute.selectPostProviderChangedCallback( this.DEFAULT_POST_PROVIDER );
		}

		/*
		 * Filters the Post providers offered by the relevant control. It adjust the control offered values for the
		 * cases when a block that uses Dynamic Sources is inside a View.
		 *
		 * @param array  i18n.postProviders The current set of Post providers.
		 * @param string clientId           The client ID assigned to the block
		 */
		const filteredPostProviders = applyFilters( 'tb.dynamicSources.filters.adjustProvidersForBlocksInsideViews', i18n.postProviders, clientId );
		const selectedPostProvider = find( filteredPostProviders, { value: dynamicSourcesEligibleAttribute.postProviderObject } );
		const placeholder = ! hideLabels ? {} : { placeholder: __( 'Select a Post Source', 'wpv-views' ) };

		return <Fragment key="post-provider-select">
			<BaseControl label={ ! hideLabels ? false : __( 'Post Source', 'wpv-views' ) } >
				<Select
					options={ filteredPostProviders }
					styles={ this.customStyles }
					{ ... placeholder }
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
		doAction( 'tb.dynamicSources.actions.source.changed', value );

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

			/*
			 * Filters the preview Post ID for the cases when a block that uses Dynamic Sources is inside a View.
			 *
			 * @param array  previewPost The current preview post ( prviewPost || post).
			 * @param string clientId    The client ID assigned to the block
			 */
			const previewPostID = applyFilters( 'tb.dynamicSources.filters.adjustPreviewPostID', previewPost || post, clientId );

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
		const hideLabels = this.props.hideLabels || true;
		const sources = this.filterSources();
		const selectedSource = find( this.ungroupedSources, { value: dynamicSourcesEligibleAttribute.sourceObject } ) || null;

		if ( dynamicSourcesEligibleAttribute.postProviderObject === '__custom_post' && ! dynamicSourcesEligibleAttribute.customPostObject ) {
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

		const { dynamicSourcesEligibleAttribute } = this.props;
		const selectedField = null !== value ? value.value : null;

		dynamicSourcesEligibleAttribute.selectFieldChangedCallback( selectedField );
		if ( selectedField ) {
			const { clientId, setLoading, post, previewPost } = this.props;
			setLoading( clientId, true );
			const provider = dynamicSourcesEligibleAttribute.postProviderObject;
			const source = dynamicSourcesEligibleAttribute.sourceObject;

			dynamicSourcesEligibleAttribute.sourceContentFetchedCallback( '' );
			const previewPostID = applyFilters( 'tb.dynamicSources.filters.adjustPreviewPostID', previewPost || post, clientId );
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
		const hideLabels = this.props.hideLabels || true;

		if ( dynamicSourcesEligibleAttribute.postProviderObject !== '__custom_post' ) {
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
		const hideLabels = this.props.hideLabels || true;

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

/**
 * @deprecated use DynamicSourceUnified instead
 */
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
			const { setLoading, setPreviewPost } = dispatch( i18n.dynamicSourcesStore );
			return {
				createErrorNotice,
				setLoading,
				setPreviewPost,
			};
		}
	),
] )( DynamicSourceClass );
export { DynamicSource };
