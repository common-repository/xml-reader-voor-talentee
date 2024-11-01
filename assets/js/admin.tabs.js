(function( $ ) {
	'use strict';

	$(window).load(function(){

		/**
		 * Enables us to toggle the field categories
		 */
		$(document).on('click', '.vwe-expand-fields a', function(e) {
			e.preventDefault();
			$(this).next().slideToggle('fast', 'linear');
		});

		/**
		 * Enables us to select all checkboxes in one go per categorie
		 */
		$(document).on('click', '.vwe-sort-fields input[type=checkbox]', function(e) {
			$(this).parent().find('li input[type=checkbox]').prop('checked', $(this).is(':checked'));
			var sibs = false;

			$(this).closest('ul').children('li').each(function () {
				if($('input[type=checkbox]', this).is(':checked')) sibs=true;
			});

			$(this).parents('ul').prev().prop('checked', sibs);
		});

		/**
		 * Switches to clicked tab in the back-end when clicked.
		 */
		$(document).on('click', '.nav-tabs li', function(e) {
			e.preventDefault();
			var target = this;

			if (!$(this).hasClass('active')) {
				$($('.active').find('a').attr('href')).fadeOut('fast', 'linear', function() {
					$($(target).find('a').attr('href')).fadeIn('fast', 'linear');
					$('.active').removeClass('active');
					$(target).addClass('active');
				})
			}
			
		});

		/**
		 * Makes an ajax call to the backend, letting it know the vwe-notice
		 * is dismissed and should be saved as dismissed.
		 */
		$(document).on('click', '.vwe-notice .notice-dismiss', function() {
			
			$.ajax({
				url: ajaxurl,
				data: {
					action: 'vwe_notice_dismiss'
				}
			})

		});

		$(document).on('click', '#app-container .save-changes', function(e) {
			e.preventDefault();
			var data = {
				action: 'vwe_save_changes',
				vwe_username: $('#app-container #vwe_username').val(),
				vwe_password: $('#app-container #vwe_password').val(),
				vwe_berichtnaam: $('#app-container #vwe_berichtnaam').val()
			}
			
			$.post(ajaxurl, data, function(res) {

				if (res == true) {
					$('#app-container .save-changes').css('background-color', '#46b450');
					if (
						   $('#app-container #vwe_username').val() != ''
						&& $('#app-container #vwe_password').val() != ''
						&& $('#app-container #vwe_berichtnaam').val() != ''
					) {
						console.log('hoi');
						$('.vwe-credential-error').fadeOut('fast', 'linear');
					}
					setTimeout(function() {
						$('#app-container .save-changes').css('background-color', '#428bca');
					},2000);
				}

			}).fail(function() {

				$('#app-container .save-changes').css('background-color', '#d54e21');
				setTimeout(function() {
					$('#app-container .save-changes').css('background-color', '#428bca');
				},2000);

			});
			
		});

	});

})( jQuery );
