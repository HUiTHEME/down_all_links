<?php
/**
 * Plugin Name: Down All Links
 * Plugin URI: https://www.huitheme.com/
 * Description: 一个按钮，将网站里所有的文章链接整理到一个txt文件里。
 * Version: 1.0.0
 * Author: 疯狂的大叔
 * Author URI: https://www.huitheme.com/
 */

function down_all_links_settings_menu() {
    add_options_page(
        'Down All Links',
        'Down All Links',
        'manage_options',
        'down-all-links',
        'down_all_links_setting_page'
    );
}
add_action('admin_menu', 'down_all_links_settings_menu');


function down_all_links_setting_page() {
    ?>
    <style>.down_all_links_css label{margin-right:8px;}.down_all_links_label{margin-bottom:20px}</style>
    <div class="wrap">
    <h2>网站链接下载</h2>
        <form class="down_all_links_css" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('down_all_links_download_links', 'down_all_links_nonce'); ?>
            <input type="hidden" name="action" value="down_all_links_download_links">
            <p class="down_all_links_label">
                <label><input type="checkbox" name="download_homepage" value="1" checked>首页</label>
                <label><input type="checkbox" name="download_categories" value="1" checked>分类</label>
                <label><input type="checkbox" name="download_tags" value="1" checked>标签</label>
                <label><input type="checkbox" name="download_pages" value="1" checked>页面</label>
                <label><input type="checkbox" name="download_posts" value="1" checked>文章</label>
            </p>
            <input type="submit" class="button-primary" value="立即下载">
        </form>
    </div>
    <?php
}

function down_all_links_download_action() {
    if (!current_user_can('manage_options') || !isset($_POST['down_all_links_nonce']) || !wp_verify_nonce($_POST['down_all_links_nonce'], 'down_all_links_download_links')) {
        wp_die('Access Denied');
    }

    // 获取用户选择的下载选项
    $download_homepage = isset($_POST['download_homepage']) ? true : false;
    $download_categories = isset($_POST['download_categories']) ? true : false;
    $download_tags = isset($_POST['download_tags']) ? true : false;
    $download_pages = isset($_POST['download_pages']) ? true : false;
    $download_posts = isset($_POST['download_posts']) ? true : false;

    // 构建要下载的链接列表
    $post_urls = array();

    if ($download_posts) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        $query = new WP_Query($args);
        $post_urls = array_map('get_permalink', $query->posts);
    }

    if ($download_pages) {
        $pages_args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        $pages_query = new WP_Query($pages_args);
        $pages_urls = array_map('get_permalink', $pages_query->posts);
        $post_urls = array_merge($post_urls, $pages_urls);
    }

    if ($download_tags) {
        $tags = get_tags();
        foreach ($tags as $tag) {
            $post_urls[] = get_tag_link($tag->term_id);
        }
    }

    if ($download_categories) {
        $categories = get_categories();
        foreach ($categories as $category) {
            $post_urls[] = get_category_link($category->term_id);
        }
    }

    if ($download_homepage) {
        $post_urls[] = home_url('/');
    }

    // 使用array_unique来排除重复链接
    $post_urls = array_unique($post_urls);

    $txt_content = implode("\n", $post_urls);
    $file_name = parse_url(get_site_url(), PHP_URL_HOST) . '_links_' . date('Y-m-d') . '.txt';
    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Type: text/plain');
    header('Content-Length: ' . strlen($txt_content));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    echo $txt_content;
    exit;
}

add_action('admin_post_down_all_links_download_links', 'down_all_links_download_action');
