<?php

namespace KHM\Services;

use KHM\Services\PluginRegistry;
use KHM\Services\MembershipRepository;
use KHM\Services\OrderRepository;
use KHM\Services\LevelRepository;
use KHM\Services\CreditService;
use KHM\Services\PDFService;

/**
 * Marketing Suite Services
 *
 * Provides standardized services that other marketing suite plugins can use
 */
class MarketingSuiteServices {

    private MembershipRepository $memberships;
    private OrderRepository $orders;
    private LevelRepository $levels;
    private CreditService $credits;
    private PDFService $pdf;

    public function __construct(
        MembershipRepository $memberships,
        OrderRepository $orders,
        LevelRepository $levels
    ) {
        $this->memberships = $memberships;
        $this->orders = $orders;
        $this->levels = $levels;
        $this->credits = new CreditService($memberships, $levels);
        $this->pdf = new PDFService();
    }

    /**
     * Register all services with the plugin registry
     */
    public function register_services(): void {
        // User & Membership Services
        PluginRegistry::register_service('get_user_membership', [$this, 'get_user_membership']);
        PluginRegistry::register_service('check_user_access', [$this, 'check_user_access']);
        PluginRegistry::register_service('get_member_discount', [$this, 'get_member_discount']);
        
        // Payment & Order Services
        PluginRegistry::register_service('create_order', [$this, 'create_order']);
        PluginRegistry::register_service('process_payment', [$this, 'process_payment']);
        PluginRegistry::register_service('get_user_orders', [$this, 'get_user_orders']);
        
        // Credit System Services (Enhanced)
        PluginRegistry::register_service('get_user_credits', [$this, 'get_user_credits']);
        PluginRegistry::register_service('use_credit', [$this, 'use_credit']);
        PluginRegistry::register_service('add_credits', [$this, 'add_credits']);
        PluginRegistry::register_service('allocate_monthly_credits', [$this, 'allocate_monthly_credits']);
        PluginRegistry::register_service('get_credit_history', [$this, 'get_credit_history']);
        
        // PDF & Download Services
        PluginRegistry::register_service('generate_article_pdf', [$this, 'generate_article_pdf']);
        PluginRegistry::register_service('create_download_url', [$this, 'create_download_url']);
        PluginRegistry::register_service('download_with_credits', [$this, 'download_with_credits']);
        
        // Level & Pricing Services
        PluginRegistry::register_service('get_all_levels', [$this, 'get_all_levels']);
        PluginRegistry::register_service('get_level_pricing', [$this, 'get_level_pricing']);
    }

    /**
     * Get user's active membership
     *
     * @param int $user_id
     * @return object|null
     */
    public function get_user_membership(int $user_id): ?object {
        $memberships = $this->memberships->findActive($user_id);
        return !empty($memberships) ? $memberships[0] : null;
    }

    /**
     * Check if user has access to specific content/feature
     *
     * @param int $user_id
     * @param string $access_type Type of access (e.g., 'article_download', 'premium_content')
     * @param array $params Additional parameters
     * @return bool
     */
    public function check_user_access(int $user_id, string $access_type, array $params = []): bool {
        $membership = $this->get_user_membership($user_id);
        
        if (!$membership) {
            return false;
        }

        // Apply filters for extensibility
        return apply_filters(
            'khm_check_user_access',
            $this->default_access_check($membership, $access_type, $params),
            $user_id,
            $access_type,
            $params,
            $membership
        );
    }

    /**
     * Get member discount for a given price
     *
     * @param int $user_id
     * @param float $original_price
     * @param string $item_type
     * @return array ['discounted_price' => float, 'discount_percent' => int, 'discount_amount' => float]
     */
    public function get_member_discount(int $user_id, float $original_price, string $item_type = 'general'): array {
        $membership = $this->get_user_membership($user_id);
        
        if (!$membership) {
            return [
                'discounted_price' => $original_price,
                'discount_percent' => 0,
                'discount_amount' => 0
            ];
        }

        // Get level-specific discount
        $level = $this->levels->get($membership->membership_id);
        $discount_percent = $level->member_discount ?? 0;

        // Apply filters for custom discount logic
        $discount_percent = apply_filters(
            'khm_member_discount_percent',
            $discount_percent,
            $user_id,
            $original_price,
            $item_type,
            $membership
        );

        $discount_amount = ($original_price * $discount_percent) / 100;
        $discounted_price = max(0, $original_price - $discount_amount);

        return [
            'discounted_price' => $discounted_price,
            'discount_percent' => $discount_percent,
            'discount_amount' => $discount_amount
        ];
    }

