<?php
// /inc/settings/security.php - Passwortschutz & Zugriffsbeschränkung
?>
<div class="mb-2">
    <label class="form-label fw-bold">Passwortschutz / Zugriffsbeschränkung</label>
    <div class="row g-3">
        <div class="col-6">
            <input type="radio" class="btn-check auto-submit" name="auth_enabled" id="authDisabled" value="0" <?= (($settings['auth_enabled'] ?? '0') === '0') ? 'checked' : '' ?>>
            <label class="btn btn-outline-secondary w-100 p-3 text-center" for="authDisabled">
                <i class="fa-solid fa-lock-open fa-2x mb-2 text-secondary d-block"></i>Deaktiviert
            </label>
        </div>
        <div class="col-6">
            <input type="radio" class="btn-check auto-submit" name="auth_enabled" id="authEnabled" value="1" <?= (($settings['auth_enabled'] ?? '0') === '1') ? 'checked' : '' ?>>
            <label class="btn btn-outline-secondary w-100 p-3 text-center" for="authEnabled">
                <i class="fa-solid fa-shield-halved fa-2x mb-2 text-danger d-block"></i>Aktiviert
            </label>
        </div>
    </div>
    <div class="form-text mt-2">
        <i class="fa-solid fa-circle-info me-1"></i> Wenn das System öffentlich zugänglich ist (z.B. im Internet), sollte der Passwortschutz aktiviert werden, um unbefugten Zugriff zu verhindern.
    </div>
</div>
