<?php
/**
 * Admin class of WP Extened Search
 *
 * @author 5um17
 */
class WP_ES_admin {
    
    /* Defaults Variable */
    public $text_domain = '';
    public $wpcf_fields = '';

    /**
     * Default Constructor
     */
    public function __construct(){
        global $WP_ES;
        
        $this->text_domain = $WP_ES->text_domain;

        add_action('admin_menu', array($this, 'WP_ES_admin_add_page'));
        add_action('admin_init', array($this, 'WP_ES_admin_init'));
        
        add_action('admin_enqueue_scripts', array($this, 'WP_ES_admin_scripts'));
    }

    /**
     * Add Admin page
     */
    public function WP_ES_admin_add_page(){
        add_options_page('WP Extended Search Settings', 'Extended Search', 'manage_options', 'wp-es', array($this, 'wp_es_page'));
    }

    /**
     * Print admin page content
     */
    public function wp_es_page(){ ?>
        <div class="wrap">
            
            <h2>WP Extended Search <?php _e('Settings', $this->text_domain); ?></h2>

            <form method="post" action="options.php"><?php
                settings_fields('wp_es_option_group');	
                do_settings_sections('wp-es');
                submit_button(__('Save Changes'), 'primary', 'submit', false);
                echo '&nbsp;&nbsp;';
                submit_button(__('Reset to WP default'), 'secondary', 'reset', false); ?>
            </form>
            
        </div><?php
    }

    /**
     * Add Section settings and settings fields
     */
    public function WP_ES_admin_init(){

        /* Register Settings */
        register_setting('wp_es_option_group', 'wp_es_options', array($this, 'wp_es_save'));

        /* Add Section */
        add_settings_section( 'wp_es_section_1', __('Select Fields to include in WordPress default Search', $this->text_domain ), array($this, 'wp_es_section_content'), 'wp-es' );	

        /* Add fields */
        add_settings_field( 'wp_es_title_and_post_content', __('General Search Setting', $this->text_domain), array($this, 'wp_es_title_content_checkbox'), 'wp-es', 'wp_es_section_1' );
        add_settings_field( 'wp_es_list_custom_fields', __('Select Meta Key Names' , $this->text_domain), array($this, 'wp_es_custom_field_name_list'), 'wp-es', 'wp_es_section_1' );
        add_settings_field( 'wp_es_list_taxonomies', __('Select Taxonomies' , $this->text_domain), array($this, 'wp_es_taxonomies_settings'), 'wp-es', 'wp_es_section_1' );
        add_settings_field( 'wp_es_list_post_types', __('Select Post Types' , $this->text_domain), array($this, 'wp_es_post_types_settings'), 'wp-es', 'wp_es_section_1' );
        
    }
    
    /**
     * enqueue admin style and scripts
     */
    public function WP_ES_admin_scripts() {
        wp_enqueue_style('wpes_admin_css', WP_ES_URL . 'assets/css/wp-es-admin.css');
    }

    /**
     * Get all meta keys
     * @global Object $wpdb WPDB object
     * @return Array array of meta keys
     */
    public function wp_es_fields() {
        global $wpdb;
        $wp_es_fields = $wpdb->get_results("select DISTINCT meta_key from $wpdb->postmeta where meta_key NOT LIKE '\_%' ORDER BY meta_key ASC");
        $meta_keys = array();

        if (is_array($wp_es_fields) && !empty($wp_es_fields)) {
            foreach ($wp_es_fields as $field){
                if (isset($field->meta_key)) {
                    $meta_keys[] = $field->meta_key;
                }
            }
        }
        
        return $meta_keys;
    }

    /**
     * Validate input settings
     * @global object $WP_ES Main class object
     * @param array $input input array by user
     * @return array validated input for saving
     */
    public function wp_es_save($input){
        global $WP_ES;
        $settings = $WP_ES->WP_ES_settings;
        
        if (isset($_POST['reset'])) {
            add_settings_error('wp_es_error', 'wp_es_error_reset', __('Your settings has been changed to WordPress default search setting.', $this->text_domain), 'updated');
            return $WP_ES->default_options();
        }
        
        if (!isset($input['post_types']) || empty($input['post_types'])) {
            add_settings_error('wp_es_error', 'wp_es_error_post_type', __('Select atleast one post type!', $this->text_domain));
            return $settings;
        }
        
        if (empty($input['title']) && empty($input['content']) && (!isset($input['meta_keys']) || empty($input['meta_keys'])) && (!isset($input['taxonomies']) || empty($input['taxonomies']))) {
            add_settings_error('wp_es_error', 'wp_es_error_all_empty', __('Select atleast one setting to search!', $this->text_domain));
            return $settings;   
        }
        
        if (empty($input['title']) || empty($input['content'])) {
            add_settings_error('wp_es_error', 'wp_es_error_settings_saved', __('Settings saved.', $this->text_domain), 'updated');
            add_settings_error('wp_es_error', 'wp_es_error_default_settings', __('You have made changes to WordPress default search settings!', $this->text_domain), 'updated attention');
        }
        
        return $input;
    }

