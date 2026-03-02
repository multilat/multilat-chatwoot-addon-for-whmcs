<?php
/**
 * Multilat Chatwoot Widget Hook
 *
 * Injects Chatwoot Live Chat Widget Into WHMCS Client Area
 * With Client Identity, Custom Attributes, and HMAC Support
 *
 * @package    MultilatChatwoot
 */

if (!defined('WHMCS')) {
    die('Access Denied');
}

use WHMCS\Database\Capsule;

add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    try {
        // Load All Module Settings In One Query
        $settings = Capsule::table('tbladdonmodules')
            ->where('module', 'MultilatChatwoot')
            ->pluck('value', 'setting')
            ->toArray();
    } catch (\Exception $e) {
        return '';
    }

    // Check If Widget Is Enabled
    if (empty($settings['enable_widget']) || $settings['enable_widget'] !== 'on') {
        return '';
    }

    $baseUrl = rtrim($settings['base_url'] ?? '', '/');
    $websiteToken = $settings['website_token'] ?? '';

    if (empty($baseUrl) || empty($websiteToken)) {
        return '';
    }

    $darkMode = $settings['dark_mode'] ?? 'light';
    $deferLoad = ($settings['defer_load'] ?? 'on') === 'on';
    $hmacToken = $settings['hmac_token'] ?? '';

    // Load Attribute Overrides
    $overrides = json_decode($settings['attribute_overrides'] ?? '{}', true) ?: [];

    // Key Resolution Helper
    $resolveKey = function ($defaultKey) use ($overrides) {
        return (!empty($overrides[$defaultKey]['key'])) ? $overrides[$defaultKey]['key'] : $defaultKey;
    };

    // Build Post-Ready Script (Runs After Chatwoot SDK Is Ready)
    $postReadyScript = '';
    $isLoggedIn = !empty($vars['loggedin']);
    $identificationEnabled = ($settings['enable_identification'] ?? 'on') === 'on';

    if ($isLoggedIn && $identificationEnabled) {
        $client = $vars['clientsdetails'] ?? [];
        $clientId = $client['id'] ?? $client['userid'] ?? 0;
        $email = $client['email'] ?? '';
        $firstName = $client['firstname'] ?? '';
        $lastName = $client['lastname'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        // Normalize Phone To E.164 Format (+CCNNNNNNNNNN)
        // phonenumberformatted Includes Country Calling Code: "+CC.NNNN"
        // Fallback To phonenumber (Local Number Only) If Formatted Not Available
        $phoneFormatted = $client['phonenumberformatted'] ?? '';
        $phone = '';
        if (!empty($phoneFormatted)) {
            $digits = preg_replace('/[^0-9]/', '', $phoneFormatted);
            if (!empty($digits)) {
                $phone = '+' . $digits;
            }
        } elseif (!empty($client['phonenumber'])) {
            $digits = preg_replace('/[^0-9]/', '', $client['phonenumber']);
            if (!empty($digits)) {
                $phone = '+' . $digits;
            }
        }

        // Build setUser Identity Data
        $identifier = $email;
        $userData = [];

        if (($settings['attr_name'] ?? '') === 'on' && !empty($fullName)) {
            $userData['name'] = $fullName;
        }

        if (($settings['attr_email'] ?? '') === 'on' && !empty($email)) {
            $userData['email'] = $email;
        }

        if (($settings['attr_ph'] ?? '') === 'on' && !empty($phone)) {
            $userData['phone_number'] = $phone;
        }

        if (($settings['attr_company'] ?? '') === 'on') {
            $company = $client['companyname'] ?? '';
            if (!empty($company)) {
                $userData['company_name'] = $company;
            }
        }

        // HMAC Identity Verification
        if (!empty($hmacToken) && !empty($identifier)) {
            $userData['identifier_hash'] = hash_hmac('sha256', $identifier, $hmacToken);
        }

        if (!empty($identifier)) {
            $postReadyScript .= 'window.$chatwoot.setUser('
                . json_encode($identifier) . ', '
                . json_encode($userData, JSON_UNESCAPED_UNICODE) . ');' . "\n";
        }

        // Build Custom Attributes
        $customAttrs = [];

        if (($settings['attr_client_id'] ?? '') === 'on' && $clientId) {
            $customAttrs[$resolveKey('client_id')] = (int) $clientId;
        }

        if (($settings['attr_billing_address'] ?? '') === 'on') {
            $parts = [];
            if (!empty($client['address1'])) $parts[] = $client['address1'];
            if (!empty($client['address2'])) $parts[] = $client['address2'];
            $cityStateZip = array_filter([
                $client['city'] ?? '',
                $client['state'] ?? '',
                $client['postcode'] ?? '',
            ]);
            if (!empty($cityStateZip)) $parts[] = implode(', ', $cityStateZip);
            if (!empty($client['country'])) {
                $code = strtoupper($client['country']);
                if (function_exists('locale_get_display_region')) {
                    $name = locale_get_display_region('_' . $code, 'en');
                    $parts[] = !empty($name) ? $name : $code;
                } else {
                    $parts[] = $code;
                }
            }
            $address = implode(', ', $parts);
            if (!empty($address)) {
                $customAttrs[$resolveKey('billing_address')] = $address;
            }
        }

        // Billing Attributes (Only Query If At Least One Is Enabled)
        $needsBilling = (
            ($settings['attr_total_paid'] ?? '') === 'on' ||
            ($settings['attr_total_due'] ?? '') === 'on' ||
            ($settings['attr_credit_balance'] ?? '') === 'on' ||
            ($settings['attr_overdue_invoices'] ?? '') === 'on'
        );

        if ($needsBilling && $clientId) {
            // Fetch Client Currency For Formatting
            $currencyPrefix = '$';
            $currencySuffix = '';
            try {
                $currencyId = $client['currency'] ?? 0;
                $currencyRow = $currencyId
                    ? Capsule::table('tblcurrencies')->where('id', $currencyId)->first()
                    : Capsule::table('tblcurrencies')->where('default', 1)->first();
                if ($currencyRow) {
                    $currencyPrefix = $currencyRow->prefix ?? '';
                    $currencySuffix = $currencyRow->suffix ?? '';
                }
            } catch (\Exception $e) {
                logActivity('Multilat Chatwoot Widget: Currency Query Error — ' . $e->getMessage());
            }

            $formatCurrency = function ($amount) use ($currencyPrefix, $currencySuffix) {
                return $currencyPrefix . number_format(round((float) $amount, 2), 2) . $currencySuffix;
            };

            try {
                if (($settings['attr_total_paid'] ?? '') === 'on') {
                    $totalPaid = Capsule::table('tblinvoices')
                        ->where('userid', $clientId)
                        ->where('status', 'Paid')
                        ->sum('total');
                    $customAttrs[$resolveKey('total_paid')] = $formatCurrency($totalPaid);
                }

                if (($settings['attr_total_due'] ?? '') === 'on') {
                    $totalDue = Capsule::table('tblinvoices')
                        ->where('userid', $clientId)
                        ->where('status', 'Unpaid')
                        ->sum('total');
                    $customAttrs[$resolveKey('total_due')] = $formatCurrency($totalDue);
                }

                if (($settings['attr_overdue_invoices'] ?? '') === 'on') {
                    $customAttrs[$resolveKey('overdue_invoices')] = Capsule::table('tblinvoices')
                        ->where('userid', $clientId)
                        ->where('status', 'Unpaid')
                        ->where('duedate', '<', date('Y-m-d'))
                        ->count();
                }
            } catch (\Exception $e) {
                logActivity('Multilat Chatwoot Widget: Billing Query Error — ' . $e->getMessage());
            }

            if (($settings['attr_credit_balance'] ?? '') === 'on') {
                $customAttrs[$resolveKey('credit_balance')] = $formatCurrency($client['credit'] ?? 0);
            }
        }

        // Service Attributes (Only Query If At Least One Is Enabled)
        $needsServices = (
            ($settings['attr_active_products'] ?? '') === 'on' ||
            ($settings['attr_suspended_products'] ?? '') === 'on' ||
            ($settings['attr_active_domains'] ?? '') === 'on' ||
            ($settings['attr_active_tickets'] ?? '') === 'on' ||
            ($settings['attr_expiring_products'] ?? '') === 'on' ||
            ($settings['attr_expiring_domains'] ?? '') === 'on' ||
            ($settings['attr_suspended_product_names'] ?? '') === 'on'
        );

        if ($needsServices && $clientId) {
            try {
                if (($settings['attr_active_products'] ?? '') === 'on') {
                    $customAttrs[$resolveKey('active_products')] = Capsule::table('tblhosting')
                        ->where('userid', $clientId)
                        ->where('domainstatus', 'Active')
                        ->count();
                }

                if (($settings['attr_suspended_products'] ?? '') === 'on') {
                    $customAttrs[$resolveKey('suspended_products')] = Capsule::table('tblhosting')
                        ->where('userid', $clientId)
                        ->where('domainstatus', 'Suspended')
                        ->count();
                }

                if (($settings['attr_active_domains'] ?? '') === 'on') {
                    $customAttrs[$resolveKey('active_domains')] = Capsule::table('tbldomains')
                        ->where('userid', $clientId)
                        ->where('status', 'Active')
                        ->count();
                }

                if (($settings['attr_active_tickets'] ?? '') === 'on') {
                    $customAttrs[$resolveKey('active_tickets')] = Capsule::table('tbltickets')
                        ->where('userid', $clientId)
                        ->whereIn('status', ['Open', 'Answered', 'Customer-Reply', 'In Progress'])
                        ->count();
                }

                // Expiring Products (30 Days)
                if (($settings['attr_expiring_products'] ?? '') === 'on') {
                    $list = Capsule::table('tblhosting')
                        ->where('userid', $clientId)->where('domainstatus', 'Active')
                        ->where('nextduedate', '>=', date('Y-m-d'))
                        ->where('nextduedate', '<=', date('Y-m-d', strtotime('+30 days')))
                        ->pluck('domain')->filter()->toArray();
                    if (!empty($list)) {
                        $customAttrs[$resolveKey('expiring_products')] = implode(', ', $list);
                    }
                }

                // Expiring Domains (30 Days)
                if (($settings['attr_expiring_domains'] ?? '') === 'on') {
                    $list = Capsule::table('tbldomains')
                        ->where('userid', $clientId)->where('status', 'Active')
                        ->where('expirydate', '>=', date('Y-m-d'))
                        ->where('expirydate', '<=', date('Y-m-d', strtotime('+30 days')))
                        ->pluck('domain')->filter()->toArray();
                    if (!empty($list)) {
                        $customAttrs[$resolveKey('expiring_domains')] = implode(', ', $list);
                    }
                }

                // Suspended Product Names
                if (($settings['attr_suspended_product_names'] ?? '') === 'on') {
                    $list = Capsule::table('tblhosting')
                        ->where('userid', $clientId)->where('domainstatus', 'Suspended')
                        ->pluck('domain')->filter()->toArray();
                    if (!empty($list)) {
                        $customAttrs[$resolveKey('suspended_product_names')] = implode(', ', $list);
                    }
                }
            } catch (\Exception $e) {
                logActivity('Multilat Chatwoot Widget: Service Query Error — ' . $e->getMessage());
            }
        }

        if (!empty($customAttrs)) {
            $postReadyScript .= 'window.$chatwoot.setCustomAttributes('
                . json_encode($customAttrs, JSON_UNESCAPED_UNICODE) . ');' . "\n";
        }
    }

    // Conversation Attributes
    $convAttrs = [];

    if (($settings['conv_attr_source'] ?? '') === 'on') {
        $convAttrs[$resolveKey('source')] = 'whmcs';
    }

    $setConvPage = ($settings['conv_attr_current_page'] ?? '') === 'on';

    if (!empty($convAttrs)) {
        $postReadyScript .= 'window.$chatwoot.setConversationCustomAttributes('
            . json_encode($convAttrs) . ');' . "\n";
    }

    if ($setConvPage) {
        $postReadyScript .= 'window.$chatwoot.setConversationCustomAttributes({' . json_encode($resolveKey('current_page')) . ': window.location.href});' . "\n";
    }

    // Escape Values For JavaScript (json_encode Includes Quotes)
    $baseUrlJs = json_encode($baseUrl);
    $tokenJs = json_encode($websiteToken);
    $darkModeJs = json_encode($darkMode);

    // Build Ready Handler
    $readyHandler = '';
    if (!empty($postReadyScript)) {
        $readyHandler = "\n    window.addEventListener('chatwoot:ready', function() {\n"
            . "      " . trim($postReadyScript) . "\n"
            . "    });";
    }

    // Build Widget Script
    if ($deferLoad) {
        $script = <<<SCRIPT
<script>
(function() {
  function loadChatwoot() {
    window.chatwootSettings = { darkMode: {$darkModeJs} };{$readyHandler}
    var s = document.createElement('script');
    s.src = {$baseUrlJs} + '/packs/js/sdk.js';
    s.defer = true;
    s.onload = function() {
      window.chatwootSDK.run({
        websiteToken: {$tokenJs},
        baseUrl: {$baseUrlJs}
      });
    };
    document.body.appendChild(s);
  }
  if (document.readyState === 'complete') {
    loadChatwoot();
  } else {
    window.addEventListener('load', loadChatwoot);
  }
  document.addEventListener('click', function(e) {
    var el = e.target.closest('.chatbox-toggle, a[href="#chatbox-toggle"]');
    if (el && window.\$chatwoot) {
      e.preventDefault();
      window.\$chatwoot.toggle();
    }
  });
})();
</script>
SCRIPT;
    } else {
        $script = <<<SCRIPT
<script>
(function() {
  window.chatwootSettings = { darkMode: {$darkModeJs} };{$readyHandler}
  var s = document.createElement('script');
  s.src = {$baseUrlJs} + '/packs/js/sdk.js';
  s.async = true;
  s.onload = function() {
    window.chatwootSDK.run({
      websiteToken: {$tokenJs},
      baseUrl: {$baseUrlJs}
    });
  };
  document.head.appendChild(s);
  document.addEventListener('click', function(e) {
    var el = e.target.closest('.chatbox-toggle, a[href="#chatbox-toggle"]');
    if (el && window.\$chatwoot) {
      e.preventDefault();
      window.\$chatwoot.toggle();
    }
  });
})();
</script>
SCRIPT;
    }

    return $script;
});
