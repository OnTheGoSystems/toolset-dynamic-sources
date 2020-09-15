import { CUSTOM_POST_UNIQUE_SLUG } from './constants';

export default function( provider ) {
	return provider.match( /^custom_post_type\|[^\|]+\|\d+$/ ) ||
		provider === CUSTOM_POST_UNIQUE_SLUG ||
		provider.startsWith( 'toolset_relationship' );
}
