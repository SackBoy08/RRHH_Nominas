<?php
// $empleado = null|array
// $departamentos, $jornadas, $estados vienen del controller
$isEdit = !empty($empleado);
$title  = $isEdit ? "Editar Empleado" : "Nuevo Empleado";
require __DIR__ . '/../layout/header.php';

$actionUrl = $isEdit
  ? "index.php?route=empleados&action=edit&id=".(int)$empleado['id']
  : "index.php?route=empleados&action=create";

function val($arr, $key, $def=''){ return htmlspecialchars($arr[$key] ?? $def); }
?>

<?php if (!empty($errors ?? null)): ?>
  <div class="alert alert-danger rounded-2xl" role="alert" aria-live="polite">
    <strong>Revisa los campos:</strong>
    <ul class="mb-0">
      <?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card mx-auto">
  <div class="card-body">
    <h4 class="card-title mb-3"><?= $title ?></h4>
    <p class="text-muted-2 mb-4">Completa la información. Los campos marcados con * son obligatorios.</p>

    <form action="<?= $actionUrl ?>" method="post" class="needs-validation" novalidate>

      <!-- Identificación -->
      <div class="mb-3">
        <h6 class="mb-2">Identificación</h6>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Nombre*</label>
            <input type="text" name="nombre" class="form-control" required
                   autocomplete="given-name" placeholder="Ej. Ana"
                   value="<?= val($empleado,'nombre') ?>">
            <div class="invalid-feedback">Ingresa el primer nombre.</div>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Nombre 2</label>
            <input type="text" name="nombre2" class="form-control" autocomplete="additional-name"
                   placeholder="Opcional" value="<?= val($empleado,'nombre2') ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Nombre 3</label>
            <input type="text" name="nombre3" class="form-control" autocomplete="additional-name"
                   placeholder="Opcional" value="<?= val($empleado,'nombre3') ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Apellido*</label>
            <input type="text" name="apellido" class="form-control" required
                   autocomplete="family-name" placeholder="Ej. López"
                   value="<?= val($empleado,'apellido') ?>">
            <div class="invalid-feedback">Ingresa el primer apellido.</div>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Apellido 2</label>
            <input type="text" name="apellido2" class="form-control" placeholder="Opcional"
                   value="<?= val($empleado,'apellido2') ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Apellido de casada</label>
            <input type="text" name="apellido_casada" class="form-control" placeholder="Opcional"
                   value="<?= val($empleado,'apellido_casada') ?>">
          </div>
        </div>
      </div>

      <!-- Documentos & Contacto -->
      <div class="mb-3">
        <h6 class="mb-2">Documentos & contacto</h6>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">DPI*</label>
            <input type="text" name="dpi" class="form-control" required
                   inputmode="numeric" pattern="^\d{13}$" maxlength="13"
                   placeholder="13 dígitos, sin guiones"
                   value="<?= val($empleado,'dpi') ?>">
            <div class="form-text text-muted-2">Ejemplo: 1234567890123</div>
            <div class="invalid-feedback">El DPI debe tener 13 dígitos.</div>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">NIT*</label>
            <input type="text" name="nit" class="form-control" required
                   pattern="^[0-9\-kK]{7,12}$" maxlength="12"
                   placeholder="Ej. 1234567-8"
                   value="<?= val($empleado,'nit') ?>">
            <div class="form-text text-muted-2">Permite dígitos, guión y K/k.</div>
            <div class="invalid-feedback">Ingresa un NIT válido (7–12 caracteres).</div>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Correo electrónico*</label>
            <input type="email" name="correo_electronico" class="form-control" required
                   autocomplete="email" placeholder="nombre@empresa.com"
                   value="<?= val($empleado,'correo_electronico') ?>">
            <div class="invalid-feedback">Ingresa un correo válido.</div>
          </div>
        </div>
      </div>

      <!-- Puesto & Condiciones -->
      <div class="mb-3">
        <h6 class="mb-2">Puesto & condiciones</h6>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Puesto*</label>
            <input type="text" name="puesto" class="form-control" required
                   placeholder="Ej. Analista"
                   value="<?= val($empleado,'puesto') ?>">
            <div class="invalid-feedback">Indica el puesto.</div>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">Departamento*</label>
            <select name="departamento_id" class="form-select" required>
              <option value="">-- Seleccione --</option>
              <?php foreach($departamentos as $d): ?>
                <option value="<?= (int)$d['id'] ?>"
                  <?= (isset($empleado['departamento_id']) && (int)$empleado['departamento_id']==(int)$d['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($d['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Selecciona un departamento.</div>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">Jornada*</label>
            <select name="jornada_id" class="form-select" required>
              <option value="">-- Seleccione --</option>
              <?php foreach($jornadas as $j): ?>
                <option value="<?= (int)$j['id'] ?>"
                  <?= (isset($empleado['jornada_id']) && (int)$empleado['jornada_id']==(int)$j['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($j['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Selecciona una jornada.</div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Fecha de ingreso*</label>
            <input type="date" name="fecha_ingreso" class="form-control" required
                   max="<?= date('Y-m-d') ?>"
                   value="<?= val($empleado,'fecha_ingreso') ?>">
            <div class="invalid-feedback">Selecciona una fecha válida (no futura).</div>
          </div>

          <div class="col-md-4 mb-1">
            <label class="form-label">Salario base (Q)*</label>
            <input type="number" name="salario_base" class="form-control" required
                   step="0.01" min="0" inputmode="decimal" placeholder="Ej. 8000.00"
                   value="<?= val($empleado,'salario_base') ?>">
            <div class="invalid-feedback">Indica el salario base.</div>
            <div class="form-text text-muted-2" id="helpSalario">
              —
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">Estado*</label>
            <select name="id_estado" class="form-select" required>
              <?php foreach($estados as $key=>$label): ?>
                <option value="<?= (int)$key ?>"
                  <?= (isset($empleado['id_estado']) && (int)$empleado['id_estado']==(int)$key) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Selecciona el estado.</div>
          </div>
        </div>
      </div>

      <!-- Acciones -->
      <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-muted-2">
          <small><span class="me-2">•</span> Se guardará en RRHH & Nómina.</small>
        </div>
        <div class="d-flex gap-2">
          <a href="index.php?route=empleados" class="btn btn-outline-primary">Cancelar</a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle"></i> <?= $isEdit ? 'Actualizar' : 'Crear' ?>
          </button>
        </div>
      </div>

      <?php if (!empty($csrf ?? null)): ?>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <?php endif; ?>
    </form>
  </div>
</div>

<script>
  // Bootstrap validation
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  // DPI: bloquear caracteres no numéricos
  const dpi = document.querySelector('input[name="dpi"]');
  if (dpi){
    dpi.addEventListener('input', () => {
      dpi.value = dpi.value.replace(/\D+/g,'').slice(0,13);
    });
  }

  // Preview salario por día/hora
  const sal = document.querySelector('input[name="salario_base"]');
  const help = document.getElementById('helpSalario');
  function updateSalarioHelp(){
    const v = parseFloat(sal.value);
    if (!isNaN(v) && v > 0){
      const dia  = (v/30);
      const hora = (v/30/8);
      help.textContent = `≈ Q${dia.toFixed(2)} por día · Q${hora.toFixed(2)} por hora`;
    } else {
      help.textContent = '—';
    }
  }
  if (sal && help){
    sal.addEventListener('input', updateSalarioHelp);
    updateSalarioHelp();
  }
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
