<?php
/**
 * Main class of WP Extened Search
 *
 * @author 5um17
 */
class WP_ES {

    /* Defaults Variable */
    public $text_domain = '';
    public $WP_ES_settings = '';

    /**
     * Default Constructor
     */
    public function __construct() {
        
        $this->text_domain = 'WP_ES';
        $this->WP_ES_settings = $this->wp_es_options();
        
        if (!is_admin()) {
            //Only filter non admin requests!
            add_action('init', array($this,'wp_es_init'));
        }
    }
    
    /**
     * Get Defualt options
     */
    public function default_options() {
        $settings = array(
                'title'             =>  true,
                'content'           =>  true,
                'meta_keys'         =>  array(),
                'taxonomies'        =>  array(),
                'post_types'        =>  array('post', 'page', 'attachment')
            );
        return $settings;
    }

    /**
     * Get plugin options
     */
    public function wp_es_options() {
        $db_settings = get_option('wp_es_options');
        $settings = wp_parse_args($db_settings, $this->default_options());
        return $settings;
    }

    /**
     * Init function
     * @return NULL
     */
    public function wp_es_init() {
        /* Filter to modify search query */
        add_filter( 'posts_search', array($this, 'wp_es_custom_query'), 500, 2 );
        
        /* Action for modify query arguments */
        add_action( 'pre_get_posts' , array($this, 'wp_es_pre_get_posts'), 500);
    }
    
    /**
     * Add post type in where clause of wp query
     * @param object $args wp_query object
     */
    public function wp_es_pre_get_posts($query) {
        if (isset($query->is_search) && !empty($query->is_search)) {
            if (isset($this->WP_ES_settings['post_types']) && !empty($this->WP_ES_settings['post_types'])) {
                $query->query_vars['post_type'] = (array) $this->WP_ES_settings['post_types'];
            }
        }
    }

    /**
     * Core function return the custom query
     * @global Object $wpdb wordpress db object
     * @param string $search Search query
     * @param object $wp_query WP query
     * @return string $search Search query
     */
    public function wp_es_custom_query( $search, $wp_query ) {
        global $wpdb;
        
        if ( empty( $search ) ) {
            return $search; // skip processing - no search term in query
        }
        
        $q = $wp_query->query_vars;
        $n = !empty($q['exact']) ? '' : '%';
        $search = $searchand = '';
        foreach ((array)$q['search_terms'] as $term ) {
            
            $term = esc_sql($term);
            
            //Support for older version of worpdress < 4.0
            if (method_exists($wpdb, 'esc_like')) {
                $term = $wpdb->esc_like( $term );
            } else {
                $term = like_escape( $term );
            }

            /* change query as per plugin settings */
            $OR = '';
            if (!empty($this->WP_ES_settings)) {
                $search .= "{$searchand} (";

                // if post title search is enabled
                if (!empty($this->WP_ES_settings['title'])) {
                    $search .= "($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')";
                    $OR = ' OR ';
                }
                
                //if content search is enabled
                if (!empty($this->WP_ES_settings['content'])) {
                    $search .= $OR;
                    $search .= "($wpdb->posts.post_content LIKE '{$n}{$term}{$n}')";
                    $OR = ' OR ';
                }

                // if post meta search is enabled
                if (isset($this->WP_ES_settings['meta_keys']) && !empty($this->WP_ES_settings['meta_keys'])) {
                    $meta_key_OR = '';

                    foreach ($this->WP_ES_settings['meta_keys'] as $key_slug) {
                        $search .= $OR;
                        $search .= "$meta_key_OR (pm.meta_key = '{$key_slug}' AND pm.meta_value LIKE '{$n}{$term}{$n}')";
                        $OR = '';
                        $meta_key_OR = ' OR ';
                    }
                    
                    $OR = ' OR ';
                }
                
                // if taxonomies search is enabled
                if (isset($this->WP_ES_settings['taxonomies']) && !empty($this->WP_ES_settings['taxonomies'])) {
                    $tax_OR = '';
                    
                    foreach ($this->WP_ES_settings['taxonomies'] as $tax) {
                        $search .= $OR;
                        $search .= "$tax_OR (tt.taxonomy = '{$tax}' AND t.name LIKE '{$n}{$term}{$n}')";
                        $OR = '';
                        $tax_OR = ' OR ';
                    }
                }
                
                $search .= ")";
            } else {
                // If plugin settings not available return the default query
                $search .= "{$searchand} (($wpdb->posts.post_title LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}'))";
            }

            $searchand = ' AND ';
        }

        if ( ! empty( $search ) ) {
            $search = " AND ({$search}) ";
            if ( ! is_user_logged_in() )
                $search .= " AND ($wpdb->posts.post_password = '') ";
        }
        
        /* Join Table */
        add_filter('posts_join_request', array($this, 'wp_es_join_table'));

        /* Request distinct results */
        add_filter('posts_distinct_request', array($this, 'WP_ES_distinct'));
        
        return $search; // phew :P All done, Now return everything to wp.
    }
    
    /**
     * Join tables
     * @global Object $wpdb WPDB object
     * @param string $join query for join
     * @return string $join query for join
     */
    public function wp_es_join_table($join){
        global $wpdb;
        
        //join post meta table
        if (!empty($this->WP_ES_settings['meta_keys'])) {
            $join .= " LEFT JOIN $wpdb->postmeta pm ON ($wpdb->posts.ID = pm.post_id) ";
        }
        
        //join taxonomies table
        if (!empty($this->WP_ES_settings['taxonomies'])) {
            $join .= " LEFT JOIN $wpdb->term_relationships tr ON ($wpdb->posts.ID = tr.object_id) ";
            $join .= " INNER JOIN $wpdb->term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
            $join .= " INNER JOIN $wpdb->terms t ON (tt.term_id = t.term_id) ";
        }
        
        return $join;
    }

    /**
     * Request distinct results
     * @param string $distinct
     * @return string $distinct
     */
    public function WP_ES_distinct($distinct) {
        $distinct = 'DISTINCT';
        return $distinct;
    }
}