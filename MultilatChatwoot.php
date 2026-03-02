<?php
/**
 * Multilat Chatwoot
 *
 * Adds Chatwoot Live Chat Widget To WHMCS With Client Identity,
 * Billing Attributes, and HMAC Identity Verification Support
 *
 * @package    MultilatChatwoot
 * @author     Multilat <https://multilat.xyz>
 * @copyright  Copyright (c) Multilat
 * @version    1.2.2
 */

if (!defined('WHMCS')) {
    die('Access Denied');
}

use WHMCS\Database\Capsule;

/**
 * Module Configuration
 *
 * @return array
 */
function MultilatChatwoot_config()
{
    return [
        'name' => 'Multilat Chatwoot',
        'description' => 'Adds Chatwoot Live Chat Widget With Client Identity and Billing Attributes',
        'version' => '1.2.2',
        'author' => '<a href="https://multilat.xyz" target="_blank"><img src="../modules/addons/MultilatChatwoot/assets/images/author-logo.png" alt="Multilat" style="max-height: 14px; vertical-align: middle;"></a>',
        'fields' => [],
    ];
}

/**
 * Get Installation Paths
 *
 * @return array
 */
function MultilatChatwoot_get_paths()
{
    return [
        'hook_source' => __DIR__ . '/includes/hooks/multilatchatwoot_widget.php',
        'hook_dest' => ROOTDIR . '/includes/hooks/multilatchatwoot_widget.php',
    ];
}

/**
 * Get Module Settings
 *
 * @return array
 */
function MultilatChatwoot_get_settings()
{
    return Capsule::table('tbladdonmodules')
        ->where('module', 'MultilatChatwoot')
        ->pluck('value', 'setting')
        ->toArray();
}

/**
 * Get Default Settings
 *
 * @return array
 */
function MultilatChatwoot_get_defaults()
{
    return [
        'enable_widget'          => '',
        'base_url'               => '',
        'website_token'          => '',
        'api_access_token'       => '',
        'account_id'             => '',
        'dark_mode'              => 'light',
        'defer_load'             => 'on',
        'locale'                 => '',
        'widget_type'            => 'standard',
        'position'               => 'right',
        'launcher_text'          => '',
        'hmac_token'             => '',
        'enable_identification'  => 'on',
        'attr_client_id'         => 'on',
        'attr_name'              => 'on',
        'attr_email'             => 'on',
        'attr_ph'                => 'on',
        'attr_company'           => 'on',
        'attr_billing_address'   => '',
        'attr_total_paid'        => '',
        'attr_total_due'         => '',
        'attr_credit_balance'    => '',
        'attr_overdue_invoices'  => '',
        'attr_active_products'   => '',
        'attr_suspended_products' => '',
        'attr_active_domains'    => '',
        'attr_active_tickets'    => '',
        'attr_expiring_products'      => '',
        'attr_expiring_domains'       => '',
        'attr_suspended_product_names' => '',
        'conv_attr_source'       => 'on',
        'conv_attr_current_page' => 'on',
        'attribute_overrides'    => '{}',
    ];
}

/**
 * Provision Default Settings (Insert Missing Settings Only)
 *
 * @return void
 */
function MultilatChatwoot_provision_defaults()
{
    $defaults = MultilatChatwoot_get_defaults();

    foreach ($defaults as $setting => $value) {
        $exists = Capsule::table('tbladdonmodules')
            ->where('module', 'MultilatChatwoot')
            ->where('setting', $setting)
            ->exists();

        if (!$exists) {
            Capsule::table('tbladdonmodules')->insert([
                'module'  => 'MultilatChatwoot',
                'setting' => $setting,
                'value'   => $value,
            ]);
        }
    }
}

/**
 * Backup Settings To tblconfiguration (Survives Deactivation)
 *
 * @return void
 */
function MultilatChatwoot_backup_settings()
{
    try {
        $settings = Capsule::table('tbladdonmodules')
            ->where('module', 'MultilatChatwoot')
            ->pluck('value', 'setting')
            ->toArray();

        if (empty($settings)) {
            return;
        }

        $json = json_encode($settings);
        $exists = Capsule::table('tblconfiguration')
            ->where('setting', 'MultilatChatwootBackup')
            ->exists();

        if ($exists) {
            Capsule::table('tblconfiguration')
                ->where('setting', 'MultilatChatwootBackup')
                ->update(['value' => $json]);
        } else {
            Capsule::table('tblconfiguration')->insert([
                'setting' => 'MultilatChatwootBackup',
                'value'   => $json,
            ]);
        }
    } catch (\Exception $e) {
        logActivity('Multilat Chatwoot: Settings Backup Failed — ' . $e->getMessage());
    }
}

