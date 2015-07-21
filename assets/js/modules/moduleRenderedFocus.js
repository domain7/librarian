librarian.moduleRenderedFocus = (function($){
	'use strict';

	var moduleRenderedFocus = {

		el: '.js-tool-focus',

		init: function() {

			// Cache el
			moduleRenderedFocus.el = $(moduleRenderedFocus.el);

			// Click on tool
			moduleRenderedFocus.el.on('click.librarian', function(event){

				// Find the rendered module
				var mod = $(this).parents('.lib_module-rendered');

				mod.attr('tabindex', 0)
				   .focus()
				   .on('blur', function() {
				   		this.removeAttribute('tabindex');
				   });

				event.preventDefault();

			});

		}

	};

	/* Document ready
	/* + + + + + + + + + + + + + + + + + + + + + + + + + + + */

	$(document).on('ready', moduleRenderedFocus.init);

	return moduleRenderedFocus;

})(jQuery);
