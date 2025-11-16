<?php
namespace KHFolders\Services;

use KHFolders\Modules\TaxonomyModule;
use WP_Error;

class FolderService
{
    const META_COLOR = 'kh_folder_color';
    const META_ORDER = 'kh_folder_order';

    /**
     * Get list of folders with meta values.
     *
     * @return array
     */
    public static function getFolders()
    {
        $terms = get_terms([
            'taxonomy'   => TaxonomyModule::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'meta_value_num',
            'meta_key'   => self::META_ORDER,
            'order'      => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        $folders = [];
        foreach ($terms as $term) {
            $folders[] = self::formatFolderData($term);
        }

        return $folders;
    }

    /**
     * Format a WP_Term as array with meta.
     */
    public static function formatFolderData($term)
    {
        $termId = (int) $term->term_id;

        return [
            'term_id' => $termId,
            'name'    => $term->name,
            'slug'    => $term->slug,
            'parent'  => (int) $term->parent,
            'color'   => get_term_meta($termId, self::META_COLOR, true) ?: '#2271b1',
            'order'   => (int) (get_term_meta($termId, self::META_ORDER, true) ?: $termId),
        ];
    }

    /**
     * Ensure default meta exists for a term.
     */
    public static function bootstrapMeta($termId)
    {
        if (! get_term_meta($termId, self::META_COLOR, true)) {
            update_term_meta($termId, self::META_COLOR, '#2271b1');
        }

        if (! get_term_meta($termId, self::META_ORDER, true)) {
            $order = (int) get_option('kh_folders_order_counter', 0) + 1;
            update_option('kh_folders_order_counter', $order);
            update_term_meta($termId, self::META_ORDER, $order);
        }
    }

    public static function createFolder($name, $parent = 0)
    {
        $result = wp_insert_term($name, TaxonomyModule::TAXONOMY, ['parent' => $parent]);
        if (is_wp_error($result)) {
            return $result;
        }

        $termId = (int) $result['term_id'];
        self::bootstrapMeta($termId);

        return get_term($termId, TaxonomyModule::TAXONOMY);
    }

    public static function deleteFolder($termId)
    {
        return wp_delete_term($termId, TaxonomyModule::TAXONOMY);
    }

    public static function assignToObject($objectId, $termId)
    {
        return wp_set_object_terms($objectId, [$termId], TaxonomyModule::TAXONOMY, false);
    }

    public static function updateMeta($termId, array $meta)
    {
        if (isset($meta['color'])) {
            update_term_meta($termId, self::META_COLOR, sanitize_hex_color($meta['color']) ?: '#2271b1');
        }

        if (isset($meta['order'])) {
            update_term_meta($termId, self::META_ORDER, absint($meta['order']));
        }

        return get_term($termId, TaxonomyModule::TAXONOMY);
    }

    /**
     * Delete multiple folders.
     */
    public static function deleteFolders(array $termIds)
    {
        $deleted = [];
        foreach ($termIds as $termId) {
            $termId = absint($termId);
            if (! $termId) {
                continue;
            }

            $result = wp_delete_term($termId, TaxonomyModule::TAXONOMY);
            if (! is_wp_error($result)) {
                $deleted[] = $termId;
            }
        }

        return $deleted;
    }

    /**
     * Reorder folders based on provided term IDs.
     */
    public static function reorderFolders(array $termIds)
    {
        $position = 1;
        foreach ($termIds as $termId) {
            $termId = absint($termId);
            if (! $termId) {
                continue;
            }

            update_term_meta($termId, self::META_ORDER, $position);
            $position++;
        }

        return self::getFolders();
    }
}
