<?php

/**
 * A utility for building UI libraries
 *
 * @package Librarian
 *
 */
class Librarian {

	/**
	 * Title of the library
	 * @property mixed Title of the library
	 */
	public $title = false;
	public $description = false;
	public $root_path = '';
	public $settings_file = 'library.json';
	public $stylesheet_path = 'assets/stylesheets/css/librarian.css';
	public $template_path = 'theme';
	public $scripts = array();
	public $js_modules = array();
	public $js_modules_dir = 'assets/js/modules';
	public $nav = false; // output for nav
	public $modules = false; // output for modules
	public $modules_raw = false;
	public $settings = false;

	private $defaults = array(
		'title' => 'UI module library',
		'description' => 'A UI module library for atomic web design.',
		'directory' => 'modules',
		'file_types_for_download' => array('scss', 'js'),
		'temp_dir' => '/var/asd',
		'exclude_js_modules' => array(),
		'exclude_modules' => array()
	);

	private $Parsedown = false;
	private $scripts_added = array();


	/**
	 * The `__construct()` gets the settings from `library.json`, sets the root path, instantiates the markdown parser and throws an error if we can't write to the tmp dir.
	 *
	 * @param Librarian
	 *
	 * @uses Librarian::root_path
	 * @uses Librarian::settings
	 * @uses Librarian::Parsedown
	 * @uses Librarian::kill()
	 * @uses Librarian::get_js_modules()
	 *
	 */
	function __construct() {

		// Set the default temp path to the value of sys_get_temp_dir()
		$this->defaults['temp_dir'] = sys_get_temp_dir();

		// Get library settings
		$this->get_settings();

		// Set $root_path
		$this->root_path = $_SERVER['DOCUMENT_ROOT'];

		// Load the Parsedown Markdown parser and store handler
		require "class.Parsedown.php";
		$this->Parsedown = new Parsedown();

		// Load all of the library js plugins used for various plugin tools.
		$this->js_modules = $this->get_js_modules();

		// Check if the temp dir is writable, otherwise die
		if ( !is_writable($this->settings['temp_dir']) ) {
			$this->kill('The temporary directory <code>' . $this->settings['temp_dir'] . '</code> needs to be writable. You can also set a different temp dir in library.json using <code>temp_dir</div>.');
		}

	}


	/**
	 * A custom wrapper for `die()` so that we can make error messages a lot friendlier
	 *
	 * @package Librarian
	 *
	 * @param string $message The error message to display
	 */
	private function kill($message = '') {

		die(
			'<body style="background-color: #e7e7e7;
						  font-family: \'Helvetica Neue\', Arial, sans-serif;
				">
				<div style="background-color: #ffffff;
							padding: 2em;
							border-radius: 5px;
							width: 50%;
							max-width: 550px;
							margin: 10% auto;">
					<h3><code>Librarian.php</code></h3>
					<h2>' . $message . '</h2>
				</div>
			</body>'
		);

	}


	/**
	 * Gets the library settings from the `$settings_file property`, defaults
	 * to library.json. Merges with defaults and sets the title/description.
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::kill()
	 * @uses Librarian::settings_file
	 * @uses Librarian::settings
	 * @uses Librarian::defaults
	 * @uses Librarian::title
	 * @uses Librarian::description
	 *
	 */
	private function get_settings() {

		// If the settings file doesn't exist, throw error
		if ( !file_exists($this->settings_file) ) {
			$this->kill('Looks like library.json is missing. This is needed for setup');
		}

		// Load the settings json file and decode json
		$config = file_get_contents($this->settings_file);
		$config = (array) json_decode($config);

		// Some error handling
		if ( !count($config) ) {
			$this->kill("There's a problem with your library.json file. I'm going to go out on a limb and say it's a trailing comma? Maybe an empty array?");
		}

		// Merge with defaults
		$this->settings = extend_args($config, $this->defaults);

		// Create some shortcut attributes for convenience
		$this->title = $this->settings['title'];
		$this->description = $this->settings['description'];

	}


