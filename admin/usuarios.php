<?php
session_start();
if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /index.php");
    exit;
}
require_once '../includes/db.php';
require_once '../includes/header.php';

// Cambia esta línea para incluir a todos los clientes y usuarios
$usuarios = $pdo->query("SELECT * FROM usuarios")->fetchAll();

// Calcular estadísticas
$total_usuarios = count($usuarios);
$admins = array_filter($usuarios, function($u) { return $u['rol'] === 'admin'; });
// Incluye tanto 'cliente' como 'user' como clientes
$clientes = array_filter($usuarios, function($u) { return $u['rol'] === 'cliente' || $u['rol'] === 'usuario'; });
$count_admins = count($admins);
$count_clientes = count($clientes);
?>

<link rel="stylesheet" href="/assets/css/usuarios.css">

<div class="admin-container">
    <div class="page-header">
        <h2>Gestión de Clientes</h2>
        <p class="page-subtitle">Administra todos los clientes registrados en la tienda</p>
    </div>

    <!-- Estadísticas de usuarios -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <span class="stat-number"><?= $total_usuarios ?></span>
                <span class="stat-label">Total Clientes</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🛡️</div>
            <div class="stat-info">
                <span class="stat-number"><?= $count_admins ?></span>
                <span class="stat-label">Administradores</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-info">
                <span class="stat-number"><?= $count_clientes ?></span>
                <span class="stat-label">Clientes</span>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="filters-card">
        <div class="filters-section">
            <div class="search-group">
                <input type="text" id="buscar-usuario" placeholder="Buscar clientes por email o UID..." class="search-input">
            </div>
            
            <div class="filter-group">
                <select id="filtro-rol" class="filter-select">
                    <option value="">Todos los tipos</option>
                    <option value="admin">Administradores</option>
                    <option value="cliente">Clientes</option>
                    <option value="user">Usuarios</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select id="ordenar-por" class="filter-select">
                    <option value="id">Ordenar por ID</option>
                    <option value="email">Ordenar por Email</option>
                    <option value="rol">Ordenar por Rol</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="users-card">
        <div class="card-header">
            <h3>Lista de Clientes</h3>
            <p><?= $total_usuarios ?> cliente<?= $total_usuarios !== 1 ? 's' : '' ?> registrado<?= $total_usuarios !== 1 ? 's' : '' ?> en total</p>
        </div>
        
        <div class="card-content">
            <?php if (empty($usuarios)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h4>No hay clientes registrados</h4>
                    <p>Los clientes aparecerán aquí cuando se registren en la plataforma</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="users-table" id="tabla-usuarios">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Identificación</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr class="user-row" 
                                    data-email="<?= strtolower(htmlspecialchars($u['email'])) ?>"
                                    data-uid="<?= strtolower(htmlspecialchars($u['firebase_uid'])) ?>"
                                    data-rol="<?= strtolower($u['rol']) ?>"
                                    data-id="<?= $u['id'] ?>">
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <span class="avatar-text"><?= strtoupper(substr($u['email'], 0, 2)) ?></span>
                                            </div>
                                            <div class="user-details">
                                                <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                                                <span class="user-id">ID: #<?= $u['id'] ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="uid-info">
                                            <code class="firebase-uid" title="<?= htmlspecialchars($u['firebase_uid']) ?>">
                                                <?= substr(htmlspecialchars($u['firebase_uid']), 0, 12) ?>...
                                            </code>
                                            <button class="btn-copy-uid" onclick="copiarUID('<?= htmlspecialchars($u['firebase_uid']) ?>')" title="Copiar UID completo">
                                                📋
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?= strtolower($u['rol']) ?>">
                                            <?php if ($u['rol'] === 'admin'): ?>
                                                🛡️ Administrador
                                            <?php elseif ($u['rol'] === 'cliente' || $u['rol'] === 'user'): ?>
                                                🛒 Cliente
                                            <?php else: ?>
                                                👤 <?= ucfirst($u['rol']) ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-active">
                                            ✅ Activo
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-view" title="Ver detalles" onclick="verDetalles(<?= $u['id'] ?>)">
                                                👁️
                                            </button>
                                            <?php if ($u['rol'] !== 'admin' || $count_admins > 1): ?>
                                                <button class="btn-edit" title="Editar rol" onclick="editarRol(<?= $u['id'] ?>, '<?= $u['rol'] ?>')">
                                                    ✏️
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($u['firebase_uid'] !== $_SESSION['firebase_uid']): ?>
                                                <button class="btn-delete" title="Eliminar usuario" onclick="eliminarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email']) ?>')">
                                                    🗑️
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="info-cards-grid">
        <div class="info-card">
            <div class="info-header">
                <h4>🔐 Seguridad</h4>
            </div>
            <div class="info-content">
                <p>Los usuarios se autentican mediante Firebase Authentication. Los UIDs son únicos y seguros.</p>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-header">
                <h4>👑 Roles</h4>
            </div>
            <div class="info-content">
                <p><strong>Admin:</strong> Acceso completo al panel<br>
                <strong>Cliente:</strong> Acceso a la tienda</p>
            </div>
        </div>
    </div>
</div>


<script>
// Filtrado y búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const buscarInput = document.getElementById('buscar-usuario');
    const filtroRol = document.getElementById('filtro-rol');
    const ordenarPor = document.getElementById('ordenar-por');
    const filas = document.querySelectorAll('.user-row');

    function filtrarUsuarios() {
        const textoBusqueda = buscarInput.value.toLowerCase();
        const rolSeleccionado = filtroRol.value.toLowerCase();

        filas.forEach(fila => {
            const email = fila.dataset.email;
            const uid = fila.dataset.uid;
            const rol = fila.dataset.rol;

            let mostrar = true;

            // Filtro de búsqueda
            if (textoBusqueda && !email.includes(textoBusqueda) && !uid.includes(textoBusqueda)) {
                mostrar = false;
            }

            // Filtro por rol
            if (rolSeleccionado && rol !== rolSeleccionado) {
                mostrar = false;
            }

            fila.style.display = mostrar ? '' : 'none';
        });
    }

    function ordenarTabla() {
        const tbody = document.querySelector('#tabla-usuarios tbody');
        const filasArray = Array.from(filas);
        const criterio = ordenarPor.value;

        filasArray.sort((a, b) => {
            let valorA, valorB;
            
            switch(criterio) {
                case 'email':
                    valorA = a.dataset.email;
                    valorB = b.dataset.email;
                    break;
                case 'rol':
                    valorA = a.dataset.rol;
                    valorB = b.dataset.rol;
                    break;
                default: // id
                    valorA = parseInt(a.dataset.id);
                    valorB = parseInt(b.dataset.id);
            }

            if (valorA < valorB) return -1;
            if (valorA > valorB) return 1;
            return 0;
        });

        filasArray.forEach(fila => tbody.appendChild(fila));
    }

    buscarInput.addEventListener('input', filtrarUsuarios);
    filtroRol.addEventListener('change', filtrarUsuarios);
    ordenarPor.addEventListener('change', ordenarTabla);
});

