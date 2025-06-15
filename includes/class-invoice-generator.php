<?php
namespace ANAF_Facturare;

class Invoice_Generator {
    private $order;
    private $settings;

    public function __construct($order) {
        $this->order = $order;
        $this->settings = get_option('anaf_facturare_settings', []);
    }

    public function generate() {
        // Verifică dacă factura a fost deja generată
        if ($this->has_invoice()) {
            return false;
        }

        // Preia datele companiei din ANAF
        $company_data = $this->get_company_data();
        if (!$company_data) {
            throw new \Exception('Nu s-au putut prelua datele companiei din ANAF');
        }

        // Generează factura
        $invoice_data = $this->prepare_invoice_data($company_data);
        $invoice_id = $this->create_invoice($invoice_data);

        // Generează XML pentru e-Factura
        $xml = $this->generate_efactura_xml($invoice_data);
        
        // Salvează XML și actualizează meta pentru comandă
        $this->save_invoice_xml($invoice_id, $xml);
        $this->update_order_meta($invoice_id);

        return $invoice_id;
    }

    private function has_invoice() {
        return (bool) get_post_meta($this->order->get_id(), '_anaf_invoice_id', true);
    }

    private function get_company_data() {
        $cui = $this->get_company_cui();
        if (!$cui) {
            return false;
        }

        $api = new ANAF_API($this->settings['api_key']);
        return $api->get_company_info($cui);
    }

    private function get_company_cui() {
        // Încearcă să preia CUI din meta comenzii sau din billing
        $cui = $this->order->get_meta('_billing_cui');
        if (!$cui) {
            // Extrage CUI din alte câmpuri sau solicită manual
            $cui = $this->extract_cui_from_billing();
        }
        return $cui;
    }

    private function prepare_invoice_data($company_data) {
        return [
            'series' => $this->settings['invoice_series'],
            'number' => $this->get_next_invoice_number(),
            'date' => current_time('mysql'),
            'due_date' => date('Y-m-d', strtotime('+' . $this->settings['payment_term'] . ' days')),
            'company' => $company_data,
            'items' => $this->get_order_items(),
            'total' => $this->order->get_total(),
            'currency' => $this->order->get_currency(),
            'vat' => $this->calculate_vat(),
            'order_id' => $this->order->get_id()
        ];
    }

    private function get_next_invoice_number() {
        $last_number = get_option('anaf_facturare_last_invoice_number', 0);
        $next_number = $last_number + 1;
        update_option('anaf_facturare_last_invoice_number', $next_number);
        return $next_number;
    }

    private function get_order_items() {
        $items = [];
        foreach ($this->order->get_items() as $item) {
            $items[] = [
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'unit_price' => $item->get_total() / $item->get_quantity(),
                'total' => $item->get_total(),
                'vat' => $this->calculate_item_vat($item)
            ];
        }
        return $items;
    }

    private function calculate_vat() {
        return $this->order->get_total_tax();
    }

    private function calculate_item_vat($item) {
        return $item->get_total_tax();
    }

    private function create_invoice($data) {
        $invoice_number = sprintf(
            '%s%06d',
            $data['series'],
            $data['number']
        );

        $post_data = [
            'post_title' => $invoice_number,
            'post_type' => 'anaf_invoice',
            'post_status' => 'publish',
            'meta_input' => [
                '_invoice_data' => $data,
                '_order_id' => $data['order_id']
            ]
        ];

        return wp_insert_post($post_data);
    }

    private function generate_efactura_xml($data) {
        // Implementare conform specificațiilor e-Factura
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"></Invoice>');
        
        // Adaugă elementele conform specificației
        $this->add_xml_elements($xml, $data);
        
        return $xml->asXML();
    }

    private function add_xml_elements($xml, $data) {
        // Implementează structura XML conform specificațiilor ANAF
        // Aceasta este o versiune simplificată
        $xml->addChild('ID', $data['series'] . $data['number']);
        $xml->addChild('IssueDate', date('Y-m-d', strtotime($data['date'])));
        $xml->addChild('DueDate', $data['due_date']);
        
        $supplier = $xml->addChild('AccountingSupplierParty');
        $this->add_party_elements($supplier, $this->settings['company_info']);
        
        $customer = $xml->addChild('AccountingCustomerParty');
        $this->add_party_elements($customer, $data['company']);
        
        $this->add_invoice_lines($xml, $data['items']);
        
        $this->add_totals($xml, $data);
    }

    private function save_invoice_xml($invoice_id, $xml) {
        $upload_dir = wp_upload_dir();
        $invoice_dir = $upload_dir['basedir'] . '/efacturi';
        
        if (!file_exists($invoice_dir)) {
            wp_mkdir_p($invoice_dir);
        }
        
        $filename = sprintf('factura_%d.xml', $invoice_id);
        $filepath = $invoice_dir . '/' . $filename;
        
        file_put_contents($filepath, $xml);
        update_post_meta($invoice_id, '_efactura_xml_path', $filepath);
    }

    private function update_order_meta($invoice_id) {
        update_post_meta($this->order->get_id(), '_anaf_invoice_id', $invoice_id);
        $this->order->add_order_note(
            sprintf(
                __('Factura %s a fost generată și transmisă către ANAF', 'anaf-facturare'),
                get_the_title($invoice_id)
            )
        );
    }
}
