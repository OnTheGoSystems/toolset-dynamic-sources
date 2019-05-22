export default function( value ) {
	switch ( value.constructor ) {
		case Array:
			if ( ! value.length ) {
				return '';
			}
			return value[ 0 ];
		case Object:
			const keys = Object.keys( value );
			if ( ! keys.length ) {
				return '';
			}
			return value[ keys[ 0 ] ];
		default:
			return value.toString();
	}
}
