<?php
/**
 * Admin General Settings Template
 *
 * @package    MultilatChatwoot
 */

if (!defined('WHMCS')) {
    die('Access Denied');
}
?>

<!-- Page Title With Save Button -->
<div class="mc-page-header mc-page-header-with-action">
    <div class="mc-page-header-left">
        <h2>General Settings</h2>
        <p>Configure Your Chatwoot Widget and Connection</p>
    </div>
    <div class="mc-page-header-right">
        <button type="submit" form="mc-general-form" class="mc-btn-gradient mc-save-btn" id="saveSettingsBtn" disabled>
            <i class="fas fa-save"></i>
            <span>Save Settings</span>
        </button>
    </div>
</div>

<form method="post" id="mc-general-form">
    <?php echo $csrfTokenField; ?>
    <input type="hidden" name="save_general" value="1">

    <!-- Connection Settings -->
    <div class="mc-card">
        <div class="mc-card-header">
            <span class="mc-card-icon" style="background: linear-gradient(135deg, #00EFAE 0%, #334EFC 100%);">
                <i class="fas fa-plug"></i>
            </span>
            <h3>Connection Settings</h3>
        </div>
        <div class="mc-card-body">
            <div class="mc-form-group">
                <label for="base_url">Base URL</label>
                <input type="url" id="base_url" name="base_url" class="mc-input mc-form-input"
                       value="<?php echo htmlspecialchars($settings['base_url'] ?? ''); ?>"
                       placeholder="https://chat.example.com">
                <p class="mc-help-text">Your Chatwoot Instance URL (Without Trailing Slash)</p>
            </div>

            <div class="mc-form-group">
                <label for="website_token">Website Token</label>
                <div class="mc-key-field">
                    <input type="password" id="website_token" name="website_token" class="mc-input mc-form-input"
                           value="<?php echo htmlspecialchars($settings['website_token'] ?? ''); ?>"
                           placeholder="xxxxxxxxxxxxxxxxxxxxxxxx">
                    <button type="button" class="mc-key-btn" onclick="toggleKeyVisibility('website_token')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="mc-key-btn" onclick="copyToClipboard('website_token')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="mc-help-text">Found In Chatwoot &gt; Settings &gt; Inboxes &gt; Configuration</p>
            </div>

        </div>
    </div>

    <!-- Widget Features + Identity Verification — Side By Side -->
    <div class="mc-grid mc-grid-2">
        <!-- Widget Features (Left — Single Column Cards) -->
        <div class="mc-card">
            <div class="mc-card-header">
                <span class="mc-card-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <i class="fas fa-sliders-h"></i>
                </span>
                <h3>Widget Features</h3>
            </div>
            <div class="mc-card-body" style="padding: 24px;">
                <p class="mc-text-muted" style="margin-bottom: 20px;">Enable or Disable Widget Features and Behavior</p>

                <div class="mc-feature-grid" style="grid-template-columns: 1fr;">
                    <?php
                    $features = [
                        ['name' => 'enable_widget', 'label' => 'Chat Widget', 'icon' => 'fa-comments', 'desc' => 'Show Chatwoot Widget on Client Area', 'color' => '#00EFAE', 'default' => ''],
                        ['name' => 'dark_mode', 'label' => 'Auto Dark Mode', 'icon' => 'fa-moon', 'desc' => 'Follow User System Theme Preference', 'color' => '#8b5cf6', 'default' => 'light', 'check_value' => 'auto'],
                        ['name' => 'defer_load', 'label' => 'Defer Loading', 'icon' => 'fa-bolt', 'desc' => 'Load Widget After Page Is Fully Loaded', 'color' => '#f59e0b', 'default' => 'on'],
                        ['name' => 'conv_attr_source', 'label' => 'Source Attribute', 'icon' => 'fa-tag', 'desc' => 'Set Source To WHMCS on Conversations', 'color' => '#06b6d4', 'default' => 'on'],
                        ['name' => 'conv_attr_current_page', 'label' => 'Current Page', 'icon' => 'fa-link', 'desc' => 'Track Which Page The Client Is Viewing', 'color' => '#3b82f6', 'default' => 'on'],
                    ];

                    foreach ($features as $feature):
                        $checkValue = $feature['check_value'] ?? 'on';
                        $isEnabled = ($settings[$feature['name']] ?? $feature['default']) === $checkValue;
                        $iconBg = $isEnabled
                            ? 'linear-gradient(135deg, ' . $feature['color'] . '33, ' . $feature['color'] . '1a)'
                            : '#f1f5f9';
                        $iconColor = $isEnabled ? $feature['color'] : '#94a3b8';
                    ?>
                    <div class="mc-feature-card" onclick="mcToggleFeature(this)">
                        <div class="mc-feature-card-inner">
                            <div class="mc-feature-card-left">
                                <div class="mc-feature-icon" style="background: <?php echo $iconBg; ?>;">
                                    <i class="fas <?php echo $feature['icon']; ?>" style="color: <?php echo $iconColor; ?>;"></i>
                                </div>
                                <div>
                                    <div class="mc-feature-label"><?php echo htmlspecialchars($feature['label']); ?></div>
                                    <div class="mc-feature-desc"><?php echo htmlspecialchars($feature['desc']); ?></div>
                                </div>
                            </div>
                            <label class="mc-toggle" style="margin-left: 16px; flex-shrink: 0;" onclick="event.stopPropagation()">
                                <input type="checkbox" class="mc-toggle-input mc-form-input" name="<?php echo $feature['name']; ?>" value="1"
                                    <?php echo $isEnabled ? 'checked' : ''; ?>
                                    onchange="mcUpdateFeatureCard(this)">
                                <span class="mc-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mc-card-footer mc-card-footer-info">
                <i class="fas fa-info-circle"></i>
                <span>Conversation Attributes Are Visible To Agents In The Conversation Sidebar</span>
            </div>
        </div>

        <!-- Identity Verification (Right) -->
        <div class="mc-card">
            <div class="mc-card-header">
                <span class="mc-card-icon" style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);">
                    <i class="fas fa-shield-alt"></i>
                </span>
                <h3>Identity Verification (HMAC)</h3>
            </div>
            <div class="mc-card-body">
                <div class="mc-form-group">
                    <label for="hmac_token">HMAC Token</label>
                    <div class="mc-key-field">
                        <input type="password" id="hmac_token" name="hmac_token" class="mc-input mc-form-input"
                               value="<?php echo htmlspecialchars($settings['hmac_token'] ?? ''); ?>"
                               placeholder="Leave Empty To Disable">
                        <button type="button" class="mc-key-btn" onclick="toggleKeyVisibility('hmac_token')">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="mc-key-btn" onclick="copyToClipboard('hmac_token')">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="mc-help-text">Found In Chatwoot &gt; Settings &gt; Inboxes &gt; Configuration &gt; Identity Validation</p>
                </div>

                <div class="mc-info-box">
                    <h4><i class="fas fa-info-circle"></i> What It Does</h4>
                    <p>Prevents Malicious Users From Impersonating Other Clients In The Chat Widget By Cryptographically Verifying Each User's Identity</p>
                </div>

                <div class="mc-info-box" style="margin-top: 12px;">
                    <h4><i class="fas fa-cogs"></i> How It Works</h4>
                    <ol>
                        <li>Server Computes A Hash of The Client Email Using This Secret</li>
                        <li>Hash Is Sent With <code>setUser</code> As <code>identifier_hash</code></li>
                        <li>Chatwoot Verifies The Hash To Prevent Impersonation</li>
                    </ol>
                    <p>Leave Empty To Disable Identity Verification</p>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Toggle Feature Card on Click
