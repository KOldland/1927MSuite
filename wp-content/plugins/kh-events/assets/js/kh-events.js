// KH Events JavaScript
jQuery(document).ready(function($) {
    // Calendar navigation
    $('.kh-nav-link').click(function(e) {
        e.preventDefault();
        var month = $(this).data('month');
        var year = $(this).data('year');
        var calendarContainer = $(this).closest('.kh-events-calendar');

        $.ajax({
            url: kh_events_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kh_load_calendar',
                month: month,
                year: year,
                category: calendarContainer.data('category'),
                tag: calendarContainer.data('tag'),
            },
            success: function(response) {
                if (response.success) {
                    calendarContainer.replaceWith(response.data.html);
                }
            }
        });
    });

    // Add any interactive functionality here
    console.log('KH Events loaded');
});