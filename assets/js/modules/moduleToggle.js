librarian.moduleToggle = (function($){
	'use strict';

	var moduleToggle = {

		el: '.js-tool-toggle',

		init: function() {

			// Cache el
			moduleToggle.el = $(moduleToggle.el);

			// Click on tool
			moduleToggle.el.on('click.librarian', function(event){

				// Toggle class
				$(this).parents('.lib_module').toggleClass('is-collapsed');

				// Toggle content area
				$(this).parents('.lib_module')
						.find('.lib_module-body')
						.slideToggle(600);

				event.preventDefault();

			});

		}

	};

	/* Document ready
	/* + + + + + + + + + + + + + + + + + + + + + + + + + + + */

	$(document).on('ready', moduleToggle.init);

	return moduleToggle;

})(jQuery);
