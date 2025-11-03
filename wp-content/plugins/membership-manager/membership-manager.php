
<?php
    /*
    Plugin Name: Membership Manager
    Description: Manages site access based on membership level.
    Version: 1.0
    Author: Kirsty Hennah
    
    */    if (!defined('ABSPATH')) exit;
    
    // Add the Member Management menu item
    add_action('admin_menu', function() {
        add_menu_page(
            'Member Management',
            'Members',
            'manage_options',
            'member-management',
            'kss_render_member_management_page',
            'dashicons-groups',
            30
        );
    });
    
    // Render the main member management page
    function kss_render_member_management_page() {
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
            if (isset($_POST['action']) && check_admin_referer('kss_member_action')) {
                switch ($_POST['action']) {
                    case 'add_member':
                        kss_handle_add_member();
                        break;
                    case 'edit_member':
                        kss_handle_edit_member();
                        break;
                    case 'delete_member':
                        kss_handle_delete_member();
                        break;
                }
            }
        }
        
        // Get all users with member role
        $members = get_users([
            'role__in' => ['subscriber', 'customer'],
            'orderby' => 'registered',
            'order' => 'DESC'
        ]);
        
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Member Management</h1>
    <a href="?page=member-management&action=add" class="page-title-action">Add New Member</a>
    
    <?php if (isset($_GET['message'])): ?>
    <div class="notice notice-success"><p><?php echo esc_html($_GET['message']); ?></p></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <?php kss_render_add_member_form(); ?>
    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['user_id'])): ?>
    <?php kss_render_edit_member_form((int)$_GET['user_id']); ?>
    <?php else: ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Membership Type</th>
                <th>Articles Read</th>
                <th>Join Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $member): ?>
            <tr>
                <td><?php echo esc_html($member->display_name); ?></td>
                <td><?php echo esc_html($member->user_email); ?></td>
                <td><?php echo esc_html(get_user_meta($member->ID, 'membership_type', true) ?: 'Free'); ?></td>
                <td><?php echo (int)get_user_meta($member->ID, 'articles_read_this_month', true); ?></td>
                <td><?php echo date('Y-m-d', strtotime($member->user_registered)); ?></td>
                <td>
                    <a href="?page=member-management&action=edit&user_id=<?php echo $member->ID; ?>" class="button button-small">Edit</a>
                    <form method="post" style="display:inline;">
                        <?php wp_nonce_field('kss_member_action'); ?>
                        <input type="hidden" name="action" value="delete_member">
                        <input type="hidden" name="user_id" value="<?php echo $member->ID; ?>">
                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Are you sure you want to delete this member?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php
    }
    
    // Render the add member form
    function kss_render_add_member_form() {
?>
<div class="card">
    <h2>Add New Member</h2>
    <form method="post">
        <?php wp_nonce_field('kss_member_action'); ?>
        <input type="hidden" name="action" value="add_member">
        
        <table class="form-table">
            <tr>
                <th><label for="email">Email</label></th>
                <td><input type="email" name="email" id="email" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="first_name">First Name</label></th>
                <td><input type="text" name="first_name" id="first_name" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="last_name">Last Name</label></th>
                <td><input type="text" name="last_name" id="last_name" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="membership_type">Membership Type</label></th>
                <td>
                    <select name="membership_type" id="membership_type">
                        <option value="free">Free</option>
                        <option value="premium">Premium</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" class="button button-primary" value="Add Member">
            <a href="?page=member-management" class="button">Cancel</a>
        </p>
    </form>
</div>
<?php
    }
    
    // Render the edit member form
    function kss_render_edit_member_form($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            echo '<div class="notice notice-error"><p>Member not found.</p></div>';
            return;
        }
?>
<div class="card">
    <h2>Edit Member</h2>
    <form method="post">
        <?php wp_nonce_field('kss_member_action'); ?>
        <input type="hidden" name="action" value="edit_member">
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        
        <table class="form-table">
            <tr>
                <th><label for="email">Email</label></th>
                <td><input type="email" name="email" id="email" class="regular-text" value="<?php echo esc_attr($user->user_email); ?>" required></td>
            </tr>
            <tr>
                <th><label for="first_name">First Name</label></th>
                <td><input type="text" name="first_name" id="first_name" class="regular-text" value="<?php echo esc_attr($user->first_name); ?>" required></td>
            </tr>
            <tr>
                <th><label for="last_name">Last Name</label></th>
                <td><input type="text" name="last_name" id="last_name" class="regular-text" value="<?php echo esc_attr($user->last_name); ?>" required></td>
            </tr>
            <tr>
                <th><label for="membership_type">Membership Type</label></th>
                <td>
                    <select name="membership_type" id="membership_type">
                        <option value="free" <?php selected(get_user_meta($user_id, 'membership_type', true), 'free'); ?>>Free</option>
                        <option value="premium" <?php selected(get_user_meta($user_id, 'membership_type', true), 'premium'); ?>>Premium</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="articles_read">Articles Read This Month</label></th>
                <td><input type="number" name="articles_read" id="articles_read" value="<?php echo (int)get_user_meta($user_id, 'articles_read_this_month', true); ?>" min="0"></td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" class="button button-primary" value="Update Member">
            <a href="?page=member-management" class="button">Cancel</a>
        </p>
    </form>
</div>
<?php
    }
    
    // Handle adding a new member
    function kss_handle_add_member() {
        if (!isset($_POST['email'], $_POST['first_name'], $_POST['last_name'], $_POST['membership_type'])) {
            wp_die('Required fields are missing.');
        }
        
        $user_data = [
            'user_login' => $_POST['email'],
            'user_email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'last_name'  => $_POST['last_name'],
            'role'       => 'subscriber',
            'user_pass'  => wp_generate_password()
        ];
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            wp_die($user_id->get_error_message());
        }
        
        update_user_meta($user_id, 'membership_type', $_POST['membership_type']);
        
        // Send new user notification
        wp_new_user_notification($user_id, null, 'both');
        
        wp_redirect(admin_url('admin.php?page=member-management&message=' . urlencode('Member added successfully.')));
        exit;
    }
    
    // Handle editing a member
    function kss_handle_edit_member() {
        if (!isset($_POST['user_id'], $_POST['email'], $_POST['first_name'], $_POST['last_name'], $_POST['membership_type'])) {
            wp_die('Required fields are missing.');
        }
        
        $user_id = (int)$_POST['user_id'];
        
        $user_data = [
            'ID'         => $user_id,
            'user_email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'last_name'  => $_POST['last_name']
        ];
        
        $user_id = wp_update_user($user_data);
        
        if (is_wp_error($user_id)) {
            wp_die($user_id->get_error_message());
        }
        
        update_user_meta($user_id, 'membership_type', $_POST['membership_type']);
        update_user_meta($user_id, 'articles_read_this_month', (int)$_POST['articles_read']);
        
        wp_redirect(admin_url('admin.php?page=member-management&message=' . urlencode('Member updated successfully.')));
        exit;
    }
    
    // Handle deleting a member
    function kss_handle_delete_member() {
        if (!isset($_POST['user_id'])) {
            wp_die('User ID is required.');
        }
        
        $user_id = (int)$_POST['user_id'];
        
        if (wp_delete_user($user_id)) {
            wp_redirect(admin_url('admin.php?page=member-management&message=' . urlencode('Member deleted successfully.')));
            exit;
        }
        
        wp_die('Failed to delete member.');
    }