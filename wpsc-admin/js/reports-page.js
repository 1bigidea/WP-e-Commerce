/**
 * WPSC_Reports_Page object and functions.
 *
 * Dependencies: jQuery, jQuery.query
 *
 * The following properties of WPSC_Reports_Page have been set by wp_localize_script():
 * - current_tab: The ID of the currently active tab
 * - nonce      : The nonce used to verify request to load tab content via AJAX
 */

/**
 * @requires jQuery
 * @requires jQuery.query
 */

(function($){

	$.extend(WPSC_Reports_Page, /** @lends WPSC_Reports_Page */ {
		/**
		 * Set to true if there are modified settings.
		 * @type {Boolean}
		 * @since 3.8.8
		 */
		unsaved_settings : false,

		/**
		 * Event binding for WPSC_Reports_Page
		 * @since 3.8.8
		 */
		init : function() {
			// make sure the event object contains the 'state' property
			$.event.props.push('state');

			// set the history state of the current page
			if (history.replaceState) {
				(function(){
					history.replaceState({url : location.search + location.hash}, '', location.search + location.hash);
				})();
			}

			// load the correct reports tab when back/forward browser button is used

			$(window).on('popstate', WPSC_Reports_Page.event_pop_state);
			$(function(){
				var wpsc_options = $('#wpsc_options');
				wpsc_options.on( 'click' , 'a.nav-tab'              , WPSC_Reports_Page.event_tab_button_clicked);
				wpsc_options.on( 'change', 'input, textarea, select', WPSC_Reports_Page.event_settings_changed);
				wpsc_options.on( 'submit', '#wpsc-reports-form'    , WPSC_Reports_Page.event_settings_form_submitted);
				$(window).on('beforeunload', WPSC_Reports_Page.event_before_unload);
				$(WPSC_Reports_Page).trigger('WPSC_Reports_tab_loaded');
				$(WPSC_Reports_Page).trigger('WPSC_Reports_tab_loaded_' + WPSC_Reports_Page.current_tab);
				$('.settings-error').insertAfter('.nav-tab-wrapper');
			});
		},

		/**
		 * This prevents the confirm dialog triggered by event_before_unload from being displayed.
		 * @since 3.8.8
		 */
		event_reports_form_submitted : function() {
			WPSC_Reports_Page.unsaved_settings = false;
		},

		/**
		 * Mark the page as "unsaved" when a field is modified
		 * @since 3.8.8
		 */
		event_settings_changed : function() {
			WPSC_Reports_Page.unsaved_settings = true;
		},

		/**
		 * Display a confirm dialog when the user is trying to navigate
		 * away with unsaved settings
		 * @since 3.8.8
		 */
		event_before_unload : function() {
			if (WPSC_Reports_Page.unsaved_settings) {
				return WPSC_Reports_Page.before_unload_dialog;
			}
		},

		/**
		 * Load the settings tab when tab buttons are clicked
		 * @since 3.8.8
		 */
		event_tab_button_clicked : function() {
			var href = $(this).attr('href');
			WPSC_Reports_Page.load_tab(href);
			return false;
		},

		/**
		 * When back/forward browser button is clicked, load the correct tab
		 * @param {Object} e Event object
		 * @since 3.8.8
		 */
		event_pop_state : function(e) {
			if (e.state) {
				WPSC_Reports_Page.load_tab(e.state.url, false);
			}
		},

		/**
		 * Display a small spinning wheel when loading a tab via AJAX
		 * @param  {String} tab_id Tab ID
		 * @since 3.8.8
		 */
		toggle_ajax_state : function(tab_id) {
			var tab_button = $('a[data-tab-id="' + tab_id + '"]');
			tab_button.toggleClass('nav-tab-loading');
		},

		/**
		 * Use AJAX to load a tab to the reports page. If there are unsaved settings in the
		 * current tab, a confirm dialog will be displayed.
		 *
		 * @param  {String}  tab_id The ID string of the tab
		 * @param  {Boolean} push_state True (Default) if we need to history.pushState.
		 *                              False if this is a result of back/forward browser button being pushed.
		 * @since 3.8.8
		 */
		load_tab : function(url, push_state) {
			if (WPSC_Reports_Page.unsaved_settings && ! confirm(WPSC_Reports_Page.ajax_navigate_confirm_dialog)) {
				return;
			}

			if (typeof push_state == 'undefined') {
				push_state = true;
			}

			var query = $.query.load(url);
			var tab_id = query.get('tab');
			var post_data = $.extend({}, query.get(), {
				'action'      : 'navigate_reports_tab',
				'nonce'       : WPSC_Reports_Page.navigate_reports_tab_nonce,
				'current_url' : location.href,
				'tab'         : tab_id
			});
			var spinner = $('#wpsc-reports-page-title .ajax-feedback');

			spinner.addClass('ajax-feedback-active');
			WPSC_Reports_Page.toggle_ajax_state(tab_id);

			// pushState to save this page load into history, and alter the address field of the browser
			if (push_state && history.pushState) {
				history.pushState({'url' : url}, '', url);
			}

			/**
			 * Replace the option tab content with the AJAX response, also change
			 * the action URL of the form and switch the active tab.
			 * @param  {String} response HTML response string
			 * @since 3.8.8
			 */
			var ajax_callback = function(response) {
				if (! response.is_successful) {
					alert(response.error.messages.join("\n"));
					return;
				}
				var t = WPSC_Reports_Page;
				t.unsaved_settings = false;
				t.toggle_ajax_state(tab_id);
				$('#options_' + WPSC_Reports_Page.current_tab).replaceWith(response.obj.content);
				WPSC_Reports_Page.current_tab = tab_id;
				$('.reports-error').remove();
				$('.nav-tab-active').removeClass('nav-tab-active');
				$('[data-tab-id="' + tab_id + '"]').addClass('nav-tab-active');
				$('#wpsc_options_page form').attr('action', url);
				$(t).trigger('WPSC_Reports_tab_loaded');
				$(t).trigger('WPSC_Reports_tab_loaded_' + tab_id);
				spinner.removeClass('ajax-feedback-active');
			};

			$.wpsc_post(post_data, ajax_callback);
		}
	});

})(jQuery);

WPSC_Reports_Page.init();
