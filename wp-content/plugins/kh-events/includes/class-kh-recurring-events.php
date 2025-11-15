<?php
/**
 * Recurring Events Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KH_Recurring_Events {

    public function __construct() {
        add_filter('kh_get_events_for_month', array($this, 'include_recurring_events'), 10, 3);
        add_filter('kh_get_events_for_list', array($this, 'include_recurring_events_list'), 10, 2);
    }

    public function include_recurring_events($events, $month, $year) {
        $recurring_events = $this->get_recurring_events();

        foreach ($recurring_events as $event) {
            $occurrences = $this->get_recurring_occurrences($event, $month, $year);
            foreach ($occurrences as $date) {
                if (!isset($events[$date])) {
                    $events[$date] = array();
                }
                $events[$date][] = $event;
            }
        }

        return $events;
    }

    public function include_recurring_events_list($events, $args) {
        $recurring_events = $this->get_recurring_events();

        foreach ($recurring_events as $event) {
            $occurrences = $this->get_recurring_occurrences_in_range($event, $args['start_date'], $args['end_date']);
            foreach ($occurrences as $date) {
                // Add to events array if not already present
                $already_included = false;
                foreach ($events as $existing_event) {
                    if ($existing_event->ID === $event->ID) {
                        $already_included = true;
                        break;
                    }
                }
                if (!$already_included) {
                    $events[] = $event;
                    break; // Only add once for list view
                }
            }
        }

        return $events;
    }

    private function get_recurring_events() {
        $args = array(
            'post_type' => 'kh_event',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_kh_event_recurring',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );

        return get_posts($args);
    }

    private function get_recurring_occurrences($event, $month, $year) {
        $start_date = get_post_meta($event->ID, '_kh_event_start_date', true);
        $recurring_type = get_post_meta($event->ID, '_kh_event_recurring_type', true);
        $recurring_end_date = get_post_meta($event->ID, '_kh_event_recurring_end_date', true);
        $interval = intval(get_post_meta($event->ID, '_kh_event_recurring_interval', true)) ?: 1;

        if (!$start_date || !$recurring_type) {
            return array();
        }

        $occurrences = array();
        $current_date = strtotime($start_date);
        $end_timestamp = $recurring_end_date ? strtotime($recurring_end_date) : strtotime('+1 year', $current_date);
        $month_start = strtotime($year . '-' . $month . '-01');
        $month_end = strtotime('+1 month -1 day', $month_start);

        while ($current_date <= $end_timestamp) {
            if ($current_date >= $month_start && $current_date <= $month_end) {
                $occurrences[] = date('Y-m-d', $current_date);
            }

            // Calculate next occurrence
            switch ($recurring_type) {
                case 'daily':
                    $current_date = strtotime('+' . $interval . ' days', $current_date);
                    break;
                case 'weekly':
                    $current_date = strtotime('+' . $interval . ' weeks', $current_date);
                    break;
                case 'monthly':
                    $current_date = strtotime('+' . $interval . ' months', $current_date);
                    break;
                case 'yearly':
                    $current_date = strtotime('+' . $interval . ' years', $current_date);
                    break;
                default:
                    break 2;
            }

            if ($current_date > $end_timestamp) break;
        }

        return $occurrences;
    }

    private function get_recurring_occurrences_in_range($event, $start_date, $end_date) {
        $start_date_meta = get_post_meta($event->ID, '_kh_event_start_date', true);
        $recurring_type = get_post_meta($event->ID, '_kh_event_recurring_type', true);
        $recurring_end_date = get_post_meta($event->ID, '_kh_event_recurring_end_date', true);
        $interval = intval(get_post_meta($event->ID, '_kh_event_recurring_interval', true)) ?: 1;

        if (!$start_date_meta || !$recurring_type) {
            return array();
        }

        $occurrences = array();
        $current_date = strtotime($start_date_meta);
        $end_timestamp = $recurring_end_date ? strtotime($recurring_end_date) : strtotime('+1 year', $current_date);
        $range_start = strtotime($start_date);
        $range_end = strtotime($end_date);

        while ($current_date <= $end_timestamp && $current_date <= $range_end) {
            if ($current_date >= $range_start) {
                $occurrences[] = date('Y-m-d', $current_date);
            }

            // Calculate next occurrence
            switch ($recurring_type) {
                case 'daily':
                    $current_date = strtotime('+' . $interval . ' days', $current_date);
                    break;
                case 'weekly':
                    $current_date = strtotime('+' . $interval . ' weeks', $current_date);
                    break;
                case 'monthly':
                    $current_date = strtotime('+' . $interval . ' months', $current_date);
                    break;
                case 'yearly':
                    $current_date = strtotime('+' . $interval . ' years', $current_date);
                    break;
                default:
                    break 2;
            }
        }

        return $occurrences;
    }
}