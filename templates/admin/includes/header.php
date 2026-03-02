<?php
/**
 * Admin Header Template
 *
 * @package    MultilatChatwoot
 */

if (!defined('WHMCS')) {
    die('Access Denied');
}

$currentAction = $_GET['action'] ?? 'general';
?>
<link rel="stylesheet" href="../modules/addons/MultilatChatwoot/assets/css/admin.css?v=<?php echo urlencode($version); ?>">

<script>
function toggleKeyVisibility(inputId) {
    var input = document.getElementById(inputId);
    var icon = input.parentElement.querySelector('.fa-eye, .fa-eye-slash');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        if (icon) icon.className = 'fas fa-eye';
    }
}

function copyToClipboard(inputId) {
    var input = document.getElementById(inputId);
    var originalType = input.type;
    input.type = 'text';
    var text = input.value;
    input.type = originalType;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(showCopyNotification);
    } else {
        input.type = 'text';
        input.select();
        document.execCommand('copy');
        input.type = originalType;
        showCopyNotification();
    }
}

function showCopyNotification() {
    var notification = document.getElementById('copy-notification');
    if (notification) {
        notification.style.display = 'block';
        setTimeout(function() {
            notification.style.display = 'none';
        }, 2000);
    }
}

function mcInitChangeDetection(formId, btnId) {
    var form = document.getElementById(formId);
    var saveBtn = document.getElementById(btnId);
    var initialState = '';

    function getFormState() {
        var formData = new FormData(form);
        var entries = [];
        for (var pair of formData.entries()) {
            entries.push(pair[0] + '=' + pair[1]);
        }
        form.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
            entries.push('cb_' + cb.name + '=' + cb.checked);
        });
        return entries.sort().join('&');
    }

    function updateSaveButton() {
        var currentState = getFormState();
        var hasChanges = currentState !== initialState;
        saveBtn.disabled = !hasChanges;
        if (hasChanges) {
            saveBtn.classList.add('mc-btn-changed');
        } else {
            saveBtn.classList.remove('mc-btn-changed');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initialState = getFormState();
        form.querySelectorAll('.mc-form-input').forEach(function(input) {
            input.addEventListener('change', updateSaveButton);
            input.addEventListener('input', updateSaveButton);
        });
    });
}
</script>

<div class="mc-admin">
    <!-- Header -->
    <div class="mc-header">
        <div class="mc-header-inner">
            <div class="mc-header-left">
                <a href="<?php echo htmlspecialchars($moduleLink); ?>" class="mc-header-brand">
                    <img src="../modules/addons/MultilatChatwoot/assets/images/multilat-logo.png"
                         alt="Multilat"
                         class="mc-header-logo"
                         onerror="this.style.display='none'">
                    <h1 class="mc-header-title">
                        Multilat Chatwoot
                        <span class="mc-header-badge">v<?php echo htmlspecialchars($version); ?></span>
                    </h1>
                </a>
            </div>
            <div class="mc-header-right">
                <a href="https://github.com/multilat/multilat-chatwoot-addon-for-whmcs" target="_blank" class="mc-header-link">
                    <i class="fab fa-github"></i>
                    <span>View on GitHub</span>
                </a>
                <a href="https://multilat.xyz/contact" target="_blank" class="mc-header-link">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Developer</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="mc-nav">
        <div class="mc-nav-inner">
            <a href="<?php echo htmlspecialchars($moduleLink); ?>" class="mc-nav-item<?php echo $currentAction === 'general' ? ' active' : ''; ?>">
                <i class="fas fa-cog"></i>
                General
            </a>
            <a href="<?php echo htmlspecialchars($moduleLink); ?>&action=attributes" class="mc-nav-item<?php echo $currentAction === 'attributes' ? ' active' : ''; ?>">
                <i class="fas fa-user-tag"></i>
                Client Attributes
            </a>
        </div>
    </nav>

    <?php if (!empty($message)): ?>
    <div class="mc-content" style="padding-bottom: 0;">
        <div class="mc-alert mc-alert-<?php echo htmlspecialchars($messageType ?? 'success'); ?>">
            <?php if ($messageType === 'success'): ?>
                <i class="fas fa-check-circle"></i>
            <?php elseif ($messageType === 'danger'): ?>
                <i class="fas fa-exclamation-circle"></i>
            <?php elseif ($messageType === 'warning'): ?>
                <i class="fas fa-exclamation-triangle"></i>
            <?php else: ?>
                <i class="fas fa-info-circle"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="mc-content">

    <!-- Copy Notification Toast -->
    <div id="copy-notification" style="display: none;">
        <i class="fas fa-check"></i> Copied To Clipboard!
    </div>
