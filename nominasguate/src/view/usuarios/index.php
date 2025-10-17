<?php
$title = "Usuarios";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-0">Usuarios</h2>
    <small class="text-muted-2">Administra cuentas y roles de acceso</small>
  </div>
  <a href="<?= $BASE ?>/index.php?route=usuarios&action=create" class="btn btn-primary">
    <i class="bi bi-person-plus"></i> Nuevo usuario
  </a>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> rounded-2xl">
    <?= htmlspecialchars($flash['msg'] ?? '') ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead class="table-dark">
        <tr>
          <th style="width:72px">#</th>
          <th>Usuario</th>
          <th>Rol</th>
          <th>Creado</th>
          <th class="text-center" style="width:220px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($usuarios)): ?>
          <tr><td colspan="5" class="text-center py-5 text-muted-2">No hay usuarios.</td></tr>
        <?php else: foreach ($usuarios as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td>
              <form action="<?= $BASE ?>/index.php?route=usuarios&action=role" method="post" class="d-inline">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <select name="rol" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value="usuario" <?= ($u['rol']==='usuario'?'selected':'') ?>>Usuario</option>
                  <option value="admin"   <?= ($u['rol']==='admin'  ?'selected':'') ?>>Admin</option>
                </select>
              </form>
            </td>
            <td><?= htmlspecialchars($u['creado_en'] ?? '') ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalReset<?= (int)$u['id'] ?>">
                <i class="bi bi-key"></i> Reset password
              </button>
              <!-- Modal reset -->
              <div class="modal fade" id="modalReset<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content rounded-2xl">
                    <div class="modal-header">
                      <h5 class="modal-title">Resetear contraseña (<?= htmlspecialchars($u['username']) ?>)</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="<?= $BASE ?>/index.php?route=usuarios&action=reset" method="post">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Nueva contraseña</label>
                          <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Confirmar contraseña</label>
                          <input type="password" name="new_password_confirm" class="form-control" required minlength="6">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Guardar</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <!-- /Modal -->
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
