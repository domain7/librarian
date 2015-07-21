# Librarian
**PHP tool for creating SCSS UI module libraries with Compass**

This tool is designed to be used for creating reusable UI libraries to be used across projects. It came out of a need at [Domain7](http://domain7.com) to have a central place for best practices and common elements to be stored; items that get used on almost any project.

Librarian libraries are self hosted and don't require a database.

## Setup
Make sure you have the required dependencies installed. Configure `library.json` as needed (details bellow), and create modules according to the `Authoring modules` section.

### Dependencies
For starters you need [SASS](http://sass-lang.com/) and [Compass](http://compass-style.org/).
The following extensions are also used:

* sass-globbing: [https://github.com/chriseppstein/sass-globbing](https://github.com/chriseppstein/sass-globbing)
* susy: [http://susy.oddbird.net/](http://susy.oddbird.net/)

### Compass watch/compile
`cd` into your project directory and run `compass watch` or `compass compile` from the root directory. Compass will watch `.scss` files in your `/modules` and `/theme` directories.

### library.json
Library configuration happens in `library.json`, which is **required**.

	{
		"title": "UI Module Library",
		"description": "Modules for frontend development",
		"excludeModules": [
			"foo",
			"baz"
		]
	}


#### library.json option

* `title` - (string) Name of the library
* `description` (string) Used as the meta description
* `exclude_modules` - (array) Array of module slugs to exclude from printing
* `file_types_for_download` - (array) Module downloads group files by type in directories. This option lets you specify which types to include. Defaults to `['scss', 'js']`
* `exclude_js_modules` - (array) Exclude specific library JS modules (see 'Built in library JS modules' section). Use module name, without extension.
* `directory` - (string) Allows a custom directory to be used for modules. Defaults to `/modules`.
* `temp_dir` - (string) Allows for a custom temp dir to be used. This is used for creating zip downloads. Defaults to value of `sys_get_temp_dir()`, usually `/var/tmp`.

## Authoring modules
Librarian becomes more useful as the number of modules contained grows. Authoring modules is easy but there is a specific format.

### File naming
To create a modue, create a new directory inside `/modules` named with your module's name. For instance `/modules/social` could be a directory. All modules must have a `.php` file for markup, and an `.scss` file for sass. Additionally you can add a `.js` file and a `.md` markdown file with documentation. All files need to match the directory's name.

SCSS files should be prefixed with an underscore, for example, `_module.scss`.

#### Possible module files
* `module/_module.scss`
* `module/module.js`
* `module/module.php`
* `module/module.md`

#### module.json
Configuration for a module can be set in an optional `module.json` file.

Options:

* `title` - (string) The title of the module. Without this option the module slug (directory name) will be used.
* `variants` - (array) An array of class name variations for a module. The module will be rendered again in the library with each of the class variations.

Example:

```
{
	"title": "Social icons",
	"variants": [
		"social--square"
	]
}
```

#### Additional scripts and module files
You can add additional files to your module directory to be included in the library. These files become attachments, or scripts run on the page, and are linked to from the library. Any file in the module directory with a name that doesn't match the directory name will be treated in this way.

A few notes on this:

* Scripts with the same name are only loaded once. For instance, you could include Owl Slider in multiple modules, but if the file names match the plugin will only be printed on the page once.
* Any package type file (e.g. `.sketch`, `.xcodeproj`, `.mindnode`) need to be zipped because Apache treats them as directories.

### Encapsulation
UI modules are self contained pieces of code to create UI elements. As such, a few principles need to be followed in authoring modules:

* **Self exucting JavaScript**  - All JavaScript modules need to be [self executing](http://markdalgleish.com/2011/03/self-executing-anonymous-functions/)
* **SCSS BEM** - All `.scss` modules need to be contained in a class. At Domain7 [we use BEM](http://domain7.github.io/dev-wiki/css/#bem-+-smacss-prefixes). Since `.scss` for all modules is loaded on the page, proper encapsulation is required, which BEM encourages. BEM also provides the benefit of your code being more self documenting. Class names like `.square` or `.large`reek havoc on an organized stylesheet.

## Theme template methods
Several methods and properties are available for the theme layer.

* `$Librarian->title ` (property) Title of the library, set in `library.json`
* `$Librarian->the_stylesheet()` (method) Prints the stylesheets
* `$Librarian->the_scripts()` (method) Prints the scripts, including jQuery
* `$Librarian->the_navigation()` (method) Prints library navigation
* `$Librarian->the_library()` (method) Prints the UI library modules
* `$Librarian->template_path` (property) Path to the theme

### Scripts
Scripts are printed in the theme using `$Librarian->the_scripts();`. Librarian finds all of the js files in your modules, aggregates them and prints them at the bottom of the document, after printing jQuery.

**Librarian includes jQuery in the library automatically**. Don't include jQuery manually in the theme.

#### Adding additional scripts
You can aditional scripts by calling `$Librarian->add_script(...)`, best called in `/theme/functions.php`. For example, `$Librarian->add_script( $Librarian->template_path . '/js/dist/library_theme.js' );`

#### Built in library JS modules
A few js modules are included for the library itself. These are:

* `moduleToggle` - Used for toggling rendered modules
* `moduleRenderedFocus` - Used for setting focus on a rendered module

## Sassyplate
At Domain7, we have a [boilerplate for SCSS](http://github.com/domain7/sassyplate). To base a UI library on that boilerplate, clone that repo (`https://github.com/domain7/sassyplate.git`) into a directory named `sassyplate` in `assets/stylesheets`, then include it in `assets/stylesheets/scss/librarian.scss` like such underneath `@import 'susy';`.

	// Import Sassyplace
	@import '../sassyplate/stylesheets/scss/screen';

## Feature roadmap
The following features are planned for future releases:

* Ability to structure modules in subdirectories inside the modules directory
* Display grouped modules in groups on one page or separate pages
* Use waypoints.js for sticky subnav
* A vanilla CSS version of Librarian
* Better theme system

## Other projects used
* Parsedown: [https://github.com/erusev/parsedown](https://github.com/erusev/parsedown)
