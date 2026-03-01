<?php
/**
 * Admin Controller
 *
 * @package    MultilatChatwoot
 * @author     Multilat <https://multilat.xyz>
 * @copyright  Copyright (c) Multilat
 */

namespace MultilatChatwoot\Admin;

if (!defined('WHMCS')) {
    die('Access Denied');
}

use WHMCS\Database\Capsule;

class Controller
{
    /**
     * Module Variables
     *
     * @var array
     */
    protected $vars;

    /**
     * Module Link
     *
     * @var string
     */
    protected $moduleLink;

    /**
     * Template Path
     *
     * @var string
     */
    protected $templatePath;

    /**
     * Constructor
     *
     * @param array $vars
     */
    public function __construct(array $vars)
    {
        $this->vars = $vars;
        $this->moduleLink = $vars['modulelink'];
        $this->templatePath = __DIR__ . '/../../templates/admin/';
    }

    /**
     * Generate CSRF Token
     *
     * @return string
     */
    public function generateCsrfToken()
    {
        if (!isset($_SESSION['mc_csrf_token'])) {
            $_SESSION['mc_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['mc_csrf_token'];
    }

    /**
     * Verify CSRF Token
     *
     * @return bool
     */
    protected function verifyCsrfToken()
    {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token)) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        $sessionToken = $_SESSION['mc_csrf_token'] ?? '';

        if (empty($token) || empty($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Get CSRF Token Field HTML
     *
     * @return string
     */
    public function getCsrfTokenField()
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($this->generateCsrfToken()) . '">';
    }

    /**
     * Dispatch Action
     *
     * @param string $action
     * @return void
     */
    public function dispatch($action)
    {
        // Verify CSRF Token Only For Our Form/AJAX Submissions
        // Skip For WHMCS Internal POST Redirects (e.g. Password Confirmation)
        $hasCsrfToken = !empty($_POST['csrf_token']) || !empty($_SERVER['HTTP_X_CSRF_TOKEN']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasCsrfToken) {
            if (!$this->verifyCsrfToken()) {
                // Token Stale (Session Expired) — Trigger WHMCS Native Password Confirmation
                unset($_SESSION['mc_csrf_token']);
                unset($_SESSION['AuthConfirmationTimestamp']);
                echo '<script>window.location.href = "' . addslashes($this->moduleLink) . '";</script>';
                return;
            }
        }

        // Map Actions To Methods
        $methods = [
            'general'          => 'general',
            'attributes'       => 'attributes',
            'sync_attributes'  => 'syncAttributes',
            'fetch_attributes' => 'fetchAttributes',
        ];

        $method = isset($methods[$action]) ? $methods[$action] : 'general';

        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            $this->general();
        }
    }

    /**
     * Render Template
     *
     * @param string $template
     * @param array $data
     * @return void
     */
    protected function render($template, array $data = [])
    {
        $data['moduleLink'] = $this->moduleLink;
        $data['version'] = $this->vars['version'] ?? '1.0.1';
        $data['csrfToken'] = $this->generateCsrfToken();
        $data['csrfTokenField'] = $this->getCsrfTokenField();

        extract($data, EXTR_SKIP);

        $templateFile = $this->templatePath . $template . '.php';

        if (file_exists($templateFile)) {
            include $this->templatePath . 'includes/header.php';
            include $templateFile;
            include $this->templatePath . 'includes/footer.php';
        } else {
            echo '<div class="alert alert-danger">Template Not Found: ' . htmlspecialchars($template) . '</div>';
        }
    }

    /**
     * Get Module Settings
     *
     * @return array
     */
    protected function getSettings()
    {
        return \MultilatChatwoot_get_settings();
    }

    /**
     * Save Module Setting
     *
     * @param string $setting
     * @param string $value
     * @return void
     */
    protected function saveSetting($setting, $value)
    {
        $exists = Capsule::table('tbladdonmodules')
            ->where('module', 'MultilatChatwoot')
            ->where('setting', $setting)
            ->exists();

        if ($exists) {
            Capsule::table('tbladdonmodules')
                ->where('module', 'MultilatChatwoot')
                ->where('setting', $setting)
                ->update(['value' => $value]);
        } else {
            Capsule::table('tbladdonmodules')->insert([
                'module'  => 'MultilatChatwoot',
                'setting' => $setting,
                'value'   => $value,
            ]);
        }
    }

    /**
     * General Settings Page
     *
     * @return void
     */
    protected function general()
    {
        $message = '';
        $messageType = 'success';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general'])) {
            $baseUrl = rtrim(trim($_POST['base_url'] ?? ''), '/');

            if (!empty($baseUrl) && (!filter_var($baseUrl, FILTER_VALIDATE_URL) || strpos($baseUrl, 'https://') !== 0)) {
                $message = 'Base URL Must Be A Valid HTTPS URL';
                $messageType = 'danger';
            } else {
                $settingsToSave = [
                    'enable_widget'  => isset($_POST['enable_widget']) ? 'on' : '',
                    'base_url'       => $baseUrl,
                    'website_token'  => trim($_POST['website_token'] ?? ''),
                    'dark_mode'      => isset($_POST['dark_mode']) ? 'auto' : 'light',
                    'defer_load'     => isset($_POST['defer_load']) ? 'on' : '',
                    'hmac_token'     => trim($_POST['hmac_token'] ?? ''),
                    // Conversation Attributes
                    'conv_attr_source'       => isset($_POST['conv_attr_source']) ? 'on' : '',
                    'conv_attr_current_page' => isset($_POST['conv_attr_current_page']) ? 'on' : '',
                ];

                foreach ($settingsToSave as $setting => $value) {
                    $this->saveSetting($setting, $value);
                }

                $message = 'Settings Saved Successfully!';
                logActivity('Multilat Chatwoot: General Settings Updated');
            }
        }

        $settings = $this->getSettings();

        $this->render('general', [
            'pageTitle'   => 'General',
            'message'     => $message,
            'messageType' => $messageType,
            'settings'    => $settings,
        ]);
    }

