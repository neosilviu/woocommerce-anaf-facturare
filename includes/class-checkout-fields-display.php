    /**
     * Afișează câmpurile în panoul de administrare
     */
    public function display_admin_order_fields($order) {
        $customer_type = get_post_meta($order->get_id(), '_billing_customer_type', true);
        $cui = get_post_meta($order->get_id(), '_billing_cui', true);
        $reg_com = get_post_meta($order->get_id(), '_billing_reg_com', true);
        
        if ($customer_type === 'company') {
            echo '<p><strong>' . __('Tip Client', 'anaf-facturare') . ':</strong> ' . __('Societate', 'anaf-facturare') . '</p>';
            if ($cui) {
                echo '<p><strong>' . __('CUI', 'anaf-facturare') . ':</strong> ' . esc_html($cui) . '</p>';
            }
            if ($reg_com) {
                echo '<p><strong>' . __('Nr. Reg. Com.', 'anaf-facturare') . ':</strong> ' . esc_html($reg_com) . '</p>';
            }
        } else {
            echo '<p><strong>' . __('Tip Client', 'anaf-facturare') . ':</strong> ' . __('Persoană Fizică', 'anaf-facturare') . '</p>';
        }
    }

    /**
     * Adaugă câmpurile în email-uri
     */
    public function add_email_order_meta($fields, $sent_to_admin, $order) {
        $customer_type = get_post_meta($order->get_id(), '_billing_customer_type', true);
        
        if ($customer_type === 'company') {
            $fields['billing_customer_type'] = [
                'label' => __('Tip Client', 'anaf-facturare'),
                'value' => __('Societate', 'anaf-facturare')
            ];
            $fields['billing_cui'] = [
                'label' => __('CUI', 'anaf-facturare'),
                'value' => get_post_meta($order->get_id(), '_billing_cui', true)
            ];
            $fields['billing_reg_com'] = [
                'label' => __('Nr. Reg. Com.', 'anaf-facturare'),
                'value' => get_post_meta($order->get_id(), '_billing_reg_com', true)
            ];
        }
        
        return $fields;
    }

    /**
     * Adaugă câmpurile în formatul de adresă
     */
    public function add_address_format_fields($address, $customer_id, $address_type) {
        if ($address_type === 'billing') {
            $customer_type = get_user_meta($customer_id, 'billing_customer_type', true);
            
            if ($customer_type === 'company') {
                $address['cui'] = get_user_meta($customer_id, 'billing_cui', true);
                $address['reg_com'] = get_user_meta($customer_id, 'billing_reg_com', true);
            }
        }
        
        return $address;
    }

    /**
     * Adaugă câmpurile în formularul de editare adresă
     */
    public function add_address_fields_to_edit($address, $load_address) {
        if ($load_address === 'billing') {
            $address['billing_customer_type'] = [
                'type' => 'select',
                'label' => __('Tip Client', 'anaf-facturare'),
                'required' => true,
                'options' => [
                    'individual' => __('Persoană Fizică', 'anaf-facturare'),
                    'company' => __('Societate', 'anaf-facturare')
                ]
            ];
            
            $address['billing_cui'] = [
                'label' => __('CUI', 'anaf-facturare'),
                'required' => false
            ];
            
            $address['billing_reg_com'] = [
                'label' => __('Nr. Reg. Com.', 'anaf-facturare'),
                'required' => false
            ];
        }
        
        return $address;
    }

    /**
     * Salvează câmpurile din formularul de editare adresă
     */
    public function save_address_fields($user_id, $load_address) {
        if ($load_address === 'billing') {
            if (isset($_POST['billing_customer_type'])) {
                update_user_meta($user_id, 'billing_customer_type', sanitize_text_field($_POST['billing_customer_type']));
            }
            if (isset($_POST['billing_cui'])) {
                update_user_meta($user_id, 'billing_cui', sanitize_text_field($_POST['billing_cui']));
            }
            if (isset($_POST['billing_reg_com'])) {
                update_user_meta($user_id, 'billing_reg_com', sanitize_text_field($_POST['billing_reg_com']));
            }
        }
    }

    /**
     * Afișează câmpurile în detaliile comenzii din contul clientului
     */
    public function display_order_customer_fields($order) {
        $customer_type = get_post_meta($order->get_id(), '_billing_customer_type', true);
        if ($customer_type === 'company') {
            echo '<section class="woocommerce-customer-details--company">';
            echo '<h2 class="woocommerce-column__title">' . __('Detalii Companie', 'anaf-facturare') . '</h2>';
            
            $cui = get_post_meta($order->get_id(), '_billing_cui', true);
            $reg_com = get_post_meta($order->get_id(), '_billing_reg_com', true);
            
            if ($cui) {
                echo '<p><strong>' . __('CUI', 'anaf-facturare') . ':</strong> ' . esc_html($cui) . '</p>';
            }
            if ($reg_com) {
                echo '<p><strong>' . __('Nr. Reg. Com.', 'anaf-facturare') . ':</strong> ' . esc_html($reg_com) . '</p>';
            }
            
            echo '</section>';
        }
    }

    /**
     * Adaugă câmpurile în formatarea adresei
     */
    public function add_formatted_address_replacements($replacements, $args) {
        $replacements['{company_cui}'] = isset($args['cui']) ? $args['cui'] : '';
        $replacements['{company_reg_com}'] = isset($args['reg_com']) ? $args['reg_com'] : '';
        return $replacements;
    }

    /**
     * Adaugă format pentru adresă
     */
    public function add_address_format($formats) {
        // Adaugă câmpurile noi în formatul existent
        foreach ($formats as $country => $format) {
            $formats[$country] = $format . "\n{company_cui}\n{company_reg_com}";
        }
        return $formats;
    }