	/**
	 * Looks at a directory and loads all of the modules, getting info about the files,
	 * and returns an array with the module's info, options, files, and file contents
	 *
	 * @package Librarian
	 *
	 * @param string $directory The directory to look in. Defaults to 'modules' if none provided
	 *
	 * @uses Librarian::settings
	 * @uses Librarian::root_path
	 * @uses Librarian::is_module_file()
	 * @uses Librarian::get_module_assets()
	 * @uses Librarian::get_module_options()
	 * @uses Librarian::scandir_without_invisibles()
	 *
	 * @return array An associative array of modules with info, options, files and file contents
	 */
	public function get_modules($directory = false) {

		// We'll build up this variable with the return value
		$output = false;

		// Set if we're working in a passed in dir or the classes's setting
		$dir = $directory ? $directory : $this->settings['directory'];

		// Create dir path
		$path = $this->root_path . '/' . $dir;

		// Get list of modules
		$modules = scandir($path);

		// If the dir's empty, end early
		if ( !count($modules) ) {
			return;
		}

		// Loop through the dir and find the modules to use
		$count = 0;
		foreach ( $modules as $module_dir_name => $module ) {

			if ( is_dir($dir . '/' . $module) && !is_hidden($module) &! in_array($module, $this->settings['exclude_modules']) ) {

				// First time with match, make output an array
				if ( $count == 0 ) {
					$output = array();
				}

				// Put module name in the array
				$output[$module]['slug'] = $module;

				// Put the module's directory in the array
				$output[$module]['dir'] = $dir;

				// Put module path in the array
				$output[$module]['path'] = $this->root_path . '/' . $dir . '/' . $module;

				// Get file content except for hidden files
				$files = $this->scandir_without_invisibles($path . '/' . $module);

				// If the directory is empty, skip this itteration and remove the item from the modules list
				if ( empty($files) ) {
					unset($modules[$module_dir_name]);
					continue;
				}

				// Set the file extension as the array key, and add to array
				$found_php = false; // Since modules must have a .php file, let's skip ones that don't

				// Find the module's files and add that to the modules array
				foreach ( $files as $file ) {

					// Get the extension
					$ext = explode(".", $file);
					$ext = end($ext);

					// Add to array if it's a module file
					if ( $this->is_module_file($file, $module) ) {
						$output[$module]['files'][$ext] = $this->root_path . '/' . $dir . '/' . $module . '/' . $file;
					} else {
						// Otherwise we're create an 'attachments' array by extension
						$output[$module]['attachments'][$ext][] = $this->root_path . '/' . $dir . '/' . $module . '/' . $file;
					}

					// Set if we found a php file
					if ( $ext == 'php' ) {
						$found_php = true;
					}

				}

				// Modules must have a php file, so let's remove ones that don't
				if ( !$found_php ) {
					unset($modules[$module_dir_name]);
					continue;
				}

				// Put module's assets in the array
				$output[$module]['assets'] = $this->get_module_assets($output[$module]);

				// Put module options in the array
				$output[$module]['options'] = $this->get_module_options($output[$module]);

				$count++;

			}

		} // foreach

		return $output;

	}


