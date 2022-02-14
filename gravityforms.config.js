const { resolve } = require( 'path' );

module.exports = {
	gulpConfig: {
		browserSync: {
			defaultUrl: 'gravity-forms.local',
			serverName: 'Gravity Forms Dev',
		},
		icons: {
			admin: {
				replaceName: /'gform-icons-admin' !important/g, // regex for the icomoon generated name to replace
				replaceScss: /\$icomoon-font-family: "gform-icons-admin" !default;\n/g, // regex for scss file replace
				varName: 'var(--t-font-family-admin-icons) !important', // the css variable name to replace replaceName with
			},
			theme: {
				replaceName: /'gform-icons-theme' !important/g,
				replaceScss: /\$icomoon-font-family: "gform-icons-theme" !default;\n/g,
				varName: 'var(--t-font-family-theme-icons) !important',
			}
		},
		paths: {
			css_dist: resolve( __dirname, 'css' ),
			css_src: resolve( __dirname, 'src/css' ),
			dev: resolve( __dirname, 'dev' ),
			fonts: resolve( __dirname, 'fonts' ),
			images: resolve( __dirname, 'images' ),
			js_dist: resolve( __dirname, 'assets/js/dist'),
			js_src: resolve( __dirname, 'assets/js/src'),
			legacy_css: resolve( __dirname, 'legacy/css' ),
			npm: resolve( __dirname, 'node_modules' ),
			postcss_assets_base_url: resolve( __dirname, '../' ),
			reports: resolve( __dirname, 'reports/webpack-%s.html' ),
			root: resolve( __dirname, '' ),
			settings_css_dist: resolve( __dirname, 'includes/settings/css' ),
		},
		tasks: [],
		tasksDir: resolve( __dirname, 'gulp-tasks' ),
		webpack: {
			alias: {
				common: resolve( __dirname, 'assets/js/src/common' ),
			},
			overrides: {
				externals: {
					admin: {
						'gform-admin-config': 'gform_admin_config',
						'gform-admin-i18n': 'gform_admin_i18n',
					},
					theme: {
						'gform-theme-config': 'gform_theme_config',
						'gform-theme-i18n': 'gform_theme_i18n',
					},
				},
				output: {
					uniqueName: 'gravityforms',
				},
			},
		}
	},
	requestConfig: {
		site_url : '',
		endpoints: {
			get_something: {
				path       : '/wp-json/gf/v2/get_something',
				rest_params: '',
				nonce      : null,
			},
		},
	},
	webpackConfig: {
		alias: {
			common: resolve( __dirname, 'assets/js/src/common' ),
		},
		paths: {
			src: resolve( __dirname, 'assets/js/src/'),
			dist: resolve( __dirname, 'assets/js/dist/'),
			reports: resolve( __dirname, 'reports/webpack-%s.html' ),
		},
		overrides: {
			externals: {
				admin: {
					'gform-admin-config': 'gform_admin_config',
					'gform-admin-i18n': 'gform_admin_i18n',
				},
				theme: {
					'gform-theme-config': 'gform_theme_config',
					'gform-theme-i18n': 'gform_theme_i18n',
				},
			},
			output: {
				uniqueName: 'gravityforms',
			},
		},
	}
}
