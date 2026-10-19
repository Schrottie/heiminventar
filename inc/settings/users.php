<?php
// /inc/settings/users.php - Benutzerverwaltung
?>
<div class="card shadow-sm">
    <div class="card-header"><i class="fa-solid fa-users me-2"></i>Benutzerverwaltung</div>
    <div class="card-body p-4">
       
        <h6 class="fw-bold mb-3">Registrierte Benutzer</h6>
        <div class="list-group mb-4">
            <?php foreach ($users as $u): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= htmlspecialchars($u['username']) ?></strong>
                        <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?> ms-1"><?= ucfirst($u['role']) ?></span>
                        <?php if ($u['is_default']): ?>
                            <span class="badge bg-warning text-dark ms-1">Default</span>
                        <?php endif; ?>
                        <?php if (!$u['is_active']): ?>
                            <span class="badge bg-outline-danger text-danger border ms-1">Deaktiviert</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-userid="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>" data-role="<?= $u['role'] ?>" title="Benutzer bearbeiten">
                            <i class="fa-solid fa-pen"></i>
                        </button>

                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action_toggle_user" value="1">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="target_status" value="<?= $u['is_active'] ? '0' : '1' ?>">
                            <button type="submit" class="btn btn-sm btn-<?= $u['is_active'] ? 'outline-warning' : 'outline-success' ?>" title="<?= $u['is_active'] ? 'Deaktivieren' : 'Aktivieren' ?>">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                        </form>

                        <?php if (!$u['is_default']): ?>
                            <form method="POST" action="settings.php" onsubmit="return confirm('Benutzer unwiderruflich löschen?');">
                                <input type="hidden" name="action_delete_user" value="1">
                                <input type="hidden" name="delete_user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr>

        <!-- Neuen Benutzer anlegen -->
        <h6 class="fw-bold mb-3">Neuen Benutzer anlegen</h6>
        <form method="POST" action="settings.php" class="row g-2">
            <input type="hidden" name="action_create_user" value="1">
            <div class="col-12 col-md-4">
                <input type="text" class="form-control" name="new_username" placeholder="Benutzername" required>
            </div>
            <div class="col-12 col-md-4">
                <input type="password" class="form-control" name="new_password" placeholder="Passwort" required>
            </div>
            <div class="col-12 col-md-2">
                <select class="form-select" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-plus me-1"></i>Anlegen</button>
            </div>
        </form>

    </div>
</div>
