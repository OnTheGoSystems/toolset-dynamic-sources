const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

const defaultConfig = require('./node_modules/@wordpress/scripts/config/webpack.config');

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
		index: path.resolve( process.cwd(), 'public_src', 'index.js' ),
	},
	output: {
		...defaultConfig.output,
		library: 'ToolsetDynamicSources',
	},
	module: {
		...defaultConfig.module,
		rules: [
			{
				enforce: 'pre',
				test: /\.js$/,
				exclude: /(node_modules|bower_components)/,
				loader: 'eslint-loader',
				options: {
					emitWarning: true,
				}
			},
			...defaultConfig.module.rules,
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
	plugins: [
		...defaultConfig.plugins,
		new MiniCssExtractPlugin( {
			filename: 'css/[name].css',
			chunkFilename: 'css/[name].css',
		} ),
	]
};