// Funciones de acción
function copiarUID(uid) {
    navigator.clipboard.writeText(uid).then(() => {
        mostrarNotificacion('UID copiado al portapapeles', 'success');
    });
}

function verDetalles(id) {
    mostrarNotificacion('Función de detalles en desarrollo', 'info');
}

function editarRol(id, rolActual) {
    const nuevoRol = prompt(`Cambiar rol del usuario (actual: ${rolActual}):\n\nOpciones: admin, cliente, user`, rolActual);
    if (nuevoRol && nuevoRol !== rolActual) {
        mostrarNotificacion(`Rol cambiado a: ${nuevoRol}`, 'success');
        // Aquí iría la lógica para actualizar el rol en la base de datos
    }
}

function eliminarUsuario(id, email) {
    if (confirm(`¿Estás seguro de que deseas eliminar al usuario?\n\nEmail: ${email}\n\nEsta acción no se puede deshacer.`)) {
        mostrarNotificacion('Usuario eliminado', 'success');
        // Aquí iría la lógica para eliminar el usuario
    }
}

function mostrarNotificacion(mensaje, tipo) {
    const notificacion = document.createElement('div');
    notificacion.className = `notification ${tipo}`;
    notificacion.textContent = mensaje;
    
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        notificacion.remove();
    }, 3000);
}
</script>

<?php include '../includes/footer.php'; ?>
