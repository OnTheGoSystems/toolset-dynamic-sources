const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

const convertToCamelCase = ( str ) => {
	return str.replace(
		/\W+(.)/g,
		function( match, chr ) {
			return chr.toUpperCase();
		}
	);
};

module.exports = {
	entry: {
		index: path.resolve( process.cwd(), 'public_src', 'index.js' ),
	},
	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'build' ),
		library: 'ToolsetDynamicSources',
	},
	resolve: {
		alias: {
			'lodash-es': 'lodash',
		},
	},
	module: {
		rules: [
			// Babel
			{
				test: /\.js$/,
				exclude: /(node_modules)/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [ '@babel/preset-react' ],
					},
				},
			},
			{
				enforce: 'pre',
				test: /\.js$/,
				exclude: /(node_modules|bower_components)/,
				loader: 'eslint-loader',
				options: {
					emitWarning: true,
				}
			},
			// Scss
			{
				test: /\.scss$/,
				use: [
					{
						loader: MiniCssExtractPlugin.loader,
					},
					{
						loader: 'css-loader',
						options: {
							url: false,
							sourceMap: true,
						},
					},
					{
						loader: 'postcss-loader',
						options: {
							plugins: [
								require( 'autoprefixer' ),
							],
						},
					},
					{
						loader: 'sass-loader',
						options: {
							url: false,
							sourceMap: true,
						},
					},
				],
			},
		]
	},
	externals: [
		{
			react: 'React',
			'react-dom': 'ReactDOM',
			jquery: 'jQuery',
			lodash: 'lodash',
		},
		// Define Wordpress libraries as externals
		// see https://webpack.js.org/configuration/externals/#function
		function( context, request, callback ) {
			const wpLibMatches = request.match( /^\@(wp|wordpress)\/(.*)/i );
			if ( wpLibMatches ) {
				return callback( null, 'window.wp.' + convertToCamelCase( wpLibMatches[ 2 ] ) );
			}
			callback();
		},
		// Define Toolset Common Es as externals
		function( context, request, callback ) {
			const toolsetMatches = request.match( /^toolset\/(.*)/i );
			if ( toolsetMatches ) {
				return callback( null, 'window.toolsetCommonEs.' + toolsetMatches[ 1 ].replace( /\//g, '.' ) );
			}
			callback();
		},
	],
	plugins: [
		new MiniCssExtractPlugin( {
			filename: 'css/[name].css',
			chunkFilename: 'css/[name].css',
		} ),
	]
};