    /**
     * Section content before display fields
     */
    public function wp_es_section_content(){ ?>
        <em><?php _e('Every field have OR relation with each other. e.g. if someone search for "5um17" then search results will show those items which have "5um17" as meta value or taxonomy\'s term or in title or in content, whatever option is selected.', $this->text_domain); ?></em><?php
    }

    /**
     * Default settings checkbox
     * @global object $WP_ES
     */
    public function wp_es_title_content_checkbox(){ 
        global $WP_ES;
        $settings = $WP_ES->WP_ES_settings; ?>

        <input type="hidden" name="wp_es_options[title]" value="0" />
        <input <?php checked($settings['title']); ?> type="checkbox" id="estitle" name="wp_es_options[title]" value="1" />&nbsp;
        <label for="estitle"><?php _e('Search in Title', $this->text_domain); ?></label>
        <br />
        <input type="hidden" name="wp_es_options[content]" value="0" />
        <input <?php checked($settings['content']); ?> type="checkbox" id="escontent" name="wp_es_options[content]" value="1" />&nbsp;
        <label for="escontent"><?php _e('Search in Content', $this->text_domain); ?></label><?php
    }

    /**
     * Meta keys checkboxes
     * @global object $WP_ES
     */
    public function wp_es_custom_field_name_list() {
        global $WP_ES;

        $meta_keys = $this->wp_es_fields();
        if (!empty($meta_keys)) { ?>
            <div class="wpes-meta-keys-wrapper"><?php
                foreach ((array)$meta_keys as $meta_key) { ?>
                    <p>
                        <input <?php echo $this->wp_es_checked($meta_key, $WP_ES->WP_ES_settings['meta_keys']); ?> type="checkbox" id="<?php echo $meta_key; ?>" name="wp_es_options[meta_keys][]" value="<?php echo $meta_key; ?>" />
                        <label for="<?php echo $meta_key; ?>"><?php echo $meta_key; ?></label>&nbsp;&nbsp;&nbsp;
                    </p><?php
                } ?>
            </div><?php
        } else { ?>
            <em><?php _e('No meta key found!', $this->text_domain); ?></em><?php
        }

    }
    
    /**
     * Taxonomies checboxes
     * @global object $WP_ES
     */
    public function wp_es_taxonomies_settings() {
        global $WP_ES;
        
        $all_taxonomies = get_taxonomies(array(
            'show_ui' => TRUE,
            'public' => TRUE
        ), 'objects');
        
        if (is_array($all_taxonomies) && !empty($all_taxonomies)) {
            foreach ($all_taxonomies as $tax_name => $tax_obj) { ?>
                <input <?php echo $this->wp_es_checked($tax_name, $WP_ES->WP_ES_settings['taxonomies']); ?> type="checkbox" value="<?php echo $tax_name; ?>" id="<?php echo 'wp_es_' . $tax_name; ?>" name="wp_es_options[taxonomies][]" />&nbsp;
                <label for="<?php echo 'wp_es_' . $tax_name; ?>"><?php echo isset($tax_obj->labels->name) ? $tax_obj->labels->name : $tax_name; ?></label><br /><?php
            }
        } else { ?>
            <em><?php _e('No public taxonomy found!', $this->text_domain); ?></em><?php
        }
    }
    
    /**
     * Post type checkboexes
     * @global object $WP_ES
     */
    public function wp_es_post_types_settings() {
        global $WP_ES;
        
        $all_post_types = get_post_types(array(
            'show_ui' => TRUE,
            'public' => TRUE
        ), 'objects');
        
        if (is_array($all_post_types) && !empty($all_post_types)) {
            foreach ($all_post_types as $post_name => $post_obj) { ?>
                <input <?php echo $this->wp_es_checked($post_name, $WP_ES->WP_ES_settings['post_types']); ?> type="checkbox" value="<?php echo $post_name; ?>" id="<?php echo 'wp_es_' . $post_name; ?>" name="wp_es_options[post_types][]" />&nbsp;
                <label for="<?php echo 'wp_es_' . $post_name; ?>"><?php echo isset($post_obj->labels->name) ? $post_obj->labels->name : $post_name; ?></label><br /><?php
            }
        } else { ?>
            <em><?php _e('No public post type found!', $this->text_domain); ?></em><?php
        }
    }
    
    /**
     * return checked if value exist in array
     * @param mixed $value value to check against array
     * @param array $array haystack array
     * @return string checked="checked" or blank string
     */
    public function wp_es_checked($value = false, $array = array()) {
        if (in_array($value, $array, true)) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }
        
        return $checked;
    }
}