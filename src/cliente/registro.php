<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($nombre) || empty($email) || empty($telefono) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son requeridos']);
        exit;
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'error' => 'Las contraseñas no coinciden']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres']);
        exit;
    }
    
    // Verificar si el email ya existe
    $check_email = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $check_email->bind_param('s', $email);
    $check_email->execute();
    $result = $check_email->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
        exit;
    }
    
    // Crear usuario con rol 'cliente'
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, telefono, password, rol) VALUES (?, ?, ?, ?, 'cliente')");
    $stmt->bind_param('ssss', $nombre, $email, $telefono, $password_hash);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registro exitoso. Ahora puedes iniciar sesión.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al registrar: ' . $conn->error]);
    }
    
    $stmt->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="/logo.ico" type="image/x-icon">
<title>Registro - RepairlyRD</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Open+Sans:wght@300;400;500;600&display=swap">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --navy: #1a2744;
  --blue: #2563a8;
  --blue-light: #3378c8;
  --gray-light: #f5f6f8;
  --gray-mid: #e8eaef;
  --text-dark: #1f2937;
  --gray-text: #6b7280;
}
body {
  font-family: 'Open Sans', sans-serif;
  color: var(--text-dark);
  background: var(--gray-light);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.auth-container {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
  max-width: 450px;
  width: 100%;
  padding: 40px;
}
.auth-header {
  text-align: center;
  margin-bottom: 32px;
}
.auth-logo {
  width: 60px;
  height: 60px;
  background: var(--blue);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  margin: 0 auto 16px;
}
.auth-header h1 {
  font-family: 'Montserrat', sans-serif;
  font-size: 24px;
  font-weight: 800;
  color: var(--navy);
  margin-bottom: 8px;
}
.auth-header p {
  color: var(--gray-text);
  font-size: 14px;
}
.form-group {
  margin-bottom: 20px;
}
.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--navy);
  margin-bottom: 8px;
}
.form-group input {
  width: 100%;
  padding: 12px;
  border: 1px solid var(--gray-mid);
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}
.form-group input:focus {
  outline: none;
  border-color: var(--blue);
}
.auth-btn {
  width: 100%;
  background: var(--blue);
  color: #fff;
  padding: 14px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s;
  font-family: 'Montserrat', sans-serif;
}
.auth-btn:hover {
  background: var(--blue-light);
}
.auth-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 13px;
  color: var(--gray-text);
}
.auth-footer a {
  color: var(--blue);
  text-decoration: none;
  font-weight: 600;
}
.auth-footer a:hover {
  text-decoration: underline;
}
.error-message {
  background: #fee2e2;
  color: #991b1b;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 13px;
  display: none;
}
.success-message {
  background: #f0fdf4;
  color: #16a34a;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 13px;
  display: none;
}
</style>
</head>
<body>
<div class="auth-container">
  <div class="auth-header">
    <div class="auth-logo">⚙️</div>
    <h1>Crear Cuenta</h1>
    <p>Regístrate para gestionar tus reparaciones</p>
  </div>
  
  <div class="error-message" id="error-message"></div>
  <div class="success-message" id="success-message"></div>
  
  <form id="registro-form">
    <div class="form-group">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" required placeholder="Tu nombre completo">
    </div>
    
    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input type="email" id="email" name="email" required placeholder="tu@email.com">
    </div>
    
    <div class="form-group">
      <label for="telefono">Teléfono</label>
      <input type="tel" id="telefono" name="telefono" required placeholder="809-123-4567">
    </div>
    
    <div class="form-group">
      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" required placeholder="Mínimo 6 caracteres">
    </div>
    
    <div class="form-group">
      <label for="confirm_password">Confirmar contraseña</label>
      <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repite tu contraseña">
    </div>
    
    <button type="submit" class="auth-btn">Registrarse</button>
  </form>
  
  <div class="auth-footer">
    ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
  </div>
</div>

<script>
document.getElementById('registro-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const nombre = document.getElementById('nombre').value.trim();
  const email = document.getElementById('email').value.trim();
  const telefono = document.getElementById('telefono').value.trim();
  const password = document.getElementById('password').value;
  const confirm_password = document.getElementById('confirm_password').value;
  
  if (!nombre || !email || !telefono || !password || !confirm_password) {
    showError('Todos los campos son requeridos');
    return;
  }
  
  if (password !== confirm_password) {
    showError('Las contraseñas no coinciden');
    return;
  }
  
  if (password.length < 6) {
    showError('La contraseña debe tener al menos 6 caracteres');
    return;
  }
  
  const formData = new FormData();
  formData.append('nombre', nombre);
  formData.append('email', email);
  formData.append('telefono', telefono);
  formData.append('password', password);
  formData.append('confirm_password', confirm_password);
  
  try {
    const response = await fetch('src/cliente/registro.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      showSuccess(result.message);
      setTimeout(() => {
        window.location.href = 'login.php';
      }, 2000);
    } else {
      showError(result.error);
    }
  } catch (error) {
    showError('Error de conexión. Por favor intenta nuevamente.');
  }
});

function showError(message) {
  const errorDiv = document.getElementById('error-message');
  const successDiv = document.getElementById('success-message');
  errorDiv.textContent = message;
  errorDiv.style.display = 'block';
  successDiv.style.display = 'none';
}

function showSuccess(message) {
  const errorDiv = document.getElementById('error-message');
  const successDiv = document.getElementById('success-message');
  successDiv.textContent = message;
  successDiv.style.display = 'block';
  errorDiv.style.display = 'none';
}
</script>
</body>
</html>