function mcToggleFeature(card) {
    var checkbox = card.querySelector('.mc-toggle-input');
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        mcUpdateFeatureCard(checkbox);
        // Trigger Change Event For Save Button Detection
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

// Update Feature Card Icon Colors Based on Toggle State
function mcUpdateFeatureCard(checkbox) {
    var card = checkbox.closest('.mc-feature-card');
    if (!card) return;
    var iconEl = card.querySelector('.mc-feature-icon');
    var iconI = card.querySelector('.mc-feature-icon i');
    if (!iconEl || !iconI) return;

    // Get Original Color From PHP Data
    var allCards = document.querySelectorAll('.mc-feature-card');
    var cardIndex = Array.prototype.indexOf.call(allCards, card);

    if (checkbox.checked) {
        // Restore Color — Read From Current Inline Style As Fallback
        var color = iconI.getAttribute('data-color') || iconI.style.color || '#94a3b8';
        iconEl.style.background = 'linear-gradient(135deg, ' + color + '33, ' + color + '1a)';
        iconI.style.color = color;
    } else {
        // Store Current Color Before Disabling
        if (!iconI.getAttribute('data-color') && iconI.style.color && iconI.style.color !== 'rgb(148, 163, 184)') {
            iconI.setAttribute('data-color', iconI.style.color);
        }
        iconEl.style.background = '#f1f5f9';
        iconI.style.color = '#94a3b8';
    }
}

// Store Initial Colors on Load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.mc-feature-card .mc-feature-icon i').forEach(function(iconI) {
        if (iconI.style.color && iconI.style.color !== '#94a3b8' && iconI.style.color !== 'rgb(148, 163, 184)') {
            iconI.setAttribute('data-color', iconI.style.color);
        }
    });
});

// Change Detection For Save Button
mcInitChangeDetection('mc-general-form', 'saveSettingsBtn');
</script>
