<?php
namespace KHFolders\Modules;

use KHFolders\Services\FolderService;

class ListTableModule implements ModuleInterface
{
    public function register()
    {
        add_action('restrict_manage_posts', [$this, 'renderFilter']);
        add_action('pre_get_posts', [$this, 'applyFilter']);
        add_filter('ajax_query_attachments_args', [$this, 'filterAttachments']);

        $postTypes = $this->getSupportedPostTypes();
        foreach ($postTypes as $postType) {
            if ($postType === 'attachment') {
                continue;
            }
            add_filter("manage_{$postType}_posts_columns", [$this, 'addFolderColumn']);
            add_action("manage_{$postType}_posts_custom_column", [$this, 'renderFolderColumn'], 10, 2);
        }

        add_filter('manage_media_columns', [$this, 'addFolderColumn']);
        add_action('manage_media_custom_column', [$this, 'renderFolderColumn'], 10, 2);
        add_action('admin_head', [$this, 'printStyles']);
    }

    public function renderFilter($postType)
    {
        $supported = $this->getSupportedPostTypes();
        if (! in_array($postType, (array) $supported, true)) {
            return;
        }

        $visibleFolders = FolderService::getFolders(['user_id' => get_current_user_id()]);
        if (empty($visibleFolders)) {
            return;
        }

        $include = wp_list_pluck($visibleFolders, 'term_id');

        $selected = isset($_GET['kh_folder']) ? absint($_GET['kh_folder']) : 0; // phpcs:ignore WordPress.Security.NonceVerification

        wp_dropdown_categories([
            'taxonomy'        => TaxonomyModule::TAXONOMY,
            'show_option_all' => __('All Folders', 'kh-folders'),
            'name'            => 'kh_folder',
            'orderby'         => 'name',
            'selected'        => $selected,
            'hierarchical'    => true,
            'depth'           => 3,
            'show_count'      => true,
            'hide_empty'      => false,
            'include'         => $include,
        ]);
    }

    public function applyFilter($query)
    {
        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        if (! isset($_GET['kh_folder']) || (int) $_GET['kh_folder'] === 0) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $postType = $query->get('post_type');
        if (! $postType) {
            $postType = 'post';
        }
        $supported = apply_filters('kh_folders_supported_post_types', ['attachment', 'page', 'post']);
        if (! in_array($postType, (array) $supported, true)) {
            return;
        }

        $termId = absint($_GET['kh_folder']); // phpcs:ignore WordPress.Security.NonceVerification
        if (! $termId) {
            return;
        }

        $taxQuery = (array) $query->get('tax_query');
        $taxQuery[] = [
            'taxonomy' => TaxonomyModule::TAXONOMY,
            'field'    => 'term_id',
            'terms'    => $termId,
        ];
        $query->set('tax_query', $taxQuery);
    }

    public function filterAttachments($args)
    {
        if (! isset($_REQUEST['kh_folder']) || ! absint($_REQUEST['kh_folder'])) { // phpcs:ignore WordPress.Security.NonceVerification
            return $args;
        }

        $args['tax_query'] = isset($args['tax_query']) ? (array) $args['tax_query'] : [];
        $args['tax_query'][] = [
            'taxonomy' => TaxonomyModule::TAXONOMY,
            'field'    => 'term_id',
            'terms'    => absint($_REQUEST['kh_folder']), // phpcs:ignore WordPress.Security.NonceVerification
        ];

        return $args;
    }

    public function addFolderColumn($columns)
    {
        $columns['kh_folder'] = __('Folders', 'kh-folders');
        return $columns;
    }

    public function renderFolderColumn($column, $postId)
    {
        if ('kh_folder' !== $column) {
            return;
        }

        $terms = wp_get_object_terms($postId, TaxonomyModule::TAXONOMY);
        if (is_wp_error($terms) || empty($terms)) {
            echo '&mdash;';
            return;
        }

        $chips = [];
        foreach ($terms as $term) {
            $color = get_term_meta($term->term_id, FolderService::META_COLOR, true);
            $color = $color ? esc_attr($color) : '#ccd0d4';
            $chips[] = '<span class="kh-folder-chip" style="border-color:' . $color . ';background:' . $color . '1a;">' . esc_html($term->name) . '</span>';
        }

        echo implode(' ', $chips);
    }

    public function printStyles()
    {
        echo '<style>.column-kh_folder .kh-folder-chip{display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;margin:1px 2px;border:1px solid #ccd0d4;background:#f6f7f7;color:#1d2327;}</style>';
    }

    private function getSupportedPostTypes()
    {
        $types = apply_filters('kh_folders_supported_post_types', ['attachment', 'page', 'post']);
        return array_unique(array_filter((array) $types));
    }
}