	/**
	 * Creates the markup for each module and builds up the array
	 * of scripts to load on this page.
	 *
	 * @param array $module The module array
	 * @return string Markup for the module
	 */
	public function make_module($module) {

		// Load the rendered markup for the module
		// We can only make a module that has markup, so return if there's no php file
		if ( isset($module['files']['php']) ) {
			$markup = $this->render_module($module['files']['php']);
		} else {
			return;
		}

		// Module wrapper
		$output = '<div id="' . $module['dir'] . '-' . $module['slug'] . '" class="lib_module">';

		// If there's a script, add it to the array of scripts to laod on the page
		if ( isset($module['files']['js']) ) {
			$this->scripts[] = $module['dir'] . '/' . $module['slug'] . '/' . basename($module['files']['js']);
		}

		// If the module has additional attached scripts, load them too
		if ( isset($module['attachments']) && isset($module['attachments']['js']) ) {
			foreach ( $module['attachments']['js'] as $script ) {

				// Don't add the same script twice
				if ( in_array(basename($script), $this->scripts_added) ) {
					continue;
				}

				// Add script
				array_unshift($this->scripts, $module['dir'] . '/' . $module['slug'] . '/' . basename($script));

				// Array tracked what's been added
				$this->scripts_added[] = basename($script);

			}
		}

		// Module header
		$output .= '<header class="lib_module-header">';

			// Module title
			$output .= '<h3 class="lib_module-title">' . $module['options']->title . ' <span class="lib_module-class">.' . $module['slug'] . '</h3>';

			// Module tools
			$output .= '<ul class="lib_tools">';
				$output .= '<li>';

					// Anchor link
					$output .= '<a href="#' . $module['dir'] . '-' . $module['slug'] . '" class="lib_tools-tool lib_tools-tool--link" title="Get link">Get link</a>';

					// Collapse
					$output .= '<a href="#" class="lib_tools-tool lib_tools-tool--toggle js-tool-toggle" title="Toggle visiblity">Toggle visibility</a>';

					// Download
					$output .= '<a href="' . $_SERVER['PHP_SELF'] . '?module=' . $module['slug'] . '" class="lib_tools-tool lib_tools-tool--download" title="Download">Download</a>';

				$output .= '</li>';
			$output .= '</ul>';


		$output .= '</header>'; // .lib_module-header

		// Module body
		$output .= '<div class="lib_module-body">';

				// Render the module
				$output .= '<div class="lib_module-rendered">';
					$output .= $markup;
					// Tools
					$output .= '<ul class="lib_tools">';
						$output .= '<li>';

							// Set focus
							$output .= '<a href="#" class="lib_tools-tool lib_tools-tool--focus js-tool-focus" title="Set focus">Focus</a>';

						$output .= '</li>';
					$output .= '</ul>';
				$output .= '</div>'; // .module-rendered

				// If there's markdown documentation, print that now
				if ( isset($module['assets']['md']) ) {
					$output .= '<div class="lib_module-documentation">';
						$output .= $this->Parsedown->text($module['assets']['md']);
					$output .= '</div>'; // .module-documentation
				}

				// If there are variations of the module, render them now too
				if ( isset($module['options']->variants) && count($module['options']->variants) ) {

					$output .= '<div class="lib_module-variants">';

					$output .= '<h4 class="lib_module-variants-title">Available class variations</h4>';

					foreach ( $module['options']->variants as $variant ) {

						$output .= '<div class="lib_module-variant lib_module-variant--' . $variant . '">';

							$output .= '<h5 class="lib_module-variant-title lib_module-class"><span class="lib_module-class-subordinate">.' . $module['slug'] . '</span>.' . $variant . '</h5>';

							// Render the module
							$output .= '<div class="lib_module-rendered">';

								$output .= $this->render_module($module['files']['php'], $variant);

								// Tools
								$output .= '<ul class="lib_tools">';
									$output .= '<li>';

										// Set focus
										$output .= '<a href="#" class="lib_tools-tool lib_tools-tool--focus js-tool-focus" title="Set focus">Focus</a>';

									$output .= '</li>';
								$output .= '</ul>';

							$output .= '</div>';

						$output .= '</div>';

					}

					$output .= '</div>'; // lib_module-variants

				} // if variants


				// Module script dependencies, based on the attachment
				if ( isset($module['attachments']) && isset($module['attachments']['js']) ) {

					$output .= '<div class="lib_module-attachments">';

						$output .= '<h4 class="lib_module-attachments-title">Required scripts</h4>';
						$output .= '<ul class="lib_module-attachments-items">';

							foreach ( $module['attachments']['js'] as $script ) {
								$output .= '<li class="lib_module-attachments-item lib_module-attachments-item--script">';
									$output .= '<a href="' . $module['dir'] . '/' . $module['slug'] . '/' . basename($script) . '">';
										$output .= basename($script);
									$output .= '</a>';
								$output .= '</li>';
							}

						$output .= '</ul>';

					$output .= '</div>'; // .lib_module-attachments

				}


				// Module file attachments
				// If there are attachments AND there are either more than 1 type or the 1 isn't js.
				// This is becasue js is dealt with separately.
				if ( isset($module['attachments']) && ( count($module['attachments'] > 1) || count($module['attachments']) == 1 && !isset($module['attachments']['js']) )  ) {

					$output .= '<div class="lib_module-attachments">';

						$output .= '<h4 class="lib_module-attachments-title">Attached files</h4>';
						$output .= '<ul class="lib_module-attachments-items">';

							foreach ( $module['attachments'] as $extension => $file_group ) {

								// Skip js, we did that
								if ( $extension == 'js' ) {
									continue;
								}

								foreach ( $file_group as $file ) {
									$output .= '<li class="lib_module-attachments-item lib_module-attachments-item--' . $extension . '">';
										$output .= '<a href="' . $module['dir'] . '/' . $module['slug'] . '/' . basename($file) . '">';
											$output .= basename($file);
										$output .= '</a>';
									$output .= '</li>';
								}
							}

						$output .= '</ul>';

					$output .= '</div>'; // .lib_module-attachments

				}


				// Module source code
				// Start with HTML because we always have it.
				// Build temp variable so we can count sources
				$source_markup = $this->make_source_markup('HTML', $markup, 'index.php?rmod=' . $module['dir'] . '/' . $module['slug']);
				$sources = 1;

				$to_print = array(
					'scss' => 'SCSS',
					'js' => 'JavaScript'
				);


				foreach ($to_print as $source => $nice_name) {
					if ( isset($module['assets'][$source]) ) {
						$source_markup .= $this->make_source_markup($nice_name, $module['assets'][$source], $this->link_path($module, $source));
						$sources++;
					}
				}

				$output .= '<div class="lib_module-sources has-sources-' . $sources . '">';

					$output .= $source_markup;

				$output .= '</div>'; // .module-source

			$output .= '</div>'; // .module-body

		$output .= '</div>'; // .module

		return $output;

	}


