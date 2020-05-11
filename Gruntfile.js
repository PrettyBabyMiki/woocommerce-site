module.exports = function( grunt ) {
	'use strict';
	var sass = require( 'node-sass' );

	grunt.initConfig({

		// Setting folder templates.
		dirs: {
			css: 'assets/css',
			fonts: 'assets/fonts',
			images: 'assets/images',
			js: 'assets/js',
			php: 'includes'
		},

		// JavaScript linting with ESLint.
		eslint: {
			src: [
				'<%= dirs.js %>/admin/*.js',
				'!<%= dirs.js %>/admin/*.min.js',
				'<%= dirs.js %>/frontend/*.js',
				'!<%= dirs.js %>/frontend/*.min.js'
			]
		},

		// Sass linting with Stylelint.
		stylelint: {
			options: {
				configFile: '.stylelintrc'
			},
			all: [
				'<%= dirs.css %>/*.scss',
				'!<%= dirs.css %>/select2.scss'
			]
		},

		// Minify .js files.
		uglify: {
			options: {
				ie8: true,
				parse: {
					strict: false
				},
				output: {
					comments : /@license|@preserve|^!/
				}
			},
			admin: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/admin/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/admin/',
					ext: '.min.js'
				}]
			},
			vendor: {
				files: {
					'<%= dirs.js %>/accounting/accounting.min.js': ['<%= dirs.js %>/accounting/accounting.js'],
					'<%= dirs.js %>/jquery-blockui/jquery.blockUI.min.js': ['<%= dirs.js %>/jquery-blockui/jquery.blockUI.js'],
					'<%= dirs.js %>/jquery-cookie/jquery.cookie.min.js': ['<%= dirs.js %>/jquery-cookie/jquery.cookie.js'],
					'<%= dirs.js %>/js-cookie/js.cookie.min.js': ['<%= dirs.js %>/js-cookie/js.cookie.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.pie.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.pie.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.resize.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.resize.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.stack.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.stack.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.time.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.time.js'],
					'<%= dirs.js %>/jquery-payment/jquery.payment.min.js': ['<%= dirs.js %>/jquery-payment/jquery.payment.js'],
					'<%= dirs.js %>/jquery-qrcode/jquery.qrcode.min.js': ['<%= dirs.js %>/jquery-qrcode/jquery.qrcode.js'],
					'<%= dirs.js %>/jquery-serializejson/jquery.serializejson.min.js': [
						'<%= dirs.js %>/jquery-serializejson/jquery.serializejson.js'
					],
					'<%= dirs.js %>/jquery-tiptip/jquery.tipTip.min.js': ['<%= dirs.js %>/jquery-tiptip/jquery.tipTip.js'],
					'<%= dirs.js %>/jquery-ui-touch-punch/jquery-ui-touch-punch.min.js': [
						'<%= dirs.js %>/jquery-ui-touch-punch/jquery-ui-touch-punch.js'
					],
					'<%= dirs.js %>/prettyPhoto/jquery.prettyPhoto.init.min.js': ['<%= dirs.js %>/prettyPhoto/jquery.prettyPhoto.init.js'],
					'<%= dirs.js %>/prettyPhoto/jquery.prettyPhoto.min.js': ['<%= dirs.js %>/prettyPhoto/jquery.prettyPhoto.js'],
					'<%= dirs.js %>/flexslider/jquery.flexslider.min.js': ['<%= dirs.js %>/flexslider/jquery.flexslider.js'],
					'<%= dirs.js %>/zoom/jquery.zoom.min.js': ['<%= dirs.js %>/zoom/jquery.zoom.js'],
					'<%= dirs.js %>/photoswipe/photoswipe.min.js': ['<%= dirs.js %>/photoswipe/photoswipe.js'],
					'<%= dirs.js %>/photoswipe/photoswipe-ui-default.min.js': ['<%= dirs.js %>/photoswipe/photoswipe-ui-default.js'],
					'<%= dirs.js %>/round/round.min.js': ['<%= dirs.js %>/round/round.js'],
					'<%= dirs.js %>/selectWoo/selectWoo.full.min.js': ['<%= dirs.js %>/selectWoo/selectWoo.full.js'],
					'<%= dirs.js %>/selectWoo/selectWoo.min.js': ['<%= dirs.js %>/selectWoo/selectWoo.js'],
					'<%= dirs.js %>/stupidtable/stupidtable.min.js': ['<%= dirs.js %>/stupidtable/stupidtable.js'],
					'<%= dirs.js %>/zeroclipboard/jquery.zeroclipboard.min.js': ['<%= dirs.js %>/zeroclipboard/jquery.zeroclipboard.js']
				}
			},
			frontend: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/frontend/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/frontend/',
					ext: '.min.js'
				}]
			},
			flexslider: {
				files: [{
					'<%= dirs.js %>/flexslider/jquery.flexslider.min.js': ['<%= dirs.js %>/flexslider/jquery.flexslider.js']
				}]
			}
		},

		// Compile all .scss files.
		sass: {
			compile: {
				options: {
					implementation: sass,
					sourceMap: 'none'
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.css %>/',
					src: ['*.scss'],
					dest: '<%= dirs.css %>/',
					ext: '.css'
				}]
			}
		},

		// Generate RTL .css files.
		rtlcss: {
			woocommerce: {
				expand: true,
				cwd: '<%= dirs.css %>',
				src: [
					'*.css',
					'!select2.css',
					'!*-rtl.css'
				],
				dest: '<%= dirs.css %>/',
				ext: '-rtl.css'
			}
		},

		// Minify all .css files.
		cssmin: {
			minify: {
				files: [
					{
						expand: true,
						cwd: '<%= dirs.css %>/',
						src: ['*.css'],
						dest: '<%= dirs.css %>/',
						ext: '.css'
					},
					{
						expand: true,
						cwd: '<%= dirs.css %>/photoswipe/',
						src: ['*.css', '!*.min.css'],
						dest: '<%= dirs.css %>/photoswipe/',
						ext: '.min.css'
					},
					{
						expand: true,
						cwd: '<%= dirs.css %>/photoswipe/default-skin/',
						src: ['*.css', '!*.min.css'],
						dest: '<%= dirs.css %>/photoswipe/default-skin/',
						ext: '.min.css'
					}
				]
			}
		},

		// Concatenate select2.css onto the admin.css files.
		concat: {
			admin: {
				files: {
					'<%= dirs.css %>/admin.css' : ['<%= dirs.css %>/select2.css', '<%= dirs.css %>/admin.css'],
					'<%= dirs.css %>/admin-rtl.css' : ['<%= dirs.css %>/select2.css', '<%= dirs.css %>/admin-rtl.css']
				}
			}
		},

		// Watch changes for assets.
		watch: {
			css: {
				files: ['<%= dirs.css %>/*.scss'],
				tasks: ['sass', 'rtlcss', 'postcss', 'cssmin', 'concat']
			},
			js: {
				files: [
					'GruntFile.js',
					'<%= dirs.js %>/admin/*js',
					'<%= dirs.js %>/frontend/*js',
					'!<%= dirs.js %>/admin/*.min.js',
					'!<%= dirs.js %>/frontend/*.min.js'
				],
				tasks: ['eslint','uglify']
			}
		},

		// Exec shell commands.
		shell: {
			options: {
				stdout: true,
				stderr: true
			},
			e2e_test: {
				command: 'npm run --silent test:single tests/e2e-tests/' + grunt.option( 'file' )
			},
			e2e_tests: {
				command: 'npm run --silent test'
			},
			e2e_tests_grep: {
				command: 'npm run --silent test:grep "' + grunt.option( 'grep' ) + '"'
			},
			contributors: {
				command: [
					'echo "<h2>WooCommerce Admin</h2>" > contributors.html',
					'echo "Generating contributor list for WC Admin since <%= fromDate %>"',
					'./node_modules/.bin/githubcontrib --owner woocommerce --repo woocommerce-admin --fromDate <%= fromDate %>' +
					' --authToken <%= authToken %> --cols 6 --sortBy contributions --format html --sortOrder desc' +
					' --showlogin true --filter "renovate-bot,apps/renovate,renovate,renovate[bot]" >> contributors.html',
					'echo "<h2>WooCommerce Blocks</h2>" >> contributors.html',
					'echo "Generating contributor list for WC Blocks since <%= fromDate %>"',
					'./node_modules/.bin/githubcontrib --owner woocommerce --repo woocommerce-gutenberg-products-block' +
					' --fromDate <%= fromDate %> --authToken <%= authToken %> --cols 6 --sortBy contributions --format html' +
					' --sortOrder desc --showlogin true --filter "renovate-bot,apps/renovate,renovate,renovate[bot]" >> contributors.html',
					'echo "<h2>Action Scheduler</h2>" >> contributors.html',
					'echo "Generating contributor list for Action Scheduler since <%= fromDate %>"',
					'./node_modules/.bin/githubcontrib --owner woocommerce --repo action-scheduler --fromDate <%= fromDate %>' +
					' --authToken <%= authToken %> --cols 6 --sortBy contributions --format html --sortOrder desc' +
					' --showlogin true --filter "renovate-bot,apps/renovate,renovate,renovate[bot]" >> contributors.html',
					'echo "<h2>REST API</h2>" >> contributors.html',
					'echo "Generating contributor list for REST API since <%= fromDate %>"',
					'./node_modules/.bin/githubcontrib --owner woocommerce --repo woocommerce-rest-api --fromDate <%= fromDate %>' +
					' --authToken <%= authToken %> --cols 6 --sortBy contributions --format html --sortOrder desc' +
					' --showlogin true --filter "renovate-bot,apps/renovate,renovate,renovate[bot]" >> contributors.html',
					'echo "<h2>WooCommerce core</h2>" >> contributors.html',
					'echo "Generating contributor list for WC core since <%= fromDate %>"',
					'./node_modules/.bin/githubcontrib --owner woocommerce --repo woocommerce --fromDate <%= fromDate %>' +
					' --authToken <%= authToken %> --cols 6 --sortBy contributions --format html --sortOrder desc' +
					' --showlogin true --filter "renovate-bot,apps/renovate,renovate,renovate[bot]" >> contributors.html',
					'echo "Output generated to contributors.html."',
				].join( '&&' )
			}
		},

		prompt: {
			contributors: {
				options: {
					questions: [
						{
							config: 'fromDate',
							type: 'input',
							message: 'What date (YYYY-MM-DD) should we get contributions since? (i.e. date of previous release)'
						},
						{
							config: 'authToken',
							type: 'input',
							message: 'Provide a personal access token (you must).' +
							' This will allow 5000 requests per hour rather than 60 - use if nothing is generated.'
						}
					]
				}
			}
		},

		// PHP Code Sniffer.
		phpcs: {
			options: {
				bin: 'vendor/bin/phpcs'
			},
			dist: {
				src:  [
					'**/*.php', // Include all php files.
					'!includes/api/legacy/**',
					'!includes/libraries/**',
					'!node_modules/**',
					'!tests/cli/**',
					'!tmp/**',
					'!vendor/**'
				]
			}
		},

		// Autoprefixer.
		postcss: {
			options: {
				processors: [
					require( 'autoprefixer' )
				]
			},
			dist: {
				src: [
					'<%= dirs.css %>/*.css'
				]
			}
		}
	});

	// Load NPM tasks to be used here.
	grunt.loadNpmTasks( 'grunt-sass' );
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-rtlcss' );
	grunt.loadNpmTasks( 'grunt-postcss' );
	grunt.loadNpmTasks( 'grunt-stylelint' );
	grunt.loadNpmTasks( 'gruntify-eslint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-concat' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-prompt' );

	// Register tasks.
	grunt.registerTask( 'default', [
		'js',
		'css'
	]);

	grunt.registerTask( 'js', [
		'eslint',
		'uglify:admin',
		'uglify:frontend'
	]);

	grunt.registerTask( 'css', [
		'sass',
		'rtlcss',
		'postcss',
		'cssmin',
		'concat'
	]);

	grunt.registerTask( 'assets', [
		'js',
		'css'
	]);

	grunt.registerTask( 'e2e-build', [
		'uglify:admin',
		'uglify:frontend',
		'uglify:flexslider',
		'css'
	]);

	grunt.registerTask( 'contributors', [
		'prompt:contributors',
		'shell:contributors'
	]);

	// Only an alias to 'default' task.
	grunt.registerTask( 'dev', [
		'default'
	]);

	grunt.registerTask( 'e2e-tests', [
		'shell:e2e_tests'
	]);

	grunt.registerTask( 'e2e-tests-grep', [
		'shell:e2e_tests_grep'
	]);

	grunt.registerTask( 'e2e-test', [
		'shell:e2e_test'
	]);
};
