<?php
/**
 * Events Views Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KH_Events_Views {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('kh_events_calendar', array($this, 'calendar_shortcode'));
        add_shortcode('kh_events_list', array($this, 'list_shortcode'));
        add_shortcode('kh_events_day', array($this, 'day_shortcode'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('kh-events-styles', KH_EVENTS_URL . 'assets/css/kh-events.css', array(), KH_EVENTS_VERSION);
        wp_enqueue_script('kh-events-scripts', KH_EVENTS_URL . 'assets/js/kh-events.js', array('jquery'), KH_EVENTS_VERSION, true);
    }

    public function calendar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => 'month',
            'month' => date('m'),
            'year' => date('Y'),
        ), $atts);

        ob_start();
        $this->render_calendar($atts);
        return ob_get_clean();
    }

    private function render_calendar($atts) {
        $month = intval($atts['month']);
        $year = intval($atts['year']);

        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('w', $first_day);

        // Get events for this month
        $events = $this->get_events_for_month($month, $year);

        ?>
        <div class="kh-events-calendar">
            <h2><?php echo date('F Y', $first_day); ?></h2>
            <table class="kh-calendar-table">
                <thead>
                    <tr>
                        <th><?php _e('Sun', 'kh-events'); ?></th>
                        <th><?php _e('Mon', 'kh-events'); ?></th>
                        <th><?php _e('Tue', 'kh-events'); ?></th>
                        <th><?php _e('Wed', 'kh-events'); ?></th>
                        <th><?php _e('Thu', 'kh-events'); ?></th>
                        <th><?php _e('Fri', 'kh-events'); ?></th>
                        <th><?php _e('Sat', 'kh-events'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $day = 1;
                    for ($week = 0; $week < 6; $week++) {
                        echo '<tr>';
                        for ($weekday = 0; $weekday < 7; $weekday++) {
                            if ($week == 0 && $weekday < $day_of_week) {
                                echo '<td class="kh-empty-cell"></td>';
                            } elseif ($day > $days_in_month) {
                                echo '<td class="kh-empty-cell"></td>';
                            } else {
                                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                $day_events = isset($events[$date_key]) ? $events[$date_key] : array();
                                echo '<td class="kh-day-cell">';
                                echo '<div class="kh-day-number">' . $day . '</div>';
                                foreach ($day_events as $event) {
                                    echo '<div class="kh-event-item"><a href="' . get_permalink($event->ID) . '">' . get_the_title($event->ID) . '</a></div>';
                                }
                                echo '</td>';
                                $day++;
                            }
                        }
                        echo '</tr>';
                        if ($day > $days_in_month) break;
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function get_events_for_month($month, $year) {
        $args = array(
            'post_type' => 'kh_event',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_kh_event_start_date',
                    'value' => array($year . '-' . $month . '-01', $year . '-' . $month . '-31'),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            )
        );

        $events = get_posts($args);
        $events_by_date = array();

        foreach ($events as $event) {
            $start_date = get_post_meta($event->ID, '_kh_event_start_date', true);
            if ($start_date) {
                if (!isset($events_by_date[$start_date])) {
                    $events_by_date[$start_date] = array();
                }
                $events_by_date[$start_date][] = $event;
            }
        }

        return $events_by_date;
    }

    public function list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => '',
        ), $atts);

        ob_start();
        $this->render_list($atts);
        return ob_get_clean();
    }

    private function render_list($atts) {
        $args = array(
            'post_type' => 'kh_event',
            'posts_per_page' => intval($atts['limit']),
            'meta_key' => '_kh_event_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_kh_event_start_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        );

        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }

        $events = get_posts($args);

        ?>
        <div class="kh-events-list">
            <?php if ($events): ?>
                <?php foreach ($events as $event): ?>
                    <div class="kh-event-item">
                        <h3><a href="<?php echo get_permalink($event->ID); ?>"><?php echo get_the_title($event->ID); ?></a></h3>
                        <div class="kh-event-meta">
                            <span class="kh-event-date"><?php echo get_post_meta($event->ID, '_kh_event_start_date', true); ?></span>
                            <span class="kh-event-time"><?php echo get_post_meta($event->ID, '_kh_event_start_time', true); ?> - <?php echo get_post_meta($event->ID, '_kh_event_end_time', true); ?></span>
                        </div>
                        <div class="kh-event-excerpt"><?php echo get_the_excerpt($event->ID); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No upcoming events found.', 'kh-events'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function day_shortcode($atts) {
        $atts = shortcode_atts(array(
            'date' => date('Y-m-d'),
        ), $atts);

        ob_start();
        $this->render_day($atts);
        return ob_get_clean();
    }

    private function render_day($atts) {
        $date = $atts['date'];

        $args = array(
            'post_type' => 'kh_event',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_kh_event_start_date',
                    'value' => $date,
                    'compare' => '=',
                    'type' => 'DATE'
                )
            )
        );

        $events = get_posts($args);

        ?>
        <div class="kh-events-day">
            <h2><?php echo date('l, F j, Y', strtotime($date)); ?></h2>
            <?php if ($events): ?>
                <?php foreach ($events as $event): ?>
                    <div class="kh-event-item">
                        <h3><a href="<?php echo get_permalink($event->ID); ?>"><?php echo get_the_title($event->ID); ?></a></h3>
                        <div class="kh-event-meta">
                            <span class="kh-event-time"><?php echo get_post_meta($event->ID, '_kh_event_start_time', true); ?> - <?php echo get_post_meta($event->ID, '_kh_event_end_time', true); ?></span>
                        </div>
                        <div class="kh-event-content"><?php echo get_the_content(null, false, $event->ID); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No events on this day.', 'kh-events'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}