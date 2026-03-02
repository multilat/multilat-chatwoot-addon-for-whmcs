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

    <!-- Widget Appearance -->
    <div class="mc-card">
        <div class="mc-card-header">
            <span class="mc-card-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i class="fas fa-paint-brush"></i>
            </span>
            <h3>Widget Appearance</h3>
        </div>
        <div class="mc-card-body">
            <div class="mc-grid mc-grid-2">
                <div class="mc-form-group">
                    <label for="locale">Locale</label>
                    <select id="locale" name="locale" class="mc-input mc-form-input">
                        <option value="" <?php echo ($settings['locale'] ?? '') === '' ? 'selected' : ''; ?>>Auto (Browser Default)</option>
                        <option value="ar" <?php echo ($settings['locale'] ?? '') === 'ar' ? 'selected' : ''; ?>>Arabic</option>
                        <option value="bn" <?php echo ($settings['locale'] ?? '') === 'bn' ? 'selected' : ''; ?>>Bengali</option>
                        <option value="zh" <?php echo ($settings['locale'] ?? '') === 'zh' ? 'selected' : ''; ?>>Chinese</option>
                        <option value="nl" <?php echo ($settings['locale'] ?? '') === 'nl' ? 'selected' : ''; ?>>Dutch</option>
                        <option value="en" <?php echo ($settings['locale'] ?? '') === 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="fr" <?php echo ($settings['locale'] ?? '') === 'fr' ? 'selected' : ''; ?>>French</option>
                        <option value="de" <?php echo ($settings['locale'] ?? '') === 'de' ? 'selected' : ''; ?>>German</option>
                        <option value="hi" <?php echo ($settings['locale'] ?? '') === 'hi' ? 'selected' : ''; ?>>Hindi</option>
                        <option value="it" <?php echo ($settings['locale'] ?? '') === 'it' ? 'selected' : ''; ?>>Italian</option>
                        <option value="ja" <?php echo ($settings['locale'] ?? '') === 'ja' ? 'selected' : ''; ?>>Japanese</option>
                        <option value="ko" <?php echo ($settings['locale'] ?? '') === 'ko' ? 'selected' : ''; ?>>Korean</option>
                        <option value="pt" <?php echo ($settings['locale'] ?? '') === 'pt' ? 'selected' : ''; ?>>Portuguese</option>
                        <option value="ru" <?php echo ($settings['locale'] ?? '') === 'ru' ? 'selected' : ''; ?>>Russian</option>
                        <option value="es" <?php echo ($settings['locale'] ?? '') === 'es' ? 'selected' : ''; ?>>Spanish</option>
                        <option value="tr" <?php echo ($settings['locale'] ?? '') === 'tr' ? 'selected' : ''; ?>>Turkish</option>
                    </select>
                    <p class="mc-help-text">Language For The Chat Widget Interface</p>
                </div>

                <div class="mc-form-group">
                    <label for="widget_type">Widget Type</label>
                    <select id="widget_type" name="widget_type" class="mc-input mc-form-input">
                        <option value="standard" <?php echo ($settings['widget_type'] ?? 'standard') === 'standard' ? 'selected' : ''; ?>>Standard (Icon Only)</option>
                        <option value="expanded_bubble" <?php echo ($settings['widget_type'] ?? 'standard') === 'expanded_bubble' ? 'selected' : ''; ?>>Expanded Bubble (Icon + Text)</option>
                    </select>
                    <p class="mc-help-text">Standard Shows A Chat Icon. Expanded Bubble Shows An Icon With Text Label.</p>
                </div>

                <div class="mc-form-group">
                    <label for="position">Position</label>
                    <select id="position" name="position" class="mc-input mc-form-input">
                        <option value="right" <?php echo ($settings['position'] ?? 'right') === 'right' ? 'selected' : ''; ?>>Right</option>
                        <option value="left" <?php echo ($settings['position'] ?? 'right') === 'left' ? 'selected' : ''; ?>>Left</option>
                    </select>
                    <p class="mc-help-text">Which Side of The Screen The Widget Appears on</p>
                </div>

                <div class="mc-form-group">
                    <label for="launcher_text">Launcher Text</label>
                    <input type="text" id="launcher_text" name="launcher_text" class="mc-input mc-form-input"
                           value="<?php echo htmlspecialchars($settings['launcher_text'] ?? ''); ?>"
                           placeholder="Chat With Us">
                    <p class="mc-help-text">Custom Text Shown on The Expanded Bubble. Leave Empty For Default.</p>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Custom Toggle Button -->
<div class="mc-card">
    <div class="mc-card-header">
        <span class="mc-card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
            <i class="fas fa-mouse-pointer"></i>
        </span>
        <h3>Custom Toggle Button</h3>
    </div>
    <div class="mc-card-body">
        <div class="mc-info-box" style="margin-top: 0;">
            <h4><i class="fas fa-info-circle"></i> Open or Close The Widget From Any Element</h4>
            <p>No extra JavaScript is needed. Use either method below:</p>

            <p style="margin-top: 12px; font-weight: 600;">Method 1: Link With <code>#chatbox-toggle</code> (Recommended For Links)</p>
            <pre style="background: #e0f2fe; padding: 10px 14px; border-radius: 8px; margin: 8px 0; overflow-x: auto; font-size: 12px; line-height: 1.6;"><code>&lt;a href="#chatbox-toggle"&gt;Chat With Us&lt;/a&gt;
&lt;a href="#chatbox-toggle"&gt;Need Help?&lt;/a&gt;</code></pre>

            <p style="font-weight: 600;">Method 2: CSS Class <code>chatbox-toggle</code> (For Buttons and Elements)</p>
            <pre style="background: #e0f2fe; padding: 10px 14px; border-radius: 8px; margin: 8px 0; overflow-x: auto; font-size: 12px; line-height: 1.6;"><code>&lt;button class="chatbox-toggle"&gt;Chat With Us&lt;/button&gt;
&lt;div class="chatbox-toggle"&gt;Support&lt;/div&gt;</code></pre>

            <p>Both methods work with any theme — use <code>#chatbox-toggle</code> as the link URL, or add <code>chatbox-toggle</code> as a CSS class to any button or element in your WHMCS template.</p>
        </div>
    </div>
</div>

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