	/**
	 * Build navigation and saves it to `$this->nav` to be printed in template. Also creates the form for custom builds
	 *
	 * @package Librarian
	 *
	 * @param array $modules The modules we're working with.
	 *
	 * @uses Librarian::nav
	 *
	 */
	public function make_nav($modules) {

		// Bail early if no modules
		if ( !$modules ) {
			return;
		}

		$output = '<nav class="lib_nav">';

			// Form for build script thing
			$output .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="GET">';

			$output .= '<ul class="lib_nav-list">';

				foreach ($modules as $module) {

					$output .= '<li class="lib_nav-list-item">';

						$id = 'builder-' . $module['dir'] . '-' . $module['slug'];

						// Checkbox for building
						$output .= '<input id="' . $id . '" class="lib_checkbox" type="checkbox" name="module[]" value="' . $module['slug'] . '">';
						$output .= '<label for="' . $id . '" class="lib_checkbox-label"></label>';

						$output .= '<a href="#' . $module['dir'] .'-' . $module['slug'] . '" class="lib_nav-item">';
							$output .= $module['options']->title;
						$output .= '</a>'; // .lib_nav-item

					$output .= '</li>'; // .lib_nav-list-item

				}

			$output .= '</ul>';

			$output .= '<button class="lib_button lib_nav-download" type="submit"><i class="fa fa-download"></i>Custom build</button>';

			$output .= '</form>';

		$output .= '</nav>'; // .lib_nav

		$this->nav = $output;

	}


