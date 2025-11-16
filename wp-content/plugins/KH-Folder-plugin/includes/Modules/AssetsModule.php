<?php
namespace KHFolders\Modules;

use KHFolders\Services\FolderService;

class AssetsModule implements ModuleInterface
{
    public function register()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function enqueueAdminAssets($hook)
    {
        $shouldForce = apply_filters('kh_folders_force_assets', false, $hook);

        if (strpos($hook, 'kh-folders') === false && ! $shouldForce) {
            return;
        }

        wp_register_style(
            'kh-folders-admin',
            KH_FOLDERS_URL . 'assets/css/admin-folders.css',
            [],
            KH_FOLDERS_VERSION
        );
        wp_register_script(
            'kh-folders-admin',
            KH_FOLDERS_URL . 'assets/js/admin-folders.js',
            ['jquery', 'jquery-ui-sortable'],
            KH_FOLDERS_VERSION,
            true
        );

        wp_enqueue_style('kh-folders-admin');
        wp_enqueue_script('kh-folders-admin');

        wp_localize_script('kh-folders-admin', 'khFoldersAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('kh_folders_actions'),
            'taxonomy'=> TaxonomyModule::TAXONOMY,
            'folders' => FolderService::getFolders(),
            'i18n'    => [
                'enterName' => __('Enter a folder name', 'kh-folders'),
                'created'   => __('Folder "%s" created', 'kh-folders'),
                ],
            'noticeSuccess' => apply_filters('kh_folders_notice_success_callback', null),
            'noticeError'   => apply_filters('kh_folders_notice_error_callback', null),
            'strings' => [
                'deleted'     => __('Folder removed.', 'kh-folders'),
                'updated'     => __('Folder updated.', 'kh-folders'),
                'delete'      => __('Delete', 'kh-folders'),
                'empty'       => __('No folders yet.', 'kh-folders'),
                'deleteLabel' => __('Delete', 'kh-folders'),
                'bulkDeleted' => __('Selected folders removed.', 'kh-folders'),
                'bulkConfirm' => __('Delete selected folders? This cannot be undone.', 'kh-folders'),
                'reordered'   => __('Folder order saved.', 'kh-folders'),
                'drag'        => __('Drag to reorder', 'kh-folders'),
            ],
        ]);
    }
}
