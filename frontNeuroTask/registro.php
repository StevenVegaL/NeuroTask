<?php
// registro.php con implementación directa
require_once 'includes/api.php';

// Función para registrar usuarios de forma directa (siguiendo el patrón del curl exitoso)
function registro_directo($nombre, $email, $password) {
    $data = [
        "nombre" => $nombre,
        "email" => $email,
        "contrasena" => $password
    ];
    
    $ch = curl_init("http://micro_user:3009/api/user/registro");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['ok' => false, 'error' => $error];
    }
    
    return json_decode($result, true);
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $passwordConfirm = trim($_POST['passwordConfirm']);

    if ($password !== $passwordConfirm) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Usar la implementación directa que sabemos que funciona
        $response = registro_directo($nombre, $email, $password);
        
        if (isset($response['ok']) && $response['ok'] === true) {
            header("Location: login.php?registered=true");
            exit();
        } else {
            $error = $response['error'] ?? "Error en el registro";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Registro - NeuroTask</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Google Fonts - Montserrat -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      /* Nueva paleta de colores */
      --orange-dark: rgba(249, 187, 87, 0.88);
      --orange-primary: rgb(250, 194, 110);
      --bluepurple-light: rgb(166, 128, 255);
      --blue-primary: #688db9;
      --purple: rgb(162, 120, 202);
      
      /* Colores complementarios */
      --white: #ffffff;
      --light-gray: #f5f6f8;
      --text-dark: #333333;
      --text-gray: #777777;
    }
    
    body {
      background: linear-gradient(135deg, var(--blue-primary) 0%, var(--purple) 100%);
      font-family: 'Montserrat', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }
    
    .register-container {
      max-width: 500px;
      width: 100%;
    }
    
    .register-form {
      background-color: var(--white);
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      animation: slideUp 0.5s ease forwards;
    }
    
    @keyframes slideUp {
      from { 
        opacity: 0;
        transform: translateY(30px);
      }
      to { 
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .register-logo {
      text-align: center;
      margin-bottom: 40px;
      color: var(--white);
    }
    
    .register-logo i {
      font-size: 3rem;
      margin-bottom: 15px;
      color: var(--white);
      background-color: var(--orange-dark);
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-left: auto;
      margin-right: auto;
      box-shadow: 0 8px 20px rgba(249, 187, 87, 0.4);
    }
    
    .register-logo h1 {
      font-size: 2rem;
      font-weight: bold;
      margin-top: 10px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-title {
      font-size: 1.75rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 30px;
      text-align: center;
    }
    
    .form-control {
      background-color: var(--light-gray);
      border: 2px solid transparent;
      border-radius: 8px;
      height: 50px;
      padding: 10px 15px;
      font-size: 1rem;
      transition: all 0.3s;
    }
    
    .form-control:focus {
      border-color: var(--orange-primary);
      box-shadow: 0 0 0 0.2rem rgba(250, 194, 110, 0.25);
      background-color: var(--white);
    }
    
    .form-label {
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 8px;
    }
    
    .form-check-input:checked {
      background-color: var(--orange-primary);
      border-color: var(--orange-primary);
    }
    
    .form-check-input:focus {
      border-color: var(--orange-primary);
      box-shadow: 0 0 0 0.2rem rgba(250, 194, 110, 0.25);
    }
    
    .btn-register {
      background: linear-gradient(90deg, var(--orange-dark) 0%, var(--orange-primary) 100%);
      color: var(--text-dark);
      font-weight: 600;
      border: none;
      border-radius: 8px;
      height: 50px;
      margin-top: 10px;
      transition: all 0.3s;
      box-shadow: 0 4px 10px rgba(249, 187, 87, 0.3);
    }
    
    .btn-register:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(249, 187, 87, 0.4);
      background: linear-gradient(90deg, var(--orange-primary) 0%, var(--orange-dark) 100%);
    }
    
    .social-register {
      display: flex;
      gap: 15px;
      margin-bottom: 25px;
    }
    
    .btn-social {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--white);
      border: 1px solid var(--light-gray);
      border-radius: 8px;
      padding: 10px;
      font-weight: 500;
      color: var(--text-dark);
      transition: all 0.3s;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      height: 50px;
    }
    
    .btn-social:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      border-color: var(--orange-primary);
    }
    
    .btn-social i {
      margin-right: 10px;
      font-size: 1.2rem;
    }
    
    .btn-google i {
      color: #DB4437;
    }
    
    .btn-microsoft i {
      color: #00A4EF;
    }
    
    .register-divider {
      text-align: center;
      margin: 25px 0;
      position: relative;
    }
    
    .register-divider:before {
      content: "";
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      border-top: 1px solid var(--light-gray);
      z-index: 1;
    }
    
    .register-divider span {
      position: relative;
      z-index: 2;
      background-color: var(--white);
      padding: 0 15px;
      color: var(--text-gray);
      font-size: 0.9rem;
    }
    
    .register-footer {
      text-align: center;
      margin-top: 30px;
      color: var(--white);
      font-size: 0.95rem;
      animation: fadeIn 0.8s ease forwards;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    .register-footer a {
      color: var(--white);
      text-decoration: none;
      font-weight: 600;
      border-bottom: 1px dashed rgba(255,255,255,0.6);
      transition: all 0.3s;
    }
    
    .register-footer a:hover {
      border-bottom: 1px solid var(--white);
      text-shadow: 0 0 5px rgba(255,255,255,0.5);
    }
    
    .register-footer p {
      margin-bottom: 10px;
    }
    
    .alert {
      border-radius: 8px;
      font-weight: 500;
      animation: shake 0.6s ease-in-out;
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .password-info {
      font-size: 0.85rem;
      color: var(--text-gray);
      margin-top: 5px;
    }
    
    /* Link de términos y condiciones */
    .form-check-label a {
      color: var(--blue-primary);
      font-weight: 500;
      text-decoration: none;
      border-bottom: 1px dashed var(--blue-primary);
      transition: all 0.3s;
    }
    
    .form-check-label a:hover {
      color: var(--purple);
      border-bottom: 1px solid var(--purple);
    }
    
    /* Responsive */
    @media (max-width: 576px) {
      .register-container {
        padding: 0 15px;
      }
      
      .register-form {
        padding: 30px 20px;
      }
      
      .register-logo i {
        width: 70px;
        height: 70px;
        font-size: 2.5rem;
      }
      
      .register-logo h1 {
        font-size: 1.8rem;
      }
      
      .form-title {
        font-size: 1.5rem;
      }
      
      .social-register {
        flex-direction: column;
        gap: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="register-container">
    <div class="register-logo">
      <i class="fas fa-tasks"></i>
      <h1>NeuroTask</h1>
    </div>
    
    <div class="register-form">
      <h2 class="form-title">Crear cuenta</h2>
      
      <?php if($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <div class="social-register">
        <button type="button" class="btn btn-social btn-google">
          <i class="fab fa-google"></i> Google
        </button>
        <button type="button" class="btn btn-social btn-microsoft">
          <i class="fab fa-microsoft"></i> Microsoft
        </button>
      </div>
      
      <div class="register-divider">
        <span>o regístrate con tu email</span>
      </div>
      
      <form method="POST" action="registro.php">
          <div class="mb-3">
              <label for="nombre" class="form-label">Nombre completo</label>
              <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Tu nombre completo" required value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
          </div>
          <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" name="email" id="email" class="form-control" placeholder="tucorreo@ejemplo.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
          </div>
          <div class="mb-3">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" name="password" id="password" class="form-control" placeholder="Crea una contraseña segura" required>
              <p class="password-info">La contraseña debe tener al menos 8 caracteres</p>
          </div>
          <div class="mb-3">
              <label for="passwordConfirm" class="form-label">Confirmar contraseña</label>
              <input type="password" name="passwordConfirm" id="passwordConfirm" class="form-control" placeholder="Repite tu contraseña" required>
          </div>
          <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="termsCheck" required>
              <label class="form-check-label" for="termsCheck">Acepto los <a href="#">Términos y condiciones</a></label>
          </div>
          <button type="submit" class="btn btn-register w-100">Crear cuenta</button>
      </form>
    </div>
    
    <div class="register-footer">
      <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
      <p><a href="index.php">Volver a la página de inicio</a></p>
    </div>
  </div>

  <!-- Scripts de Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>