    /**
     * Create an order for external plugins
     *
     * @param array $order_data
     * @return object|false
     */
    public function create_order(array $order_data) {
        // Validate required fields
        $required = ['user_id', 'total', 'item_type'];
        foreach ($required as $field) {
            if (!isset($order_data[$field])) {
                return false;
            }
        }

        // Create order object
        $order = (object) array_merge([
            'user_id' => 0,
            'membership_id' => 0,
            'code' => $this->orders->generateOrderCode(),
            'subtotal' => $order_data['total'],
            'total' => $order_data['total'],
            'currency' => 'GBP',
            'status' => 'pending',
            'gateway' => 'khm_external',
            'created_at' => current_time('mysql')
        ], $order_data);

        return $this->orders->create($order);
    }

    /**
     * Get user's credit balance (Enhanced)
     *
     * @param int $user_id
     * @return int
     */
    public function get_user_credits(int $user_id): int {
        return $this->credits->getUserCredits($user_id);
    }

    /**
     * Use credits for a user (Enhanced)
     *
     * @param int $user_id
     * @param int $amount
     * @param string $reason
     * @param int|null $object_id
     * @return bool
     */
    public function use_credit(int $user_id, int $amount = 1, string $reason = 'download', ?int $object_id = null): bool {
        return $this->credits->useCredits($user_id, $amount, $reason, $object_id);
    }

    /**
     * Add credits to a user (Enhanced)
     *
     * @param int $user_id
     * @param int $amount
     * @param string $reason
     * @return bool
     */
    public function add_credits(int $user_id, int $amount, string $reason = 'manual'): bool {
        return $this->credits->addBonusCredits($user_id, $amount, $reason);
    }

    /**
     * Allocate monthly credits for a user
     *
     * @param int $user_id
     * @return bool
     */
    public function allocate_monthly_credits(int $user_id): bool {
        return $this->credits->allocateMonthlyCredits($user_id);
    }

    /**
     * Get credit usage history for a user
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function get_credit_history(int $user_id, int $limit = 20): array {
        return $this->credits->getCreditHistory($user_id, $limit);
    }

    /**
     * Generate PDF for article download
     *
     * @param int $post_id
     * @param int $user_id
     * @return array
     */
    public function generate_article_pdf(int $post_id, int $user_id): array {
        return $this->pdf->generateArticlePDF($post_id, $user_id);
    }

    /**
     * Create secure download URL
     *
     * @param int $post_id
     * @param int $user_id
     * @param int $expires_hours
     * @return string
     */
    public function create_download_url(int $post_id, int $user_id, int $expires_hours = 2): string {
        return $this->pdf->createDownloadURL($post_id, $user_id, $expires_hours);
    }

    /**
     * Complete download flow with credit usage
     *
     * @param int $post_id
     * @param int $user_id
     * @return array
     */
    public function download_with_credits(int $post_id, int $user_id): array {
        // Check if user has credits
        if ($this->get_user_credits($user_id) < 1) {
            return [
                'success' => false,
                'error' => 'Insufficient credits',
                'credits_remaining' => 0
            ];
        }

        // Use credit
        if (!$this->use_credit($user_id, 1, 'article_download', $post_id)) {
            return [
                'success' => false,
                'error' => 'Failed to process credit usage'
            ];
        }

        // Create download URL
        $download_url = $this->create_download_url($post_id, $user_id);
        
        return [
            'success' => true,
            'download_url' => $download_url,
            'credits_remaining' => $this->get_user_credits($user_id)
        ];
    }

    /**
     * Get all membership levels
     *
     * @return array
     */
    public function get_all_levels(): array {
        return $this->levels->all();
    }

    /**
     * Get user's order history
     *
     * @param int $user_id
     * @param array $args
     * @return array
     */
    public function get_user_orders(int $user_id, array $args = []): array {
        return $this->orders->findByUser($user_id, $args);
    }

    /**
     * Default access check logic
     *
     * @param object $membership
     * @param string $access_type
     * @param array $params
     * @return bool
     */
    private function default_access_check(object $membership, string $access_type, array $params): bool {
        // Basic active membership check
        if ($membership->status !== 'active') {
            return false;
        }

        // Check expiration
        if ($membership->enddate && strtotime($membership->enddate) < time()) {
            return false;
        }

        // Type-specific checks
        switch ($access_type) {
            case 'article_download':
                return $this->get_user_credits($membership->user_id) > 0;
            
            case 'premium_content':
                return true; // Active membership grants access
            
            case 'member_pricing':
                return true; // Active membership gets discounts
            
            default:
                return true;
        }
    }
}