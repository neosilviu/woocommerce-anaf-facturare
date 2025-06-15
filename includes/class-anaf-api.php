<?php
namespace ANAF_Facturare;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once(ABSPATH . 'wp-includes/class-wp-error.php');
require_once(ABSPATH . 'wp-includes/http.php');
require_once(ABSPATH . 'wp-includes/formatting.php');

use WP_Error;

/**
 * ANAF API Integration Class
 */
class ANAF_API {
    /**
     * ANAF API endpoint URL
     */
    const API_URL = 'https://webservicesp.anaf.ro/PlatitorTvaRest/api/v9/ws/tva';

    /**
     * Get company details by CUI
     *
     * @param string $cui Company CUI (without RO prefix)
     * @return array|WP_Error Company details or error
     */
    public function get_company_details($cui) {
        // Remove RO prefix if present
        $cui = preg_replace('/^RO/', '', trim($cui));

        // Format date as required by ANAF API (current date)
        $date = current_time('Y-m-d');

        // Prepare request data
        $request_data = json_encode([
            [
                'cui' => $cui,
                'data' => $date
            ]
        ]);

        // Make API request
        $response = wp_remote_post(self::API_URL, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => $request_data,
            'timeout' => 15
        ]);

        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Check if we got valid data
        if (!$data || !isset($data[0]) || isset($data[0]['cod']) && $data[0]['cod'] === 404) {
            return new WP_Error('invalid_cui', __('Invalid CUI or company not found', 'anaf-facturare'));
        }

        // Format response data
        $company_data = $data[0];
        return [
            'company_name' => $company_data['denumire'] ?? '',
            'registration_number' => $company_data['nrRegCom'] ?? '',
            'address' => [
                'street' => $company_data['adresa'] ?? '',
                'city' => $this->extract_city($company_data['adresa_domiciliu_fiscal'] ?? ''),
                'county' => $this->extract_county($company_data['adresa_domiciliu_fiscal'] ?? ''),
                'postcode' => $company_data['cod_Postal'] ?? ''
            ],
            'vat_registered' => ($company_data['scpTVA'] ?? false) === true,
            'active' => ($company_data['statusInactivi'] ?? 'NO') === 'NO'
        ];
    }

    /**
     * Extract city from ANAF address string
     *
     * @param string $address Full address from ANAF
     * @return string City name
     */
    private function extract_city($address) {
        // Common city prefixes in Romanian addresses
        $prefixes = ['MUNICIPIUL', 'ORAS', 'COMUNA', 'SAT'];
        $address = strtoupper($address);

        foreach ($prefixes as $prefix) {
            if (strpos($address, $prefix) !== false) {
                $parts = explode($prefix, $address, 2);
                if (isset($parts[1])) {
                    $city_part = trim($parts[1]);
                    // Get first word after prefix
                    $city = explode(' ', $city_part)[0];
                    return trim($city);
                }
            }
        }

        return '';
    }

    /**
     * Extract county from ANAF address string
     *
     * @param string $address Full address from ANAF
     * @return string County name
     */
    private function extract_county($address) {
        // Match the county pattern (JUD. NAME)
        if (preg_match('/JUD\.\s*([^,\n]+)/', strtoupper($address), $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
}
