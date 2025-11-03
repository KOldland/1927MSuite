<?php
/*
Plugin Name: Social Sharing Modal
Description: Adds a floating button to share articles via email using a modal.
Version: 1.0
Author: Kirsty Hennah
*/

if (!defined('ABSPATH')) exit;

// Load functional logic
require_once __DIR__ . '/includes/sm.functions.php';

// Load modal markup as a shortcode
require_once __DIR__ . '/includes/email-modal.php';
