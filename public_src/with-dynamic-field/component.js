// WordPress dependencies
import { createHigherOrderComponent } from '@wordpress/compose';
import { Component, Fragment } from '@wordpress/element';
import { addAction, removeAction, hasAction, applyFilters } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

// Internal Dependencies
import { DynamicSourceUnified } from '../control/dynamic-sources/DynamicSourceUnified';
import { fetchDynamicContent } from '../control/dynamic-sources/utils/fetchData';
import PostPreview from '../control/post-preview/post-preview';
import getDifferenceOfObjects from '../utils/object-get-difference';
import assureString from '../utils/assure-string';

// External Dependencies
import { includes } from 'lodash';

const { toolsetDynamicSourcesScriptData: i18n } = window;

const DEFAULT_FIELD_ATTRIBUTES = {
	isActive: false,
	provider: null,
	source: null,
	customPost: null,
	field: null,
};

export default createHigherOrderComponent( ( WrappedComponent ) => {
	return class extends Component {
		constructor() {
			super( ...arguments );

			this.dynamicFieldsRegister = this.dynamicFieldsRegister.bind( this );
			this.dynamicFieldControlRender = this.dynamicFieldControlRender.bind( this );
			this.dynamicFieldGet = this.dynamicFieldGet.bind( this );
			this.dynamicFieldReset = this.dynamicFieldReset.bind( this );
			this.dynamicFieldNoDataRender = this.dynamicFieldNoDataRender.bind( this );
			this.requestUpdateContent = this.requestUpdateContent.bind( this );
			this.requestFieldToggle = this.requestFieldToggle.bind( this );
			this.requestFieldAttributeUpdate = this.requestFieldAttributeUpdate.bind( this );
			this.doFieldsAttributeUpdate = this.doFieldsAttributeUpdate.bind( this );

			this.currentPostId = select( 'core/editor' ).getCurrentPostId();

			this.attributesUpdateRequired = false;
		}

		componentDidMount() {
			this.requestUpdateContent();

			if ( hasAction( 'tb.dynamicSources.actions.cache.updated', 'with-dynamic-field-hoc-' + this.props.clientId ) ) {
				return;
			}

			addAction(
				'tb.dynamicSources.actions.cache.updated',
				'with-dynamic-field-hoc-' + this.props.clientId,
				this.requestUpdateContent
			);
		}

		componentWillUnmount() {
			removeAction( 'tb.dynamicSources.actions.cache.updated', 'with-dynamic-field-hoc-' + this.props.clientId );
		}

		dynamicFieldsRegister( fields, dynamicRootAttributeKey = 'dynamic' ) {
			if ( typeof fields !== 'object' ) {
				this.throwError( 'dynamicFieldsRegister() first parameter must be an object.' );
				return;
			}

			if ( this.fields ) {
				// fields register can only be called once, no need for error if the fields are the same
				if ( Object.keys( this.fields ).toString() !== Object.keys( fields ).toString() ) {
					this.throwError( 'dynamicFieldsRegister() is called twice. Register all fields with one call.' );
				}

				return;
			}

			this.dynamicRootAttributeKey = dynamicRootAttributeKey;
			this.fields = {};

			Object.keys( fields ).forEach( key => {
				fields[ key ].attributeKey = fields[ key ].attributeKey || key;
				this.fields[ key ] = {
					...DEFAULT_FIELD_ATTRIBUTES,
					...this.props.attributes[ dynamicRootAttributeKey ] && this.props.attributes[ dynamicRootAttributeKey ][ key ] ?
						this.props.attributes[ dynamicRootAttributeKey ][ key ] :
						{},
					...fields[ key ],
				};
			} );
		}

		dynamicFieldControlRender( field ) {
			if ( ! this.fieldExists( field ) ) {
				return;
			}

			return (
				<DynamicSourceUnified
					clientId={ this.props.clientId }
					dynamicSourcesEligibleAttribute={ this.getFieldControlSetup( this.fields[ field ] ) }
				/>
			);
		}

		dynamicFieldGet( field ) {
			if ( ! this.fieldExists( field ) ) {
				return DEFAULT_FIELD_ATTRIBUTES;
			}

			return this.fields[ field ];
		}

		dynamicFieldReset( field, isActiveAfterReset = null ) {
			if ( ! this.fieldExists( field ) ) {
				return;
			}

			this.requestFieldAttributeUpdate( this.fields[ field ], null, isActiveAfterReset );
		}

		dynamicFieldNoDataRender( field ) {
			if ( ! this.fieldExists( field ) ) {
				return;
			}

			const usedField = this.fields[ field ];

			if ( ! this.props.attributes[ usedField.attributeKey ] && usedField.isActive && usedField.source ) {
				return (
					<span style={ { color: '#ccc' } }>
						{ __( 'This dynamic source returned no content.', 'wpv-views' ) }
					</span>
				);
			}

			return null;
		}

		fieldExists( field ) {
			if ( typeof field !== 'string' ) {
				this.throwError( 'First parameter must be a string' );
				return false;
			}

			if ( ! this.fields || ! this.fields[ field ] ) {
				this.throwError( `The requested field "${ field }" is not registered.` );
				return false;
			}

			return true;
		}

		getFieldControlSetup( field ) {
			const label = field.label || __( 'Dynamic Source', 'wpv-views' );

			return {
				attributeKey: field.attributeKey,
				label: label,
				condition: field.condition || field.isActive,
				postProvider: field.provider,
				source: field.source,
				field: field.field,
				customPost: field.customPost,
				toggleHide: field.toggleHide || false,
				toggleChangedCallback: () => {
					this.requestFieldToggle( field );
				},
				fieldSelectedCallback: value => {
					this.requestFieldAttributeUpdate( field, value );
				},
				category: field.category || 'text',
				bannedCategories: field.bannedCategories || [],
				allowRepeatable: field.allowRepeatable || false,
				forceRepeatable: field.forceRepeatable || false,
				returnType: 'anything',
			};
		}

		/*
		 * Push new content to the block.
		 *
		 * "source" This required for a workaround until toolsetblocks-578 is fixed. Just for image block.
		 */
		updateFieldContent( field, data, returnValue = false, source = null ) {
			if ( data && field.customContentCallback ) {
				if ( typeof field.customContentCallback !== 'function' ) {
					this.throwError( 'customContentCallback must be a function.' );
				} else {
					field.customContentCallback( data, this.props.attributes, source );
					return false;
				}
			} else {
				let content = data && data.sourceContent ? data.sourceContent : '';

				// When the field does not allow repeatable make sure a string is returned.
				if ( field.allowRepeatable === false ) {
					content = assureString( content );
				}

				if ( field.parse ) {
					switch ( field.parse ) {
						case 'int':
							content = parseInt( content );
							break;
						case 'float':
							content = parseFloat( content );
							break;
					}
				}

				if ( field.returnType === 'array' && !! content && ! Array.isArray( content ) ) {
					content = [ content ];
				}

				if ( returnValue ) {
					return { [ field.attributeKey ]: content };
				}

				this.props.setAttributes( { [ field.attributeKey ]: content } );
			}
		}

		requestFieldToggle( field ) {
			this.fields[ field.attributeKey ].isActive = ! this.fields[ field.attributeKey ].isActive;
			this.attributesUpdateRequired = true;
			this.doFieldsAttributeUpdate();
			if ( this.fields[ field.attributeKey ].isActive ) {
				this.requestUpdateContent();
			}
		}

		requestFieldAttributeUpdate( field, value, isActiveAfterReset = null ) {
			if ( isActiveAfterReset === null && field.toggleHide ) {
				this.fields[ field.attributeKey ].isActive = true;
			}

			if ( isActiveAfterReset !== null ) {
				this.fields[ field.attributeKey ].isActive = !! isActiveAfterReset;
			}

			if ( value === null ) {
				this.fields[ field.attributeKey ].customPost = null;
				this.fields[ field.attributeKey ].field = null;
				this.fields[ field.attributeKey ].provider = null;
				this.fields[ field.attributeKey ].source = null;
			} else {
				this.fields[ field.attributeKey ].customPost = value.customPost;
				this.fields[ field.attributeKey ].field = value.field;
				this.fields[ field.attributeKey ].provider = value.postProvider;
				this.fields[ field.attributeKey ].source = value.source;
			}
			this.attributesUpdateRequired = true;

			this.doFieldsAttributeUpdate();
			this.requestUpdateContent();
		}

		doFieldsAttributeUpdate() {
			const updateAttributes = {};

			if ( ! this.attributesUpdateRequired ) {
				return;
			}

			// reset attributesToUpdate
			this.attributesUpdateRequired = false;

			Object.keys( this.fields ).forEach( key => {
				const changesWithoutDefaults = getDifferenceOfObjects( DEFAULT_FIELD_ATTRIBUTES, this.fields[ key ] );

				if ( Object.keys( changesWithoutDefaults ).length > 0 ) {
					updateAttributes[ key ] = changesWithoutDefaults;
				}
			} );

			this.props.setAttributes( {
				[ this.dynamicRootAttributeKey ]: updateAttributes,
			} );
		}

		maybeGetParentViewBlock = ( blockId ) => {
			const { getBlock, getBlockRootClientId } = select( 'core/block-editor' );
			let result = null,
				currentBlockId = blockId;

			do {
				currentBlockId = getBlockRootClientId( currentBlockId );
				if ( !! currentBlockId ) {
					const block = getBlock( currentBlockId );
					if ( includes( [ 'toolset-views/view-editor', 'toolset-views/wpa-editor' ], block.name ) ) {
						result = block;
					}
				}
			} while ( !! currentBlockId );

			return result;
		};

		requestUpdateContent() {
			const {
				currentPostId,
				props: { setAttributes, clientId },
			} = this;

			const previewPostId = applyFilters( 'tb.dynamicSources.filters.adjustPreviewPostID', select( i18n.dynamicSourcesStore ).getPreviewPost(), clientId );
			const postId = previewPostId || currentPostId;

			Object.keys( this.fields ).map( async key => {
				const {
					isActive,
					source,
					field,
					provider,
					customPost,
				} = this.fields[ key ];

				if ( ! isActive ) {
					return;
				}

				const finalProvider = !! customPost ? customPost.value : provider;
				const parentViewBlock = this.maybeGetParentViewBlock( clientId );
				let viewId = null;
				if ( !! parentViewBlock ) {
					const viewGeneralSettings = select( parentViewBlock.attributes.reduxStoreId ).getGeneral();
					viewId = viewGeneralSettings.id;
				}

				const response = await fetchDynamicContent(
					finalProvider,
					postId,
					source,
					field ? field : null,
					viewId
				);
				const updateAttributes = this.updateFieldContent(
					this.fields[ key ],
					response,
					true,
					source
				);
				if ( updateAttributes !== false ) {
					setAttributes( updateAttributes );
				}
			} );
		}

		throwError( error ) {
			// eslint-disable-next-line
			console.error( `[withToolsetDynamicField] ${ error }` );
		}

		render() {
			return (
				<Fragment>
					<PostPreview { ...this.props } />
					<WrappedComponent
						{ ...this.props }
						dynamicFieldsRegister={ this.dynamicFieldsRegister }
						dynamicFieldControlRender={ this.dynamicFieldControlRender }
						dynamicFieldNoDataRender={ this.dynamicFieldNoDataRender }
						dynamicFieldGet={ this.dynamicFieldGet }
						dynamicFieldReset={ this.dynamicFieldReset }
					/>
				</Fragment>
			);
		}
	};
}, 'withToolsetDynamicField' );
