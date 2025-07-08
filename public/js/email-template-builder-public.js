(function( $ ) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed that you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope of this
     * function.
     *
     * This external file is loaded jQuery.ready via the plugin loader.
     */

    $(function() { // Shorthand for $(document).ready()

        console.log('Email Template Builder Public JS Loaded');
        console.log('AJAX Object:', etb_ajax_obj); // Check if our localized data is available

        // Initialize jQuery UI Tabs
        if ($('#etb-preview-tabs').length) {
            $('#etb-preview-tabs').tabs();
        }

        // Initialize jQuery UI Sortable (example on a placeholder container)
        if ($('#etb-sidebar-panel').length) { // Check if the panel exists
            // This will be refined later to target the actual list of sortable sections
            // For now, making the entire sidebar panel sortable if it had direct children
            // that were meant to be sortable.
            // Example: $("#etb-sortable-sections-container").sortable({ ... });
        }

        // --- Mock AJAX calls for testing ---
        // These are just to demonstrate the AJAX setup is working.
        // Replace with actual event handlers (e.g., button clicks) later.

        function testAjaxCalls() {
            // Example: Load Templates
            $.ajax({
                url: etb_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'etb_load_templates',
                    nonce: etb_ajax_obj.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Load Templates AJAX success:', response.data.message);
                    } else {
                        console.error('Load Templates AJAX error:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Load Templates AJAX request failed:', error);
                }
            });

            // Example: Save Template (mock data)
            $.ajax({
                url: etb_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'etb_save_template',
                    nonce: etb_ajax_obj.nonce,
                    template_name: 'My Test Template',
                    html_en: '<p>Hello World</p>',
                    // ... other template data
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Save Template AJAX success:', response.data.message, response.data.data);
                    } else {
                        console.error('Save Template AJAX error:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Save Template AJAX request failed:', error);
                }
            });
        }

        // Uncomment to run test AJAX calls on load:
        // testAjaxCalls();

        // More JS for the builder will go here:
        // - Handling sidebar control changes
        // - Updating iframes
        // - Drag and drop functionality
        // - AJAX for save, load, delete, clone
        // - Export functionality
        // - Reset functionality

    }); // End document ready

})( jQuery );
