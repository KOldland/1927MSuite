<?php
namespace KH\XAPI;

use KH\XAPI\Services\DatabaseManager;
use KH\XAPI\Services\AddonManager;
use KH\XAPI\Services\StateStore;
use KH\XAPI\Services\LearningDataService;
use KH\XAPI\Admin\SettingsPage;
use KH\XAPI\API\ReportsController;
use KH\XAPI\Shortcodes\ReportsShortcode;

class Plugin {
    private DatabaseManager $db_manager;
    private AddonManager $addon_manager;
    private StateStore $state_store;
    private LearningDataService $learning_data;
    private ?SettingsPage $settings = null;
    private ReportsController $reports_controller;
    private ReportsShortcode $reports_shortcode;

    public function __construct() {
        $this->db_manager    = new DatabaseManager();
        $this->addon_manager = new AddonManager();
        $this->state_store   = new StateStore();
        $this->learning_data = new LearningDataService();
        $this->reports_controller = new ReportsController( $this->learning_data );
        $this->reports_shortcode  = new ReportsShortcode();

        if ( is_admin() ) {
            $this->settings = new SettingsPage();
        }
    }

    public function init(): void {
        $this->db_manager->register_hooks();
        $this->addon_manager->boot();
        $this->reports_controller->hooks();
        $this->reports_shortcode->hooks();

        if ( $this->settings ) {
            $this->settings->hooks();
        }
    }

    public function db(): DatabaseManager {
        return $this->db_manager;
    }

    public function addons(): AddonManager {
        return $this->addon_manager;
    }

    public function state(): StateStore {
        return $this->state_store;
    }

    public function data(): LearningDataService {
        return $this->learning_data;
    }
}
