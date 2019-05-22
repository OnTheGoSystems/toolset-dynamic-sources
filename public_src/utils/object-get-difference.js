import { isEqual, reduce } from 'lodash';

export default function( first, second ) {
	if ( second === undefined ) {
		return {};
	}

	const differentKeys = reduce( first, function( result, value, key ) {
		return isEqual( value, second[ key ] ) ?
			result : result.concat( key );
	}, [] );

	const difference = {};

	differentKeys.forEach( ( key ) => {
		difference[ key ] = second[ key ];
	} );

	return difference;
}