    /**
     * Client Attributes Page
     *
     * @return void
     */
    protected function attributes()
    {
        $message = '';
        $messageType = 'success';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attributes'])) {
            $settingsToSave = [
                'enable_identification'  => isset($_POST['enable_identification']) ? 'on' : '',
                'api_access_token'       => trim($_POST['api_access_token'] ?? ''),
                'account_id'             => preg_replace('/[^0-9]/', '', $_POST['account_id'] ?? ''),
                // Personal
                'attr_client_id'         => isset($_POST['attr_client_id']) ? 'on' : '',
                'attr_name'              => isset($_POST['attr_name']) ? 'on' : '',
                'attr_email'             => isset($_POST['attr_email']) ? 'on' : '',
                'attr_ph'                => isset($_POST['attr_ph']) ? 'on' : '',
                'attr_company'           => isset($_POST['attr_company']) ? 'on' : '',
                'attr_billing_address'   => isset($_POST['attr_billing_address']) ? 'on' : '',
                // Billing
                'attr_total_paid'        => isset($_POST['attr_total_paid']) ? 'on' : '',
                'attr_total_due'         => isset($_POST['attr_total_due']) ? 'on' : '',
                'attr_credit_balance'    => isset($_POST['attr_credit_balance']) ? 'on' : '',
                'attr_overdue_invoices'  => isset($_POST['attr_overdue_invoices']) ? 'on' : '',
                // Services
                'attr_active_products'   => isset($_POST['attr_active_products']) ? 'on' : '',
                'attr_suspended_products' => isset($_POST['attr_suspended_products']) ? 'on' : '',
                'attr_active_domains'    => isset($_POST['attr_active_domains']) ? 'on' : '',
                'attr_active_tickets'    => isset($_POST['attr_active_tickets']) ? 'on' : '',
                'attr_expiring_products' => isset($_POST['attr_expiring_products']) ? 'on' : '',
                'attr_expiring_domains'  => isset($_POST['attr_expiring_domains']) ? 'on' : '',
                'attr_suspended_product_names' => isset($_POST['attr_suspended_product_names']) ? 'on' : '',
            ];

            foreach ($settingsToSave as $setting => $value) {
                $this->saveSetting($setting, $value);
            }

            // Build Attribute Overrides JSON
            $overrides = [];
            $syncableKeys = [
                'client_id', 'billing_address',
                'total_paid', 'total_due', 'credit_balance', 'overdue_invoices',
                'active_products', 'suspended_products', 'active_domains', 'active_tickets',
                'expiring_products', 'expiring_domains', 'suspended_product_names',
                'source', 'current_page',
            ];

            foreach ($syncableKeys as $attrKey) {
                $customKey = trim($_POST['override_key_' . $attrKey] ?? '');
                if (!empty($customKey) && !preg_match('/^[a-z0-9_]+$/', $customKey)) {
                    $customKey = '';
                }
                $customDisplay = trim($_POST['override_display_' . $attrKey] ?? '');
                if (!empty($customKey) || !empty($customDisplay)) {
                    $override = [];
                    if (!empty($customKey)) $override['key'] = $customKey;
                    if (!empty($customDisplay)) $override['display'] = $customDisplay;
                    $overrides[$attrKey] = $override;
                }
            }
            $this->saveSetting('attribute_overrides', json_encode($overrides));

            $message = 'Client Attributes Saved Successfully!';
            logActivity('Multilat Chatwoot: Client Attributes Updated');
        }