	/**
	 * Bootstraps the whole library. This sets off all the dominos.
	 *
	 * @package Librarian
	 *
	 * @param string $directory Specific directory to search for modules. Will default to `$this->settings['directory']`, which defaults to `/modules`.
	 *
	 * @uses Librarian::settings
	 * @uses Librarian::modules_raw
	 * @uses Librarian::modules
	 * @uses Librarian::get_modules()
	 * @uses Librarian::make_nav()
	 * @uses Librarian::make_module()
	 */
	public function make_library($directory = false) {

		// Variable to store output
		$output = false;

		// Set if we're working in a passed in dir or the classes's setting
		$dir = $directory ? $directory : $this->settings['directory'];

		// Get all our modules
		$modules = $this->get_modules($dir);

		// Store the raw values for later
		$this->modules_raw = $modules;

		// If there are no modules, bail
		if ( $modules ) {

			// Create a nav list
			$output .= $this->make_nav($modules);

			$output .= '<div class="lib_modules">';

				foreach ( $modules as $module ) {

					$output .= $this->make_module($module);

				}

			$output .= '</div>'; // .lib_modules


		} else {
			return;
		}

		// Print the modules!

		$this->modules = $output;

	}


	/**
	 * Check if a file is a module's file or an extra file
	 *
	 * @package Librarian
	 *
	 * @param string $file File path to check
	 * @param string $module Module name
	 *
	 * @return bool Whether or not this is a module file or attachment
	 *
	 */
	private function is_module_file($file, $module) {

		// Get just the name of the module file, without underscores or extensions
		$info = pathinfo($file);
		$name = basename($file,'.'.$info['extension']);

		// For scss files, remove leading underscore
		if ( $info['extension'] == 'scss' && substr($name, 0, 1) == '_' ) {
			$name = substr($name, 1);
		}

		return $name == $module;

	}


	/**
	 * Make the link path for raw file
	 *
	 * @package Librarian
	 *
	 * @param array $module The module array
	 * @param string $lang Which language this is
	 *
	 */
	private function link_path($module, $lang) {
		return $module['dir'] . '/' . $module['slug'] . '/' . basename($module['files'][$lang]);
	}


	/**
	 * Get a module's options from it's `{module}.json` file
	 *
	 * @package Librarian
	 *
	 * @param array $module The module array
	 * @return array JSON decoded options
	 *
	 */
	private function get_module_options($module) {

		// If there a json file return it json decoded, otherwise
		// let's just return an options array with slug as title.
		// That way we can always assume a title later on.
		if ( isset($module['assets']['json']) ) {
			return json_decode($module['assets']['json']);
		} else {
			$options = new stdClass;
			$options->title = $module['slug'];
			return $options;
		}

	}


	/**
	 * Load all of the files associated with a module
	 *
	 * @package Librarian
	 *
	 * @param array $module The module array
	 * @return array An array of loaded file contents
	 */
	private function get_module_assets($module) {

		// Array to return
		$assets = array();

		// Loop through files
		if ( !count($module['files']) ) {
			return;
		}
		foreach ( $module['files'] as $type => $file ) {

			// Let's skip the PHP itteration because we need to do weird things to print classes
			if ( $type == 'php' ) {
				continue;
			}

			$assets[$type] = file_get_contents($file);

		}

		return $assets;

	}


	/**
	 * Render module's php markup file.
	 *
	 * @package Librarian
	 *
	 * @param string $file 	The module file to render
	 * @param string $class  Optional, the class to add, mainly for variations
	 */
	public function render_module($file, $class = false) {

		$output = '';

		// If a class is set
		$class = $class ? ' ' . $class : '';

		// Render the module
		ob_start();
			include $file;
		$output .= ob_get_clean();

		return $output;

	}


