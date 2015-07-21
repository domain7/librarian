# Require any additional compass plugins here.
require 'sass-globbing' # https://github.com/chriseppstein/sass-globbing
require 'susy' # http://susy.oddbird.net/

# Set this to the root of your project when deployed:
http_path = "/"
css_dir = "assets/stylesheets/css"
sass_dir = "assets/stylesheets/scss"
images_dir = "assets/images"
fonts_dir = "assets/stylesheets/sassyplate/fonts"
javascripts_dir = "assets/js"
additional_import_paths = ["theme", "modules"]

# You can select your preferred output style here (can be overridden via the command line):
# output_style = :expanded or :nested or :compact or :compressed
# Use :expanded for development and :compressed for production
output_style = :expanded

# Source maps! They're great!
# http://thesassway.com/intermediate/using-source-maps-with-sass
sourcemap = true

# To enable relative paths to assets via compass helper functions. Uncomment:
relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
## line_comments = false

# We only use the scss syntax
preferred_syntax = :scss
