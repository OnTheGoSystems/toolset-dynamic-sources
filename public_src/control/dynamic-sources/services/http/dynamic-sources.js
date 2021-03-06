import apiFetch from '@wordpress/api-fetch';

export default class DynamicSourcesService {
	get( postTypes, previewPostID, viewId ) {
		if ( ! postTypes || ! previewPostID ) {
			return;
		}
		const viewIdString = viewId ? `&view-id=${ viewId }` : '';

		return apiFetch( {
			path: `/toolset-dynamic-sources/v1/dynamic-sources?post-type=${ postTypes.join( ',' ) }&preview-post-id=${ previewPostID }${ viewIdString }`,
		} );
	}
}
