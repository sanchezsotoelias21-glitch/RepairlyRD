<?php
require_once __DIR__ . '/../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Email y contraseña son requeridos']);
        exit;
    }
    
    // Buscar usuario
    $stmt = $conn->prepare("SELECT id_usuario, nombre, email, password, rol FROM usuarios WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
        exit;
    }
    
    // Verificar que sea cliente
    if ($user['rol'] !== 'cliente') {
        echo json_encode(['success' => false, 'error' => 'Solo clientes pueden acceder desde aquí']);
        exit;
    }
    
    // Iniciar sesión
    $_SESSION['cliente_id'] = $user['id_usuario'];
    $_SESSION['cliente_nombre'] = $user['nombre'];
    $_SESSION['cliente_email'] = $user['email'];
    $_SESSION['cliente_rol'] = $user['rol'];
    
    echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión - RepairlyRD</title>
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
  max-width: 400px;
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
    <h1>Iniciar Sesión</h1>
    <p>Accede a tu cuenta de cliente</p>
  </div>
  
  <div class="error-message" id="error-message"></div>
  <div class="success-message" id="success-message"></div>
  
  <form id="login-form">
    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input type="email" id="email" name="email" required placeholder="tu@email.com">
    </div>
    
    <div class="form-group">
      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" required placeholder="Tu contraseña">
    </div>
    
    <button type="submit" class="auth-btn">Iniciar Sesión</button>
  </form>
  
  <div class="auth-footer">
    ¿No tienes cuenta? <a href="registro.php">Regístrate</a>
  </div>
  
  <div class="auth-footer" style="margin-top: 12px;">
    <a href="paginaclientes.php">← Volver al inicio</a>
  </div>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  
  if (!email || !password) {
    showError('Email y contraseña son requeridos');
    return;
  }
  
  const formData = new FormData();
  formData.append('email', email);
  formData.append('password', password);
  
  try {
    const response = await fetch('login.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      showSuccess(result.message);
      setTimeout(() => {
        window.location.href = 'paginaclientes.php';
      }, 1000);
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
