<?php
/**
 * Admin Client Attributes Template
 *
 * @package    MultilatChatwoot
 */

if (!defined('WHMCS')) {
    die('Access Denied');
}

$identificationEnabled = ($settings['enable_identification'] ?? 'on') === 'on';
$attributeOverrides = json_decode($settings['attribute_overrides'] ?? '{}', true) ?: [];

// Define Attribute Sections
$sections = [
    'personal' => [
        'label' => 'Personal Information',
        'icon' => 'fa-user',
        'color' => '#3b82f6',
        'attrs' => [
            ['name' => 'attr_client_id', 'code' => 'client_id', 'display' => 'Client ID', 'desc' => 'WHMCS Client ID', 'type' => 'Custom', 'default' => 'on'],
            ['name' => 'attr_name', 'code' => 'name', 'display' => 'Name', 'desc' => 'Full Name (First + Last)', 'type' => 'Identity', 'default' => 'on'],
            ['name' => 'attr_email', 'code' => 'email', 'display' => 'Email', 'desc' => 'Email Address (Also Used As Identifier)', 'type' => 'Identity', 'default' => 'on'],
            ['name' => 'attr_ph', 'code' => 'phone', 'display' => 'Phone Number', 'desc' => 'Phone Number', 'type' => 'Identity', 'default' => 'on'],
            ['name' => 'attr_company', 'code' => 'company', 'display' => 'Company', 'desc' => 'Company Name', 'type' => 'Contact', 'default' => 'on'],
            ['name' => 'attr_billing_address', 'code' => 'billing_address', 'display' => 'Billing Address', 'desc' => 'Full Billing Address', 'type' => 'Custom', 'default' => ''],
        ],
    ],
    'billing' => [
        'label' => 'Billing',
        'icon' => 'fa-file-invoice-dollar',
        'color' => '#f59e0b',
        'attrs' => [
            ['name' => 'attr_total_paid', 'code' => 'total_paid', 'display' => 'Total Paid', 'desc' => 'Total Amount Paid (All Paid Invoices)', 'type' => 'Custom', 'default' => ''],
            ['name' => 'attr_total_due', 'code' => 'total_due', 'display' => 'Total Due', 'desc' => 'Total Amount Due (All Unpaid Invoices)', 'type' => 'Custom', 'default' => ''],
            ['name' => 'attr_credit_balance', 'code' => 'credit_balance', 'display' => 'Credit Balance', 'desc' => 'Account Credit Balance', 'type' => 'Custom', 'default' => ''],
            ['name' => 'attr_overdue_invoices', 'code' => 'overdue_invoices', 'display' => 'Overdue Invoices', 'desc' => 'Number of Overdue Invoices', 'type' => 'Custom', 'default' => ''],
        ],
    ],
    'services' => [
        'label' => 'Services',
        'icon' => 'fa-server',
        'color' => '#10b981',
        'attrs' => [
            ['name' => 'attr_active_products', 'code' => 'active_products', 'display' => 'Active Products', 'desc' => 'Number of Active Products / Services', 'type' => 'Custom', 'default' => ''],
            ['name' => 'attr_suspended_products', 'code' => 'suspended_products', 'display' => 'Suspended Products', 'desc' => 'Number of Suspended Products / Services', 'type' => 'Custom', 'default' => ''],
            ['name' => 'attr_active_domains', 'code' => 'active_domains', 'display' => 'Active Domains', 'desc' => 'Number of Active Domains', 'type' => 'Custom', 'default' => ''],
            ['name' => 'attr_active_tickets', 'code' => 'active_tickets', 'display' => 'Active Tickets', 'desc' => 'Number of Active Support Tickets', 'type' => 'Custom', 'default' => ''],
            ['name' => 'attr_expiring_products', 'code' => 'expiring_products', 'display' => 'Expiring Products', 'desc' => 'Products Expiring Within 30 Days (Comma-Separated)', 'type' => 'Custom', 'default' => ''],
            ['name' => 'attr_expiring_domains', 'code' => 'expiring_domains', 'display' => 'Expiring Domains', 'desc' => 'Domains Expiring Within 30 Days (Comma-Separated)', 'type' => 'Custom', 'default' => ''],
            ['name' => 'attr_suspended_product_names', 'code' => 'suspended_product_names', 'display' => 'Suspended Product Names', 'desc' => 'Names of Suspended Products (Comma-Separated)', 'type' => 'Custom', 'default' => ''],
        ],
    ],
];

// Count Enabled Per Section
$sectionCounts = [];
foreach ($sections as $key => $section) {
    $enabled = 0;
    $total = count($section['attrs']);
    foreach ($section['attrs'] as $attr) {
        if (($settings[$attr['name']] ?? $attr['default']) === 'on') {
            $enabled++;
        }
    }
    $sectionCounts[$key] = ['enabled' => $enabled, 'total' => $total];
}

