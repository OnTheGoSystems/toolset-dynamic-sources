function shortcodeOrNot( isDynamic, source, field, provider, noDynamicReturn = '', post = 'current', forceString = false, extraAttributes = {} ) {
	if ( isDynamic && source ) {
		const attributes = { provider, post, source, ...extraAttributes };
		let shortcode = '[tb-dynamic';

		Object.keys( attributes ).forEach( ( name ) => {
			shortcode += ` ${ name }='${ attributes[ name ] }'`;
		} );

		if ( !! field ) {
			shortcode += ` field='${ field }'`;
		}

		if ( forceString ) {
			shortcode += ` force-string='${ forceString }' `;
		}

		shortcode += ']';
		return shortcode;
	}

	return noDynamicReturn;
}

export default function( field, content, options = {} ) {
	if ( ! field || ! field.isActive || ! field.source ) {
		return content;
	}
	const post = options.post || 'current';
	const repeatableFieldShowOnly = options.repeatableFieldShowOnly || false;
	const extraAttributes = options.extraAttributes || {};

	const provider = !! field.customPost ? field.customPost.value : field.provider;

	return shortcodeOrNot(
		field.isActive,
		field.source,
		field.field,
		provider,
		content,
		post,
		repeatableFieldShowOnly,
		extraAttributes
	);
}
