'use strict';
module.exports = function(grunt) {

  // Load grunt tasks automatically
  require('load-grunt-tasks')(grunt);

  // Project configuration.
  grunt.initConfig({
    // Metadata.
    pkg: grunt.file.readJSON('package.json'),
    banner: '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' +
      '<%= grunt.template.today("yyyy-mm-dd") %>\n' +
      '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' +
      '* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.client %>;' +
      '*/\n',
    uglify: {
      options: {
        banner: '<%= banner %>',
        sourceMap: true,
        beautify: false,
        mangle: true,
        compress: {
          drop_console: false,
          drop_debugger: false
        }
      },
      all: {
        files: {
          'js/dist/library_theme.js': [
            'js/src/theme.js',
            'js/src/modules/*.js',
            'js/src/vendor/prism.js'
          ]
        }
      }
    },
    jshint: {
      options: {
        jshintrc: '.jshintrc',
        reporter: require('jshint-stylish'),
        force: true
      },
      all: {
        src: [
          'Gruntfile.js',
          'js/src/modules'
        ]
      }
    },
    modernizr: {
      dist: {
        // [REQUIRED] Path to the build you're using for development.
        devFile: 'remote',
        outputFile: 'js/src/vendor/modernizr-custom.js',
        extra: {
          shiv: true,
          printshiv: false,
          load: true,
          mq: false,
          cssclasses: true
        },
        extensibility: {
          addtest : false,
          prefixed : false,
          teststyles : false,
          testprops : false,
          testallprops : false,
          hasevents : false,
          prefixes : false,
          domprefixes : false,
          cssclassprefix: ''
        },
        //Add any test not in your JS/CSS here:
        tests: [],
        files: {
          src: [
            'stylesheets/scss/{,*/}*.scss',
            'js/src/{,*/}*.js',
            '!js/src/vendor/{,*/}*.js'
          ]
        },
        matchCommunityTests: true,
        // Have custom Modernizr tests? Add paths to their location here.
        customTests: []
      }
    },

    watch: {
      js: {
        files: ['js/src/{,*/}*.js'],
        tasks: ['js']
      },
    }
  });

  //Tasks
  grunt.registerTask('default', ['js']);
  grunt.registerTask('js', ['jshint', 'uglify']);

};