/**
 * Restore Settings From tblconfiguration Backup
 *
 * @return void
 */
function MultilatChatwoot_restore_settings()
{
    try {
        $row = Capsule::table('tblconfiguration')
            ->where('setting', 'MultilatChatwootBackup')
            ->first();

        if (!$row || empty($row->value)) {
            return;
        }

        $settings = json_decode($row->value, true);
        if (!is_array($settings) || empty($settings)) {
            return;
        }

        foreach ($settings as $setting => $value) {
            $exists = Capsule::table('tbladdonmodules')
                ->where('module', 'MultilatChatwoot')
                ->where('setting', $setting)
                ->exists();

            if (!$exists) {
                Capsule::table('tbladdonmodules')->insert([
                    'module'  => 'MultilatChatwoot',
                    'setting' => $setting,
                    'value'   => $value,
                ]);
            }
        }

        // Remove Backup After Successful Restore
        Capsule::table('tblconfiguration')
            ->where('setting', 'MultilatChatwootBackup')
            ->delete();

    } catch (\Exception $e) {
        logActivity('Multilat Chatwoot: Settings Restore Failed — ' . $e->getMessage());
    }
}

/**
 * Module Activation
 *
 * @return array
 */
function MultilatChatwoot_activate()
{
    $paths = MultilatChatwoot_get_paths();

    try {
        // Verify Source Files
        if (!file_exists($paths['hook_source'])) {
            throw new \Exception('Hook Source File Not Found');
        }

        if (!is_writable(dirname($paths['hook_dest']))) {
            throw new \Exception('WHMCS Hooks Directory Is Not Writable');
        }

        // Copy Widget Hook
        if (!copy($paths['hook_source'], $paths['hook_dest'])) {
            throw new \Exception('Could Not Copy Widget Hook');
        }
        @chmod($paths['hook_dest'], 0644);

        // Restore Saved Settings From Previous Deactivation (If Any)
        MultilatChatwoot_restore_settings();

        // Provision Defaults For Any Missing Settings
        MultilatChatwoot_provision_defaults();

        logActivity('Multilat Chatwoot: Module Activated Successfully');

        return ['status' => 'success', 'description' => 'Module Activated Successfully'];

    } catch (\Exception $e) {
        logActivity('Multilat Chatwoot Activation Error: ' . $e->getMessage());
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

/**
 * Module Deactivation
 *
 * @return array
 */
function MultilatChatwoot_deactivate()
{
    // Backup Settings To tblconfiguration Before WHMCS Wipes tbladdonmodules
    MultilatChatwoot_backup_settings();

    $paths = MultilatChatwoot_get_paths();

    if (file_exists($paths['hook_dest']) && !@unlink($paths['hook_dest'])) {
        logActivity('Multilat Chatwoot: Could Not Remove Widget Hook');
        return ['status' => 'warning', 'description' => 'Could Not Remove Widget Hook File'];
    }

    logActivity('Multilat Chatwoot: Module Deactivated');

    return ['status' => 'success', 'description' => 'Module Deactivated Successfully'];
}

/**
 * Module Upgrade
 *
 * @param array $vars
 * @return void
 */
function MultilatChatwoot_upgrade($vars)
{
    $paths = MultilatChatwoot_get_paths();

    try {
        if (file_exists($paths['hook_source']) && is_writable(dirname($paths['hook_dest']))) {
            copy($paths['hook_source'], $paths['hook_dest']);
            @chmod($paths['hook_dest'], 0644);
        }

        // Provision Any New Settings Added In This Version
        MultilatChatwoot_provision_defaults();

        logActivity('Multilat Chatwoot: Module Upgraded To v' . $vars['version']);

    } catch (\Exception $e) {
        logActivity('Multilat Chatwoot Upgrade Error: ' . $e->getMessage());
    }
}

/**
 * Admin Area Output
 *
 * @param array $vars
 * @return array
 */
function MultilatChatwoot_output($vars)
{
    require_once __DIR__ . '/lib/Admin/Controller.php';

    $controller = new \MultilatChatwoot\Admin\Controller($vars);
    $action = $_GET['action'] ?? 'general';
    $controller->dispatch($action);

    return [];
}
