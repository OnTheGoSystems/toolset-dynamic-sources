import { __, sprintf } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { addAction, doAction } from '@wordpress/hooks';

import { isEqual } from 'lodash';

const { toolsetDynamicSourcesScriptData: i18n } = window;

const getContentFromCache = ( provider, post, source, field ) => {
	const cache = ( !! i18n.cacheForViews && !! i18n.cacheForViews[ post ] ) ? i18n.cacheForViews[ post ] : i18n.cache;

	let content = '';

	if ( 'undefined' !== typeof cache[ source ] ) {
		content = cache[ source ];
	}

	if (
		'undefined' !== typeof cache[ provider ] &&
		'undefined' !== typeof cache[ provider ][ source ] &&
		post === cache[ provider ][ 'post-id' ]
	) {
		content = cache[ provider ][ source ];
	}

	if ( 'undefined' !== typeof content[ field ] ) {
		content = content[ field ];
	}

	return '' !== content ? { sourceContent: content } : null;
};

const fetchDynamicContent = async( provider, post, source, field = null ) => {
	const maybeContentIsCached = getContentFromCache( provider, post, source, field );
	if ( maybeContentIsCached ) {
		return maybeContentIsCached;
	}

	let path = `/toolset-dynamic-sources/v1/render-source/?source=${ source }&provider=${ provider }&post=${ post }`;

	let subpath = '';

	if ( field ) {
		subpath += `field=${ field }`;
	}

	if ( '' !== subpath ) {
		path += `&${ subpath }`;
	}

	try {
		return await apiFetch( { path } );
	} catch ( error ) {
		const errorMessage = sprintf(
			// translators: placeholder is an error message.
			__( 'Something went wrong while fetching the dynamic content from the selected source, with message: "%s".', 'wpv-views' ),
			error.message
		);

		dispatch( 'core/notices' ).createErrorNotice(
			errorMessage,
			{ id: 'toolset_blocks_dynamic_source_content_preloading_error' }
		);

		return null;
	}
};

const fetchCache = async( postID ) => {
	try {
		return await apiFetch( { path: `/toolset-dynamic-sources/v1/get-cache?post=${ postID }` } );
	} catch ( error ) {
		// No notice displayed to avoid users to get confused. Some non-related Toolset error might cause an error.
		return null;
	}
};

/**
 * It searchs posts
 * @param {string} text
 * @returns {Promise}
 */
const searchPost = text => {
	return new Promise( resolve => {
		apiFetch( { path: `/toolset-dynamic-sources/v1/search-post?s=${ text }` } )
			.then( result => {
				const posts = result.map( item => {
					return { label: item.title, value: `custom_post_type|${ item.post_type }|${ item.id }` };
				} );

				resolve( posts );
			} );
	} );
};

/**
 * Loads the source depending on custom post data
 *
 * @param {object} data Select data
 */
const loadSourceFromCustomPost = async( data ) => {
	const [ , postType, postId ] = data.value.split( '|' );
	const source = await apiFetch( { path: `toolset-dynamic-sources/v1/get-source?post_type=${ postType }&post_id=${ postId }` } );

	// Updates `toolsetBlocksStrings`
	const providerId = data.value;

	if ( ! i18n.postProviders.find( item => item.value === providerId ) ) {
		i18n.postProviders.push( { value: providerId, label: data.label } );
	}
	i18n.dynamicSources[ providerId ] = source.__current_post;

	fetchCache( postId )
		.then( cache => {
			i18n.cache[ providerId ] = cache.__current_post;
			doAction( 'tb.caching.updated' );
		} );
	addAction(
		'tb.caching.tick',
		'toolset-blocks',
		async( updatedCache ) => {
			const sourceCache = await fetchCache( postId );
			if ( ! updatedCache[ providerId ] || ! isEqual( updatedCache[ providerId ], sourceCache.__current_post ) ) {
				i18n.cache[ providerId ] = sourceCache.__current_post;
				doAction( 'tb.caching.updated' );
			}
		}
	);
};

export { fetchDynamicContent, fetchCache, searchPost, loadSourceFromCustomPost };
