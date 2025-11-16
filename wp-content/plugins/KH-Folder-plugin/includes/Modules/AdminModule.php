<?php
namespace KHFolders\Modules;

use KHFolders\Services\FolderService;

class AdminModule implements ModuleInterface
{
    public function register()
    {
        if (! is_admin()) {
            return;
        }

        add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu()
    {
        add_menu_page(
            __('KH Folders', 'kh-folders'),
            __('KH Folders', 'kh-folders'),
            'manage_options',
            'kh-folders',
            [$this, 'renderRootPage'],
            'dashicons-category'
        );
    }

    public function renderRootPage()
    {
        $folders = FolderService::getFolders();

        echo '<div class="wrap"><h1>' . esc_html__('KH Folders', 'kh-folders') . '</h1>';
        echo '<p>' . esc_html__('Manage your content folders below.', 'kh-folders') . '</p>';
        echo '<div id="kh-folders-notices" class="kh-folders-admin-notice" style="display:none;"></div>';
        echo '<div class="kh-folders-actions">';
        echo '<button class="button button-primary" data-kh-folders-create>' . esc_html__('Create Folder', 'kh-folders') . '</button>';
        echo '<button class="button" data-kh-folders-bulk-delete disabled>' . esc_html__('Delete Selected', 'kh-folders') . '</button>';
        echo '</div>';
        echo '<table class="kh-folders-table widefat striped"><thead><tr>';
        echo '<th class="column-handle" scope="col"></th>';
        echo '<th>' . esc_html__('Name', 'kh-folders') . '</th>';
        echo '<th>' . esc_html__('Color', 'kh-folders') . '</th>';
        echo '<th>' . esc_html__('Order', 'kh-folders') . '</th>';
        echo '<th>' . esc_html__('Actions', 'kh-folders') . '</th>';
        echo '<th class="column-select"><input type="checkbox" id="kh-folders-select-all" /></th>';
        echo '</tr></thead><tbody id="kh-folders-list">';

        if (empty($folders)) {
            echo '<tr class="no-items"><td colspan="4">' . esc_html__('No folders yet.', 'kh-folders') . '</td></tr>';
        } else {
            foreach ($folders as $folder) {
                echo $this->renderRow($folder);
            }
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    private function renderRow($folder)
    {
        $color = esc_attr($folder['color']);
        $order = esc_attr($folder['order']);
        $termId = (int) $folder['term_id'];

        $html  = '<tr data-kh-folder-row data-term-id="' . $termId . '">';
        $html .= '<td class="column-handle"><span class="kh-folder-drag dashicons dashicons-move" title="' . esc_attr__('Drag to reorder', 'kh-folders') . '"></span></td>';
        $html .= '<td>' . esc_html($folder['name']) . '</td>';
        $html .= '<td><input type="color" value="' . $color . '" data-kh-folder-color="' . $termId . '"/></td>';
        $html .= '<td><input type="number" class="small-text" value="' . $order . '" data-kh-folder-order="' . $termId . '"/></td>';
        $html .= '<td><button class="button button-link-delete" data-kh-folder-delete="' . $termId . '">' . esc_html__('Delete', 'kh-folders') . '</button></td>';
        $html .= '<td class="column-select"><input type="checkbox" data-kh-folder-select="' . $termId . '"/></td>';
        $html .= '</tr>';

        return $html;
    }
}