// Total Enabled Count
$totalEnabled = 0;
$totalAttrs = 0;
foreach ($sectionCounts as $counts) {
    $totalEnabled += $counts['enabled'];
    $totalAttrs += $counts['total'];
}

// Check If API Credentials Are Configured
$apiConfigured = !empty($settings['base_url']) && !empty($settings['api_access_token']) && !empty($settings['account_id']);

?>

<!-- Page Title With Save Button -->
<div class="mc-page-header mc-page-header-with-action">
    <div class="mc-page-header-left">
        <h2>Client Attributes
            <span class="mc-badge mc-badge-info" style="font-size: 12px; vertical-align: middle; margin-left: 8px;">
                <?php echo $totalAttrs; ?> Total
            </span>
        </h2>
        <p>Configure Which Client Data Is Sent To Chatwoot</p>
    </div>
    <div class="mc-page-header-right">
        <button type="submit" form="mc-attributes-form" class="mc-btn-gradient mc-save-btn" id="saveSettingsBtn" disabled>
            <i class="fas fa-save"></i>
            <span>Save Settings</span>
        </button>
    </div>
</div>

<form method="post" id="mc-attributes-form">
    <?php echo $csrfTokenField; ?>
    <input type="hidden" name="save_attributes" value="1">

    <!-- Master Toggle -->
    <div class="mc-card">
        <div class="mc-card-header">
            <span class="mc-card-icon" style="background: linear-gradient(135deg, #00EFAE 0%, #334EFC 100%);">
                <i class="fas fa-id-badge"></i>
            </span>
            <h3>Client Identification</h3>
        </div>
        <div class="mc-card-body" style="padding: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="mcToggleMaster(this)">
                <div style="display: flex; align-items: center; gap: 14px; flex: 1;">
                    <div class="mc-feature-icon" style="background: <?php echo $identificationEnabled ? 'linear-gradient(135deg, #10b98133, #10b9811a)' : '#f1f5f9'; ?>;">
                        <i class="fas fa-fingerprint" style="color: <?php echo $identificationEnabled ? '#10b981' : '#94a3b8'; ?>;" data-color="#10b981"></i>
                    </div>
                    <div>
                        <div class="mc-feature-label">Enable Client Identification</div>
                        <div class="mc-feature-desc">Send Logged-In Client Data To Chatwoot via setUser and setCustomAttributes. When Disabled, The Widget Runs In Anonymous Mode.</div>
                    </div>
                </div>
                <label class="mc-toggle" style="margin-left: 16px; flex-shrink: 0;" onclick="event.stopPropagation()">
                    <input type="checkbox" class="mc-toggle-input mc-form-input" name="enable_identification" value="1"
                        id="enableIdentification"
                        <?php echo $identificationEnabled ? 'checked' : ''; ?>
                        onchange="mcUpdateMasterState(this)">
                    <span class="mc-toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>

    <!-- API Sync Card -->
    <div class="mc-card">
        <div class="mc-card-header">
            <span class="mc-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                <i class="fas fa-cloud-upload-alt"></i>
            </span>
            <h3>Attribute Sync</h3>
        </div>
        <div class="mc-card-body">
            <div class="mc-alert mc-alert-info" style="margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i>
                <span>Chatwoot Requires Attribute Definitions Before It Can Display Custom Data. Use This Section To Automatically Create Those Definitions via The API, or Create Them Manually In Chatwoot Admin &gt; Settings &gt; Custom Attributes.</span>
            </div>

            <div class="mc-grid mc-grid-2">
                <div class="mc-form-group" style="margin-bottom: 0;">
                    <label for="account_id">Account ID</label>
                    <input type="text" id="account_id" name="account_id" class="mc-input mc-form-input"
                           value="<?php echo htmlspecialchars($settings['account_id'] ?? ''); ?>"
                           placeholder="e.g. 1">
                    <p class="mc-help-text">Found In The URL: <code>/app/accounts/<strong>1</strong>/...</code></p>
                </div>
                <div class="mc-form-group" style="margin-bottom: 0;">
                    <label for="api_access_token">API Access Token</label>
                    <div class="mc-key-field">
                        <input type="password" id="api_access_token" name="api_access_token" class="mc-input mc-form-input"
                               value="<?php echo htmlspecialchars($settings['api_access_token'] ?? ''); ?>"
                               placeholder="xxxxxxxxxxxxxxxxxxxxxxxx">
                        <button type="button" class="mc-key-btn" onclick="toggleKeyVisibility('api_access_token')">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="mc-key-btn" onclick="copyToClipboard('api_access_token')">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="mc-help-text">Found In Chatwoot &gt; Profile Settings &gt; Access Token</p>
                </div>
            </div>
        </div>
        <div class="mc-card-footer" style="justify-content: space-between; align-items: center; gap: 16px;">
            <div style="flex: 1;">
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--mc-gray); line-height: 1.8;">
                    <li><strong>Without Syncing:</strong> Chatwoot Silently Ignores Custom Attributes Without Definitions</li>
                    <li><strong>Manual Alternative:</strong> Create Definitions In Chatwoot Admin &gt; Settings &gt; Custom Attributes</li>
                    <li><strong>Safe To Re-Run:</strong> Duplicate Keys Return 422 "Already Exists", No Data Overwritten</li>
                </ul>
            </div>
            <div style="flex-shrink: 0; display: flex; gap: 10px;">
                <button type="button" class="mc-btn-gradient mc-btn-gradient-outline" id="fetchAttributesBtn"
                        onclick="mcFetchAttributes()"
                        <?php echo !$apiConfigured ? 'disabled' : ''; ?>
                        title="<?php echo !$apiConfigured ? 'Configure Account ID, API Access Token, and Base URL In General Settings' : 'Fetch Existing Attribute Definitions From Chatwoot'; ?>">
                    <i class="fas fa-download"></i> Fetch Current Attributes
                </button>
                <button type="button" class="mc-btn-gradient mc-btn-solid-primary" id="syncAttributesBtn"
                        onclick="mcSyncAttributes()"
                        <?php echo !$apiConfigured ? 'disabled' : ''; ?>
                        title="<?php echo !$apiConfigured ? 'Configure Account ID, API Access Token, and Base URL In General Settings' : 'Create Attribute Definitions In Chatwoot'; ?>">
                    <i class="fas fa-sync-alt"></i> Sync Attributes To Chatwoot
                </button>
            </div>
        </div>
    </div>

    <!-- Attribute Sections -->
    <div id="attributeSections" class="<?php echo !$identificationEnabled ? 'mc-attrs-disabled' : ''; ?>">
        <!-- Summary Bar -->
        <div class="mc-card" style="margin-bottom: 16px;">
            <div class="mc-card-body" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-chart-pie" style="color: var(--mc-primary);"></i>
                    <span style="font-size: 14px; font-weight: 500; color: var(--mc-dark);">
                        <strong id="totalEnabledCount"><?php echo $totalEnabled; ?></strong> of <?php echo $totalAttrs; ?> Attributes Enabled
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="mc-btn mc-btn-outline-success mc-btn-sm" onclick="mcEnableAllAttrs()">
                        <i class="fas fa-check"></i> Enable All
                    </button>
                    <button type="button" class="mc-btn mc-btn-outline-danger mc-btn-sm" onclick="mcDisableAllAttrs()">
                        <i class="fas fa-times"></i> Disable All
                    </button>
                </div>
            </div>
        </div>

        <!-- Fetch Results Container (Hidden By Default) -->
        <div id="fetchResultsContainer" style="display: none; margin-bottom: 16px;">
            <div class="mc-card">
                <div class="mc-card-header">
                    <span class="mc-card-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                        <i class="fas fa-download"></i>
                    </span>
                    <h3>Chatwoot Attributes</h3>
                    <span id="fetchTotalBadge" class="mc-badge mc-badge-info" style="font-size: 12px; margin-left: 8px;"></span>
                    <button type="button" style="margin-left: auto; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 16px; padding: 4px 8px;"
                            onclick="document.getElementById('fetchResultsContainer').style.display='none'" title="Dismiss">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mc-card-body" style="padding: 0;">
                    <div id="fetchResultsTable"></div>
                </div>
            </div>
        </div>

        <!-- Sync Results Container (Hidden By Default) -->
        <div id="syncResultsContainer" style="display: none; margin-bottom: 16px;">
            <div class="mc-card">
                <div class="mc-card-header">
                    <span class="mc-card-icon" style="background: linear-gradient(135deg, #00EFAE 0%, #334EFC 100%);">
                        <i class="fas fa-sync-alt"></i>
                    </span>
                    <h3>Sync Results</h3>
                    <button type="button" style="margin-left: auto; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 16px; padding: 4px 8px;"
                            onclick="document.getElementById('syncResultsContainer').style.display='none'" title="Dismiss">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mc-card-body" style="padding: 20px;">
                    <!-- Summary Badges -->
                    <div id="syncSummary" style="display: flex; gap: 12px; margin-bottom: 16px;"></div>
                    <!-- Results Table -->
                    <div id="syncResultsTable"></div>
                </div>
            </div>
        </div>

        <?php foreach ($sections as $sectionKey => $section):
            $counts = $sectionCounts[$sectionKey];
            $allEnabled = $counts['enabled'] === $counts['total'];
            $allDisabled = $counts['enabled'] === 0;
        ?>
        <div class="mc-card mc-attr-section" data-section="<?php echo $sectionKey; ?>">
            <!-- Section Header With Enable/Disable Buttons -->
            <div class="mc-attr-section-header">
                <h3 class="mc-attr-section-title">
                    <i class="fas <?php echo $section['icon']; ?>" style="color: <?php echo $section['color']; ?>;"></i>
                    <span><?php echo htmlspecialchars($section['label']); ?></span>
                    <small>(<?php echo $counts['total']; ?>)</small>
                </h3>
                <div class="mc-attr-section-actions">
                    <button type="button" class="mc-btn mc-btn-outline-success mc-btn-sm" onclick="mcEnableSection('<?php echo $sectionKey; ?>')"<?php echo $allEnabled ? ' disabled' : ''; ?>>
                        <i class="fas fa-check"></i> Enable
                    </button>
                    <button type="button" class="mc-btn mc-btn-outline-danger mc-btn-sm" onclick="mcDisableSection('<?php echo $sectionKey; ?>')"<?php echo $allDisabled ? ' disabled' : ''; ?>>
                        <i class="fas fa-times"></i> Disable
                    </button>
                </div>
            </div>

            <!-- Attributes Table -->
            <div style="padding: 0;">
                <table class="mc-table mc-attr-table">
                    <colgroup>
                        <col class="mc-col-attr">
                        <col class="mc-col-display">
                        <col>
                        <col style="width: 95px;">
                        <col style="width: 85px;">
                        <col style="width: 95px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th style="padding-left: 20px; white-space: nowrap;">Attribute</th>
                            <th style="white-space: nowrap;">Display Name</th>
                            <th>Description</th>
                            <th style="text-align: center;">Type</th>
                            <th style="text-align: center;">Sync</th>
                            <th style="text-align: right; padding-right: 20px;">Enabled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($section['attrs'] as $attr):
                            $attrEnabled = ($settings[$attr['name']] ?? $attr['default']) === 'on';
                            $badgeClass = $attr['type'] === 'Identity' ? 'mc-badge-info'
                                : ($attr['type'] === 'Contact' ? 'mc-badge-contact' : 'mc-badge-success');
                            $isNative = in_array($attr['type'], ['Identity', 'Contact']);
                            $overrideKey = !$isNative ? ($attributeOverrides[$attr['code']]['key'] ?? '') : '';
                            $overrideDisplay = !$isNative ? ($attributeOverrides[$attr['code']]['display'] ?? '') : '';
                            $effectiveKey = !empty($overrideKey) ? $overrideKey : $attr['code'];
                            $effectiveDisplay = !empty($overrideDisplay) ? $overrideDisplay : $attr['display'];
                            $rowId = 'override-' . $attr['code'];
                        ?>
                        <tr>
                            <td style="padding-left: 20px;">
                                <code><?php echo htmlspecialchars($effectiveKey); ?><?php if (!empty($overrideKey)): ?> <i class="fas fa-pen-fancy" style="font-size: 9px; color: var(--mc-primary);" title="Custom Key"></i><?php endif; ?><?php if (!$isNative): ?> <i class="fas fa-cog mc-attr-edit-btn" onclick="mcToggleOverrideRow('<?php echo $rowId; ?>')" title="Customize Key and Display Name" style="font-size: 11px; color: var(--mc-gray); cursor: pointer; transition: color 0.2s;"></i><?php endif; ?></code>
                            </td>
                            <td>
                                <?php if ($isNative): ?>
                                    <span style="color: var(--mc-gray-400);">—</span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($effectiveDisplay); ?><?php if (!empty($overrideDisplay)): ?> <i class="fas fa-pen-fancy" style="font-size: 9px; color: var(--mc-primary);" title="Custom Display Name"></i><?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($attr['desc']); ?></td>
                            <td style="text-align: center;">
                                <span class="mc-badge <?php echo $badgeClass; ?>"><?php echo $attr['type']; ?></span>
                            </td>
                            <td style="text-align: center;" data-sync-icon="<?php echo $attr['code']; ?>">
                                <?php if ($isNative): ?>
                                    <span style="color: var(--mc-gray-400);">—</span>
                                <?php else: ?>
                                    <i class="fas fa-spinner fa-spin" style="color: var(--mc-gray-300); font-size: 15px;" title="Checking..."></i>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; padding-right: 20px;">
                                <label class="mc-table-toggle" style="display: inline-flex; justify-content: flex-end;">
                                    <input type="checkbox" class="mc-table-toggle-input mc-form-input mc-attr-toggle"
                                           name="<?php echo $attr['name']; ?>" value="1"
                                           data-section="<?php echo $sectionKey; ?>"
                                           <?php echo $attrEnabled ? 'checked' : ''; ?>
                                           onchange="mcUpdateSectionButtons('<?php echo $sectionKey; ?>')">
                                    <span class="mc-table-toggle-slider"></span>
                                </label>
                            </td>
                        </tr>
                        <?php if (!$isNative): ?>
                        <tr class="mc-override-row" id="<?php echo $rowId; ?>" style="display: none;">
                            <td colspan="6" style="padding: 12px 20px; background: #f8fafc;">
                                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 180px;">
                                        <label style="font-size: 11px; font-weight: 600; color: var(--mc-gray); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Custom Key</label>
                                        <input type="text" name="override_key_<?php echo $attr['code']; ?>" class="mc-input mc-form-input"
                                               value="<?php echo htmlspecialchars($overrideKey); ?>"
                                               placeholder="<?php echo $attr['code']; ?>"
                                               pattern="[a-z0-9_]*"
                                               title="Lowercase Letters, Numbers, and Underscores Only"
                                               style="font-family: monospace; font-size: 13px; padding: 8px 12px;">
                                    </div>
                                    <div style="flex: 1; min-width: 180px;">
                                        <label style="font-size: 11px; font-weight: 600; color: var(--mc-gray); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Custom Display Name</label>
                                        <input type="text" name="override_display_<?php echo $attr['code']; ?>" class="mc-input mc-form-input"
                                               value="<?php echo htmlspecialchars($overrideDisplay); ?>"
                                               placeholder="<?php echo htmlspecialchars($attr['display']); ?>"
                                               style="font-size: 13px; padding: 8px 12px;">
                                    </div>
                                    <div style="flex-shrink: 0; padding-top: 18px;">
                                        <button type="button" class="mc-btn mc-btn-sm" onclick="mcClearOverride('<?php echo $attr['code']; ?>', '<?php echo $rowId; ?>')"
                                                style="background: none; border: 1px solid var(--mc-border); color: var(--mc-gray); border-radius: 6px; padding: 0 10px; height: 34px;"
                                                title="Reset To Defaults">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="mc-help-text" style="margin-top: 8px;">Leave Empty To Use Defaults. Custom Key Affects Both Widget and Sync.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Info Footer -->
    <div class="mc-card" style="margin-top: 20px;">
        <div class="mc-card-footer mc-card-footer-info">
            <i class="fas fa-info-circle"></i>
            <span>Identity and Contact Data Appears In Chatwoot's "Contact Information" Section. Custom Attributes Appear In The "Contact Attributes" Section.</span>
        </div>
    </div>
