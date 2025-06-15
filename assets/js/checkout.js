jQuery(document).ready(function($) {
    // Funcție pentru actualizarea vizibilității câmpurilor
    function updateFieldVisibility() {
        var customerType = $('#billing_customer_type').val();
        var companyFields = $('.billing-company, .billing-cui, .billing-reg-com');
        
        if (customerType === 'company') {
            companyFields.show();
            // Marchează câmpurile ca obligatorii
            $('.billing-company input, .billing-cui input, .billing-reg-com input').prop('required', true);
            // Adaugă asterisc pentru câmpuri obligatorii
            companyFields.find('label').addClass('required');
        } else {
            companyFields.hide();
            // Elimină obligativitatea câmpurilor
            $('.billing-company input, .billing-cui input, .billing-reg-com input').prop('required', false);
            // Elimină asterisc pentru câmpuri obligatorii
            companyFields.find('label').removeClass('required');
        }
    }

    // Ascunde inițial câmpurile pentru companie
    updateFieldVisibility();

    // Actualizează când se schimbă tipul de client
    $('#billing_customer_type').on('change', function() {
        updateFieldVisibility();
    });

    // Validare CUI când se introduce
    $('#billing_cui').on('blur', function() {
        var cui = $(this).val();
        if (cui && $('#billing_customer_type').val() === 'company') {
            $.ajax({
                url: anafCheckout.ajaxurl,
                type: 'POST',
                data: {
                    action: 'validate_cui',
                    nonce: anafCheckout.nonce,
                    cui: cui
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Auto-completează datele companiei
                        $('#billing_company').val(response.data.name);
                        // Poți adăuga și alte câmpuri aici
                    } else {
                        alert('CUI invalid sau companie inactivă');
                    }
                }
            });
        }
    });

    // Formatare automată pentru Registrul Comerțului
    $('#billing_reg_com').on('input', function() {
        var value = $(this).val();
        // Elimină orice caracter care nu e literă, număr sau /
        value = value.replace(/[^A-Z0-9/]/gi, '');
        // Formatează automat (ex: J40/123/2000)
        value = value.replace(/^([A-Z])(\d{1,2})(\d{1,6})(\d{4})$/, '$1$2/$3/$4');
        $(this).val(value.toUpperCase());
    });

    // Fields mapping from ANAF API response to WooCommerce checkout fields
    const FIELDS_MAPPING = {
        'company_name': 'billing_company',
        'registration_number': 'billing_reg_com',
        'address.street': 'billing_address_1',
        'address.city': 'billing_city',
        'address.county': 'billing_state',
        'address.postcode': 'billing_postcode'
    };

    // Helper function to get nested object value by string path
    const getNestedValue = (obj, path) => {
        return path.split('.').reduce((acc, part) => acc && acc[part], obj);
    };

    // Helper function to set field value and trigger change event
    const setFieldValue = (field, value) => {
        $(`#${field}`).val(value).trigger('change');
    };

    // Add loading message to CUI field
    const setLoadingState = (isLoading) => {
        const cuiField = $('#billing_cui');
        if (isLoading) {
            cuiField.addClass('loading');
            cuiField.attr('placeholder', anafFacturare.i18n.loading);
        } else {
            cuiField.removeClass('loading');
            cuiField.attr('placeholder', '');
        }
    };

    // Handle CUI field change with debounce
    let cuiTimeout;
    $('#billing_cui').on('input', function() {
        const cui = $(this).val().trim();
        
        // Clear any pending timeout
        clearTimeout(cuiTimeout);
        
        // Don't lookup if CUI is too short
        if (cui.length < 6) {
            return;
        }

        // Set a timeout to prevent too many requests
        cuiTimeout = setTimeout(function() {
            // Make AJAX request
            $.ajax({
                url: anafFacturare.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'anaf_lookup_company',
                    nonce: anafFacturare.nonce,
                    cui: cui
                },
                beforeSend: function() {
                    setLoadingState(true);
                },
                success: function(response) {
                    if (!response.success) {
                        console.error('ANAF API Error:', response.data.message);
                        // Show error message to user
                        alert(response.data.message || anafFacturare.i18n.error);
                        return;
                    }

                    // Update fields with ANAF data
                    Object.entries(FIELDS_MAPPING).forEach(([apiField, wooField]) => {
                        const value = getNestedValue(response.data, apiField);
                        if (value) {
                            setFieldValue(wooField, value);
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert(anafFacturare.i18n.networkError);
                },
                complete: function() {
                    setLoadingState(false);
                }
            });
        }, 500); // 500ms debounce
    });
});
