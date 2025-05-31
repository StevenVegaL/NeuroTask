<?php
// index.php (versión actualizada con nueva paleta de colores)
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>NeuroTask - Gestión de Proyectos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Google Fonts - Montserrat -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      /* Nueva paleta de colores */
      --orange-dark:rgb(201, 127, 247);
      --orange-primary: #688db9;
      --orange-light: #789dca;
      --blue-primary:rgb(199, 155, 88);
      --blue-light:rgb(233, 182, 110);
      
      /* Colores complementarios */
      --white: #ffffff;
      --light-gray: #f5f6f8;
      --text-dark: #333333;
      --text-gray: #777777;
    }

    body {
      font-family: 'Montserrat', sans-serif;
      color: var(--text-dark);
      background-color: var(--light-gray);
    }

    /* Navbar styles */
    .navbar-custom {
      background-color: var(--orange-primary);
      padding: 15px 0;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
      font-weight: 600;
      font-size: 1.5rem;
    }

    .navbar-dark .navbar-nav .nav-link {
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
      padding: 10px 15px;
      transition: all 0.3s ease;
    }

    .navbar-dark .navbar-nav .nav-link:hover {
      color: var(--white);
      background-color: rgba(255, 255, 255, 0.1);
      border-radius: 8px;
    }

    .btn-custom-light {
      background-color: var(--white);
      color: var(--orange-dark);
      border: none;
      font-weight: 600;
      padding: 10px 20px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .btn-custom-light:hover {
      background-color: rgba(255, 255, 255, 0.9);
      color: var(--orange-primary);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-custom-outline {
      background-color: transparent;
      color: var(--white);
      border: 2px solid var(--white);
      font-weight: 600;
      padding: 8px 20px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .btn-custom-outline:hover {
      background-color: var(--white);
      color: var(--orange-primary);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Hero section styles */
    .hero-section {
      background: linear-gradient(135deg, var(--orange-primary) 0%, var(--orange-light) 50%, var(--orange-dark) 100%);
      color: var(--white);
      padding: 100px 0;
      min-height: 85vh;
      display: flex;
      align-items: center;
    }

    .hero-title {
      font-size: 3.2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      line-height: 1.2;
    }

    .hero-subtitle {
      font-size: 1.4rem;
      margin-bottom: 2.5rem;
      font-weight: 300;
      color: rgba(255, 255, 255, 0.9);
    }

    .btn-hero-primary {
      background-color: var(--white);
      color: var(--orange-dark);
      border: none;
      font-weight: 600;
      padding: 12px 28px;
      border-radius: 8px;
      font-size: 1.1rem;
      transition: all 0.3s;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-hero-primary:hover {
      background-color: rgba(255, 255, 255, 0.9);
      color: var(--orange-primary);
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-hero-outline {
      background-color: transparent;
      color: var(--white);
      border: 2px solid var(--white);
      font-weight: 600;
      padding: 12px 28px;
      border-radius: 8px;
      font-size: 1.1rem;
      transition: all 0.3s;
    }

    .btn-hero-outline:hover {
      background-color: rgba(255, 255, 255, 0.2);
      color: var(--white);
      transform: translateY(-3px);
    }

    /* Feature section styles */
    .features-section {
      padding: 80px 0;
      background-color: var(--light-gray);
    }
    
    .section-title {
      font-weight: 700;
      margin-bottom: 15px;
      font-size: 2.5rem;
      color: var(--text-dark);
      text-align: center;
    }

    .section-subtitle {
      color: var(--text-gray);
      text-align: center;
      margin-bottom: 40px;
      font-size: 1.2rem;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .feature-columns {
      display: flex;
      flex-wrap: nowrap;
      justify-content: space-between;
      gap: 15px;
      margin-bottom: 30px;
    }

    .feature-column {
      flex: 1;
      max-width: 23%; /* Para que quepan 4 en una línea con el gap */
      margin-bottom: 20px;
    }

    .feature-card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 20px 15px;
      margin-bottom: 0;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      height: 100%;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border-top: 4px solid transparent;
    }
    
    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
      border-top: 4px solid var(--orange-primary);
    }

    .feature-icon {
      font-size: 2rem;
      color: var(--orange-primary);
      background-color: rgba(104, 141, 185, 0.1);
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
      transition: all 0.3s ease;
    }

    .feature-card:hover .feature-icon {
      background-color: var(--orange-primary);
      color: var(--white);
      transform: rotateY(180deg);
    }

    .feature-title {
      font-size: 1.1rem;
      margin-bottom: 10px;
      font-weight: 600;
      color: var(--text-dark);
    }

    .feature-description {
      font-size: 0.9rem;
      line-height: 1.4;
      color: var(--text-gray);
    }
    
    .cta-btn {
      background-color: var(--orange-primary);
      color: var(--white);
      border: none;
      font-weight: 600;
      padding: 15px 40px;
      border-radius: 8px;
      font-size: 1.1rem;
      transition: all 0.3s;
      box-shadow: 0 4px 12px rgba(104, 141, 185, 0.3);
    }

    .cta-btn:hover {
      background-color: var(--orange-dark);
      color: var(--white);
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(104, 141, 185, 0.4);
    }

    /* Footer styles */
    .footer {
      background-color: var(--blue-primary);
      color: var(--white);
      padding: 60px 0 30px;
    }

    .footer a {
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: color 0.2s;
    }

    .footer a:hover {
      color: var(--white);
      text-decoration: underline;
    }

    .footer h5 {
      color: var(--white);
      font-weight: 600;
      margin-bottom: 20px;
      font-size: 1.2rem;
    }

    .footer ul {
      padding-left: 0;
    }

    .footer ul li {
      margin-bottom: 10px;
    }

    .footer-border-top {
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 30px;
      margin-top: 30px;
    }

    .social-icons {
      display: flex;
      gap: 15px;
    }

    .social-icon {
      background-color: rgba(255, 255, 255, 0.1);
      color: var(--white);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s;
    }

    .social-icon:hover {
      background-color: var(--white);
      color: var(--blue-primary);
      transform: translateY(-3px);
    }

    /* Animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .animated {
      animation: fadeInUp 0.6s ease-out forwards;
    }

    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    .delay-4 { animation-delay: 0.4s; }

    /* Responsive styles */
    @media (max-width: 992px) {
      .hero-title {
        font-size: 2.5rem;
      }
      .hero-subtitle {
        font-size: 1.2rem;
      }
      .hero-section {
        padding: 80px 0;
      }
      .feature-columns {
        flex-wrap: wrap;
      }
      .feature-column {
        max-width: 48%;
        flex: 0 0 48%;
      }
    }

    @media (max-width: 768px) {
      .hero-section {
        text-align: center;
        padding: 60px 0;
        min-height: auto;
      }
      .hero-title {
        font-size: 2rem;
      }
      .hero-actions {
        justify-content: center;
      }
      .feature-card {
        padding: 20px 15px;
      }
      .section-title {
        font-size: 2rem;
      }
      .footer {
        text-align: center;
      }
      .social-icons {
        justify-content: center;
        margin-bottom: 30px;
      }
    }

    @media (max-width: 576px) {
      .hero-title {
        font-size: 1.8rem;
      }
      .hero-subtitle {
        font-size: 1rem;
      }
      .btn-hero-primary, .btn-hero-outline {
        width: 100%;
        margin-bottom: 10px;
      }
      .feature-column {
        max-width: 100%;
        flex: 0 0 100%;
      }
      .section-title {
        font-size: 1.8rem;
      }
      .section-subtitle {
        font-size: 1rem;
        margin-bottom: 30px;
      }
      .cta-btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container">
      <a class="navbar-brand" href="#">
        <i class="fas fa-tasks me-2"></i> NeuroTask
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <!-- Elementos de navegación eliminados según lo solicitado -->
        </ul>
        <div class="d-flex">
          <?php if(isset($_SESSION['usuario'])): ?>
            <a href="dashboard.php" class="btn btn-custom-light me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-custom-outline">Cerrar sesión</a>
          <?php else: ?>
            <a href="login.php" class="btn btn-custom-light me-2">Iniciar sesión</a>
            <a href="registro.php" class="btn btn-custom-outline">Registrarse</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6">
          <h1 class="hero-title">Organiza tu trabajo, potencia tu productividad</h1>
          <p class="hero-subtitle">NeuroTask te ayuda a gestionar proyectos, organizar tareas y colaborar con tu equipo, todo en un solo lugar.</p>
          <div class="d-flex flex-wrap gap-3 hero-actions">
            <a href="registro.php" class="btn btn-hero-primary mb-2">Comenzar gratis</a>
            <a href="#features" class="btn btn-hero-outline mb-2">Conoce más</a>
          </div>
        </div>
        <div class="col-lg-6 text-center d-none d-lg-block">
          <!-- <img src="assets/img/hero-image.svg" alt="NeuroTask Demo" class="img-fluid" style="max-width: 80%;"> -->
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features-section" id="features">
    <div class="container">
      <h2 class="section-title">Haz más con menos esfuerzo</h2>
      <p class="section-subtitle">Herramientas intuitivas para equipos de todos los tamaños, diseñadas para optimizar la colaboración y el seguimiento de proyectos.</p>
      
      <div class="feature-columns">
        <div class="feature-column">
          <div class="feature-card animated delay-1">
            <div class="feature-icon">
              <i class="fas fa-columns"></i>
            </div>
            <h4 class="feature-title">Tableros Kanban</h4>
            <p class="feature-description">Visualiza tus tareas en tableros intuitivos. Arrastra y suelta para cambiar estados fácilmente.</p>
          </div>
        </div>
        
        <div class="feature-column">
          <div class="feature-card animated delay-2">
            <div class="feature-icon">
              <i class="fas fa-users"></i>
            </div>
            <h4 class="feature-title">Trabajo en equipo</h4>
            <p class="feature-description">Colabora en tiempo real. Asigna tareas y mantén a todos informados del progreso.</p>
          </div>
        </div>
        
        <div class="feature-column">
          <div class="feature-card animated delay-3">
            <div class="feature-icon">
              <i class="fas fa-tasks"></i>
            </div>
            <h4 class="feature-title">Gestión de tareas</h4>
            <p class="feature-description">Crea, organiza y prioriza tus tareas con fechas límite y seguimiento visual.</p>
          </div>
        </div>
        
        <div class="feature-column">
          <div class="feature-card animated delay-4">
            <div class="feature-icon">
              <i class="fas fa-chart-line"></i>
            </div>
            <h4 class="feature-title">Analítica avanzada</h4>
            <p class="feature-description">Obtén métricas detalladas sobre el rendimiento de tu equipo y proyectos.</p>
          </div>
        </div>
      </div>
      
      <div class="text-center mt-5">
        <a href="registro.php" class="cta-btn">¡Comienza ahora mismo!</a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 mb-4 mb-lg-0">
          <h5>NeuroTask</h5>
          <p>Una plataforma completa para gestionar proyectos y aumentar la productividad de equipos modernos y ágiles.</p>
          <div class="social-icons">
            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
          <h5>Producto</h5>
          <ul class="list-unstyled">
            <li><a href="#">Características</a></li>
            <li><a href="#">Planes</a></li>
            <li><a href="#">Seguridad</a></li>
            <li><a href="#">Actualizaciones</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
          <h5>Recursos</h5>
          <ul class="list-unstyled">
            <li><a href="#">Documentación</a></li>
            <li><a href="#">Guías</a></li>
            <li><a href="#">Soporte</a></li>
            <li><a href="#">API</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
          <h5>Compañía</h5>
          <ul class="list-unstyled">
            <li><a href="#">Acerca de</a></li>
            <li><a href="#">Blog</a></li>
            <li><a href="#">Empleo</a></li>
            <li><a href="#">Contacto</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
          <h5>Legal</h5>
          <ul class="list-unstyled">
            <li><a href="#">Privacidad</a></li>
            <li><a href="#">Términos</a></li>
            <li><a href="#">Cookies</a></li>
          </ul>
        </div>
      </div>
      <div class="text-center footer-border-top">
        <p class="mb-0">&copy; 2025 NeuroTask. Todos los derechos reservados.</p>
      </div>
    </div>
  </footer>

  <!-- Scripts de Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Script para manejar la animación de scroll suave
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });

    // Script para animar los elementos cuando entran en el viewport
    const animateOnScroll = () => {
      const elements = document.querySelectorAll('.animated');
      elements.forEach(element => {
        const position = element.getBoundingClientRect().top;
        const windowHeight = window.innerHeight;
        if(position < windowHeight * 0.8) {
          element.style.opacity = 1;
        }
      });
    };

    // Inicialización
    window.addEventListener('load', () => {
      animateOnScroll();
      window.addEventListener('scroll', animateOnScroll);
    });
  </script>
</body>
</html>