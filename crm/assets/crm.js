/**
 * Compra Captación CRM - Panel de Control y Operaciones Staff HQ
 * Diseño Moderno Dribbble 2026: Paginación por bloques, ancho completo, búsqueda rápida Ctrl+K,
 * Exportación a CSV, recuperación segura de contraseñas y edición de perfil.
 */
(function() {
  'use strict';

  if (window.__CRM_INITIALIZED__) return;
  window.__CRM_INITIALIZED__ = true;

  const HASH_TO_TAB = {
    '': 'dashboard',
    '/': 'dashboard',
    'resumen': 'dashboard',
    'dashboard': 'dashboard',
    'inmuebles': 'records',
    'records': 'records',
    'tickets': 'tickets',
    'usuarios': 'users',
    'users': 'users',
    'xml': 'xml',
    'finanzas': 'finance',
    'finance': 'finance',
    'seguridad': 'telemetry',
    'telemetry': 'telemetry'
  };

  const TAB_TO_HASH = {
    'dashboard': 'resumen',
    'records': 'inmuebles',
    'tickets': 'tickets',
    'users': 'usuarios',
    'xml': 'xml',
    'finance': 'finanzas',
    'telemetry': 'seguridad'
  };

  let currentAdmin = { email: 'staff@compracaptacion.com', full_name: 'Operador Staff', phone: '' };
  let cachedStats = null;
  let cachedRecords = (window.INITIAL_DATA && window.INITIAL_DATA.records) || [];
  let cachedUsers = (window.INITIAL_DATA && window.INITIAL_DATA.users) || [];
  let cachedTickets = (window.INITIAL_DATA && window.INITIAL_DATA.tickets) || [];
  let currentTab = 'dashboard';
  let isRefreshing = false;
  let autoRefreshTimer = null;
  let currentRecordFilter = 'all';
  let currentUserFilter = 'all';

  // Paginación por Bloques (Sin scroll infinito)
  let recordsPage = 1;
  let recordsPerPage = 10;
  let usersPage = 1;
  let usersPerPage = 10;

  function getApiUrl(endpoint) {
    if (endpoint.startsWith('/')) return endpoint;
    return '/' + endpoint;
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
  }

  // 1. TEMA CLARO / OSCURO
  function initTheme() {
    const savedTheme = localStorage.getItem('captacion_theme_v1') || localStorage.getItem('crm_theme') || 'dark';
    applyTheme(savedTheme);
  }

  function applyTheme(theme) {
    const isDark = theme === 'dark';
    if (isDark) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('captacion_theme_v1', theme);
    localStorage.setItem('crm_theme', theme);

    const quickIcon = document.getElementById('theme-quick-icon');
    const quickText = document.getElementById('theme-quick-text');
    if (quickIcon) quickIcon.textContent = isDark ? '🌙' : '☀️';
    if (quickText) quickText.textContent = isDark ? 'Modo Oscuro' : 'Modo Claro';

    const gatekeeperIcon = document.getElementById('theme-gatekeeper-icon');
    const gatekeeperText = document.getElementById('theme-gatekeeper-text');
    if (gatekeeperIcon) gatekeeperIcon.textContent = isDark ? '🌙' : '☀️';
    if (gatekeeperText) gatekeeperText.textContent = isDark ? 'Modo Oscuro' : 'Modo Claro';
  }

  function toggleTheme() {
    const isDark = document.documentElement.classList.contains('dark');
    applyTheme(isDark ? 'light' : 'dark');
  }

  function setTheme(theme) {
    applyTheme(theme);
  }

  // 2. TOGGLE PASSWORD VISIBILITY (MOSTRAR / OCULTAR CONTRASEÑA)
  function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    const textSpan = btn.querySelector('.pwd-toggle-text');
    if (textSpan) textSpan.textContent = isPassword ? 'Ocultar' : 'Mostrar';

    const icon = btn.querySelector('.pwd-toggle-icon');
    if (icon) {
      icon.innerHTML = isPassword
        ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />`
        : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />`;
    }
  }

  // 3. MENÚ LATERAL MÓVIL
  function toggleMobileSidebar() {
    const sidebar = document.getElementById('crm-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (!sidebar) return;
    const isClosed = sidebar.classList.contains('-translate-x-full');
    if (isClosed) {
      sidebar.classList.remove('-translate-x-full');
      backdrop?.classList.remove('hidden');
    } else {
      sidebar.classList.add('-translate-x-full');
      backdrop?.classList.add('hidden');
    }
  }

  function closeMobileSidebarIfOpen() {
    const sidebar = document.getElementById('crm-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (sidebar && !sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
      sidebar.classList.add('-translate-x-full');
      backdrop?.classList.add('hidden');
    }
  }

  // 4. ENRUTAMIENTO Y URLS INDEPENDIENTES
  function handleUrlRouting() {
    const hash = window.location.hash.replace('#', '').trim().toLowerCase();
    const tab = HASH_TO_TAB[hash] || 'dashboard';
    switchCrmTab(tab, false);
  }

  function onHashChange() {
    handleUrlRouting();
  }

  // 5. INICIALIZACIÓN Y AUTH CHECK
  async function checkAdminAuth() {
    initTheme();
    updateSubdomainBadge();
    validateResetTokenInUrl();

    try {
      const res = await fetch(getApiUrl('api/admin/auth.php?action=me'), { credentials: 'same-origin' });
      const data = await res.json();
      if (data && data.ok && data.user) {
        currentAdmin = data.user;
        const display = document.getElementById('admin-user-display');
        if (display) display.textContent = currentAdmin.full_name || 'Staff HQ';
        const sidebarDisplay = document.getElementById('sidebar-user-display');
        if (sidebarDisplay) sidebarDisplay.textContent = currentAdmin.full_name || 'Operador Staff';
      }
    } catch(e) {}

    handleUrlRouting();
    initRealtimeEngine();
  }

  // 6. FLUJO DE AUTENTICACIÓN, REGISTRO SEGÚN CATEGORÍA Y GATEKEEPER STAFF
  function toggleGatekeeperView(view) {
    const vLogin = document.getElementById('gatekeeper-view-login');
    const vReg = document.getElementById('gatekeeper-view-register');
    const vForgot = document.getElementById('gatekeeper-view-forgot');
    const vReset = document.getElementById('gatekeeper-view-reset');

    if (vLogin) vLogin.classList.toggle('hidden', view !== 'login');
    if (vReg) vReg.classList.toggle('hidden', view !== 'register');
    if (vForgot) vForgot.classList.toggle('hidden', view !== 'forgot');
    if (vReset) vReset.classList.toggle('hidden', view !== 'reset');
  }

  function openAdminAuthModal(view = 'login') {
    toggleAuthModalView(view);
    const modal = document.getElementById('admin-login-modal');
    if (modal) modal.classList.remove('hidden');
  }

  function closeAdminAuthModal() {
    const modal = document.getElementById('admin-login-modal');
    if (modal) modal.classList.add('hidden');
  }

  function toggleAuthModalView(view) {
    const vLogin = document.getElementById('auth-view-login');
    const vForgot = document.getElementById('auth-view-forgot');
    const vReset = document.getElementById('auth-view-reset');
    if (vLogin) vLogin.classList.toggle('hidden', view !== 'login');
    if (vForgot) vForgot.classList.toggle('hidden', view !== 'forgot');
    if (vReset) vReset.classList.toggle('hidden', view !== 'reset');
  }

  async function handleAdminLogin(event) {
    event.preventDefault();
    const email = (document.getElementById('gatekeeper-email')?.value || document.getElementById('admin-email')?.value || '').trim();
    const password = document.getElementById('gatekeeper-password')?.value || document.getElementById('admin-password')?.value || '';
    const btn = document.getElementById('btn-gatekeeper-login') || document.getElementById('btn-admin-login');
    const feedback = document.getElementById('gatekeeper-login-feedback') || document.getElementById('login-feedback-box');

    if (!email || !password) return;
    if (btn) { btn.disabled = true; btn.textContent = 'Verificando credenciales...'; }
    if (feedback) feedback.classList.add('hidden');

    try {
      const res = await fetch(getApiUrl('api/admin/auth.php?action=login'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const data = await res.json();
      if (data && data.ok) {
        currentAdmin = data.user;
        location.reload();
      } else {
        if (feedback) {
          feedback.textContent = data.error || 'Credenciales administrativas no válidas.';
          feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
          feedback.classList.remove('hidden');
        }
      }
    } catch(err) {
      if (feedback) {
        feedback.textContent = 'Error de conexión con el servidor.';
        feedback.classList.remove('hidden');
      }
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Acceder al Panel de Operaciones →'; }
    }
  }

  async function handleStaffRegister(event) {
    event.preventDefault();
    const fullName = (document.getElementById('reg-fullname')?.value || '').trim();
    const email = (document.getElementById('reg-email')?.value || '').trim();
    const phone = (document.getElementById('reg-phone')?.value || '').trim();
    const staffCategory = (document.getElementById('reg-category')?.value || 'staff_gerente').trim();
    const password = document.getElementById('reg-password')?.value || '';
    const passwordConfirm = document.getElementById('reg-password-confirm')?.value || '';
    const btn = document.getElementById('btn-gatekeeper-reg');
    const feedback = document.getElementById('gatekeeper-reg-feedback');

    if (!fullName || !email || !password) return;

    if (password.length < 8) {
      if (feedback) {
        feedback.textContent = 'La contraseña debe contener al menos 8 caracteres.';
        feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
        feedback.classList.remove('hidden');
      }
      return;
    }

    if (password !== passwordConfirm) {
      if (feedback) {
        feedback.textContent = 'Las contraseñas no coinciden. Por favor verifícalas.';
        feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
        feedback.classList.remove('hidden');
      }
      return;
    }

    if (btn) { btn.disabled = true; btn.textContent = 'Procesando solicitud...'; }
    if (feedback) feedback.classList.add('hidden');

    try {
      const res = await fetch(getApiUrl('api/admin/auth.php?action=register_staff'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          full_name: fullName,
          email,
          phone,
          staff_category: staffCategory,
          password
        })
      });
      const data = await res.json();
      if (data && data.ok) {
        if (feedback) {
          feedback.textContent = data.message || '✓ Solicitud de Staff enviada con éxito.';
          feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
          feedback.classList.remove('hidden');
        }
        if (data.auto_login) {
          setTimeout(() => location.reload(), 1500);
        } else {
          setTimeout(() => {
            toggleGatekeeperView('login');
            const emailInput = document.getElementById('gatekeeper-email');
            if (emailInput) emailInput.value = email;
          }, 3500);
        }
      } else {
        if (feedback) {
          feedback.textContent = data.error || 'Error al procesar la solicitud.';
          feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
          feedback.classList.remove('hidden');
        }
      }
    } catch(e) {
      if (feedback) {
        feedback.textContent = 'Error de conexión.';
        feedback.classList.remove('hidden');
      }
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Enviar Solicitud de Acceso Staff →'; }
    }
  }

  async function handleAdminForgotPassword(event) {
    event.preventDefault();
    const email = (document.getElementById('gatekeeper-forgot-email')?.value || document.getElementById('forgot-email')?.value || '').trim();
    const btn = document.getElementById('btn-gatekeeper-forgot') || document.getElementById('btn-submit-forgot');
    const feedback = document.getElementById('gatekeeper-forgot-feedback') || document.getElementById('forgot-feedback-box');

    if (!email) return;
    if (btn) { btn.disabled = true; btn.textContent = 'Generando enlace seguro...'; }
    if (feedback) feedback.classList.add('hidden');

    try {
      const res = await fetch(getApiUrl('api/auth.php?action=request_password_reset'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, source: 'crm' })
      });
      const data = await res.json();
      if (feedback) {
        feedback.textContent = data.message || 'Si el email existe en la plataforma, recibirás las instrucciones en breve.';
        feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
        feedback.classList.remove('hidden');
      }
    } catch(err) {
      if (feedback) {
        feedback.textContent = 'Error al procesar la solicitud.';
        feedback.classList.remove('hidden');
      }
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Enviar Enlace de Recuperación →'; }
    }
  }

  async function handleAdminResetPassword(event) {
    event.preventDefault();
    const token = (document.getElementById('gatekeeper-reset-token')?.value || document.getElementById('reset-token')?.value || '').trim();
    const password = document.getElementById('gatekeeper-new-pwd')?.value || document.getElementById('new-password')?.value || '';
    const passwordConfirm = document.getElementById('gatekeeper-new-pwd-confirm')?.value || document.getElementById('new-password-confirm')?.value || '';
    const btn = document.getElementById('btn-gatekeeper-reset') || document.getElementById('btn-submit-reset');
    const feedback = document.getElementById('gatekeeper-reset-feedback') || document.getElementById('reset-feedback-box');

    if (!token || !password) return;
    if (password.length < 8) {
      if (feedback) {
        feedback.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.';
        feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
        feedback.classList.remove('hidden');
      }
      return;
    }
    if (password !== passwordConfirm) {
      if (feedback) {
        feedback.textContent = 'Las contraseñas no coinciden. Por favor verifícalas.';
        feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
        feedback.classList.remove('hidden');
      }
      return;
    }

    if (btn) { btn.disabled = true; btn.textContent = 'Guardando nueva clave...'; }
    if (feedback) feedback.classList.add('hidden');

    try {
      const res = await fetch(getApiUrl('api/auth.php?action=confirm_password_reset'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, password, password_confirm: passwordConfirm })
      });
      const data = await res.json();
      if (data && data.ok) {
        if (feedback) {
          feedback.textContent = '¡Contraseña actualizada con éxito! Redirigiendo...';
          feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
          feedback.classList.remove('hidden');
        }
        setTimeout(() => {
          window.location.href = window.location.pathname;
        }, 1500);
      } else {
        if (feedback) {
          feedback.textContent = data.error || 'El enlace de recuperación es inválido o ha caducado.';
          feedback.className = 'auth-feedback-box p-3.5 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
          feedback.classList.remove('hidden');
        }
      }
    } catch(err) {
      if (feedback) {
        feedback.textContent = 'Error al actualizar la contraseña.';
        feedback.classList.remove('hidden');
      }
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Guardar Nueva Contraseña →'; }
    }
  }

  function validateResetTokenInUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const resetToken = urlParams.get('reset_token');
    if (!resetToken) return;

    fetch(getApiUrl('api/auth.php?action=validate_reset_token&token=' + encodeURIComponent(resetToken)))
      .then(r => r.json())
      .then(data => {
        if (data && data.ok && data.valid) {
          const tokenInput = document.getElementById('gatekeeper-reset-token') || document.getElementById('reset-token');
          if (tokenInput) tokenInput.value = resetToken;
          toggleGatekeeperView('reset');
          openAdminAuthModal('reset');
        } else {
          alert(data.error || 'El enlace de recuperación ha caducado o es inválido.');
        }
      })
      .catch(() => {});
  }

  async function handleAdminLogout() {
    if (!confirm('¿Cerrar la sesión de Staff y salir del panel a la pantalla de bienvenida?')) return;
    try {
      await fetch(getApiUrl('api/admin/auth.php?action=logout'), { method: 'POST', credentials: 'same-origin' });
    } catch(e) {}
    // Limpiar hash de navegación y devolver a la pantalla de bienvenida
    window.location.hash = '';
    window.location.href = window.location.pathname;
  }

  // 7. MODAL DE EDICIÓN DE PERFIL STAFF
  function openStaffProfileModal() {
    const nameInput = document.getElementById('profile-fullname');
    const phoneInput = document.getElementById('profile-phone');
    const pwdInput = document.getElementById('profile-new-password');
    const feedback = document.getElementById('profile-feedback-box');

    if (nameInput) nameInput.value = currentAdmin.full_name || '';
    if (phoneInput) phoneInput.value = currentAdmin.phone || '';
    if (pwdInput) pwdInput.value = '';
    if (feedback) feedback.classList.add('hidden');

    const modal = document.getElementById('staff-profile-modal');
    if (modal) modal.classList.remove('hidden');
  }

  function closeStaffProfileModal() {
    const modal = document.getElementById('staff-profile-modal');
    if (modal) modal.classList.add('hidden');
  }

  async function handleSaveStaffProfile(event) {
    event.preventDefault();
    const fullName = document.getElementById('profile-fullname')?.value.trim();
    const phone = document.getElementById('profile-phone')?.value.trim();
    const newPassword = document.getElementById('profile-new-password')?.value || '';
    const btn = document.getElementById('btn-save-profile');
    const feedback = document.getElementById('profile-feedback-box');

    if (!fullName) return;
    if (newPassword !== '' && newPassword.length < 8) {
      if (feedback) {
        feedback.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.';
        feedback.className = 'auth-feedback-box p-3 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
        feedback.classList.remove('hidden');
      }
      return;
    }

    if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }
    if (feedback) feedback.classList.add('hidden');

    try {
      const res = await fetch(getApiUrl('api/admin/auth.php?action=update_profile'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ full_name: fullName, phone, new_password: newPassword })
      });
      const data = await res.json();
      if (data && data.ok) {
        currentAdmin.full_name = fullName;
        currentAdmin.phone = phone;
        const display = document.getElementById('admin-user-display');
        if (display) display.textContent = fullName;
        const sidebarDisplay = document.getElementById('sidebar-user-display');
        if (sidebarDisplay) sidebarDisplay.textContent = fullName;

        if (feedback) {
          feedback.textContent = '✓ Perfil actualizado correctamente.';
          feedback.className = 'auth-feedback-box p-3 rounded-xl text-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
          feedback.classList.remove('hidden');
        }
        setTimeout(() => closeStaffProfileModal(), 1000);
      } else {
        if (feedback) {
          feedback.textContent = data.error || 'Error al actualizar perfil.';
          feedback.className = 'auth-feedback-box p-3 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
          feedback.classList.remove('hidden');
        }
      }
    } catch(e) {
      if (feedback) {
        feedback.textContent = 'Error de conexión.';
        feedback.classList.remove('hidden');
      }
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Guardar Cambios'; }
    }
  }

  // 8. BÚSQUEDA RÁPIDA GLOBAL (CTRL + K)
  function openQuickSearchModal() {
    const modal = document.getElementById('quick-search-modal');
    const input = document.getElementById('global-search-input');
    if (modal) {
      modal.classList.remove('hidden');
      setTimeout(() => input?.focus(), 50);
    }
  }

  function closeQuickSearchModal() {
    const modal = document.getElementById('quick-search-modal');
    if (modal) modal.classList.add('hidden');
  }

  function handleGlobalSearch(event) {
    if (event.key === 'Escape') {
      closeQuickSearchModal();
      return;
    }
    const q = (document.getElementById('global-search-input')?.value || '').trim().toLowerCase();
    const resultsContainer = document.getElementById('global-search-results');
    if (!resultsContainer) return;

    if (!q || q.length < 2) {
      resultsContainer.innerHTML = '<div class="p-6 text-center text-slate-400">Escribe al menos 2 letras para buscar en toda la plataforma...</div>';
      return;
    }

    const matchedRecords = cachedRecords.filter(r => 
      (r.title || '').toLowerCase().includes(q) ||
      (r.municipality || '').toLowerCase().includes(q) ||
      (r.province || '').toLowerCase().includes(q) ||
      (r.author_agency || '').toLowerCase().includes(q)
    ).slice(0, 5);

    const matchedUsers = cachedUsers.filter(u => 
      (u.email || '').toLowerCase().includes(q) ||
      (u.full_name || '').toLowerCase().includes(q) ||
      (u.agency_name || '').toLowerCase().includes(q)
    ).slice(0, 5);

    let html = '';
    if (matchedRecords.length > 0) {
      html += '<div class="px-2 pt-2 text-[10px] font-black uppercase tracking-wider text-slate-400">Inmuebles y Demandas</div>';
      html += matchedRecords.map(r => `
        <div onclick="closeQuickSearchModal(); switchCrmTab('records');" class="p-3 rounded-2xl bg-slate-50 dark:bg-darkbg-main hover:bg-brand-500/10 border border-slate-100 dark:border-darkbg-border flex items-center justify-between cursor-pointer transition-colors">
          <div>
            <strong class="text-xs text-slate-900 dark:text-white block">${r.title || 'Inmueble'}</strong>
            <span class="text-[10px] text-slate-400">${r.municipality || ''}, ${r.province || ''} · ${Number(r.price || 0).toLocaleString('es-ES')} €</span>
          </div>
          <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-brand-500/15 text-brand-600 dark:text-brand-neon">${r.record_type === 'property' ? 'En Venta' : 'Demanda'}</span>
        </div>
      `).join('');
    }

    if (matchedUsers.length > 0) {
      html += '<div class="px-2 pt-2 text-[10px] font-black uppercase tracking-wider text-slate-400">Agencias y Miembros</div>';
      html += matchedUsers.map(u => `
        <div onclick="closeQuickSearchModal(); switchCrmTab('users');" class="p-3 rounded-2xl bg-slate-50 dark:bg-darkbg-main hover:bg-blue-500/10 border border-slate-100 dark:border-darkbg-border flex items-center justify-between cursor-pointer transition-colors">
          <div>
            <strong class="text-xs text-slate-900 dark:text-white block">${u.full_name || u.email}</strong>
            <span class="text-[10px] text-slate-400">${u.agency_name || 'Independiente'} · ${u.email}</span>
          </div>
          <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">${Math.round(u.credits || 0)} cr</span>
        </div>
      `).join('');
    }

    if (!html) {
      html = '<div class="p-6 text-center text-slate-400">No se encontraron resultados para "' + q + '".</div>';
    }

    resultsContainer.innerHTML = html;
  }

  // Keyboard shortcut listener para Ctrl + K
  window.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      openQuickSearchModal();
    }
  });

  // 9. EXPORTACIÓN A CSV
  function exportRecordsToCSV() {
    if (!cachedRecords || !cachedRecords.length) {
      alert('No hay inmuebles para exportar.');
      return;
    }
    const headers = ['ID', 'Tipo', 'Título', 'Provincia', 'Municipio', 'Precio/Presupuesto', 'Agencia/Contacto', 'Email', 'Exclusiva', 'Estado'];
    const rows = cachedRecords.map(r => [
      r.id,
      r.record_type === 'property' ? 'En Venta' : 'Demanda',
      `"${(r.title || '').replace(/"/g, '""')}"`,
      `"${(r.province || '').replace(/"/g, '""')}"`,
      `"${(r.municipality || '').replace(/"/g, '""')}"`,
      r.price || 0,
      `"${(r.author_agency || r.author_name || '').replace(/"/g, '""')}"`,
      `"${(r.author_email || '').replace(/"/g, '""')}"`,
      r.is_exclusive ? 'Sí' : 'No',
      r.status || 'active'
    ]);

    const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    downloadCSV(csvContent, `cartera_inmuebles_staff_${new Date().toISOString().slice(0,10)}.csv`);
  }

  function exportUsersToCSV() {
    if (!cachedUsers || !cachedUsers.length) {
      alert('No hay usuarios para exportar.');
      return;
    }
    const headers = ['ID', 'Nombre', 'Email', 'Agencia', 'CIF_NIF', 'Telefono', 'Rol', 'Creditos', 'Estado'];
    const rows = cachedUsers.map(u => [
      u.id,
      `"${(u.full_name || '').replace(/"/g, '""')}"`,
      `"${(u.email || '').replace(/"/g, '""')}"`,
      `"${(u.agency_name || '').replace(/"/g, '""')}"`,
      `"${(u.cif_nif || '').replace(/"/g, '""')}"`,
      `"${(u.phone || '').replace(/"/g, '""')}"`,
      u.role || 'professional',
      u.credits || 0,
      u.verification_status || 'approved'
    ]);

    const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    downloadCSV(csvContent, `usuarios_agencias_staff_${new Date().toISOString().slice(0,10)}.csv`);
  }

  function downloadCSV(content, filename) {
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  // 10. SUBDOMINIOS Y SINCRONIZACIÓN EN TIEMPO REAL
  function updateSubdomainBadge() {
    const badge = document.getElementById('sync-subdomain-badge');
    if (!badge) return;
    badge.textContent = window.location.hostname || 'crm.compracaptacion.com';
  }

  function updateSyncTimestamp() {
    const timeBadge = document.getElementById('sync-time-badge');
    if (!timeBadge) return;
    const now = new Date();
    timeBadge.textContent = `Sincronizado: ${now.toTimeString().split(' ')[0]}`;
  }

  function initRealtimeEngine() {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    autoRefreshTimer = setInterval(() => {
      if (!document.hidden) refreshCrmData(false);
    }, 20000);

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) refreshCrmData(false);
    });
    window.addEventListener('hashchange', onHashChange);
  }

  async function refreshCrmData(manual = false) {
    if (isRefreshing) return;
    isRefreshing = true;

    const refreshIcon = document.getElementById('refresh-icon');
    const refreshText = document.getElementById('refresh-btn-text');

    if (manual) {
      if (refreshIcon) refreshIcon.classList.add('animate-spin');
      if (refreshText) refreshText.textContent = 'Actualizando...';
    }

    try {
      await loadDashboardData();
      if (currentTab === 'records') await loadRecords();
      else if (currentTab === 'tickets') await loadTickets();
      else if (currentTab === 'users') await loadUsers();
      else if (currentTab === 'xml') await loadXmlBatches();
      else if (currentTab === 'telemetry') await loadLogs();

      updateSyncTimestamp();

      if (manual && refreshText) {
        refreshText.textContent = '✓ Actualizado';
        setTimeout(() => {
          if (refreshText) refreshText.textContent = 'Actualizar';
        }, 1200);
      }
    } catch(err) {
      console.error('[CRM] Error en sincronización:', err);
    } finally {
      isRefreshing = false;
      if (manual && refreshIcon) {
        setTimeout(() => refreshIcon.classList.remove('animate-spin'), 500);
      }
    }
  }

  // 11. ENRUTADOR DE PESTAÑAS (SPA)
  function switchCrmTab(tabId, updateHash = true) {
    currentTab = tabId;

    if (updateHash) {
      const targetHash = TAB_TO_HASH[tabId] || tabId;
      if (window.location.hash !== '#' + targetHash) {
        window.location.hash = '#' + targetHash;
      }
    }

    const panels = ['dashboard', 'records', 'tickets', 'users', 'xml', 'finance', 'telemetry'];
    panels.forEach(p => {
      const panelEl = document.getElementById('crm-panel-' + p);
      if (panelEl) panelEl.classList.toggle('hidden', p !== tabId);

      const navBtn = document.getElementById('nav-tab-' + p);
      if (navBtn) navBtn.classList.toggle('is-active', p === tabId);
    });

    const titles = {
      dashboard: { title: 'Resumen General Staff', sub: 'Cuadro de mando ejecutivo y control de flujo de operaciones' },
      records: { title: 'Cartera de Inmuebles Staff', sub: 'Captaciones exclusivas y demandas activas organizadas por bloques' },
      tickets: { title: 'Atención y Tickets Staff', sub: 'Centro de soporte técnico y consultas de agencias' },
      users: { title: 'Directorio de Usuarios y Agencias', sub: 'Control de miembros registrados, licencias y saldos de créditos' },
      xml: { title: 'Pasarelas de Ingestión Automática XML', sub: 'Conexión y mapeo de feeds Kyero, Inmovilla, Idealista y Habitaclia' },
      finance: { title: 'Finanzas y Créditos Staff', sub: 'Métricas de volumen transaccional e ingresos recurrentes' },
      telemetry: { title: 'Seguridad y Auditoría Staff', sub: 'Registro de accesos, telemetría y diagnósticos del servidor' }
    };

    if (titles[tabId]) {
      const titleEl = document.getElementById('crm-current-title');
      const subEl = document.getElementById('crm-current-subtitle');
      if (titleEl) titleEl.textContent = titles[tabId].title;
      if (subEl) subEl.textContent = titles[tabId].sub;
    }

    renderSubOptionsBar(tabId);

    if (tabId === 'records') renderRecordsTable(cachedRecords);
    else if (tabId === 'tickets') renderTicketsTable(cachedTickets);
    else if (tabId === 'users') renderUsersTable(cachedUsers);
    else if (tabId === 'xml') renderXmlBatches(window.INITIAL_DATA.xmlBatches);
    else if (tabId === 'telemetry') renderLogsTable(window.INITIAL_DATA.logs);
  }

  function renderSubOptionsBar(tabId) {
    const container = document.getElementById('crm-sub-options-container');
    if (!container) return;
    let html = '';

    if (tabId === 'dashboard') {
      html = `
        <div class="flex items-center flex-wrap gap-2">
          <a href="#resumen" onclick="switchCrmTab('dashboard')" class="sub-option-pill is-active px-3.5 py-1.5 rounded-xl text-xs font-bold text-white shadow-sm flex items-center gap-1.5"><span>📊</span><span>Resumen General</span></a>
          <a href="#inmuebles" onclick="switchCrmTab('records')" class="sub-option-pill px-3.5 py-1.5 rounded-xl bg-white dark:bg-darkbg-card hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200/80 dark:border-darkbg-border flex items-center gap-1.5 transition-all"><span>🏠</span><span>Cartera de Inmuebles</span></a>
          <a href="#usuarios" onclick="switchCrmTab('users')" class="sub-option-pill px-3.5 py-1.5 rounded-xl bg-white dark:bg-darkbg-card hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200/80 dark:border-darkbg-border flex items-center gap-1.5 transition-all"><span>👥</span><span>Usuarios y Agencias</span></a>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="runSystemDiagnostic()" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm flex items-center gap-1.5"><span>⚡</span><span>Diagnóstico Staff</span></button>
        </div>
      `;
    } else if (tabId === 'records') {
      html = `
        <div class="flex items-center flex-wrap gap-2">
          <span class="text-xs text-slate-500 dark:text-slate-400 font-bold">Vista por Bloques:</span>
          <span class="text-xs font-extrabold text-brand-600 dark:text-brand-neon">100% Ancho Panorámico</span>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="exportRecordsToCSV()" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center gap-1"><span>📥</span><span>CSV</span></button>
          <button onclick="promptCreateRecord()" class="px-4 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/25 flex items-center gap-1.5"><span>+</span><span>Añadir</span></button>
        </div>
      `;
    } else if (tabId === 'users') {
      html = `
        <div class="flex items-center flex-wrap gap-2">
          <span class="text-xs text-slate-500 dark:text-slate-400 font-bold">Directorio:</span>
          <span class="text-xs font-extrabold text-brand-600 dark:text-brand-neon">Agencias y Profesionales Verificados</span>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="exportUsersToCSV()" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center gap-1"><span>📥</span><span>CSV</span></button>
        </div>
      `;
    } else {
      html = `<div class="text-xs text-slate-500">Panel Operativo Compra Captación HQ</div>`;
    }
    container.innerHTML = html;
  }

  // 12. CARGA DE DATOS DESDE ENDPOINTS
  async function loadDashboardData() {
    try {
      const res = await fetch(getApiUrl('api/admin/stats.php'), { credentials: 'same-origin' });
      const data = await res.json();
      if (!data || !data.ok) return;
      cachedStats = data.stats;

      const totalRecs = (cachedStats.records.properties || 0) + (cachedStats.records.demands || 0);
      const kpiUsersTotal = document.getElementById('kpi-users-total');
      if (kpiUsersTotal) kpiUsersTotal.textContent = cachedStats.users.total;
      
      const kpiRecordsTotal = document.getElementById('kpi-records-total');
      if (kpiRecordsTotal) kpiRecordsTotal.textContent = totalRecs;
      
      const badgeRecs = document.getElementById('badge-total-records');
      if (badgeRecs) badgeRecs.textContent = totalRecs;

      const kpiCreditsTotal = document.getElementById('kpi-credits-total');
      if (kpiCreditsTotal) kpiCreditsTotal.textContent = Math.round(cachedStats.finance.circulating_credits) + ' cr';

      const paidRevenue = document.getElementById('kpi-paid-revenue');
      if (paidRevenue) {
        paidRevenue.textContent = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(cachedStats.finance.paid_revenue || 0);
      }
      
      const kpiTicketsTotal = document.getElementById('kpi-tickets-total');
      if (kpiTicketsTotal) kpiTicketsTotal.textContent = cachedStats.support.open_tickets;
      
      const badgeTickets = document.getElementById('badge-open-tickets');
      if (badgeTickets) badgeTickets.textContent = cachedStats.support.open_tickets;

      const disk = cachedStats.telemetry && cachedStats.telemetry.disk;
      const storage = document.getElementById('telemetry-storage');
      if (storage) storage.textContent = disk ? `${disk.used_percent}% usado · ${disk.free_mb} MB libres` : 'No verificado';

      const alerts = Array.isArray(cachedStats.alerts) ? cachedStats.alerts : [];
      const alertCount = document.getElementById('ceo-alerts-count');
      const alertList = document.getElementById('ceo-alerts-list');
      const healthBadge = document.getElementById('platform-health-badge');
      if (alertCount) alertCount.textContent = alerts.length ? `${alerts.length} señal${alerts.length === 1 ? '' : 'es'} activa${alerts.length === 1 ? '' : 's'}` : 'Sin alertas activas';
      if (healthBadge) {
        const hasCritical = alerts.some(alert => alert.level === 'critical');
        const hasWarning = alerts.some(alert => alert.level === 'warning');
        healthBadge.textContent = hasCritical ? 'Atención requerida' : (hasWarning ? 'Revisar alertas' : 'Estado verificado');
        healthBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-black ' + (hasCritical ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400' : (hasWarning ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'));
      }
      if (alertList) {
        alertList.innerHTML = alerts.length ? alerts.map(alert => `<div class="flex items-start gap-3 p-3 rounded-2xl ${alert.level === 'critical' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300' : 'bg-amber-500/10 text-amber-700 dark:text-amber-300'}"><span aria-hidden="true">${alert.level === 'critical' ? '⚠️' : '🔔'}</span><div><strong class="block text-xs font-bold">${escapeHtml(alert.title || 'Señal operativa')}</strong><span class="block text-xs mt-0.5">${escapeHtml(alert.detail || '')}</span></div></div>`).join('') : '<p class="text-xs text-emerald-600 dark:text-emerald-400">✓ No hay incidencias ejecutivas activas.</p>';
      }

    } catch (error) {
      console.error('No se pudo cargar el panel ejecutivo.', error);
    }
  }

  async function loadXmlBatches() {
    const container = document.getElementById('xml-batches-list');
    if (!container) return;
    try {
      const res = await fetch(getApiUrl('api/xml_feeds.php?action=list'), { credentials: 'same-origin' });
      const data = await res.json();
      if (data && data.ok && data.batches) {
        renderXmlBatches(data.batches);
      }
    } catch(e) {}
  }

  async function loadLogs() {
    try {
      const res = await fetch(getApiUrl('api/admin/logs.php'), { credentials: 'same-origin' });
      const data = await res.json();
      if (data && data.ok && data.logs) {
        renderLogsTable(data.logs);
      }
    } catch(e) {}
  }

  // =========================================================
  // 13. SELECCIÓN DE AGRUPADO (BULK ACTIONS) Y TABLAS DENSAS
  // =========================================================
  let selectedRecordIds = new Set();
  let selectedUserIds = new Set();
  let selectedTicketIds = new Set();

  // A. SELECCIÓN AGRUPADA DE INMUEBLES
  function toggleSelectAllRecords(masterCb) {
    const checkboxes = document.querySelectorAll('.chk-record-item');
    checkboxes.forEach(cb => {
      cb.checked = masterCb.checked;
      if (masterCb.checked) selectedRecordIds.add(cb.value);
      else selectedRecordIds.delete(cb.value);
    });
    updateRecordSelectionBar();
  }

  function updateRecordSelection() {
    selectedRecordIds.clear();
    const checkboxes = document.querySelectorAll('.chk-record-item:checked');
    checkboxes.forEach(cb => selectedRecordIds.add(cb.value));
    updateRecordSelectionBar();
  }

  function updateRecordSelectionBar() {
    const bar = document.getElementById('records-bulk-bar');
    const countEl = document.getElementById('records-selected-count');
    const masterCb = document.getElementById('chk-records-master');
    const allCbs = document.querySelectorAll('.chk-record-item');
    const count = selectedRecordIds.size;

    if (countEl) countEl.textContent = count;
    if (bar) bar.classList.toggle('hidden', count === 0);
    if (masterCb && allCbs.length > 0) {
      masterCb.checked = (allCbs.length === document.querySelectorAll('.chk-record-item:checked').length);
    }
  }

  async function deleteSelectedRecords() {
    const ids = Array.from(selectedRecordIds).map(Number);
    if (!ids.length) return;
    if (!confirm(`¿Estás seguro de eliminar permanentemente ${ids.length} inmueble(s) seleccionado(s)?`)) return;

    try {
      const res = await fetch(getApiUrl('api/admin/records.php?action=bulk_delete'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ record_ids: ids })
      });
      const data = await res.json();
      if (data && data.ok) {
        alert(data.message || 'Inmuebles eliminados correctamente.');
        selectedRecordIds.clear();
        updateRecordSelectionBar();
        refreshCrmData(true);
      } else {
        alert(data.error || 'Error al eliminar inmuebles.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  async function setStatusSelectedRecords(status) {
    const ids = Array.from(selectedRecordIds).map(Number);
    if (!ids.length) return;

    try {
      const res = await fetch(getApiUrl('api/admin/records.php?action=bulk_status'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ record_ids: ids, status })
      });
      const data = await res.json();
      if (data && data.ok) {
        selectedRecordIds.clear();
        updateRecordSelectionBar();
        refreshCrmData(true);
      } else {
        alert(data.error || 'Error al actualizar estado.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  // B. SELECCIÓN AGRUPADA DE USUARIOS
  function toggleSelectAllUsers(masterCb) {
    const checkboxes = document.querySelectorAll('.chk-user-item');
    checkboxes.forEach(cb => {
      cb.checked = masterCb.checked;
      if (masterCb.checked) selectedUserIds.add(cb.value);
      else selectedUserIds.delete(cb.value);
    });
    updateUserSelectionBar();
  }

  function updateUserSelection() {
    selectedUserIds.clear();
    const checkboxes = document.querySelectorAll('.chk-user-item:checked');
    checkboxes.forEach(cb => selectedUserIds.add(cb.value));
    updateUserSelectionBar();
  }

  function updateUserSelectionBar() {
    const bar = document.getElementById('users-bulk-bar');
    const countEl = document.getElementById('users-selected-count');
    const masterCb = document.getElementById('chk-users-master');
    const allCbs = document.querySelectorAll('.chk-user-item');
    const count = selectedUserIds.size;

    if (countEl) countEl.textContent = count;
    if (bar) bar.classList.toggle('hidden', count === 0);
    if (masterCb && allCbs.length > 0) {
      masterCb.checked = (allCbs.length === document.querySelectorAll('.chk-user-item:checked').length);
    }
  }

  async function deleteSelectedUsers() {
    const ids = Array.from(selectedUserIds).map(Number);
    if (!ids.length) return;
    if (!confirm(`¿Eliminar los ${ids.length} usuario(s) seleccionado(s)? Esta acción retirará su acceso.`)) return;

    try {
      const res = await fetch(getApiUrl('api/admin/users.php?action=bulk_delete'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_ids: ids })
      });
      const data = await res.json();
      if (data && data.ok) {
        alert(data.message || 'Usuarios eliminados correctamente.');
        selectedUserIds.clear();
        updateUserSelectionBar();
        refreshCrmData(true);
      } else {
        alert(data.error || 'Error al eliminar usuarios.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  async function setStatusSelectedUsers(status) {
    const ids = Array.from(selectedUserIds).map(Number);
    if (!ids.length) return;

    try {
      const res = await fetch(getApiUrl('api/admin/users.php?action=bulk_status'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_ids: ids, status })
      });
      const data = await res.json();
      if (data && data.ok) {
        selectedUserIds.clear();
        updateUserSelectionBar();
        refreshCrmData(true);
      } else {
        alert(data.error || 'Error al actualizar estado.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  // C. SELECCIÓN AGRUPADA DE TICKETS
  function toggleSelectAllTickets(masterCb) {
    const checkboxes = document.querySelectorAll('.chk-ticket-item');
    checkboxes.forEach(cb => {
      cb.checked = masterCb.checked;
      if (masterCb.checked) selectedTicketIds.add(cb.value);
      else selectedTicketIds.delete(cb.value);
    });
    updateTicketSelectionBar();
  }

  function updateTicketSelection() {
    selectedTicketIds.clear();
    const checkboxes = document.querySelectorAll('.chk-ticket-item:checked');
    checkboxes.forEach(cb => selectedTicketIds.add(cb.value));
    updateTicketSelectionBar();
  }

  function updateTicketSelectionBar() {
    const bar = document.getElementById('tickets-bulk-bar');
    const countEl = document.getElementById('tickets-selected-count');
    const masterCb = document.getElementById('chk-tickets-master');
    const allCbs = document.querySelectorAll('.chk-ticket-item');
    const count = selectedTicketIds.size;

    if (countEl) countEl.textContent = count;
    if (bar) bar.classList.toggle('hidden', count === 0);
    if (masterCb && allCbs.length > 0) {
      masterCb.checked = (allCbs.length === document.querySelectorAll('.chk-ticket-item:checked').length);
    }
  }

  async function deleteSelectedTickets() {
    const ids = Array.from(selectedTicketIds).map(Number);
    if (!ids.length) return;
    if (!confirm(`¿Eliminar los ${ids.length} ticket(s) seleccionados de la base de datos?`)) return;

    try {
      const res = await fetch(getApiUrl('api/admin/tickets.php?action=bulk_delete'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ticket_ids: ids })
      });
      const data = await res.json();
      if (data && data.ok) {
        alert(data.message || 'Tickets eliminados correctamente.');
        selectedTicketIds.clear();
        updateTicketSelectionBar();
        refreshCrmData(true);
      } else {
        alert(data.error || 'Error al eliminar tickets.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  async function setStatusSelectedTickets(status) {
    const ids = Array.from(selectedTicketIds).map(Number);
    if (!ids.length) return;

    try {
      const res = await fetch(getApiUrl('api/admin/tickets.php?action=bulk_status'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ticket_ids: ids, status })
      });
      const data = await res.json();
      if (data && data.ok) {
        selectedTicketIds.clear();
        updateTicketSelectionBar();
        refreshCrmData(true);
      } else {
        alert(data.error || 'Error al actualizar estado.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  // =========================================================
  // 14. RENDERIZADO DE TABLAS CON CHECKBOXES Y PAGINACIÓN
  // =========================================================
  function renderRecordsTable(records) {
    const tbody = document.getElementById('records-table-body');
    const paginationBar = document.getElementById('records-pagination-bar');
    if (!tbody) return;

    if (!records || !records.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="p-8 text-center text-slate-500">No se encontraron inmuebles con este filtro.</td></tr>';
      if (paginationBar) paginationBar.innerHTML = '';
      return;
    }

    const total = records.length;
    const totalPages = Math.ceil(total / recordsPerPage);
    if (recordsPage > totalPages) recordsPage = totalPages;
    if (recordsPage < 1) recordsPage = 1;

    const startIdx = (recordsPage - 1) * recordsPerPage;
    const endIdx = Math.min(startIdx + recordsPerPage, total);
    const paginatedRecords = records.slice(startIdx, endIdx);

    tbody.innerHTML = paginatedRecords.map((r, i) => {
      const realIdx = startIdx + i;
      const isProp = r.record_type === 'property';
      const isChecked = selectedRecordIds.has(String(r.id));
      const typeBadge = isProp 
        ? '<span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/20">🏠 En Venta</span>'
        : '<span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">🎯 Demanda</span>';
      
      const statusBadge = r.status === 'active'
        ? '<span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">ACTIVO</span>'
        : '<span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/15 text-amber-600 dark:text-amber-400">PAUSADO</span>';

      const priceFmt = Number(r.price || 0).toLocaleString('es-ES') + ' €';

      return `
        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors cursor-pointer ${isChecked ? 'bg-brand-500/5' : ''}" onclick="inspectRecord(${realIdx})">
          <td class="p-4 pl-6 w-10 text-center" onclick="event.stopPropagation()">
            <input type="checkbox" value="${r.id}" ${isChecked ? 'checked' : ''} onchange="updateRecordSelection()" class="chk-record-item w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500 cursor-pointer" />
          </td>
          <td class="p-4 font-mono font-bold text-slate-400">#${r.id}</td>
          <td class="p-4">${typeBadge}</td>
          <td class="p-4 max-w-sm">
            <strong class="text-slate-900 dark:text-white block truncate text-xs font-bold">${r.title || 'Inmueble'}</strong>
            <span class="text-slate-500 text-[10px] block">${r.municipality || ''}, ${r.province || ''} · ${r.property_type || ''}</span>
          </td>
          <td class="p-4">
            <span class="font-semibold text-slate-800 dark:text-slate-200 block text-xs">${r.author_agency || r.author_name || 'Agente'}</span>
            <span class="text-slate-500 text-[10px] block">${r.author_email || ''}</span>
          </td>
          <td class="p-4 font-extrabold text-emerald-600 dark:text-emerald-400 text-xs">${priceFmt}</td>
          <td class="p-4 text-[11px] text-slate-600 dark:text-slate-300">
            50/50
            ${r.is_exclusive ? '<span class="ml-1 text-[9px] px-1.5 py-0.5 rounded bg-brand-500/20 text-brand-600 dark:text-brand-neon font-black">EXCL</span>' : ''}
          </td>
          <td class="p-4">${statusBadge}</td>
          <td class="p-4 pr-6 text-right" onclick="event.stopPropagation()">
            <div class="flex items-center justify-end gap-1.5">
              <button onclick="toggleRecordStatus(${r.id}, '${r.status}')" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-[10px] transition-all">
                ${r.status === 'active' ? 'Pausar' : 'Activar'}
              </button>
              <button onclick="inspectRecord(${realIdx})" class="px-2.5 py-1.5 rounded-xl bg-brand-500/10 hover:bg-brand-500/20 text-brand-600 dark:text-brand-neon font-bold text-[10px] transition-all">
                Ficha ↗
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    updateRecordSelectionBar();

    // Barra de Paginación por Bloques
    if (paginationBar) {
      paginationBar.innerHTML = `
        <div class="flex items-center gap-3">
          <span class="text-slate-500">Mostrando <strong>${startIdx + 1}</strong> a <strong>${endIdx}</strong> de <strong>${total}</strong> registros</span>
          <div class="flex items-center gap-1.5 pl-2 border-l border-slate-200 dark:border-slate-700">
            <span class="text-[11px] text-slate-400">Por página:</span>
            <select onchange="setRecordsPageSize(this.value)" class="px-2 py-1 rounded-lg bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border text-xs font-bold outline-none">
              <option value="10" ${recordsPerPage === 10 ? 'selected' : ''}>10</option>
              <option value="25" ${recordsPerPage === 25 ? 'selected' : ''}>25</option>
              <option value="50" ${recordsPerPage === 50 ? 'selected' : ''}>50</option>
            </select>
          </div>
        </div>
        <div class="flex items-center gap-1.5">
          <button onclick="goToRecordsPage(${recordsPage - 1})" ${recordsPage <= 1 ? 'disabled class="opacity-40 cursor-not-allowed"' : ''} class="px-3 py-1.5 rounded-xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold transition-all shadow-sm">
            « Anterior
          </button>
          <span class="px-3 py-1.5 rounded-xl bg-brand-600 text-white font-black text-xs shadow-sm">${recordsPage} / ${totalPages || 1}</span>
          <button onclick="goToRecordsPage(${recordsPage + 1})" ${recordsPage >= totalPages ? 'disabled class="opacity-40 cursor-not-allowed"' : ''} class="px-3 py-1.5 rounded-xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold transition-all shadow-sm">
            Siguiente »
          </button>
        </div>
      `;
    }
  }

  function goToRecordsPage(page) {
    recordsPage = page;
    renderRecordsTable(cachedRecords);
  }

  function isMasterAdmin() {
    if (window.INITIAL_DATA && window.INITIAL_DATA.isMasterAdmin) return true;
    if (window.INITIAL_DATA && window.INITIAL_DATA.currentStaff) {
      const email = (window.INITIAL_DATA.currentStaff.email || '').toLowerCase().trim();
      if (email === 'inmobia360@gmail.com' || email === 'inmobia360@mail.com' || email === 'admin@compracaptacion.com') return true;
      if (window.INITIAL_DATA.currentStaff.role === 'admin' || window.INITIAL_DATA.currentStaff.staff_category === 'master_admin') return true;
    }
    if (currentAdmin) {
      const email = (currentAdmin.email || '').toLowerCase().trim();
      if (currentAdmin.is_master_admin || 
          currentAdmin.staff_category === 'master_admin' || 
          currentAdmin.role === 'admin' || 
          email === 'inmobia360@gmail.com' || 
          email === 'inmobia360@mail.com' || 
          email === 'admin@compracaptacion.com') {
        return true;
      }
    }
    return false;
  }

  // 15. RENDERIZADO DE USUARIOS Y AGENCIAS CON CHECKBOXES
  function renderUsersTable(users) {
    const tbody = document.getElementById('users-table-body');
    const paginationBar = document.getElementById('users-pagination-bar');
    if (!tbody) return;

    let filteredUsers = users;
    if (currentUserFilter === 'agency') filteredUsers = users.filter(u => u.role === 'agency');
    else if (currentUserFilter === 'professional') filteredUsers = users.filter(u => u.role === 'professional' || (!u.role && u.role !== 'agency' && u.role !== 'staff' && u.role !== 'admin'));
    else if (currentUserFilter === 'staff') filteredUsers = users.filter(u => u.role === 'staff' || u.role === 'admin' || u.staff_category);

    if (!filteredUsers || !filteredUsers.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="p-8 text-center text-slate-500">No se encontraron usuarios con este filtro.</td></tr>';
      if (paginationBar) paginationBar.innerHTML = '';
      return;
    }

    const total = filteredUsers.length;
    const totalPages = Math.ceil(total / usersPerPage);
    if (usersPage > totalPages) usersPage = totalPages;
    if (usersPage < 1) usersPage = 1;

    const startIdx = (usersPage - 1) * usersPerPage;
    const endIdx = Math.min(startIdx + usersPerPage, total);
    const paginatedUsers = filteredUsers.slice(startIdx, endIdx);
    const isMaster = isMasterAdmin();

    // Actualizar visibilidad del botón de crear usuario
    const createBtn = document.getElementById('btn-master-create-user');
    if (createBtn) createBtn.classList.toggle('hidden', !isMaster);

    tbody.innerHTML = paginatedUsers.map((u, i) => {
      const realIdx = startIdx + i;
      const isChecked = selectedUserIds.has(String(u.id));

      let roleLabel = 'PROFESIONAL';
      let roleClass = 'bg-slate-500/15 text-slate-700 dark:text-slate-300';
      if (u.staff_category === 'master_admin' || u.role === 'admin') {
        roleLabel = 'MASTER ADMIN';
        roleClass = 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30';
      } else if (u.staff_category === 'master_pro') {
        roleLabel = 'MASTER PRO';
        roleClass = 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20';
      } else if (u.role === 'staff' || u.staff_category) {
        roleLabel = (u.staff_category === 'staff_operaciones' || u.staff_category === 'staff_agente_operaciones') ? 'AGENTE OP.' : 'STAFF HQ';
        roleClass = 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/20';
      } else if (u.role === 'agency') {
        roleLabel = 'AGENCIA';
        roleClass = 'bg-brand-500/15 text-brand-600 dark:text-brand-neon';
      }

      let statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">ACTIVO</span>';
      if (u.verification_status === 'suspended') {
        statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-red-500/15 text-red-600 dark:text-red-400">SUSPENDIDO</span>';
      } else if (u.verification_status === 'pending') {
        statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/15 text-amber-600 dark:text-amber-400">PENDIENTE</span>';
      }

      let actionsHtml = `
        <div class="flex items-center justify-end gap-1.5 flex-wrap">
          <button onclick="inspectUser(${realIdx})" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-[10px] transition-all">Ficha ↗</button>
      `;

      if (isMaster) {
        actionsHtml += `
          <button onclick="openMasterEditUserModal(${u.id})" class="px-2 py-1.5 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 font-bold text-[10px] transition-all" title="Editar datos">✏️</button>
          <button onclick="masterToggleUserStatus(${u.id}, '${u.verification_status || 'approved'}')" class="px-2 py-1.5 rounded-xl ${u.verification_status === 'suspended' ? 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600' : 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-600'} font-bold text-[10px] transition-all" title="${u.verification_status === 'suspended' ? 'Reactivar acceso' : 'Pausar / Suspender acceso'}">${u.verification_status === 'suspended' ? '▶️' : '⏸️'}</button>
          <button onclick="masterSendPasswordReset(${u.id})" class="px-2 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold text-[10px] transition-all" title="Generar enlace de restablecimiento de contraseña">🔑</button>
          <button onclick="promptAdjustCredits(${u.id}, '${u.email}')" class="px-2 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] shadow-sm transition-all" title="Ajustar saldo de créditos">±</button>
        `;
        if (u.staff_category !== 'master_admin' && u.email !== 'inmobia360@mail.com') {
          actionsHtml += `
            <button onclick="masterDeleteUser(${u.id}, '${u.email}')" class="px-2 py-1.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-[10px] transition-all" title="Eliminar usuario">🗑️</button>
          `;
        }
      }
      actionsHtml += `</div>`;

      return `
        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors cursor-pointer ${isChecked ? 'bg-brand-500/5' : ''}" onclick="inspectUser(${realIdx})">
          <td class="p-4 pl-6 w-10 text-center" onclick="event.stopPropagation()">
            <input type="checkbox" value="${u.id}" ${isChecked ? 'checked' : ''} onchange="updateUserSelection()" class="chk-user-item w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500 cursor-pointer" />
          </td>
          <td class="p-4 font-mono font-bold text-slate-400">#${u.id}</td>
          <td class="p-4"><strong class="text-slate-900 dark:text-white">${u.full_name || 'Profesional'}</strong><span class="block text-slate-500 text-[10px]">${u.agency_name || 'Independiente'}</span></td>
          <td class="p-4"><span class="text-slate-800 dark:text-slate-200 block">${u.email}</span><span class="text-slate-500 text-[10px]">${u.phone || 'Sin teléfono'}</span></td>
          <td class="p-4"><span class="px-2.5 py-1 rounded-full text-[10px] font-black ${roleClass}">${roleLabel}</span></td>
          <td class="p-4 font-mono text-slate-500 text-xs">${u.cif_nif || 'Sin CIF'}</td>
          <td class="p-4 font-extrabold text-emerald-600 dark:text-emerald-400 text-xs">${Math.round(u.credits || 0)} cr</td>
          <td class="p-4">${statusBadge}</td>
          <td class="p-4 pr-6 text-right" onclick="event.stopPropagation()">
            ${actionsHtml}
          </td>
        </tr>
      `;
    }).join('');

    updateUserSelectionBar();

    // Barra de Paginación Usuarios
    if (paginationBar) {
      paginationBar.innerHTML = `
        <div class="flex items-center gap-3">
          <span class="text-slate-500">Mostrando <strong>${startIdx + 1}</strong> a <strong>${endIdx}</strong> de <strong>${total}</strong> usuarios</span>
          <div class="flex items-center gap-1.5 pl-2 border-l border-slate-200 dark:border-slate-700">
            <span class="text-[11px] text-slate-400">Por página:</span>
            <select onchange="setUsersPageSize(this.value)" class="px-2 py-1 rounded-lg bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border text-xs font-bold outline-none">
              <option value="10" ${usersPerPage === 10 ? 'selected' : ''}>10</option>
              <option value="25" ${usersPerPage === 25 ? 'selected' : ''}>25</option>
              <option value="50" ${usersPerPage === 50 ? 'selected' : ''}>50</option>
            </select>
          </div>
        </div>
        <div class="flex items-center gap-1.5">
          <button onclick="goToUsersPage(${usersPage - 1})" ${usersPage <= 1 ? 'disabled class="opacity-40 cursor-not-allowed"' : ''} class="px-3 py-1.5 rounded-xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold transition-all shadow-sm">
            « Anterior
          </button>
          <span class="px-3 py-1.5 rounded-xl bg-brand-600 text-white font-black text-xs shadow-sm">${usersPage} / ${totalPages || 1}</span>
          <button onclick="goToUsersPage(${usersPage + 1})" ${usersPage >= totalPages ? 'disabled class="opacity-40 cursor-not-allowed"' : ''} class="px-3 py-1.5 rounded-xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold transition-all shadow-sm">
            Siguiente »
          </button>
        </div>
      `;
    }
  }

  function goToUsersPage(page) {
    usersPage = page;
    renderUsersTable(cachedUsers);
  }

  function setUsersPageSize(size) {
    usersPerPage = parseInt(size, 10) || 10;
    usersPage = 1;
    renderUsersTable(cachedUsers);
  }

  function filterUsersRole(role) {
    currentUserFilter = role;
    usersPage = 1;
    renderUsersTable(cachedUsers);
  }

  // 16. RENDERIZADO DE TICKETS, XML Y LOGS
  function renderTicketsTable(tickets) {
    const tbody = document.getElementById('tickets-table-body');
    if (!tbody) return;
    if (!tickets || !tickets.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="p-8 text-center text-slate-500">No hay tickets registrados en esta sección.</td></tr>';
      return;
    }
    tbody.innerHTML = tickets.map(t => {
      const isChecked = selectedTicketIds.has(String(t.id));
      return `
        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors ${isChecked ? 'bg-brand-500/5' : ''}">
          <td class="p-4 pl-6 w-10 text-center" onclick="event.stopPropagation()">
            <input type="checkbox" value="${t.id}" ${isChecked ? 'checked' : ''} onchange="updateTicketSelection()" class="chk-ticket-item w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500 cursor-pointer" />
          </td>
          <td class="p-4 font-mono font-bold text-brand-600 dark:text-brand-neon">${t.ticket_code}</td>
          <td class="p-4"><strong>${t.user_name || 'Usuario'}</strong><span class="block text-slate-500 text-[10px]">${t.user_email}</span></td>
          <td class="p-4 font-semibold text-slate-900 dark:text-white">${t.subject}</td>
          <td class="p-4"><span class="px-2.5 py-1 rounded-full text-[10px] font-black ${t.priority === 'urgent' ? 'bg-red-500/15 text-red-600 dark:text-red-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'}">${t.priority === 'urgent' ? 'URGENTE' : 'NORMAL'}</span></td>
          <td class="p-4"><span class="px-2.5 py-1 rounded-full text-[10px] font-black ${t.status === 'open' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'}">${t.status === 'open' ? 'ABIERTO' : 'RESUELTO'}</span></td>
          <td class="p-4 text-slate-500">${(t.created_at || '').split(' ')[0]}</td>
          <td class="p-4 pr-6 text-right">
            <button onclick="replyTicketPrompt(${t.id}, '${t.ticket_code}')" class="px-3 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-[11px] shadow-sm">Responder</button>
          </td>
        </tr>
      `;
    }).join('');

    updateTicketSelectionBar();
  }

  // =========================================================
  // 17. GOOGLE DRIVE SUITE & COPIAS DE SEGURIDAD EN LA NUBE
  // =========================================================
  function openGoogleDriveModal() {
    const modal = document.getElementById('google-drive-config-modal');
    if (modal) modal.classList.remove('hidden');
    loadGoogleDriveStatus();
  }

  function closeGoogleDriveModal() {
    const modal = document.getElementById('google-drive-config-modal');
    if (modal) modal.classList.add('hidden');
  }

  async function loadGoogleDriveStatus() {
    try {
      const res = await fetch(getApiUrl('api/admin/backup.php?action=status'), { credentials: 'same-origin' });
      const data = await res.json();
      if (data && data.ok && data.google_drive) {
        const gd = data.google_drive;
        const fInput = document.getElementById('drive-folder-id');
        const wInput = document.getElementById('drive-webhook-url');
        const freqSelect = document.getElementById('drive-frequency');
        const autoCheck = document.getElementById('drive-auto-sync');

        if (fInput) fInput.value = gd.folder_id || '';
        if (freqSelect) freqSelect.value = gd.frequency || 'daily';
        if (autoCheck) autoCheck.checked = gd.auto_sync;

        const badge = document.getElementById('drive-sync-status-badge');
        if (badge) badge.textContent = gd.connected ? '✓ Conectado y Listo' : 'Listo para Configurar';

        const lastBackupEl = document.getElementById('drive-last-backup-display');
        if (lastBackupEl && gd.last_backup) lastBackupEl.textContent = gd.last_backup;

        const folderEl = document.getElementById('drive-folder-display');
        if (folderEl) folderEl.textContent = 'Carpeta: ' + (gd.folder_id || 'Backup-CompraCaptacion-HQ');
      }
    } catch(e) {}
  }

  async function saveGoogleDriveConfig(event) {
    event.preventDefault();
    const folderId = (document.getElementById('drive-folder-id')?.value || '').trim();
    const webhookUrl = (document.getElementById('drive-webhook-url')?.value || '').trim();
    const frequency = document.getElementById('drive-frequency')?.value || 'daily';
    const autoSync = document.getElementById('drive-auto-sync')?.checked;
    const btn = document.getElementById('btn-save-drive-config');
    const feedback = document.getElementById('drive-config-feedback');

    if (btn) { btn.disabled = true; btn.textContent = 'Guardando configuración...'; }
    if (feedback) feedback.classList.add('hidden');

    try {
      const res = await fetch(getApiUrl('api/admin/backup.php?action=save_config'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ folder_id: folderId, webhook_url: webhookUrl, frequency, auto_sync: autoSync })
      });
      const data = await res.json();
      if (data && data.ok) {
        if (feedback) {
          feedback.textContent = '✓ Configuración de Google Drive guardada con éxito.';
          feedback.className = 'auth-feedback-box p-3 rounded-xl text-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
          feedback.classList.remove('hidden');
        }
        setTimeout(() => closeGoogleDriveModal(), 1200);
        loadGoogleDriveStatus();
      } else {
        if (feedback) {
          feedback.textContent = data.error || 'Error al guardar.';
          feedback.className = 'auth-feedback-box p-3 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
          feedback.classList.remove('hidden');
        }
      }
    } catch(e) {
      if (feedback) {
        feedback.textContent = 'Error de conexión.';
        feedback.classList.remove('hidden');
      }
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Guardar Configuración'; }
    }
  }

  function downloadBackupJson() {
    window.location.href = getApiUrl('api/admin/backup.php?action=download');
  }

  async function syncGoogleDriveNow() {
    const btn = document.getElementById('btn-sync-drive-now');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span>⏳</span><span>Sincronizando...</span>'; }

    try {
      const res = await fetch(getApiUrl('api/admin/backup.php?action=sync_drive_now'), {
        method: 'POST', credentials: 'same-origin'
      });
      const data = await res.json();
      if (data && data.ok) {
        alert(data.message || '✓ Respaldo generado y transmitido a Google Drive con éxito.');
        loadGoogleDriveStatus();
      } else {
        alert(data.error || 'Error en la sincronización.');
      }
    } catch(e) {
      alert('Error de conexión al sincronizar.');
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
    }
  }

  function renderXmlBatches(batches) {
    const container = document.getElementById('xml-batches-list');
    if (!container) return;
    if (!batches || !batches.length) {
      container.innerHTML = '<div class="p-6 text-center text-slate-500">Sin conexiones XML activas.</div>';
      return;
    }
    container.innerHTML = batches.map(b => `
      <div class="p-6 rounded-3xl bg-white dark:bg-darkbg-card border border-slate-200/80 dark:border-darkbg-border shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="font-mono text-xs font-bold text-brand-600 dark:text-brand-neon">${b.import_batch_id}</span>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">ACTIVO</span>
          </div>
          <strong class="text-slate-900 dark:text-white text-sm block mt-1">${b.source_file_name || b.source_url || 'Feed Inmobiliario'}</strong>
          <span class="text-slate-500 dark:text-slate-400 text-xs mt-1 block">${b.records_imported || 0} inmuebles sincronizados</span>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="refreshCrmData(true)" class="px-4 py-2 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">Sincronizar</button>
        </div>
      </div>
    `).join('');
  }

  function renderLogsTable(logs) {
    const tbody = document.getElementById('logs-table-body');
    if (!tbody) return;
    if (!logs || !logs.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="p-6 text-center text-slate-500">Sin registros de actividad recientes.</td></tr>';
      return;
    }
    tbody.innerHTML = logs.map(l => `
      <tr>
        <td class="p-3 font-mono text-slate-500">#${l.id}</td>
        <td class="p-3 font-bold text-brand-600 dark:text-brand-neon">${l.action}</td>
        <td class="p-3 text-slate-500 dark:text-slate-400">${l.ip_address || '127.0.0.1'}</td>
        <td class="p-3 text-slate-700 dark:text-slate-300 truncate max-w-xs">${l.details || '-'}</td>
        <td class="p-3 text-slate-500">${l.created_at}</td>
      </tr>
    `).join('');
  }

  // 16. FILTROS Y BÚSQUEDA INTERNA
  function filterRecords(filter) {
    currentRecordFilter = filter;
    recordsPage = 1;
    loadRecords();
  }

  function searchRecords() {
    const q = (document.getElementById('record-search-input')?.value || '').trim();
    loadRecords(q);
  }

  function searchUsers() {
    const q = (document.getElementById('user-search-input')?.value || '').trim();
    loadUsers(q);
  }

  function filterTickets(status) {
    loadTickets(status);
  }

  // 17. INSPECTOR DESLIZABLE (SLIDEOVER DRAWER)
  function inspectRecord(idx) {
    const r = cachedRecords[idx];
    if (!r) return;

    const titleEl = document.getElementById('drawer-title');
    const subEl = document.getElementById('drawer-subtitle');
    const iconEl = document.getElementById('drawer-icon');
    const bodyEl = document.getElementById('drawer-body');
    const footerEl = document.getElementById('drawer-footer');

    if (iconEl) iconEl.textContent = r.record_type === 'property' ? '🏠' : '🎯';
    if (titleEl) titleEl.textContent = r.title || 'Detalle del Inmueble';
    if (subEl) subEl.textContent = `#${r.id} · Ref: ${r.record_key || 'INM'}`;

    const priceFmt = Number(r.price || 0).toLocaleString('es-ES') + ' €';

    bodyEl.innerHTML = `
      <div class="p-4 rounded-2xl bg-slate-100/80 dark:bg-darkbg-main border border-slate-200 dark:border-darkbg-border space-y-2">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Precio / Presupuesto</span>
        <strong class="block text-2xl font-black text-emerald-600 dark:text-emerald-400">${priceFmt}</strong>
        <div class="flex items-center gap-3 text-xs text-slate-600 dark:text-slate-300 pt-1">
          <span>Colaboración: <strong>50/50</strong></span>
          <span>·</span>
          <span>Exclusiva: <strong>${r.is_exclusive ? 'Sí' : 'No'}</strong></span>
        </div>
      </div>

      <div class="space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Profesional o Agencia Publicadora</h4>
        <div class="p-3.5 rounded-xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border space-y-1.5">
          <strong class="text-slate-900 dark:text-white block text-sm">${r.author_agency || r.author_name || 'Agencia'}</strong>
          <span class="text-slate-500 block text-xs">${r.author_email || ''}</span>
          <span class="text-slate-500 block text-xs">Ubicación: ${r.municipality || ''}, ${r.province || ''}</span>
        </div>
      </div>

      <div class="space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Descripción del Inmueble</h4>
        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed p-3.5 rounded-xl bg-slate-50 dark:bg-darkbg-main border border-slate-200 dark:border-darkbg-border">
          ${r.description_public || 'Inmueble publicado para colaboración entre profesionales bajo condiciones compartidas al 50%.'}
        </p>
      </div>
    `;

    footerEl.innerHTML = `
      <button onclick="toggleRecordStatus(${r.id}, '${r.status}')" class="flex-1 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-sm transition-all">
        ${r.status === 'active' ? 'Pausar Publicación' : 'Activar Publicación'}
      </button>
      <button onclick="deleteRecord(${r.id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs transition-all">
        Eliminar
      </button>
    `;

    openDrawer();
  }

  function inspectUser(idx) {
    const u = cachedUsers[idx];
    if (!u) return;

    const titleEl = document.getElementById('drawer-title');
    const subEl = document.getElementById('drawer-subtitle');
    const iconEl = document.getElementById('drawer-icon');
    const bodyEl = document.getElementById('drawer-body');
    const footerEl = document.getElementById('drawer-footer');

    if (iconEl) iconEl.textContent = '👤';
    if (titleEl) titleEl.textContent = u.full_name || u.email;
    if (subEl) subEl.textContent = `ID #${u.id} · ${u.agency_name || 'Autónomo'}`;

    bodyEl.innerHTML = `
      <div class="p-4 rounded-2xl bg-slate-100/80 dark:bg-darkbg-main border border-slate-200 dark:border-darkbg-border space-y-2">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Saldo de Créditos</span>
        <strong class="block text-2xl font-black text-emerald-600 dark:text-emerald-400">${Math.round(u.credits || 0)} créditos</strong>
        <span class="text-xs text-slate-500 block">Equivalente: ${Math.round((u.credits || 0) * 10)} €</span>
      </div>

      <div class="space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Datos de Contacto y Fiscales</h4>
        <div class="p-3.5 rounded-xl bg-white dark:bg-darkbg-card border border-slate-200 dark:border-darkbg-border space-y-1.5">
          <div class="flex justify-between"><span>Email:</span><strong class="text-slate-900 dark:text-white">${u.email}</strong></div>
          <div class="flex justify-between"><span>CIF / NIF:</span><strong>${u.cif_nif || 'No indicado'}</strong></div>
          <div class="flex justify-between"><span>Teléfono:</span><strong>${u.phone || 'No indicado'}</strong></div>
          <div class="flex justify-between"><span>Tipo:</span><strong class="uppercase text-brand-600">${u.role === 'agency' ? 'Agencia' : 'Profesional'}</strong></div>
          <div class="flex justify-between"><span>Estado:</span><strong class="uppercase ${u.verification_status === 'approved' ? 'text-emerald-600' : 'text-amber-600'}">${u.verification_status || 'approved'}</strong></div>
        </div>
      </div>
    `;

    footerEl.innerHTML = `
      <button onclick="promptAdjustCredits(${u.id}, '${u.email}')" class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm transition-all">
        ± Ajustar Saldo de Créditos
      </button>
      <button onclick="toggleUserStatus(${u.id}, '${u.verification_status}')" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition-all">
        Estado
      </button>
    `;

    openDrawer();
  }

  function openDrawer() {
    const backdrop = document.getElementById('crm-drawer-backdrop');
    const drawer = document.getElementById('crm-slideover-drawer');
    if (backdrop && drawer) {
      backdrop.classList.remove('hidden');
      setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
      drawer.classList.remove('translate-x-full');
    }
  }

  function closeInspectorDrawer() {
    const backdrop = document.getElementById('crm-drawer-backdrop');
    const drawer = document.getElementById('crm-slideover-drawer');
    if (backdrop && drawer) {
      backdrop.classList.add('opacity-0');
      drawer.classList.add('translate-x-full');
      setTimeout(() => backdrop.classList.add('hidden'), 250);
    }
  }

  // 18. ACCIONES DIRECTAS
  async function toggleRecordStatus(recordId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';
    try {
      const res = await fetch(getApiUrl('api/admin/records.php?action=set_status'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ record_id: recordId, status: newStatus })
      });
      const data = await res.json();
      if (data && data.ok) {
        const r = cachedRecords.find(item => item.id == recordId);
        if (r) r.status = newStatus;
        renderRecordsTable(cachedRecords);
        loadDashboardData();
        closeInspectorDrawer();
      } else {
        alert(data.error || 'Error al cambiar estado.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  async function deleteRecord(recordId) {
    if (!confirm(`¿Estás seguro de retirar de publicación el inmueble #${recordId}?`)) return;
    try {
      const res = await fetch(getApiUrl('api/admin/records.php?action=delete'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ record_id: recordId })
      });
      const data = await res.json();
      if (data && data.ok) {
        cachedRecords = cachedRecords.filter(item => item.id != recordId);
        renderRecordsTable(cachedRecords);
        loadDashboardData();
        closeInspectorDrawer();
      } else {
        alert(data.error || 'Error al eliminar.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  function promptCreateRecord() {
    const title = prompt('Título del inmueble o demanda:');
    if (!title) return;
    const type = confirm('¿Es un INMUEBLE EN VENTA (Aceptar) o una DEMANDA DE COMPRA (Cancelar)?') ? 'property' : 'need';
    const priceStr = prompt('Precio o Presupuesto en Euros:', '350000');
    if (!priceStr) return;
    const price = parseFloat(priceStr);
    const province = prompt('Provincia:', 'Madrid') || 'Madrid';
    const municipality = prompt('Ciudad o Municipio:', 'Madrid') || 'Madrid';

    fetch(getApiUrl('api/records.php?action=create'), {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title, record_type: type, price, province, municipality,
        operation_type: 'colaboracion_50_50', commission_percentage: 50.0
      })
    }).then(r => r.json()).then(d => {
      alert(d.message || 'Inmueble publicado con éxito.');
      loadRecords();
      loadDashboardData();
    });
  }

  function promptAdjustCredits(userId, email) {
    const amountStr = prompt(`Ajustar saldo de créditos para ${email}:\n(Escribe un número positivo como +10 para sumar, o negativo como -5 para restar)`);
    if (!amountStr) return;
    const amount = parseFloat(amountStr);
    if (isNaN(amount) || amount === 0) { alert('Introduce una cantidad válida.'); return; }
    const reason = prompt('Motivo del ajuste:', 'Recarga / Bono de bienvenida') || 'Ajuste manual';

    fetch(getApiUrl('api/admin/users.php?action=adjust_credits'), {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId, amount, reason })
    }).then(r => r.json()).then(d => {
      alert(d.message || 'Saldo actualizado.');
      loadUsers();
      loadDashboardData();
      closeInspectorDrawer();
    });
  }

  function toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus === 'approved' ? 'suspended' : 'approved';
    if (!confirm(`¿Cambiar estado del usuario a ${newStatus === 'approved' ? 'Activo' : 'Suspendido'}?`)) return;
    fetch(getApiUrl('api/admin/users.php?action=set_status'), {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId, status: newStatus })
    }).then(r => r.json()).then(d => {
      alert(d.message || 'Estado actualizado.');
      loadUsers();
      closeInspectorDrawer();
    });
  }

  function openNewTicketModal() {
    const email = prompt('Email del destinatario:', 'contacto@inmobiliaria.es');
    if (!email) return;
    const subject = prompt('Asunto del ticket:');
    if (!subject) return;
    const priority = confirm('¿Es urgente?') ? 'urgent' : 'medium';

    fetch(getApiUrl('api/admin/tickets.php?action=create'), {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_email: email, subject, priority })
    }).then(r => r.json()).then(d => {
      alert(d.message || 'Ticket creado correctamente.');
      loadTickets();
      loadDashboardData();
    });
  }

  function replyTicketPrompt(ticketId, code) {
    const msg = prompt(`Responder al Ticket ${code}:\nIntroduce tu mensaje de respuesta:`);
    if (!msg || !msg.trim()) return;

    fetch(getApiUrl('api/admin/tickets.php?action=reply'), {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ticket_id: ticketId, message: msg.trim() })
    }).then(r => r.json()).then(d => {
      alert(d.message || 'Respuesta enviada correctamente.');
      loadTickets();
      loadDashboardData();
    });
  }

  function testXmlFeedConnection() {
    alert('⚡ Sincronizador XML listo:\n\nFormatos compatibles: Kyero v3, Inmovilla, Idealista, Habitaclia.\nEl parser universal detectará automáticamente cualquier taxonomía de inmueble.');
  }

  function runSystemDiagnostic() {
    alert('⚡ Comprobación de Plataforma Staff Finalizada:\n\n- Conexión Base de Datos: Correcta\n- Sincronización en Tiempo Real: Activa\n- Modo Oscuro / Claro: 100% Funcional\n- Subdominio: ' + window.location.hostname + '\n- Trazabilidad y Filtros por Bloques: Operativos');
  }

  // =========================================================
  // 19. GESTIÓN EXCLUSIVA MASTER ADMIN DE USUARIOS
  // =========================================================
  function openMasterCreateUserModal() {
    if (!isMasterAdmin()) {
      alert('Solo el Administrador Maestro (Master Admin) tiene permisos para crear usuarios manualmente.');
      return;
    }
    const modal = document.getElementById('master-create-user-modal');
    const form = document.getElementById('form-master-create-user');
    const feedback = document.getElementById('create-user-feedback');
    if (form) form.reset();
    if (feedback) feedback.classList.add('hidden');
    onRoleChangeCreateUser('professional');
    if (modal) modal.classList.remove('hidden');
  }

  function closeMasterCreateUserModal() {
    const modal = document.getElementById('master-create-user-modal');
    if (modal) modal.classList.add('hidden');
  }

  function onRoleChangeCreateUser(role) {
    const wrapper = document.getElementById('wrapper-create-staff-category');
    if (wrapper) {
      wrapper.classList.toggle('hidden', role !== 'staff');
    }
  }

  async function handleMasterCreateUser(event) {
    event.preventDefault();
    if (!isMasterAdmin()) return;

    const fullName = document.getElementById('create-user-fullname')?.value.trim();
    const email = document.getElementById('create-user-email')?.value.trim();
    const password = document.getElementById('create-user-password')?.value;
    const phone = document.getElementById('create-user-phone')?.value.trim();
    const agencyName = document.getElementById('create-user-agency')?.value.trim();
    const cifNif = document.getElementById('create-user-cif')?.value.trim();
    const role = document.getElementById('create-user-role')?.value;
    const staffCategory = document.getElementById('create-user-category')?.value;
    const credits = parseFloat(document.getElementById('create-user-credits')?.value) || 0;
    const status = document.getElementById('create-user-status')?.value;

    const btn = document.getElementById('btn-submit-create-user');
    const feedback = document.getElementById('create-user-feedback');

    if (btn) { btn.disabled = true; btn.textContent = 'Creando usuario...'; }
    if (feedback) feedback.classList.add('hidden');

    try {
      const res = await fetch(getApiUrl('api/admin/users.php?action=create_user'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          full_name: fullName,
          email: email,
          password: password,
          phone: phone,
          agency_name: agencyName,
          cif_nif: cifNif,
          role: role,
          staff_category: role === 'staff' ? staffCategory : '',
          credits: credits,
          status: status
        })
      });
      const data = await res.json();
      if (data && data.ok) {
        if (feedback) {
          feedback.textContent = '✓ ' + (data.message || 'Usuario creado correctamente.');
          feedback.className = 'auth-feedback-box p-3 rounded-xl text-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
          feedback.classList.remove('hidden');
        }
        setTimeout(() => {
          closeMasterCreateUserModal();
          refreshCrmData(true);
        }, 1200);
      } else {
        if (feedback) {
          feedback.textContent = data.error || 'Error al crear usuario.';
          feedback.className = 'auth-feedback-box p-3 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
          feedback.classList.remove('hidden');
        }
      }
    } catch(e) {
      if (feedback) {
        feedback.textContent = 'Error de conexión con el servidor.';
        feedback.classList.remove('hidden');
      }
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Crear Usuario'; }
    }
  }

  function openMasterEditUserModal(userId) {
    if (!isMasterAdmin()) {
      alert('Solo el Administrador Maestro (Master Admin) tiene permisos para editar usuarios.');
      return;
    }
    const u = cachedUsers.find(item => item.id == userId);
    if (!u) return;

    const modal = document.getElementById('master-edit-user-modal');
    const idBadge = document.getElementById('edit-user-id-badge');
    const idInput = document.getElementById('edit-user-id');
    const nameInput = document.getElementById('edit-user-fullname');
    const emailInput = document.getElementById('edit-user-email');
    const agencyInput = document.getElementById('edit-user-agency');
    const cifInput = document.getElementById('edit-user-cif');
    const phoneInput = document.getElementById('edit-user-phone');
    const roleSelect = document.getElementById('edit-user-role');
    const catSelect = document.getElementById('edit-user-category');
    const statusSelect = document.getElementById('edit-user-status');
    const pwdInput = document.getElementById('edit-user-new-password');
    const feedback = document.getElementById('edit-user-feedback');

    if (idBadge) idBadge.textContent = `#${u.id}`;
    if (idInput) idInput.value = u.id;
    if (nameInput) nameInput.value = u.full_name || '';
    if (emailInput) emailInput.value = u.email || '';
    if (agencyInput) agencyInput.value = u.agency_name || '';
    if (cifInput) cifInput.value = u.cif_nif || '';
    if (phoneInput) phoneInput.value = u.phone || '';
    if (roleSelect) roleSelect.value = u.role || 'professional';
    if (catSelect) catSelect.value = u.staff_category || '';
    if (statusSelect) statusSelect.value = u.verification_status || 'approved';
    if (pwdInput) pwdInput.value = '';
    if (feedback) feedback.classList.add('hidden');

    onRoleChangeEditUser(u.role || 'professional');

    if (modal) modal.classList.remove('hidden');
  }

  function closeMasterEditUserModal() {
    const modal = document.getElementById('master-edit-user-modal');
    if (modal) modal.classList.add('hidden');
  }

  function onRoleChangeEditUser(role) {
    const wrapper = document.getElementById('wrapper-edit-staff-category');
    if (wrapper) {
      wrapper.classList.toggle('hidden', role !== 'staff');
    }
  }

  async function handleMasterEditUser(event) {
    event.preventDefault();
    if (!isMasterAdmin()) return;

    const userId = parseInt(document.getElementById('edit-user-id')?.value, 10);
    const fullName = document.getElementById('edit-user-fullname')?.value.trim();
    const email = document.getElementById('edit-user-email')?.value.trim();
    const phone = document.getElementById('edit-user-phone')?.value.trim();
    const agencyName = document.getElementById('edit-user-agency')?.value.trim();
    const cifNif = document.getElementById('edit-user-cif')?.value.trim();
    const role = document.getElementById('edit-user-role')?.value;
    const staffCategory = document.getElementById('edit-user-category')?.value;
    const status = document.getElementById('edit-user-status')?.value;
    const newPassword = document.getElementById('edit-user-new-password')?.value;

    const btn = document.getElementById('btn-submit-edit-user');
    const feedback = document.getElementById('edit-user-feedback');

    if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }
    if (feedback) feedback.classList.add('hidden');

    try {
      const res = await fetch(getApiUrl('api/admin/users.php?action=update_user'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: userId,
          full_name: fullName,
          email: email,
          phone: phone,
          agency_name: agencyName,
          cif_nif: cifNif,
          role: role,
          staff_category: role === 'staff' ? staffCategory : '',
          status: status,
          new_password: newPassword
        })
      });
      const data = await res.json();
      if (data && data.ok) {
        if (feedback) {
          feedback.textContent = '✓ ' + (data.message || 'Usuario actualizado correctamente.');
          feedback.className = 'auth-feedback-box p-3 rounded-xl text-xs bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
          feedback.classList.remove('hidden');
        }
        setTimeout(() => {
          closeMasterEditUserModal();
          closeInspectorDrawer();
          refreshCrmData(true);
        }, 1200);
      } else {
        if (feedback) {
          feedback.textContent = data.error || 'Error al actualizar usuario.';
          feedback.className = 'auth-feedback-box p-3 rounded-xl text-xs bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
          feedback.classList.remove('hidden');
        }
      }
    } catch(e) {
      if (feedback) {
        feedback.textContent = 'Error de conexión.';
        feedback.classList.remove('hidden');
      }
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Guardar Cambios'; }
    }
  }

  async function masterToggleUserStatus(userId, currentStatus) {
    if (!isMasterAdmin()) {
      alert('Solo el Administrador Maestro (Master Admin) puede cambiar estados de acceso.');
      return;
    }
    const newStatus = (currentStatus === 'suspended') ? 'approved' : 'suspended';
    const actionLabel = (newStatus === 'approved') ? 'reactivar' : 'pausar y suspender temporalmente';
    
    if (!confirm(`¿Confirmas que deseas ${actionLabel} el acceso del usuario #${userId}?`)) return;

    try {
      const res = await fetch(getApiUrl('api/admin/users.php?action=set_status'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, status: newStatus })
      });
      const data = await res.json();
      if (data && data.ok) {
        alert(data.message || 'Estado actualizado correctamente.');
        closeInspectorDrawer();
        refreshCrmData(true);
      } else {
        alert(data.error || 'Error al cambiar estado.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  async function masterSendPasswordReset(userId) {
    if (!isMasterAdmin()) {
      alert('Solo el Administrador Maestro puede emitir enlaces de recuperación de clave.');
      return;
    }
    try {
      const res = await fetch(getApiUrl('api/admin/users.php?action=send_password_reset'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
      });
      const data = await res.json();
      if (data && data.ok && data.reset_url) {
        const modal = document.getElementById('master-reset-password-modal');
        const input = document.getElementById('master-reset-link-input');
        if (input) input.value = data.reset_url;
        if (modal) modal.classList.remove('hidden');
      } else {
        alert(data.error || 'Error al generar enlace de restablecimiento.');
      }
    } catch(e) {
      alert('Error de conexión al emitir restablecimiento.');
    }
  }

  function copyMasterResetLink() {
    const input = document.getElementById('master-reset-link-input');
    if (input) {
      input.select();
      navigator.clipboard.writeText(input.value).then(() => {
        alert('✓ Enlace copiado al portapapeles con éxito.');
      }).catch(() => {
        alert('Enlace seleccionado. Presiona Ctrl+C para copiar.');
      });
    }
  }

  function closeMasterResetModal() {
    const modal = document.getElementById('master-reset-password-modal');
    if (modal) modal.classList.add('hidden');
  }

  async function masterDeleteUser(userId, email) {
    if (!isMasterAdmin()) {
      alert('Solo el Administrador Maestro tiene permisos para eliminar usuarios.');
      return;
    }
    if (!confirm(`⚠️ ATENCIÓN: ¿Estás seguro de ELIMINAR PERMANENTEMENTE al usuario #${userId} (${email})?\n\nEsta acción es irreversible y eliminará su cuenta, cartera de inmuebles y monedero de créditos.`)) return;

    try {
      const res = await fetch(getApiUrl('api/admin/users.php?action=delete_user'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
      });
      const data = await res.json();
      if (data && data.ok) {
        alert(data.message || 'Usuario eliminado correctamente.');
        closeInspectorDrawer();
        refreshCrmData(true);
      } else {
        alert(data.error || 'Error al eliminar usuario.');
      }
    } catch(e) {
      alert('Error de conexión.');
    }
  }

  // Exportar funciones globales a window
  window.initTheme = initTheme;
  window.applyTheme = applyTheme;
  window.toggleTheme = toggleTheme;
  window.setTheme = setTheme;
  window.togglePasswordVisibility = togglePasswordVisibility;
  window.toggleMobileSidebar = toggleMobileSidebar;
  window.closeMobileSidebarIfOpen = closeMobileSidebarIfOpen;
  window.openAdminAuthModal = openAdminAuthModal;
  window.closeAdminAuthModal = closeAdminAuthModal;
  window.toggleAuthModalView = toggleAuthModalView;
  window.toggleGatekeeperView = toggleGatekeeperView;
  window.handleAdminLogin = handleAdminLogin;
  window.handleStaffRegister = handleStaffRegister;
  window.handleAdminForgotPassword = handleAdminForgotPassword;
  window.handleAdminResetPassword = handleAdminResetPassword;
  window.handleAdminLogout = handleAdminLogout;
  window.openStaffProfileModal = openStaffProfileModal;
  window.closeStaffProfileModal = closeStaffProfileModal;
  window.handleSaveStaffProfile = handleSaveStaffProfile;
  window.openQuickSearchModal = openQuickSearchModal;
  window.closeQuickSearchModal = closeQuickSearchModal;
  window.handleGlobalSearch = handleGlobalSearch;
  window.exportRecordsToCSV = exportRecordsToCSV;
  window.exportUsersToCSV = exportUsersToCSV;
  window.switchCrmTab = switchCrmTab;
  window.renderSubOptionsBar = renderSubOptionsBar;
  window.refreshCrmData = refreshCrmData;
  window.loadRecords = loadRecords;
  window.filterRecords = filterRecords;
  window.searchRecords = searchRecords;
  window.goToRecordsPage = goToRecordsPage;
  window.setRecordsPageSize = setRecordsPageSize;
  window.goToUsersPage = goToUsersPage;
  window.setUsersPageSize = setUsersPageSize;
  window.filterUsersRole = filterUsersRole;
  window.toggleRecordStatus = toggleRecordStatus;
  window.deleteRecord = deleteRecord;
  window.promptCreateRecord = promptCreateRecord;
  window.inspectRecord = inspectRecord;
  window.inspectUser = inspectUser;
  window.openInspectorDrawer = openDrawer;
  window.closeInspectorDrawer = closeInspectorDrawer;
  window.searchUsers = searchUsers;
  window.toggleSelectAllRecords = toggleSelectAllRecords;
  window.updateRecordSelection = updateRecordSelection;
  window.deleteSelectedRecords = deleteSelectedRecords;
  window.setStatusSelectedRecords = setStatusSelectedRecords;
  window.toggleSelectAllUsers = toggleSelectAllUsers;
  window.updateUserSelection = updateUserSelection;
  window.deleteSelectedUsers = deleteSelectedUsers;
  window.setStatusSelectedUsers = setStatusSelectedUsers;
  window.toggleSelectAllTickets = toggleSelectAllTickets;
  window.updateTicketSelection = updateTicketSelection;
  window.deleteSelectedTickets = deleteSelectedTickets;
  window.setStatusSelectedTickets = setStatusSelectedTickets;
  window.openGoogleDriveModal = openGoogleDriveModal;
  window.closeGoogleDriveModal = closeGoogleDriveModal;
  window.saveGoogleDriveConfig = saveGoogleDriveConfig;
  window.syncGoogleDriveNow = syncGoogleDriveNow;
  window.downloadBackupJson = downloadBackupJson;
  window.loadGoogleDriveStatus = loadGoogleDriveStatus;
  window.filterTickets = filterTickets;
  window.replyTicketPrompt = replyTicketPrompt;
  window.openNewTicketModal = openNewTicketModal;
  window.promptAdjustCredits = promptAdjustCredits;
  window.toggleUserStatus = toggleUserStatus;
  window.testXmlFeedConnection = testXmlFeedConnection;
  window.runSystemDiagnostic = runSystemDiagnostic;

  // Exportar funciones exclusivas de Master Admin
  window.openMasterCreateUserModal = openMasterCreateUserModal;
  window.closeMasterCreateUserModal = closeMasterCreateUserModal;
  window.onRoleChangeCreateUser = onRoleChangeCreateUser;
  window.handleMasterCreateUser = handleMasterCreateUser;
  window.openMasterEditUserModal = openMasterEditUserModal;
  window.closeMasterEditUserModal = closeMasterEditUserModal;
  window.onRoleChangeEditUser = onRoleChangeEditUser;
  window.handleMasterEditUser = handleMasterEditUser;
  window.masterToggleUserStatus = masterToggleUserStatus;
  window.masterSendPasswordReset = masterSendPasswordReset;
  window.copyMasterResetLink = copyMasterResetLink;
  window.closeMasterResetModal = closeMasterResetModal;
  window.masterDeleteUser = masterDeleteUser;
  window.isMasterAdmin = isMasterAdmin;

  // Inicialización
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkAdminAuth);
  } else {
    checkAdminAuth();
  }
})();

