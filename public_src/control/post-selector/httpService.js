import apiFetch from '@wordpress/api-fetch';

export default class ViewService {
	baseUrl = '/toolset-dynamic-sources/v1';

	savePreviewPostId( ctId, previewPostId ) {
		return apiFetch(
			{
				path: `${ this.baseUrl }/preview-post?ctId=${ ctId }&previewPostId=${ previewPostId }`,
				method: 'POST',
			}
		);
	}
}