	/**
	 * Render source code for a module
	 *
	 * @package Librarian
	 *
	 * @param string $language Printable name of language of source file
	 * @param string $source The actual source code to print
	 * @param bool $raw If the link to raw view should be included
	 *
	 * @return string Generated html markup
	 *
	 */
	private function make_source_markup($language, $source, $raw = false) {

		$output = '';

		// Language class
		$language_class = $language == 'HTML' ? 'language-markup' : 'language-' . strtolower($language);

		$output .= '<div class="lib_module-source lib_module-source--' . strtolower($language) . '">';

			// Title
			$output .= '<h4 class="lib_module-source-language">' . $language . '</h4>';

			// Source printing
			$output .= '<div class="lib_module-source-code">';
				$output .= '<pre>';
					$output .= '<code class="' . $language_class . '">';
						$output .= trim(htmlentities($source));
					$output .= '</code>';
				$output .= '</pre>';
			$output .= '</div>'; // lib_module-source-code.

			// Link to raw
			if ( $raw ) {
				$output .= '<ul class="lib_tools">';
					$output .= '<li>';
						$output .= '<a href="' . $raw . '" class="lib_tools-tool lib_tools-tool--raw" title="View raw">View raw</a>';
					$output .= '</li>';
				$output .= '</ul>';
			}

		$output .= '</div>'; // .module-source--lang

		return $output;

	}


	/**
	 * Create .zip archive downloads with ZipArchive, sets header to download.
	 *
	 * @package Librarian
	 *
	 * @param array $files Array of files to include in zip download
	 * @param string $filename The filename desired. Will be used as `Librarian-{$filename}.zip`.
	 *
	 * @uses Librarian::settings
	 * @uses ZipArchive PHP's zip archive helper class
	 *
	 */
	private function make_zip($files, $filename = 'modules') {

		$zipdir = $this->settings['temp_dir'];
		$zipname = $zipdir . '/Librarian-' . $filename . '.zip';
		$dl_filename = 'Librarian-' . $filename . '.zip';

		$zip = new ZipArchive;

		$zip->open($zipname, ZipArchive::CREATE);

		// Loop through files and put into zip
		foreach ( $files as $type => $files ) {
			foreach ( $files as $module => $file ) {

				$zip->addFile($file, $type . '/' . basename($file));

			}
		}

		$zip->close();

		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename=' . $dl_filename);
		header('Content-Length: ' . filesize($zipname));
		readfile($zipname);
		unlink($zipname);

	}


	/**
	 * Since downloads are submitted to `index.php` this handles downlaod requests on page load and determines which files should be included in download.
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::make_zip()
	 * @uses Librarian::modules_raw
	 * @uses Librarian::settings
	 *
	 */
	public function init_downloader() {


		// Bail if a module isn't set
		if ( !isset($_GET['module']) ) {
			return;
		}

		$sanitized = array();
		$files = array();
		$filename = 'modules';

		// Sanitize _get vars
		if ( is_array($_GET['module']) ) {
			foreach ( $_GET['module'] as $var ) {
				$sanitized[] = preg_replace('/[^-a-zA-Z0-9_]/', '', $var);
			}
			// For a single module, let's make the filename more specific
			if ( count($_GET['module']) < 2 ) {
				$filename = $sanitized[0];
			}
		} else {
			$san = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['module']);
			$sanitized[] = $san;
			// For a single module, let's make the filename more specific
			$filename = $san;
		}

		// Build up a list of files to download

		foreach ( $sanitized as $module ) {
			if ( isset($this->modules_raw[$module]) ) {

				$mod_files = $this->modules_raw[$module]['files'];

				// Add the files in the module, based on the settings of which types to add
				foreach ( $this->settings['file_types_for_download'] as $type ) {

					if ( isset($mod_files[$type]) ) {
						$files[$type][$module] = $mod_files[$type];
					}

				} // each type allowed

			} // if module
		} // foreach sanitized

