<?php
namespace ANAF_Facturare;

class Checkout_Fields {
    public function __construct() {
        // Adaugă câmpuri în checkout
        add_filter('woocommerce_checkout_fields', [$this, 'add_checkout_fields']);
        
        // Adaugă selector tip client
        add_action('woocommerce_before_checkout_billing_form', [$this, 'add_customer_type_field']);
        
        // Validare câmpuri
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);
        
        // Salvare câmpuri
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_checkout_fields']);
        
        // Script pentru gestionarea afișării câmpurilor
        add_action('wp_enqueue_scripts', [$this, 'enqueue_checkout_scripts']);

        // Adaugă câmpurile în administrare
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_admin_order_fields'], 10, 1);
        
        // Adaugă câmpurile în email-uri
        add_filter('woocommerce_email_order_meta_fields', [$this, 'add_email_order_meta'], 10, 3);
        
        // Adaugă câmpurile în contul clientului - Adrese
        add_filter('woocommerce_my_account_my_address_formatted_address', [$this, 'add_address_format_fields'], 10, 3);
        
        // Adaugă câmpurile în formularul de editare adresă
        add_filter('woocommerce_address_to_edit', [$this, 'add_address_fields_to_edit'], 10, 2);
        
        // Salvează câmpurile din formularul de editare adresă
        add_action('woocommerce_customer_save_address', [$this, 'save_address_fields'], 10, 2);
        
        // Adaugă câmpurile în detaliile comenzii din contul clientului
        add_action('woocommerce_order_details_after_customer_details', [$this, 'display_order_customer_fields'], 10, 1);
        
        // Formatare adresă pentru afișare
        add_filter('woocommerce_formatted_address_replacements', [$this, 'add_formatted_address_replacements'], 10, 2);
        add_filter('woocommerce_localisation_address_formats', [$this, 'add_address_format']);
    }

    public function add_customer_type_field($checkout) {
        woocommerce_form_field('billing_customer_type', [
            'type' => 'select',
            'class' => ['form-row-wide'],
            'label' => __('Tip Client', 'anaf-facturare'),
            'required' => true,
            'options' => [
                'individual' => __('Persoană Fizică', 'anaf-facturare'),
                'company' => __('Societate', 'anaf-facturare')
            ],
            'default' => 'individual'
        ], $checkout->get_value('billing_customer_type'));
    }

    public function add_checkout_fields($fields) {
        // Adaugă câmp CUI
        $fields['billing']['billing_cui'] = [
            'label' => __('Cod Fiscal (CUI)', 'anaf-facturare'),
            'placeholder' => __('Ex: RO12345678', 'anaf-facturare'),
            'required' => false,
            'class' => ['form-row-first', 'billing-cui'],
            'clear' => false,
            'priority' => 31
        ];

        // Adaugă câmp Registrul Comerțului
        $fields['billing']['billing_reg_com'] = [
            'label' => __('Nr. Registrul Comerțului', 'anaf-facturare'),
            'placeholder' => __('Ex: J40/123/2000', 'anaf-facturare'),
            'required' => false,
            'class' => ['form-row-last', 'billing-reg-com'],
            'clear' => true,
            'priority' => 31
        ];

        // Modifică prioritățile pentru o aranjare mai bună
        $fields['billing']['billing_company']['priority'] = 30;
        $fields['billing']['billing_company']['class'][] = 'billing-company';

        return $fields;
    }

    public function validate_checkout_fields() {
        // Verifică dacă e selectat tipul de client
        if (!$_POST['billing_customer_type']) {
            wc_add_notice(__('Vă rugăm să selectați tipul de client.', 'anaf-facturare'), 'error');
        }

        // Dacă e societate, verifică câmpurile obligatorii
        if ($_POST['billing_customer_type'] === 'company') {
            if (empty($_POST['billing_company'])) {
                wc_add_notice(__('Vă rugăm să completați numele firmei.', 'anaf-facturare'), 'error');
            }
            if (empty($_POST['billing_cui'])) {
                wc_add_notice(__('Vă rugăm să completați codul fiscal (CUI).', 'anaf-facturare'), 'error');
            }
            if (empty($_POST['billing_reg_com'])) {
                wc_add_notice(__('Vă rugăm să completați numărul de înregistrare la Registrul Comerțului.', 'anaf-facturare'), 'error');
            }
        }
    }

    public function save_checkout_fields($order_id) {
        if (!empty($_POST['billing_customer_type'])) {
            update_post_meta($order_id, '_billing_customer_type', sanitize_text_field($_POST['billing_customer_type']));
        }
        if (!empty($_POST['billing_cui'])) {
            update_post_meta($order_id, '_billing_cui', sanitize_text_field($_POST['billing_cui']));
        }
        if (!empty($_POST['billing_reg_com'])) {
            update_post_meta($order_id, '_billing_reg_com', sanitize_text_field($_POST['billing_reg_com']));
        }
    }

    public function enqueue_checkout_scripts() {
        if (!is_checkout()) {
            return;
        }

        wp_enqueue_script(
            'anaf-checkout',
            ANAF_FACTURARE_URL . 'assets/js/checkout.js',
            ['jquery'],
            ANAF_FACTURARE_VERSION,
            true
        );

        wp_localize_script('anaf-checkout', 'anafCheckout', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('anaf-checkout')
        ]);
    }
}