        $settings = $this->getSettings();

        $this->render('attributes', [
            'pageTitle'   => 'Client Attributes',
            'message'     => $message,
            'messageType' => $messageType,
            'settings'    => $settings,
        ]);
    }

    /**
     * Make API Request To Chatwoot
     *
     * @param string $url
     * @param string $method
     * @param string $apiToken
     * @param string|null $payload
     * @return array
     */
    private function makeApiRequest($url, $method, $apiToken, $payload = null)
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'api_access_token: ' . $apiToken,
            ],
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $payload;
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
    }

    /**
     * Sync Custom Attribute Definitions To Chatwoot via API
     *
     * @return void
     */
    protected function syncAttributes()
    {
        // Clear Output Buffers To Prevent WHMCS Admin HTML Wrapping
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');

        $settings = $this->getSettings();

        $baseUrl = rtrim($settings['base_url'] ?? '', '/');
        $apiToken = $settings['api_access_token'] ?? '';
        $accountId = $settings['account_id'] ?? '';

        if (empty($baseUrl) || empty($apiToken) || empty($accountId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing API Credentials. Please Configure Base URL and Account ID In General Settings, and API Access Token In Client Attributes.',
            ]);
            exit;
        }

        // Load Attribute Overrides
        $overrides = json_decode($settings['attribute_overrides'] ?? '{}', true) ?: [];

        // Define All Syncable Attributes
        $attributeDefinitions = [
            // Contact Attributes
            'attr_client_id'         => ['key' => 'client_id',         'name' => 'Client ID',              'type' => 'number', 'model' => 'contact_attribute',      'desc' => 'WHMCS Client ID'],
            'attr_billing_address'   => ['key' => 'billing_address',   'name' => 'Billing Address',        'type' => 'text',   'model' => 'contact_attribute',      'desc' => 'Full Billing Address'],
            'attr_total_paid'        => ['key' => 'total_paid',        'name' => 'Total Paid',             'type' => 'text',   'model' => 'contact_attribute',      'desc' => 'Total Amount Paid'],
            'attr_total_due'         => ['key' => 'total_due',         'name' => 'Total Due',              'type' => 'text',   'model' => 'contact_attribute',      'desc' => 'Total Amount Due'],
            'attr_credit_balance'    => ['key' => 'credit_balance',    'name' => 'Credit Balance',         'type' => 'text',   'model' => 'contact_attribute',      'desc' => 'Account Credit Balance'],
            'attr_overdue_invoices'  => ['key' => 'overdue_invoices',  'name' => 'Overdue Invoices',       'type' => 'number', 'model' => 'contact_attribute',      'desc' => 'Number of Overdue Invoices'],
            'attr_active_products'   => ['key' => 'active_products',   'name' => 'Active Products',        'type' => 'number', 'model' => 'contact_attribute',      'desc' => 'Number of Active Products'],
            'attr_suspended_products' => ['key' => 'suspended_products', 'name' => 'Suspended Products',   'type' => 'number', 'model' => 'contact_attribute',      'desc' => 'Number of Suspended Products'],
            'attr_active_domains'    => ['key' => 'active_domains',    'name' => 'Active Domains',         'type' => 'number', 'model' => 'contact_attribute',      'desc' => 'Number of Active Domains'],
            'attr_active_tickets'    => ['key' => 'active_tickets',    'name' => 'Active Tickets',         'type' => 'number', 'model' => 'contact_attribute',      'desc' => 'Number of Active Tickets'],
            'attr_expiring_products' => ['key' => 'expiring_products', 'name' => 'Expiring Products',      'type' => 'text',   'model' => 'contact_attribute',      'desc' => 'Products Expiring Within 30 Days'],
            'attr_expiring_domains'  => ['key' => 'expiring_domains',  'name' => 'Expiring Domains',       'type' => 'text',   'model' => 'contact_attribute',      'desc' => 'Domains Expiring Within 30 Days'],
            'attr_suspended_product_names' => ['key' => 'suspended_product_names', 'name' => 'Suspended Product Names', 'type' => 'text', 'model' => 'contact_attribute', 'desc' => 'Names of Suspended Products'],
            // Conversation Attributes
            'conv_attr_source'       => ['key' => 'source',            'name' => 'Source',                 'type' => 'text',   'model' => 'conversation_attribute', 'desc' => 'Conversation Source'],
            'conv_attr_current_page' => ['key' => 'current_page',      'name' => 'Current Page',           'type' => 'link',   'model' => 'conversation_attribute', 'desc' => 'Page The Client Is Viewing'],
        ];

        // Filter To Only Enabled Attributes
        $toSync = [];
        foreach ($attributeDefinitions as $settingKey => $def) {
            if (($settings[$settingKey] ?? '') === 'on') {
                // Store Original Key Before Overrides
                $def['originalKey'] = $def['key'];
                // Apply Overrides To Key and Display Name
                $defaultKey = $def['key'];
                if (!empty($overrides[$defaultKey]['key'])) {
                    $def['key'] = $overrides[$defaultKey]['key'];
                }
                if (!empty($overrides[$defaultKey]['display'])) {
                    $def['name'] = $overrides[$defaultKey]['display'];
                }
                $toSync[$settingKey] = $def;
            }
        }

        if (empty($toSync)) {
            echo json_encode([
                'success' => false,
                'message' => 'No Attributes Are Enabled. Please Enable At Least One Attribute Before Syncing.',
            ]);
            exit;
        }

        $endpoint = $baseUrl . '/api/v1/accounts/' . $accountId . '/custom_attribute_definitions';
        $results = [];
        $summary = ['total' => count($toSync), 'created' => 0, 'existed' => 0, 'failed' => 0];

        foreach ($toSync as $settingKey => $def) {
            $payload = json_encode([
                'custom_attribute_definition' => [
                    'attribute_display_name' => $def['name'],
                    'attribute_key'          => $def['key'],
                    'attribute_display_type' => $def['type'],
                    'attribute_model'        => $def['model'],
                    'attribute_description'  => $def['desc'],
                ],
            ]);

            $result = $this->makeApiRequest($endpoint, 'POST', $apiToken, $payload);
            $response = $result['response'];
            $httpCode = $result['httpCode'];
            $curlError = $result['error'];

            $origKey = $def['originalKey'];

            if ($curlError) {
                $results[] = ['key' => $def['key'], 'originalKey' => $origKey, 'status' => 'failed', 'detail' => 'Connection Error: ' . $curlError];
                $summary['failed']++;
                continue;
            }

            if ($httpCode === 401) {
                $results[] = ['key' => $def['key'], 'originalKey' => $origKey, 'status' => 'failed', 'detail' => 'Authentication Failed — Invalid API Token'];
                $summary['failed']++;
                // All Subsequent Requests Will Also Fail
                foreach (array_slice(array_keys($toSync), array_search($settingKey, array_keys($toSync)) + 1) as $skippedKey) {
                    $results[] = ['key' => $toSync[$skippedKey]['key'], 'originalKey' => $toSync[$skippedKey]['originalKey'], 'status' => 'skipped', 'detail' => 'Skipped Due To Auth Failure'];
                    $summary['failed']++;
                }
                break;
            }

            if ($httpCode === 200 || $httpCode === 201) {
                $results[] = ['key' => $def['key'], 'originalKey' => $origKey, 'status' => 'created', 'detail' => 'Created Successfully'];
                $summary['created']++;
            } elseif ($httpCode === 422) {
                $results[] = ['key' => $def['key'], 'originalKey' => $origKey, 'status' => 'existed', 'detail' => 'Already Exists'];
                $summary['existed']++;
            } else {
                $detail = 'HTTP ' . $httpCode;
                $decoded = json_decode($response, true);
                if (isset($decoded['message'])) {
                    $detail .= ' — ' . $decoded['message'];
                }
                $results[] = ['key' => $def['key'], 'originalKey' => $origKey, 'status' => 'failed', 'detail' => $detail];
                $summary['failed']++;
            }
        }

        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'results' => $results,
        ]);
        exit;
    }

    /**
     * Fetch Custom Attribute Definitions From Chatwoot Instance
     *
     * @return void
     */
    protected function fetchAttributes()
    {
        // Clear Output Buffers To Prevent WHMCS Admin HTML Wrapping
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');

        $settings = $this->getSettings();

        $baseUrl = rtrim($settings['base_url'] ?? '', '/');
        $apiToken = $settings['api_access_token'] ?? '';
        $accountId = $settings['account_id'] ?? '';

        if (empty($baseUrl) || empty($apiToken) || empty($accountId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing API Credentials. Please Configure Base URL In General Settings, and Account ID and API Access Token Above.',
            ]);
            exit;
        }

        // Fetch Both Contact and Conversation Attributes
        $allAttributes = [];

        foreach (['contact_attribute', 'conversation_attribute'] as $model) {
            $endpoint = $baseUrl . '/api/v1/accounts/' . $accountId . '/custom_attribute_definitions?attribute_model=' . $model;

            $result = $this->makeApiRequest($endpoint, 'GET', $apiToken);
            $response = $result['response'];
            $httpCode = $result['httpCode'];
            $curlError = $result['error'];

            if ($curlError) {
                echo json_encode(['success' => false, 'message' => 'Connection Error: ' . $curlError]);
                exit;
            }

            if ($httpCode === 401) {
                echo json_encode(['success' => false, 'message' => 'Authentication Failed — Invalid API Token']);
                exit;
            }

            if ($httpCode !== 200) {
                $detail = 'HTTP ' . $httpCode;
                $decoded = json_decode($response, true);
                if (isset($decoded['message'])) {
                    $detail .= ' — ' . $decoded['message'];
                }
                echo json_encode(['success' => false, 'message' => $detail]);
                exit;
            }

            $data = json_decode($response, true);
            if (!is_array($data)) {
                echo json_encode(['success' => false, 'message' => 'Invalid Response From Chatwoot']);
                exit;
            }

            $modelLabel = $model === 'contact_attribute' ? 'Contact' : 'Conversation';

            foreach ($data as $attr) {
                $allAttributes[] = [
                    'key'          => $attr['attribute_key'] ?? '',
                    'display_name' => $attr['attribute_display_name'] ?? '',
                    'type'         => $attr['attribute_display_type'] ?? '',
                    'model'        => $modelLabel,
                    'description'  => $attr['attribute_description'] ?? '',
                ];
            }
        }

        echo json_encode([
            'success'    => true,
            'attributes' => $allAttributes,
            'total'      => count($allAttributes),
        ]);
        exit;
    }
}
