const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

module.exports = [
	{
		...defaultConfig,
		entry: {
			...defaultConfig.entry(),
			'js/admin': './resources/js/admin.js',
			'js/frontend': './resources/js/frontend.js',
			'css/admin': './resources/css/admin.scss',
			'css/frontend': './resources/css/frontend.scss',
		},
		output: {
			...defaultConfig.output,
			filename: '[name].js',
			path: __dirname + '/assets/',
		},
		module: {
			rules: [
				...defaultConfig.module.rules,
				{
					test: /\.svg$/,
					issuer: /\.(j|t)sx?$/,
					use: ['@svgr/webpack', 'url-loader'],
					type: 'javascript/auto',
				},
				{
					test: /\.svg$/,
					issuer: /\.(sc|sa|c)ss$/,
					type: 'asset/inline',
				},
				{
					test: /\.(bmp|png|jpe?g|gif)$/i,
					type: 'asset/resource',
					generator: {
						filename: 'images/[name].[hash:8][ext]',
					},
				},
			],
		},
		plugins: [
			...defaultConfig.plugins,
			// new CopyWebpackPlugin({
			// 	patterns: [
			// 		{
			// 			from: path.resolve(__dirname, 'resources/js/owlcarousel'),
			// 			to: path.resolve(__dirname, 'assets/js/owlcarousel'),
			// 		},
			// 		{
			// 			from: path.resolve(__dirname, 'resources/css/owlcarousel'),
			// 			to: path.resolve(__dirname, 'assets/css/owlcarousel'),
			// 		}
			// 	],
			// }),
			new RemoveEmptyScriptsPlugin({
				stage: RemoveEmptyScriptsPlugin.STAGE_AFTER_PROCESS_PLUGINS,
				remove: /\.(js)$/,
			}),
		],
	},
];
