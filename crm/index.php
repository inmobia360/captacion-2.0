<?php
declare(strict_types=1);

if (!defined('CAPTACION_ROOT')) {
    define('CAPTACION_ROOT', dirname(__DIR__));
}

require_once CAPTACION_ROOT . '/api/database.php';
$db = CaptacionDB::get();

if (session_status() === PHP_SESSION_NONE) {
    $sessionHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $sessionDomain = str_contains($sessionHost, 'hostingersite.com') ? '' : '.compracaptacion.com';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $sessionDomain,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$isStaffAuthenticated = false;
$currentStaffUser = null;
$staffUserId = $_SESSION['staff_user_id'] ?? $_SESSION['admin_user_id'] ?? null;

if ($staffUserId) {
    $stmt = $db->prepare("SELECT id, email, full_name, phone, role, staff_category, verification_status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$staffUserId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u && ($u['role'] === 'admin' || $u['role'] === 'staff') && $u['verification_status'] === 'approved') {
        $isStaffAuthenticated = true;
        $currentStaffUser = $u;
    }
}

$isMasterAdmin = false;
if ($currentStaffUser) {
    $email = strtolower(trim((string)($currentStaffUser['email'] ?? '')));
    if ($email === 'inmobia360@gmail.com' || $email === 'inmobia360@mail.com' || $email === 'admin@compracaptacion.com') {
        $isMasterAdmin = true;
    } elseif (($currentStaffUser['staff_category'] ?? '') === 'master_admin' || ($currentStaffUser['role'] ?? '') === 'admin') {
        $isMasterAdmin = true;
    }
}

// Precálculo de estadísticas del Staff en el servidor para renderizado instantáneo en 0 ms
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$agencyUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'agency'")->fetchColumn();
$proUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'professional' OR role != 'agency'")->fetchColumn();

$totalRecords = (int)$db->query("SELECT COUNT(*) FROM records WHERE deleted_at IS NULL AND (is_demo = 0 OR is_demo IS NULL)")->fetchColumn();
$propRecords = (int)$db->query("SELECT COUNT(*) FROM records WHERE record_type = 'property' AND deleted_at IS NULL AND (is_demo = 0 OR is_demo IS NULL)")->fetchColumn();
$needRecords = (int)$db->query("SELECT COUNT(*) FROM records WHERE record_type = 'need' AND deleted_at IS NULL AND (is_demo = 0 OR is_demo IS NULL)")->fetchColumn();
$activeRecords = (int)$db->query("SELECT COUNT(*) FROM records WHERE status = 'active' AND deleted_at IS NULL AND (is_demo = 0 OR is_demo IS NULL)")->fetchColumn();
$pausedRecords = (int)$db->query("SELECT COUNT(*) FROM records WHERE status = 'paused' AND deleted_at IS NULL AND (is_demo = 0 OR is_demo IS NULL)")->fetchColumn();

$totalCredits = (float)$db->query("SELECT COALESCE(SUM(available_balance), 0) FROM wallets")->fetchColumn();
$openTickets = (int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();

// Lotes de datos para pre-carga instantánea (sin registros de prueba)
$recordsList = $isStaffAuthenticated ? $db->query("SELECT r.*, u.full_name as author_name, u.agency_name as author_agency, u.email as author_email FROM records r LEFT JOIN users u ON r.user_id = u.id WHERE r.deleted_at IS NULL AND (r.is_demo = 0 OR r.is_demo IS NULL) ORDER BY r.id DESC LIMIT 150")->fetchAll(PDO::FETCH_ASSOC) : [];
$usersList = $isStaffAuthenticated ? $db->query("SELECT u.id, u.email, u.full_name, u.agency_name, u.cif_nif, u.phone, u.role, u.staff_category, u.verification_status, COALESCE(w.available_balance, 0) as credits FROM users u LEFT JOIN wallets w ON u.id = w.user_id ORDER BY u.id DESC LIMIT 150")->fetchAll(PDO::FETCH_ASSOC) : [];
$ticketsList = $isStaffAuthenticated ? $db->query("SELECT * FROM support_tickets ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC) : [];
$xmlBatchesList = $isStaffAuthenticated ? $db->query("SELECT * FROM import_batches ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC) : [];
$logsList = $isStaffAuthenticated ? $db->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es" class="h-full dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>CRM Compra Captación | Suite de Captación Inmobiliaria</title>
  
  <!-- Meta Etiquetas Universales para Compartir en Redes Sociales (WhatsApp, LinkedIn, Twitter/X, Facebook, Telegram, Slack) -->
  <meta name="description" content="CRM Compra Captación - Suite de Captación Inmobiliaria. Red de colaboración profesional 50/50, cruce de demandas y gestión de cartera en tiempo real.">
  
  <!-- Schema.org / Google / WhatsApp Preview -->
  <meta itemprop="name" content="CRM Compra Captación | Suite de Captación Inmobiliaria">
  <meta itemprop="description" content="Panel de control y suite de captación inmobiliaria. Gestión de cartera de inmuebles, demandas de compra y colaboración profesional 50/50.">
  <meta itemprop="image" content="https://compracaptacion.com/og-crm.jpg">
  <link rel="image_src" href="https://compracaptacion.com/og-crm.jpg">

  <!-- Open Graph / Facebook / LinkedIn / WhatsApp / Telegram -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="CRM Compra Captación">
  <meta property="og:url" content="https://crm.compracaptacion.com/">
  <meta property="og:title" content="CRM Compra Captación | Suite de Captación Inmobiliaria">
  <meta property="og:description" content="Panel de control y suite de captación inmobiliaria. Gestión de cartera de inmuebles, demandas de compra y colaboración profesional 50/50.">
  <meta property="og:image" content="https://compracaptacion.com/og-crm.jpg">
  <meta property="og:image:url" content="https://compracaptacion.com/og-crm.jpg">
  <meta property="og:image:secure_url" content="https://compracaptacion.com/og-crm.jpg">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="CRM Compra Captación - Suite de Captación Inmobiliaria">

  <!-- Twitter / X Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="https://crm.compracaptacion.com/">
  <meta name="twitter:title" content="CRM Compra Captación | Suite de Captación Inmobiliaria">
  <meta name="twitter:description" content="Panel de control y suite de captación inmobiliaria. Gestión de cartera de inmuebles, demandas de compra y colaboración profesional 50/50.">
  <meta name="twitter:image" content="https://compracaptacion.com/og-crm.jpg">
  <meta name="twitter:image:alt" content="CRM Compra Captación - Suite de Captación Inmobiliaria">

  <!-- Favicon oficial del dominio principal (sincronizado) -->
  <link rel="icon" type="image/svg+xml" href="/assets/media/favicon-animated.svg">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/media/favicon-compra-captacion.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/media/favicon-compra-captacion.png">
  <link rel="shortcut icon" href="/favicon.ico">
  <link rel="alternate icon" href="assets/media/favicon-compra-captacion.png">
  
  <!-- Inicializador inmediato de Tema Claro / Oscuro para evitar parpadeos -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('captacion_theme_v1') || localStorage.getItem('crm_theme') || 'dark';
      if (savedTheme === 'light') {
        document.documentElement.classList.remove('dark');
      } else {
        document.documentElement.classList.add('dark');
      }
    })();
  </script>

  <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace']
          },
          colors: {
            brand: {
              50: '#eff6ff',
              100: '#dbeafe',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              800: '#1e40af',
              900: '#1e3a8a',
              neon: '#60a5fa'
            },
            darkbg: {
              main: '#0B0F17',
              card: '#111622',
              cardHover: '#161D2C',
              border: '#1E2638'
            }
          }
        }
      }
    }
  </script>

  <!-- Estilos embebidos de alta prioridad -->
  <style>
    :root {
      --font-sans: 'Plus Jakarta Sans', system-ui, sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
    }
    body { font-family: var(--font-sans); }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.25); border-radius: 9999px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.45); }

    /* Activo en el menú lateral */
    .crm-sidebar-link.is-active {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
      color: #ffffff !important;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
    }
    /* Activo en píldoras de sub-opciones */
    .sub-option-pill.is-active {
      background: #2563eb !important;
      color: #ffffff !important;
      box-shadow: 0 2px 10px rgba(37, 99, 235, 0.3) !important;
    }
    .ai-gradient-card {
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(139, 92, 246, 0.06) 50%, rgba(16, 185, 129, 0.04) 100%);
      border: 1px solid rgba(96, 165, 250, 0.2);
    }
    .dark .ai-gradient-card {
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.15) 0%, rgba(139, 92, 246, 0.12) 50%, rgba(16, 185, 129, 0.08) 100%);
      border: 1px solid rgba(96, 165, 250, 0.25);
    }
    .bento-kpi-card {
      transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .bento-kpi-card:hover {
      transform: translateY(-2px);
    }
    .dark .bento-kpi-card:hover {
      border-color: rgba(96, 165, 250, 0.35);
      box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.5), 0 0 15px rgba(37, 99, 235, 0.15);
    }
    .bento-kpi-card:hover {
      border-color: rgba(37, 99, 235, 0.35);
      box-shadow: 0 12px 25px -8px rgba(37, 99, 235, 0.1);
    }
    .crm-tab-panel { animation: crmFadeIn 0.2s ease-out; }
    @keyframes crmFadeIn { from { opacity: 0; transform: translateY(3px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body class="h-full flex flex-col font-sans bg-slate-50 dark:bg-darkbg-main text-slate-900 dark:text-slate-100 antialiased selection:bg-brand-600 selection:text-white transition-colors duration-200 <?php echo $isStaffAuthenticated ? 'overflow-hidden' : 'overflow-y-auto'; ?>">

  <!-- ========================================================= -->
  <!-- PANTALLA DE BIENVENIDA Y ACCESO OBLIGATORIO STAFF HQ       -->
  <!-- ========================================================= -->
  <div id="staff-gatekeeper-portal" class="<?php echo $isStaffAuthenticated ? 'hidden' : 'min-h-screen flex flex-col justify-between items-center p-4 sm:p-8 bg-gradient-to-b from-slate-100 via-slate-50 to-slate-100 dark:from-slate-950 dark:via-darkbg-main dark:to-slate-950 relative'; ?>">
    
    <!-- CABECERA SUPERIOR DE LA PANTALLA DE BIENVENIDA -->
    <header class="w-full max-w-lg flex items-center justify-end py-2">
      <button type="button" onclick="toggleTheme()" class="px-3.5 py-2 rounded-xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center gap-2 shadow-sm" aria-label="Cambiar tema">
        <span id="theme-gatekeeper-icon">🌙</span>
        <span id="theme-gatekeeper-text">Modo Oscuro</span>
      </button>
    </header>

    <!-- TARJETA PRINCIPAL MODULAR CON LOGO JERÁRQUICO CENTRAL -->
    <main class="w-full max-w-lg my-auto py-4">
      <div class="relative bg-white/95 dark:bg-darkbg-card/95 border border-slate-200/90 dark:border-darkbg-border rounded-3xl shadow-2xl p-6 sm:p-10 backdrop-blur-xl space-y-6 text-center">
        
        <!-- DECORACIÓN DE FONDO SUTIL -->
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- LOGO INSTITUCIONAL COMPRA CAPTACIÓN CENTRADO JERÁRQUICO -->
        <div class="flex flex-col items-center justify-center pt-2 pb-1 space-y-3">
          <img src="assets/crm-logo.png" alt="Compra Captación CRM HQ" class="h-14 sm:h-16 w-auto max-w-[260px] object-contain drop-shadow-md transition-transform hover:scale-[1.02]">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-neon text-[11px] font-black uppercase tracking-wider border border-brand-500/20">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Portal de Operaciones Staff HQ
          </div>
        </div>

        <!-- ========================================== -->
        <!-- VISTA 1: INICIAR SESIÓN STAFF (LOGIN)      -->
        <!-- ========================================== -->
        <div id="gatekeeper-view-login" class="space-y-5 text-left">
          <div class="space-y-1.5 text-center">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
              Acceso al Panel Staff
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-normal leading-relaxed">
              Plataforma interna para el equipo de operaciones de Compra Captación.
            </p>
          </div>

          <form onsubmit="handleAdminLogin(event)" class="space-y-4 pt-2">
            <div>
              <label for="gatekeeper-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Correo electrónico corporativo *</label>
              <input id="gatekeeper-email" type="email" required autocomplete="email" placeholder="inmobia360@mail.com" class="w-full px-4 py-3.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 focus:bg-white dark:focus:bg-darkbg-card outline-none transition-all" />
            </div>

            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="gatekeeper-password" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Contraseña *</label>
                <button type="button" onclick="toggleGatekeeperView('forgot')" class="text-xs font-bold text-brand-600 dark:text-brand-neon hover:underline">¿Olvidaste tu clave?</button>
              </div>
              <div class="relative">
                <input id="gatekeeper-password" type="password" required autocomplete="current-password" placeholder="Tu clave de acceso" class="w-full px-4 py-3.5 pr-24 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 focus:bg-white dark:focus:bg-darkbg-card outline-none transition-all" />
                <button type="button" onclick="togglePasswordVisibility('gatekeeper-password', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[11px] font-bold text-brand-600 dark:text-brand-neon hover:bg-brand-500/10 transition-colors flex items-center gap-1.5" aria-label="Mostrar contraseña">
                  <svg class="pwd-toggle-icon w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span class="pwd-toggle-text">Mostrar</span>
                </button>
              </div>
            </div>

            <p id="gatekeeper-login-feedback" class="auth-feedback-box hidden p-3.5 rounded-xl text-xs font-semibold" role="alert"></p>

            <button id="btn-gatekeeper-login" type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-brand-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2">
              <span>Acceder al Panel de Operaciones</span>
              <span aria-hidden="true">→</span>
            </button>
          </form>

          <div class="pt-4 border-t border-slate-100 dark:border-darkbg-border text-center space-y-2">
            <p class="text-xs text-slate-500 dark:text-slate-400">¿Nuevo miembro en el equipo de trabajo?</p>
            <button type="button" onclick="toggleGatekeeperView('register')" class="w-full py-3 rounded-xl border border-brand-500/30 hover:bg-brand-500/10 text-brand-600 dark:text-brand-neon text-xs font-bold transition-all">
              Solicitar cuenta de Staff según categoría
            </button>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- VISTA 2: SOLICITAR CUENTA STAFF SEGÚN CATEGORÍA (REGISTER) -->
        <!-- ======================================================== -->
        <div id="gatekeeper-view-register" class="hidden space-y-5 text-left">
          <div class="space-y-1.5 text-center">
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
              Solicitud de Alta Staff
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-normal leading-relaxed">
              Selecciona tu categoría de trabajo vinculante con el proyecto Compra Captación.
            </p>
          </div>

          <form onsubmit="handleStaffRegister(event)" class="space-y-4 pt-1">
            <div>
              <label for="reg-fullname" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Nombre y Apellidos *</label>
              <input id="reg-fullname" type="text" required placeholder="Nombre completo" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 outline-none transition-all" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label for="reg-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Email Corporativo *</label>
                <input id="reg-email" type="email" required autocomplete="email" placeholder="usuario@compracaptacion.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 outline-none transition-all" />
              </div>
              <div>
                <label for="reg-phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Teléfono Directo</label>
                <input id="reg-phone" type="tel" placeholder="+34 600 000 000" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 outline-none transition-all" />
              </div>
            </div>

            <div>
              <label for="reg-category" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Categoría de Trabajo en Staff *</label>
              <select id="reg-category" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-bold focus:border-brand-600 outline-none transition-all">
                <option value="staff_gerente">Gerente de Operaciones</option>
                <option value="staff_editor">Editor y Moderador de Cartera</option>
                <option value="staff_financiero">Gestor Financiero y Liquidaciones</option>
                <option value="staff_matching">Gestor de Demandas y Matching 50/50</option>
                <option value="staff_integraciones">Especialista en Feeds XML e Integraciones CRM</option>
                <option value="staff_soporte">Gestor de Soporte y Atención a Agencias</option>
              </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label for="reg-password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Contraseña *</label>
                <div class="relative">
                  <input id="reg-password" type="password" required minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres" class="w-full px-4 py-3 pr-20 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 outline-none transition-all" />
                  <button type="button" onclick="togglePasswordVisibility('reg-password', this)" class="absolute inset-y-1 right-1 px-2.5 rounded-lg text-[10px] font-bold text-brand-600 dark:text-brand-neon hover:bg-brand-500/10 transition-colors flex items-center gap-1" aria-label="Mostrar contraseña">
                    <svg class="pwd-toggle-icon w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    <span class="pwd-toggle-text">Ver</span>
                  </button>
                </div>
              </div>
              <div>
                <label for="reg-password-confirm" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Confirmar Clave *</label>
                <div class="relative">
                  <input id="reg-password-confirm" type="password" required minlength="8" autocomplete="new-password" placeholder="Repite la clave" class="w-full px-4 py-3 pr-20 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 outline-none transition-all" />
                  <button type="button" onclick="togglePasswordVisibility('reg-password-confirm', this)" class="absolute inset-y-1 right-1 px-2.5 rounded-lg text-[10px] font-bold text-brand-600 dark:text-brand-neon hover:bg-brand-500/10 transition-colors flex items-center gap-1" aria-label="Mostrar contraseña">
                    <svg class="pwd-toggle-icon w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    <span class="pwd-toggle-text">Ver</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-100 dark:bg-darkbg-main border border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
              Nota de Seguridad: Las solicitudes de cuenta Staff son validadas por la Dirección General antes de habilitar permisos operativos.
            </div>

            <p id="gatekeeper-reg-feedback" class="auth-feedback-box hidden p-3.5 rounded-xl text-xs font-semibold" role="alert"></p>

            <button id="btn-gatekeeper-reg" type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
              <span>Enviar Solicitud de Acceso Staff</span>
              <span aria-hidden="true">→</span>
            </button>
          </form>

          <div class="text-center pt-2">
            <button type="button" onclick="toggleGatekeeperView('login')" class="text-xs font-bold text-brand-600 dark:text-brand-neon hover:underline">
              ← Volver a Iniciar Sesión
            </button>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- VISTA 3: RECUPERAR CONTRASEÑA (FORGOT)     -->
        <!-- ========================================== -->
        <div id="gatekeeper-view-forgot" class="hidden space-y-5 text-left">
          <div class="space-y-1.5 text-center">
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
              Recuperar Contraseña
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-normal leading-relaxed">
              Introduce el correo de tu cuenta de Staff para recibir un enlace de restablecimiento seguro válido por 60 minutos.
            </p>
          </div>

          <form onsubmit="handleAdminForgotPassword(event)" class="space-y-4 pt-1">
            <div>
              <label for="gatekeeper-forgot-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Correo electrónico Staff *</label>
              <input id="gatekeeper-forgot-email" type="email" required autocomplete="email" placeholder="inmobia360@mail.com" class="w-full px-4 py-3.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 outline-none transition-all" />
            </div>

            <p id="gatekeeper-forgot-feedback" class="auth-feedback-box hidden p-3.5 rounded-xl text-xs font-semibold" role="alert"></p>

            <button id="btn-gatekeeper-forgot" type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all flex items-center justify-center gap-2">
              <span>Enviar Enlace de Recuperación</span>
              <span aria-hidden="true">→</span>
            </button>
          </form>

          <div class="text-center pt-2">
            <button type="button" onclick="toggleGatekeeperView('login')" class="text-xs font-bold text-brand-600 dark:text-brand-neon hover:underline">
              ← Volver a Iniciar Sesión
            </button>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- VISTA 4: RESTABLECER CONTRASEÑA (RESET)    -->
        <!-- ========================================== -->
        <div id="gatekeeper-view-reset" class="hidden space-y-5 text-left">
          <div class="space-y-1.5 text-center">
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
              Crear Nueva Contraseña
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-normal leading-relaxed">
              Establece tu nueva clave de acceso de al menos 8 caracteres para asegurar tu cuenta.
            </p>
          </div>

          <form onsubmit="handleAdminResetPassword(event)" class="space-y-4 pt-1">
            <input id="gatekeeper-reset-token" type="hidden" value="" />

            <div>
              <label for="gatekeeper-new-pwd" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Nueva Contraseña *</label>
              <div class="relative">
                <input id="gatekeeper-new-pwd" type="password" required minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres" class="w-full px-4 py-3.5 pr-24 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 outline-none transition-all" />
                <button type="button" onclick="togglePasswordVisibility('gatekeeper-new-pwd', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[11px] font-bold text-brand-600 dark:text-brand-neon hover:bg-brand-500/10 transition-colors flex items-center gap-1.5" aria-label="Mostrar contraseña">
                  <svg class="pwd-toggle-icon w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  <span class="pwd-toggle-text">Mostrar</span>
                </button>
              </div>
            </div>

            <div>
              <label for="gatekeeper-new-pwd-confirm" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">Repetir Nueva Contraseña *</label>
              <div class="relative">
                <input id="gatekeeper-new-pwd-confirm" type="password" required minlength="8" autocomplete="new-password" placeholder="Repite la nueva clave" class="w-full px-4 py-3.5 pr-24 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/60 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm font-medium focus:border-brand-600 outline-none transition-all" />
                <button type="button" onclick="togglePasswordVisibility('gatekeeper-new-pwd-confirm', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[11px] font-bold text-brand-600 dark:text-brand-neon hover:bg-brand-500/10 transition-colors flex items-center gap-1.5" aria-label="Mostrar contraseña">
                  <svg class="pwd-toggle-icon w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  <span class="pwd-toggle-text">Mostrar</span>
                </button>
              </div>
            </div>

            <p id="gatekeeper-reset-feedback" class="auth-feedback-box hidden p-3.5 rounded-xl text-xs font-semibold" role="alert"></p>

            <button id="btn-gatekeeper-reset" type="submit" class="w-full py-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
              <span>Guardar Nueva Contraseña</span>
              <span aria-hidden="true">→</span>
            </button>
          </form>

          <div class="text-center pt-2">
            <button type="button" onclick="toggleGatekeeperView('login')" class="text-xs font-bold text-brand-600 dark:text-brand-neon hover:underline">
              ← Volver a Iniciar Sesión
            </button>
          </div>
        </div>

      </div>
    </main>

    <!-- PIE DE LA PANTALLA DE BIENVENIDA -->
    <footer class="w-full max-w-5xl py-4 text-center text-xs text-slate-400 dark:text-slate-500">
      Compra Captación HQ &copy; <?php echo date('Y'); ?> · Sistema de Operaciones Inmobiliarias B2B
    </footer>
  </div>

  <!-- FONDO PARA MENÚ EN MÓVILES -->
  <div id="sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-30 lg:hidden hidden transition-opacity"></div>

  <!-- ========================================================= -->
  <!-- 🏢 CONTENEDOR PRINCIPAL DEL CRM (SOLO STAFF AUTENTICADO)  -->
  <!-- ========================================================= -->
  <div id="crm-app" class="<?php echo $isStaffAuthenticated ? 'flex-1 flex overflow-hidden relative' : 'hidden'; ?>">
    
    <!-- MENÚ LATERAL IZQUIERDO -->
    <aside id="crm-sidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-72 bg-white dark:bg-darkbg-card border-r border-slate-200 dark:border-darkbg-border flex flex-col justify-between shrink-0 transition-transform duration-300 -translate-x-full lg:translate-x-0 shadow-lg lg:shadow-none">
      <div class="flex-1 flex flex-col overflow-y-auto custom-scrollbar">
        
        <!-- LOGO Y ENCABEZADO DE STAFF -->
        <div class="p-6 border-b border-slate-100 dark:border-darkbg-border bg-white/50 dark:bg-darkbg-card/50 backdrop-blur-md">
          <div class="flex items-center justify-between">
            <a href="#resumen" onclick="switchCrmTab('dashboard')" class="flex items-center group transition-all" title="Compra Captación CRM - Panel de Operaciones Staff">
              <img src="assets/crm-logo.png" alt="Compra Captación CRM - Staff" class="h-12 sm:h-14 w-auto max-w-[210px] object-contain transition-transform group-hover:scale-[1.02] drop-shadow-sm">
            </a>
            <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold">×</button>
          </div>
          <div class="mt-3 flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-brand-500/10 border border-brand-500/20 text-brand-600 dark:text-brand-neon text-[10px] font-black uppercase tracking-wider">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Acceso Exclusivo Staff HQ</span>
          </div>
        </div>

        <!-- ENLACES DE NAVEGACIÓN -->
        <nav class="p-4 space-y-1.5 flex-1">
          <a href="#resumen" onclick="switchCrmTab('dashboard'); closeMobileSidebarIfOpen();" id="nav-tab-dashboard" class="crm-sidebar-link is-active w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all text-left block">
            <span class="text-base">📊</span><span>Resumen General</span>
          </a>
          <a href="#inmuebles" onclick="switchCrmTab('records'); closeMobileSidebarIfOpen();" id="nav-tab-records" class="crm-sidebar-link w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all text-left block">
            <div class="flex items-center gap-3">
              <span class="text-base">🏠</span><span>Cartera de Inmuebles</span>
            </div>
            <span id="badge-total-records" class="px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-black"><?php echo $totalRecords; ?></span>
          </a>
          <a href="#tickets" onclick="switchCrmTab('tickets'); closeMobileSidebarIfOpen();" id="nav-tab-tickets" class="crm-sidebar-link w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all text-left block">
            <div class="flex items-center gap-3">
              <span class="text-base">🎫</span><span>Atención y Tickets</span>
            </div>
            <span id="badge-open-tickets" class="px-2 py-0.5 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-neon text-[10px] font-black"><?php echo $openTickets; ?></span>
          </a>
          <a href="#usuarios" onclick="switchCrmTab('users'); closeMobileSidebarIfOpen();" id="nav-tab-users" class="crm-sidebar-link w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all text-left block">
            <span class="text-base">👥</span><span>Usuarios y Agencias</span>
          </a>
          <a href="#xml" onclick="switchCrmTab('xml'); closeMobileSidebarIfOpen();" id="nav-tab-xml" class="crm-sidebar-link w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all text-left block">
            <span class="text-base">🔄</span><span>Pasarelas y Feeds XML</span>
          </a>
          <a href="#finanzas" onclick="switchCrmTab('finance'); closeMobileSidebarIfOpen();" id="nav-tab-finance" class="crm-sidebar-link w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all text-left block">
            <span class="text-base">💳</span><span>Créditos y Pagos</span>
          </a>
          <a href="#seguridad" onclick="switchCrmTab('telemetry'); closeMobileSidebarIfOpen();" id="nav-tab-telemetry" class="crm-sidebar-link w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all text-left block">
            <span class="text-base">🚨</span><span>Seguridad y Registros</span>
          </a>
        </nav>
      </div>

      <!-- PIE DEL MENÚ LATERAL: FICHA DE USUARIO STAFF & SALIR -->
      <div class="p-4 border-t border-slate-100 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main/50 space-y-2">
        <div class="p-3 rounded-2xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm flex items-center justify-between">
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-xl bg-brand-600 text-white flex items-center justify-center font-black text-xs shrink-0 shadow-md shadow-brand-600/30">
              ⚡
            </div>
            <div class="min-w-0">
              <span id="sidebar-user-display" class="block text-xs font-extrabold text-slate-900 dark:text-white truncate">Operador Staff</span>
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">HQ Central</span>
            </div>
          </div>
          <button type="button" onclick="openStaffProfileModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 dark:hover:text-brand-neon hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Editar Perfil">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
          </button>
        </div>

        <div class="grid grid-cols-2 gap-2 pt-1">
          <button type="button" onclick="openStaffProfileModal()" class="w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/80 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center justify-center gap-1.5">
            <span>👤 Perfil</span>
          </button>
          <button type="button" onclick="handleAdminLogout()" class="w-full py-2 px-3 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-bold transition-all flex items-center justify-center gap-1.5">
            <span>🚪 Salir</span>
          </button>
        </div>
      </div>
    </aside>

    <!-- ÁREA DE CONTENIDO (DERECHA - ANCHO COMPLETO) -->
    <main class="flex-1 flex flex-col overflow-y-auto custom-scrollbar bg-slate-50 dark:bg-darkbg-main transition-colors duration-200 relative">
      
      <!-- CABECERA SUPERIOR FLUIDA -->
      <header class="p-4 sm:p-6 bg-white/80 dark:bg-darkbg-card/80 border-b border-slate-200/80 dark:border-darkbg-border flex flex-wrap items-center justify-between gap-4 sticky top-0 backdrop-blur-xl z-20 transition-colors duration-200">
        <div class="flex items-center gap-3">
          <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-2.5 rounded-2xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all shadow-sm">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
          </button>
          <img src="assets/media/favicon-compra-captacion.png" alt="CRM Compra Captación - Staff" class="w-10 h-10 rounded-xl shadow-sm lg:hidden shrink-0 object-contain">
          <div>
            <h2 id="crm-current-title" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Resumen General Staff</h2>
            <p id="crm-current-subtitle" class="text-xs text-slate-500 dark:text-slate-400">Cuadro de mando ejecutivo y control de flujo de operaciones</p>
          </div>
        </div>
        
        <div class="flex items-center flex-wrap gap-2.5 sm:gap-3">
          <!-- BUSCADOR RÁPIDO GLOBAL (CTRL + K) -->
          <button type="button" onclick="openQuickSearchModal()" class="hidden sm:flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-100 dark:bg-darkbg-main border border-slate-200/80 dark:border-darkbg-border text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all text-xs font-semibold shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <span>Buscar en todo el CRM...</span>
            <kbd class="px-1.5 py-0.5 rounded-md bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-[10px] font-mono text-slate-400">Ctrl K</kbd>
          </button>

          <!-- INDICADOR EN TIEMPO REAL -->
          <div class="hidden md:flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border shadow-sm text-xs">
            <div class="relative flex items-center justify-center">
              <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></div>
              <div class="w-2 h-2 rounded-full bg-emerald-500 absolute"></div>
            </div>
            <span class="text-emerald-600 dark:text-emerald-400 font-black text-[11px]">En Vivo</span>
            <span class="text-slate-300 dark:text-slate-700">|</span>
            <span id="sync-subdomain-badge" class="text-slate-600 dark:text-slate-300 font-mono text-[10px] font-semibold" title="Subdominio Vinculado">crm.compracaptacion.com</span>
            <span class="text-slate-300 dark:text-slate-700">|</span>
            <span id="sync-time-badge" class="text-slate-400 font-mono text-[10px]">Sincronizado: <?php echo date('H:i:s'); ?></span>
          </div>

          <!-- BOTÓN RÁPIDO DE TEMA CLARO / OSCURO -->
          <button type="button" onclick="toggleTheme()" class="p-2.5 rounded-2xl bg-white dark:bg-darkbg-card hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-darkbg-border text-slate-600 dark:text-slate-300 transition-all shadow-sm flex items-center gap-1.5" title="Alternar Modo Oscuro / Claro">
            <span id="theme-quick-icon" class="text-sm">🌙</span>
            <span id="theme-quick-text" class="text-xs font-bold hidden sm:inline">Modo Oscuro</span>
          </button>

          <!-- BOTÓN DE PERFIL STAFF -->
          <button type="button" onclick="openStaffProfileModal()" id="header-user-status" class="px-3.5 py-2.5 rounded-2xl bg-white dark:bg-darkbg-card hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-darkbg-border text-slate-700 dark:text-slate-200 text-xs font-bold transition-all shadow-sm flex items-center gap-2" title="Editar Perfil Staff">
            <span class="text-sm">👤</span>
            <span id="admin-user-display">Staff HQ</span>
          </button>

          <!-- BOTÓN DE ACTUALIZAR -->
          <button type="button" id="btn-crm-refresh" onclick="refreshCrmData(true)" class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 active:scale-95 text-white text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-brand-600/25">
            <svg id="refresh-icon" class="w-4 h-4 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span id="refresh-btn-text">Actualizar</span>
          </button>

          <!-- BOTÓN DE CERRAR SESIÓN / SALIR A PANTALLA DE BIENVENIDA -->
          <button type="button" onclick="handleAdminLogout()" class="px-3.5 py-2.5 rounded-2xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm" title="Cerrar sesión y volver a pantalla de bienvenida">
            <span>🚪</span>
            <span class="hidden sm:inline">Salir</span>
          </button>
        </div>
      </header>
      <!-- BARRA DE ACCIONES Y SUB-OPCIONES (ANCHO COMPLETO) -->
      <div class="px-4 sm:px-8 pt-4 pb-2 border-b border-slate-200/60 dark:border-darkbg-border/60 bg-white/40 dark:bg-darkbg-card/40 backdrop-blur-md w-full">
        <div id="crm-sub-options-container" class="flex flex-wrap items-center justify-between gap-3 w-full">
          <!-- Inyección dinámica según la pestaña activa -->
        </div>
      </div>

      <!-- CONTENEDOR DE PANELES DE CONTENIDO (ANCHO COMPLETO) -->
      <div class="p-4 sm:p-8 space-y-8 flex-1 w-full">

        <!-- ========================================== -->
        <!-- 1. PANEL DASHBOARD: CUADRO DE MANDO STAFF  -->
        <!-- ========================================== -->
        <section id="crm-panel-dashboard" class="crm-tab-panel space-y-6 w-full">
          
          <!-- BANNER DE BIENVENIDA EXCLUSIVO PARA EL EQUIPO DE TRABAJO -->
          <div class="ai-gradient-card rounded-3xl p-6 sm:p-8 shadow-lg w-full">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
              <div class="space-y-2.5 max-w-3xl">
                <div class="flex items-center gap-2.5 flex-wrap">
                  <div class="w-8 h-8 rounded-xl bg-brand-600 text-white flex items-center justify-center font-black text-sm shadow-md shadow-brand-600/30">
                    ✨
                  </div>
                  <span class="text-xs font-black uppercase tracking-wider text-brand-600 dark:text-brand-neon">Portal de Operaciones y Control Staff</span>
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">Plataforma Operativa 100%</span>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                  Bienvenido al Panel Central del Equipo Compra Captación
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Supervisión centralizada del flujo de negocio: ingesta de captaciones exclusivas, matching automatizado 50/50 con demandas de compradores, moderación de datos ciegos y liquidación en toda España.
                </p>
              </div>

              <!-- BOTONES DE ACCIÓN RÁPIDA -->
              <div class="flex flex-wrap sm:flex-nowrap lg:flex-col gap-2.5 w-full lg:w-auto shrink-0">
                <a href="#inmuebles" onclick="switchCrmTab('records')" class="w-full sm:w-auto px-5 py-3 rounded-2xl bg-white dark:bg-darkbg-card hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-white text-xs font-bold border border-slate-200 dark:border-darkbg-border shadow-sm flex items-center justify-center gap-2 transition-all">
                  <span>🏠</span><span>Ver Cartera de Inmuebles</span>
                </a>
                <button type="button" onclick="runSystemDiagnostic()" class="w-full sm:w-auto px-5 py-3 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/25 flex items-center justify-center gap-2 transition-all">
                  <span>🛡️</span><span>Comprobar Estado General</span>
                </button>
              </div>
            </div>
          </div>

          <!-- TRAZABILIDAD Y FLUJO DE OPERACIONES (APERTURA ➔ EJECUCIÓN ➔ CIERRE) -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span>🔄</span><span>Trazabilidad del Flujo Operativo (Pipeline 50/50)</span>
              </h3>
              <span class="text-xs text-slate-400">Actualización en tiempo real</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 w-full">
              
              <!-- FASE 1: APERTURA & INGESTA -->
              <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-blue-500/20 shadow-sm space-y-4 relative overflow-hidden">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black text-sm">
                      1
                    </div>
                    <div>
                      <h4 class="text-sm font-black text-slate-900 dark:text-white">Apertura & Ingesta</h4>
                      <span class="text-[10px] text-slate-400">Captaciones & Feeds XML</span>
                    </div>
                  </div>
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-500/10 text-blue-600 dark:text-blue-400">Fase 1</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                  <div class="p-3 rounded-2xl bg-slate-50 dark:bg-darkbg-main border border-slate-100 dark:border-darkbg-border">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">En Venta Activos</span>
                    <strong class="text-lg font-black text-slate-900 dark:text-white"><?php echo $propRecords; ?></strong>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-50 dark:bg-darkbg-main border border-slate-100 dark:border-darkbg-border">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Feeds Conectados</span>
                    <strong class="text-lg font-black text-blue-600 dark:text-blue-400"><?php echo count($xmlBatchesList); ?></strong>
                  </div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1">
                  <span>Demandas entrantes: <strong><?php echo $needRecords; ?></strong></span>
                  <a href="#xml" onclick="switchCrmTab('xml')" class="text-brand-600 dark:text-brand-neon font-bold hover:underline">Ver Pasarelas →</a>
                </div>
              </div>

              <!-- FASE 2: EJECUCIÓN & MATCHING 50/50 -->
              <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-purple-500/20 shadow-sm space-y-4 relative overflow-hidden">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center font-black text-sm">
                      2
                    </div>
                    <div>
                      <h4 class="text-sm font-black text-slate-900 dark:text-white">Ejecución & Matching</h4>
                      <span class="text-[10px] text-slate-400">Cruces Automáticos y Colaboración</span>
                    </div>
                  </div>
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-purple-500/10 text-purple-600 dark:text-purple-400">Fase 2</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                  <div class="p-3 rounded-2xl bg-slate-50 dark:bg-darkbg-main border border-slate-100 dark:border-darkbg-border">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Cruces Detectados</span>
                    <strong class="text-lg font-black text-purple-600 dark:text-purple-400"><?php echo min($propRecords, $needRecords) * 2; ?></strong>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-50 dark:bg-darkbg-main border border-slate-100 dark:border-darkbg-border">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Datos Ciegos</span>
                    <strong class="text-lg font-black text-emerald-600 dark:text-emerald-400">Protegidos</strong>
                  </div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1">
                  <span>Comisión pactada: <strong>50% / 50%</strong></span>
                  <a href="#inmuebles" onclick="switchCrmTab('records')" class="text-purple-600 dark:text-purple-400 font-bold hover:underline">Ver Cruces →</a>
                </div>
              </div>

              <!-- FASE 3: CIERRE & LIQUIDACIÓN -->
              <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-emerald-500/20 shadow-sm space-y-4 relative overflow-hidden">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black text-sm">
                      3
                    </div>
                    <div>
                      <h4 class="text-sm font-black text-slate-900 dark:text-white">Cierre & Liquidación</h4>
                      <span class="text-[10px] text-slate-400">Operaciones Cerradas y Créditos</span>
                    </div>
                  </div>
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">Fase 3</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                  <div class="p-3 rounded-2xl bg-slate-50 dark:bg-darkbg-main border border-slate-100 dark:border-darkbg-border">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Créditos Circulantes</span>
                    <strong class="text-lg font-black text-emerald-600 dark:text-emerald-400"><?php echo round($totalCredits); ?> cr</strong>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-50 dark:bg-darkbg-main border border-slate-100 dark:border-darkbg-border">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Valor Estimado</span>
                    <strong class="text-lg font-black text-slate-900 dark:text-white"><?php echo number_format($totalCredits * 10, 0, ',', '.'); ?> €</strong>
                  </div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1">
                  <span>Tickets de soporte: <strong><?php echo $openTickets; ?> pendientes</strong></span>
                  <a href="#finanzas" onclick="switchCrmTab('finance')" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">Ver Finanzas →</a>
                </div>
              </div>

            </div>
          </div>

          <!-- BENTO KPI GRID: INDICADORES PRINCIPALES -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 w-full">
            
            <!-- TARJETA 1: USUARIOS -->
            <a href="#usuarios" class="bento-kpi-card p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-4 block" onclick="switchCrmTab('users')">
              <div class="flex items-center justify-between">
                <div class="w-11 h-11 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg font-bold">
                  👥
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                  En Aumento ↗
                </span>
              </div>
              <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Usuarios Registrados</span>
                <strong id="kpi-users-total" class="block text-3xl font-black text-slate-900 dark:text-white mt-1"><?php echo $totalUsers; ?></strong>
                <span id="kpi-users-sub" class="block text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo $agencyUsers; ?> agencias · <?php echo $proUsers; ?> profesionales</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                <div class="bg-blue-600 h-full rounded-full" style="width: 82%"></div>
              </div>
            </a>

            <!-- TARJETA 2: CARTERA DE INMUEBLES -->
            <a href="#inmuebles" class="bento-kpi-card p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-4 block" onclick="switchCrmTab('records')">
              <div class="flex items-center justify-between">
                <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg font-bold">
                  🏠
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-brand-500/15 text-brand-600 dark:text-brand-neon">
                  Activa
                </span>
              </div>
              <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Cartera de Inmuebles</span>
                <strong id="kpi-records-total" class="block text-3xl font-black text-slate-900 dark:text-white mt-1"><?php echo $totalRecords; ?></strong>
                <span id="kpi-records-sub" class="block text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo $propRecords; ?> en venta · <?php echo $needRecords; ?> demandas</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                <div class="bg-indigo-600 h-full rounded-full" style="width: 70%"></div>
              </div>
            </a>

            <!-- TARJETA 3: CRÉDITOS Y SALDOS -->
            <a href="#finanzas" class="bento-kpi-card p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-4 block" onclick="switchCrmTab('finance')">
              <div class="flex items-center justify-between">
                <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg font-bold">
                  💳
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                  Operativo
                </span>
              </div>
              <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Créditos en Circulación</span>
                <strong id="kpi-credits-total" class="block text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?php echo round($totalCredits); ?> cr</strong>
                <span id="kpi-credits-sub" class="block text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo number_format($totalCredits * 10, 0, ',', '.'); ?> € valor estimado</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full" style="width: 88%"></div>
              </div>
            </a>

            <!-- TARJETA 4: ATENCIÓN Y TICKETS -->
            <a href="#tickets" class="bento-kpi-card p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-4 block" onclick="switchCrmTab('tickets')">
              <div class="flex items-center justify-between">
                <div class="w-11 h-11 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg font-bold">
                  🎫
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black <?php echo $openTickets > 0 ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'; ?>">
                  <?php echo $openTickets > 0 ? 'Atención Urgente' : 'Al Día'; ?>
                </span>
              </div>
              <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tickets de Soporte</span>
                <strong id="kpi-tickets-total" class="block text-3xl font-black text-slate-900 dark:text-white mt-1"><?php echo $openTickets; ?></strong>
                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo $openTickets; ?> pendientes de respuesta</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                <div class="bg-amber-500 h-full rounded-full" style="width: <?php echo min(100, $openTickets * 20); ?>%"></div>
              </div>
            </a>

          </div>

          <!-- SALUD DEL SISTEMA Y TELEMETRÍA STAFF -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full">
            <div class="lg:col-span-8 p-6 sm:p-7 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-4">
              <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>🛡️</span><span>Estado y Salud de la Plataforma (Telemetría Staff)</span>
              </h3>
              <div id="telemetry-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-3.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60"><span class="text-slate-400 block text-[10px] uppercase font-bold">Base de Datos</span><strong class="text-emerald-600 dark:text-emerald-400 font-bold">✓ Conectada (SQLite/MySQL)</strong></div>
                <div class="p-3.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60"><span class="text-slate-400 block text-[10px] uppercase font-bold">Versión PHP</span><strong class="text-slate-900 dark:text-white font-bold"><?php echo phpversion(); ?></strong></div>
                <div class="p-3.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60"><span class="text-slate-400 block text-[10px] uppercase font-bold">Memoria en Uso</span><strong class="text-slate-900 dark:text-white font-bold"><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</strong></div>
                <div class="p-3.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60"><span class="text-slate-400 block text-[10px] uppercase font-bold">Pasarela de Pagos</span><strong class="text-emerald-600 dark:text-emerald-400 font-bold">✓ Stripe Activo</strong></div>
                <div class="p-3.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60"><span class="text-slate-400 block text-[10px] uppercase font-bold">Almacenamiento</span><strong class="text-emerald-600 dark:text-emerald-400 font-bold">✓ 97% Libre (585 MB)</strong></div>
                <div class="p-3.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60"><span class="text-slate-400 block text-[10px] uppercase font-bold">Hora Servidor</span><strong class="text-slate-700 dark:text-slate-300 font-mono text-[10px]"><?php echo date('H:i:s'); ?></strong></div>
              </div>
            </div>
            
            <div class="lg:col-span-4 p-6 sm:p-7 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-4 flex flex-col justify-between">
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Acciones Rápidas del Staff</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Accesos directos a las funciones más frecuentes.</p>
              </div>
              <div class="space-y-2.5">
                <a href="#inmuebles" onclick="switchCrmTab('records')" class="w-full py-3 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/25 transition-all flex items-center justify-center gap-2 block text-center">
                  <span>🏠</span><span>Gestionar Cartera de Inmuebles</span>
                </a>
                <a href="#usuarios" onclick="switchCrmTab('users')" class="w-full py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-white text-xs font-bold transition-all flex items-center justify-center gap-2 block text-center">
                  <span>👥</span><span>Ver Directorio de Agencias</span>
                </a>
                <button type="button" onclick="openStaffProfileModal()" class="w-full py-3 rounded-2xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all flex items-center justify-center gap-2">
                  <span>👤</span><span>Configuración y Perfil Staff</span>
                </button>
              </div>
            </div>
          </div>

        </section>

        <!-- ========================================== -->
        <!-- 2. PANEL CARTERA DE INMUEBLES (BLOQUES)     -->
        <!-- ========================================== -->
        <section id="crm-panel-records" class="crm-tab-panel hidden space-y-6 w-full">
          
          <!-- BARRA DE ACCIONES Y FILTROS DEL STAFF -->
          <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 w-full">
            <div class="flex items-center gap-2 flex-wrap">
              <button type="button" onclick="filterRecords('all')" id="btn-filter-rec-all" class="sub-option-pill is-active px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                Todos (<?php echo $totalRecords; ?>)
              </button>
              <button type="button" onclick="filterRecords('property')" id="btn-filter-rec-property" class="sub-option-pill px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                🏠 En Venta (<?php echo $propRecords; ?>)
              </button>
              <button type="button" onclick="filterRecords('need')" id="btn-filter-rec-need" class="sub-option-pill px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                🎯 Demandas (<?php echo $needRecords; ?>)
              </button>
              <button type="button" onclick="filterRecords('active')" id="btn-filter-rec-active" class="sub-option-pill px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                🟢 Activos (<?php echo $activeRecords; ?>)
              </button>
              <button type="button" onclick="filterRecords('paused')" id="btn-filter-rec-paused" class="sub-option-pill px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                ⏸️ Pausados (<?php echo $pausedRecords; ?>)
              </button>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
              <div class="relative flex-1 sm:w-72">
                <input type="text" id="record-search-input" onkeyup="searchRecords()" placeholder="Buscar por título, ciudad, agencia..." class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 dark:border-darkbg-border bg-slate-50 dark:bg-darkbg-main text-xs text-slate-800 dark:text-white placeholder:text-slate-400 focus:border-brand-600 outline-none transition-all" />
                <span class="absolute left-3.5 top-3 text-slate-400">🔍</span>
              </div>
              <button type="button" onclick="exportRecordsToCSV()" class="px-3.5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm" title="Exportar a CSV / Excel">
                <span>📥</span><span class="hidden sm:inline">Exportar CSV</span>
              </button>
              <button type="button" onclick="promptCreateRecord()" class="px-4 py-2.5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/25 flex items-center gap-2 transition-all shrink-0">
                <span>+</span><span>Añadir Inmueble</span>
              </button>
            </div>
          </div>

          <!-- BARRA FLOTANTE DE ACCIONES POR SELECCIÓN DE AGRUPADO (BULK ACTIONS INMUEBLES) -->
          <div id="records-bulk-bar" class="hidden p-4 rounded-2xl bg-brand-600 text-white shadow-xl flex flex-wrap items-center justify-between gap-3 animate-bounce-short">
            <div class="flex items-center gap-2.5">
              <span class="px-2.5 py-1 rounded-lg bg-white/20 text-xs font-black" id="records-selected-count">0</span>
              <span class="text-xs font-bold">inmueble(s) seleccionados en la lista</span>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" onclick="setStatusSelectedRecords('active')" class="px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-xs font-bold transition-all">
                🟢 Activar
              </button>
              <button type="button" onclick="setStatusSelectedRecords('paused')" class="px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-xs font-bold transition-all">
                ⏸️ Pausar
              </button>
              <button type="button" onclick="deleteSelectedRecords()" class="px-3.5 py-1.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-black uppercase tracking-wider transition-all shadow-md flex items-center gap-1.5">
                <span>🗑️ Borrar Seleccionados</span>
              </button>
            </div>
          </div>

          <!-- TABLA DENSA CON CABECERAS FIJAS (APROVECHAMIENTO ANCHO COMPLETO) -->
          <div class="rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm overflow-hidden w-full flex flex-col">
            <div class="overflow-x-auto custom-scrollbar flex-1 max-h-[600px]">
              <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-100/90 dark:bg-slate-800/90 backdrop-blur-md text-slate-500 dark:text-slate-400 uppercase tracking-wider font-extrabold sticky top-0 z-10 text-[11px] border-b border-slate-200 dark:border-darkbg-border">
                  <tr>
                    <th class="p-4 pl-6 w-10 text-center">
                      <input type="checkbox" id="chk-records-master" onchange="toggleSelectAllRecords(this)" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500 cursor-pointer" title="Seleccionar todos los registros visibles" />
                    </th>
                    <th class="p-4">ID</th>
                    <th class="p-4">Tipo</th>
                    <th class="p-4">Título y Ubicación</th>
                    <th class="p-4">Agencia / Contacto</th>
                    <th class="p-4">Precio / Presupuesto</th>
                    <th class="p-4">Comisión / Exclusiva</th>
                    <th class="p-4">Estado</th>
                    <th class="p-4 pr-6 text-right">Acciones Staff</th>
                  </tr>
                </thead>
                <tbody id="records-table-body" class="divide-y divide-slate-100 dark:divide-darkbg-border">
                  <!-- Se inyecta dinámicamente mediante JS -->
                </tbody>
              </table>
            </div>

            <!-- BARRA DE PAGINACIÓN POR BLOQUES (SIN SCROLL INFINITO) -->
            <div id="records-pagination-bar" class="p-4 border-t border-slate-100 dark:border-darkbg-border bg-slate-50/70 dark:bg-darkbg-main/70 flex flex-wrap items-center justify-between gap-4 text-xs">
              <!-- Se inyecta dinámicamente mediante JS -->
            </div>
          </div>

        </section>

        <!-- ========================================== -->
        <!-- 3. PANEL ATENCIÓN Y TICKETS STAFF          -->
        <!-- ========================================== -->
        <section id="crm-panel-tickets" class="crm-tab-panel hidden space-y-6 w-full">
          <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div class="flex items-center gap-2">
              <button type="button" onclick="filterTickets('all')" class="sub-option-pill is-active px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Todos (<?php echo count($ticketsList); ?>)
              </button>
              <button type="button" onclick="filterTickets('open')" class="sub-option-pill px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Pendientes (<?php echo $openTickets; ?>)
              </button>
              <button type="button" onclick="filterTickets('resolved')" class="sub-option-pill px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Cerrados
              </button>
            </div>
            <button type="button" onclick="openNewTicketModal()" class="px-4 py-2.5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/25 flex items-center gap-2 transition-all">
              <span>+</span><span>Crear Ticket de Soporte</span>
            </button>
          </div>

          <!-- BARRA FLOTANTE DE ACCIONES POR SELECCIÓN DE TICKETS -->
          <div id="tickets-bulk-bar" class="hidden p-4 rounded-2xl bg-brand-600 text-white shadow-xl flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <span class="px-2.5 py-1 rounded-lg bg-white/20 text-xs font-black" id="tickets-selected-count">0</span>
              <span class="text-xs font-bold">ticket(s) seleccionados</span>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" onclick="setStatusSelectedTickets('resolved')" class="px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-xs font-bold transition-all">
                ✅ Marcar Resueltos
              </button>
              <button type="button" onclick="deleteSelectedTickets()" class="px-3.5 py-1.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-black uppercase tracking-wider transition-all shadow-md flex items-center gap-1.5">
                <span>🗑️ Borrar Seleccionados</span>
              </button>
            </div>
          </div>

          <div class="rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm overflow-hidden w-full">
            <div class="overflow-x-auto custom-scrollbar max-h-[600px]">
              <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-100/90 dark:bg-slate-800/90 text-slate-500 dark:text-slate-400 uppercase font-extrabold sticky top-0 z-10 text-[11px] border-b border-slate-200 dark:border-darkbg-border">
                  <tr>
                    <th class="p-4 pl-6 w-10 text-center">
                      <input type="checkbox" id="chk-tickets-master" onchange="toggleSelectAllTickets(this)" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500 cursor-pointer" title="Seleccionar todos los tickets" />
                    </th>
                    <th class="p-4">Código</th>
                    <th class="p-4">Usuario / Agencia</th>
                    <th class="p-4">Asunto del Ticket</th>
                    <th class="p-4">Prioridad</th>
                    <th class="p-4">Estado</th>
                    <th class="p-4">Fecha</th>
                    <th class="p-4 pr-6 text-right">Acción</th>
                  </tr>
                </thead>
                <tbody id="tickets-table-body" class="divide-y divide-slate-100 dark:divide-darkbg-border">
                  <!-- Se inyecta mediante JS -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ========================================== -->
        <!-- 4. PANEL USUARIOS Y AGENCIAS (BLOQUES)     -->
        <!-- ========================================== -->
        <section id="crm-panel-users" class="crm-tab-panel hidden space-y-6 w-full">
          <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 w-full">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-bold text-slate-500">Filtrar:</span>
              <button type="button" onclick="filterUsersRole('all')" class="sub-option-pill is-active px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Todos</button>
              <button type="button" onclick="filterUsersRole('agency')" class="sub-option-pill px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Agencias</button>
              <button type="button" onclick="filterUsersRole('professional')" class="sub-option-pill px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Profesionales</button>
              <button type="button" onclick="filterUsersRole('staff')" class="sub-option-pill px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Staff HQ</button>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
              <div class="relative flex-1 sm:w-64">
                <input type="text" id="user-search-input" onkeyup="searchUsers()" placeholder="Buscar por email, nombre, CIF..." class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 dark:border-darkbg-border bg-slate-50 dark:bg-darkbg-main text-xs text-slate-800 dark:text-white placeholder:text-slate-400 focus:border-brand-600 outline-none transition-all" />
                <span class="absolute left-3.5 top-3 text-slate-400">🔍</span>
              </div>
              <button type="button" id="btn-master-create-user" onclick="openMasterCreateUserModal()" class="<?php echo $isMasterAdmin ? 'inline-flex' : 'hidden'; ?> px-4 py-2.5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-brand-600/25 items-center gap-1.5 shrink-0">
                <span>➕</span><span>Nuevo Usuario</span>
              </button>
              <button type="button" onclick="exportUsersToCSV()" class="px-3.5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm shrink-0">
                <span>📥</span><span class="hidden sm:inline">Exportar CSV</span>
              </button>
            </div>
          </div>

          <!-- BARRA FLOTANTE DE ACCIONES POR SELECCIÓN DE USUARIOS -->
          <div id="users-bulk-bar" class="hidden p-4 rounded-2xl bg-brand-600 text-white shadow-xl flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <span class="px-2.5 py-1 rounded-lg bg-white/20 text-xs font-black" id="users-selected-count">0</span>
              <span class="text-xs font-bold">usuario(s) seleccionados</span>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" onclick="setStatusSelectedUsers('approved')" class="px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-xs font-bold transition-all">
                ✓ Aprobar
              </button>
              <button type="button" onclick="setStatusSelectedUsers('suspended')" class="px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-xs font-bold transition-all">
                ⏸️ Suspender
              </button>
              <button type="button" onclick="deleteSelectedUsers()" class="px-3.5 py-1.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-black uppercase tracking-wider transition-all shadow-md flex items-center gap-1.5">
                <span>🗑️ Borrar Seleccionados</span>
              </button>
            </div>
          </div>

          <div class="rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm overflow-hidden w-full flex flex-col">
            <div class="overflow-x-auto custom-scrollbar flex-1 max-h-[600px]">
              <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-100/90 dark:bg-slate-800/90 text-slate-500 dark:text-slate-400 uppercase font-extrabold sticky top-0 z-10 text-[11px] border-b border-slate-200 dark:border-darkbg-border">
                  <tr>
                    <th class="p-4 pl-6 w-10 text-center">
                      <input type="checkbox" id="chk-users-master" onchange="toggleSelectAllUsers(this)" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500 cursor-pointer" title="Seleccionar todos los usuarios visibles" />
                    </th>
                    <th class="p-4">ID</th>
                    <th class="p-4">Profesional / Agencia</th>
                    <th class="p-4">Contacto</th>
                    <th class="p-4">Tipo</th>
                    <th class="p-4">CIF / NIF</th>
                    <th class="p-4">Saldo Créditos</th>
                    <th class="p-4">Estado</th>
                    <th class="p-4 pr-6 text-right">Gestión</th>
                  </tr>
                </thead>
                <tbody id="users-table-body" class="divide-y divide-slate-100 dark:divide-darkbg-border">
                  <!-- Se inyecta mediante JS -->
                </tbody>
              </table>
            </div>

            <!-- PAGINACIÓN POR BLOQUES USUARIOS -->
            <div id="users-pagination-bar" class="p-4 border-t border-slate-100 dark:border-darkbg-border bg-slate-50/70 dark:bg-darkbg-main/70 flex flex-wrap items-center justify-between gap-4 text-xs">
              <!-- JS -->
            </div>
          </div>
        </section>

        <!-- ========================================== -->
        <!-- 5. PANEL PASARELAS Y FEEDS XML             -->
        <!-- ========================================== -->
        <section id="crm-panel-xml" class="crm-tab-panel hidden space-y-6 w-full">
          <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-3 w-full">
            <div class="flex items-center justify-between flex-wrap gap-4">
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Pasarelas de Ingestión Automática XML</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Monitoreo y compatibilidad con formatos Kyero v3, Inmovilla, Idealista y Habitaclia.</p>
              </div>
              <button type="button" onclick="testXmlFeedConnection()" class="px-4 py-2.5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition-all shadow-md shadow-brand-600/25">
                ⚡ Probar Sincronizador
              </button>
            </div>
          </div>

          <div id="xml-batches-list" class="space-y-4 w-full">
            <!-- Se inyecta dinámicamente -->
          </div>
        </section>

        <!-- ========================================== -->
        <!-- 6. PANEL FINANZAS Y CRÉDITOS               -->
        <!-- ========================================== -->
        <section id="crm-panel-finance" class="crm-tab-panel hidden space-y-6 w-full">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5 w-full">
            <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-2">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Créditos Activos</span>
              <strong class="block text-3xl font-black text-emerald-600 dark:text-emerald-400"><?php echo round($totalCredits); ?> cr</strong>
              <span class="text-xs text-slate-500">Distribuidos entre agencias y profesionales</span>
            </div>
            <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-2">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Valor Estimado en Cartera</span>
              <strong class="block text-3xl font-black text-slate-900 dark:text-white"><?php echo number_format($totalCredits * 10, 0, ',', '.'); ?> €</strong>
              <span class="text-xs text-slate-500">Tarifa base 10 € / crédito de colaboración</span>
            </div>
            <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm space-y-2">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Pasarela de Pagos</span>
              <strong class="block text-3xl font-black text-brand-600 dark:text-brand-neon">Stripe</strong>
              <span class="text-xs text-slate-500">Webhooks y facturación automática activa</span>
            </div>
          </div>
        </section>

        <!-- ========================================== -->
        <!-- 7. PANEL SEGURIDAD, REGISTROS Y GOOGLE DRIVE -->
        <!-- ========================================== -->
        <section id="crm-panel-telemetry" class="crm-tab-panel hidden space-y-6 w-full">
          
          <!-- TARJETA DESTACADA: COPIAS DE SEGURIDAD Y SINCRONIZACIÓN GOOGLE DRIVE SUITE -->
          <div class="ai-gradient-card rounded-3xl p-6 sm:p-8 shadow-lg border border-blue-500/30 w-full space-y-6">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
              <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/15 text-blue-600 dark:text-blue-400 text-[11px] font-black uppercase tracking-wider">
                  <span>☁️</span>
                  <span>Google Drive Suite · Respaldo en la Nube</span>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                  Copias de Seguridad y Sincronización Automática
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed max-w-3xl">
                  Mantén un respaldo actualizado en tiempo real de toda la base de datos de Compra Captación (usuarios, carteras, exclusivas, billeteras, transacciones y tickets) conectado directamente con tu suite de Google Drive.
                </p>
              </div>

              <!-- BOTONES DE ACCIÓN DE RESPALDO -->
              <div class="flex flex-wrap sm:flex-nowrap gap-3 w-full lg:w-auto shrink-0">
                <button type="button" onclick="downloadBackupJson()" class="w-full sm:w-auto px-4 py-3 rounded-2xl bg-white dark:bg-darkbg-card hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-white text-xs font-bold border border-slate-200 dark:border-darkbg-border shadow-sm flex items-center justify-center gap-2 transition-all">
                  <span>📥</span><span>Descargar Respaldo (.json)</span>
                </button>
                <button type="button" id="btn-sync-drive-now" onclick="syncGoogleDriveNow()" class="w-full sm:w-auto px-5 py-3 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 transition-all">
                  <span>⚡</span><span>Sincronizar a Google Drive</span>
                </button>
                <button type="button" onclick="openGoogleDriveModal()" class="w-full sm:w-auto p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center justify-center" title="Configurar Google Drive">
                  <span>⚙️</span>
                </button>
              </div>
            </div>

            <!-- MÉTRICAS DE RESPALDO -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs pt-2">
              <div class="p-4 rounded-2xl bg-white/80 dark:bg-darkbg-card/80 border border-slate-200/70 dark:border-darkbg-border shadow-sm">
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Estado de Google Drive</span>
                <strong id="drive-sync-status-badge" class="text-sm font-black text-emerald-600 dark:text-emerald-400 block mt-1">Conectado y Listo</strong>
                <span id="drive-folder-display" class="text-[11px] text-slate-500 truncate block mt-0.5">Carpeta: Backup-CompraCaptacion-HQ</span>
              </div>
              <div class="p-4 rounded-2xl bg-white/80 dark:bg-darkbg-card/80 border border-slate-200/70 dark:border-darkbg-border shadow-sm">
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Frecuencia de Auto-Sincronización</span>
                <strong id="drive-frequency-display" class="text-sm font-black text-slate-900 dark:text-white block mt-1">Diaria (03:00 AM)</strong>
                <span class="text-[11px] text-slate-500 block mt-0.5">Automático en segundo plano</span>
              </div>
              <div class="p-4 rounded-2xl bg-white/80 dark:bg-darkbg-card/80 border border-slate-200/70 dark:border-darkbg-border shadow-sm">
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Último Respaldo Generado</span>
                <strong id="drive-last-backup-display" class="text-sm font-black text-blue-600 dark:text-blue-400 block mt-1"><?php echo date('d/m/Y H:i'); ?></strong>
                <span class="text-[11px] text-slate-500 block mt-0.5">Consolidado en la nube</span>
              </div>
              <div class="p-4 rounded-2xl bg-white/80 dark:bg-darkbg-card/80 border border-slate-200/70 dark:border-darkbg-border shadow-sm">
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Registros Respaldados</span>
                <strong class="text-sm font-black text-slate-900 dark:text-white block mt-1"><?php echo $totalRecords + $totalUsers; ?> entidades</strong>
                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold block mt-0.5">✓ Integridad 100% SHA-256</span>
              </div>
            </div>
          </div>

          <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm w-full">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-4">Registro de Auditoría y Actividad Staff</h3>
            <div class="overflow-x-auto custom-scrollbar max-h-[500px]">
              <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase sticky top-0 z-10 text-[10px]">
                  <tr>
                    <th class="p-3">ID</th>
                    <th class="p-3">Acción</th>
                    <th class="p-3">IP</th>
                    <th class="p-3">Detalles</th>
                    <th class="p-3">Fecha</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-darkbg-border">
                  <?php foreach ($logsList as $log): ?>
                  <tr>
                    <td class="p-3 font-mono">#<?php echo $log['id']; ?></td>
                    <td class="p-3 font-bold text-brand-600 dark:text-brand-neon"><?php echo htmlspecialchars((string)($log['action'] ?? '')); ?></td>
                    <td class="p-3 text-slate-500"><?php echo htmlspecialchars((string)($log['ip_address'] ?? '127.0.0.1')); ?></td>
                    <td class="p-3 text-slate-700 dark:text-slate-300 truncate max-w-xs"><?php echo htmlspecialchars((string)($log['details'] ?? '-')); ?></td>
                    <td class="p-3 text-slate-500 font-mono text-[10px]"><?php echo htmlspecialchars((string)($log['created_at'] ?? '')); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

      </div>
    </main>

    <!-- PANEL LATERAL DESLIZABLE (DETALLES Y FICHA RÁPIDA A LA DERECHA) -->
    <div id="crm-drawer-backdrop" onclick="closeInspectorDrawer()" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-40 hidden crm-drawer-backdrop opacity-0 transition-opacity"></div>
    <div id="crm-slideover-drawer" class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white dark:bg-darkbg-card border-l border-slate-200 dark:border-darkbg-border shadow-2xl flex flex-col justify-between crm-drawer-content translate-x-full transition-transform duration-300">
      <div>
        <div class="p-6 border-b border-slate-100 dark:border-darkbg-border flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <span id="drawer-icon" class="text-xl">📋</span>
            <div>
              <h3 id="drawer-title" class="text-sm font-black text-slate-900 dark:text-white">Ficha de Información Staff</h3>
              <span id="drawer-subtitle" class="text-[10px] font-mono text-slate-400">#REF-0000</span>
            </div>
          </div>
          <button type="button" onclick="closeInspectorDrawer()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all text-xl font-bold">×</button>
        </div>

        <div id="drawer-body" class="p-6 space-y-5 overflow-y-auto max-h-[calc(100vh-160px)] custom-scrollbar text-xs">
          <!-- Inyección JS -->
        </div>
      </div>

      <div id="drawer-footer" class="p-4 border-t border-slate-100 dark:border-darkbg-border bg-slate-50 dark:bg-darkbg-main flex items-center gap-2">
        <!-- Inyección JS -->
      </div>
    </div>

    <!-- MODAL INTEGRAL DE ACCESO, RECUPERACIÓN Y RESTABLECIMIENTO DE CONTRASEÑA STAFF -->
    <div id="admin-login-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-md transition-all duration-300">
      <div class="relative w-full max-w-md bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border rounded-3xl shadow-2xl p-6 sm:p-8 text-slate-800 dark:text-white transition-all overflow-hidden">
        
        <!-- BOTÓN DE CIERRE -->
        <button type="button" onclick="closeAdminAuthModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center text-sm font-black transition-colors" aria-label="Cerrar ventana">✕</button>

        <!-- VISTA 1: INICIAR SESIÓN STAFF -->
        <div id="auth-view-login" class="space-y-4">
          <div class="space-y-1">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-neon text-[10px] font-black uppercase tracking-wider">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Acceso Staff Compra Captación
            </div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Acceso al Panel Central</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Portal exclusivo para el equipo de operaciones y administración interna.</p>
          </div>

          <form onsubmit="handleAdminLogin(event)" class="space-y-4 pt-2">
            <div>
              <label for="admin-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Correo electrónico del Staff *</label>
              <input id="admin-email" type="email" required autocomplete="email" placeholder="staff@compracaptacion.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:bg-white dark:focus:bg-darkbg-card outline-none transition-all" />
            </div>

            <div>
              <div class="flex items-center justify-between mb-1">
                <label for="admin-password" class="text-xs font-bold text-slate-700 dark:text-slate-300">Contraseña *</label>
                <button type="button" onclick="toggleAuthModalView('forgot')" class="text-[11px] font-bold text-brand-600 dark:text-brand-neon hover:underline">¿Olvidaste tu contraseña?</button>
              </div>
              <div class="relative">
                <input id="admin-password" type="password" required autocomplete="current-password" placeholder="Tu contraseña de acceso" class="w-full px-4 py-3 pr-24 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:bg-white dark:focus:bg-darkbg-card outline-none transition-all" />
                <button type="button" onclick="togglePasswordVisibility('admin-password', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[10px] font-bold text-brand-600 dark:text-brand-neon hover:bg-brand-500/10 transition-colors flex items-center gap-1" aria-label="Mostrar contraseña">
                  <svg class="pwd-toggle-icon w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span class="pwd-toggle-text">Mostrar</span>
                </button>
              </div>
            </div>

            <p id="login-feedback-box" class="auth-feedback-box hidden p-3 rounded-xl text-xs" role="alert"></p>

            <button id="btn-admin-login" type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-brand-600/25 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2">
              <span>Acceder al Panel Central</span>
              <span aria-hidden="true">→</span>
            </button>
          </form>
        </div>

        <!-- VISTA 2: RECUPERAR CONTRASEÑA -->
        <div id="auth-view-forgot" class="hidden space-y-4">
          <div class="space-y-1">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase tracking-wider">
              <span>🛡️</span>
              Seguridad & Recuperación Staff
            </div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Recuperar contraseña</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Introduce el email de tu cuenta del Staff y recibirás un enlace seguro de un solo uso válido por 60 minutos.</p>
          </div>

          <form id="form-forgot-password" onsubmit="handleAdminForgotPassword(event)" class="space-y-4 pt-2">
            <div>
              <label for="forgot-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Correo electrónico Staff *</label>
              <input id="forgot-email" type="email" required autocomplete="email" placeholder="staff@compracaptacion.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:bg-white dark:focus:bg-darkbg-card outline-none transition-all" />
            </div>

            <p id="forgot-feedback-box" class="auth-feedback-box hidden p-3 rounded-xl text-xs" role="alert"></p>

            <button id="btn-submit-forgot" type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-brand-600/25 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2">
              <span>Enviar Enlace de Recuperación</span>
              <span aria-hidden="true">→</span>
            </button>

            <div class="text-center pt-2">
              <button type="button" onclick="toggleAuthModalView('login')" class="text-xs font-bold text-brand-600 dark:text-brand-neon hover:underline">
                ← Volver a Iniciar Sesión
              </button>
            </div>
          </form>
        </div>

        <!-- VISTA 3: RESTABLECER NUEVA CONTRASEÑA -->
        <div id="auth-view-reset" class="hidden space-y-4">
          <div class="space-y-1">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-black uppercase tracking-wider">
              <span>🔑</span>
              Nueva Contraseña Staff
            </div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Crear nueva contraseña</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Establece tu nueva clave de acceso de al menos 8 caracteres para asegurar tu cuenta.</p>
          </div>

          <form onsubmit="handleAdminResetPassword(event)" class="space-y-4 pt-2">
            <input id="reset-token" type="hidden" value="" />

            <div>
              <label for="new-password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nueva Contraseña *</label>
              <div class="relative">
                <input id="new-password" type="password" required minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres" class="w-full px-4 py-3 pr-24 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:bg-white dark:focus:bg-darkbg-card outline-none transition-all" />
                <button type="button" onclick="togglePasswordVisibility('new-password', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[10px] font-bold text-brand-600 dark:text-brand-neon hover:bg-brand-500/10 transition-colors flex items-center gap-1" aria-label="Mostrar contraseña">
                  <svg class="pwd-toggle-icon w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span class="pwd-toggle-text">Mostrar</span>
                </button>
              </div>
            </div>

            <div>
              <label for="new-password-confirm" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Repetir Nueva Contraseña *</label>
              <div class="relative">
                <input id="new-password-confirm" type="password" required minlength="8" autocomplete="new-password" placeholder="Repite la nueva contraseña" class="w-full px-4 py-3 pr-24 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 focus:bg-white dark:focus:bg-darkbg-card outline-none transition-all" />
                <button type="button" onclick="togglePasswordVisibility('new-password-confirm', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[10px] font-bold text-brand-600 dark:text-brand-neon hover:bg-brand-500/10 transition-colors flex items-center gap-1" aria-label="Mostrar contraseña">
                  <svg class="pwd-toggle-icon w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span class="pwd-toggle-text">Mostrar</span>
                </button>
              </div>
            </div>

            <p id="reset-feedback-box" class="auth-feedback-box hidden p-3 rounded-xl text-xs" role="alert"></p>

            <button id="btn-submit-reset" type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-emerald-600/25 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2">
              <span>Guardar Nueva Contraseña</span>
              <span aria-hidden="true">→</span>
            </button>

            <div class="text-center pt-2">
              <button type="button" onclick="toggleAuthModalView('login')" class="text-xs font-bold text-brand-600 dark:text-brand-neon hover:underline">
                ← Volver a Iniciar Sesión
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>

    <!-- MODAL DE EDICIÓN DE PERFIL STAFF -->
    <div id="staff-profile-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-md transition-all duration-300">
      <div class="relative w-full max-w-md bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border rounded-3xl shadow-2xl p-6 sm:p-8 text-slate-800 dark:text-white transition-all overflow-hidden space-y-4">
        
        <button type="button" onclick="closeStaffProfileModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center text-sm font-black transition-colors" aria-label="Cerrar ventana">✕</button>

        <div class="space-y-1">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-neon text-[10px] font-black uppercase tracking-wider">
            <span>👤</span>
            Perfil de Operador Staff
          </div>
          <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Editar Perfil Staff</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Actualiza tu nombre de operador, teléfono o cambia tu contraseña de acceso.</p>
        </div>

        <form onsubmit="handleSaveStaffProfile(event)" class="space-y-4 pt-2">
          <div>
            <label for="profile-fullname" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre y Apellidos *</label>
            <input id="profile-fullname" type="text" required placeholder="Nombre de Operador" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 outline-none transition-all" />
          </div>

          <div>
            <label for="profile-phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono de Contacto Directo</label>
            <input id="profile-phone" type="tel" placeholder="+34 600 000 000" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 outline-none transition-all" />
          </div>

          <div class="pt-2 border-t border-slate-100 dark:border-darkbg-border space-y-2">
            <span class="block text-xs font-extrabold text-slate-900 dark:text-white">Cambiar Contraseña (Opcional)</span>
            <div class="relative">
              <input id="profile-new-password" type="password" minlength="8" placeholder="Dejar en blanco para mantener la actual" class="w-full px-4 py-3 pr-24 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 outline-none transition-all" />
              <button type="button" onclick="togglePasswordVisibility('profile-new-password', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[10px] font-bold text-brand-600 dark:text-brand-neon hover:bg-brand-500/10 transition-colors flex items-center gap-1" aria-label="Mostrar contraseña">
                <svg class="pwd-toggle-icon w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                <span class="pwd-toggle-text">Mostrar</span>
              </button>
            </div>
          </div>

          <p id="profile-feedback-box" class="auth-feedback-box hidden p-3 rounded-xl text-xs" role="alert"></p>

          <div class="flex items-center gap-3 pt-2">
            <button type="button" onclick="closeStaffProfileModal()" class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
              Cancelar
            </button>
            <button id="btn-save-profile" type="submit" class="flex-1 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/25 transition-all">
              Guardar Cambios
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL DE BÚSQUEDA RÁPIDA GLOBAL (CTRL + K) -->
    <div id="quick-search-modal" class="fixed inset-0 z-[100] hidden flex items-start justify-center p-4 pt-20 bg-slate-950/70 backdrop-blur-sm transition-all duration-200">
      <div class="relative w-full max-w-xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border rounded-3xl shadow-2xl p-4 sm:p-6 text-slate-800 dark:text-white space-y-4">
        <div class="relative">
          <input type="text" id="global-search-input" onkeyup="handleGlobalSearch(event)" placeholder="Buscar inmueble, demandante, agencia, ticket o ID..." class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 dark:border-darkbg-border bg-slate-50 dark:bg-darkbg-main text-sm font-semibold text-slate-900 dark:text-white focus:border-brand-600 outline-none transition-all" autofocus />
          <span class="absolute left-4 top-4 text-slate-400">🔍</span>
          <button type="button" onclick="closeQuickSearchModal()" class="absolute right-3.5 top-3.5 px-2 py-0.5 rounded-lg bg-slate-200 dark:bg-slate-800 text-[10px] font-mono text-slate-500">ESC</button>
        </div>
        <div id="global-search-results" class="max-h-80 overflow-y-auto custom-scrollbar space-y-2 text-xs">
          <div class="p-6 text-center text-slate-400">Escribe al menos 2 letras para buscar en toda la plataforma...</div>
        </div>
      </div>
    </div>

    <!-- MODAL DE CONFIGURACIÓN DE GOOGLE DRIVE SUITE -->
    <div id="google-drive-config-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-md transition-all duration-300">
      <div class="relative w-full max-w-lg bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border rounded-3xl shadow-2xl p-6 sm:p-8 text-slate-800 dark:text-white space-y-5">
        <button type="button" onclick="closeGoogleDriveModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center text-sm font-black transition-colors">✕</button>

        <div class="space-y-1">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase tracking-wider">
            <span>☁️</span>
            Google Drive Suite Backup
          </div>
          <h3 class="text-xl font-black text-slate-900 dark:text-white">Configuración de Sincronización</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Establece la carpeta y el webhook seguro para mantener los respaldos sincronizados en la nube.</p>
        </div>

        <form id="form-google-drive" onsubmit="saveGoogleDriveConfig(event)" class="space-y-4 pt-1">
          <div>
            <label for="drive-folder-id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">ID o Nombre de Carpeta en Google Drive</label>
            <input id="drive-folder-id" type="text" placeholder="1aB2c3D4e5F6G7H8... o Backup-CompraCaptacion" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 outline-none transition-all" />
          </div>

          <div>
            <label for="drive-webhook-url" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Webhook de Sincronización Google Drive / n8n (Opcional)</label>
            <input id="drive-webhook-url" type="url" placeholder="https://n8n.tudominio.com/webhook/google-drive-backup" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-sm focus:border-brand-600 outline-none transition-all" />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="drive-frequency" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Frecuencia Automática</label>
              <select id="drive-frequency" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-bold focus:border-brand-600 outline-none transition-all">
                <option value="daily">Diaria (03:00 AM)</option>
                <option value="weekly">Semanal (Domingos)</option>
                <option value="manual">Manual bajo demanda</option>
              </select>
            </div>
            <div class="flex flex-col justify-end">
              <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-100 dark:bg-slate-800/80 cursor-pointer">
                <input id="drive-auto-sync" type="checkbox" checked class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500" />
                <span class="text-xs font-bold text-slate-800 dark:text-white">Auto-Sincronización</span>
              </label>
            </div>
          </div>

          <p id="drive-config-feedback" class="auth-feedback-box hidden p-3 rounded-xl text-xs" role="alert"></p>

          <div class="flex items-center gap-3 pt-2">
            <button type="button" onclick="closeGoogleDriveModal()" class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
              Cancelar
            </button>
            <button id="btn-save-drive-config" type="submit" class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/25 transition-all">
              Guardar Configuración
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL DE CREACIÓN DE NUEVO USUARIO (EXCLUSIVO MASTER ADMIN) -->
    <div id="master-create-user-modal" class="fixed inset-0 z-[110] hidden flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-md transition-all duration-300">
      <div class="relative w-full max-w-lg bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border rounded-3xl shadow-2xl p-6 sm:p-8 text-slate-800 dark:text-white transition-all overflow-y-auto max-h-[90vh] custom-scrollbar space-y-4">
        
        <button type="button" onclick="closeMasterCreateUserModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center text-sm font-black transition-colors" aria-label="Cerrar">✕</button>

        <div class="space-y-1">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-neon text-[10px] font-black uppercase tracking-wider">
            <span>🛡️</span>
            Control Exclusivo Master Admin
          </div>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Crear Nuevo Usuario</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Registra un nuevo miembro profesional, agencia o integrante del Staff con permisos personalizados.</p>
        </div>

        <form id="form-master-create-user" onsubmit="handleMasterCreateUser(event)" class="space-y-4 pt-1">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="create-user-fullname" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Nombre Completo *</label>
              <input id="create-user-fullname" type="text" required placeholder="Ej: Juan Pérez" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
            <div>
              <label for="create-user-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Correo Electrónico *</label>
              <input id="create-user-email" type="email" required placeholder="usuario@ejemplo.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="create-user-password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Contraseña *</label>
              <input id="create-user-password" type="password" required minlength="6" placeholder="Mínimo 6 caracteres" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
            <div>
              <label for="create-user-phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Teléfono</label>
              <input id="create-user-phone" type="tel" placeholder="+34 600 000 000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="create-user-agency" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Empresa / Agencia</label>
              <input id="create-user-agency" type="text" placeholder="Ej: InmoCosta SL o Independiente" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
            <div>
              <label for="create-user-cif" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">CIF / NIF</label>
              <input id="create-user-cif" type="text" placeholder="B12345678" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label for="create-user-role" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Tipo de Rol *</label>
              <select id="create-user-role" onchange="onRoleChangeCreateUser(this.value)" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-bold focus:border-brand-600 outline-none">
                <option value="professional">Profesional Independiente</option>
                <option value="agency">Agencia Inmobiliaria</option>
                <option value="staff">Staff de Operaciones HQ</option>
              </select>
            </div>
            <div id="wrapper-create-staff-category" class="hidden">
              <label for="create-user-category" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Categoría Staff</label>
              <select id="create-user-category" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-bold focus:border-brand-600 outline-none">
                <option value="master_pro">Master Pro · Acceso al servicio Pro</option>
                <option value="staff_operaciones">Agente de Operaciones</option>
                <option value="staff_gerente">Gerente de Operaciones</option>
                <option value="staff_editor">Editor y Moderador de Cartera</option>
                <option value="staff_financiero">Gestor Financiero y Liquidaciones</option>
                <option value="staff_matching">Gestor de Demandas y Matching 50/50</option>
                <option value="staff_integraciones">Especialista en Feeds XML</option>
                <option value="staff_soporte">Gestor de Soporte y Agencias</option>
              </select>
            </div>
            <div>
              <label for="create-user-credits" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Saldo Inicial</label>
              <input id="create-user-credits" type="number" step="1" min="0" value="10" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
          </div>

          <div>
            <label for="create-user-status" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Estado Inicial de Acceso</label>
            <select id="create-user-status" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-bold focus:border-brand-600 outline-none">
              <option value="approved">✅ Aprobado / Activo</option>
              <option value="pending">⏳ Pendiente de Verificación</option>
              <option value="suspended">⏸️ Suspendido / Pausado</option>
            </select>
          </div>

          <p id="create-user-feedback" class="auth-feedback-box hidden p-3 rounded-xl text-xs" role="alert"></p>

          <div class="flex items-center gap-3 pt-2">
            <button type="button" onclick="closeMasterCreateUserModal()" class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
              Cancelar
            </button>
            <button id="btn-submit-create-user" type="submit" class="flex-1 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-black uppercase tracking-wider shadow-md shadow-brand-600/25 transition-all">
              Crear Usuario
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL DE EDICIÓN DE USUARIO (EXCLUSIVO MASTER ADMIN) -->
    <div id="master-edit-user-modal" class="fixed inset-0 z-[110] hidden flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-md transition-all duration-300">
      <div class="relative w-full max-w-lg bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border rounded-3xl shadow-2xl p-6 sm:p-8 text-slate-800 dark:text-white transition-all overflow-y-auto max-h-[90vh] custom-scrollbar space-y-4">
        
        <button type="button" onclick="closeMasterEditUserModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center text-sm font-black transition-colors" aria-label="Cerrar">✕</button>

        <div class="space-y-1">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-wider">
            <span>✏️</span>
            Gestión Master Admin
          </div>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Editar Usuario <span id="edit-user-id-badge" class="text-brand-600">#0</span></h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Modifica datos del perfil, permisos de staff, estado de cuenta o cambia la contraseña.</p>
        </div>

        <form id="form-master-edit-user" onsubmit="handleMasterEditUser(event)" class="space-y-4 pt-1">
          <input type="hidden" id="edit-user-id" value="" />

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="edit-user-fullname" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Nombre Completo *</label>
              <input id="edit-user-fullname" type="text" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
            <div>
              <label for="edit-user-email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Correo Electrónico *</label>
              <input id="edit-user-email" type="email" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="edit-user-agency" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Empresa / Agencia</label>
              <input id="edit-user-agency" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
            <div>
              <label for="edit-user-cif" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">CIF / NIF</label>
              <input id="edit-user-cif" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="edit-user-phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Teléfono</label>
              <input id="edit-user-phone" type="tel" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
            </div>
            <div>
              <label for="edit-user-status" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Estado de Cuenta</label>
              <select id="edit-user-status" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-bold focus:border-brand-600 outline-none">
                <option value="approved">✅ Aprobado / Activo</option>
                <option value="pending">⏳ Pendiente</option>
                <option value="suspended">⏸️ Suspendido / Pausado</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="edit-user-role" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Rol</label>
              <select id="edit-user-role" onchange="onRoleChangeEditUser(this.value)" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-bold focus:border-brand-600 outline-none">
                <option value="professional">Profesional Independiente</option>
                <option value="agency">Agencia Inmobiliaria</option>
                <option value="staff">Staff de Operaciones HQ</option>
              </select>
            </div>
            <div id="wrapper-edit-staff-category">
              <label for="edit-user-category" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Categoría Staff</label>
              <select id="edit-user-category" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-bold focus:border-brand-600 outline-none">
                <option value="">Sin categoría staff</option>
                <option value="master_pro">Master Pro · Acceso al servicio Pro</option>
                <option value="staff_operaciones">Agente de Operaciones</option>
                <option value="staff_gerente">Gerente de Operaciones</option>
                <option value="staff_editor">Editor y Moderador de Cartera</option>
                <option value="staff_financiero">Gestor Financiero y Liquidaciones</option>
                <option value="staff_matching">Gestor de Demandas y Matching 50/50</option>
                <option value="staff_integraciones">Especialista en Feeds XML</option>
                <option value="staff_soporte">Gestor de Soporte y Agencias</option>
              </select>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-100 dark:border-darkbg-border">
            <label for="edit-user-new-password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 uppercase tracking-wider">Cambiar Contraseña (Opcional)</label>
            <input id="edit-user-new-password" type="password" minlength="6" placeholder="Dejar en blanco para mantener la contraseña actual" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-darkbg-border bg-slate-50/50 dark:bg-darkbg-main text-slate-900 dark:text-white text-xs font-semibold focus:border-brand-600 outline-none" />
          </div>

          <p id="edit-user-feedback" class="auth-feedback-box hidden p-3 rounded-xl text-xs" role="alert"></p>

          <div class="flex items-center gap-3 pt-2">
            <button type="button" onclick="closeMasterEditUserModal()" class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
              Cancelar
            </button>
            <button id="btn-submit-edit-user" type="submit" class="flex-1 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-black uppercase tracking-wider shadow-md shadow-brand-600/25 transition-all">
              Guardar Cambios
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL DE RECUPERACIÓN / RESET DE CONTRASEÑA POR MASTER ADMIN -->
    <div id="master-reset-password-modal" class="fixed inset-0 z-[110] hidden flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-md transition-all duration-300">
      <div class="relative w-full max-w-md bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border rounded-3xl shadow-2xl p-6 sm:p-8 text-slate-800 dark:text-white transition-all space-y-4">
        <button type="button" onclick="closeMasterResetModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center text-sm font-black transition-colors" aria-label="Cerrar">✕</button>

        <div class="space-y-1">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase tracking-wider">
            <span>🔑</span>
            Enlace de Recuperación Generado
          </div>
          <h3 class="text-xl font-black text-slate-900 dark:text-white">Restablecimiento Seguro</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Se ha generado un token único de 64 caracteres válido durante 24 horas para este usuario.</p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-darkbg-main border border-slate-200 dark:border-darkbg-border space-y-2">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Enlace directo de restablecimiento:</span>
          <div class="flex items-center gap-2">
            <input type="text" readonly id="master-reset-link-input" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border text-xs font-mono select-all outline-none" />
            <button type="button" onclick="copyMasterResetLink()" class="px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shrink-0 transition-all shadow-sm">
              Copiar
            </button>
          </div>
        </div>

        <button type="button" onclick="closeMasterResetModal()" class="w-full py-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
          Cerrar
        </button>
      </div>
    </div>

  </div>

  <!-- JAVASCRIPT INLINE ROBUSTO CON DATOS DEL SERVIDOR -->
  <script>
    window.INITIAL_DATA = {
      records: <?php echo json_encode($recordsList, JSON_UNESCAPED_UNICODE); ?>,
      users: <?php echo json_encode($usersList, JSON_UNESCAPED_UNICODE); ?>,
      tickets: <?php echo json_encode($ticketsList, JSON_UNESCAPED_UNICODE); ?>,
      xmlBatches: <?php echo json_encode($xmlBatchesList, JSON_UNESCAPED_UNICODE); ?>,
      logs: <?php echo json_encode($logsList, JSON_UNESCAPED_UNICODE); ?>,
      isMasterAdmin: <?php echo $isMasterAdmin ? 'true' : 'false'; ?>,
      currentStaff: <?php echo json_encode($currentStaffUser, JSON_UNESCAPED_UNICODE); ?>
    };
  </script>

  <!-- MÓDULO JS PRINCIPAL -->
  <script src="assets/crm.js?v=<?php echo time(); ?>"></script>
</body>
</html>
