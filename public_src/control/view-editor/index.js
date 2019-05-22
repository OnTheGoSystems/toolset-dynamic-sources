import { Component } from '@wordpress/element';
import { addAction } from '@wordpress/hooks';
import { select, subscribe } from '@wordpress/data';

const { toolsetDynamicSourcesScriptData: i18n } = window;

class ViewEditor extends Component {
	postTypes = [];
	random = null;

	constructor( props = {} ) {
		super( props );

		addAction(
			'tb.dynamicSources.update',
			'toolset-blocks',
			( postTypes ) => {
				this.postTypes = postTypes;
				this.random = Math.random();
				// A random value is always passed as an argument to the store's selector in order to always invoke the
				// relevant resolver. The "data" package seems to be doing some kind of caching when it comes to resolvers
				// with argument values that have already been resolved, To bypass this a random argument needs to be fed in,
				// to fake an argument set change.
				select( i18n.dynamicSourcesStore ).getDynamicSources( postTypes, this.random );
			}
		);

		subscribe( () => {
			if ( this.postTypes.length <= 0 ) {
				return;
			}

			const dynamicSourcesInfo = select( i18n.dynamicSourcesStore ).getDynamicSources( this.postTypes, this.random );

			i18n.postProviders = dynamicSourcesInfo.postProviders;
			i18n.dynamicSources = dynamicSourcesInfo.dynamicSources;
		} );
	}
}

export default ViewEditor;