</form>

<script>
// Toggle Master Identification
function mcToggleMaster(el) {
    var checkbox = el.querySelector('.mc-toggle-input') || document.getElementById('enableIdentification');
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        mcUpdateMasterState(checkbox);
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

// Update Master Toggle State — Enable/Disable Sections
function mcUpdateMasterState(checkbox) {
    var sections = document.getElementById('attributeSections');
    var cardBody = checkbox.closest('.mc-card-body');
    var iconEl = cardBody ? cardBody.querySelector('.mc-feature-icon') : null;
    var iconI = cardBody ? cardBody.querySelector('.mc-feature-icon i') : null;

    if (checkbox.checked) {
        sections.classList.remove('mc-attrs-disabled');
        if (iconEl) iconEl.style.background = 'linear-gradient(135deg, #10b98133, #10b9811a)';
        if (iconI) iconI.style.color = '#10b981';
    } else {
        sections.classList.add('mc-attrs-disabled');
        if (iconEl) iconEl.style.background = '#f1f5f9';
        if (iconI) iconI.style.color = '#94a3b8';
    }
}

// Toggle Override Row Visibility
function mcToggleOverrideRow(rowId) {
    var row = document.getElementById(rowId);
    if (row) {
        row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
    }
}

// Clear Override Inputs
function mcClearOverride(attrCode, rowId) {
    var keyInput = document.querySelector('input[name="override_key_' + attrCode + '"]');
    var displayInput = document.querySelector('input[name="override_display_' + attrCode + '"]');
    if (keyInput) {
        keyInput.value = '';
        keyInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (displayInput) {
        displayInput.value = '';
        displayInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

// Enable All Attributes In A Section
function mcEnableSection(sectionKey) {
    var toggles = document.querySelectorAll('.mc-attr-toggle[data-section="' + sectionKey + '"]');
    toggles.forEach(function(t) {
        t.checked = true;
        t.dispatchEvent(new Event('change', { bubbles: true }));
    });
    mcUpdateSectionButtons(sectionKey);
}

// Disable All Attributes In A Section
function mcDisableSection(sectionKey) {
    var toggles = document.querySelectorAll('.mc-attr-toggle[data-section="' + sectionKey + '"]');
    toggles.forEach(function(t) {
        t.checked = false;
        t.dispatchEvent(new Event('change', { bubbles: true }));
    });
    mcUpdateSectionButtons(sectionKey);
}

// Enable All Attributes Globally
function mcEnableAllAttrs() {
    document.querySelectorAll('.mc-attr-toggle').forEach(function(t) {
        t.checked = true;
        t.dispatchEvent(new Event('change', { bubbles: true }));
    });
    mcUpdateAllSectionButtons();
}

// Disable All Attributes Globally
function mcDisableAllAttrs() {
    document.querySelectorAll('.mc-attr-toggle').forEach(function(t) {
        t.checked = false;
        t.dispatchEvent(new Event('change', { bubbles: true }));
    });
    mcUpdateAllSectionButtons();
}

// Update Section Enable/Disable Button States
function mcUpdateSectionButtons(sectionKey) {
    var card = document.querySelector('.mc-attr-section[data-section="' + sectionKey + '"]');
    if (!card) return;

    var toggles = card.querySelectorAll('.mc-attr-toggle');
    var enabledCount = 0;
    toggles.forEach(function(t) { if (t.checked) enabledCount++; });

    var enableBtn = card.querySelector('.mc-btn-outline-success');
    var disableBtn = card.querySelector('.mc-btn-outline-danger');

    if (enableBtn) enableBtn.disabled = (enabledCount === toggles.length);
    if (disableBtn) disableBtn.disabled = (enabledCount === 0);

    mcUpdateTotalCount();
}

// Update All Section Buttons
function mcUpdateAllSectionButtons() {
    document.querySelectorAll('.mc-attr-section').forEach(function(card) {
        var sectionKey = card.getAttribute('data-section');
        mcUpdateSectionButtons(sectionKey);
    });
}

// Update Total Enabled Count
function mcUpdateTotalCount() {
    var total = 0;
    document.querySelectorAll('.mc-attr-toggle').forEach(function(t) {
        if (t.checked) total++;
    });
    var countEl = document.getElementById('totalEnabledCount');
    if (countEl) countEl.textContent = total;
}

// Change Detection For Save Button
mcInitChangeDetection('mc-attributes-form', 'saveSettingsBtn');

// HTML Escape Helper For innerHTML Insertions
function mcEscapeHtml(str) {
    if (typeof str !== 'string') return str;
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// Shared API Module Link and CSRF Token
var mcModuleLink = <?php echo json_encode($moduleLink); ?>;
var mcCsrfToken = <?php echo json_encode($csrfToken); ?>;

// Shared XHR Helper
function mcApiRequest(url, csrfToken, onSuccess, onError) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onload = function() {
        try {
            var data = JSON.parse(xhr.responseText);
            onSuccess(data);
        } catch (e) {
            onError('Invalid Response From Server');
        }
    };
    xhr.onerror = function() {
        onError('Network Error — Check Your Connection');
    };
    xhr.send('csrf_token=' + encodeURIComponent(csrfToken));
}

// Sync Attributes To Chatwoot via API
function mcSyncAttributes() {
    var btn = document.getElementById('syncAttributesBtn');
    var originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';

    mcApiRequest(
        mcModuleLink + '&action=sync_attributes',
        mcCsrfToken,
        function(data) {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            if (data.success) {
                mcShowSyncResults(data);
            } else {
                mcShowSyncError(data.message || 'Sync Failed');
            }
        },
        function(message) {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            mcShowSyncError(message);
        }
    );
}

// Render Sync Results
function mcShowSyncResults(data) {
    var container = document.getElementById('syncResultsContainer');
    var summaryEl = document.getElementById('syncSummary');
    var tableEl = document.getElementById('syncResultsTable');

    var s = data.summary;
    summaryEl.innerHTML =
        '<span class="mc-badge mc-badge-success" style="font-size: 13px; padding: 6px 14px;">' +
            '<i class="fas fa-check-circle"></i> ' + s.created + ' Created</span>' +
        '<span class="mc-badge mc-badge-info" style="font-size: 13px; padding: 6px 14px;">' +
            '<i class="fas fa-info-circle"></i> ' + s.existed + ' Already Existed</span>' +
        (s.failed > 0
            ? '<span class="mc-badge mc-badge-danger" style="font-size: 13px; padding: 6px 14px;">' +
                '<i class="fas fa-exclamation-circle"></i> ' + s.failed + ' Failed</span>'
            : '');

    var rows = '';
    data.results.forEach(function(r) {
        var badgeClass = r.status === 'created' ? 'mc-badge-success'
                       : r.status === 'existed' ? 'mc-badge-info'
                       : 'mc-badge-danger';
        var statusLabel = r.status === 'created' ? 'Created'
                        : r.status === 'existed' ? 'Existed'
                        : r.status === 'skipped' ? 'Skipped'
                        : 'Failed';
        rows += '<tr>' +
            '<td style="padding-left: 20px;"><code>' + mcEscapeHtml(r.key) + '</code></td>' +
            '<td style="text-align: center;"><span class="mc-badge ' + badgeClass + '">' + statusLabel + '</span></td>' +
            '<td>' + mcEscapeHtml(r.detail) + '</td>' +
        '</tr>';
    });

    tableEl.innerHTML =
        '<table class="mc-table">' +
            '<thead><tr>' +
                '<th style="padding-left: 20px;">Attribute Key</th>' +
                '<th style="width: 100px; text-align: center;">Status</th>' +
                '<th>Detail</th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
        '</table>';

    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Update Sync Icons In Attribute Tables
    data.results.forEach(function(r) {
        if (!r.originalKey) return;
        var td = document.querySelector('[data-sync-icon="' + r.originalKey + '"]');
        if (!td) return;
        if (r.status === 'created' || r.status === 'existed') {
            td.innerHTML = '<i class="fas fa-check-circle" style="color: var(--mc-success); font-size: 15px;" title="Synced To Chatwoot"></i>';
        } else if (r.status === 'failed' || r.status === 'skipped') {
            td.innerHTML = '<i class="fas fa-times-circle" style="color: var(--mc-danger); font-size: 15px;" title="Sync Failed"></i>';
        }
    });
}

// Render Sync Error
function mcShowSyncError(message) {
    var container = document.getElementById('syncResultsContainer');
    var summaryEl = document.getElementById('syncSummary');
    var tableEl = document.getElementById('syncResultsTable');

    summaryEl.innerHTML =
        '<span class="mc-badge mc-badge-danger" style="font-size: 13px; padding: 6px 14px;">' +
            '<i class="fas fa-exclamation-triangle"></i> Error</span>';

    tableEl.innerHTML =
        '<div style="padding: 16px; color: #ef4444; font-size: 14px;">' +
            '<i class="fas fa-exclamation-circle"></i> ' + mcEscapeHtml(message) +
        '</div>';

    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Fetch Attributes From Chatwoot via API
function mcFetchAttributes() {
    var btn = document.getElementById('fetchAttributesBtn');
    var originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching...';

    mcApiRequest(
        mcModuleLink + '&action=fetch_attributes',
        mcCsrfToken,
        function(data) {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            if (data.success) {
                mcShowFetchResults(data);
            } else {
                mcShowFetchError(data.message || 'Fetch Failed');
            }
        },
        function(message) {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            mcShowFetchError(message);
        }
    );
}

// Render Fetch Results
function mcShowFetchResults(data) {
    var container = document.getElementById('fetchResultsContainer');
    var badgeEl = document.getElementById('fetchTotalBadge');
    var tableEl = document.getElementById('fetchResultsTable');

    badgeEl.textContent = data.total + ' Found';

    if (data.attributes.length === 0) {
        tableEl.innerHTML =
            '<div style="padding: 20px; text-align: center; color: var(--mc-gray);">' +
                '<i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>' +
                'No Custom Attributes Found In Chatwoot' +
            '</div>';
    } else {
        // Group By Model
        var contactAttrs = data.attributes.filter(function(a) { return a.model === 'Contact'; });
        var convAttrs = data.attributes.filter(function(a) { return a.model === 'Conversation'; });

        var rows = '';
        function buildRows(attrs) {
            attrs.forEach(function(a) {
                var typeBadge = '<span class="mc-badge mc-badge-success" style="font-size: 11px;">' + mcEscapeHtml(a.type || '—') + '</span>';
                var modelBadge = a.model === 'Contact'
                    ? '<span class="mc-badge mc-badge-info" style="font-size: 11px;">Contact</span>'
                    : '<span class="mc-badge" style="font-size: 11px; background: #f3e8ff; color: #7c3aed;">Conversation</span>';
                rows += '<tr>' +
                    '<td style="padding-left: 20px;"><code>' + mcEscapeHtml(a.key) + '</code></td>' +
                    '<td>' + mcEscapeHtml(a.display_name || '—') + '</td>' +
                    '<td>' + mcEscapeHtml(a.description || '—') + '</td>' +
                    '<td style="text-align: center;">' + typeBadge + '</td>' +
                    '<td style="text-align: center;">' + modelBadge + '</td>' +
                '</tr>';
            });
        }

        if (contactAttrs.length > 0) {
            rows += '<tr><td colspan="5" style="padding: 10px 20px; background: #f8fafc; font-weight: 600; font-size: 13px; color: var(--mc-dark);">' +
                '<i class="fas fa-user" style="color: #3b82f6; margin-right: 6px;"></i>Contact Attributes (' + contactAttrs.length + ')</td></tr>';
            buildRows(contactAttrs);
        }
        if (convAttrs.length > 0) {
            rows += '<tr><td colspan="5" style="padding: 10px 20px; background: #f8fafc; font-weight: 600; font-size: 13px; color: var(--mc-dark);">' +
                '<i class="fas fa-comment-dots" style="color: #7c3aed; margin-right: 6px;"></i>Conversation Attributes (' + convAttrs.length + ')</td></tr>';
            buildRows(convAttrs);
        }

        tableEl.innerHTML =
            '<table class="mc-table">' +
                '<thead><tr>' +
                    '<th style="padding-left: 20px;">Attribute Key</th>' +
                    '<th>Display Name</th>' +
                    '<th>Description</th>' +
                    '<th style="width: 95px; text-align: center;">Type</th>' +
                    '<th style="width: 120px; text-align: center;">Model</th>' +
                '</tr></thead>' +
                '<tbody>' + rows + '</tbody>' +
            '</table>';
    }

    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Render Fetch Error
function mcShowFetchError(message) {
    var container = document.getElementById('fetchResultsContainer');
    var badgeEl = document.getElementById('fetchTotalBadge');
    var tableEl = document.getElementById('fetchResultsTable');

    badgeEl.textContent = 'Error';
    badgeEl.className = 'mc-badge mc-badge-danger';

    tableEl.innerHTML =
        '<div style="padding: 16px 20px; color: #ef4444; font-size: 14px;">' +
            '<i class="fas fa-exclamation-circle"></i> ' + mcEscapeHtml(message) +
        '</div>';

    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Auto-Check Sync Status on Page Load by Fetching Live Data From Chatwoot
function mcCheckSyncStatus() {
    <?php if (!$apiConfigured): ?>
    // API Not Configured — Show Dash For All Non-Native Sync Icons
    document.querySelectorAll('[data-sync-icon]').forEach(function(td) {
        if (td.querySelector('.fa-spinner')) {
            td.innerHTML = '<i class="fas fa-minus-circle" style="color: var(--mc-gray-300); font-size: 15px;" title="API Not Configured"></i>';
        }
    });
    return;
    <?php endif; ?>

    mcApiRequest(
        mcModuleLink + '&action=fetch_attributes',
        mcCsrfToken,
        function(data) {
            if (data.success) {
                mcUpdateSyncIcons(data.attributes);
            } else {
                mcSetAllSyncIcons('unknown');
            }
        },
        function() {
            mcSetAllSyncIcons('unknown');
        }
    );
}

// Compare Chatwoot Attributes Against Our Attribute Keys and Update Icons
function mcUpdateSyncIcons(chatwootAttrs) {
    // Build A Set of Existing Chatwoot Attribute Keys (Contact Only)
    var existingKeys = {};
    chatwootAttrs.forEach(function(a) {
        existingKeys[a.key] = a.model;
    });

    document.querySelectorAll('[data-sync-icon]').forEach(function(td) {
        // Skip Native (Identity/Contact) — They Already Show "—"
        if (td.querySelector('span')) return;

        var attrCode = td.getAttribute('data-sync-icon');
        // Resolve Effective Key (Check If Override Exists)
        var codeCell = td.closest('tr').querySelector('td:first-child code');
        var effectiveKey = codeCell ? codeCell.textContent.trim() : attrCode;

        if (existingKeys[effectiveKey]) {
            td.innerHTML = '<i class="fas fa-check-circle" style="color: var(--mc-success); font-size: 15px;" title="Exists In Chatwoot"></i>';
        } else {
            td.innerHTML = '<i class="fas fa-minus-circle" style="color: var(--mc-gray-300); font-size: 15px;" title="Not Found In Chatwoot"></i>';
        }
    });
}

// Set All Non-Native Sync Icons To A Given State
function mcSetAllSyncIcons(state) {
    document.querySelectorAll('[data-sync-icon]').forEach(function(td) {
        if (td.querySelector('span')) return;
        if (state === 'unknown') {
            td.innerHTML = '<i class="fas fa-question-circle" style="color: var(--mc-gray-300); font-size: 15px;" title="Could Not Check"></i>';
        }
    });
}

// Fire On Page Load
document.addEventListener('DOMContentLoaded', mcCheckSyncStatus);
</script>
