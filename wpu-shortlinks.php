<?php
/*
Plugin Name: WPU Shortlinks
Plugin URI: http://wpu.ir
Description: WPU.IR is a powerful and Free URL Shortener Service
Author: Parsa Kafi
Version: 2.1
Author URI: http://parsa.ws
Text Domain: wpu_shortlinks
Domain Path: /languages/
License: GPL v3
*/

if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

/**
 * Class WPUShortLinks
 */
class WPUShortLinks
{
    /**
     * @var string
     */
    protected $Shortener_API = 'http://wpu.ir/api/v1/shortener';
    /**
     * @var array
     */
    protected $ignore_post_types = array("attachment", "revision", "nav_menu_item", "custom_css", "customize_changeset");
    /**
     * @var
     */
    protected $social_network;
    /**
     * @var string
     */
    protected $text_domain = 'wpu_shortlinks';

    /**
     * WPUShortLinks constructor.
     */
    function __construct()
    {
        global $wp_version;
        $set = get_option("wpu_shortlinks_settings");

        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'plugin_activate'));
        add_action('save_post', array($this, 'get_with_post'), 10, 3);
        add_action('admin_menu', array($this, 'menu_page'));
        add_action('wp_head', array($this, 'wp_head'));
        add_action('wp_enqueue_scripts', array($this, 'load_jscss'), 20000);

        if ($set['admin_bar_box']) {
            add_action('admin_bar_menu', array($this, 'admin_bar'), 50);
            add_action('admin_head', array($this, 'wp_head'));
            add_action('admin_head', array($this, 'load_jscss'));
            add_action('wp_footer', array($this, 'load_box'));
            add_action('admin_footer', array($this, 'load_box'));
            add_action('wp_ajax_wpu_shortlinks_get', array($this, 'shortlinks_get'));
        }

        add_filter('the_content', array($this, 'display_content'), 1000);
        add_action('post_submitbox_misc_actions', array($this, 'publish_widget'));
        add_action('admin_head', array($this, 'admin_head'));
        add_filter('manage_posts_columns', array($this, 'columns_head'));
        add_filter('manage_pages_columns', array($this, 'columns_head'));
        add_action('manage_posts_custom_column', array($this, 'columns_content'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'columns_content'), 10, 2);

        if ( version_compare( $wp_version, '4.7', '>=' ) ) {
            add_filter('bulk_actions-edit-post', array($this, 'bulk_actions'));
            add_filter('bulk_actions-edit-page', array($this, 'bulk_actions'));
            add_filter('handle_bulk_actions-edit-post', array($this, 'action_handler'), 10, 3);
            add_filter('handle_bulk_actions-edit-page', array($this, 'action_handler'), 10, 3);
            add_action('admin_notices', array($this, 'bulk_action_admin_notice'));
        }

        add_shortcode('wpu', array($this, 'wpu_shortcode'));
    }

    /**
     *
     */
    private function set_variable()
    {
        $this->social_network = array(
            'facebook' => array(
                'label' => __('Facebook', $this->text_domain),
                'icon_class' => 'icon-facebook-square',
                'url' => 'https://www.facebook.com/sharer.php?u={url}&t={title}'
            ),
            'twitter' => array(
                'label' => __('Twitter', $this->text_domain),
                'icon_class' => 'icon-twitter-square',
                'url' => 'https://twitter.com/intent/tweet?via={via}&text={title}&url={url}'
            ),
            'linkedin' => array(
                'label' => __('Linkedin', $this->text_domain),
                'icon_class' => 'icon-linkedin-square',
                'url' => 'https://www.linkedin.com/cws/share?token&isFramed=false&url={url}'
            ),
            'google-plus' => array(
                'label' => __('Google', $this->text_domain),
                'icon_class' => 'icon-google-plus-square',
                'url' => 'https://plus.google.com/share?url={url}'
            ),
            'tumblr' => array(
                'label' => __('Tumblr', $this->text_domain),
                'icon_class' => 'icon-tumblr-square',
                'url' => 'https://www.tumblr.com/share?v=3&u={url}&t={title}'
            ),
            'pinterest' => array(
                'label' => __('Pinterest', $this->text_domain),
                'icon_class' => 'icon-pinterest-square',
                'url' => 'https://www.pinterest.com/pin/create/button/?url={url}&media={image}&description={title}'
            ),
            'reddit' => array(
                'label' => __('Reddit', $this->text_domain),
                'icon_class' => 'icon-reddit-square',
                'url' => 'https://www.reddit.com/submit?url={url}&title={title}'
            ),
            'telegram' => array(
                'label' => __('Telegram', $this->text_domain),
                'icon_class' => 'icon-telegram',
                'url' => 'https://telegram.me/share/url?url={url}&text={title}'
            ),
            'skype' => array(
                'label' => __('Skype', $this->text_domain),
                'icon_class' => 'icon-skype',
                'url' => 'https://web.skype.com/share?url={url}&lang=en-US&source=wpu_shortlinks'
            ),
            'whatsapp' => array(
                'label' => __('WhatsApp', $this->text_domain),
                'icon_class' => 'icon-whatsapp',
                'url' => 'whatsapp://send?text={title} {url}'
            ),
            'pocket' => array(
                'label' => __('Pocket', $this->text_domain),
                'icon_class' => 'icon-get-pocket',
                'url' => 'https://getpocket.com/edit?url={url}&title={title}'
            ),
            'email' => array(
                'label' => __('Email', $this->text_domain),
                'icon_class' => 'icon-envelope',
                'url' => 'mailto:?subject={title}&body={title} {url}'
            ),
        );
    }

    /**
     *
     */
    function load_jscss()
    {
        wp_enqueue_style('wpu_social', plugins_url("wpu-shortlinks/css/wpu-social.css", dirname(__FILE__)), array(), '', 'screen');

        if (is_admin() || (!is_admin() && is_admin_bar_showing())) {
            wp_enqueue_style('wpu_style', plugins_url("wpu-shortlinks/css/wpu-style.css", dirname(__FILE__)), array(), '', 'screen');
            wp_enqueue_script('wpu_script', plugins_url("wpu-shortlinks/js/wpu-js.js", dirname(__FILE__)), '', '', true);
            wp_enqueue_script("jquery");
        }
    }

    /**
     * @param $type
     * @return bool
     */
    private function support($type)
    {
        global $post, $pagenow;
        $post_id = $post->ID;
        $post_type = get_post_type($post_id);
        if ($pagenow == 'edit.php' && $_GET['post_type'] == 'page')
            $post_type = 'page';
        if (!in_array($post_type, $this->ignore_post_types)) {
            $set = get_option("wpu_shortlinks_settings");
            if (is_array($set[$type]) && in_array($post_type, array_keys($set[$type])))
                return true;
        }
        return false;
    }

    /**
     * @param $bulk_actions
     * @return mixed
     */
    function bulk_actions($bulk_actions)
    {
        if (!$this->support('ptype'))
            return $bulk_actions;
        $bulk_actions['wpu_get_shortlinks'] = __('Generate Shortlinks', $this->text_domain);
        return $bulk_actions;
    }

    /**
     * @param $redirect_to
     * @param $doaction
     * @param $post_ids
     * @return string
     */
    function action_handler($redirect_to, $doaction, $post_ids)
    {
        if (!$this->support('ptype'))
            return $redirect_to;
        if ($doaction !== 'wpu_get_shortlinks') {
            return $redirect_to;
        }
        $c = 0;
        foreach ($post_ids as $post_id) {
            $wpu_shortlink = $this->get_post_meta($post_id);
            if (!$wpu_shortlink || empty($wpu_shortlink)) {
                $result = $this->get_shortlink($post_id);
                if ($result) {
                    $this->save_response($result, $post_id);
                    $c++;
                }
            }
        }
        $redirect_to = add_query_arg('bulk_wpu_shortlinks', $c, $redirect_to);
        return $redirect_to;
    }

    /**
     *
     */
    function bulk_action_admin_notice()
    {
        if (!$this->support('ptype'))
            return;
        if (isset($_REQUEST['bulk_wpu_shortlinks'])) {
            $short_link_count = intval($_REQUEST['bulk_wpu_shortlinks']);
            if ($short_link_count == 0) {
                if (!empty($_SESSION['wpu_error'])) {
                    echo "<div class='notice notice-warning is-dismissible'><p>" . $_SESSION['wpu_error'] . "</p></div>";
                    unset($_SESSION['wpu_error']);
                }
            } else {
                $message = sprintf(_n(__('Generate Shortlinks.', $this->text_domain), '%s ' . __('Posts Generate Shortlinks.', $this->text_domain), $short_link_count), number_format_i18n($short_link_count));
                echo "<div class='notice notice-success is-dismissible'><p>{$message}</p></div>";
            }
        }
    }

    /**
     *
     */
    function init()
    {
        if (!session_id())
            session_start();

        load_plugin_textdomain($this->text_domain, false, dirname(plugin_basename(__FILE__)) . '/languages/');

        if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
            add_filter('mce_external_plugins', array($this, 'mce_external_plugins'));
            add_filter('mce_buttons', array($this, 'mce_buttons'));
        }

        if (isset($_POST['wpu_submit'])) {
            update_option("wpu_shortlinks_settings", $_POST);
        }
    }

    /**
     *
     */
    function plugin_activate()
    {
        if (!get_option('wpu_shortlinks_settings')) {
            $set = array(
                'ptype' => array(),
                'sh_ptype' => array(),
                'admin_bar_box' => 1,
                'dsc_type' => 'text',
                'social_sharing_status' => 0,
                'sn_ptype' => array(),
                'buttons_style' => 'icon-text',
                'social_sharing_order' => 'after_shortlink',
                'social_sharing_label' => '',
                'twitter_username' => ''
            );
            add_option("wpu_shortlinks_settings", $set);
        }
    }

    /**
     *
     */
    function menu_page()
    {
        add_submenu_page('options-general.php', __('WPU Shortlinks', $this->text_domain), __('WPU Shortlinks', $this->text_domain), 'manage_options', 'wpu_settings', array($this, 'settings'));
    }

    /**
     *
     */
    function settings()
    {
        $set = get_option("wpu_shortlinks_settings");
        $post_types = get_post_types("", "objects");
        $this->set_variable();

        ?>
        <style>
            .wpu_wrap {
                margin-top: 20px
            }

            .wpu_wrap .title img {
                margin: 0px 53px;
            }

            .wpu_wrap td {
                vertical-align: middle;
            }
        </style>
        <div class="wpu_wrap">
            <div class="title">
                <h1> <?php _e('WPU Shortlinks', $this->text_domain) ?></h1>
            </div>
            <?php
            if (isset($_POST['wpu_submit']))
                echo '<div class="updated" id="message"><p>' . __('Settings saved.') . '</p></div>';
            ?>

            <form action="" method="post" id="wpu_settings">
                <table widtd="100%" border="0" cellspacing="5" cellpadding="5">
                    <tr valign="top">
                        <td scope="row">
                            <?php _e('WPU Shortener API Key', $this->text_domain); ?>
                        </td>
                        <td>
                            <input type="text" name="api_key" class="widefat ltr"
                                   value="<?php echo esc_attr($set['api_key']); ?>">
                            <small><a href="http://wpu.ir/dashboard/webservice"
                                      target="_blank"><?php _e('Get free API key', $this->text_domain); ?></a></small>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e("Active shortlink on", $this->text_domain) ?></td>
                        <td>
                            <?php
                            foreach ($post_types as $post_type) {
                                if (!in_array($post_type->name, $this->ignore_post_types)) {
                                    echo '<label><input type="checkbox" name="ptype[' . $post_type->name . ']" value="1" ' . checked(1, $set['ptype'][$post_type->name], false) . ' class="post_type_chk"/>' . $post_type->label . '</label> &nbsp;';
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td scope="row"><?php _e('Access With Admin Bar', $this->text_domain) ?></td>
                        <td><label><input type="checkbox" name="admin_bar_box"
                                          value="1" <?php checked($set['admin_bar_box'], 1); ?> /> <?php _e('Active', $this->text_domain) ?>
                            </label></td>
                    </tr>
                    <tr>
                        <td scope="row">
                            <?php _e('Show Shortlink on', $this->text_domain) ?></td>
                        <td>
                            <?php
                            echo '<label><input type="checkbox" name="sh_ptype[index]" value="1" ' . checked(1, $set['sh_ptype']['index'], false) . ' class="post_type_chk"/>' . __('Front Page, Archive Pages, and Search Results', $this->text_domain) . '</label> &nbsp;';
                            foreach ($post_types as $post_type) {
                                if (!in_array($post_type->name, $this->ignore_post_types)) {
                                    echo '<label><input type="checkbox" name="sh_ptype[' . $post_type->name . ']" value="1" ' . checked(1, $set['sh_ptype'][$post_type->name], false) . ' class="post_type_chk"/>' . $post_type->label . '</label> &nbsp;';
                                }
                            }
                            ?>
                            <br>
                            <p style="display: inline">
                                <?php _e('Type: ', $this->text_domain) ?> <label for="dsc_text">
                                    <input type="radio"
                                           id="dsc_text"
                                           name="dsc_type"
                                           value="text" <?php checked($set['dsc_type'], "text"); ?> /><?php _e('Text', $this->text_domain) ?>
                                </label>
                                &nbsp;<label for="dsc_textbox">
                                    <input type="radio" id="dsc_textbox"
                                           name="dsc_type"
                                           value="textbox" <?php checked($set['dsc_type'], "textbox"); ?>/><?php _e('Textbox', $this->text_domain) ?>
                                </label>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Social Sharing', $this->text_domain) ?></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td widtd="200" scope="row"><?php _e('Status', $this->text_domain) ?></td>
                        <td>
                            <label>
                                <input type="checkbox" name="social_sharing_status"
                                       value="1" <?php checked($set['social_sharing_status'], 1); ?> /> <?php _e('Active', $this->text_domain) ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e("Show buttons on", $this->text_domain) ?></td>
                        <td>
                            <?php
                            echo '<label><input type="checkbox" name="sn_ptype[index]" value="1" ' . checked(1, $set['sn_ptype']['index'], false) . ' class="post_type_chk"/>' . __('Front Page, Archive Pages, and Search Results', $this->text_domain) . '</label> &nbsp;';
                            foreach ($post_types as $post_type) {
                                if (!in_array($post_type->name, $this->ignore_post_types)) {
                                    echo '<label><input type="checkbox" name="sn_ptype[' . $post_type->name . ']" value="1" ' . checked(1, $set['sn_ptype'][$post_type->name], false) . ' class="post_type_chk"/>' . $post_type->label . '</label> &nbsp;';
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e("Buttons", $this->text_domain) ?></td>
                        <td>
                            <?php
                            foreach ($this->social_network as $name => $social) {
                                echo '<label><input type="checkbox" name="social_network[' . $name . ']" value="1" ' . checked(1, $set['social_network'][$name], false) . ' class="post_type_chk"/>' . $social['label'] . '</label> &nbsp;';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td scope="row">
                            <label><?php _e('Button style', $this->text_domain); ?></label>
                        </td>
                        <td>
                            <select name="buttons_style">
                                <option value="icon-text" <?php selected($set['buttons_style'], 'icon-text'); ?> ><?php _e('Icon + text', $this->text_domain); ?></option>
                                <option value="icon" <?php selected($set['buttons_style'], 'icon'); ?> ><?php _e('Icon only', $this->text_domain); ?></option>
                                <option value="text" <?php selected($set['buttons_style'], 'text'); ?> ><?php _e('Text only', $this->text_domain); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td scope="row">
                            <label><?php _e('Order', $this->text_domain); ?></label>
                        </td>
                        <td>
                            <select name="social_sharing_order">
                                <option value="after_shortlink" <?php selected($set['social_sharing_order'], 'after_shortlink'); ?> ><?php _e('After Shortlink', $this->text_domain); ?></option>
                                <option value="before_shortlink" <?php selected($set['social_sharing_order'], 'before_shortlink'); ?> ><?php _e('Before Shortlink', $this->text_domain); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr valign="top">
                        <td scope="row">
                            <?php _e('Sharing label', $this->text_domain); ?>
                        </td>
                        <td>
                            <input type="text" name="social_sharing_label" class="widefat"
                                   placeholder="<?php _e('Share:', $this->text_domain) ?>"
                                   value="<?php echo esc_attr($set['social_sharing_label']); ?>">
                            <small><?php _e('the text to show before the sharing links.', $this->text_domain); ?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <td scope="row">
                            <label><?php _e('Twitter Site Tag', $this->text_domain); ?></label>
                        </td>
                        <td>
                            <input type="text" name="twitter_username" class="ltr"
                                   value="<?php echo esc_attr($set['twitter_username']); ?>"><br>
                            <small><?php _e('The Twitter username of the owner of this site\'s domain.', $this->text_domain); ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="submit" name="wpu_submit" value="<?php _e('Save', $this->text_domain) ?>"
                                   class="button-primary"/></td>
                        <td></td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
    }

    /**
     *
     */
    function shortlinks_get()
    {
        $url = $_POST['url'];
        $result = $this->get_shortlink($url, $full = true);

        if (is_array($result) && $result['status'] == "OK") {
            $qrc = array('qrcode_img' => $this->qrcode($result['short_url'], 200));
            $result = array_merge($result, $qrc);
        }

        echo json_encode($result);
        exit;
    }

    /**
     *
     */
    function load_box()
    {
        if (is_admin() || (!is_admin() && is_admin_bar_showing())) {
            ?>
            <div id="wpu_box_overlay"></div>
            <div id="wpu_box">
                <div class="wpu_title">
                    <span class="title"><?php _e('<a href="http://wpu.ir" target="_blank">WPU.IR</a> url shortener', $this->text_domain) ?></span>
                    <span class="hide">×</span>
                </div>
                <div class="wpu_content">
                    <?php _e('Long URL:', $this->text_domain) ?>
                    <br>
                    <input type="text" class="long_url ltr" onfocus="this.select()">
                    <div>
                        <input type="button"
                               class="button button-primary button-large"
                               value="<?php _e('Shorten URL', $this->text_domain) ?>">
                        <span class="spinner is-active"></span>
                    </div>
                    <div class="update-nag">
                        <p class="INVALID_URL"><?php _e('Invalid URL!', $this->text_domain) ?></p>
                        <p class="RATE_LIMIT"><?php _e('Rate limit exceeded!', $this->text_domain) ?></p>
                        <p class="AUTH_ERROR"><?php _e('Invalid API key!', $this->text_domain) ?></p>
                        <p class="INTERNAL_ERROR"><?php _e('Error in connect to webservice!', $this->text_domain) ?></p>
                    </div>
                    <div class="result">
                        <?php _e('Shortlink: ', $this->text_domain); ?>
                        <input type="text" class="short_url ltr" readonly onfocus="this.select();"
                               onmouseup="return false;">
                        <br>
                        <img src=""
                             class="qrcode" alt="QRCode">
                    </div>
                </div>
            </div>
            <?php
        }
    }

    /**
     *
     */
    function wp_head()
    {
        if (is_admin() || (!is_admin() && is_admin_bar_showing())) {
            ?>
            <script type="text/javascript">
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            </script>
            <?php
        }
    }

    /**
     * @param $wp_admin_bar
     */
    function admin_bar($wp_admin_bar)
    {
        $args = array(
            'id' => 'wpu_shortlink',
            "href" => "#",
            'title' => __('WPU Shortlinks', $this->text_domain),
            'meta' => array(
                'class' => 'wpu_shortlink_ab',
                'onclick' => 'wpu_load_box()'
            )
        );
        $wp_admin_bar->add_menu($args);
    }

    /**
     * @param $data
     * @param int $size
     * @param int $margin
     * @return string
     */
    function qrcode($data, $size = 150, $margin = 1)
    {
        return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$data}&choe=UTF-8&chld=L|{$margin}";
    }

    /**
     * @param $defaults
     * @return mixed
     */
    function columns_head($defaults)
    {
        if (!$this->support('ptype'))
            return $defaults;
        $defaults['wpu_shortlinks_column'] = __('Shortlink', $this->text_domain);
        return $defaults;
    }

    /**
     * @param $column_name
     * @param $post_id
     */
    function columns_content($column_name, $post_id)
    {
        if (!$this->support('ptype'))
            return;
        if ($column_name == 'wpu_shortlinks_column') {
            $wpu_shortlink = $this->get_post_meta($post_id);
            if (!empty($wpu_shortlink)) {
                $wpu_shortlink_text = str_replace(array("http://", "http://www"), '', $wpu_shortlink);
                echo '<a href="' . $wpu_shortlink . '" target="_blank">' . $wpu_shortlink_text . '</a>';
            }
        }
    }

    /**
     *
     */
    function admin_head()
    {
        global $pagenow;
        if ($pagenow == "post.php" || $pagenow == "post-new.php") {
            echo '
		<script language="javascript">
			var wpu_shortlinks_slidt = \'' . __('Post ID Shortlink? (Optional)', $this->text_domain) . '\';
			var wpu_shortlinks_slt = \'' . __('Shortlink Title?', $this->text_domain) . '\';
			var wpu_shortlinks_sltd = \'' . __('Shortlink', $this->text_domain) . '\';
			var wpu_shortlinks_ast = \'' . __('Add Shortlink wpu.ir', $this->text_domain) . '\';			
		</script>';
        }
    }

    /**
     * @param $content
     * @return string
     */
    function display_content($content)
    {
        global $post;
        $set = get_option("wpu_shortlinks_settings");
        $post_id = $post->ID;
        $sharing_support = $shortlink_support = false;
        $sh_support = $this->support('sh_ptype');
        $sn_support = $this->support('sn_ptype');

        if ($sh_support && isset($set['sh_ptype']['index']) && (is_archive() || is_home() || is_search()))
            $shortlink_support = true;
        elseif ($sh_support && (is_page() || is_single()))
            $shortlink_support = true;

        if ($set['social_sharing_status']) {
            if ($sn_support && isset($set['sn_ptype']['index']) && (is_archive() || is_home() || is_search()))
                $sharing_support = true;
            elseif ($sn_support && (is_page() || is_single()))
                $sharing_support = true;
        }

        if ($sharing_support && $set['social_sharing_order'] == "before_shortlink") {
            $content .= $this->social_sharing(false);
        }

        if ($shortlink_support) {
            $short_link = $this->get_post_meta($post_id);

            if (empty($short_link))
                $short_link = wp_get_shortlink($post_id);

            if ($short_link || !empty($short_link)) {
                if ($set['dsc_type'] == "text")
                    $content .= '<div id="wpu_shortlink_content">' . __('Shortlink: ', $this->text_domain) . '<span class="shortlink_text">' . $short_link . '</span></div>';
                else
                    $content .= '<div id="wpu_shortlink_content">' . __('Shortlink: ', $this->text_domain) . ' <input type="text" value="' . $short_link . '" size="20" class="shortlink_textbox" readonly onfocus="this.select();" onclick="this.select();" />' . '</div>';
            }
        }

        if ($sharing_support && $set['social_sharing_order'] == "after_shortlink") {
            $content .= $this->social_sharing(false);
        }

        return $content;
    }

    /**
     * @param $post_id
     * @return mixed
     */
    function get_with_post($post_id)
    {
        global $post;
        /*
        * We need to verify this came from our screen and with proper authorization,
        * because the save_post action can be triggered at other times.
        */
        // Check if our nonce is set.
        if (!isset($_POST['wpu-shortlink-nonce']))
            return $post_id;

        if (!isset($_POST["wpu-shortlink-nonce"]) || !wp_verify_nonce($_POST["wpu-shortlink-nonce"], basename(__FILE__)))
            return $post_id;

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;

        // Return if it's a post revision
        if (false !== wp_is_post_revision($post_id))
            return $post_id;

        // Check the user's permissions.
        if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id))
                return $post_id;
        } else
            if (!current_user_can('edit_post', $post_id))
                return $post_id;

        $pos = strpos($post->post_name, 'autosave');
        if ($pos !== false)
            return $post_id;

        $pos = strpos($post->post_name, 'revision');
        if ($pos !== false)
            return $post_id;


        /*$revision = wp_is_post_revision($post_id);
        if ($revision)
            $post_id = $revision;
        $post = get_post($post_id);
        $post_id = $post->ID;*/

        $wpu_shortlink = $this->get_post_meta($post_id);

        if (empty($wpu_shortlink)) {
            $result = $this->get_shortlink($post_id);
            if ($result)
                $this->save_response($result, $post_id);
        }

        return $post_id;
    }


    /**
     * @param $attr
     * @param null $content
     * @return string
     */
    function wpu_shortcode($attr, $content = null)
    {
        global $post;
        $id = 0;
        extract(shortcode_atts(array(
            'id' => $post->ID
        ), $attr));
        $post_id = $id;
        $wpu_shortlink = $this->get_post_meta($post_id);
        if ($wpu_shortlink) {
            if (!empty($content))
                $shortlink_text = $content;
            else
                $shortlink_text = __('Post Shortlink', $this->text_domain);

            $result = '<a href="' . $wpu_shortlink . '" target="_blank">' . $shortlink_text . '</a>';
        }
        return $result;
    }

    /**
     * @param $buttons
     * @return mixed
     */
    function mce_buttons($buttons)
    {
        array_push($buttons, "wpu_shortcode");
        return $buttons;
    }

    /**
     * @param $plugin_array
     * @return mixed
     */
    function mce_external_plugins($plugin_array)
    {
        $plugin_array['wpu_shortcode'] = plugins_url('wpu-shortlinks/js/wpu-shortcode.js');
        return $plugin_array;
    }

    /**
     * @param $post
     */
    function publish_widget($post)
    {
        global $post;
        $post_id = $post->ID;

        if (!$this->support('ptype'))
            return;

        $wpu_shortlink = $this->get_post_meta($post_id);
        $out = '<div style="margin:5px 10px">';
        $out .= __('Shortlink: ', $this->text_domain) . ' <input type="text" name="wpu_shortlink" value="' . $wpu_shortlink . '" size="25" onfocus="this.select()" style="text-align:left;direction:ltr;" readonly />';
        if (!empty($_SESSION['wpu_error'])) {
            $out .= "<br /><span style='color:#f00'>" . $_SESSION['wpu_error'] . "</span>";
            unset($_SESSION['wpu_error']);
        }
        $out .= '</div>';
        wp_nonce_field(basename(__FILE__), "wpu-shortlink-nonce");
        echo $out;
    }

    /**
     * @param bool $echo
     * @return string
     */
    function social_sharing($echo = true)
    {
        $set = get_option("wpu_shortlinks_settings");
        if (!$this->support('sn_ptype') || !is_array($set['social_network']))
            return '';

        global $post;
        $post_id = $post->ID;
        $thumb_url = '';
        $this->set_variable();

        $short_link = $this->get_post_meta($post_id);
        if (empty($short_link))
            $short_link = wp_get_shortlink($post_id);

        if (has_post_thumbnail($post_id)) {
            $thumb_id = get_post_thumbnail_id();
            $thumb_url_array = wp_get_attachment_image_src($thumb_id, 'thumbnail-size', true);
            $thumb_url = $thumb_url_array[0];
        }

        $search = array('{url}', '{title}', '{image}', '{via}');
        $replace = array($short_link, $post->post_title, $thumb_url, $set['twitter_username']);

        $result = '<div class="wpu_sharing">';
        $result .= '<h3 class="title">' . $set['social_sharing_label'] . '</h3><br>';
        foreach ($set['social_network'] as $social_name => $v) {
            $social = $this->social_network[$social_name];
            $url = str_replace($search, $replace, $social['url']);
            $text = ($set['buttons_style'] == 'icon-text' || $set['buttons_style'] == 'icon' ? '<span class="' . $social['icon_class'] . '"></span>' : '');
            $text .= ($set['buttons_style'] == 'icon-text' || $set['buttons_style'] == 'text' ? '<span>' . $social['label'] . '</span>' : '');
            $result .= '<a href="' . $url . '" title="' . $social['label'] . '" target="_blank" class="share-' . $social_name . ' style-' . $set['buttons_style'] . '" rel="nofollow">' . $text . '</a> ';
        }

        $result .= '</div>';

        if ($echo)
            echo $result;
        else
            return $result;
    }

    /**
     * @param $post
     * @param bool $full
     * @return array|bool
     */
    function get_shortlink($post, $full = false)
    {
        $set = get_option("wpu_shortlinks_settings");

        if (empty(trim($set['api_key']))) {
            if ($full)
                return array('status' => 'AUTH_ERROR');
            else
                return false;
        }

        if (is_numeric($post)) {
            if (function_exists("wp_get_shortlink"))
                $post_url = wp_get_shortlink($post);
            if (empty($post_url))
                $post_url = home_url('/') . '?p=' . $post;

        } elseif (is_object($post)) {
            $post = $post->ID;
            $post_url = home_url('/') . '?p=' . $post;
        } else {
            $post_url = $post;
        }

        if ($this->is_validurl($post_url)) {
            $response = wp_remote_post($this->Shortener_API, array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => array(
                        'key' => $set['api_key'],
                        'long_url' => $post_url
                    ),
                    'cookies' => array()
                )
            );

            if (is_wp_error($response)) {
                $_SESSION['wpu_error'] = __('Error in connect to webservice!', $this->text_domain);
                if ($full)
                    return array('status' => 'INTERNAL_ERROR');
                else
                    return false;
            } else {
                if ($full)
                    return json_decode($response['body'], true);

                $json = json_decode($response['body'], true);
                if ($json['status'] == 'OK') {
                    return $json['short_url'];
                } else {
                    if ($json['status'] == 'INVALID_URL')
                        $_SESSION['wpu_error'] = __('Invalid URL!', $this->text_domain);
                    elseif ($json['status'] == 'RATE_LIMIT')
                        $_SESSION['wpu_error'] = __('Rate limit exceeded!', $this->text_domain);
                    elseif ($json['status'] == 'AUTH_ERROR')
                        $_SESSION['wpu_error'] = __('Invalid API key!', $this->text_domain);
                    elseif ($json['status'] == 'INTERNAL_ERROR')
                        $_SESSION['wpu_error'] = __('Error in connect to webservice!', $this->text_domain);

                    return false;
                }
            }
        } else {
            $_SESSION['wpu_error'] = __('Invalid URL!', $this->text_domain);
            if ($full)
                return array('status' => 'INVALID_URL');
            else
                return false;
        }
    }

    /**
     * @param $result
     * @param $post_id
     */
    function save_response($result, $post_id)
    {
        if ($this->is_validurl($result))
            if (!add_post_meta($post_id, 'wpu_shortlink', $result, true))
                update_post_meta($post_id, 'wpu_shortlink', $result);
    }

    /**
     * @param $url
     * @return bool
     */
    function is_validurl($url)
    {
        $url = trim($url);
        if ($url == NULL || $url == "")
            return false;

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $post_id
     * @return bool|mixed
     */
    function get_post_meta($post_id)
    {
        $wpu_shortlink = get_post_meta($post_id, 'wpu_shortlink', true);

        if ($this->is_validurl($wpu_shortlink))
            return $wpu_shortlink;
        else
            return false;
    }
}

$WPU_ShortLink = new WPUShortLinks;

/**
 * Get Post ShortLink
 * @param null $post_id
 * @param bool $echo
 * @return bool|mixed|string
 */
function wpu_shortlink($post_id = NULL, $echo = true)
{
    global $post, $WPU_ShortLink;
    if ($post_id == NULL)
        $post_id = $post->ID;
    $short_link = $WPU_ShortLink->get_post_meta($post_id);
    if ($short_link)
        $short_link = wp_get_shortlink($post_id = $post->ID);
    if ($echo)
        echo $short_link;
    else
        return $short_link;
}


/**
 * Display Social Sharing Buttons
 * @param bool $echo
 * @return string
 */
function wpu_social_sharing($echo = true)
{
    global $WPU_ShortLink;
    $html = $WPU_ShortLink->social_sharing();
    if ($echo)
        echo $html;
    else
        return $html;
}

/**
 * Get ShortLink from URL
 * @param $url
 * @return mixed
 */
function wpu_get_shortlink($url)
{
    global $WPU_ShortLink;
   return $WPU_ShortLink->get_shortlink($url);
}