module.exports = function( grunt ) {
	'use strict';

	// Load all grunt tasks matching the `grunt-*` pattern.
	require( 'load-grunt-tasks' )( grunt );

	// Show elapsed time.
	require( '@lodder/time-grunt' )( grunt );

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				text_domain: 'tiered-pricing-for-woocommerce',
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!vendor/**',
					],
				},
			},
		},

		checktextdomain: {
			options: {
				text_domain: 'tiered-pricing-for-woocommerce',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d',
				],
			},
			target: {
				files: [{
					src: [
						'**/*.php',
						'!node_modules/**',
						'!vendor/**',
					],
					expand: true,
				}],
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: 'languages',
					mainFile: 'tiered-pricing-for-woocommerce.php',
					potFilename: 'tiered-pricing-for-woocommerce.pot',
					type: 'wp-plugin',
					potHeaders: {
						'report-msgid-bugs-to': 'https://mofizul.com/support/',
						'poedit': true,
						'x-poedit-keywordslist': true,
					},
				},
			},
		},
	});

	grunt.registerTask( 'i18n', [ 'addtextdomain', 'checktextdomain', 'makepot' ] );
	grunt.registerTask( 'build', [ 'i18n' ] );
};
