<?php
/**
 * Plugin Name: ANAF Facturare
 * Description: Integration with ANAF API for company lookup
 * Version: 1.0.1
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: anaf-facturare
 * Domain Path: /languages
 * 
 * Changelog:
 * 1.0.1 - Added complete translation support and improved error handling
 */

defined('ABSPATH') || exit;

// Load dependencies
require_once __DIR__ . '/includes/class-anaf-api.php';

/**
 * Main plugin class
 */
class ANAF_Facturare_Plugin {
    /**
     * @var ANAF_API
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new \ANAF_Facturare\ANAF_API();
        
        // Load translations
        add_action('init', [$this, 'load_plugin_textdomain']);
        
        // Register AJAX endpoints
        add_action('wp_ajax_anaf_lookup_company', [$this, 'handle_company_lookup']);
        add_action('wp_ajax_nopriv_anaf_lookup_company', [$this, 'handle_company_lookup']);

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Load plugin translations
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'anaf-facturare',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Handle AJAX company lookup
     */
    public function handle_company_lookup() {
        check_ajax_referer('anaf_lookup_nonce', 'nonce');

        // Get CUI from request
        $cui = isset($_POST['cui']) ? sanitize_text_field($_POST['cui']) : '';
        if (empty($cui)) {
            wp_send_json_error([
                'message' => esc_html__('Vă rugăm să introduceți un CUI valid', 'anaf-facturare')
            ]);
        }

        // Lookup company
        $result = $this->api->get_company_details($cui);
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => esc_html__('Eroare la căutarea companiei: ', 'anaf-facturare') . $result->get_error_message()
            ]);
        }

        // Return success response
        wp_send_json_success($result);
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        wp_enqueue_script(
            'anaf-facturare',
            plugins_url('assets/js/checkout.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('anaf-facturare', 'anafFacturare', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('anaf_lookup_nonce'),
            'i18n' => [
                'error' => esc_html__('Eroare la căutarea detaliilor companiei', 'anaf-facturare'),
                'notFound' => esc_html__('Compania nu a fost găsită', 'anaf-facturare'),
                'loading' => esc_html__('Se caută...', 'anaf-facturare'),
                'invalidCui' => esc_html__('CUI invalid', 'anaf-facturare'),
                'networkError' => esc_html__('Eroare de rețea. Vă rugăm încercați din nou.', 'anaf-facturare')
            ]
        ]);
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        new ANAF_Facturare_Plugin();
    }
});
