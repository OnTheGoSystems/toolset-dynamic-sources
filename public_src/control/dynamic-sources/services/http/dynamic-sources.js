import apiFetch from '@wordpress/api-fetch';

export default class DynamicSourcesService {
	get( postTypes ) {
		return apiFetch( { path: `/toolset-dynamic-sources/v1/dynamic-sources?post-type=${ postTypes.join( ',' ) }` } );
	}
}
