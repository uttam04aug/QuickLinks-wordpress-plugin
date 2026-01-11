<?php
/*
Plugin Name: QuickLinks
Plugin URI: https://github.com/uttam04aug/QuickLinks-wordpress-plugin
Description: Quick links plugin for WordPress
Version: 1.0.0
Author: Uttam Singh
Author URI: https://github.com/uttam04aug
License: GPL v2 or later
Text Domain: quicklinks
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Table Creation
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table = $wpdb->prefix . 'quicklinks';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id INT NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        url VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
});

// 2. Admin Menu
add_action('admin_menu', function () {
    add_menu_page('QuickLinks', 'QuickLinks', 'manage_options', 'quicklinks', 'quicklinks_admin_page', 'dashicons-admin-links');
});

// 3. Admin Page
function quicklinks_admin_page() {
    global $wpdb; 
    $table = $wpdb->prefix . 'quicklinks';

    // Delete Logic
    if (isset($_GET['delete'])) { 
        $wpdb->delete($table, ['id' => intval($_GET['delete'])]); 
        echo '<div class="updated"><p>Link Deleted!</p></div>';
    }

    // Save/Update Logic
    if (isset($_POST['ql_save'])) {
        $data = [
            'title' => sanitize_text_field($_POST['title']), 
            'url'   => esc_url_raw($_POST['url']) // URL sanitize
        ];
        if (!empty($_POST['id'])) { 
            $wpdb->update($table, $data, ['id' => intval($_POST['id'])]); 
        } else { 
            $wpdb->insert($table, $data); 
        }
        echo '<div class="updated"><p>Saved Successfully!</p></div>';
    }

    $edit = isset($_GET['edit']) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $_GET['edit'])) : null;
    $links = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    ?>
    <div class="wrap">
        <h2>QuickLinks Management</h2>
        <form method="post" style="padding:20px; background:#fff; border:1px solid #ccc; margin-bottom:20px;">
            <input type="hidden" name="id" value="<?= $edit->id ?? '' ?>">
            <label>Link Text:</label><br>
            <input type="text" name="title" value="<?= esc_attr($edit->title ?? '') ?>" required style="width:300px;"><br><br>
            <label>Link URL:</label><br>
            <input type="url" name="url" value="<?= esc_url($edit->url ?? '') ?>" required style="width:300px;"><br><br>
            <button class="button button-primary" name="ql_save">Save Link</button>
        </form>

        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Text (Title)</th>
                    <th>URL (Link)</th> <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($links): foreach ($links as $l): ?>
                <tr>
                    <td><?= $l->id ?></td>
                    <td><strong><?= esc_html($l->title) ?></strong></td>
                    <td><a href="<?= esc_url($l->url) ?>" target="_blank"><?= esc_url($l->url) ?></a></td> <td>
                        <a href="?page=quicklinks&edit=<?= $l->id ?>" class="button button-small">Edit</a>
                        <a href="?page=quicklinks&delete=<?= $l->id ?>" class="button button-small" style="color:red;" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4">No links found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
// 4. THE ULTIMATE FIX: Inline CSS in Shortcode
add_shortcode('quicklinks', function () {
    global $wpdb;
    $links = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "quicklinks");
    if (!$links) return '';

    $items = '';
    foreach ($links as $link) {
        // inline-block & no-wrap force 
        $items .= '<a target="_blank" href="'.esc_url($link->url).'" style="display:inline-block !important; color:#000 !important; text-decoration:none !important; padding:0 15px !important; font-weight:bold !important; font-size:14px !important; white-space:nowrap !important;"><span style="color:#000 !important; margin-right:5px !important;">✔</span>'.esc_html($link->title).'</a>';
    }

    $unique_id = 'ql_' . rand(1000, 9999);

    ob_start();
    ?>
    <style>
        #<?= $unique_id ?>_wrap { width: 100% !important; overflow: hidden !important; background: #fbfbfb !important; margin-top:15px; border-top: 1px solid #ccc !important; border-bottom: 2px solid #ffb129 !important; padding: 5px 0 10px !important; }
        #<?= $unique_id ?>_track { display: block !important; white-space: nowrap !important; width: fit-content !important; animation: <?= $unique_id ?>_anim 25s linear infinite !important; }
        #<?= $unique_id ?>_track:hover { animation-play-state: paused !important; }
        @keyframes <?= $unique_id ?>_anim {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
    </style>
    <div id="<?= $unique_id ?>_wrap" >
        <div id="<?= $unique_id ?>_track">
            <?= $items . $items ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
