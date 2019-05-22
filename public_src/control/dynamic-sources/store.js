import { registerStore } from '@wordpress/data';
import DynamicSourcesService from './services/http/dynamic-sources';

const { toolsetDynamicSourcesScriptData: i18n } = window;

const DEFAULT_STATE = {
	dynamicSources: [],
};

const actions = {
	setLoading( blockID, loading ) {
		return {
			type: 'SET_LOADING',
			blockID,
			loading,
		};
	},
	setCacheUpdating( cacheUpdating ) {
		return {
			type: 'SET_CACHE_UPDATING',
			cacheUpdating,
		};
	},
	setPreviewPost( previewPost ) {
		return {
			type: 'SET_PREVIEW_POST',
			previewPost,
		};
	},
	setDynamicSources: ( dynamicSources ) => {
		return {
			type: 'DYNAMIC_SOURCES_SET',
			dynamicSources,
		};
	},
	// eslint-disable-next-line
	getDynamicSources: ( postTypes, randomAttr ) => {
		// A random value is always passed as an argument to the store's selector in order to always invoke the
		// relevant resolver. The data package seems to be doing some kind of caching when it comes to resolvers
		// with argument values that have already been resolved, To bypass this a random argument needs to be fed in,
		// to fake an argument set change.
		return {
			type: 'DYNAMIC_SOURCES_GET',
			postTypes,
		};
	},
};

const selectors = {
	getLoading( state, blockID ) {
		if ( state[ blockID ] ) {
			return state[ blockID ].loading;
		}

		return false;
	},
	getCacheUpdating( state ) {
		if ( state.cacheUpdating ) {
			return state.cacheUpdating;
		}

		return false;
	},
	getPreviewPost( state ) {
		if ( state.previewPost ) {
			return state.previewPost;
		}

		return null;
	},
	// eslint-disable-next-line
	getDynamicSources( state, postTypes, randomAttr ) {
		// A random value is always passed as an argument to the store's selector in order to always invoke the
		// relevant resolver. The data package seems to be doing some kind of caching when it comes to resolvers
		// with argument values that have already been resolved, To bypass this a random argument needs to be fed in,
		// to fake an argument set change.
		return state.dynamicSources;
	},
};

const dynamicSourcesService = new DynamicSourcesService();
const controls = {
	DYNAMIC_SOURCES_GET( action ) {
		return dynamicSourcesService.get( action.postTypes );
	},
};

const resolvers = {
	* getDynamicSources( postTypes, randomAttr ) {
		// A random value is always passed as an argument to the store's selector in order to always invoke the
		// relevant resolver. The "data" package seems to be doing some kind of caching when it comes to resolvers
		// with argument values that have already been resolved, To bypass this a random argument needs to be fed in,
		// to fake an argument set change.
		const sources = yield actions.getDynamicSources( postTypes, randomAttr );
		yield actions.setDynamicSources( sources );
	},
};

const storeSetting = {
	actions,
	selectors,
	controls,
	resolvers,
	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'SET_LOADING':
				return {
					...state,
					[ action.blockID ]: {
						loading: action.loading,
					},
				};
			case 'SET_CACHE_UPDATING':
				return {
					...state,
					cacheUpdating: !! action.cacheUpdating,
				};
			case 'SET_PREVIEW_POST':
				return {
					...state,
					previewPost: parseInt( action.previewPost ),
				};
			case 'DYNAMIC_SOURCES_SET': {
				return {
					...state,
					dynamicSources: action.dynamicSources,
				};
			}
		}

		return state;
	},
};

const registerDynamicSourceStore = () => {
	registerStore( i18n.dynamicSourcesStore, storeSetting );
};

export { registerDynamicSourceStore };