		$this->make_zip($files, $filename);

	}


	/**
	 * Load Librarian js modules. These are used for various module tools like focusing, collapsing and the like
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::js_modules
	 * @uses Librarian::js_modules_dir
	 * @uses Librarian::settings
	 * @uses Librarian::scandir_without_invisibles()
	 *
	 * @return array Found js module scripts
	 *
	 */
	private function get_js_modules() {

		$output = false;

		// Get the js modules available
		$modules = $this->scandir_without_invisibles($this->js_modules_dir);

		if ( $modules ) {

			// Inlclude base instantiation
			$output = array('assets/js/librarian.js');

			// Loop through files, add them if the it isn't set to be excluded
			foreach ( $modules as $module ) {

				if ( !in_array(basename($module, '.js'), $this->settings['exclude_js_modules']) ) {
					$output[] = $this->js_modules_dir . '/' . $module;
				}

			}

		}

		return $output;

	}


	/**
	 * The raw viewer for php.
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::render_module()
	 *
	 */
	public function view_raw() {

		if ( isset($_GET['rmod']) ) {

			// Process and get the module
			$module = preg_replace('/[^-a-zA-Z0-9_\/]/', '', $_GET['rmod']);
			$module = explode("/", $module);
			$module = end($module);
			$module = $this->modules_raw[$module];

			// If the module exists
			if ( isset($module['files']['php']) ) {
				echo '<pre>';
					echo(htmlentities($this->render_module($module['files']['php'])));
				echo '</pre>';
			}

			die();

		}
	}


	/**
	 * Prints with print_r the modules returned. Useful for development purposes.
	 *
	 * @package Librarian
	 *
	 * @param string $directory The directory to look in for modules. Defaults to 'modules'.
	 *
	 */
	public function pre_print_modules($directory = false) {

		// Set if we're working in a passed in dir or the classes's setting
		$dir = $directory ? $directory : $this->directory;

		echo '<pre>';
			print_r($this->get_modules($dir));
		echo '</pre>';

	}


	/**
	 * Allow scripts to be added
	 *
	 * @package Librarian
	 *
	 * @param string $script Path to the script to use
	 * @uses Librarian::scripts
	 *
	 */
	public function add_script($script) {

		if ( file_exists($script) ) {
			$this->scripts[] = $script;
		}

	}


	/**
	 * Getter for built modules
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::modules
	 *
	 * @return array Array of modules found
	 */
	public function get_library() {

		if ( $this->modules ) {
			return $this->modules;
		}

	}


	/**
	 * Print built modules. Used in the theme
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::modules
	 *
	 */
	public function the_library() {
		$modules = $this->modules;
		if ( $modules ) {
			echo $modules;
		} else {
			echo "You don't have any modules. See the readme for help on authoring modules.";
		}

	}


	/**
	 * Getter for the navigation.
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::nav
	 *
	 * @return string Markup for navigation
	 *
	 */
	public function get_navigation() {

		if ( $this->nav ) {
			return $this->nav;
		}

	}


	/**
	 * Print the nav.
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::get_navigation()
	 *
	 */
	public function the_navigation() {

		$nav = $this->get_navigation();
		if ( $nav ) {
			echo $nav;
		}

	}


	/**
	 * Return library scripts
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::scripts
	 *
	 * @return array Array of library scripts
	 *
	 */
	public function get_scripts() {

		return array_merge($this->js_modules, $this->scripts);

	}


	/**
	 * Print all scripts on the page that were found in modules, as well as jQuery
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::get_scripts()
	 *
	 */
	public function the_scripts() {

		$scripts = $this->get_scripts();

		// First let's print jQuery
		echo '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>';
		echo '<script>window.jQuery || document.write(\'<script src="/assets/js/jquery.1.11.3.min.js">\x3C/script>\')</script>';

		foreach ( $scripts as $script ) {
			echo '<script src="' . $script . '"></script>';
		}

	}


	/**
	 * Print library stylesheet
	 *
	 * @package Librarian
	 *
	 * @uses Librarian::stylesheet_path
	 *
	 */
	public function the_stylesheet() {

		echo '<link rel="stylesheet" href="/' . $this->stylesheet_path . '" />';

	}


	/**
	 * Filter out invisibile directories
	 *
	 * @package Librarian
	 *
	 * @param string $directory The directory to do a scandir on.
	 *
	 * @return array Filtered results of `scandir()`
	 *
	 */
	public function scandir_without_invisibles($directory) {
		return array_filter(scandir($directory), function($item) {
			return $item[0] !== '.';
		});

	}

}
