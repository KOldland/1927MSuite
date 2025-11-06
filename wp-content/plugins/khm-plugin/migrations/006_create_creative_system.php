<?php
/**
 * KHM Creative System Migration
 * 
 * Creates database tables for the professional marketing materials system
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHM_Creative_Migration {
    
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    
    /**
     * Run the migration
     */
    public function migrate() {
        $this->create_creatives_table();
        $this->create_creative_usage_table();
        $this->insert_sample_creatives();
        
        return true;
    }
    
    /**
     * Create the creatives table
     */
    private function create_creatives_table() {
        $table_name = $this->db->prefix . 'khm_creatives';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type enum('banner','text','video','social','other') NOT NULL DEFAULT 'banner',
            content longtext,
            image_url varchar(500),
            alt_text varchar(255),
            landing_url varchar(500),
            dimensions varchar(50),
            description text,
            status enum('active','inactive','archived') NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime,
            PRIMARY KEY (id),
            KEY type_status (type, status),
            KEY created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->query($sql);
        
        if ($this->db->last_error) {
            error_log("KHM Creative Migration Error - Creatives Table: " . $this->db->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Create the creative usage tracking table
     */
    private function create_creative_usage_table() {
        $table_name = $this->db->prefix . 'khm_creative_usage';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            creative_id bigint(20) unsigned NOT NULL,
            member_id bigint(20) unsigned NOT NULL,
            action enum('view','click','conversion') NOT NULL DEFAULT 'view',
            platform varchar(50),
            ip_address varchar(45),
            user_agent text,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY creative_member (creative_id, member_id),
            KEY action_created (action, created_at),
            KEY platform (platform)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->query($sql);
        
        if ($this->db->last_error) {
            error_log("KHM Creative Migration Error - Usage Table: " . $this->db->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Insert sample creative materials for testing
     */
    private function insert_sample_creatives() {
        $table_name = $this->db->prefix . 'khm_creatives';
        
        // Check if we already have sample data
        $existing = $this->db->get_var("SELECT COUNT(*) FROM {$table_name}");
        if ($existing > 0) {
            return true; // Skip if data already exists
        }
        
        $sample_creatives = array(
            array(
                'name' => 'Premium Membership Banner',
                'type' => 'banner',
                'content' => '<h3>Join Our Premium Membership</h3><p>Unlock exclusive content and benefits today!</p>',
                'image_url' => 'https://via.placeholder.com/728x90/007cba/ffffff?text=Premium+Membership',
                'alt_text' => 'Premium Membership Banner',
                'landing_url' => '/membership',
                'dimensions' => '728x90',
                'description' => 'Standard leaderboard banner for premium membership promotion',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ),
            array(
                'name' => 'Feature Article Text Link',
                'type' => 'text',
                'content' => '<strong>Read our latest feature article:</strong> "The Future of Digital Marketing" - Discover cutting-edge strategies that are reshaping the industry.',
                'landing_url' => '/articles/future-digital-marketing',
                'description' => 'Text-based promotional content for feature articles',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ),
            array(
                'name' => 'Social Media Share Pack',
                'type' => 'social',
                'content' => 'Check out this amazing resource that transformed my business! 🚀 #BusinessGrowth #Success #Affiliate',
                'landing_url' => '/resources',
                'description' => 'Ready-to-share social media content with optimized hashtags',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ),
            array(
                'name' => 'Video Tutorial Promo',
                'type' => 'video',
                'content' => '<h4>Master Advanced Techniques</h4><p>Watch our exclusive video tutorial series and take your skills to the next level.</p>',
                'image_url' => 'https://via.placeholder.com/560x315/ff6b6b/ffffff?text=Video+Tutorial',
                'alt_text' => 'Video Tutorial Thumbnail',
                'landing_url' => '/tutorials',
                'dimensions' => '560x315',
                'description' => 'Video tutorial promotional material with engaging thumbnail',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ),
            array(
                'name' => 'Newsletter Signup Banner',
                'type' => 'banner',
                'content' => '<h3>Stay Updated</h3><p>Subscribe to our newsletter for the latest insights and exclusive offers!</p>',
                'image_url' => 'https://via.placeholder.com/320x250/28a745/ffffff?text=Newsletter+Signup',
                'alt_text' => 'Newsletter Signup Banner',
                'landing_url' => '/newsletter',
                'dimensions' => '320x250',
                'description' => 'Medium rectangle banner for newsletter subscription promotion',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            )
        );
        
        foreach ($sample_creatives as $creative) {
            $this->db->insert($table_name, $creative);
            
            if ($this->db->last_error) {
                error_log("KHM Creative Migration Error - Sample Data: " . $this->db->last_error);
            }
        }
        
        return true;
    }
    
    /**
     * Rollback the migration (for testing purposes)
     */
    public function rollback() {
        $creatives_table = $this->db->prefix . 'khm_creatives';
        $usage_table = $this->db->prefix . 'khm_creative_usage';
        
        $this->db->query("DROP TABLE IF EXISTS {$usage_table}");
        $this->db->query("DROP TABLE IF EXISTS {$creatives_table}");
        
        return true;
    }
    
    /**
     * Check if migration has been run
     */
    public function is_migrated() {
        $table_name = $this->db->prefix . 'khm_creatives';
        
        $result = $this->db->get_var("SHOW TABLES LIKE '{$table_name}'");
        return $result === $table_name;
    }
}

// Auto-run migration if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME']) || 
    (isset($_GET['run_migration']) && $_GET['run_migration'] === 'khm_creative')) {
    
    $migration = new KHM_Creative_Migration();
    
    if (!$migration->is_migrated()) {
        $result = $migration->migrate();
        
        if ($result) {
            echo "✅ KHM Creative System migration completed successfully!\n";
            echo "📊 Sample creative materials have been added for testing.\n";
        } else {
            echo "❌ KHM Creative System migration failed. Check error logs.\n";
        }
    } else {
        echo "ℹ️  KHM Creative System is already migrated.\n";
    }
}