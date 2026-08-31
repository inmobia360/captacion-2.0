<?php
/**
 * Compra Captación - Master Production SPA Entry Point
 */
$captacion_host = $_SERVER['HTTP_HOST'] ?? '';
if (stripos($captacion_host, 'crm.') === 0 || stripos($captacion_host, 'crm.xn--') === 0) {
    require_once __DIR__ . '/crm/index.php';
    exit;
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/api/database.php';
require_once __DIR__ . '/api/auth.php';

$captacion_req_uri = $_SERVER['REQUEST_URI'] ?? '/';
$captacion_req_path = trim(parse_url($captacion_req_uri, PHP_URL_PATH) ?: '/', '/');
$captacion_first_seg = explode('/', $captacion_req_path)[0] ?? '';
if (strpos($captacion_req_path, 'area-privada') !== false) {
    $captacion_first_seg = 'area-privada';
}
$captacion_page_map = [
    '' => 'page-inicio',
    'inicio' => 'page-inicio',
    'marketplace' => 'page-marketplace',
    'propiedades' => 'page-marketplace',
    'demandas' => 'page-buscar-captaciones',
    'buscar-captaciones' => 'page-buscar-captaciones',
    'publicar' => 'page-publicar',
    'publicar-propiedad' => 'page-publicar',
    'publicar-demanda' => 'page-publicar',
    'ofrecer-captacion' => 'page-publicar',
    'como-funciona' => 'page-como-funciona',
    'precios' => 'page-planes',
    'planes' => 'page-planes',
    'recursos' => 'page-recursos',
    'contacto' => 'page-contacto',
    'coincidencias-ventas' => 'page-coincidencias-ventas',
    'aviso-legal' => 'page-aviso-legal',
    'privacidad' => 'page-privacidad',
    'cookies' => 'page-cookies',
    'normas-publicacion' => 'page-normas-publicacion',
    'condiciones-de-contratacion' => 'page-condiciones-de-contratacion',
    'politica-reembolsos' => 'page-politica-reembolsos',
    'datos-ciegos' => 'page-datos-ciegos',
    'canal-de-denuncias' => 'page-canal-de-denuncias',
    'area-privada' => 'page-area-privada',
];
$captacion_active_page_id = $captacion_page_map[$captacion_first_seg] ?? ($captacion_page_map[$captacion_req_path] ?? 'page-inicio');

if (!function_exists('esc_url_raw')) { function esc_url_raw($u) { return filter_var($u, FILTER_SANITIZE_URL); } }
if (!function_exists('esc_url')) { function esc_url($u) { return filter_var((string)$u, FILTER_SANITIZE_URL); } }
if (!function_exists('esc_attr')) { function esc_attr($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('esc_html')) { function esc_html($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('trailingslashit')) { function trailingslashit($s) { return rtrim((string)$s, '/\\') . '/'; } }
if (!function_exists('home_url')) { function home_url($p = '') { return '/' . ltrim((string)$p, '/'); } }
if (!function_exists('rest_url')) { 
    function rest_url($p = '') { 
        $path = ltrim((string)$p, '/');
        if (strpos($path, 'xml-feeds') !== false) return '/api/xml_feeds.php';
        if (strpos($path, 'public-records') !== false || strpos($path, 'records') !== false) return '/api/records.php';
        if (strpos($path, 'login') !== false) return '/api/auth.php?action=login';
        if (strpos($path, 'register') !== false) return '/api/auth.php?action=register';
        if (strpos($path, 'logout') !== false) return '/api/auth.php?action=logout';
        if (strpos($path, 'marketplace-access/status') !== false) return '/api/credits.php?action=status';
        if (strpos($path, 'marketplace-access/consume') !== false) return '/api/credits.php?action=consume';
        if (strpos($path, 'marketplace-access/purchase-intent') !== false) return '/api/stripe.php?action=create_checkout_session';
        if (strpos($path, 'credits/purchase') !== false) return '/api/stripe.php';
        if (strpos($path, 'credits/status') !== false) return '/api/credits.php?action=status';
        if (strpos($path, 'credits/ledger') !== false) return '/api/credits.php?action=ledger';
        if (strpos($path, 'referrals') !== false) return '/api/referrals.php';
        if (strpos($path, 'territories') !== false) return '/api/territories.php';
        return '/api/records.php';
    } 
}
if (!function_exists('wp_login_url')) { function wp_login_url($r = '') { return '#/panel'; } }
if (!function_exists('wp_lostpassword_url')) { function wp_lostpassword_url($r = '') { return '#/panel'; } }
if (!function_exists('get_user_meta')) { function get_user_meta($u, $k, $s = false) { return ''; } }
if (!function_exists('captacion_app_get_user_access_state')) { function captacion_app_get_user_access_state($u) { return ['has_access' => true, 'unlocked_records' => []]; } }
if (!function_exists('is_user_logged_in')) { function is_user_logged_in() { return !empty($_SESSION['user_id']); } }
if (!function_exists('get_current_user_id')) { function get_current_user_id() { return $_SESSION['user_id'] ?? 0; } }
if (!function_exists('wp_get_current_user')) { function wp_get_current_user() { $u = get_auth_user(); return (object)($u ?: ['ID' => 0, 'display_name' => '', 'user_email' => '', 'first_name' => '', 'last_name' => '', 'user_login' => '']); } }
if (!function_exists('wp_create_nonce')) { function wp_create_nonce($a = '') { return 'nonce_' . time(); } }
if (!function_exists('captacion_app_setting')) { function captacion_app_setting($k) { return ''; } }
if (!function_exists('captacion_app_resource_catalog')) { 
    function captacion_app_resource_catalog() { 
        return [
            [
                'resource_id' => 'colaboracion',
                'title' => 'Contrato Oficial de Colaboración 50/50 y Reparto de Honorarios',
                'description' => 'Documento vinculante homologado para formalizar el reparto de honorarios al 50% antes de la visita con devengo en notaría.',
                'tag' => 'Seguridad Jurídica',
                'plan_access' => 'free',
                'static_pdf_url' => 'assets/docs/plantilla-acuerdo-colaboracion-honorarios-captacion-app.pdf'
            ],
            [
                'resource_id' => 'nda',
                'title' => 'Acuerdo de Confidencialidad y Custodia de Datos (NDA)',
                'description' => 'Blindaje frente a contacto directo con el cliente propietario y protección estricta de notas simples y catastro.',
                'tag' => 'Protección de Datos',
                'plan_access' => 'free',
                'static_pdf_url' => 'assets/docs/plantilla-nda-confidencialidad-captacion-app.pdf'
            ],
            [
                'resource_id' => 'parte_visita',
                'title' => 'Hoja Oficial de Registro y Reconocimiento de Visita Compartida',
                'description' => 'Parte de visita 50/50 firmado en el acto que acredita la presentación del comprador por la agencia colaboradora durante 12 meses.',
                'tag' => 'Acreditación de Visitas',
                'plan_access' => 'free',
                'static_pdf_url' => 'assets/docs/plantilla-parte-visita-colaboracion-captacion-app.pdf'
            ],
            [
                'resource_id' => 'pitch_exclusiva',
                'title' => 'Dossier Ejecutivo: Cómo Vender tu Casa con Exclusiva Compartida',
                'description' => 'Presentación comercial de alto impacto para convencer al propietario reacio de las ventajas de la red colaborativa.',
                'tag' => 'Captación de Exclusivas',
                'plan_access' => 'professional',
                'static_pdf_url' => 'assets/docs/dossier-exclusiva-compartida-propietario-captacion.pdf'
            ],
            [
                'resource_id' => 'score_comprador',
                'title' => 'Matriz y Checklist de Pre-Cualificación Financiera del Comprador',
                'description' => 'Sistema de scoring financiero, validación de fondos propios (30%) y cálculo del ratio de endeudamiento DTI.',
                'tag' => 'Cualificación Solvente',
                'plan_access' => 'professional',
                'static_pdf_url' => 'recursos/matriz-precualificacion-financiera-comprador.pdf'
            ],
            [
                'resource_id' => 'acm_vera',
                'title' => 'Generador de Análisis Comparativo de Mercado (ACM) con IA Vera',
                'description' => 'Informe técnico de valoración con testigos de venta reales y justificación de precio óptimo ante el vendedor.',
                'tag' => 'Valoración con IA',
                'plan_access' => 'professional',
                'static_pdf_url' => 'recursos/informe-acm-valoracion-mercado-ia-vera.pdf'
            ],
            [
                'resource_id' => 'oferta_reserva',
                'title' => 'Propuesta Formal de Compra con Depósito de Reserva Blindada',
                'description' => 'Documento vinculante de intención firme de compra con consignación de señal y plazo de aceptación por el vendedor.',
                'tag' => 'Cierre de Ofertas',
                'plan_access' => 'professional',
                'static_pdf_url' => 'recursos/propuesta-formal-compra-deposito-reserva.pdf'
            ],
            [
                'resource_id' => 'guia_fiscal',
                'title' => 'Guía y Calculadora Fiscal de Compraventas (ITP, Plusvalía e IRPF)',
                'description' => 'Dossier con tablas autonómicas actualizadas para liquidar con precisión los gastos de notaría, registro e impuestos.',
                'tag' => 'Fiscalidad Inmobiliaria',
                'plan_access' => 'professional',
                'static_pdf_url' => 'recursos/guia-fiscalidad-inmobiliaria-liquidaciones.pdf'
            ],
            [
                'resource_id' => 'arras_1454',
                'title' => 'Modelo de Contrato de Arras Penitenciales (Art. 1454 CC con Hipoteca)',
                'description' => 'Contrato blindado con cláusula resolutoria de financiación bancaria para proteger a las partes y los honorarios.',
                'tag' => 'Seguridad Jurídica Pro',
                'plan_access' => 'professional',
                'static_pdf_url' => 'recursos/contrato-arras-penitenciales-art1454-cc.pdf'
            ]
        ]; 
    } 
}
if (!function_exists('body_class')) { function body_class($class = '') { echo 'class="captacion-app-body ' . esc_attr($class) . '"'; } }
if (!function_exists('language_attributes')) { function language_attributes($doctype = 'html') { echo 'lang="es"'; } }
if (!function_exists('bloginfo')) { function bloginfo($show = '') { echo 'Compra Captación'; } }
if (!function_exists('get_header')) { function get_header($name = null, $args = []) {} }
if (!function_exists('get_footer')) { function get_footer($name = null, $args = []) {} }
if (!function_exists('get_sidebar')) { function get_sidebar($name = null, $args = []) {} }
if (!function_exists('get_template_part')) { function get_template_part($slug, $name = null, $args = []) {} }
if (!function_exists('wp_head')) { function wp_head() {} }
if (!function_exists('wp_footer')) { function wp_footer() {} }
if (!function_exists('wp_body_open')) { function wp_body_open() {} }
if (!function_exists('get_theme_file_path')) { function get_theme_file_path($file = '') { return __DIR__ . '/' . ltrim($file, '/'); } }
if (!function_exists('wp_json_encode')) { function wp_json_encode($data, $options = 0, $depth = 512) { return json_encode($data, $options, $depth); } }
if (!function_exists('wp_parse_url')) { function wp_parse_url($url, $component = -1) { return parse_url($url, $component); } }
if (!function_exists('shortcode_exists')) { function shortcode_exists($tag) { return false; } }
if (!function_exists('do_shortcode')) { function do_shortcode($content, $ignore_html = false) { return $content; } }
if (!function_exists('apply_filters')) { function apply_filters($hook_name, $value, ...$args) { return $value; } }
if (!function_exists('do_action')) { function do_action($hook_name, ...$args) {} }
if (!function_exists('captacion_app_resource_template_pdf_url')) { 
    function captacion_app_resource_template_pdf_url($id) { 
        $map = [
            'colaboracion' => 'assets/docs/plantilla-acuerdo-colaboracion-honorarios-captacion-app.pdf',
            'nda' => 'assets/docs/plantilla-nda-confidencialidad-captacion-app.pdf',
            'parte_visita' => 'assets/docs/plantilla-parte-visita-colaboracion-captacion-app.pdf',
            'pitch_exclusiva' => 'assets/docs/dossier-exclusiva-compartida-propietario-captacion.pdf',
            'score_comprador' => 'assets/docs/matriz-precualificacion-financiera-comprador.pdf',
            'acm_vera' => 'assets/docs/informe-acm-valoracion-mercado-ia-vera.pdf',
            'oferta_reserva' => 'assets/docs/propuesta-formal-compra-deposito-reserva.pdf',
            'guia_fiscal' => 'assets/docs/guia-fiscalidad-inmobiliaria-liquidaciones.pdf',
            'arras_1454' => 'assets/docs/contrato-arras-penitenciales-art1454-cc.pdf'
        ];
        return $map[$id] ?? 'assets/docs/plantilla-acuerdo-colaboracion-honorarios-captacion-app.pdf';
    } 
}
if (!function_exists('current_user_can')) { function current_user_can($capability, ...$args) { $u = get_auth_user(); return !empty($u) && ($u['role'] === 'admin' || $capability === 'read'); } }
if (!function_exists('user_can')) { function user_can($user, $capability, ...$args) { return true; } }
if (!function_exists('is_admin')) { function is_admin() { return false; } }
if (!function_exists('captacion_app_is_email_verified')) { function captacion_app_is_email_verified($uid) { return true; } }
if (!function_exists('captacion_app_get_user_access_state')) { function captacion_app_get_user_access_state($u) { return ['has_access' => true, 'unlocked_records' => []]; } }

$captacion_theme_uri = function_exists('get_stylesheet_directory_uri') ? get_stylesheet_directory_uri() : (function_exists('get_template_directory_uri') ? get_template_directory_uri() : '');
if (empty($captacion_theme_uri) || $captacion_theme_uri === '.') {
    $captacion_theme_uri = '';
}
$captacion_media_url = function($path) use ($captacion_theme_uri) {
    if (function_exists('get_theme_file_uri')) {
        return get_theme_file_uri('assets/' . ltrim($path, '/'));
    }
    $base = rtrim((string)$captacion_theme_uri, '/');
    return ($base !== '' ? $base : '') . '/assets/' . ltrim($path, '/');
};
$captacion_video_webm_url = $captacion_media_url('media/compracaptacion_video.webm');
$captacion_vera_image_url = $captacion_media_url('media/Vera_assistent_.png');
$captacion_favicon_url = $captacion_media_url('media/favicon-compra-captacion.png');
$captacion_favicon_animated_url = $captacion_media_url('media/favicon-animated.svg');
$captacion_has_explainer_video = true;
$captacion_media = array(
  'logo' => $captacion_media_url('media/logo-compra-captacion-horizontal.png'),
  'video_mp4' => $captacion_media_url('media/video-explicativo-captacion-app.mp4'),
  'video_webm' => $captacion_video_webm_url,
  'video_poster' => $captacion_media_url('media/poster-video-captacion-app.webp'),
  'property_defaults' => array(
    'piso' => $captacion_media_url('media/property-defaults/piso-default.jpg'),
    'casa_chalet' => $captacion_media_url('media/property-defaults/casa-chalet-default.jpg'),
    'comercial' => $captacion_media_url('media/property-defaults/comercial-default.jpg'),
    'nave' => $captacion_media_url('media/property-defaults/nave-default.jpg'),
    'oficina' => $captacion_media_url('media/property-defaults/oficina-default.jpg'),
    'edificio' => $captacion_media_url('media/property-defaults/edificio-default.jpg'),
    'terreno' => $captacion_media_url('media/property-defaults/terreno-default.jpg'),
  ),
);
$captacion_brand_name = 'Compra Captación';
$captacion_og_default_image = 'https://compracaptacion.com/assets/media/og-share-landing.jpg';

$captacion_seo_routes = [
    'page-inicio' => [
        'title' => 'Compra Captación | Plataforma de Colaboración entre Profesionales Inmobiliarios',
        'description' => 'Conecta con agentes inmobiliarios en España, comparte captaciones al 50/50 y encuentra inmuebles para tus compradores con datos protegidos. Empieza gratis.',
        'canonical' => 'https://compracaptacion.com/',
        'robots' => 'index, follow'
    ],
    'page-marketplace' => [
        'title' => 'Inmuebles en Colaboración | Cartera Compartida para Profesionales | Compra Captación',
        'description' => 'Explora inmuebles verificados para tus compradores. Colabora directamente con agencias y agentes en toda España con reparto de honorarios garantizado.',
        'canonical' => 'https://compracaptacion.com/propiedades',
        'robots' => 'index, follow'
    ],
    'page-buscar-captaciones' => [
        'title' => 'Demandas de Compradores Activas | Oportunidades de Venta Inmobiliaria | Compra Captación',
        'description' => 'Accede a demandas de compradores solventes con fondos listos. Da salida a tus captaciones contactando directamente con el agente del comprador.',
        'canonical' => 'https://compracaptacion.com/demandas',
        'robots' => 'index, follow'
    ],
    'page-publicar' => [
        'title' => 'Publicar Captación o Demanda | Red de Colaboración Inmobiliaria | Compra Captación',
        'description' => 'Publica tus captaciones o demandas de compradores en minutos. Conecta con agentes cualificados y multiplica tus cierres con acuerdos protegidos.',
        'canonical' => 'https://compracaptacion.com/publicar',
        'robots' => 'index, follow'
    ],
    'page-como-funciona' => [
        'title' => 'Cómo Funciona Compra Captación | Protocolo de Colaboración Inmobiliaria 50/50',
        'description' => 'Descubre cómo colaborar con seguridad: acuerdos 50/50 vinculantes, registro de visitas, protección de honorarios y cruces automáticos de compradores.',
        'canonical' => 'https://compracaptacion.com/como-funciona',
        'robots' => 'index, follow'
    ],
    'page-planes' => [
        'title' => 'Planes y Precios | Acceso Flexible para Agentes y Agencias | Compra Captación',
        'description' => 'Consulta nuestros planes de acceso profesional y paquetes de créditos para desbloquear contactos y colaborar con total libertad y sin cuotas ocultas.',
        'canonical' => 'https://compracaptacion.com/planes',
        'robots' => 'index, follow'
    ],
    'page-recursos' => [
        'title' => 'Recursos y Contratos Inmobiliarios | Plantillas de Colaboración y NDA | Compra Captación',
        'description' => 'Descarga contratos oficiales de colaboración 50/50, acuerdos de confidencialidad NDA y hojas de visita homologadas para operaciones seguras.',
        'canonical' => 'https://compracaptacion.com/recursos',
        'robots' => 'index, follow'
    ],
    'page-coincidencias-ventas' => [
        'title' => 'Coincidencias Inteligentes de Venta | Cruce Inmuebles y Compradores | Compra Captación',
        'description' => 'Motor inteligente de cruce entre captaciones y compradores cualificados. Acelera tus ventas detectando agentes con clientes compatibles al instante.',
        'canonical' => 'https://compracaptacion.com/coincidencias-ventas',
        'robots' => 'index, follow'
    ],
    'page-contacto' => [
        'title' => 'Contacto y Soporte Profesional | Compra Captación',
        'description' => '¿Tienes dudas o necesitas soporte para tu agencia? Contacta con el equipo de Compra Captación y te ayudaremos a potenciar tus colaboraciones.',
        'canonical' => 'https://compracaptacion.com/contacto',
        'robots' => 'index, follow'
    ],
    'page-aviso-legal' => [
        'title' => 'Aviso Legal y Condiciones de Uso | Compra Captación',
        'description' => 'Información legal, titularidad y términos de servicio de la plataforma Compra Captación para profesionales del sector inmobiliario.',
        'canonical' => 'https://compracaptacion.com/aviso-legal',
        'robots' => 'index, follow'
    ],
    'page-privacidad' => [
        'title' => 'Política de Privacidad y Protección de Datos RGPD | Compra Captación',
        'description' => 'Conoce el tratamiento y la protección estricta de tus datos personales y profesionales conforme al Reglamento General de Protección de Datos (RGPD).',
        'canonical' => 'https://compracaptacion.com/privacidad',
        'robots' => 'index, follow'
    ],
    'page-cookies' => [
        'title' => 'Política de Cookies | Compra Captación',
        'description' => 'Información sobre el uso de cookies técnicas y analíticas en la plataforma Compra Captación para optimizar tu experiencia de navegación.',
        'canonical' => 'https://compracaptacion.com/cookies',
        'robots' => 'index, follow'
    ],
    'page-normas-publicacion' => [
        'title' => 'Normas de Publicación y Conducta Profesional | Compra Captación',
        'description' => 'Directrices éticas y estándares de calidad para la publicación de inmuebles y demandas en la red de profesionales de Compra Captación.',
        'canonical' => 'https://compracaptacion.com/normas-publicacion',
        'robots' => 'index, follow'
    ],
    'page-condiciones-de-contratacion' => [
        'title' => 'Condiciones Generales de Contratación | Compra Captación',
        'description' => 'Términos contractuales y normativas para la adquisición de créditos y suscripciones en la plataforma Compra Captación.',
        'canonical' => 'https://compracaptacion.com/condiciones-de-contratacion',
        'robots' => 'index, follow'
    ],
    'page-canal-de-denuncias' => [
        'title' => 'Canal Ético y de Denuncias | Compra Captación',
        'description' => 'Canal confidencial de comunicación y denuncias conforme a la Ley 2/2023 de protección del informante en Compra Captación.',
        'canonical' => 'https://compracaptacion.com/canal-de-denuncias',
        'robots' => 'index, follow'
    ],
    'page-area-privada' => [
        'title' => 'Área Privada | Panel de Control Profesional | Compra Captación',
        'description' => 'Gestión de cartera, demandas, contactos y operaciones de colaboración en el panel privado de Compra Captación.',
        'canonical' => 'https://compracaptacion.com/area-privada',
        'robots' => 'noindex, nofollow'
    ],
];

$captacion_current_seo = $captacion_seo_routes[$captacion_active_page_id] ?? $captacion_seo_routes['page-inicio'];
$captacion_site_title = $captacion_current_seo['title'];
$captacion_meta_description = $captacion_current_seo['description'];
$captacion_canonical_url = $captacion_current_seo['canonical'];
$captacion_robots = $captacion_current_seo['robots'] ?? 'index, follow';
$captacion_is_staging_host = str_contains(strtolower((string)($_SERVER['HTTP_HOST'] ?? '')), 'hostingersite.com');
if ($captacion_is_staging_host) {
    // Staging must never compete with the production domain in search engines.
    $captacion_robots = 'noindex, nofollow, noarchive';
    $captacion_canonical_url = '';
}

$captacion_contact_email = 'hola@compracaptacion.com';
$captacion_stripe_link = captacion_app_setting('stripe_payment_link');
$captacion_membership_links = array(
  'initial' => captacion_app_setting('stripe_membership_initial_link'),
  'initial_annual' => captacion_app_setting('stripe_membership_initial_annual_link'),
  'professional' => captacion_app_setting('stripe_membership_professional_link'),
  'professional_annual' => captacion_app_setting('stripe_membership_professional_annual_link'),
  'premium' => captacion_app_setting('stripe_membership_agency_link'),
  'premium_annual' => captacion_app_setting('stripe_membership_agency_annual_link'),
);
$captacion_resource_cards = array();
foreach (captacion_app_resource_catalog() as $resource) {
  $resource_id = $resource['resource_id'];
  $captacion_resource_cards[] = array(
    'id' => $resource_id,
    'resource_id' => $resource_id,
    'title' => $resource['title'],
    'description' => $resource['description'],
    'tag' => $resource['tag'] ?? 'Herramienta Inmobiliaria',
    'plan_access' => $resource['plan_access'],
    'has_static_pdf' => true,
    'pdf_url' => function_exists('captacion_app_resource_template_pdf_url') ? esc_url_raw(captacion_app_resource_template_pdf_url($resource_id)) : esc_url_raw($resource['static_pdf_url']),
    'create_url' => home_url('/recursos/crear-pdf/?resource=' . rawurlencode($resource_id)),
  );
}
$captacion_user = wp_get_current_user();
$captacion_current_user = $captacion_user;
$captacion_is_logged_in = is_user_logged_in();
$captacion_current_user_id = $captacion_is_logged_in ? get_current_user_id() : 0;
$captacion_wp_rest_nonce = wp_create_nonce('wp_rest');
$captacion_user_display_name = '';
if ($captacion_is_logged_in) {
  $display_name = trim((string) $captacion_user->display_name);
  $full_name = trim((string) $captacion_user->first_name . ' ' . (string) $captacion_user->last_name);
  $login_name = trim((string) $captacion_user->user_login);
  $email = trim((string) $captacion_user->user_email);
  $captacion_user_display_name = ($display_name && strcasecmp($display_name, $email) !== 0)
    ? $display_name
    : ($full_name ?: ($login_name ?: $email));
}
$captacion_mailchimp_config = array(
  'endpoint' => esc_url_raw(rest_url('captacion/v1/mailchimp/subscribe')),
  'notificationsEndpoint' => esc_url_raw(rest_url('captacion/v1/notifications/send')),
  'recordsEndpoint' => esc_url_raw(rest_url('captacion/v1/records')),
  'publicRecordsEndpoint' => esc_url_raw(rest_url('captacion/v1/public-records')),
  'registerEndpoint' => esc_url_raw(rest_url('captacion/v1/register')),
  'loginEndpoint' => esc_url_raw(rest_url('captacion/v1/login')),
  'resendVerificationEndpoint' => esc_url_raw(rest_url('captacion/v1/verification/resend')),
  'logoutEndpoint' => esc_url_raw(rest_url('captacion/v1/logout')),
  'accessStatusEndpoint' => esc_url_raw(rest_url('captacion/v1/marketplace-access/status')),
  'accessConsumeEndpoint' => esc_url_raw(rest_url('captacion/v1/marketplace-access/consume')),
  'accessPurchaseEndpoint' => esc_url_raw(rest_url('captacion/v1/marketplace-access/purchase-intent')),
  'creditPurchaseEndpoint' => esc_url_raw(rest_url(defined('CMC_VERSION') ? 'cmc/v1/credits/purchase' : 'captacion/v1/credits/purchase')),
  'creditsStatusEndpoint' => esc_url_raw(rest_url(defined('CMC_VERSION') ? 'cmc/v1/credits/status' : 'captacion/v1/credits/status')),
  'creditsLedgerEndpoint' => esc_url_raw(rest_url(defined('CMC_VERSION') ? 'cmc/v1/credits/ledger' : 'captacion/v1/credits/ledger')),
  'referralsEndpoint' => esc_url_raw(rest_url('captacion/v1/referrals')),
  'tasksEndpoint' => esc_url_raw(rest_url('captacion/v1/tasks')),
  'contactEndpoint' => esc_url_raw(rest_url('captacion/v1/contact')),
  'reportEndpoint' => esc_url_raw(rest_url('captacion/v1/reports')),
  'betaFeedbackEndpoint' => esc_url_raw(rest_url('captacion/v1/beta-feedback')),
  'betaProgram' => array('days' => 30, 'newUserCredits' => 3, 'founderCredits' => 3, 'creditValue' => 10, 'plansLocked' => true),
  'supportEmail' => $captacion_contact_email,
  'loginUrl' => esc_url_raw(wp_login_url()),
  'lostPasswordUrl' => esc_url_raw(wp_lostpassword_url(home_url('/'))),
  'socialLoginEnabled' => captacion_app_setting('social_login_enabled') === '1',
  'territoriesEndpoint' => esc_url_raw(rest_url('captacion/v1/territories')),
  'territoryValidationEndpoint' => esc_url_raw(rest_url('captacion/v1/address/validate')),
  'loggedIn' => $captacion_is_logged_in,
  'emailVerified' => $captacion_is_logged_in ? captacion_app_is_email_verified($captacion_current_user_id) : false,
  'commercialConsent' => $captacion_is_logged_in ? get_user_meta($captacion_current_user_id, 'captacion_commercial_consent', true) === '1' : false,
  'currentUser' => $captacion_is_logged_in ? array(
    'name' => $captacion_user_display_name,
    'displayName' => $captacion_user_display_name,
    'firstName' => $captacion_user->first_name,
    'lastName' => $captacion_user->last_name,
    'username' => $captacion_user->user_login,
    'email' => $captacion_user->user_email,
    'phone' => get_user_meta($captacion_current_user_id, 'captacion_phone', true),
    'profileType' => get_user_meta($captacion_current_user_id, 'captacion_profile_type', true),
    'businessName' => get_user_meta($captacion_current_user_id, 'captacion_business_name', true),
  ) : null,
  'accessState' => $captacion_is_logged_in ? captacion_app_get_user_access_state($captacion_current_user_id) : null,
  'resources' => $captacion_resource_cards,
  'nonce' => $captacion_wp_rest_nonce,
);
$captacion_rest_nonce = $captacion_is_logged_in ? $captacion_wp_rest_nonce : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="color-scheme" content="light dark" />
  <meta id="theme-color-meta" name="theme-color" content="#eef3f8" />
  <meta name="robots" content="<?php echo esc_attr($captacion_robots); ?>" />
  <meta name="description" content="<?php echo esc_attr($captacion_meta_description); ?>" />
  <title><?php echo esc_html($captacion_site_title); ?></title>
  <?php if ($captacion_canonical_url !== ''): ?><link id="captacion-canonical" rel="canonical" href="<?php echo esc_url($captacion_canonical_url); ?>" /><?php endif; ?>

  <!-- Progressive Web App (PWA) Manifest & Mobile Meta Tags -->
  <link rel="manifest" href="/manifest.json" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <meta name="apple-mobile-web-app-title" content="Compra Captación" />
  <meta name="application-name" content="Compra Captación" />
  <meta name="msapplication-TileColor" content="#0B1528" />
  <meta name="msapplication-TileImage" content="/assets/media/icon-192.png" />
  <link rel="apple-touch-icon" href="/assets/media/apple-touch-icon.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/media/apple-touch-icon.png" />
  <link rel="icon" type="image/png" sizes="192x192" href="/assets/media/icon-192.png" />
  <link rel="icon" type="image/png" sizes="512x512" href="/assets/media/icon-512.png" />

  <!-- Open Graph / Social Sharing Meta Tags -->
  <meta property="og:type" content="website" />
  <meta property="og:locale" content="es_ES" />
  <meta property="og:site_name" content="Compra Captación" />
  <meta property="og:title" content="<?php echo esc_attr($captacion_site_title); ?>" />
  <meta property="og:description" content="<?php echo esc_attr($captacion_meta_description); ?>" />
  <meta property="og:url" content="<?php echo esc_url($captacion_canonical_url); ?>" />
  <meta property="og:image" content="<?php echo esc_url($captacion_og_default_image); ?>" />
  <meta property="og:image:secure_url" content="<?php echo esc_url($captacion_og_default_image); ?>" />
  <meta property="og:image:type" content="image/jpeg" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:image:alt" content="Compra Captación - Plataforma para profesionales del sector inmobiliario" />

  <!-- Twitter Cards -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?php echo esc_attr($captacion_site_title); ?>" />
  <meta name="twitter:description" content="<?php echo esc_attr($captacion_meta_description); ?>" />
  <meta name="twitter:image" content="<?php echo esc_url($captacion_og_default_image); ?>" />
  <meta name="twitter:image:alt" content="Compra Captación - Colaboración Profesional Inmobiliaria" />

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Schema.org JSON-LD Structured Data for SEO -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "SoftwareApplication",
        "name": "Compra Captación",
        "operatingSystem": "All",
        "applicationCategory": "BusinessApplication",
        "offers": {
          "@type": "Offer",
          "price": "0",
          "priceCurrency": "EUR"
        },
        "description": "Plataforma privada que facilita el contacto y la colaboración directa entre profesionales del sector inmobiliario en España con reparto de honorarios 50/50 y trazabilidad blindada."
      },
      {
        "@type": "RealEstateAgent",
        "name": "Compra Captación",
        "url": "https://compracaptacion.com",
        "areaServed": "ES"
      }
    ]
  }
  </script>

  <!-- Tailwind CSS Engine & CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
            display: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace']
          },
          colors: {
            navy: { DEFAULT: '#0b192c', dark: '#050c17', light: '#142944' },
            blue: { DEFAULT: '#1b67d6', dark: '#0d4eae', light: '#e8f4ff', neon: '#38bdf8' },
            green: { DEFAULT: '#059669', dark: '#047857', light: '#ecfdf5', emerald: '#10b981' },
            amber: { DEFAULT: '#d97706', light: '#fffbeb' }
          }
        }
      }
    };
  </script>

  <!-- Theme Init Script -->
  <script>
    (function () {
      try {
        var storedTheme = localStorage.getItem('captacion_theme_v1') || localStorage.getItem('theme') || 'light';
        document.documentElement.dataset.theme = storedTheme;
        if (storedTheme === 'dark') {
          document.documentElement.classList.add('dark');
        } else {
          document.documentElement.classList.remove('dark');
        }
      } catch (error) {
        document.documentElement.dataset.theme = 'light';
        document.documentElement.classList.remove('dark');
      }
    })();
  </script>

  <style>
    /* SVG & Icon dimension safeguards */
    svg {
      display: inline-block;
      vertical-align: middle;
      max-width: 100%;
    }
    .w-3 { width: 0.75rem !important; }
    .w-4 { width: 1rem !important; }
    .w-5 { width: 1.25rem !important; }
    .w-6 { width: 1.5rem !important; }
    .w-7 { width: 1.75rem !important; }
    .w-8 { width: 2rem !important; }
    .w-10 { width: 2.5rem !important; }
    .w-12 { width: 3rem !important; }
    .w-14 { width: 3.5rem !important; }
    .w-16 { width: 4rem !important; }
    .w-20 { width: 5rem !important; }
    .w-24 { width: 6rem !important; }
    .h-3 { height: 0.75rem !important; }
    .h-4 { height: 1rem !important; }
    .h-5 { height: 1.25rem !important; }
    .h-6 { height: 1.5rem !important; }
    .h-7 { height: 1.75rem !important; }
    .h-8 { height: 2rem !important; }
    .h-10 { height: 2.5rem !important; }
    .h-12 { height: 3rem !important; }
    .h-14 { height: 3.5rem !important; }
    .h-16 { height: 4rem !important; }
    .h-20 { height: 5rem !important; }
    .h-24 { height: 6rem !important; }

    html {
      font-size: 16px !important;
      scroll-behavior: smooth;
    }
    @media (min-width: 768px) {
      html {
        font-size: 16.5px !important;
      }
    }
    body {
      line-height: 1.6;
      text-rendering: optimizeLegibility;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      transition: background-color 0.2s ease, color 0.2s ease;
    }

    /* Reglas Globales de Tema Claro / Oscuro sin excepciones */
    html.dark, html[data-theme="dark"], body.dark {
      background-color: #020617 !important; /* Slate-950 */
      color: #f1f5f9 !important; /* Slate-100 */
    }
    html:not(.dark):not([data-theme="dark"]), body:not(.dark) {
      background-color: #f8fafc !important; /* Slate-50 */
      color: #0f172a !important; /* Slate-900 */
    }
    
    html.dark .text-navy, html[data-theme="dark"] .text-navy {
      color: #f8fafc !important;
    }
    html.dark .text-slate-600, html[data-theme="dark"] .text-slate-600,
    html.dark .text-slate-500, html[data-theme="dark"] .text-slate-500 {
      color: #cbd5e1 !important; /* Slate-300 */
    }
    html.dark .text-slate-400, html[data-theme="dark"] .text-slate-400 {
      color: #94a3b8 !important; /* Slate-400 */
    }
    html.dark .border-slate-200, html[data-theme="dark"] .border-slate-200,
    html.dark .border-slate-100, html[data-theme="dark"] .border-slate-100 {
      border-color: #1e293b !important; /* Slate-800 */
    }
    html.dark label, html[data-theme="dark"] label {
      color: #cbd5e1 !important;
    }

    /* Tipografía fluida y altamente legible */
    p {
      line-height: 1.65 !important;
    }
    .text-xs { font-size: 0.85rem !important; }
    .text-sm { font-size: 0.95rem !important; }
    .text-base { font-size: 1.05rem !important; }
    .text-lg { font-size: 1.18rem !important; }
    .text-xl { font-size: 1.35rem !important; }
    .text-2xl { font-size: 1.60rem !important; }
    .text-3xl { font-size: 2.00rem !important; }
    .text-4xl { font-size: 2.45rem !important; }
    .text-5xl { font-size: 3.10rem !important; }

    .font-black, .font-extrabold {
      font-weight: 700 !important;
    }
    .font-bold {
      font-weight: 600 !important;
    }

    #menu-btn:hover {
      background-color: #ffffff !important;
      color: #1b67d6 !important;
      border-color: #1b67d6 !important;
    }
    #menu-btn:hover #menu-icon-text {
      color: #1b67d6 !important;
    }
    
    /* Ocultar enlaces de Planes y secciones no deseadas del MVP */
    a[href="#/planes-premium"], .plan-link,
    [data-private-panel="subscriptions"], [data-private-panel="traceability"], [data-private-panel="ai"],
    option[value="subscriptions"], option[value="traceability"], option[value="ai"] {
      display: none !important;
    }
    body {
      font-family: 'Inter', sans-serif;
    }
    :where(a, button, input, select, textarea, summary):focus-visible {
      outline: 3px solid #1b67d6 !important;
      outline-offset: 3px !important;
    }
    @media (max-width: 767px) {
      .page-section button, .page-section a, .page-section input, .page-section select { min-height: 44px; }
    }
    .scrollbar-hidden::-webkit-scrollbar {
      display: none;
    }
    .scrollbar-hidden {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
    .scrollbar-custom::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    .scrollbar-custom::-webkit-scrollbar-track {
      background: rgba(27, 103, 214, 0.05);
      border-radius: 4px;
    }
    .scrollbar-custom::-webkit-scrollbar-thumb {
      background: #1b67d6;
      border-radius: 4px;
      border: 1px solid rgba(255, 255, 255, 0.8);
    }
    .scrollbar-custom::-webkit-scrollbar-thumb:hover {
      background: #0d4eae;
    }
    .scrollbar-custom {
      scrollbar-width: thin;
      scrollbar-color: #1b67d6 rgba(27, 103, 214, 0.05);
    }
    /* Transición suave para cambio de páginas */
    .page-section {
      animation: fadeIn 0.25s ease-in-out forwards;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }
    #home-map, #marketplace-map, #needs-map { min-height: 430px; }
    .leaflet-container { font-family: 'Inter', sans-serif; background: #e8eef5; }
    .leaflet-popup-content-wrapper { border-radius: 16px; }
    .leaflet-popup-content { margin: 14px 16px; }
    .map-label-div-icon { background: transparent; border: 0; }
    .map-price-pill, .map-demand-pill {
      display: inline-flex;
      min-width: 52px;
      align-items: center;
      justify-content: center;
      padding: 5px 8px;
      border-radius: 999px;
      border: 2px solid rgba(255,255,255,.95);
      color: white;
      font-size: 10px;
      font-weight: 900;
      line-height: 1;
      letter-spacing: -.02em;
      box-shadow: 0 5px 12px rgba(15, 23, 42, .24);
      white-space: nowrap;
    }
    .map-price-pill { background: #b00016; }
    .map-demand-pill { background: #087653; }
    .map-view-active { background: #10233c; color: white; border-color: #10233c; }
    .auth-tab-active { background: #10233c; color: white; box-shadow: 0 4px 12px rgba(16,35,60,.16); }
    .map-filter-active { background: #10233c; color: white; border-color: #10233c; }
    details.captacion-accordion > summary { list-style: none; }
    details.captacion-accordion > summary::-webkit-details-marker { display: none; }
    details.captacion-accordion[open] .captacion-accordion-chevron { transform: rotate(180deg); }
    .opportunity-accordion > summary { font-size:14px !important; line-height:1.45; }
    .opportunity-accordion > summary + div { font-size:14px !important; line-height:1.65; }
    .captacion-field-max-10ch { max-width: 10rem; }
    .captacion-field-max-12ch { max-width: 12rem; }
    .captacion-field-max-14ch { max-width: 14rem; }
    .captacion-field-max-18ch { max-width: 18rem; }
    #offer-publication-form { max-width: 1320px; }
    #offer-publication-form .grid { gap: 1rem !important; }
    #offer-publication-form label { font-size: 13px !important; line-height: 1.35; }
    #offer-publication-form input:not([type="radio"]):not([type="checkbox"]):not([type="file"]),
    #offer-publication-form select,
    #offer-publication-form textarea { padding:.68rem .8rem !important; font-size:14px !important; }
    #offer-type, #offer-operation { max-width:22rem; }
    #offer-ccaa-sel { max-width:23rem; }
    #offer-province-sel, #offer-municipality-sel, #offer-locality-input { max-width:21rem; }
    #offer-postal-code { max-width:9rem; }
    #offer-bedrooms, #offer-bathrooms { max-width:12rem; }
    #offer-surface, #offer-estate-surface { max-width:14rem; }
    @media (max-width:767px) {
      #offer-publication-form input,
      #offer-publication-form select,
      #offer-publication-form textarea { max-width:none !important; }
    }

    /* =========================================================
       TEMA VISUAL GLOBAL: CLARO CON CONTRASTE Y MODO OSCURO
       ========================================================= */
    :root {
      color-scheme: light;
      --app-bg: #eef3f8;
      --app-surface: #ffffff;
      --app-surface-soft: #f5f8fc;
      --app-surface-muted: #e8eef5;
      --app-border: #cbd5e1;
      --app-border-soft: #dbe3ed;
      --app-text: #24364b;
      --app-text-muted: #52657a;
      --app-shadow: 0 12px 30px rgba(15, 35, 60, .10);
    }
    html[data-theme="dark"] {
      color-scheme: dark;
      --app-bg: #091321;
      --app-surface: #111f31;
      --app-surface-soft: #16263a;
      --app-surface-muted: #1b2c42;
      --app-border: #33465d;
      --app-border-soft: #26394e;
      --app-text: #e8f0fa;
      --app-text-muted: #a9b8ca;
      --app-shadow: 0 14px 34px rgba(0, 0, 0, .28);
    }
    body {
      background: var(--app-bg) !important;
      color: var(--app-text);
      transition: background-color .22s ease, color .22s ease;
    }
    body *:not(.cmplz-cookiebanner):not(.cmplz-cookiebanner *):not(.cmplz-manage-consent):not(.cmplz-manage-consent *),
    body *::before,
    body *::after {
      transition-property: background-color, border-color, color, box-shadow, opacity, transform;
      transition-duration: .18s;
      transition-timing-function: ease;
    }
    .theme-toggle-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 38px;
      height: 38px;
      border: 1px solid var(--app-border);
      border-radius: 12px;
      background: var(--app-surface-soft);
      color: var(--app-text);
      padding: 0;
      font-size: 11px;
      font-weight: 800;
      box-shadow: 0 4px 10px rgba(15, 35, 60, .06);
    }
    .theme-toggle-button:hover { transform: translateY(-1px); border-color: #1b67d6; }

    /* =========================================================
       TEMA VISUAL GLOBAL: CLARO CON CONTRASTE Y MODO OSCURO
       ========================================================= */
    :root {
      color-scheme: light;
      --app-bg: #eef3f8;
      --app-surface: #ffffff;
      --app-surface-soft: #f5f8fc;
      --app-surface-muted: #e8eef5;
      --app-border: #cbd5e1;
      --app-border-soft: #dbe3ed;
      --app-text: #24364b;
      --app-text-muted: #52657a;
      --app-shadow: 0 12px 30px rgba(15, 35, 60, .10);
    }
    html[data-theme="dark"] {
      color-scheme: dark;
      --app-bg: #091321;
      --app-surface: #111f31;
      --app-surface-soft: #16263a;
      --app-surface-muted: #1b2c42;
      --app-border: #33465d;
      --app-border-soft: #26394e;
      --app-text: #e8f0fa;
      --app-text-muted: #a9b8ca;
      --app-shadow: 0 14px 34px rgba(0, 0, 0, .28);
    }
    body {
      background: var(--app-bg) !important;
      color: var(--app-text);
      transition: background-color .22s ease, color .22s ease;
    }
    body *:not(.cmplz-cookiebanner):not(.cmplz-cookiebanner *):not(.cmplz-manage-consent):not(.cmplz-manage-consent *),
    body *::before,
    body *::after {
      transition-property: background-color, border-color, color, box-shadow, opacity, transform;
      transition-duration: .18s;
      transition-timing-function: ease;
    }
    .theme-toggle-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 38px;
      height: 38px;
      border: 1px solid var(--app-border);
      border-radius: 12px;
      background: var(--app-surface-soft);
      color: var(--app-text);
      padding: 0;
      font-size: 11px;
      font-weight: 800;
      box-shadow: 0 4px 10px rgba(15, 35, 60, .06);
    }
    .theme-toggle-button:hover { transform: translateY(-1px); border-color: #1b67d6; }
    .theme-toggle-icon { font-size: 14px; line-height: 1; }
    .brand-logo-full {
      display: block;
      height: 62px;
      width: auto;
      max-width: min(42vw, 380px);
      object-fit: contain;
    }
    @media (max-width: 640px) {
      .brand-logo-full { height: 50px; max-width: 58vw; }
    }
    .brand-logo-mark {
      display: block;
      height: 46px;
      width: 46px;
      object-fit: contain;
      border-radius: 12px;
    }
    #home-explainer-video-slot {
      aspect-ratio: 16 / 9;
      width: 100%;
      height: auto;
      overflow: hidden;
      border-radius: 1.25rem;
      background: #091321;
    }
    #home-explainer-video-slot video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transform: none;
      display: block;
    }
    .hero-title {
      text-wrap: balance;
    }
    .font-black { font-weight: 900 !important; }
    .font-extrabold { font-weight: 800 !important; }
    h1, h2, h3, h4, strong {
      text-wrap: balance;
    }
    button.bg-blue, button.bg-navy, button.bg-green,
    a.bg-blue, a.bg-navy, a.bg-green,
    button[class*="from-purple-600"], button[class*="from-blue"] {
      font-family: 'Inter', sans-serif !important;
      font-weight: 700 !important;
      letter-spacing: .012em !important;
      line-height: 1.3;
    }

    /* Etiquetas de datos inmobiliarios: negrita legible sin densidad excesiva */
    .metric-label {
      display: block;
      margin-top: .125rem;
      color: #64748b;
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      font-weight: 600;
      line-height: 1.25;
      letter-spacing: .012em;
    }
    .metric-value {
      display: block;
      color: #10233c;
      font-family: 'Inter', sans-serif;
      font-weight: 700;
      line-height: 1.22;
      letter-spacing: -.006em;
    }


    /* Carruseles compactos de la Home: 5 fichas visibles en escritorio */
    .home-carousel-shell { position: relative; }
    .home-carousel-track {
      display: flex;
      gap: 1rem;
      overflow-x: auto;
      scroll-behavior: smooth;
      scroll-snap-type: x mandatory;
      padding: .2rem .15rem .65rem;
    }
    .home-carousel-card {
      flex: 0 0 100%;
      min-width: 0;
      scroll-snap-align: start;
    }
    .home-carousel-nav {
      position: absolute;
      top: 50%;
      z-index: 10;
      display: inline-flex;
      width: 2.5rem;
      height: 2.5rem;
      align-items: center;
      justify-content: center;
      transform: translateY(-50%);
      border: 1px solid var(--app-border);
      border-radius: 999px;
      background: var(--app-surface);
      color: var(--app-text);
      box-shadow: 0 8px 18px rgba(15, 35, 60, .16);
      font-size: 1rem;
      font-weight: 800;
    }
    .home-carousel-nav:hover { border-color: #1b67d6; color: #1b67d6; }
    .home-carousel-nav-prev { left: -.7rem; }
    .home-carousel-nav-next { right: -.7rem; }
    @media (min-width: 640px) {
      .home-carousel-card { flex-basis: calc((100% - 1rem) / 2); }
    }
    @media (min-width: 1024px) {
      .home-carousel-card { flex-basis: calc((100% - 4rem) / 5); }
    }
    html[data-theme="dark"] .metric-label { color: #a9b8ca; }
    html[data-theme="dark"] .metric-value { color: #edf5ff; }

    /* Modo claro con separación visual más definida */
    html[data-theme="light"] body { background: #eef3f8 !important; }
    html[data-theme="light"] .bg-white { background-color: #ffffff !important; }
    html[data-theme="light"] .bg-slate-50 { background-color: #f4f7fb !important; }
    html[data-theme="light"] .bg-slate-100 { background-color: #e8eef5 !important; }
    html[data-theme="light"] .border-slate-100 { border-color: #dbe3ed !important; }
    html[data-theme="light"] .border-slate-200 { border-color: #cbd5e1 !important; }
    html[data-theme="light"] .border-slate-300 { border-color: #b7c4d4 !important; }
    html[data-theme="light"] .shadow-sm,
    html[data-theme="light"] .shadow-md,
    html[data-theme="light"] .shadow-lg,
    html[data-theme="light"] .shadow-xl { box-shadow: var(--app-shadow) !important; }
    html[data-theme="light"] .text-slate-700 { color: #34485d !important; }
    html[data-theme="light"] .text-slate-600 { color: #415569 !important; }
    html[data-theme="light"] .text-slate-500 { color: #4c6074 !important; }
    html[data-theme="light"] .text-slate-400 { color: #607489 !important; }
    html[data-theme="light"] .text-navy { color: #10233c !important; }
    html[data-theme="light"] input,
    html[data-theme="light"] select,
    html[data-theme="light"] textarea { background-color: #ffffff; border-color: #b9c7d8 !important; color: #24364b; }

    /* Modo oscuro coherente para superficies, formularios y mapas */
    html[data-theme="dark"] body { background: #091321 !important; color: #e8f0fa; }
    html[data-theme="dark"] header { background: rgba(13, 28, 48, .96) !important; border-color: #33465d !important; }
    html[data-theme="dark"] .bg-white { background-color: #111f31 !important; }
    html[data-theme="dark"] .bg-white\/95 { background-color: rgba(17, 31, 49, .95) !important; }
    html[data-theme="dark"] .bg-white\/90 { background-color: rgba(17, 31, 49, .90) !important; }
    html[data-theme="dark"] .bg-white\/80 { background-color: rgba(17, 31, 49, .80) !important; }
    html[data-theme="dark"] .bg-slate-50 { background-color: #16263a !important; }
    html[data-theme="dark"] .bg-slate-50\/50 { background-color: rgba(22, 38, 58, .72) !important; }
    html[data-theme="dark"] .bg-slate-50\/70 { background-color: rgba(22, 38, 58, .86) !important; }
    html[data-theme="dark"] .bg-slate-100 { background-color: #1b2c42 !important; }
    html[data-theme="dark"] .bg-slate-200 { background-color: #33465d !important; }
    html[data-theme="dark"] .bg-blue-light { background-color: #17375e !important; }
    html[data-theme="dark"] .bg-green-light { background-color: #123d34 !important; }
    html[data-theme="dark"] .bg-amber-light { background-color: #4a3513 !important; }
    html[data-theme="dark"] .text-navy { color: #edf5ff !important; }
    html[data-theme="dark"] .text-slate-800 { color: #e8f0fa !important; }
    html[data-theme="dark"] .text-slate-700 { color: #e2e8f0 !important; }
    html[data-theme="dark"] .text-slate-600 { color: #cbd5e1 !important; }
    html[data-theme="dark"] .text-slate-500 { color: #94a3b8 !important; }
    html[data-theme="dark"] .text-slate-400 { color: #a7b6c8 !important; }
    html[data-theme="dark"] .text-blue { color: #79b7ff !important; }
    html[data-theme="dark"] .text-green { color: #5ad8ad !important; }
    html[data-theme="dark"] .text-amber { color: #f6c668 !important; }
    html[data-theme="dark"] .text-red-600 { color: #f87171 !important; }
    html[data-theme="dark"] .text-red-700 { color: #fca5a5 !important; }
    html[data-theme="dark"] .text-amber-700 { color: #fcd34d !important; }
    html[data-theme="dark"] .text-green-700 { color: #6ee7b7 !important; }
    html[data-theme="dark"] .text-emerald-900 { color: #34d399 !important; }
    html[data-theme="dark"] .bg-red-50 { background-color: rgba(220, 38, 38, 0.15) !important; }
    html[data-theme="dark"] .bg-red-100 { background-color: rgba(220, 38, 38, 0.25) !important; }
    html[data-theme="dark"] .bg-emerald-50 { background-color: rgba(16, 185, 129, 0.15) !important; }
    html[data-theme="dark"] .bg-amber-50 { background-color: rgba(245, 158, 11, 0.15) !important; }
    html[data-theme="dark"] .border-slate-100 { border-color: #26394e !important; }
    html[data-theme="dark"] .border-slate-200 { border-color: #33465d !important; }
    html[data-theme="dark"] .border-slate-300 { border-color: #496079 !important; }
    html[data-theme="dark"] .shadow-sm,
    html[data-theme="dark"] .shadow-md,
    html[data-theme="dark"] .shadow-lg,
    html[data-theme="dark"] .shadow-xl,
    html[data-theme="dark"] .shadow-2xl { box-shadow: var(--app-shadow) !important; }
    html[data-theme="dark"] input,
    html[data-theme="dark"] select,
    html[data-theme="dark"] textarea {
      background-color: #0d1c30 !important;
      border-color: #41566e !important;
      color: #edf5ff !important;
    }
    html[data-theme="dark"] input::placeholder,
    html[data-theme="dark"] textarea::placeholder { color: #8191a6 !important; }
    html[data-theme="dark"] option { background-color: #0d1c30; color: #edf5ff; }
    html[data-theme="dark"] .leaflet-container { background: #16263a; }
    html[data-theme="dark"] .leaflet-tile { filter: brightness(.72) contrast(1.12) saturate(.78); }
    html[data-theme="dark"] .leaflet-popup-content-wrapper,
    html[data-theme="dark"] .leaflet-popup-tip,
    html[data-theme="dark"] .leaflet-control-zoom a { background: #111f31; color: #edf5ff; border-color: #33465d; }
    html[data-theme="dark"] #page-inicio > section:first-child {
      background: linear-gradient(135deg, #10233c 0%, #0d1c30 54%, #091321 100%) !important;
    }


    /* =========================================================
       MODO OSCURO HOMOGÉNEO: FONDO IA MODERNO Y CONSISTENTE
       ========================================================= */
    html[data-theme="dark"] body {
      background:
        radial-gradient(circle at 12% 8%, rgba(27, 103, 214, .20), transparent 34%),
        radial-gradient(circle at 88% 14%, rgba(124, 58, 237, .14), transparent 28%),
        radial-gradient(circle at 52% 94%, rgba(21, 147, 106, .10), transparent 34%),
        #07111f !important;
      background-attachment: fixed !important;
      color: #e8f0fa;
    }
    html[data-theme="dark"] .page-section,
    html[data-theme="dark"] .page-section > section,
    html[data-theme="dark"] #page-inicio > section:first-child {
      background: transparent !important;
    }
    html[data-theme="dark"] header {
      background: rgba(7, 17, 31, .88) !important;
      border-color: rgba(100, 139, 190, .28) !important;
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }
    html[data-theme="dark"] footer {
      background: rgba(7, 17, 31, .82) !important;
      border-color: rgba(100, 139, 190, .24) !important;
    }
    html[data-theme="dark"] .bg-white,
    html[data-theme="dark"] .legal-card,
    html[data-theme="dark"] .ai-connection-card {
      background: rgba(15, 29, 48, .88) !important;
      border-color: rgba(100, 139, 190, .28) !important;
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
    }
    html[data-theme="dark"] .bg-slate-50,
    html[data-theme="dark"] .bg-slate-50\/50,
    html[data-theme="dark"] .bg-slate-50\/70,
    html[data-theme="dark"] .bg-slate-100 {
      background-color: rgba(18, 35, 57, .78) !important;
    }
    html[data-theme="dark"] .ai-provider-chip {
      background: rgba(24, 48, 78, .82);
      border-color: rgba(121, 183, 255, .32);
    }
    .ai-provider-chip {
      border: 1px solid var(--app-border);
      border-radius: 1rem;
      background: var(--app-surface-soft);
      padding: .9rem;
    }
    .ai-connection-card {
      border: 1px solid var(--app-border);
      border-radius: 1rem;
      background: var(--app-surface);
      padding: 1rem;
    }
    .ai-manual-section { border:1px solid rgba(27,103,214,.22); background:linear-gradient(145deg,rgba(239,246,255,.92),rgba(255,255,255,.82)); }
    .ai-manual-kicker { background:#ffffff; color:#1b67d6; border:1px solid rgba(27,103,214,.18); }
    .ai-manual-step,
    .ai-manual-card,
    .ai-manual-note { border:1px solid #d8e4f1; background:#ffffff; color:#10233c; box-shadow:0 10px 24px rgba(15,35,60,.06); }
    .ai-manual-card.is-highlight { border-color:rgba(15,159,110,.28); background:#effcf6; }
    .ai-manual-card h4,
    .ai-manual-step strong,
    .ai-manual-note strong { color:#10233c; }
    .ai-manual-card p,
    .ai-manual-step span,
    .ai-manual-note { color:#43586f; }
    .ai-manual-badge { background:#e8f2ff; color:#1454aa; border:1px solid rgba(27,103,214,.18); }
    .ai-manual-badge.green { background:#e7f8f1; color:#087456; border-color:rgba(15,159,110,.22); }
    .ai-manual-link { color:#1554b3; font-weight:800; }
    html[data-theme="dark"] .ai-manual-section { background:linear-gradient(145deg,rgba(13,28,48,.96),rgba(9,19,33,.92)); border-color:rgba(121,183,255,.30); box-shadow:0 18px 50px rgba(0,0,0,.28); }
    html[data-theme="dark"] .ai-manual-kicker { background:rgba(121,183,255,.14); color:#d9ecff; border-color:rgba(121,183,255,.28); }
    html[data-theme="dark"] .ai-manual-step,
    html[data-theme="dark"] .ai-manual-card,
    html[data-theme="dark"] .ai-manual-note { background:rgba(12,26,44,.96); border-color:rgba(121,183,255,.24); color:#edf5ff; box-shadow:0 16px 36px rgba(0,0,0,.26); }
    html[data-theme="dark"] .ai-manual-card.is-highlight { background:linear-gradient(145deg,rgba(10,50,43,.96),rgba(12,35,45,.96)); border-color:rgba(90,216,173,.34); }
    html[data-theme="dark"] .ai-manual-card h4,
    html[data-theme="dark"] .ai-manual-step strong,
    html[data-theme="dark"] .ai-manual-note strong { color:#f3f8ff !important; }
    html[data-theme="dark"] .ai-manual-card p,
    html[data-theme="dark"] .ai-manual-step span,
    html[data-theme="dark"] .ai-manual-note { color:#c9d7e8 !important; }
    html[data-theme="dark"] .ai-manual-badge { background:rgba(121,183,255,.15); color:#d9ecff; border-color:rgba(121,183,255,.28); }
    html[data-theme="dark"] .ai-manual-badge.green { background:rgba(90,216,173,.15); color:#bdf4df; border-color:rgba(90,216,173,.30); }
    html[data-theme="dark"] .ai-manual-link { color:#9fd0ff !important; }


    /* =========================================================
       CAPA LEGAL Y DE CUMPLIMIENTO: RGPD / LOPDGDD / LSSI / DSA
       ========================================================= */
    .legal-placeholder {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      border: 1px solid #f59e0b;
      border-radius: .55rem;
      background: #fff7d6;
      color: #8a4b00;
      padding: .2rem .45rem;
      font-size: .72rem;
      font-weight: 800;
      line-height: 1.25;
    }
    .legal-card {
      border: 1px solid var(--app-border);
      border-radius: 1rem;
      background: var(--app-surface);
      padding: 1.15rem;
      box-shadow: 0 8px 18px rgba(15,35,60,.06);
    }
    .legal-card h3 { color: var(--app-text); font-weight: 800; }
    .legal-card p, .legal-card li { color: var(--app-text-muted); }
    .legal-card ul { padding-left: 1.1rem; list-style: disc; }
    .legal-link { color: #1b67d6; font-weight: 700; }
    .legal-link:hover { text-decoration: underline; }
    .legal-footer {
      border-top: 1px solid var(--app-border);
      background: var(--app-surface);
      color: var(--app-text-muted);
    }
    .legal-footer a, .legal-footer button { color: var(--app-text-muted); font-weight: 700; text-align: left; }
    .legal-footer a:hover, .legal-footer button:hover { color: #1b67d6; }
    /* Footer estable: no depende de clases arbitrarias de Tailwind. */
    .legal-footer { position:relative; padding-bottom:clamp(4.5rem, 8vw, 6rem); }
    .legal-footer > div { width:min(1600px, calc(100% - 2rem)); margin:0 auto; padding:2rem 0 !important; }
    .legal-footer > div > .legal-footer-grid { display:grid !important; grid-template-columns:minmax(0,1.35fr) repeat(3,minmax(0,1fr)); align-items:start; gap:clamp(1.25rem, 3vw, 3rem); }
    .legal-footer-grid > .legal-footer-brand,
    .legal-footer-grid > .legal-footer-column { min-width:0; }
    .legal-footer-column { display:grid; align-content:start; gap:.35rem; }
    .legal-footer-links-title { display:block; margin:0 0 .55rem; color:var(--app-text); font-size:.7rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
    .legal-footer-links a, .legal-footer-links button { display:block; width:100%; min-height:1.65rem; padding:.18rem 0; border-radius:.5rem; background:transparent; transition:background .18s ease, color .18s ease; }
    .legal-footer-column .legal-footer-consent { width:auto; text-align:left; }
    .legal-footer-links a:hover, .legal-footer-links button:hover, .legal-footer-links a:focus-visible, .legal-footer-links button:focus-visible { background:var(--app-surface-soft); outline:none; }
    .legal-footer-links-title { color:var(--app-text); font-size:.68rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; margin:.15rem 0 .05rem; }
    .legal-footer-link-group { display:grid; gap:.08rem; width:100%; }
    .legal-footer-consent { border:1px solid #d18b16 !important; color:#9a5a00 !important; background:#fff5d6 !important; }
    .legal-footer-consent:hover, .legal-footer-consent:focus-visible { color:#6d3d00 !important; background:#ffe9a8 !important; }
    /* La imagen de sesión debe tocar los bordes del bloque, sin bandas verticales. */
    .auth-session-visual { margin-top:0 !important; margin-bottom:0 !important; }
    .auth-session-visual img { display:block !important; width:100% !important; height:auto !important; max-height:none !important; margin:0 !important; object-fit:cover; }
    html[data-theme="dark"] .legal-footer-consent { color:#ffdd88 !important; background:rgba(209,139,22,.18) !important; border-color:#d9a538 !important; }
    html[data-theme="dark"] .legal-footer-consent:hover, html[data-theme="dark"] .legal-footer-consent:focus-visible { color:#fff1bd !important; background:rgba(209,139,22,.3) !important; }
    html[data-theme="dark"] .legal-placeholder {
      border-color: #b8730f;
      background: rgba(217,139,19,.15);
      color: #ffd58a;
    }
    .legal-consent-box {
      border: 1px solid var(--app-border-soft);
      border-radius: .9rem;
      background: var(--app-surface-soft);
      padding: .8rem .9rem;
    }
    .captacion-cookie-notice { position:fixed; left:50%; bottom:1rem; transform:translateX(-50%); z-index:90; width:min(960px,calc(100% - 2rem)); border:1px solid rgba(86,140,205,.55); border-radius:1rem; background:#f8fbff; color:#14243b; box-shadow:0 18px 45px rgba(0,0,0,.28); }
    .captacion-cookie-notice.is-hidden { display:none; }
    .captacion-cookie-notice-inner { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:center; padding:1rem 1.15rem; }
    .captacion-cookie-notice-copy h2 { margin:0; font-size:1rem; font-weight:900; color:#10233c; }
    .captacion-cookie-notice-copy p { margin:.25rem 0 0; font-size:.75rem; line-height:1.4; color:#40536b; }
    .captacion-cookie-notice-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:.45rem; }
    .captacion-cookie-notice-actions button { min-height:2.35rem; padding:.55rem .85rem; border-radius:.65rem; border:1px solid #b8c7d9; background:#fff; color:#18314f; font-size:.75rem; font-weight:800; cursor:pointer; }
    .captacion-cookie-notice-actions .is-primary { border-color:#d18b16; background:#f3b52e; color:#17263b; }
    .captacion-cookie-notice-actions .is-preferences { border-color:#2d72ce; color:#1b67d6; }
    .captacion-cookie-notice-close { position:absolute; top:.35rem; right:.45rem; border:0; background:transparent; color:#54708d; font-size:1.25rem; line-height:1; cursor:pointer; padding:.2rem .35rem; }
    html[data-theme="dark"] .captacion-cookie-notice { background:#10233c; color:#ecf5ff; border-color:rgba(117,178,255,.55); }
    html[data-theme="dark"] .captacion-cookie-notice-copy h2 { color:#fff; }
    html[data-theme="dark"] .captacion-cookie-notice-copy p { color:#c8d9ec; }
    html[data-theme="dark"] .captacion-cookie-notice-actions button { background:#0b1a2d; color:#edf5ff; border-color:#4f6681; }
    html[data-theme="dark"] .captacion-cookie-notice-actions .is-primary { background:#f3b52e; color:#17263b; border-color:#f3b52e; }
    @media (max-width: 980px) { .legal-footer > div > .legal-footer-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 640px) { .legal-footer { padding-bottom:4.5rem; } .legal-footer > div > .legal-footer-grid { grid-template-columns:1fr; gap:1.15rem; } }
    @media (max-width: 700px) { .captacion-cookie-notice-inner { grid-template-columns:1fr; padding:.9rem 1rem; } .captacion-cookie-notice-actions { justify-content:stretch; } .captacion-cookie-notice-actions button { flex:1 1 30%; } }


    /* =========================================================
       DASHBOARD PRIVADO DEL AGENTE: CAPTACIÓN + DEMANDA
       ========================================================= */
    .private-dashboard-shell { display:grid; gap:1.25rem; }
    @media (min-width: 1024px) { .private-dashboard-shell { grid-template-columns: 248px minmax(0, 1fr); } }
    .private-dashboard-sidebar {
      border:1px solid var(--app-border); border-radius:1.25rem; background:var(--app-surface);
      box-shadow:var(--app-shadow); padding:.75rem; height:max-content; position:sticky; top:6rem;
    }
    #page-area-privada { font-size:16px; }
    .private-dashboard-nav { width:100%; display:flex; align-items:center; gap:.7rem; padding:.72rem .78rem; border-radius:.85rem; color:var(--app-text-muted); font-size:.875rem; font-weight:700; text-align:left; }
    .private-dashboard-nav:hover { background:var(--app-surface-soft); color:#1b67d6; }
    .private-dashboard-nav.active { background:#10233c; color:#fff; box-shadow:0 7px 16px rgba(16,35,60,.18); }
    html[data-theme="dark"] .private-dashboard-nav.active { background:linear-gradient(135deg,#1b67d6,#5b3fd1); }
    .private-dashboard-panel { display:none; }
    .private-dashboard-panel.active { display:block; animation:fadeIn .22s ease both; }
    .private-kpi-card { border:1px solid var(--app-border); border-radius:1rem; background:var(--app-surface); padding:1rem; box-shadow:0 8px 20px rgba(15,35,60,.06); }
    .private-kpi-card button { text-align:left; width:100%; }
    .private-section-card { border:1px solid var(--app-border); border-radius:1.15rem; background:var(--app-surface); box-shadow:0 10px 24px rgba(15,35,60,.06); }
    .private-priority-high { border-left:4px solid #dc2626; }
    .private-priority-medium { border-left:4px solid #b8730f; }
    .private-priority-low { border-left:4px solid #1b67d6; }
    .private-status-pill { display:inline-flex; align-items:center; border-radius:999px; padding:.28rem .55rem; font-size:.62rem; font-weight:800; line-height:1; }
    .private-table th { color:#64748b; font-size:.64rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; white-space:nowrap; }
    .private-table td { color:var(--app-text-muted); font-size:.73rem; vertical-align:top; }
    .private-table tr:hover td { background:var(--app-surface-soft); }
    .private-dashboard-mobile-select { border:1px solid var(--app-border); border-radius:.85rem; background:var(--app-surface); color:var(--app-text); padding:.78rem .9rem; width:100%; font-size:.8rem; font-weight:700; }
    .private-mini-card { border:1px solid var(--app-border); border-radius:1rem; background:var(--app-surface-soft); padding:.85rem; }
    .private-progress-track { height:.45rem; overflow:hidden; border-radius:999px; background:var(--app-surface-muted); }
    .private-progress-bar { height:100%; border-radius:999px; background:linear-gradient(90deg,#1b67d6,#15936a); }

    /* Resumen ejecutivo y panel privado adaptativo, moderno y unificado */
    #page-area-privada { min-height:100vh; }
    html[data-theme="light"] #page-area-privada { background:#f8fafc; color:#0f172a; }
    html[data-theme="dark"] #page-area-privada { background:#060d17; color:#e2e8f0; }
    #page-area-privada > section { max-width:none; width:100%; padding-top:.5rem; }
    #page-area-privada .private-area-legacy-header { display:none; }
    #page-area-privada .private-dashboard-shell { gap:0; grid-template-columns:240px minmax(0,1fr); overflow:hidden; border:1px solid #e2e8f0; border-radius:1.4rem; background:#ffffff; box-shadow:0 1px 3px rgba(0,0,0,0.03), 0 12px 36px -4px rgba(0,0,0,0.04); }
    html[data-theme="dark"] #page-area-privada .private-dashboard-shell { border-color:rgba(255,255,255,.08); background:#0b192c; box-shadow:0 24px 60px rgba(0,0,0,.45); }
    #page-area-privada .private-dashboard-sidebar { height:100%; min-height:920px; top:0; border:0; border-right:1px solid #e2e8f0; border-radius:0; padding:1.25rem .85rem; background:#ffffff; box-shadow:none; }
    html[data-theme="dark"] #page-area-privada .private-dashboard-sidebar { border-color:rgba(255,255,255,.08); background:#071220; }
    #page-area-privada .private-dashboard-nav { color:#475569; padding:.72rem .85rem; border-radius:.65rem; font-size:.875rem; font-weight:600; }
    html[data-theme="dark"] #page-area-privada .private-dashboard-nav { color:#94a3b8; }
    #page-area-privada .private-dashboard-nav:hover { background:rgba(0,82,236,.06); color:#0052ec; }
    html[data-theme="dark"] #page-area-privada .private-dashboard-nav:hover { background:rgba(56,189,248,.1); color:#38bdf8; }
    #page-area-privada .private-dashboard-nav.active { background:linear-gradient(135deg,#0052ec,#0a44b8); color:#fff !important; box-shadow:0 6px 16px rgba(0,82,236,.25); }
    #page-area-privada .private-dashboard-sidebar nav > div { border-color:#e2e8f0; }
    html[data-theme="dark"] #page-area-privada .private-dashboard-sidebar nav > div { border-color:rgba(255,255,255,.08); }
    /* ESTILOS EXECUTIVE FINTECH SAAS DASHBOARD (INSPIRADO EN ONPOINT STUDIO DRIBBLE) */
    .exec-sidebar-brand { display:flex; align-items:center; gap:.75rem; padding:.75rem .75rem 1.25rem; }
    .exec-brand-mark { position:relative; width:36px; height:36px; flex:0 0 36px; border-radius:10px; background:transparent url(/assets/media/favicon-compra-captacion.png) center center / contain no-repeat; display:block; filter:drop-shadow(0 2px 8px rgba(0,82,236,0.25)); }
    .exec-sidebar-profile { margin-top:1rem; padding:.85rem .75rem .4rem; border-top:1px solid rgba(226,232,240,.6); border-radius:14px; background:rgba(241,245,249,.5); border-bottom:0 !important; }
    html[data-theme="dark"] .exec-sidebar-profile { border-top-color:rgba(255,255,255,.08); background:rgba(15,23,42,.6); }
    #page-area-privada #private-dashboard-agent-name { color:#0f172a; font-weight:800; }
    html[data-theme="dark"] #page-area-privada #private-dashboard-agent-name { color:#f8fafc; }
    #page-area-privada #private-dashboard-agent-agency { color:#64748b; }
    
    .private-dashboard-panel { display:none !important; min-height:920px; padding:1.75rem; background:#f8fafc; color:#0f172a; border-radius:24px; }
    html[data-theme="dark"] .private-dashboard-panel { background:#070e17; color:#f8fafc; }
    .private-dashboard-panel.active { display:block !important; animation:fadeIn .22s ease both; }
    .exec-dashboard { min-height:920px; padding:0; background:transparent; color:inherit; }
    html[data-theme="dark"] .exec-dashboard { background:#070e17; color:#f8fafc; }
    
    /* Dynamic Neon Range Sliders for 50/50 Fee Calculator (Light & Dark Mode) */
    .calc-range-slider {
      -webkit-appearance: none;
      appearance: none;
      width: 100%;
      height: 12px;
      border-radius: 9999px;
      outline: none;
      background: #e2e8f0;
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.12), 0 0 10px rgba(0, 82, 236, 0.18);
      transition: box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s ease;
      cursor: pointer;
    }
    html[data-theme="dark"] .calc-range-slider {
      background: #0f172a;
      box-shadow: inset 0 1px 4px rgba(0,0,0,0.6), 0 0 14px rgba(56, 189, 248, 0.28);
    }
    .calc-range-slider:focus, .calc-range-slider:hover {
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.15), 0 0 16px rgba(0, 229, 255, 0.45);
    }
    html[data-theme="dark"] .calc-range-slider:focus, html[data-theme="dark"] .calc-range-slider:hover {
      box-shadow: inset 0 1px 4px rgba(0,0,0,0.7), 0 0 20px rgba(0, 229, 255, 0.65);
    }
    .calc-range-slider::-webkit-slider-thumb {
      -webkit-appearance: none;
      appearance: none;
      width: 26px;
      height: 26px;
      border-radius: 50%;
      cursor: pointer;
      box-shadow: 0 0 14px rgba(0, 229, 255, 0.75), 0 2px 8px rgba(0,0,0,0.25);
      transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
    }
    .calc-range-slider::-webkit-slider-thumb:hover, .calc-range-slider::-webkit-slider-thumb:active {
      transform: scale(1.22);
      box-shadow: 0 0 22px rgba(0, 229, 255, 1), 0 4px 12px rgba(0,0,0,0.35);
    }
    .calc-range-slider::-moz-range-thumb {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      cursor: pointer;
      box-shadow: 0 0 14px rgba(0, 229, 255, 0.75), 0 2px 8px rgba(0,0,0,0.25);
      transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
    }
    .calc-range-slider::-moz-range-thumb:hover, .calc-range-slider::-moz-range-thumb:active {
      transform: scale(1.22);
      box-shadow: 0 0 22px rgba(0, 229, 255, 1), 0 4px 12px rgba(0,0,0,0.35);
    }
    #calc-price-slider::-webkit-slider-thumb {
      background: radial-gradient(circle, #34d399 20%, #059669 100%);
      border: 3px solid #ffffff;
      box-shadow: 0 0 14px rgba(16, 185, 129, 0.8), 0 2px 6px rgba(0,0,0,0.2);
    }
    #calc-price-slider::-moz-range-thumb {
      background: radial-gradient(circle, #34d399 20%, #059669 100%);
      border: 3px solid #ffffff;
      box-shadow: 0 0 14px rgba(16, 185, 129, 0.8), 0 2px 6px rgba(0,0,0,0.2);
    }
    #calc-commission-slider::-webkit-slider-thumb {
      background: radial-gradient(circle, #38bdf8 20%, #0052ec 100%);
      border: 3px solid #ffffff;
      box-shadow: 0 0 16px rgba(0, 180, 255, 0.9), 0 2px 6px rgba(0,0,0,0.2);
    }
    #calc-commission-slider::-moz-range-thumb {
      background: radial-gradient(circle, #38bdf8 20%, #0052ec 100%);
      border: 3px solid #ffffff;
      box-shadow: 0 0 16px rgba(0, 180, 255, 0.9), 0 2px 6px rgba(0,0,0,0.2);
    }
    .map-geo-path {
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .map-geo-path:hover {
      filter: brightness(1.2) drop-shadow(0 0 6px rgba(0, 229, 255, 0.7));
      stroke: #ffffff !important;
      stroke-width: 2 !important;
    }
    .map-geo-path.active-region {
      filter: brightness(1.25) drop-shadow(0 0 10px rgba(0, 229, 255, 0.95));
      stroke: #ffffff !important;
      stroke-width: 2.2 !important;
    }

    /* Top Loader & Animated transition for mobile/tablet */
    #captacion-page-loader { position:fixed; top:0; left:0; width:0; height:3.5px; background:linear-gradient(90deg,#0052ec,#38bdf8,#10b981); z-index:999999; pointer-events:none; transition:width .28s cubic-bezier(0.4,0,0.2,1),opacity .22s ease; box-shadow:0 0 12px rgba(0,82,236,0.65); opacity:0; }
    #captacion-page-loader.loading { width:78%; opacity:1; }
    #captacion-page-loader.done { width:100%; opacity:0; }
    
    .exec-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1.25rem; margin-bottom:1.5rem; }
    .exec-head h3 { margin:0; color:#0f172a; font-size:1.85rem; line-height:1.15; font-weight:900; letter-spacing:-.035em; }
    html[data-theme="dark"] .exec-head h3 { color:#ffffff; }
    .exec-head p { margin:.35rem 0 0; color:#64748b; font-size:.9rem; }
    html[data-theme="dark"] .exec-head p { color:#94a3b8; }
    .exec-head-actions { display:flex; gap:.75rem; }
    .exec-control { display:inline-flex; align-items:center; justify-content:center; gap:.5rem; min-height:40px; padding:.5rem 1rem; border:1px solid #e2e8f0; border-radius:12px; background:#ffffff; color:#334155; font-size:.85rem; font-weight:700; box-shadow:0 1px 2px rgba(0,0,0,0.04); transition:all .2s ease; cursor:pointer; }
    .exec-control:hover { border-color:#0052ec; color:#0052ec; transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,82,236,0.12); }
    html[data-theme="dark"] .exec-control { border-color:rgba(255,255,255,.1); background:#0f172a; color:#cbd5e1; }
    html[data-theme="dark"] .exec-control:hover { border-color:#38bdf8; color:#38bdf8; }
    
    .exec-kpis { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:1rem; margin-bottom:1.25rem; }
    .exec-card { border:1px solid #e2e8f0; border-radius:20px; background:#ffffff; box-shadow:0 1px 3px rgba(0,0,0,0.03), 0 8px 24px -4px rgba(0,0,0,0.04); transition:all .25s ease; }
    html[data-theme="dark"] .exec-card { border-color:rgba(255,255,255,.08); background:#0b192c; box-shadow:0 8px 24px -4px rgba(0,0,0,0.4); }
    .exec-kpi { position:relative; width:100%; min-height:165px; padding:1.25rem; overflow:hidden; text-align:left; cursor:pointer; }
    .exec-kpi:hover,.exec-kpi:focus-visible,.exec-clickable:hover,.exec-clickable:focus-visible { transform:translateY(-3px); border-color:#0052ec; box-shadow:0 14px 30px -4px rgba(0,82,236,0.15); outline:0; }
    html[data-theme="dark"] .exec-kpi:hover { border-color:#38bdf8; box-shadow:0 14px 30px -4px rgba(56,189,248,0.2); }
    
    .exec-kpi-blue { --glow:#0052ec; }
    .exec-kpi-green { --glow:#10b981; }
    .exec-kpi-yellow { --glow:#f59e0b; }
    .exec-kpi-violet { --glow:#8b5cf6; }
    .exec-kpi-top { display:flex; align-items:center; gap:.75rem; }
    .exec-icon { display:grid; place-items:center; width:38px; height:38px; flex:0 0 38px; border-radius:12px; color:#fff; font-size:1.1rem; font-weight:900; background:var(--glow); box-shadow:0 4px 12px rgba(0,0,0,0.15); }
    .exec-kpi-label { color:#64748b; font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.02em; }
    html[data-theme="dark"] .exec-kpi-label { color:#94a3b8; }
    .exec-kpi strong { display:block; margin-top:.35rem; color:#0f172a; font-size:2.1rem; line-height:1; letter-spacing:-.035em; font-weight:900; }
    html[data-theme="dark"] .exec-kpi strong { color:#ffffff; }
    .exec-kpi-value { margin-top:.6rem; color:#64748b; font-size:.82rem; font-weight:600; }
    html[data-theme="dark"] .exec-kpi-value { color:#94a3b8; }
    .exec-trend { display:inline-flex; align-items:center; gap:.35rem; margin-top:.6rem; padding:.2rem .5rem; border-radius:8px; background:rgba(16,185,129,0.1); color:#10b981; font-size:.75rem; font-weight:800; }
    .exec-trend.neutral { background:rgba(100,116,139,0.1); color:#64748b; }
    .exec-card-cta { display:inline-flex; align-items:center; gap:.35rem; margin-top:.75rem; color:#0052ec; font-size:.8rem; font-weight:800; }
    html[data-theme="dark"] .exec-card-cta { color:#38bdf8; }
    
    .exec-pipeline { min-height:165px; padding:1.25rem; }
    .exec-pipeline-label { color:#64748b; font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.02em; }
    html[data-theme="dark"] .exec-pipeline-label { color:#94a3b8; }
    .exec-pipeline strong { display:block; color:#0f172a; font-size:1.65rem; font-weight:900; margin:.35rem 0 .2rem; letter-spacing:-.035em; }
    html[data-theme="dark"] .exec-pipeline strong { color:#ffffff; }
    .exec-sparkline { width:100%; height:52px; overflow:visible; }
    .exec-sparkline .area { fill:url(#execSparkGradient); }
    .exec-sparkline .line { fill:none; stroke:#0052ec; stroke-width:2.5; stroke-linecap:round; }
    html[data-theme="dark"] .exec-sparkline .line { stroke:#38bdf8; }
    .exec-months { display:flex; justify-content:space-between; color:#94a3b8; font-size:.65rem; font-weight:700; margin-top:.25rem; }
    
    .exec-central { display:grid; grid-template-columns:1fr 1.25fr; gap:1.25rem; margin-bottom:1.25rem; }
    .exec-panel { padding:1.5rem; }
    .exec-panel-title { margin:0 0 1.25rem; color:#0f172a; font-size:.95rem; font-weight:900; text-transform:uppercase; letter-spacing:.03em; }
    html[data-theme="dark"] .exec-panel-title { color:#ffffff; }
    .exec-distribution { display:grid; grid-template-columns:minmax(180px,.9fr) 1fr; align-items:center; gap:1.5rem; }
    .exec-donut { position:relative; width:min(200px,100%); aspect-ratio:1; margin:auto; border-radius:50%; }
    .exec-donut-svg { width:100%; height:100%; transform:rotate(-90deg); overflow:visible; }
    .exec-donut-segment { fill:none; stroke-width:22; cursor:pointer; transition:all .2s ease; }
    .exec-donut-segment:hover { stroke-width:26; filter:drop-shadow(0 0 8px currentColor); opacity:.95; }
    .exec-donut-hole { position:absolute; inset:25%; border-radius:50%; background:#ffffff; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
    html[data-theme="dark"] .exec-donut-hole { background:#0b192c; box-shadow:0 2px 8px rgba(0,0,0,0.5); }
    .exec-donut-center { position:absolute; inset:0; z-index:1; display:grid; place-content:center; text-align:center; pointer-events:none; }
    .exec-donut-center strong { color:#0f172a; font-size:2rem; line-height:1; font-weight:900; }
    html[data-theme="dark"] .exec-donut-center strong { color:#ffffff; }
    .exec-donut-center span { margin-top:.25rem; color:#64748b; font-size:.68rem; font-weight:700; }
    
    .exec-legend { display:grid; gap:.75rem; }
    .exec-legend-row { display:grid; width:100%; grid-template-columns:auto 1fr auto; align-items:center; gap:.75rem; padding:.6rem .75rem; border-radius:12px; color:#334155; font-size:.85rem; font-weight:600; text-align:left; cursor:pointer; background:rgba(241,245,249,0.5); transition:all .2s; }
    html[data-theme="dark"] .exec-legend-row { color:#cbd5e1; background:rgba(15,23,42,0.5); }
    .exec-legend-row:hover { background:rgba(0,82,236,0.08); color:#0052ec; }
    .exec-dot { width:10px; height:10px; border-radius:50%; }
    .exec-legend-row b { color:#0f172a; font-size:.85rem; font-weight:800; }
    html[data-theme="dark"] .exec-legend-row b { color:#ffffff; }
    
    .exec-funnel-grid { display:grid; grid-template-columns:minmax(180px,.8fr) 1.2fr; align-items:center; gap:1.5rem; }
    .exec-funnel { display:flex; flex-direction:column; align-items:center; gap:5px; }
    .exec-funnel-step { height:44px; border:0; cursor:pointer; clip-path:polygon(0 0,100% 0,88% 100%,12% 100%); transition:all .2s; }
    .exec-funnel-step:hover { filter:brightness(1.1); transform:scale(1.02); }
    .exec-funnel-step:nth-child(1){width:100%;background:linear-gradient(90deg,#0052ec,#3b82f6)}
    .exec-funnel-step:nth-child(2){width:76%;background:linear-gradient(90deg,#10b981,#34d399)}
    .exec-funnel-step:nth-child(3){width:54%;background:linear-gradient(90deg,#f59e0b,#fbbf24)}
    .exec-funnel-step:nth-child(4){width:34%;background:linear-gradient(90deg,#8b5cf6,#a78bfa)}
    .exec-funnel-step:nth-child(5){width:22%;height:32px;background:linear-gradient(90deg,#ec4899,#f472b6)}
    .exec-metric-table, .exec-funnel-table { width:100%; border-collapse:collapse; }
    .exec-metric-table tr, .exec-funnel-table tr { border-bottom:1px solid #f1f5f9; cursor:pointer; }
    html[data-theme="dark"] .exec-metric-table tr, html[data-theme="dark"] .exec-funnel-table tr { border-bottom-color:rgba(255,255,255,0.06); }
    .exec-metric-table tr:hover, .exec-funnel-table tr:hover { background:rgba(0,82,236,0.04); }
    .exec-funnel-table button { width:100%; color:inherit; text-align:left; font-size:.85rem; font-weight:700; }
    .exec-metric-table td, .exec-funnel-table td { padding:.65rem .4rem; color:#475569; font-size:.85rem; }
    html[data-theme="dark"] .exec-metric-table td, html[data-theme="dark"] .exec-funnel-table td { color:#cbd5e1; }
    .exec-funnel-table td:nth-child(2),.exec-funnel-table td:nth-child(3){text-align:right;color:#0f172a;font-weight:800}
    html[data-theme="dark"] .exec-funnel-table td:nth-child(2),html[data-theme="dark"] .exec-funnel-table td:nth-child(3){color:#ffffff}
    
    .exec-lower { display:grid; grid-template-columns:repeat(3,1fr); gap:1.25rem; margin-bottom:1.25rem; }
    .exec-list-card { min-height:220px; padding:1.25rem; }
    .exec-list-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:.85rem; }
    .exec-list-head h4 { margin:0; color:#0f172a; font-size:.9rem; font-weight:900; text-transform:uppercase; letter-spacing:.02em; }
    html[data-theme="dark"] .exec-list-head h4 { color:#ffffff; }
    .exec-list-head button { color:#0052ec; font-size:.8rem; font-weight:800; }
    html[data-theme="dark"] .exec-list-head button { color:#38bdf8; }
    
    .exec-summary { display:grid; grid-template-columns:repeat(6,1fr); padding:.75rem 1rem; }
    .exec-summary-item { display:flex; align-items:center; justify-content:center; gap:.75rem; min-height:58px; padding:.5rem; border-right:1px solid #f1f5f9; cursor:pointer; transition:all .2s; }
    html[data-theme="dark"] .exec-summary-item { border-right-color:rgba(255,255,255,0.06); }
    .exec-summary-item:last-child { border-right:0; }
    .exec-summary-item:hover { background:rgba(0,82,236,0.04); border-radius:12px; }
    .exec-summary-icon { display:grid; place-items:center; width:36px; height:36px; border-radius:10px; font-size:1.1rem; }
    .exec-summary-copy span { display:block; color:#64748b; font-size:.72rem; font-weight:800; text-transform:uppercase; }
    .exec-summary-copy strong { display:inline-block; color:#0f172a; font-size:1.25rem; font-weight:900; }
    html[data-theme="dark"] .exec-summary-copy strong { color:#ffffff; }
    
    @media (max-width:1280px) {
      .exec-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
      .exec-pipeline { grid-column:span 2; }
      .exec-central,.exec-lower { grid-template-columns:1fr; }
      .exec-summary { grid-template-columns:repeat(3,1fr); }
    }
    @media (max-width:1023px) {
      #page-area-privada.executive-mode > section { padding:0; }
      #page-area-privada.executive-mode .private-dashboard-shell { display:block; border-radius:0; border-left:0; border-right:0; }
      .exec-dashboard { padding:1rem; border-radius:0; }
    }
    @media (max-width:680px) {
      .exec-head { flex-direction:column; }
      .exec-head-actions { width:100%; }
      .exec-control { flex:1; }
      .exec-kpis { grid-template-columns:1fr; }
      .exec-pipeline { grid-column:auto; }
      .exec-distribution,.exec-funnel-grid { grid-template-columns:1fr; }
      .exec-donut { max-width:160px; }
      .exec-summary { grid-template-columns:1fr 1fr; }
      .exec-summary-item:nth-child(2n) { border-right:0; }
      .exec-summary-item:nth-child(n+5) { border-bottom:0; }
    }


    .home-kpi-card { position:relative; display:flex; min-height:132px; flex-direction:column; padding:1.15rem !important; }
    .home-kpi-row { display:flex; flex:1; align-items:flex-end; justify-content:space-between; gap:.85rem; margin-top:.65rem; }
    .home-kpi-copy { min-width:0; }
    .metric-action-link { display:inline-flex; min-height:42px; max-width:10.5rem; align-items:center; justify-content:center; padding:9px 12px; border:1px solid #b9c8d8; border-radius:10px; background:#fff; color:#0d4eae; font-size:12px; line-height:1.3; font-weight:600; text-align:center; }
    .metric-action-link:hover { border-color:#1b67d6; background:#e8f4ff; }
    @media (max-width:640px) {
      .home-kpi-card { min-height:auto; }
      .home-kpi-row { display:flex; align-items:stretch; flex-direction:column; gap:.85rem; }
      .home-kpi-copy { max-width:none; }
      .metric-action-link { width:100%; max-width:none; min-height:44px; }
    }
    .favorite-toggle { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; flex:0 0 34px; border:1px solid #b9c8d8; border-radius:9px; background:rgba(255,255,255,.96); color:#52677d; font-size:17px; line-height:1; box-shadow:0 2px 8px rgba(16,35,60,.12); }
    .favorite-toggle:hover { color:#e11d48; border-color:#fb7185; }
    .favorite-toggle.is-active { color:#be123c; border-color:#fb7185; background:#fff1f2; }
    .bg-navy .text-slate-400, .bg-navy-dark .text-slate-400, .from-navy .text-slate-400 { color:#cbd5e1 !important; }
    .bg-navy .text-slate-300, .bg-navy-dark .text-slate-300, .from-navy .text-slate-300 { color:#e2e8f0 !important; }
    html[data-theme="light"] .bg-white.border-slate-200, html[data-theme="light"] .bg-slate-50.border-slate-100 { border-color:#c7d3df !important; }
    html[data-theme="dark"] .metric-action-link { background:#172f50; border-color:#45627f; color:#d9eaff; }
    .resource-guest-download { background:#1b67d6; color:#fff; }
    .resource-guest-download:hover { background:#0d4eae; }
    html[data-theme="dark"] .resource-guest-download { background:#1b67d6; color:#fff; border-color:transparent; }
    html[data-theme="dark"] .resource-guest-download:hover { background:#0d4eae; }

    .private-field-label { display:block; margin-bottom:5px; color:var(--app-muted); font-size:10px; font-weight:800; text-transform:uppercase; }
    .private-field-input { width:100%; padding:10px 12px; border:1px solid var(--app-border); border-radius:10px; background:var(--app-surface); color:var(--app-text); font-size:12px; }
    .private-field-input:focus { outline:none; border-color:#1b67d6; box-shadow:0 0 0 3px rgba(27,103,214,.12); }

    /* Readable SaaS typography: WCAG-friendly default scale without losing dashboard density. */
    html { font-size: 16px; }
    body {
      font-size: 1rem;
      font-weight: 400;
      line-height: 1.5;
      text-rendering: optimizeLegibility;
    }
    p, li { line-height: 1.6; }
    h1, h2, h3, h4, h5, h6 { font-weight: 700; line-height: 1.18; }
    h3 { font-size: clamp(1.35rem, 1.15rem + .7vw, 1.55rem); }
    h4 { font-size: clamp(1.15rem, 1.02rem + .4vw, 1.3rem); }
    h5 { font-size: 1.0625rem; }
    .font-black, .font-extrabold { font-weight: 700 !important; letter-spacing: -.012em; }
    .font-bold { font-weight: 600; }
    .text-\[10px\], .text-\[11px\], .text-xs { font-size: .75rem !important; line-height: 1.45; }
    .private-field-label, .private-table th { font-size: .75rem; font-weight: 700; }
    .private-field-input { font-size: .9375rem; line-height: 1.5; }
    html[data-theme="light"] { --app-text-muted: #465a70; }
    html[data-theme="dark"] { --app-text-muted: #bdc9d8; }
    @media (min-width: 768px) {
      body { font-size: 1.0625rem; }
      p, li { line-height: 1.62; }
      .text-\[10px\], .text-\[11px\], .text-xs { font-size: .8125rem !important; }
    }
    @media (max-width: 640px) {
      .private-table { font-size: .875rem; }
      .private-table td { font-size: .8125rem; line-height: 1.45; }
      .private-dashboard-nav { font-size: .9375rem; }
    }

    /* =========================================================
       FORMULARIOS PROFESIONALES: COMPACTOS, LEGIBLES Y WCAG 2.2
       ========================================================= */
    :root {
      --form-shell: #f8fafc;
      --form-header: #f1f5f9;
      --form-section: #ffffff;
      --form-control: #ffffff;
      --form-control-disabled: #e8eef5;
      --form-border: #94a3b8;
      --form-border-soft: #cbd5e1;
      --form-label: #24364b;
      --form-muted: #52657a;
      --form-placeholder: #64748b;
      --form-focus: #1b67d6;
    }
    html[data-theme="dark"] {
      --form-shell: #0f1f32;
      --form-header: #14283f;
      --form-section: #12263c;
      --form-control: #091a2d;
      --form-control-disabled: #1c3048;
      --form-border: #526a84;
      --form-border-soft: #3f5873;
      --form-label: #f1f5f9;
      --form-muted: #c7d2df;
      --form-placeholder: #a7b6c8;
      --form-focus: #60a5fa;
    }
    .captacion-form-shell {
      background: var(--form-shell) !important;
      border-color: var(--form-border-soft) !important;
      border-radius: 1.25rem !important;
      box-shadow: 0 12px 28px rgba(15, 35, 60, .10) !important;
    }
    .captacion-form-shell > summary {
      min-height: 72px;
      padding: 1rem 1.25rem !important;
      background: var(--form-header) !important;
      border-bottom: 1px solid transparent;
    }
    .captacion-form-shell[open] > summary {
      border-bottom-color: var(--form-border-soft);
    }
    .captacion-form-shell > summary h3 {
      color: var(--form-label) !important;
      font-size: 1.125rem !important;
      line-height: 1.35;
    }
    .captacion-form-shell > summary p {
      color: var(--form-muted) !important;
      font-size: .875rem !important;
      line-height: 1.5;
    }
    .captacion-form-shell > div {
      padding: 1rem 1.25rem 1.25rem !important;
    }
    .captacion-professional-form {
      max-width: 1180px !important;
      margin-inline: auto;
      display: flex;
      flex-direction: column;
      gap: 1rem !important;
      color: var(--form-label);
    }
    .captacion-professional-form > * {
      margin-top: 0 !important;
    }
    .captacion-form-tools {
      min-height: 44px;
      padding-bottom: .75rem !important;
      border-color: var(--form-border-soft) !important;
    }
    .captacion-form-tools-label {
      color: var(--form-muted);
      font-size: .875rem;
      font-weight: 600;
    }
    .captacion-form-section {
      padding: 1rem !important;
      border: 1px solid var(--form-border-soft) !important;
      border-radius: 1rem !important;
      background: var(--form-section) !important;
    }
    .captacion-professional-form label,
    .captacion-search-panel label {
      color: var(--form-label) !important;
      font-size: .875rem !important;
      font-weight: 600 !important;
      line-height: 1.4 !important;
      margin-bottom: .375rem !important;
      text-transform: none !important;
      letter-spacing: normal !important;
    }
    .captacion-professional-form p,
    .captacion-professional-form small,
    .captacion-form-section > div > p,
    .captacion-search-panel p {
      color: var(--form-muted) !important;
    }
    .captacion-professional-form .text-xs,
    .captacion-professional-form .text-\[10px\],
    .captacion-professional-form .text-\[11px\] {
      font-size: .8125rem !important;
      line-height: 1.5 !important;
    }
    .captacion-professional-form input:not([type="radio"]):not([type="checkbox"]):not([type="file"]),
    .captacion-professional-form select,
    .captacion-professional-form textarea,
    .captacion-search-panel input,
    .captacion-search-panel select {
      width: 100%;
      max-width: none !important;
      min-height: 46px;
      padding: .65rem .8rem !important;
      border: 1px solid var(--form-border) !important;
      border-radius: .75rem !important;
      background: var(--form-control) !important;
      color: var(--form-label) !important;
      font-size: .9375rem !important;
      font-weight: 500;
      line-height: 1.4;
      opacity: 1 !important;
    }
    .captacion-professional-form textarea {
      min-height: 112px;
      resize: vertical;
    }
    .captacion-professional-form input::placeholder,
    .captacion-professional-form textarea::placeholder,
    .captacion-search-panel input::placeholder {
      color: var(--form-placeholder) !important;
      opacity: 1 !important;
    }
    .captacion-professional-form input:disabled,
    .captacion-professional-form select:disabled,
    .captacion-search-panel input:disabled,
    .captacion-search-panel select:disabled {
      background: var(--form-control-disabled) !important;
      color: var(--form-muted) !important;
      cursor: not-allowed;
    }
    .captacion-professional-form input:focus,
    .captacion-professional-form select:focus,
    .captacion-professional-form textarea:focus,
    .captacion-search-panel input:focus,
    .captacion-search-panel select:focus {
      border-color: var(--form-focus) !important;
      outline: 2px solid transparent !important;
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--form-focus) 28%, transparent) !important;
    }
    .captacion-professional-form input[type="checkbox"],
    .captacion-professional-form input[type="radio"] {
      width: 1.125rem;
      height: 1.125rem;
      min-height: 1.125rem;
      flex: 0 0 auto;
      accent-color: #1b67d6;
    }
    .captacion-form-grid {
      display: grid !important;
      align-items: end;
      gap: .75rem 1rem !important;
    }
    .captacion-grid-intro > div {
      grid-column: auto !important;
    }
    #need-publication-form .captacion-grid-intro {
      grid-template-columns: minmax(20rem, 2fr) minmax(15rem, 1fr) minmax(15rem, 1fr) !important;
    }
    #offer-publication-form .captacion-grid-intro {
      grid-template-columns: minmax(16rem, 22rem) minmax(18rem, 24rem) !important;
      justify-content: start;
    }
    .captacion-grid-location {
      grid-template-columns: minmax(14rem, 1.25fr) minmax(13rem, 1fr) minmax(14rem, 1fr) minmax(8rem, .55fr) minmax(13rem, 1fr) !important;
    }
    .captacion-grid-features {
      grid-template-columns: repeat(3, minmax(10rem, 14rem)) !important;
      justify-content: start;
    }
    .captacion-grid-features-optional {
      grid-template-columns: repeat(4, minmax(10rem, 13rem)) !important;
    }
    .captacion-grid-commercial {
      grid-template-columns: repeat(5, minmax(10.5rem, 1fr)) !important;
    }
    .captacion-grid-commercial-pair {
      grid-template-columns: minmax(16rem, 26rem) minmax(18rem, 28rem) !important;
      justify-content: start;
    }
    .captacion-grid-requirements {
      grid-template-columns: repeat(3, minmax(16rem, 1fr)) !important;
    }
    .captacion-grid-halves {
      grid-template-columns: repeat(2, minmax(18rem, 32rem)) !important;
      justify-content: start;
    }
    #offer-title,
    #need-pub-title { max-width: 46rem !important; }
    #offer-description,
    #need-pub-desc { max-width: 58rem !important; }
    #offer-cadastral-reference { max-width: 24rem !important; }
    .captacion-form-media label.flex {
      min-height: 74px;
      padding: .875rem !important;
      border-color: var(--form-border-soft) !important;
      background: var(--form-control) !important;
    }
    .captacion-form-media strong {
      color: var(--form-label) !important;
      font-size: .875rem !important;
    }
    .captacion-form-media small {
      color: var(--form-muted) !important;
      font-size: .8125rem !important;
    }
    #offer-image-upload-panel {
      padding: 1rem !important;
      border-color: var(--form-border) !important;
      background: var(--form-header) !important;
    }
    .captacion-form-actions {
      padding: 1rem !important;
      border: 1px solid var(--form-border-soft);
      border-radius: 1rem;
      background: var(--form-header);
    }
    .captacion-form-actions .legal-consent-box {
      max-width: 42rem;
      color: var(--form-muted) !important;
      font-size: .8125rem !important;
    }
    .captacion-form-actions button[type="submit"] {
      min-height: 46px;
      padding: .75rem 1.25rem !important;
      font-size: .9375rem !important;
      font-weight: 700 !important;
      white-space: nowrap;
    }
    .captacion-search-panel {
      max-width: 1180px;
      margin-inline: auto;
      padding: 1rem 1.25rem !important;
      border-color: var(--form-border-soft) !important;
      background: var(--form-shell) !important;
    }
    .captacion-search-grid {
      display: grid !important;
      grid-template-columns: minmax(16rem, 1.2fr) minmax(14rem, 1fr) minmax(14rem, 1fr) auto !important;
      gap: .75rem 1rem !important;
      align-items: end;
    }
    .captacion-search-grid > button {
      min-height: 46px;
      padding-inline: 1.25rem !important;
      font-size: .9375rem !important;
      white-space: nowrap;
    }
    .captacion-advanced-filters {
      padding: .875rem 1rem !important;
      border-color: var(--form-border-soft) !important;
      background: var(--form-section) !important;
    }
    .captacion-advanced-filters > summary {
      min-height: 28px;
      color: var(--form-focus) !important;
      font-size: .875rem !important;
      font-weight: 650 !important;
    }
    .captacion-search-grid-advanced {
      grid-template-columns: repeat(5, minmax(10rem, 1fr)) !important;
    }
    html[data-theme="dark"] .captacion-form-shell,
    html[data-theme="dark"] .captacion-search-panel,
    html[data-theme="dark"] .captacion-form-section,
    html[data-theme="dark"] .captacion-advanced-filters,
    html[data-theme="dark"] .captacion-form-actions {
      color: var(--form-label) !important;
    }
    html[data-theme="dark"] .captacion-professional-form label,
    html[data-theme="dark"] .captacion-search-panel label,
    html[data-theme="dark"] .captacion-form-tools-label {
      color: var(--form-label) !important;
    }
    html[data-theme="dark"] .captacion-professional-form p,
    html[data-theme="dark"] .captacion-professional-form small,
    html[data-theme="dark"] .captacion-search-panel p,
    html[data-theme="dark"] .captacion-form-actions .legal-consent-box {
      color: var(--form-muted) !important;
    }
    @media (min-width: 1024px) {
      .captacion-form-actions {
        position: sticky;
        bottom: .75rem;
        z-index: 20;
        box-shadow: 0 10px 26px rgba(15, 35, 60, .16);
      }
    }
    @media (max-width: 1180px) {
      #need-publication-form .captacion-grid-intro,
      #offer-publication-form .captacion-grid-intro,
      .captacion-grid-location,
      .captacion-grid-commercial,
      .captacion-grid-requirements,
      .captacion-grid-halves,
      .captacion-search-grid,
      .captacion-search-grid-advanced {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
      }
      .captacion-grid-commercial-pair {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
      }
    }
    @media (max-width: 767px) {
      .captacion-form-shell > summary,
      .captacion-form-shell > div,
      .captacion-search-panel { padding: .875rem !important; }
      #need-publication-form .captacion-grid-intro,
      #offer-publication-form .captacion-grid-intro,
      .captacion-grid-location,
      .captacion-grid-features,
      .captacion-grid-features-optional,
      .captacion-grid-commercial,
      .captacion-grid-commercial-pair,
      .captacion-grid-requirements,
      .captacion-grid-halves,
      .captacion-search-grid,
      .captacion-search-grid-advanced {
        grid-template-columns: minmax(0, 1fr) !important;
      }
      .captacion-form-section { padding: .875rem !important; }
      .captacion-form-actions button[type="submit"] { width: 100%; }
    }

    .private-panel-tabs { display:flex; flex-wrap:wrap; gap:.35rem; margin:0 0 1.15rem; padding:.3rem; border:1px solid var(--app-border); border-radius:.9rem; background:var(--app-surface-soft); }
    .private-panel-tab { min-height:42px; padding:.62rem .9rem; border-radius:.7rem; color:var(--app-text-muted); font-size:.875rem; line-height:1.25; font-weight:600; text-align:center; }
    .private-panel-tab:hover { color:#1b67d6; background:var(--app-surface); }
    .private-panel-tab.active { color:#fff; background:#10233c; box-shadow:0 4px 12px rgba(16,35,60,.16); }
    html[data-theme="dark"] .private-panel-tab.active { background:#1b67d6; }
    .private-profile-form { width:100%; max-width:none; }
    .private-profile-section { width:100%; padding:1rem; border:1px solid var(--app-border); border-radius:1rem; background:var(--app-surface); box-shadow:0 6px 16px rgba(15,35,60,.04); }
    .private-profile-section-private { border-color:rgba(27,103,214,.28); }
    .private-profile-section-heading { margin-bottom:.9rem; padding-bottom:.65rem; border-bottom:1px solid var(--app-border); }
    .private-profile-section-heading h4 { color:var(--app-text); font-size:1rem; }
    .private-profile-section-heading p { margin-top:.2rem; color:var(--app-text-muted); font-size:.8125rem; }
    .private-profile-fields { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.8rem; align-items:start; }
    .profile-progress-notice { padding:1rem; border:1px solid; border-radius:1rem; color:var(--app-text); }
    .profile-progress-low { border-color:#f1a94b; background:rgba(217,119,6,.11); }
    .profile-progress-medium { border-color:#e0b12e; background:rgba(234,179,8,.11); }
    .profile-progress-complete { border-color:#2fb57b; background:rgba(16,185,129,.11); }
    .profile-progress-low [id$="completion-bar"], .profile-progress-low #professional-profile-progress-bar { background:#d97706; }
    .profile-progress-medium [id$="completion-bar"], .profile-progress-medium #professional-profile-progress-bar { background:#ca8a04; }
    .profile-progress-complete [id$="completion-bar"], .profile-progress-complete #professional-profile-progress-bar { background:#159568; }
    html[data-theme="dark"] .private-profile-section { background:#12263c; }
    html[data-theme="dark"] .private-profile-section-heading h4 { color:#f4f8fc; }
    html[data-theme="dark"] .private-profile-section-heading p, html[data-theme="dark"] .profile-progress-notice { color:#c4d0dd; }
    html[data-theme="dark"] .listing-url-import { background:#12263c !important; border-color:#365a7d !important; }
    html[data-theme="dark"] .listing-url-import h4 { color:#f4f8fc !important; }
    html[data-theme="dark"] .listing-url-import p, html[data-theme="dark"] .listing-url-import label { color:#c4d0dd !important; }
    html[data-theme="dark"] .listing-url-import #offer-source-import-result { background:#0d2035 !important; border-color:#365a7d !important; }
    html[data-theme="dark"] .listing-url-import #offer-source-assisted { background:#18283a !important; border-color:#9b6a18 !important; }
    html[data-theme="dark"] .listing-url-import #offer-source-assisted h5 { color:#f4f8fc !important; }
    html[data-theme="dark"] .profile-logo-upload label[for="profile-logo-file"] { background:#172f50; color:#d9eaff; border-color:#45627f; }
    @media (max-width:900px) { .private-profile-fields { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:640px) { .private-panel-tabs { display:grid; grid-template-columns:1fr; } .private-profile-fields { grid-template-columns:1fr; } .private-profile-fields .md\:col-span-2 { grid-column:auto; } }
</style>

<style>
  /* ==========================================
     CENTRO DE COMUNICACIÓN INTERNA Y TRAZABILIDAD · DEMO WORDPRESS
     ========================================== */
  .comm-stat-card{border:1px solid var(--app-border);background:var(--app-surface);border-radius:1rem;padding:.95rem;box-shadow:0 8px 18px rgba(15,35,60,.05)}
  .comm-channel-badge{display:inline-flex;align-items:center;gap:.25rem;border-radius:999px;padding:.28rem .55rem;font-size:.62rem;font-weight:800;line-height:1;border:1px solid var(--app-border)}
  .comm-channel-ok{background:#eaf8f2;color:#167453;border-color:#bde9d7}.comm-channel-pending{background:#fff6df;color:#9a6500;border-color:#f5d98b}.comm-channel-off{background:#f1f5f9;color:#64748b;border-color:#d8e0e8}
  .comm-thread-card{border:1px solid var(--app-border);background:var(--app-surface);border-radius:1rem;padding:1rem;transition:.18s ease;box-shadow:0 8px 18px rgba(15,35,60,.04)}
  .comm-thread-card:hover{transform:translateY(-2px);border-color:#9ac3f8;box-shadow:0 14px 26px rgba(27,103,214,.1)}
  .comm-message{max-width:82%;border-radius:1rem;padding:.72rem .85rem;font-size:.75rem;line-height:1.5}
  .comm-message-system{background:var(--app-surface-soft);color:var(--app-text-muted);border:1px solid var(--app-border)}
  .comm-message-me{margin-left:auto;background:#1b67d6;color:white}.comm-message-other{background:var(--app-surface-soft);color:var(--app-text);border:1px solid var(--app-border)}
  .comm-trace-line{position:relative;padding-left:1.25rem}.comm-trace-line:before{content:'';position:absolute;left:.25rem;top:.2rem;bottom:-.85rem;width:1px;background:var(--app-border)}.comm-trace-line:last-child:before{display:none}.comm-trace-line:after{content:'';position:absolute;left:0;top:.34rem;width:.54rem;height:.54rem;border-radius:999px;background:#1b67d6;box-shadow:0 0 0 3px rgba(27,103,214,.14)}
  .comm-safe-banner{border:1px solid rgba(21,147,106,.26);background:linear-gradient(135deg,rgba(21,147,106,.1),rgba(27,103,214,.07));border-radius:1rem;padding:1rem}
  .internal-page-banner {
    position: relative;
    min-height: 168px;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, .34);
    border-radius: 1.35rem;
    background: #071a31;
    box-shadow: 0 18px 45px rgba(15, 35, 61, .14);
  }
  .internal-page-banner::after {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 1;
    background: linear-gradient(90deg, rgba(7, 26, 49, .9) 0%, rgba(7, 26, 49, .72) 35%, rgba(7, 26, 49, .24) 70%, rgba(7, 26, 49, .08) 100%);
    pointer-events: none;
  }
  .internal-page-banner__image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 47%;
  }
  .internal-page-banner__content {
    position: relative;
    z-index: 2;
    display: flex;
    min-height: 168px;
    max-width: 720px;
    flex-direction: column;
    justify-content: center;
    padding: 1.6rem 2rem;
  }
  .internal-page-banner__kicker {
    display: inline-flex;
    align-self: flex-start;
    padding: .35rem .75rem;
    border: 1px solid rgba(255,255,255,.26);
    border-radius: 999px;
    background: rgba(7, 26, 49, .48);
    color: #dbeafe;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .12em;
    line-height: 1.2;
    text-transform: uppercase;
  }
  .internal-page-banner__title {
    margin-top: .65rem;
    max-width: 650px;
    color: #fff;
    font-size: clamp(1.65rem, 2.25vw, 2.15rem);
    font-weight: 900;
    letter-spacing: -.025em;
    line-height: 1.08;
    text-wrap: balance;
  }
  .internal-page-banner__support {
    margin-top: .55rem;
    max-width: 590px;
    color: #e2e8f0;
    font-size: .95rem;
    line-height: 1.5;
    text-wrap: pretty;
  }
  .internal-page-banner--how .internal-page-banner__image {
    object-position: center 46%;
  }
  .internal-split-banner {
    display: grid;
    grid-template-columns: minmax(210px, 260px) minmax(0, 1fr);
    align-items: stretch;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, .34);
    border-radius: 1.35rem;
    background: var(--surface, #fff);
    box-shadow: 0 18px 45px rgba(15, 35, 61, .1);
  }
  .internal-split-banner__media {
    position: relative;
    min-height: 0;
    aspect-ratio: auto;
    align-self: stretch;
    overflow: hidden;
    background: #dbeafe;
  }
  .internal-split-banner__image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
  }
  .internal-split-banner__content {
    display: flex;
    min-width: 0;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    padding: 1.65rem clamp(1.4rem, 3vw, 2.75rem);
    text-align: left;
  }
  .internal-split-banner__kicker {
    display: inline-flex;
    padding: .35rem .75rem;
    border-radius: 999px;
    background: rgba(27, 103, 214, .12);
    color: #1b67d6;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .11em;
    line-height: 1.2;
    text-transform: uppercase;
  }
  .internal-split-banner__title {
    margin-top: .7rem;
    max-width: 920px;
    color: var(--text-primary, #10233c);
    font-size: clamp(1.65rem, 2.3vw, 2.2rem);
    font-weight: 900;
    letter-spacing: -.025em;
    line-height: 1.1;
    text-wrap: balance;
  }
  .internal-split-banner__support {
    margin-top: .6rem;
    max-width: 880px;
    color: var(--text-secondary, #64748b);
    font-size: .95rem;
    line-height: 1.5;
    text-wrap: pretty;
  }
  .internal-split-banner__action {
    margin-top: 1rem;
  }
  html[data-theme="dark"] .internal-page-banner {
    border-color: rgba(148, 163, 184, .4);
    box-shadow: 0 20px 48px rgba(0, 0, 0, .3);
  }
  html[data-theme="dark"] .internal-split-banner {
    border-color: rgba(148, 163, 184, .35);
    background: #0d2038;
    box-shadow: 0 20px 48px rgba(0, 0, 0, .26);
  }
  html[data-theme="dark"] .internal-split-banner__title { color: #f8fafc; }
  html[data-theme="dark"] .internal-split-banner__support { color: #cbd5e1; }
  html[data-theme="dark"] .internal-split-banner__kicker {
    background: rgba(37, 99, 235, .22);
    color: #bfdbfe;
  }
  @media (max-width: 640px) {
    .internal-page-banner {
      min-height: 136px;
      border-radius: 1rem;
    }
    .internal-page-banner::after {
      background: linear-gradient(90deg, rgba(7, 26, 49, .92) 0%, rgba(7, 26, 49, .76) 62%, rgba(7, 26, 49, .28) 100%);
    }
    .internal-page-banner__image {
      object-position: 48% 47%;
    }
    .internal-page-banner__content {
      min-height: 136px;
      padding: 1.15rem 1.2rem;
    }
    .internal-page-banner__kicker { font-size: .62rem; }
    .internal-page-banner__title { font-size: 1.45rem; }
    .internal-page-banner__support {
      max-width: 94%;
      font-size: .82rem;
      line-height: 1.42;
    }
    .internal-split-banner {
      grid-template-columns: 1fr;
      border-radius: 1rem;
    }
    .internal-split-banner__media {
      width: 100%;
      min-height: 0;
      aspect-ratio: 1;
      margin: 0;
      border-radius: 0;
    }
    .internal-split-banner__content {
      padding: 1.15rem 1.2rem 1.35rem;
    }
    .internal-split-banner__title { font-size: 1.45rem; }
    .internal-split-banner__support {
      font-size: .84rem;
      line-height: 1.45;
    }
  }
  .comm-flow-step{display:flex;align-items:center;gap:.45rem;color:#64748b;font-size:.67rem;font-weight:800}.comm-flow-step:before{content:'';width:.55rem;height:.55rem;border-radius:999px;background:#cbd5e1}.comm-flow-step.done{color:#167453}.comm-flow-step.done:before{background:#15936a}.comm-flow-step.current{color:#1b67d6}.comm-flow-step.current:before{background:#1b67d6;box-shadow:0 0 0 3px rgba(27,103,214,.14)}
  .comm-table th{font-size:.62rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:800;white-space:nowrap}.comm-table td{font-size:.72rem;color:var(--app-text-muted);vertical-align:top}
  .opportunity-accordion{border:1px solid var(--app-border);background:var(--app-surface);border-radius:1.25rem;box-shadow:0 8px 20px rgba(15,35,60,.05);overflow:hidden}
  .opportunity-accordion summary{list-style:none;cursor:pointer}
  .opportunity-accordion summary::-webkit-details-marker{display:none}
  .opportunity-accordion[open] .opportunity-accordion-chevron{transform:rotate(180deg)}
  .opportunity-mini-row{border:1px solid var(--app-border-soft);background:var(--app-surface-soft);border-radius:1rem;padding:.85rem}
  .opportunity-showcase{border:1px solid var(--app-border);background:var(--app-surface);border-radius:1.4rem;box-shadow:0 10px 24px rgba(15,35,60,.06);padding:1.1rem 1.1rem 1.2rem}
  .opportunity-showcase-rail{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(220px,1fr);gap:1rem;overflow-x:auto;padding-bottom:.35rem;scrollbar-width:thin;scroll-snap-type:x proximity}
  .opportunity-showcase-rail::-webkit-scrollbar{height:.45rem}
  .opportunity-showcase-rail::-webkit-scrollbar-thumb{background:rgba(100,116,139,.28);border-radius:999px}
  .opportunity-showcase-card{scroll-snap-align:start;border:1px solid rgba(148,163,184,.18);background:linear-gradient(180deg,#152847 0%,#12223c 100%);border-radius:1.35rem;overflow:hidden;box-shadow:0 18px 34px rgba(7,18,33,.18);min-height:100%;color:#f8fbff}
  .opportunity-showcase-card-image{position:relative;aspect-ratio:16/10;background:#0f172a;overflow:hidden}
  .opportunity-showcase-card-image img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
  .opportunity-showcase-card-image:after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(12,24,44,.02) 0%,rgba(12,24,44,.28) 55%,rgba(12,24,44,.72) 100%)}
  .opportunity-showcase-badge{position:absolute;left:.9rem;bottom:.9rem;z-index:2;display:inline-flex;align-items:center;border-radius:999px;padding:.34rem .62rem;background:rgba(18,40,71,.82);color:#93c5fd;font-size:.63rem;font-weight:900;letter-spacing:.02em;text-transform:uppercase}
  .opportunity-showcase-score{position:absolute;right:.9rem;top:.9rem;z-index:2;display:inline-flex;align-items:center;border-radius:999px;padding:.38rem .64rem;background:rgba(255,255,255,.94);color:#0f2746;font-size:.64rem;font-weight:900;box-shadow:0 10px 20px rgba(15,23,42,.16)}
  .opportunity-showcase-body{padding:1rem}
  .opportunity-showcase-meta{display:flex;align-items:center;justify-content:space-between;gap:.8rem;color:#b7c6da;font-size:.69rem;font-weight:700}
  .opportunity-showcase-title{display:block;margin-top:.75rem;color:#f8fbff;font-size:1.05rem;line-height:1.2;font-weight:900}
  .opportunity-showcase-copy{display:block;margin-top:.45rem;color:#d8e3f1;font-size:.76rem;line-height:1.45}
  .opportunity-showcase-footer{display:flex;align-items:end;justify-content:space-between;gap:.9rem;margin-top:1rem;padding-top:.9rem;border-top:1px solid rgba(255,255,255,.09)}
  .opportunity-showcase-price{display:block;color:#f8fbff;font-size:1.3rem;line-height:1;font-weight:900}
  .opportunity-showcase-note{display:block;margin-top:.25rem;color:#9fb3ca;font-size:.68rem;font-weight:700}
  .opportunity-showcase-shell{display:flex;flex-direction:column;gap:1rem}
  .opportunity-showcase-toolbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem}
  .opportunity-showcase-controls{display:flex;align-items:center;flex-wrap:wrap;justify-content:flex-end;gap:.55rem}
  .opportunity-showcase-arrow{min-width:3rem;height:2.6rem;padding:0 .9rem;border-radius:999px;border:1px solid var(--app-border);background:var(--app-surface-soft);color:var(--app-text);font-size:.86rem;font-weight:900;display:inline-flex;align-items:center;justify-content:center;gap:.35rem;transition:.18s ease}
  .opportunity-showcase-arrow:hover{transform:translateY(-1px);border-color:#9ac3f8;color:#1b67d6;background:#eff6ff}
  .opportunity-showcase-arrow-label{font-size:.68rem;letter-spacing:.02em}
  .opportunity-category-explorer{border:1px solid var(--app-border);background:var(--app-surface);border-radius:1.4rem;box-shadow:0 10px 24px rgba(15,35,60,.06);padding:1.1rem}
  .opportunity-category-explorer-toolbar{display:flex;flex-wrap:wrap;align-items:end;justify-content:space-between;gap:1rem}
  .opportunity-category-search{width:min(100%,20rem);padding:.82rem .95rem;border-radius:1rem;border:1px solid var(--app-border);background:var(--app-surface-soft);color:var(--app-text);font-size:.78rem;font-weight:700}
  .opportunity-category-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1rem;margin-top:1rem}
  .opportunity-category-card{display:flex;flex-direction:column;overflow:hidden;border:1px solid rgba(148,163,184,.18);border-radius:1.2rem;background:linear-gradient(180deg,#152847 0%,#12223c 100%);box-shadow:0 16px 30px rgba(7,18,33,.14);color:#f8fbff}
  .opportunity-category-card.is-hidden{display:none}
  .opportunity-category-card-image{position:relative;aspect-ratio:16/10;background:#0f172a}
  .opportunity-category-card-image img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
  .opportunity-category-card-image:after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(12,24,44,.04) 0%,rgba(12,24,44,.36) 52%,rgba(12,24,44,.78) 100%)}
  .opportunity-category-card-badge{position:absolute;left:.9rem;bottom:.9rem;z-index:2;display:inline-flex;align-items:center;border-radius:999px;padding:.34rem .62rem;background:rgba(18,40,71,.82);color:#bfdbfe;font-size:.62rem;font-weight:900;letter-spacing:.03em;text-transform:uppercase}
  .opportunity-category-card-count{position:absolute;right:.9rem;top:.9rem;z-index:2;display:inline-flex;align-items:center;border-radius:999px;padding:.36rem .62rem;background:rgba(255,255,255,.94);color:#0f2746;font-size:.64rem;font-weight:900}
  .opportunity-category-card-body{display:flex;flex:1;flex-direction:column;gap:.6rem;padding:1rem}
  .opportunity-category-card-title{display:block;font-size:1rem;line-height:1.2;font-weight:900;color:#f8fbff}
  .opportunity-category-card-copy{display:block;color:#d8e3f1;font-size:.76rem;line-height:1.45}
  .opportunity-category-card-footer{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-top:auto;padding-top:.85rem;border-top:1px solid rgba(255,255,255,.09)}
  .opportunity-category-card-note{display:block;color:#9fb3ca;font-size:.68rem;font-weight:700}
  .opportunity-category-card-action{padding:.7rem .95rem;border-radius:1rem;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.08);color:#fff;font-size:.7rem;font-weight:900;letter-spacing:.02em}
  .opportunity-category-empty{margin-top:1rem;padding:1rem 1.1rem;border-radius:1rem;border:1px dashed var(--app-border);background:var(--app-surface-soft);color:var(--app-text-muted);font-size:.78rem;font-weight:700}
  @media (max-width:768px){.opportunity-showcase-rail{grid-auto-columns:minmax(260px,88vw)}}
  .private-calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:.35rem}
  .private-calendar-day{min-height:4.4rem;border:1px solid var(--app-border-soft);border-radius:.9rem;background:var(--app-surface-soft);padding:.45rem;display:flex;flex-direction:column;gap:.2rem}
  .private-calendar-day.is-today{border-color:#1b67d6;box-shadow:0 0 0 1px rgba(27,103,214,.18) inset}
  .private-calendar-day.is-active{background:linear-gradient(180deg,rgba(27,103,214,.08),rgba(21,147,106,.04))}
  .private-calendar-dot{width:.42rem;height:.42rem;border-radius:999px;display:inline-block}
  .comm-flow-step{display:flex;align-items:center;gap:.45rem;color:#64748b;font-size:.67rem;font-weight:800}.comm-flow-step:before{content:'';width:.55rem;height:.55rem;border-radius:999px;background:#cbd5e1}.comm-flow-step.done{color:#167453}.comm-flow-step.done:before{background:#15936a}.comm-flow-step.current{color:#1b67d6}.comm-flow-step.current:before{background:#1b67d6;box-shadow:0 0 0 3px rgba(27,103,214,.14)}
  .comm-table th{font-size:.62rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:800;white-space:nowrap}.comm-table td{font-size:.72rem;color:var(--app-text-muted);vertical-align:top}
  .opportunity-accordion{border:1px solid var(--app-border);background:var(--app-surface);border-radius:1.25rem;box-shadow:0 8px 20px rgba(15,35,60,.05);overflow:hidden}
  .opportunity-accordion summary{list-style:none;cursor:pointer}
  .opportunity-accordion summary::-webkit-details-marker{display:none}
  .opportunity-accordion[open] .opportunity-accordion-chevron{transform:rotate(180deg)}
  .opportunity-mini-row{border:1px solid var(--app-border-soft);background:var(--app-surface-soft);border-radius:1rem;padding:.85rem}
  .opportunity-showcase{border:1px solid var(--app-border);background:var(--app-surface);border-radius:1.4rem;box-shadow:0 10px 24px rgba(15,35,60,.06);padding:1.1rem 1.1rem 1.2rem}
  .opportunity-showcase-rail{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(220px,1fr);gap:1rem;overflow-x:auto;padding-bottom:.35rem;scrollbar-width:thin;scroll-snap-type:x proximity}
  .opportunity-showcase-rail::-webkit-scrollbar{height:.45rem}
  .opportunity-showcase-rail::-webkit-scrollbar-thumb{background:rgba(100,116,139,.28);border-radius:999px}
  .opportunity-showcase-card{scroll-snap-align:start;border:1px solid rgba(148,163,184,.18);background:linear-gradient(180deg,#152847 0%,#12223c 100%);border-radius:1.35rem;overflow:hidden;box-shadow:0 18px 34px rgba(7,18,33,.18);min-height:100%;color:#f8fbff}
  .opportunity-showcase-card-image{position:relative;aspect-ratio:16/10;background:#0f172a;overflow:hidden}
  .opportunity-showcase-card-image img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
  .opportunity-showcase-card-image:after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(12,24,44,.02) 0%,rgba(12,24,44,.28) 55%,rgba(12,24,44,.72) 100%)}
  .opportunity-showcase-badge{position:absolute;left:.9rem;bottom:.9rem;z-index:2;display:inline-flex;align-items:center;border-radius:999px;padding:.34rem .62rem;background:rgba(18,40,71,.82);color:#93c5fd;font-size:.63rem;font-weight:900;letter-spacing:.02em;text-transform:uppercase}
  .opportunity-showcase-score{position:absolute;right:.9rem;top:.9rem;z-index:2;display:inline-flex;align-items:center;border-radius:999px;padding:.38rem .64rem;background:rgba(255,255,255,.94);color:#0f2746;font-size:.64rem;font-weight:900;box-shadow:0 10px 20px rgba(15,23,42,.16)}
  .opportunity-showcase-body{padding:1rem}
  .opportunity-showcase-meta{display:flex;align-items:center;justify-content:space-between;gap:.8rem;color:#b7c6da;font-size:.69rem;font-weight:700}
  .opportunity-showcase-title{display:block;margin-top:.75rem;color:#f8fbff;font-size:1.05rem;line-height:1.2;font-weight:900}
  .opportunity-showcase-copy{display:block;margin-top:.45rem;color:#d8e3f1;font-size:.76rem;line-height:1.45}
  .opportunity-showcase-footer{display:flex;align-items:end;justify-content:space-between;gap:.9rem;margin-top:1rem;padding-top:.9rem;border-top:1px solid rgba(255,255,255,.09)}
  .opportunity-showcase-price{display:block;color:#f8fbff;font-size:1.3rem;line-height:1;font-weight:900}
  .opportunity-showcase-note{display:block;margin-top:.25rem;color:#9fb3ca;font-size:.68rem;font-weight:700}
  .opportunity-showcase-shell{display:flex;flex-direction:column;gap:1rem}
  .opportunity-showcase-toolbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem}
  .opportunity-showcase-controls{display:flex;align-items:center;flex-wrap:wrap;justify-content:flex-end;gap:.55rem}
  .opportunity-showcase-arrow{min-width:3rem;height:2.6rem;padding:0 .9rem;border-radius:999px;border:1px solid var(--app-border);background:var(--app-surface-soft);color:var(--app-text);font-size:.86rem;font-weight:900;display:inline-flex;align-items:center;justify-content:center;gap:.35rem;transition:.18s ease}
  .opportunity-showcase-arrow:hover{transform:translateY(-1px);border-color:#9ac3f8;color:#1b67d6;background:#eff6ff}
  .opportunity-showcase-arrow-label{font-size:.68rem;letter-spacing:.02em}
  .opportunity-category-explorer{border:1px solid var(--app-border);background:var(--app-surface);border-radius:1.4rem;box-shadow:0 10px 24px rgba(15,35,60,.06);padding:1.1rem}
  .opportunity-category-explorer-toolbar{display:flex;flex-wrap:wrap;align-items:end;justify-content:space-between;gap:1rem}
  .opportunity-category-search{width:min(100%,20rem);padding:.82rem .95rem;border-radius:1rem;border:1px solid var(--app-border);background:var(--app-surface-soft);color:var(--app-text);font-size:.78rem;font-weight:700}
  .opportunity-category-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1rem;margin-top:1rem}
  .opportunity-category-card{display:flex;flex-direction:column;overflow:hidden;border:1px solid rgba(148,163,184,.18);border-radius:1.2rem;background:linear-gradient(180deg,#152847 0%,#12223c 100%);box-shadow:0 16px 30px rgba(7,18,33,.14);color:#f8fbff}
  .opportunity-category-card.is-hidden{display:none}
  .opportunity-category-card-image{position:relative;aspect-ratio:16/10;background:#0f172a}
  .opportunity-category-card-image img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
  .opportunity-category-card-image:after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(12,24,44,.04) 0%,rgba(12,24,44,.36) 52%,rgba(12,24,44,.78) 100%)}
  .opportunity-category-card-badge{position:absolute;left:.9rem;bottom:.9rem;z-index:2;display:inline-flex;align-items:center;border-radius:999px;padding:.34rem .62rem;background:rgba(18,40,71,.82);color:#bfdbfe;font-size:.62rem;font-weight:900;letter-spacing:.03em;text-transform:uppercase}
  .opportunity-category-card-count{position:absolute;right:.9rem;top:.9rem;z-index:2;display:inline-flex;align-items:center;border-radius:999px;padding:.36rem .62rem;background:rgba(255,255,255,.94);color:#0f2746;font-size:.64rem;font-weight:900}
  .opportunity-category-card-body{display:flex;flex:1;flex-direction:column;gap:.6rem;padding:1rem}
  .opportunity-category-card-title{display:block;font-size:1rem;line-height:1.2;font-weight:900;color:#f8fbff}
  .opportunity-category-card-copy{display:block;color:#d8e3f1;font-size:.76rem;line-height:1.45}
  .opportunity-category-card-footer{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-top:auto;padding-top:.85rem;border-top:1px solid rgba(255,255,255,.09)}
  .opportunity-category-card-note{display:block;color:#9fb3ca;font-size:.68rem;font-weight:700}
  .opportunity-category-card-action{padding:.7rem .95rem;border-radius:1rem;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.08);color:#fff;font-size:.7rem;font-weight:900;letter-spacing:.02em}
  .opportunity-category-empty{margin-top:1rem;padding:1rem 1.1rem;border-radius:1rem;border:1px dashed var(--app-border);background:var(--app-surface-soft);color:var(--app-text-muted);font-size:.78rem;font-weight:700}
  @media (max-width:768px){.opportunity-showcase-rail{grid-auto-columns:minmax(260px,88vw)}}
  .private-calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:.35rem}
  .private-calendar-day{min-height:4.4rem;border:1px solid var(--app-border-soft);border-radius:.9rem;background:var(--app-surface-soft);padding:.45rem;display:flex;flex-direction:column;gap:.2rem}
  .private-calendar-day.is-today{border-color:#1b67d6;box-shadow:0 0 0 1px rgba(27,103,214,.18) inset}
  .private-calendar-day.is-active{background:linear-gradient(180deg,rgba(27,103,214,.08),rgba(21,147,106,.04))}
  .private-calendar-dot{width:.42rem;height:.42rem;border-radius:999px;display:inline-block}
  .private-calendar-dot.task{background:#1b67d6}
  .private-calendar-dot.alert{background:#b8730f}
  .private-calendar-dot.op{background:#15936a}
  html[data-theme="dark"] .comm-message-me{background:linear-gradient(135deg,#1b67d6,#5b3fd1)}
  html[data-theme="dark"] .comm-channel-ok{background:rgba(21,147,106,.16);border-color:rgba(21,147,106,.36);color:#8ee6c4}
  html[data-theme="dark"] .comm-channel-pending{background:rgba(217,139,19,.14);border-color:rgba(217,139,19,.34);color:#ffd98b}
  html[data-theme="dark"] nav .group>div>div{background:#10233c;border-color:#29415f}
  html[data-theme="dark"] nav .group>div>div a{color:#dbe7f5}
  html[data-theme="dark"] nav .group>div>div a:hover{background:#193859;color:#93c5fd}
  .territory-scroll-hint{margin-top:.3rem;color:var(--app-text-muted);font-size:.62rem;font-weight:700}
</style>

  <!-- Favicon Dinámico / Animado y Soporte PWA para dispositivos móviles y escritorio -->
  <link rel="icon" type="image/svg+xml" href="<?php echo esc_url($captacion_favicon_animated_url . '?v=3'); ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url($captacion_favicon_url . '?v=3'); ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url($captacion_favicon_url . '?v=3'); ?>">
  <link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url(home_url('/assets/media/icon-192.png?v=3')); ?>">
  <link rel="icon" type="image/png" sizes="512x512" href="<?php echo esc_url(home_url('/assets/media/icon-512.png?v=3')); ?>">
  <link rel="shortcut icon" href="<?php echo esc_url($captacion_favicon_url . '?v=3'); ?>">
  <link rel="apple-touch-icon" href="<?php echo esc_url(home_url('/assets/media/apple-touch-icon.png?v=3')); ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(home_url('/assets/media/apple-touch-icon.png?v=3')); ?>">
  <link rel="apple-touch-icon" sizes="152x152" href="<?php echo esc_url(home_url('/assets/media/apple-touch-icon.png?v=3')); ?>">
  <link rel="apple-touch-icon" sizes="120x120" href="<?php echo esc_url(home_url('/assets/media/apple-touch-icon.png?v=3')); ?>">
  <link rel="manifest" href="<?php echo esc_url(home_url('/manifest.json?v=3')); ?>">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Compra Captación">
  <meta name="application-name" content="Compra Captación">
  <meta name="theme-color" content="#0052ec">
  <meta name="msapplication-TileColor" content="#0052ec">
  <meta name="msapplication-TileImage" content="<?php echo esc_url(home_url('/assets/media/icon-512.png?v=3')); ?>">

  <!-- Configuración Global Compra Captación -->
  <script>
    window.CAPTACION_CONFIG = {
      basePath: <?php echo wp_json_encode(wp_parse_url(home_url("/"), PHP_URL_PATH) ?: "/"); ?>,
      stripePaymentLink: <?php echo wp_json_encode($captacion_stripe_link); ?>,
      membershipLinks: <?php echo wp_json_encode($captacion_membership_links); ?>,
      mailchimp: <?php echo wp_json_encode($captacion_mailchimp_config); ?>,
      sessionImage: 'https://compracaptacion.com/wp-content/uploads/2026/08/Vera_asi-funciona_865-x-591.png',
      xmlProxyUrl: <?php echo wp_json_encode($captacion_theme_uri . '/xml-proxy.php?url={url}'); ?>,
      contactEmail: <?php echo wp_json_encode($captacion_contact_email); ?>,
      mediaDefaults: {
        piso: <?php echo wp_json_encode($captacion_media['property_defaults']['piso']); ?>,
        casa_chalet: <?php echo wp_json_encode($captacion_media['property_defaults']['casa_chalet']); ?>,
        comercial: <?php echo wp_json_encode($captacion_media['property_defaults']['comercial']); ?>,
        nave: <?php echo wp_json_encode($captacion_media['property_defaults']['nave']); ?>,
        oficina: <?php echo wp_json_encode($captacion_media['property_defaults']['oficina']); ?>,
        edificio: <?php echo wp_json_encode($captacion_media['property_defaults']['edificio']); ?>,
        terreno: <?php echo wp_json_encode($captacion_media['property_defaults']['terreno']); ?>
      },
      api: {
        restUrl: <?php echo wp_json_encode(rtrim(rest_url(), '/')); ?>,
        nonce: <?php echo wp_json_encode($captacion_rest_nonce); ?>,
        currentUserId: <?php echo wp_json_encode(get_current_user_id()); ?>,
        endpoints: {
          importXmlUrl: '/api/xml_feeds.php?action=import_url',
          uploadXmlFile: '/api/xml_feeds.php?action=upload_file',
          uploadImportFile: '/api/xml_feeds.php?action=upload_file',
          importTemplate: '/api/xml_feeds.php?action=template',
          listXmlFeeds: '/api/xml_feeds.php?action=list',
          xmlFeed: '/api/xml_feeds.php?action=feed&id=',
          importBatch: '/api/xml_feeds.php?action=batch&id=',
          syncXmlFeed: '/api/xml_feeds.php?action=sync',
          feedPending: '/api/xml_feeds.php?action=pending',
          feedPublishAll: '/api/xml_feeds.php?action=publish_all',
          exportUserXml: '/api/xml_feeds.php?action=export',
          deleteMyData: '/api/records.php?action=delete_all',
          deleteMyListings: '/api/records.php?action=delete_all',
          deleteMyXmlFeeds: '/api/xml_feeds.php?action=delete_all',
          resetMarketplace: '/api/records.php?action=reset',
          listingImportPreview: '/api/import_preview.php',
          profileLogo: '/api/auth.php?action=update_profile'
        }
      }
    };
  </script>

<?php wp_head(); ?>
</head>
<body <?php body_class('bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100 antialiased min-h-screen flex flex-col transition-colors duration-200'); ?>>
<?php wp_body_open(); ?><?php wp_body_open(); ?>

  <!-- SISTEMA DE NOTIFICACIONES FLOTANTES (Toast System) -->
  <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-3 max-w-sm pointer-events-none"></div>

  <!-- CABECERA / MENÚ DE NAVEGACIÓN PRINCIPAL -->
  <header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 shadow-sm transition-colors duration-200">
    <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 h-20 flex items-center justify-between">

      <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="flex items-center gap-3 group min-w-0">
        <img src="<?php echo esc_url($captacion_media['logo']); ?>" alt="<?php echo esc_attr($captacion_brand_name); ?>" width="360" height="120" decoding="async" fetchpriority="high" class="brand-logo-full group-hover:scale-[1.01] transition-transform">
      </a>

      <!-- Botón menú móvil -->
      <button id="menu-btn" type="button" onclick="toggleMenu()" aria-controls="mobile-nav" aria-expanded="false" class="lg:hidden flex items-center gap-2 px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 font-bold transition-all duration-200 hover:bg-slate-100 dark:hover:bg-slate-800 active:scale-95">
        <span id="menu-icon-text" class="text-sm">☰</span> Menú
      </button>

      <!-- Enlaces de Navegación multipágina -->
      <nav id="nav-menu" class="hidden lg:flex items-center gap-6 xl:gap-8 text-sm xl:text-[15px] font-bold text-slate-700 dark:text-slate-200">
        <a href="<?php echo esc_url(home_url('/inicio')); ?>" class="nav-link py-2 border-b-2 border-transparent transition-all hover:text-blue dark:hover:text-blue-neon">Inicio</a>
        
        <!-- Oportunidades Dropdown -->
        <div class="relative group py-2">
          <button type="button" class="nav-link inline-flex items-center gap-1.5 py-2 border-b-2 border-transparent transition-all hover:text-blue dark:hover:text-blue-neon focus:outline-none" aria-expanded="false">
            <span>Oportunidades</span>
            <span class="text-[10px] transition-transform duration-200 group-hover:rotate-180">▾</span>
          </button>
          <div class="absolute left-0 top-full pt-2 w-80 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 transform group-hover:translate-y-0 translate-y-1">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200/80 dark:border-slate-700 p-2 space-y-1">
              <a href="<?php echo esc_url(home_url('/propiedades')); ?>" class="flex items-start gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/60 transition-colors group/item">
                <span class="text-xl p-2 rounded-lg bg-blue/10 dark:bg-blue/20 text-blue">🏢</span>
                <div>
                  <strong class="block text-xs font-bold text-slate-900 dark:text-white group-hover/item:text-blue transition-colors">Propiedades compartidas</strong>
                  <span class="block text-[11px] text-slate-500 dark:text-slate-400 font-normal leading-snug">Inmuebles en exclusiva por profesionales inmobiliarios</span>
                </div>
              </a>
              <a href="<?php echo esc_url(home_url('/demandas')); ?>" class="flex items-start gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/60 transition-colors group/item">
                <span class="text-xl p-2 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-500">🎯</span>
                <div>
                  <strong class="block text-xs font-bold text-slate-900 dark:text-white group-hover/item:text-emerald-500 transition-colors">Demandas compartidas</strong>
                  <span class="block text-[11px] text-slate-500 dark:text-slate-400 font-normal leading-snug">Búsquedas activas de compradores solventes</span>
                </div>
              </a>
            </div>
          </div>
        </div>

        <a href="<?php echo esc_url(home_url('/como-funciona')); ?>" class="nav-link py-2 border-b-2 border-transparent transition-all hover:text-blue dark:hover:text-blue-neon">Cómo funciona</a>
        <a href="<?php echo esc_url(home_url('/precios')); ?>" class="nav-link py-2 border-b-2 border-transparent transition-all hover:text-blue dark:hover:text-blue-neon">Precios</a>
        <a href="<?php echo esc_url(home_url('/recursos')); ?>" class="nav-link py-2 border-b-2 border-transparent transition-all hover:text-blue dark:hover:text-blue-neon">Recursos y Contratos</a>
        
        <button id="theme-toggle-desktop" type="button" onclick="toggleTheme()" class="theme-toggle-button ml-1" aria-label="Cambiar apariencia" aria-pressed="false" title="Cambiar tema">
          <span id="theme-toggle-desktop-icon" class="theme-toggle-icon" aria-hidden="true">☀</span>
        </button>
        <button id="pwa-install-header-btn" type="button" onclick="triggerPWAInstall()" class="hidden ml-1 items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-blue/10 dark:bg-blue/20 text-blue dark:text-blue-neon text-xs font-bold hover:bg-blue hover:text-white transition-all shadow-sm" title="Instalar aplicación en tu dispositivo">
          <span aria-hidden="true">📲</span><span>Instalar App</span>
        </button>
        
        <!-- Estado No Logueado (Desktop) -->
        <div id="header-auth-unauthenticated" class="inline-flex items-center gap-2.5">
          <button type="button" onclick="openProfessionalSubscriptionModal('header')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue text-white text-xs font-bold tracking-wide hover:bg-blue-dark transition-all shadow-sm hover:scale-105">
            <span>Crear cuenta gratis</span>
          </button>
          <button type="button" onclick="openProfessionalAccess('login')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold tracking-wide transition-all shadow-sm hover:scale-105" title="Iniciar sesión en tu cuenta profesional">
            <span aria-hidden="true">👤</span><span>Inicia sesión</span>
          </button>
        </div>

        <!-- Estado Logueado (Desktop): Acceso directo al Panel Ejecutivo -->
        <div id="header-auth-authenticated" class="hidden items-center gap-2.5">
          <button type="button" onclick="switchPrivateDashboardPanel('offers', true); navigateTo('/area-privada');" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-blue text-white text-xs font-bold hover:bg-blue-dark transition-all shadow-sm">
            <span>+ Publicar</span>
          </button>
          <button type="button" onclick="switchPrivateDashboardPanel('overview', true); navigateTo('/area-privada');" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold tracking-wide transition-all shadow-md hover:scale-105 border border-emerald-500/50" title="Acceder directamente al Panel Ejecutivo">
            <span aria-hidden="true">👤</span><span>Acceso a Sesión</span>
          </button>
        </div>
      </nav>
    </div>
  </header>

  <!-- DRAWER / PANEL MÓVIL Y TABLET OFF-CANVAS -->
  <div id="mobile-nav-backdrop" onclick="toggleMenu(true)" class="fixed inset-0 z-[120] bg-slate-950/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"></div>

  <div id="mobile-nav" class="fixed top-0 right-0 bottom-0 w-[320px] max-w-[85vw] z-[125] bg-white dark:bg-[#091524] border-l border-slate-200 dark:border-slate-800 shadow-2xl translate-x-full transition-transform duration-300 ease-out flex flex-col justify-between p-6 lg:hidden overflow-y-auto">
    
    <!-- Cabecera interior del menú -->
    <div class="space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
        <img src="<?php echo esc_url($captacion_media['logo']); ?>" alt="Compra Captación" width="160" height="48" class="h-8 w-auto">
        <div class="flex items-center gap-2">
          <button id="theme-toggle-mobile" type="button" onclick="toggleTheme()" class="theme-toggle-button w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-amber border border-slate-200 dark:border-slate-700" aria-label="Cambiar apariencia" aria-pressed="false" title="Cambiar tema">
            <span id="theme-toggle-mobile-icon" class="theme-toggle-icon text-sm" aria-hidden="true">☀</span>
          </button>
          <button type="button" onclick="toggleMenu(true)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 text-lg font-bold transition-colors" aria-label="Cerrar menú">
            ✕
          </button>
        </div>
      </div>

      <!-- Lista limpia de navegación -->
      <nav class="space-y-1">
        <a href="<?php echo esc_url(home_url('/inicio')); ?>" onclick="toggleMenu(true)" class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-blue/10 hover:text-blue dark:hover:bg-slate-800 transition-colors">
          <span class="text-base" aria-hidden="true">🏠</span>
          <span>Inicio</span>
        </a>

        <!-- Grupo Oportunidades -->
        <div class="pt-2 pb-1">
          <span class="px-3.5 text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Oportunidades</span>
          <div class="mt-1 space-y-1">
            <a href="<?php echo esc_url(home_url('/propiedades')); ?>" onclick="toggleMenu(true)" class="nav-link flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-blue/10 hover:text-blue dark:hover:bg-slate-800 transition-colors">
              <div class="flex items-center gap-3">
                <span class="text-base" aria-hidden="true">🏢</span>
                <span>Propiedades</span>
              </div>
              <span class="px-2 py-0.5 rounded-full bg-blue/10 text-blue text-[10px] font-black">Exclusivas</span>
            </a>
            <a href="<?php echo esc_url(home_url('/demandas')); ?>" onclick="toggleMenu(true)" class="nav-link flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:bg-slate-800 transition-colors">
              <div class="flex items-center gap-3">
                <span class="text-base" aria-hidden="true">🎯</span>
                <span>Demandas activas</span>
              </div>
              <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 text-[10px] font-black">Compradores</span>
            </a>
          </div>
        </div>

        <a href="<?php echo esc_url(home_url('/como-funciona')); ?>" onclick="toggleMenu(true)" class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-blue/10 hover:text-blue dark:hover:bg-slate-800 transition-colors">
          <span class="text-base" aria-hidden="true">⚡</span>
          <span>Cómo funciona</span>
        </a>
        <a href="<?php echo esc_url(home_url('/precios')); ?>" onclick="toggleMenu(true)" class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-blue/10 hover:text-blue dark:hover:bg-slate-800 transition-colors">
          <span class="text-base" aria-hidden="true">💎</span>
          <span>Precios y créditos</span>
        </a>
        <a href="<?php echo esc_url(home_url('/recursos')); ?>" onclick="toggleMenu(true)" class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-blue/10 hover:text-blue dark:hover:bg-slate-800 transition-colors">
          <span class="text-base" aria-hidden="true">📚</span>
          <span>Biblioteca de Contratos y Recursos</span>
        </a>
        <a href="<?php echo esc_url(home_url('/contacto')); ?>" onclick="toggleMenu(true)" class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-700 dark:text-slate-200 font-bold text-sm hover:bg-blue/10 hover:text-blue dark:hover:bg-slate-800 transition-colors">
          <span class="text-base">📞</span>
          <span>Contacto y soporte</span>
        </a>
      </nav>
    </div>

    <!-- Botones de Acción al pie del menú Móvil -->
    <div id="drawer-auth-unauthenticated" class="pt-6 border-t border-slate-100 dark:border-slate-800 space-y-2.5">
      <button id="pwa-install-drawer-btn" type="button" onclick="toggleMenu(true); triggerPWAInstall();" class="w-full py-3 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 border border-emerald-500/20">
        <span aria-hidden="true">📲</span> <span>Instalar App en el móvil</span>
      </button>
      <button type="button" onclick="toggleMenu(true); openProfessionalSubscriptionModal('mobile-drawer')" class="w-full py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md flex items-center justify-center gap-2">
        <span>Crear cuenta gratis</span>
      </button>
      <button type="button" onclick="toggleMenu(true); openProfessionalAccess('login')" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 shadow-sm">
        <span aria-hidden="true">👤</span> <span>Inicia sesión</span>
      </button>
    </div>

    <div id="drawer-auth-authenticated" class="hidden pt-6 border-t border-slate-100 dark:border-slate-800 space-y-2.5">
      <button type="button" onclick="toggleMenu(true); switchPrivateDashboardPanel('overview', true); navigateTo('/area-privada');" class="w-full py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md flex items-center justify-center gap-2">
        <span aria-hidden="true">👤</span> <span>Acceso a Sesión</span>
      </button>
      <button type="button" onclick="toggleMenu(true); logoutDemo();" class="w-full py-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-red-600 font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2">
        <span>Cerrar sesión</span>
      </button>
    </div>

  </div>

  <!-- CONTENEDOR MULTIPÁGINA PRINCIPAL -->
  <script>
    (function(){
      try {
        var path = window.location.pathname.replace(/^\/+|\/+$/g, '');
        var hash = String(window.location.hash || '');
        if (hash.startsWith('#/')) { path = hash.substring(2); }
        if (path.startsWith('marketplace/') && path !== 'marketplace') { path = path.substring(12); }
        var routeMap = {
          '': 'page-inicio',
          'inicio': 'page-inicio',
          'marketplace': 'page-marketplace',
          'propiedades': 'page-marketplace',
          'demandas': 'page-buscar-captaciones',
          'buscar-captaciones': 'page-buscar-captaciones',
          'publicar': 'page-publicar',
          'publicar-propiedad': 'page-publicar',
          'publicar-demanda': 'page-publicar',
          'ofrecer-captacion': 'page-publicar',
          'como-funciona': 'page-como-funciona',
          'precios': 'page-planes',
          'planes': 'page-planes',
          'recursos': 'page-recursos',
          'contacto': 'page-contacto',
          'coincidencias-ventas': 'page-coincidencias-ventas',
          'aviso-legal': 'page-aviso-legal',
          'privacidad': 'page-privacidad',
          'cookies': 'page-cookies',
          'normas-publicacion': 'page-normas-publicacion',
          'condiciones-de-contratacion': 'page-condiciones-de-contratacion',
          'politica-reembolsos': 'page-politica-reembolsos',
          'datos-ciegos': 'page-datos-ciegos',
          'canal-de-denuncias': 'page-canal-de-denuncias',
          'area-privada': 'page-area-privada'
        };
        var firstSegment = path.split('/')[0];
        if (path.indexOf('area-privada') !== -1) firstSegment = 'area-privada';
        var targetId = routeMap[path] || routeMap[firstSegment] || 'page-inicio';
        if (targetId && targetId !== 'page-inicio') {
          document.addEventListener('DOMContentLoaded', function(){
            document.querySelectorAll('.page-section').forEach(function(s){ s.classList.add('hidden'); });
            var target = document.getElementById(targetId);
            if (target) target.classList.remove('hidden');
          });
        }
      } catch(e){}
    })();
  </script>
    <!-- PÁGINA 1: INICIO (EMBUDO DE CONVERSIÓN OPTIMIZADO) -->
    <div id="page-inicio" class="page-section <?php echo $captacion_active_page_id === 'page-inicio' ? '' : 'hidden'; ?>">
      <!-- HERO PRINCIPAL (ESTILO QUANTEXA & DRIBBBLE MODERN ENTERPRISE) -->
      <section class="relative overflow-hidden bg-gradient-to-b from-slate-50 via-blue-50/40 to-slate-100 dark:from-[#050c17] dark:via-[#091729] dark:to-[#0c1e33] py-12 lg:py-16 text-slate-800 dark:text-white transition-colors">
        <!-- Glow radial backgrounds -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[400px] bg-blue/10 dark:bg-blue/15 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute top-1/3 right-10 w-[450px] h-[350px] bg-emerald-500/10 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 relative z-10">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 xl:gap-14 items-center">
            
            <!-- COLUMNA IZQUIERDA: COPYWRITING, CREDIBILIDAD & CTAS (7 COLS EN DESKTOP PARA MÁXIMO IMPACTO) -->
            <div class="lg:col-span-7 space-y-6 text-left">
              <!-- Pill Badge -->
              <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue/10 border border-blue/30 text-blue dark:text-blue-neon text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                <span>Red de Colaboración B2B España</span>
                <span class="px-2 py-0.5 rounded-full bg-blue text-white text-[10px] font-black ml-1">Reparto 50/50</span>
              </div>

              <!-- Main Title Potente -->
              <h1 class="text-3xl sm:text-4xl lg:text-[42px] xl:text-[48px] font-black text-navy dark:text-white leading-[1.15] tracking-tight font-display">
                Multiplica tus ventas inmobiliarias <span class="bg-gradient-to-r from-blue via-indigo-500 to-emerald-500 dark:from-blue-neon dark:via-cyan-300 dark:to-emerald-400 bg-clip-text text-transparent">colaborando entre profesionales</span> con total confianza
              </h1>

              <!-- Subtitle -->
              <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed font-normal">
                Conecta de forma segura con agentes colegiados e inmobiliarias en toda España. Comparte captaciones, cruza con demandas solventes y formaliza acuerdos de honorarios al 50% con trazabilidad digital y datos ciegos.
              </p>

              <!-- CTA Actions -->
              <div class="pt-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5">
                <button type="button" onclick="openProfessionalSubscriptionModal('hero')" class="inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl bg-blue hover:bg-blue-dark text-white font-bold text-sm uppercase tracking-wider transition-all shadow-xl shadow-blue/25 hover:scale-[1.02] active:scale-95 group">
                  <span>Empezar gratis con 3 créditos (30 días)</span>
                  <span class="group-hover:translate-x-1 transition-transform">→</span>
                </button>
                <button type="button" onclick="openOpportunityChoiceModal()" class="inline-flex items-center justify-center gap-2.5 px-7 py-4 rounded-2xl bg-white hover:bg-slate-100 dark:bg-slate-900/90 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:border-blue/50 text-navy dark:text-white font-bold text-sm transition-all shadow-sm hover:scale-[1.02] active:scale-95">
                  <span>Explorar oportunidades</span>
                </button>
              </div>

              <!-- Micro-trust proof points & Métricas en Vivo -->
              <div class="pt-2 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs sm:text-sm text-slate-600 dark:text-slate-300 border-t border-slate-200/80 dark:border-slate-800/80 font-medium">
                <div class="flex items-center gap-2">
                  <span class="text-emerald-500 font-black">✓</span>
                  <span>3 Créditos de bienvenida (30 días)</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-emerald-500 font-black">✓</span>
                  <span>Sin permanencia ni tarjeta</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-emerald-500 font-black" aria-hidden="true">✓</span>
                  <span>Acuerdos conforme al Art. 1255 Código Civil</span>
                </div>
              </div>

              <!-- Tira Discreta, Funcional y Equilibrada de Ejemplo 50/50 -->
              <div class="pt-1">
                <div class="p-2.5 sm:py-2 sm:px-3.5 rounded-xl bg-slate-50/90 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-2.5 text-xs">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-6 h-6 rounded-md bg-blue/10 dark:bg-blue/20 text-blue dark:text-blue-neon flex items-center justify-center text-xs font-bold shrink-0" aria-hidden="true">🏢</span>
                    <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 truncate">
                      Cruce real: <strong>Chalet en Godella</strong> (Valencia)
                    </span>
                    <span class="px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold shrink-0">Match 98%</span>
                  </div>

                  <div class="flex items-center gap-2 sm:gap-3 text-[11px] shrink-0 font-medium">
                    <span class="text-slate-500 dark:text-slate-400">Precio: <strong class="text-navy dark:text-white font-bold">895.000 €</strong></span>
                    <span class="text-slate-300 dark:text-slate-700">·</span>
                    <span class="text-slate-500 dark:text-slate-400 hidden sm:inline">Honorarios 3%: <strong class="text-amber-600 dark:text-amber font-bold">26.850 €</strong></span>
                    <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">·</span>
                    <span class="px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300/80 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-300 font-bold">
                      Tus honorarios 50%: <strong class="font-black">13.425 €</strong>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- COLUMNA DERECHA: VÍDEO COMPACTO Y ELEVADO (5 COLS) -->
            <div class="lg:col-span-5 relative flex items-center">
              <div class="w-full p-1.5 sm:p-2 rounded-3xl bg-gradient-to-b from-blue/20 via-slate-200/70 to-white dark:from-blue/30 dark:via-slate-800/50 dark:to-slate-900/90 border border-slate-200 dark:border-slate-700/60 shadow-xl dark:shadow-2xl backdrop-blur-xl">
                <div class="relative rounded-2xl overflow-hidden border border-slate-200/80 dark:border-slate-700/60 bg-slate-100 dark:bg-slate-900 group/video shadow-inner">
                  <div id="home-explainer-video-slot" class="aspect-video w-full relative bg-slate-900 flex items-center justify-center">
                    <?php if ($captacion_has_explainer_video) : ?>
                      <video id="hero-explainer-video" class="h-full w-full object-cover" muted loop playsinline preload="none" poster="<?php echo esc_url($captacion_media['video_poster']); ?>" onerror="this.style.display='none';const fb=document.getElementById('hero-video-fallback-img');if(fb)fb.classList.remove('hidden');">
                        <source src="<?php echo esc_url($captacion_media['video_webm']); ?>" type="video/webm">
                        <source src="<?php echo esc_url($captacion_media['video_mp4']); ?>" type="video/mp4">
                      </video>
                      <img id="hero-video-fallback-img" class="h-full w-full object-cover hidden" src="<?php echo esc_url($captacion_media_url('media/Vera_macth.png')); ?>" alt="Cruce de operaciones Compra Captación" loading="lazy" decoding="async" />
                    <?php else : ?>
                      <img class="h-full w-full object-cover" src="<?php echo esc_url($captacion_media_url('media/Vera_macth.png')); ?>" alt="Compra Captación">
                    <?php endif; ?>

                    <!-- Botón claro y accesible para audio del vídeo -->
                    <button id="hero-video-audio-btn" type="button" onclick="toggleHeroVideoAudio()" class="absolute top-3 right-3 z-20 px-3 py-1.5 rounded-xl bg-slate-950/80 hover:bg-slate-950 text-white backdrop-blur-md border border-white/20 shadow-md flex items-center gap-1.5 transition-all hover:scale-105 active:scale-95" title="Activar sonido del vídeo de presentación" aria-label="Activar sonido del vídeo de presentación">
                      <span id="hero-video-audio-icon" class="text-xs" aria-hidden="true">🔇</span>
                      <span id="hero-video-audio-label" class="text-[11px] font-bold">Activar audio</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 4 PILARES DESTACADOS -->
          <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 hover:border-blue/40 shadow-sm backdrop-blur-md transition-all text-left">
              <div class="w-9 h-9 rounded-xl bg-blue/10 text-blue dark:bg-blue/20 dark:text-blue-neon flex items-center justify-center font-bold text-base mb-3">🛡️</div>
              <h4 class="text-base font-bold text-navy dark:text-white">Privacidad y Datos Ciegos</h4>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Dirección exacta y datos del propietario protegidos bajo candado digital para evitar puenteos.</p>
            </div>
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 hover:border-blue/40 shadow-sm backdrop-blur-md transition-all text-left">
              <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 flex items-center justify-center font-bold text-base mb-3">⚖️</div>
              <h4 class="text-base font-bold text-navy dark:text-white">Reparto 50/50 Homologado</h4>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Condiciones transparentes con plantillas contractuales homologadas para cobrar tus honorarios.</p>
            </div>
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 hover:border-blue/40 shadow-sm backdrop-blur-md transition-all text-left">
              <div class="w-9 h-9 rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400 flex items-center justify-center font-bold text-base mb-3">🤖</div>
              <h4 class="text-base font-bold text-navy dark:text-white">Asistente IA Vera</h4>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Cruce inmediato de propiedades y demandas activas con asistencia inteligente 24/7.</p>
            </div>
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 hover:border-blue/40 shadow-sm backdrop-blur-md transition-all text-left">
              <div class="w-9 h-9 rounded-xl bg-amber/10 text-amber-600 dark:bg-amber/20 dark:text-amber flex items-center justify-center font-bold text-base mb-3">🎁</div>
              <h4 class="text-sm sm:text-base font-bold text-navy dark:text-white">3 Créditos de Bienvenida</h4>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Válidos durante 30 días para desbloquear tus primeros contactos comerciales sin tarjeta bancaria.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- [NUEVO BLOQUE DE EMBUDO]: IDENTIFICACIÓN DE PUNTOS DE DOLOR -->
      <section class="py-14 sm:py-16 bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 space-y-10">
          <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">El dilema del sector</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">¿Te ocurre esto en tu agencia cada mes?</h2>
            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">Las dos situaciones donde las inmobiliarias pierden dinero por no disponer de una red de colaboración segura:</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full">
            <div class="p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 flex flex-col justify-between">
              <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center text-2xl font-black">🏠</div>
                <h3 class="text-xl font-bold text-navy dark:text-white">Tienes la vivienda en exclusiva, pero te falta el comprador</h3>
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                  Has captado un inmueble con gran potencial, pero pasan las semanas y en tu base de datos no hay clientes que encajen. La exclusiva corre peligro de vencerse y el propietario pierde la confianza.
                </p>
              </div>
              <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-xs sm:text-sm font-semibold text-rose-600 dark:text-rose-400">
                Solución: Conéctate con un agente que ya tiene al comprador listo y repartid los honorarios al 50%.
              </div>
            </div>

            <div class="p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 flex flex-col justify-between">
              <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber flex items-center justify-center text-2xl font-black">🎯</div>
                <h3 class="text-xl font-bold text-navy dark:text-white">Tienes al comprador con hipoteca aprobada, pero no la vivienda</h3>
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                  Un cliente solvente entra a tu oficina buscando una vivienda muy concreta en una zona específica. No la tienes en stock y, tras unos días de búsqueda infructuosa, el comprador se va a otra agencia.
                </p>
              </div>
              <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-xs sm:text-sm font-semibold text-amber-600 dark:text-amber-400">
                Solución: Localiza la captación en nuestra red en 1 minuto y cierra la operación sin perder el cliente.
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CALCULADORA INTERACTIVA DE REPARTO DE HONORARIOS 50/50 & MAPA DE COLABORACIÓN ESPAÑA -->
      <section id="honorarios" class="py-14 sm:py-16 bg-slate-50 dark:bg-[#07111e] border-y border-slate-200 dark:border-slate-800 relative text-slate-800 dark:text-white transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 space-y-8">
          
          <div class="text-center max-w-3xl mx-auto space-y-2">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
              <span aria-hidden="true">⚖️</span>
              <span>Calculadora de Honorarios 50/50 & Mapa de Colaboración España</span>
            </div>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-navy dark:text-white tracking-tight">
              ¿Cuánto ganas en una operación compartida?
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              Simula tu beneficio al 50% y consulta los honorarios medios reales en cada Comunidad Autónoma (Estudio InmoAdvisor 2026).
            </p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start text-left">
            
            <!-- COLUMNA IZQUIERDA (7 cols): Simulador Dinámico de Honorarios 50/50 -->
            <div class="lg:col-span-7 space-y-6">
              <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#081526] border border-slate-200 dark:border-slate-800 shadow-xl dark:shadow-2xl space-y-6">
                <!-- Selector de Rol Tipo Switch Tabs -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800/80">
                  <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Selecciona tu rol en la venta:</span>
                  <div class="inline-flex p-1 rounded-2xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800" role="tablist">
                    <button type="button" id="calc-role-captador" onclick="setCalculatorRole('captador')" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5">
                      <span aria-hidden="true">🏢</span> Tengo la captación
                    </button>
                    <button type="button" id="calc-role-colaborador" onclick="setCalculatorRole('colaborador')" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-navy dark:hover:text-white text-xs font-bold transition-all flex items-center gap-1.5">
                      <span aria-hidden="true">🎯</span> Tengo al comprador
                    </button>
                  </div>
                </div>

                <!-- 2. Valor de Venta + Slider + Presets -->
                <div class="space-y-3">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <label for="calc-price-slider" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                      Precio de venta del inmueble:
                    </label>
                    <div class="flex items-baseline gap-2">
                      <span id="calc-price-display" class="text-2xl sm:text-3xl font-extrabold text-navy dark:text-white tracking-tight">210.000 €</span>
                    </div>
                  </div>

                  <!-- Slider Principal de Precio con Relleno Dinámico -->
                  <div class="relative py-1">
                    <input id="calc-price-slider" type="range" min="50000" max="1500000" step="10000" value="210000" oninput="updateFeeCalculator(this.value, null)" class="calc-range-slider w-full" />
                  </div>

                  <!-- Botones de Tickets Rápidos -->
                  <div class="flex flex-wrap items-center justify-between gap-1.5 pt-1">
                    <span class="text-[11px] text-slate-400 mr-1">Rápido:</span>
                    <button type="button" onclick="setCalculatorPreset(150000)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all">150.000 €</button>
                    <button type="button" onclick="setCalculatorPreset(210000)" class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-500/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold transition-all shadow-sm">★ 210.000 € (Media)</button>
                    <button type="button" onclick="setCalculatorPreset(350000)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all">350.000 €</button>
                    <button type="button" onclick="setCalculatorPreset(600000)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all">600.000 €</button>
                    <button type="button" onclick="setCalculatorPreset(1000000)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all">1.000.000 €</button>
                  </div>
                </div>

                <!-- 2.1 Porcentaje de Honorarios Pactados (1% a 20%) -->
                <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                      <label for="calc-commission-slider" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Honorarios pactados con el cliente:
                      </label>
                      <span class="block text-[11px] text-slate-400">Pactados con el propietario o comprador</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                      <span id="calc-comm-pct-display" class="text-2xl sm:text-3xl font-black text-blue dark:text-blue-neon tracking-tight">3%</span>
                    </div>
                  </div>

                  <!-- Slider Dinámico de Honorarios (1% a 20%) con Relleno Dinámico -->
                  <div class="relative py-1">
                    <input id="calc-commission-slider" type="range" min="1" max="20" step="0.5" value="3" oninput="updateFeeCalculator(null, this.value)" class="calc-range-slider w-full" />
                  </div>

                  <!-- Benchmarks de Honorarios Inmobiliarios -->
                  <div class="flex flex-wrap items-center justify-between gap-1.5 pt-1">
                    <span class="text-[11px] text-slate-400 mr-1">Rango habitual:</span>
                    <button type="button" onclick="setCalculatorCommission(3)" class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-500/30 text-xs font-bold transition-all">3% (Norte / MLS)</button>
                    <button type="button" onclick="setCalculatorCommission(4)" class="px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-500/30 text-xs font-bold transition-all">4% (Centro)</button>
                    <button type="button" onclick="setCalculatorCommission(5)" class="px-2.5 py-1 rounded-lg bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300 border border-orange-300 dark:border-orange-500/30 text-xs font-bold transition-all">5% (Este / Islas)</button>
                    <button type="button" onclick="setCalculatorCommission(10)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all">10% (Suelo)</button>
                    <button type="button" onclick="setCalculatorCommission(20)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all">20% (Máx)</button>
                  </div>
                </div>

                <!-- 3. Tarjeta de Resultado Protagonista -->
                <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-transparent border-2 border-emerald-500/30 dark:border-emerald-500/20 shadow-sm space-y-4">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                      <span id="calc-role-title" class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                        Tus Honorarios Netos (50% de la operación)
                      </span>
                      <span class="text-xs text-slate-500 dark:text-slate-400 block mt-0.5" id="calc-role-desc">
                        Como agencia con la captación en cartera
                      </span>
                    </div>
                    <div class="text-left sm:text-right">
                      <span id="calc-your-share" class="text-3xl sm:text-4xl font-black text-emerald-700 dark:text-emerald-300 tracking-tight">3.150 €</span>
                    </div>
                  </div>

                  <!-- Desglose Mínimo y Transparente -->
                  <div class="pt-3 border-t border-emerald-500/20 grid grid-cols-2 gap-4 text-xs">
                    <div>
                      <span class="text-slate-500 dark:text-slate-400">Honorarios totales (<span id="calc-comm-pct-label">3%</span>):</span>
                      <strong id="calc-total-commission" class="font-bold text-slate-800 dark:text-slate-200 block text-sm mt-0.5">6.300 €</strong>
                    </div>
                    <div>
                      <span class="text-slate-500 dark:text-slate-400">Honorarios agencia colaboradora (50%):</span>
                      <strong id="calc-partner-share" class="font-bold text-slate-800 dark:text-slate-200 block text-sm mt-0.5">3.150 €</strong>
                    </div>
                  </div>
                </div>

                <!-- 4. Pie de Calculadora y CTA -->
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4">
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Cobro directo de honorarios con trazabilidad documental.
                  </p>
                  <button type="button" onclick="openProfessionalSubscriptionModal('calculator')" class="w-full sm:w-auto px-7 py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md text-center hover:scale-105">
                    Empezar gratis con 3 créditos →
                  </button>
                </div>
              </div>
            </div>

            <!-- COLUMNA DERECHA (5 cols): Mapa Geográfico de España por CCAA & Estudio InmoAdvisor -->
            <div class="lg:col-span-5 space-y-4">
              <div class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-[#081526] border border-slate-200 dark:border-slate-800 shadow-xl dark:shadow-2xl space-y-5">
                
                <div class="space-y-1">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-blue bg-blue/10 px-2.5 py-0.5 rounded-full border border-blue/20">
                      Estudio InmoAdvisor 2026
                    </span>
                    <span class="text-[11px] text-slate-400">4 bloques por zona</span>
                  </div>
                  <h3 class="text-lg font-black text-navy dark:text-white tracking-tight">
                    Mapa de honorarios en España
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Pasa el ratón para ver el porcentaje o haz clic en cualquier zona para calibrar la calculadora.
                  </p>
                </div>

                <!-- Leyenda de Bloques de CCAA -->
                <div class="grid grid-cols-2 gap-2 text-[11px] font-bold">
                  <button type="button" onclick="selectRegionHonorarios('norte')" class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-left flex items-center gap-2 transition-all">
                    <span class="w-3 h-3 rounded-full bg-[#48a853] shrink-0"></span>
                    <div>
                      <strong class="block text-xs leading-none">Bloque 3%</strong>
                      <span class="text-[10px] opacity-80 font-normal">Norte / Galicia / Aragón</span>
                    </div>
                  </button>
                  <button type="button" onclick="selectRegionHonorarios('centro')" class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 text-left flex items-center gap-2 transition-all">
                    <span class="w-3 h-3 rounded-full bg-[#f59e0b] shrink-0"></span>
                    <div>
                      <strong class="block text-xs leading-none">Bloque 4%</strong>
                      <span class="text-[10px] opacity-80 font-normal">Madrid / Castillas</span>
                    </div>
                  </button>
                  <button type="button" onclick="selectRegionHonorarios('este')" class="p-2.5 rounded-xl bg-orange-500/10 border border-orange-500/30 hover:bg-orange-500/20 text-orange-700 dark:text-orange-400 text-left flex items-center gap-2 transition-all">
                    <span class="w-3 h-3 rounded-full bg-[#f97316] shrink-0"></span>
                    <div>
                      <strong class="block text-xs leading-none">Bloque 5%</strong>
                      <span class="text-[10px] opacity-80 font-normal">Cataluña / Islas</span>
                    </div>
                  </button>
                  <button type="button" onclick="selectRegionHonorarios('sur')" class="p-2.5 rounded-xl bg-blue/10 border border-blue/30 hover:bg-blue/20 text-blue dark:text-blue-neon text-left flex items-center gap-2 transition-all">
                    <span class="w-3 h-3 rounded-full bg-[#3b82f6] shrink-0"></span>
                    <div>
                      <strong class="block text-xs leading-none">Rango 3% - 6%</strong>
                      <span class="text-[10px] opacity-80 font-normal">Andalucía / Levante</span>
                    </div>
                  </button>
                </div>

                <!-- MAPA REALISTA E INTERACTIVO DE ESPAÑA CON PROVINCIAS Y TOOLTIPS -->
                <div class="relative p-4 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 text-center shadow-inner overflow-hidden">
<svg viewBox="0 0 580 410" class="w-full max-h-[290px] mx-auto filter drop-shadow-md select-none" style="overflow:visible;" aria-label="Mapa cartográfico oficial de honorarios en España">
  <!-- Inset Box Canarias -->
  <g id="map-canarias-box" class="cursor-pointer transition-all" onclick="selectProvinceHonorarios('canarias')">
    <rect x="12" y="305" width="130" height="95" rx="10" fill="#ffffff" fill-opacity="0.9" stroke="#94a3b8" stroke-dasharray="3,3" stroke-width="1.2" />
    <text x="77" y="322" fill="#475569" font-size="9.5" font-weight="900" text-anchor="middle" letter-spacing="0.5">CANARIAS (5%)</text>
  </g>

  <!-- Castilla y León -->
  <path id="map-geo-castilla_leon" d="M195.6,50.8L197.7,53.1L197.3,56.1L198.6,57.7L200.6,58.5L200.6,59.4L201.5,59.4L201.7,60.9L205.7,61.2L206.9,60.3L208.8,61.1L210.3,59.8L212.1,60.2L212.6,59.2L213.2,59.3L215.1,60.4L217.1,63.3L221.4,64L221.5,65.8L222.1,66.1L222,68.6L223,69.2L225.2,68.1L225.8,69.1L225.3,69.9L223.8,70.1L223.6,70.8L224.9,71.8L227,71.7L228,73.1L229,72.8L230.6,69.8L230.9,72.7L231.7,73.2L234.4,72.8L235,71.1L236.2,71.9L237.9,71.3L237.7,68L236.2,67.1L235.6,68.9L234.2,68.5L234.9,67.6L233.8,67.1L235.1,67.2L234.5,66L236,66.7L237.4,65.8L237.2,64.8L235.3,64.4L235.1,65.5L233.5,66.7L233.6,65.9L231.8,66.1L231.4,64.8L233,61.7L234.4,61.2L235.2,60L236.7,60.2L237.5,59L236.9,58L240.1,58.2L240.5,57.3L242.5,56.6L244.6,53.5L246.1,54.1L246.5,55L252.7,55.8L254.9,55.5L260.3,52.7L261.4,54L264.4,53.9L264.7,54.4L263.1,56.3L265,57.4L264,58.5L264.7,58.9L264.7,60.6L263.3,61L267.9,61.8L268.6,63.4L271,64.8L269.5,66.2L266.6,66.7L264.4,65.8L264.6,65L261.4,64.3L260.5,66.1L259.3,66.6L259.1,67.8L262.2,70L261.5,69.5L262.8,68.1L263.9,68.6L265.7,67.1L265.8,68.4L264,71.1L265.4,71.8L264.4,73.3L268.3,72.9L268.6,74.1L269.6,74L271,75.7L273,75.5L274.1,76.2L274.4,78L275.9,78.8L273.6,79.7L272.4,78.7L267,78.7L267.9,81.1L265,83.2L267.4,83.8L266.5,85.7L268.5,87.7L267.8,88.7L268,91.2L267,89.9L267.2,89.1L266.3,89L266.1,90.4L266.9,90.5L267.8,91.8L266,92L266.8,96.9L265.2,99.1L268.9,104.3L272.8,104.1L273.7,107.2L274.9,107.8L278.2,106.1L278,104.5L279.2,102.5L280.7,102.5L281.9,103.5L279.8,106.7L280.3,107.8L286.8,108.4L288.1,105.8L289.4,105L289.4,102.9L290.6,103.3L292.4,101.9L297.2,101.5L298.6,102.2L299.1,104.1L301.5,103.4L304.7,103.7L304.7,104.5L303.3,105.1L305.5,108.1L304.8,108.4L305.4,110.1L308.3,111L308.6,110.3L310.9,111.9L315.4,109.8L315.3,112.4L316.9,114.9L316.9,116L315.5,117.8L317.4,119.4L317.2,120.3L318.6,120.8L318.1,122L317.1,122.5L317.4,123.6L315.3,123.7L312.8,126.6L310,126.5L311,128.9L310.5,131.7L311.6,132.8L311.5,134.5L312.2,135.2L308.6,136.3L308,135.7L308.3,134.4L305.7,133.5L305.1,136.4L303.3,137.6L302.9,140.7L304,145.4L306.6,146L307.7,147.1L307.1,149.4L307.8,150.2L306.9,150.4L304.5,148.4L302.8,149.9L300.6,149.3L299.3,150.7L297,151.2L295.7,149.8L293.2,151.1L291.2,150.2L290.7,148.5L289.7,148.3L288.6,146.4L287,147.4L286.2,146.9L286.9,145.6L285.2,145.2L286.3,144.7L286.5,143.6L284.9,144L284.2,142.8L281.7,142.3L281.5,141.2L279.7,141.3L279.1,142.4L277.3,142.1L275.5,141.3L275.5,139.5L274.7,139L272.6,139.5L272.2,140.5L267.8,141.3L262.4,139.9L261.2,140.4L260.3,142L259.5,141.9L259.4,141.3L258.8,142L257,142.3L254.1,141.9L253.9,141.3L254.5,142.2L254.4,144L252.8,144L251.9,145.4L249.9,146.4L246.1,147L243.6,149.8L241.1,151.3L239.5,153.7L234.5,155.7L232.7,158.5L232.9,159.9L231.6,163.4L227.8,163.2L227,165.1L224.1,168.1L224,170.4L224.5,170.7L221.2,171.6L220.5,171.5L220.2,170.4L219.4,170.5L219.9,171.6L219.4,173.3L217.9,174.7L218.4,177.8L217.4,179.4L218.2,180.2L214.2,180.4L213.3,181.3L212.9,184.3L211,184.5L210.1,183.1L209.3,183.3L210,185.5L208.4,187.1L208.5,188.8L205,189.7L203.7,189.1L203.3,185.9L200.8,186.9L199.1,186.3L198.6,187.2L199,188L194.4,191L194.3,192.5L191.2,193.6L190.7,191.4L188.8,191.8L187.9,193L184.6,194.7L183.4,194.8L182.9,193.7L178.8,193.7L176.9,191.3L176.8,188.8L177.9,186.7L174.5,187.3L172.8,189.3L170.1,189.8L167.2,188.8L166.9,187.6L165.8,187.4L164.1,185.5L160.1,185.7L159.8,185.3L160.6,184.6L159.9,182.7L156.5,184L155.2,186.1L153.2,185.6L151.3,184.4L151.3,183.3L147.8,182.6L148.7,182.3L149.1,181.3L148.6,181.1L149.4,180.8L146.9,179.7L147.4,178.8L146.2,179.1L145.8,178.1L142.9,176.8L141.1,178.1L138.4,178.8L137.3,180.6L135.5,180.7L134.7,181.9L130.8,183L129.9,183.9L130,185.6L128.8,186.5L125.5,186.8L124.8,187.7L123.6,186.5L122.2,187.9L118.6,187.2L117.9,186.5L117.9,185.3L120.7,183.9L121.2,182.3L119.1,180.3L118.5,178.3L120.6,175.7L120.4,173.9L118.7,173.2L120.7,168.9L119.2,164.9L119.8,162.7L119.4,160.8L120.4,160.6L120.1,159L118.5,158.2L118,156L115.6,153.6L115.3,152.3L120.1,152.1L122.2,149.1L121.7,147.6L124.3,145.6L126.4,142.5L128.3,142.7L128.6,142.2L129.4,142.9L131.4,141.2L132.4,141.7L133.1,141.3L132.9,140.4L134.7,139.9L135.5,138L137,137.5L136.6,136.3L139.4,136.2L138.8,135.1L140.2,134.2L139.9,133.3L140.7,132L141.6,131.7L141.9,130.4L144.5,127.6L140.1,123.6L134.4,122.6L132.3,123.9L130.4,122.6L130.3,120.8L129.5,119.9L131.6,114.1L129.4,113.5L130.4,111.1L128.8,109.7L127.5,111L124.4,111.3L122.4,110.8L121.7,108.9L120,108.5L119.6,110.7L114.8,110.8L114.2,109.6L113.1,109.4L112.9,108.7L114.1,106.9L113.5,105.7L112.1,105.8L111.2,104.8L112.4,104.3L112.9,102.6L114.7,101.7L114.8,100.2L116.4,99.5L117.3,98.1L118.7,97.7L120.9,98.3L120.3,97.2L121.7,96.4L122.7,94.5L123,91.7L118.8,89.6L119.9,88.5L119.5,87.4L120.1,86.6L119.4,85.5L118.1,85.7L115.2,84.2L114.1,84.4L113.4,85.5L109.5,84.8L110.2,83.8L109.8,82.7L111.2,81.3L110.6,80.7L110.7,79L112.4,78.6L110.8,76.2L112.1,74.8L113.6,74.8L114.1,73.4L115.1,74L117.8,71.9L118.8,70.2L117.6,67.9L119.1,67.5L119.8,68L118.5,66L121.3,66.6L121.5,67.4L123.5,65.8L124.8,65.6L132.7,65.7L134.9,64.8L134.9,63.7L133.3,63.5L133.1,62.4L135.2,62.5L135.9,62L135.8,61.2L136.4,61.2L136.3,60.2L137.8,59.2L139.1,60.5L140.7,60.3L143,61.6L143.5,59.8L144.9,59.8L146.6,61L148.2,60.5L148.7,58.5L151.2,59.4L153,58.9L153.7,61.1L155.3,62.3L159.7,63.7L161.3,63.4L162.7,60L164.1,59.3L168.9,60.9L171.9,61L172.5,59.4L176,59.7L176.9,57.6L180.3,58.4L182.9,58L185.6,56.6L187.4,57.2L188.4,53.5L191.5,53.9L192.8,52.1L195.6,50.8ZM218.1,169.4L220.7,169.4L220.8,168.9L219.4,168.1L218.1,169.4ZM277.1,71.8L280.9,71.5L284.3,72.9L286,72.6L286.5,73.6L287.7,73L287.1,74.8L285.7,76.1L286.9,77.4L289.1,76.7L289.3,78.4L284.7,78.3L283.7,77.2L279.4,77.5L278.8,76L275.5,74.1L276.8,71.5L277.1,71.8ZM227,70.2L227.4,71L226.9,71.6L226.4,70.5L227,70.2ZM272.8,80.8L272.4,80.4L272.9,79.9L273.3,80.5L272.8,80.8ZM224.2,70.8L223.7,70.3L224.4,70.3L224.2,70.8ZM270.6,80.3L270.5,79.8L271,80.1L270.6,80.3Z" fill="#48a853" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Castilla y León', '4%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('castilla_leon')" />
  <!-- Cataluña -->
  <path id="map-geo-cataluna" d="M416.8,68.6L422.3,70L425.1,71.8L426.5,71L427.4,71.9L431.1,71.8L434.6,75.5L437.1,74.6L438,75.3L442.1,75L441.9,75.8L443.4,76.5L443.3,77.4L444.3,78L445.5,80.4L444.8,81.4L445.6,82L444.3,83.6L445.8,83.3L446.5,84.7L444.6,85.9L445.4,86.3L446,88.1L449.8,88.3L450.2,87.1L450.9,87.5L451.6,86.7L453.9,86.7L454.4,85L455.8,85.7L456.6,85L456.8,85.5L460.9,86L463.2,87.6L465,87.3L466.1,90.7L468.1,92.2L470.8,91.6L473.3,88.9L475.6,89L477.6,88L484.5,90.1L486.4,92.5L488.5,92.9L490,91.7L494,92.5L493.2,90.4L493.9,90.4L494,89.6L497.2,88.6L498.1,89.3L500.6,87.1L503.7,87.2L504.8,86L505.6,86.8L508.5,86.4L510.3,88.6L513.8,88.2L512.9,90.1L513.5,90.3L513,90.7L513.3,91.6L514.7,92.8L515.9,92L516.8,93L517.5,92.6L519.6,93.5L518.2,94.2L518.7,95.1L517.8,95L518.3,96L516.9,96.6L517.3,97.3L516,96.6L515.5,97.5L513.3,96.1L511.3,98.7L511.8,102.3L513.9,103.1L515.3,105L514.6,107.6L516.1,110.5L515.5,110.8L515.6,111.8L513.9,114.3L512,115.5L510.9,115.3L508.8,118.2L506,119.3L504.3,121.2L499.8,122.4L498,124.3L489.4,127.4L482,131.5L478,132.9L474.2,138.5L474.8,136.5L472,140.6L464.9,141.8L456.9,144.3L448.3,145.7L443.6,147.8L439.8,147.9L436.7,149.9L437.9,148.8L436.2,149.2L434.7,151.2L432.9,150.3L427.5,151.9L422.6,156.1L417.8,161.7L416.2,162.5L417.3,164.1L419.4,164.2L417.4,163.2L418.8,163.1L421.4,165.7L423,165.7L422.8,167.5L418.5,169.9L415,173.5L413,173.8L412.1,172.7L414.2,172.9L414.6,172.1L416.5,172.1L418,170.1L412.3,170.7L412.4,171.3L411.9,171.1L409.8,173.2L408.9,175.2L406.3,174.3L405.7,173.3L406.1,172.7L404.5,171.6L399.6,170.4L399.2,169.1L400.1,167.7L397.9,167.1L397.5,165.7L396.4,166.1L394.9,165L395.5,165.4L395.5,164.8L397.7,164.2L397.8,163.3L399.6,161.8L398,158.9L400.2,155L398.8,153L399.6,152.8L397.2,151.7L397.7,151.2L397.3,150L396.5,149.9L396.5,148L397,147.5L398.4,147.8L398.6,146.9L400.1,146.6L401.3,143.5L403.6,143L403.8,141.1L402.3,138.8L403.3,137.8L401.2,135.9L402.6,133.2L402.1,131.8L404.2,131.5L406.2,129.1L405.5,126.4L402.4,126.5L402.7,125L401.5,123.7L401.5,122.8L403.8,120.1L404.2,120.5L404.3,119.3L407.1,119L407.6,117.3L408.9,116.3L410,116.4L410.5,114.9L411.5,115L412.5,114.1L412,113.5L412,112L412.5,111.9L410.8,111L411.7,109.7L412.4,110L413,108.7L413.8,108.5L413.9,109L416.2,103.3L416,101.2L416.7,100.7L416.1,100.3L416.3,99.5L418.7,93.4L417.8,92.7L419,92.1L417.1,91.3L418,90.5L415.8,85.8L417.8,84.9L417,84.1L418.2,83.6L417.8,83.1L418.9,80L416.2,79.2L416,77.8L414.6,76.5L415.3,76.4L415.4,74.8L413.9,73.4L414.9,72.4L414,72.1L415,71.2L414.6,69.5L416.8,68.6ZM467.4,85.8L466.9,86.5L468,87.6L465.8,87.1L466.6,85.4L467.4,85.8Z" fill="#f97316" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Cataluña', '5%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('cataluna')" />
  <!-- Ceuta -->
  <path id="map-geo-andalucia" d="M177.8,377L176.7,376.5L176.5,375.1L178.9,376.2L180.4,375.8L177.8,377Z" fill="#3b82f6" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Ceuta', '4%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('andalucia')" />
  <!-- Región de Murcia -->
  <path id="map-geo-murcia" d="M343.2,253.8L344.4,254.2L344.6,255.3L348.1,257.8L349.1,261.4L348.1,263.5L348.6,264.7L345.4,267.2L346.1,267.5L345.8,271.2L349.6,272.3L350.4,274.3L349.6,277.6L347.7,280.3L348.3,283L353.1,289.6L355.8,292.1L358.5,292.8L359,293.9L358.9,295.4L358,294L357.2,294.4L357.1,295.5L355.3,297.1L354.8,298.6L356.9,301L359.9,302L360.2,301.4L359.4,300.9L359.8,300.8L359.2,298.9L359.2,297.1L359.7,297.2L358.7,295.7L359.2,295.7L359.2,296.6L360.1,297.5L359.7,297.2L359.5,297.7L359.7,300.1L361.4,302.1L359.7,303.4L356.3,304.6L354,304.5L352.3,305.5L350.7,305.3L350.4,304.8L351.1,304.9L349.5,303.4L349.8,304.2L348.4,304.7L346.2,304.2L344.9,304.8L344.1,305.5L344.8,306.3L342.6,306L342.3,305.1L340.7,304.5L339.4,304.5L339.1,305.3L336.3,305.1L333.3,307.5L331,308.4L330,311.1L328.7,310.7L324.4,313.2L320.1,310.2L317.1,309.9L317.3,310.6L315.8,309.7L309.3,300.4L309.9,298L309.4,296L310.7,291.9L309.9,291.4L308.9,291.9L305.8,291.5L304.9,290.5L302.9,291L300,289.1L297.9,286.1L296.2,285.1L296.7,283.4L299.4,281.3L301.3,277.4L305.2,275.9L307.8,273L310.4,274L313.4,273.3L318.2,270.8L317.8,270.2L318.7,270.4L318.7,269.8L319.8,269.7L322,270.4L321.8,271.6L322.7,272.7L325.9,272.8L327.1,272.1L330.3,269.9L330.2,265.4L329.5,263.1L329.9,261.8L331.3,261L331.5,258L332.5,256.8L334.7,255.6L335.6,256.8L339.3,254.1L341.9,253.4L343.2,253.8ZM358.2,299.1L358.3,299.6L358.2,299.1Z" fill="#3b82f6" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Región de Murcia', '3% a 4%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('murcia')" />
  <!-- La Rioja -->
  <path id="map-geo-la_rioja" d="M270.4,78.6L272.4,78.7L273.6,79.7L276.1,78.9L276.5,80.2L277.4,80L277.4,81L276.6,81.4L277.3,82.7L279,81.4L279.6,79.5L280.5,79.5L282.9,80.8L281.9,84.4L283.1,84L284.2,86L285.8,85L286.2,86.4L287.3,85.5L288.8,85.9L288.5,85.4L289.6,84.3L289.4,85.6L293.3,85.6L296.1,87.4L296.7,86.6L297.9,87.2L298.4,86.7L298.9,87.7L302.8,89.4L305.3,88.9L305.2,89.8L306.6,91.3L306.2,92.3L307,92.5L309.1,91.2L310.9,92L311.1,92.8L312.9,93.2L313.1,94.4L314.8,94.9L314.8,96.6L315.6,96.3L317.5,97.9L320.5,98.4L321.4,99.4L322.3,99.4L321.5,100.3L322.4,101.2L320.8,102.1L320,101.3L318.4,101.9L316.3,101.4L313.3,105.5L313.6,106.8L315.7,107.8L315.9,108.8L315,109.2L315.4,109.7L310.9,111.9L308.6,110.3L308.3,111L305.2,110L304.8,108.4L305.5,108.1L303.3,105.1L304.7,104.5L304.5,103.6L300.4,103.5L299.1,104.1L298.6,102.2L297.2,101.5L292.4,101.9L290.6,103.3L289.4,102.9L289.4,105L288.1,105.8L286.8,108.4L280.3,107.8L279.8,106.7L281.9,103.5L280.7,102.5L278.5,103.2L278.2,106.1L274.6,107.7L273.7,107.2L273,104.2L268.9,104.3L265.2,99.1L266.8,96.9L266,92L267.8,91.8L266.9,90.5L266.1,90.4L266.3,89L267.2,89.1L267,89.9L268,91.2L267.8,88.7L268.5,87.7L266.5,85.7L267.4,83.8L265,83.2L267.9,81.1L267,78.7L270.4,78.6ZM272.4,80.4L273.3,80.5L272.9,79.9L272.4,80.4ZM270.3,80.5L270.9,79.9L270.3,80.5Z" fill="#48a853" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'La Rioja', '3%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('la_rioja')" />
  <!-- Islas Baleares -->
  <path id="map-geo-baleares" d="M515.3,200.4L513,201.9L512.1,201.8L511.3,203L510.1,202.9L510.2,204.1L511.1,204.9L515,203.5L514.3,203.8L514.7,204.7L513.9,205.6L511.7,206L512.2,208.2L516.9,210.5L519.9,209.2L520.6,207.9L523.9,209.8L525.1,209.7L524.8,210.8L525.8,211.1L524.9,211.6L524.9,213.8L524.3,213.8L524.1,214.8L522.6,215L522.1,216.6L522.8,217.2L520,218.9L518.4,220.9L517.7,224.4L517.2,224L516.5,226.1L515.7,226.3L516.1,226.8L514.3,227.2L513.6,228.3L512.7,228.2L509.6,231L508.9,231L507.4,229L506.4,228.8L506.5,227.9L505.2,226.8L503.5,227.2L502.2,226.6L498.5,226.7L496.7,224.8L496.9,223.3L495.9,221.8L497,220.2L493.9,217.9L492.4,217.7L491.9,218.5L492.4,218.7L491.2,218.4L490.7,219.3L488.8,219.6L488,222.6L486.2,222.2L486.4,221L485.5,220.5L486.4,219.9L485.5,219.2L484,219.7L483.8,219L482.7,219.8L482.8,219.2L481.8,219.2L482.9,218.6L481.1,217.8L481.5,216L485.7,214L488.3,211.8L490.6,211.1L491.9,209.4L493.6,208.6L493.9,207.8L494.9,207.8L495,206.9L498.1,205.6L498.2,204.9L500.8,204.7L504.9,202.1L506.2,202.6L508,201.4L509.3,201.5L509.3,202.1L511.7,201.6L513.4,200.3L515.3,200.4ZM550.1,194.6L550.4,196.5L551.6,195.7L551.3,197.4L551.9,196.9L551.7,195.5L552.5,195.5L553.1,195.8L552.7,197L553.5,197.6L554,197.1L554.4,198.8L554.5,197.9L555,198.7L557,198.7L556.4,198.8L556.8,199.3L556.4,200.1L557.4,200.8L557.2,201.5L558.6,203.8L559.3,204L558.6,204.3L558,203.3L556.5,203.1L558.5,204.5L557.7,206.7L554.5,206.3L548.6,202.7L544.7,201.3L542.4,202L539.3,201.8L539.9,198.4L538.4,198.6L538.1,197.8L539.3,196.2L542.3,195.9L543.2,196.5L543.7,195.7L545.5,196.3L547.6,195.6L548.5,196.2L549.9,195.5L549.6,194.6L550.1,194.6ZM449,237.5L450,238.6L451.9,238.7L451.6,239.1L452.1,239.2L451.2,239.9L452.5,241.5L451.1,242L451,243.2L448.8,243.5L448.9,245.1L448.1,245L446.8,246.5L446,246.3L446.1,246.9L445.4,246.3L445.3,247L444.6,246.9L443.9,248.2L444.1,250.1L443.2,249.6L442.7,250.1L441.6,248.3L440.7,249L439.7,248.1L438.6,248L437.8,249L436.9,248.3L436.4,247L437.4,245.4L436.5,244.5L437.1,243.8L440.1,243.9L439.2,243L439.8,242.3L439.6,241.3L442.2,239.4L442.8,240L444.8,238.6L445.4,239.2L446.3,238.1L447.3,237.8L447.7,238.4L449,237.5ZM445.2,253.1L447.6,256.3L448.9,257L450.5,256.2L451.1,257.4L448.7,257.9L445.7,256.5L443.2,258.4L443,255L443.7,254.3L444.6,254.8L445.2,253.1ZM505,235.4L506,235.8L505.2,236.2L505.2,237.1L503.7,237L503.6,235.6L504.2,236.4L504.2,235.8L504.9,236.2L505,235.4ZM480.8,216.4L479.4,217.5L480.8,216.4ZM505.6,234.3L505.4,235L505.6,234.3ZM444.7,251.6L445.1,252.6L444.7,251.6ZM436.5,243.1L436.5,243.7L436.5,243.1ZM435.9,248.4L435.5,248.7L435.9,248.4ZM557.3,200L557.1,200.4L557.3,200ZM453.3,240.9L453.7,241.3L453.3,240.9ZM446.9,251.4L447,252L446.9,251.4Z" fill="#f97316" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Islas Baleares', '5% a 6%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('baleares')" />
  <!-- Canarias -->
  <path id="map-geo-canarias" d="M66.4,351.1L67.2,353.1L64.5,354.8L64.3,355.9L61.6,358.6L61.6,361.3L60.1,364.8L60.1,366.8L57.2,371.3L56.1,371.1L55,372L53.6,372L52.8,369L50.4,364.7L50.3,362.7L48.3,359.9L48.5,359.3L50.5,358.1L52.4,358.9L54.3,357.8L55.9,358.1L58,357.2L59.4,355.7L60.4,353.3L62.3,352.2L62.4,351.5L64.9,351.9L66.4,351.1ZM119.8,345.1L121,346.7L121.3,352.4L120.3,355L120.7,357.8L119.1,363.1L116.9,364.7L112,366.2L109.4,370.5L105.9,369.4L105.2,369.9L105.6,368.2L106.6,368.6L108.4,368L111.9,364.5L112.4,360.4L116.4,351L116.7,346.9L119.8,345.1ZM83.8,365.7L83.8,370L84.9,371.8L84.6,373.3L85.2,374.6L84.6,374.7L84.6,377.2L83.9,377.7L83.7,379L80.4,380.7L80.2,381.5L77.8,381.1L75.1,378L74,375.3L74.3,371.7L76.9,369.2L77,366.2L78,366.5L78.6,366L79.5,367L81.2,366.6L83,367.6L83.8,365.7ZM129.7,328.2L130.8,329.8L129.8,331.9L130.2,333.2L129.3,336.7L125.6,339.3L123.4,339.8L122.2,342.2L121.8,341.5L120.1,341.5L120,340.5L121.2,338.9L121.5,335.2L122.9,333.6L124.1,333.6L125.2,332L125.8,332.3L126.5,331.6L127.8,332.2L129,328.6L129.7,328.2ZM25.2,341.5L26.4,342.4L28.3,342.1L29.5,345.6L28.5,348L28.7,351.8L26.8,356L22.9,345.1L23.8,342.6L25.2,341.5ZM40.5,364.4L40.8,365L42,365L44.2,367.6L44.3,368.7L42.6,370.9L40.2,371.2L38.4,368.6L39.1,364.9L40.5,364.4ZM24.8,377.5L25.8,379.2L24.8,381.7L24.1,382.1L23.6,385L21.9,383.1L19.3,382.3L19.5,380.7L22.2,380.6L23.3,379.3L23.2,378.5L24.8,377.5ZM128.8,326L129.6,326.9L128.8,328.4L127.9,328.6L128.8,326ZM128.8,321.6L129.1,322.3L128.3,322.7L128.8,321.6ZM121.6,344.8L121.5,345.8L121,345.3L121.6,344.8ZM128.1,325.6L128.2,326.1L128.1,325.6Z" fill="#f97316" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Canarias', '5%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('canarias')" />
  <!-- Cantabria -->
  <path id="map-geo-cantabria" d="M247,38L248.7,38.2L250.6,39.9L253.3,40.4L253.3,41.4L251.9,41.2L250.9,42.1L252,41.8L252,43.1L252.5,41.6L253.5,42.7L257.5,42.4L257.6,43.2L261.6,43.6L261.9,44.5L264.2,45.5L264.3,47.6L262,48.7L261.4,47.6L259.3,48.4L257.2,47.9L256.9,49L252.6,51L253.8,55.8L246.5,55L246.1,54.1L244.6,53.5L242.5,56.6L240.5,57.3L240.1,58.2L236.9,58L237.5,59L236.8,60.1L235.2,60L234.4,61.2L232.7,62L231.2,65.2L231.9,66.1L233.6,66L233.5,66.7L236.1,64.1L237.5,65.3L236.1,66.7L234.5,66L235.1,67.2L233.8,67.1L234.9,67.6L234.2,68.5L235.6,68.9L236.2,67.1L237.4,67.7L238.1,70.8L236.3,71.9L235,71.1L234.4,72.8L231.7,73.2L230.9,72.7L230.7,69.8L228.7,73L227.6,73.1L226.7,71.6L224.9,71.8L223.5,70.5L224.6,69.5L225.3,69.9L225.9,68.7L224.9,68L222.4,69.1L221.4,64L217.1,63.3L215.1,60.4L213.2,59.3L212.6,59.2L212.1,60.2L210.3,59.8L208.8,61.1L206.9,60.3L205.7,61.2L202.3,61.2L201.5,60.7L201.5,59.4L200.6,59.4L200.6,58.5L198.6,57.7L197.3,56.1L197.9,54.6L197.4,53.7L201.9,53.1L202.5,50.9L202,50L205.9,49.5L206.9,47.9L208.9,48.5L209.2,49.4L210.2,49L210.2,46.3L209.6,45.3L210.7,43.6L215.5,43.8L217.4,43L217.9,43.7L219.8,43.8L223.9,43.2L227.6,41.5L231.7,41.4L233,39.9L239.3,39L240.2,40.1L237.7,40.9L238.1,41.5L237.5,41.6L238.6,42L237.8,42.6L238.1,43.2L239.6,42.3L239.1,41.8L239.7,41L240.9,40.9L239.6,40.6L241.3,40.5L247,38ZM226.5,70.2L226.7,71.4L227.3,71.3L227.4,70.4L226.5,70.2ZM224,70.2L224.5,70.7L224,70.2ZM260,49.7L260.4,49.9L259.6,51.1L259.8,52.4L259,52.7L258.3,49.9L260,49.7Z" fill="#48a853" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Cantabria', '3%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('cantabria')" />
  <!-- Andalucía -->
  <path id="map-geo-andalucia" d="M190,254.7L191.2,255.3L191.2,256L196.3,256.4L196.9,259.6L202.9,261.4L203.1,262.2L206.3,263.5L207.4,265.1L208.5,264.9L210.6,266.1L213,268.9L219.5,271.4L220.2,271.2L219.9,269.3L220.7,268.8L224.7,269.7L234.2,270.2L236.6,270L237.5,269.2L237.5,268.3L239.3,267.8L246.7,269L247.3,266.7L248.9,266.9L249.4,268.5L252.5,268.9L255.4,267.2L255.5,265.6L257.4,265.3L259.3,266.3L264.8,267.2L267.7,265.5L270.2,268.5L270.5,266.7L271.7,265.8L274,265.8L275,266.6L279,264.1L279.4,263.1L282.9,264.7L286.3,264L287.4,265L287.1,268.2L290.7,269L290.6,273.2L292.3,273.9L292.5,274.8L291.8,276.9L292.1,278.2L288,282.6L293,284.8L296.3,285.1L297.9,286.1L300,289.1L302.9,291L304.9,290.5L305.8,291.5L308.9,291.9L309.9,291.4L310.7,291.9L309.9,293.1L309.4,296L309.9,298L309.2,299.2L310.6,302.7L315.8,309.7L317.3,310.6L317.1,309.9L320.1,310.2L324.3,313.2L322.5,314.1L321.2,316.2L317.7,319.4L315.7,326.3L313.6,329.7L313.5,331.9L312.1,331.9L311,333.5L310,333.7L309.7,336L307.4,337.3L307.3,338.7L305.5,339.3L304.8,340.6L302.1,341.1L298.2,336.9L295.5,335.9L292.9,337.3L291,336.1L291.4,336.6L290.7,336.2L287.6,337L285.3,340.8L282.1,342.7L279.3,342.8L277.6,341.7L276.2,342.2L273.2,339.8L271.2,340.3L260.1,339.7L256.9,340.3L253.7,342.2L251.2,342.1L249.5,340.9L248.4,341.3L246.5,340.1L244.3,340L241.6,341.2L240.7,340.4L240,340.7L237.3,339.7L232.5,340.9L228.2,339.9L226.6,340.9L223.7,341.3L215.3,341L214.5,341.7L214.5,341.2L210.7,346.5L207.1,347.8L205.7,350.2L201.6,351.2L194.8,350.2L191.2,352.2L188.2,352.6L184.4,354.1L178.9,361.9L178.1,365.1L177,365L176.2,364L174.6,364.2L173.8,365.7L174,366.4L174.5,365.7L173.9,367.8L174.5,368.6L167.3,371.6L165.7,369.3L164.2,368.7L162.9,369.1L160.8,367.9L160,368.3L155.3,363.8L150.6,364L147.9,359.6L146.3,359.1L144.8,356L143.3,355L140.6,349.5L139.8,349.2L141,348.6L140.4,349.1L141.1,348.7L141.4,349.1L140.9,349.3L141.9,350.1L141.8,351.6L142.9,352L145.1,349.6L144.1,349.2L143.8,350.1L142.4,350.2L141.8,349.2L142.9,349.4L142.8,346.9L142.1,347.4L142.6,346.9L141.5,347.1L140.1,345.5L139.3,345.7L139.7,345.5L139,345.2L139.1,345.8L138.5,345.1L137.8,345.6L136.4,344.8L134.5,340.3L138.5,337.9L136.3,337.4L134.3,333.1L131.4,330.2L115.8,321.7L119.2,324.6L115.5,322.1L110.8,320.3L101.9,320.6L97.7,321.9L96.5,321.5L95.4,318.5L95.9,317.5L95.1,315.9L95.2,312.5L94.3,311.7L95,311.3L94.2,308.3L91.9,305.5L92.6,305.3L92.7,303.3L95,300.7L94.7,300.1L95.9,296.6L99.8,294.2L101.4,291.9L102.7,288.2L102.2,287.1L104.6,286.1L107.8,286L107.5,284.9L108.6,284.3L110.2,285.5L112.4,285.3L113,281.7L114.4,279.6L113.8,279L115.2,277.2L118.5,278.6L120.6,278.5L119.8,281L121.6,281.4L122.1,282.2L127.4,282L129.1,284L129,285.3L130.5,285L133.2,285.9L134.5,284.2L134.1,283.8L137.5,284.1L138.1,285.9L140,287.2L144,287.9L144.9,288.8L147.4,287L153.8,286.4L154.6,284.2L155.6,283.5L154.9,281.7L156.7,280.5L156.8,279.5L157.8,278.8L161.5,278.4L162.7,277.7L164,278.2L164.4,279.4L163.6,278.9L164,279.7L162.2,280.8L162.4,282.5L163.4,282.7L166.5,280.2L168.3,280.5L170.1,279.3L170.8,275.9L169.7,272.8L168.7,272L169.4,270.3L168.3,269.5L169,267.5L170.9,266.1L172.3,266.1L172.5,265.2L174.9,264L175.4,262.6L176.3,262.8L175.9,262.2L176.7,260.9L179.2,261.2L179.9,259.7L182.6,257.7L182.9,258.1L183.2,257.2L184.1,257.6L184.1,256.9L184.8,256.7L184.1,255L187.5,255.5L190,254.7ZM143.3,355.5L143.1,354.9L143.3,355.5Z" fill="#3b82f6" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Andalucía', '3% a 5%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('andalucia')" />
  <!-- Asturias -->
  <path id="map-geo-asturias" d="M158.2,31L158.7,32L160.5,32.4L160.2,33.2L161.6,34.7L164.7,35.3L163.8,35.5L164.6,36.7L167.1,35.8L171.9,36.5L175.1,36L176.1,37.2L179.8,36.9L182.9,39.8L186.5,39.3L189.9,40.4L194.3,40.4L203.3,43.1L210.4,43.5L210.7,44.2L209.8,44.8L209.6,45.9L210.2,46.3L210.3,48.9L209.2,49.4L208.9,48.5L206.9,47.9L205.9,49.5L202,50L202.5,51L201.4,53.4L197.7,53.5L196.5,51.3L194.9,50.9L192.8,52.1L191.5,53.9L188.4,53.5L187.4,57.2L185.6,56.6L182.9,58L180.3,58.4L176.9,57.6L176,59.7L172.5,59.4L171.9,61L168.9,60.9L164.1,59.3L162.7,60L161.3,63.4L159.7,63.7L155.3,62.3L153.7,61.1L153,58.9L151.2,59.4L148.7,58.5L148.2,60.5L146.6,61L144.9,59.8L143.5,59.8L143,61.6L140.7,60.3L139.1,60.5L137.8,59.2L136.3,60.2L136.4,61.2L135.8,61.2L135.9,62L135.2,62.5L133.1,62.4L133.3,63.5L134.9,63.7L134.9,64.8L130.6,66.1L126.8,65.5L123.5,65.8L121.5,67.4L121.6,66.7L119,65.9L119,63.9L117.6,62.5L116.1,62.4L114.2,60.3L114,62.4L112.6,61.1L113.2,60.1L113.9,60.4L113.8,59.3L115,57.9L115.9,58.6L119.3,56.2L119.6,55.3L118.9,54.2L117.6,53.6L117.7,53.1L117.1,54.7L114.2,55.7L113.3,54.2L113.7,52.4L112.5,52.3L112.2,51.6L111.7,52L110.1,50.6L109.7,49.3L110.2,47.7L108.6,47.8L107.4,46.5L107.5,44.6L105.3,43.6L105.9,43.1L105.7,41.9L108.5,42L109,40.7L110.6,39.7L111.6,35.8L113.6,35.9L115.5,35.1L123.5,36L127.3,35.2L130.9,36.5L133.3,36.2L133.4,35.2L135,36.1L137.9,36.2L141.9,34.9L142.3,34.1L144,35.2L144.5,34.8L144.6,35.6L146,35.3L147.5,36L151.3,34.4L152.1,35.1L153.7,34.8L155.3,33.5L155.3,32.8L157.2,32.4L158.2,31Z" fill="#48a853" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Asturias', '3%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('asturias')" />
  <!-- Comunidad Valenciana -->
  <path id="map-geo-valencia" d="M382.1,163.2L382.9,163.4L383.6,164.8L386,166L389.2,165.9L389.6,167.4L390.3,167.6L393,166L394.3,166.4L397.5,165.7L397.9,167.1L400.1,167.7L399.2,169.1L399.6,170.4L404.4,171.6L406.1,172.7L405.9,174.1L408.9,175.2L405.1,180.7L404.7,182.6L399.6,187.6L399,189.2L396.2,190.8L394.4,194.8L390.5,197L388.4,202.3L383.8,206.8L381.1,211L380.4,214.4L380.1,213.8L378.6,215.3L376.1,219.6L375.9,222.3L376.7,223.6L375.5,222.5L376.1,223.2L375.4,223.6L376.4,223.9L375.4,224L376.2,227.1L380.1,234.5L379.1,235L379.3,236.7L381.1,240.8L382.9,242.9L382.2,243L382.8,242.9L384.4,245.2L387.8,248.2L393.6,249.3L392.9,249.5L396.4,251.3L395.8,251.7L396.1,252.6L397.5,253L397.8,254.2L395.8,254.9L394.5,256.7L392.5,257L391.5,258.2L391.9,258.8L390.6,258.4L389.8,259.1L388,259L386.2,261L386.6,261.8L384.7,263.5L382.7,263L381.8,263.8L376.3,265.5L373.3,267.5L372.6,269L372.7,271L371.1,270.5L369.3,272L369.6,271.3L368.6,271.8L368.1,273.3L368.5,277.5L364.3,278.8L363.4,280.6L362.7,287L361.1,287.6L360.2,290.1L359,290.6L358.5,292.8L357.8,292.9L355.8,292.1L353.1,289.6L348.3,283L347.7,280.3L349.6,277.6L350.4,274.3L349.6,272.3L345.8,271.2L346.1,267.5L345.4,267.2L348.6,264.7L348.1,263.5L349.1,261.4L348.1,257.8L350.6,257.7L352.5,256L350.7,252.5L352,252.2L352.1,247.4L350.9,246.2L350.8,245.1L343.4,245.8L340.2,241.6L338.7,240.7L339.1,238L342,233.4L342.8,229.3L336.8,227.6L335.9,228L332.8,226L332.4,226.7L332.1,226.1L331.2,226.8L331.5,226.1L330.7,225.8L331.3,225.8L331.3,225.2L330.5,225.5L330.6,224.8L329.9,225L330.1,224.1L329.5,224.4L329.6,223.8L328.6,223.7L328.3,222.7L328.9,222.5L328.9,220.9L329.5,220.7L329.3,218.7L328.6,218.5L330.9,217.3L332.7,213.8L334.6,212.3L336.8,213.1L338.6,212.2L338.3,210.1L340.7,207L341.3,204.8L341.1,200.8L343.5,199.8L344.4,200.3L345,199.6L349.6,199.3L352.4,200.2L353,201.6L352.6,204.2L353.5,205.1L354.5,205.3L357.1,203.8L355.4,201.9L355.6,199.6L357.9,198.6L358.9,196.5L361.7,196.6L363.9,195.1L364.4,195.4L363.8,194L365.8,192.4L367.1,187.3L369.1,188.3L371.7,187.7L373.5,186.7L372.8,185.4L373.3,184.8L374.1,184.9L375.2,183.4L377.5,182.2L374.9,178.7L377.8,177.4L377.6,176.1L376.7,175.5L377,171.2L374,171.1L373.6,168.8L375.6,168.1L376.5,169L379.3,167.6L379.8,164.8L380.8,163.4L382.1,163.2ZM337.5,189.1L337.9,190.9L339.5,192L339.5,193.4L343.3,193.4L346.3,196.3L343.9,197.8L339.4,198.7L334.3,197.8L332.6,194.4L333.1,194L331.1,192.5L331.5,192.1L335.2,192.8L336.7,191.9L336.4,190.3L337.5,189.1ZM369.7,279L370.3,279.2L369.7,279ZM415.7,202.9L415.7,203.3L415.7,202.9Z" fill="#3b82f6" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Comunidad Valenciana', '3% a 5%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('valencia')" />
  <!-- Melilla -->
  <path id="map-geo-andalucia" d="M272.2,402.4L271.5,401.4L272.2,400.1L273.3,401.4L272.2,402.4Z" fill="#3b82f6" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Melilla', '4%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('andalucia')" />
  <!-- Navarra -->
  <path id="map-geo-navarra" d="M323.4,47.5L324.5,47.7L324.3,48.7L325.1,50.2L326.5,50.3L326.9,48.5L328,48.2L331.9,49.5L332.8,49.2L334,50.1L334.1,53.1L333.3,53.7L332.8,56L330.5,57.9L331.8,59.8L335.2,60.7L336,57L338.5,56.5L337,58.7L338.8,59.9L340.8,59.6L343.4,60.8L343.6,61.6L344.6,60.9L345.9,62L348.7,62.3L351.3,64.2L352.8,63.6L356.7,64.2L358.8,63.5L360,65.6L357.7,65.6L356.3,66.6L356.3,68.1L354.8,69.1L354.7,71.4L355.2,71.9L354.5,73L353.1,73.1L353.1,73.9L352.2,73.9L351.2,75.4L348.1,75.9L347.7,78.2L347.1,78.6L343.2,78.3L343,80.1L342.1,80.1L342.9,80.6L341.2,81.6L341.2,82.9L338.5,82.9L337.6,84.7L338.4,86L335.7,88.7L335,90.8L336.2,91.9L333.4,94.8L333.7,95.7L332.7,98.5L333.7,99.9L333.4,102.4L334.7,103.3L335.3,104.9L336.9,104.9L337.1,106.2L332.7,112.2L330.3,111.6L327.1,112.1L323.4,109.8L320.9,110.2L318.9,108.4L316.3,108.4L313.6,106.8L313.6,104.8L316.3,101.4L318.4,101.9L320,101.3L320.8,102.1L322.4,101.2L321.5,100.3L322.4,99.4L321.4,99.4L320.5,98.4L317.5,97.9L315.6,96.3L314.8,96.6L314.8,94.9L313.1,94.4L312.9,93.2L311.1,92.9L309.9,91.5L306.2,92.3L306.6,91.3L305.2,89.8L305.3,88.9L302.8,89.4L298.9,87.7L298.4,86.7L297.9,87.2L296.7,86.6L296.1,87.4L295.8,86.8L293.9,86.5L293,84.7L294.3,84.4L294.1,80.6L293.1,80.2L291.8,82.1L291.5,81.1L290.4,81.2L290,80.1L293.3,77.7L294.9,78.2L294.7,79L298.3,78L298.1,76.9L297.2,76.7L296.9,74.8L297.2,74.1L299.4,73.7L298.6,72L300.5,69.4L299.7,67L300.3,66.8L300.4,65.4L301.4,64.3L302.6,65L305.1,64.7L306,63.3L308.2,62.9L308.6,62L308.1,60.6L308.9,59.8L308.8,58.9L311.8,57.4L313.6,55.6L312.8,55L313.1,51.5L314.4,52.3L314.4,51.3L314.9,51.2L315.2,52.1L317.8,50.5L318.3,48.6L323.4,47.5ZM345.4,85.8L347,87.6L346.9,88.2L344.1,87.6L345.4,85.8ZM343.3,87.9L342.9,89.3L341.7,89.7L341.9,88.7L343.3,87.9Z" fill="#48a853" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Navarra', '3%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('navarra')" />
  <!-- Galicia -->
  <path id="map-geo-galicia" d="M85.7,25.1L86.4,25.6L84.9,27.5L85.6,27.8L86,27L87.4,26.7L87.7,28.1L88.6,28.3L88.3,30.1L88.9,30.9L88.8,30L89.8,29.5L89.5,28.6L90.4,28.7L90.6,27.8L91.9,27.4L92.2,28.3L94.1,28L94.4,28.4L93.7,28.8L94.4,28.6L94.8,29.5L95.3,29.1L98.5,30.5L100.9,34.2L102.9,35.1L101.8,35.6L102.4,36.4L103.2,35.3L111.2,36.2L110.6,39.7L109,40.7L108.5,42L105.7,41.9L105.9,43.1L105.3,43.6L107.5,44.6L107.4,46.5L108.6,47.8L110.2,47.7L109.7,49.3L110.1,50.6L111.7,52L112.2,51.6L112.5,52.3L113.7,52.4L113.3,54.2L114.2,55.7L117.1,54.7L117.7,53.1L119.6,55.3L117.6,57.7L115.9,58.6L115,57.9L113.8,59.3L113.9,60.4L113.2,60.1L112.6,61.1L114,62.4L114.2,60.3L116.1,62.4L117.9,62.8L119,63.9L118.8,65.3L119.5,65.9L118.5,66L119.8,68L119.1,67.5L117.6,67.9L118.8,70.2L117.8,71.9L115.1,74L114.1,73.4L113.6,74.8L112.1,74.8L110.8,76.2L112.4,78.6L110.7,79L110.6,80.7L111.2,81.3L109.8,82.7L110.2,83.8L109.5,84.8L113.4,85.5L114.1,84.4L115.2,84.2L118.1,85.7L119.5,85.6L120.1,86.6L119.5,87.4L119.9,88.5L118.8,89.6L123,91.7L122.7,94.5L121.7,96.4L120.3,97.2L120.9,98.3L118.7,97.7L117.3,98.1L116.4,99.5L114.8,100.2L114.7,101.7L112.9,102.6L112.4,104.3L111.2,104.8L112.1,105.8L113.5,105.7L114.1,106.9L112.3,110.5L109.6,110.5L109.1,109.4L107,108.6L105.6,109.1L105,110.9L105.7,111.9L104.8,113.7L100.1,115.4L98.1,115L95.7,117L94.7,114.4L92.7,114.2L92,115.4L88.3,116L89.4,113.6L86.7,113.6L84.9,112.4L79.2,113.6L79.2,114.4L78,115.1L77.4,114.6L77.3,111.5L76.7,111.7L76.2,113.7L73.9,114.1L72.6,116L69.4,117L66.6,116.5L66.4,113.7L65.2,114L64.5,112.2L66.7,109L69.6,107.5L69.8,106.6L69.3,104.9L68.4,104.4L65.9,105.1L65.2,101.1L60.4,103.5L60,104.3L52.5,104.6L51.1,105.8L48,106L46.9,108.1L43.8,109.8L43.3,111.2L38.7,114.3L38.1,111.1L38.5,108.1L37.6,103.1L39.5,102.3L40.5,102.9L40.8,101.7L39.6,101L40.7,101L40.9,99.6L42.4,98.9L42.5,98L45,97.1L47,95.1L49,95L48.5,94L49,92.3L48.3,92.1L47.7,92.6L48,93.2L47.1,93.7L47.1,94.8L45,95.5L44.3,95L41.8,96.9L41,96.3L38.7,96.7L39.3,94L40.9,95.4L40,92.6L41.9,93.2L43.3,92.6L44,90.9L47.4,88.5L46.2,88.8L45.7,88.1L42.5,90.4L41,89.8L40,90.5L38.4,89.1L38.3,87.2L37.1,86.9L36.5,87.4L36,86.9L36.1,86.1L38,86.1L38,85.4L38.7,85.1L39.4,86.4L38.8,87.6L39.7,87L40.8,87.4L40.3,85.9L41.3,85.1L40.5,84.5L41,83.8L40.4,84.3L40.1,82.5L40.8,82.4L40.2,82.2L41.7,81.5L42.1,80.6L42.6,80.9L42.4,79.9L43.9,78.3L44.5,76.5L43.6,78.2L41.5,78.9L40.5,78.4L41.1,77.4L39.8,76.5L40,77.7L39.5,77.5L39.1,78.4L40.1,79.1L39.2,79.3L39.3,80.3L38.1,80.1L38.7,79.1L37.6,78.5L36.1,80.2L36.8,81.2L34.1,82.1L33.8,83.8L32,84.4L32.5,83L32.1,82.2L30.1,81.6L32.1,78.8L32.3,76.1L34,74L35.5,73.7L36.6,71.8L37.5,71.8L37.4,70.6L38.4,70.5L38.6,69.9L38.1,70.6L37.3,70.1L37.3,71.5L36.4,70.9L35.1,72.3L34.7,71.7L34.4,72.3L33,72.2L32.9,71.3L31.1,71.9L31.8,72.7L30.1,74.1L27.7,71.4L29.2,70.7L29.4,69.6L28.2,69L27.7,67.9L28.7,66.4L27.3,66.1L26.8,64.6L26.1,64.2L26.2,65.9L24.5,64.7L24.5,65.4L23.3,65.8L23,67.5L22.6,67.1L22.8,66.2L21.9,65.6L23.5,61.9L22.7,61.4L21.9,59.4L23.3,59.7L23.5,57.8L24.9,57.9L25,56.7L26.2,57.8L27.1,57.1L27.2,54.9L26.1,56.3L25.2,54.9L26.3,54.5L26.8,53L27.3,53.4L28.5,52.7L30.6,53.5L32.1,52.8L33.2,51.1L33.8,51.9L34.4,51.3L36,51.4L35.5,49.7L34,49.2L35.4,48.3L36.5,48.8L37.4,48.2L37.1,47L39.6,47.1L39.9,45.8L42.1,47.8L45,48.4L49.2,46.9L52,47.2L53.1,46.5L53.6,45.1L55.1,45.2L55.8,44.1L57,44.7L57.3,43.8L58.3,44.9L57.2,45L58.2,45.3L57.8,46.1L59.1,45.9L59.8,44.4L59.1,43.7L61,43L64.9,46.7L64.3,43.9L66.2,42.3L63.9,41.9L62.9,42.4L60.6,41.2L63,40.2L65.2,40.5L66.4,39.5L67.1,37.8L65.2,39.7L63.3,39.7L63.3,38.9L62.6,40.1L59.6,40.5L60.5,38.7L60,38L61.4,36.9L60.4,35.5L62.8,35.9L65.5,33.7L65.7,32.9L67,33.2L69.4,30.7L70.1,31.8L71,31.6L70.1,31L70.6,29L71.8,28.5L73.1,28.9L75.2,27.1L76.1,27.1L76.9,25.9L78.3,25.7L78.7,26.3L78.1,27.5L79,28.7L78,28.1L78.3,28.8L77.4,29.9L78.2,30.5L76.9,30.5L79.1,30.7L78.6,28.9L79.6,28.7L79.5,29.4L80.4,29.4L79.8,28.7L80.5,27.9L83.9,27L85.7,25.1ZM38.5,81.7L39.3,82.1L38.7,84.1L38.3,83.8L38.8,82.4L37.9,82L38.5,81.7ZM36.7,89.8L35.9,91.9L35.8,90.7L36.7,89.8ZM37.1,96.7L37.8,97.8L37.1,98.5L37.1,96.7ZM33.2,85.9L33.7,86.6L33.3,86.9L33.2,85.9ZM37.3,98.8L38,99.4L37.1,99.2L37.3,98.8ZM39.9,85.1L39.5,86L39.9,85.1ZM40,45L39.8,45.4L40,45ZM42.4,79.6L41.9,79.8L42.4,79.6Z" fill="#48a853" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Galicia', '3%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('galicia')" />
  <!-- Aragón -->
  <path id="map-geo-aragon" d="M359.1,65.6L360,65.7L359.6,66L360.3,67.3L361.8,67.4L364.8,69.8L364.9,71.2L366.1,71L366.2,72.2L367.7,71.7L368.7,70L371.2,71.5L373,71.3L376.2,69L381.2,71.9L382.3,71.4L384.4,75L385.9,75.1L386.1,76.2L387.9,76.6L389.2,75.8L390.9,76L391.9,75.2L393.8,75.2L395.5,74.2L398.8,75.2L399,76.2L400.2,77.2L402.8,74.8L405.2,76.4L411.8,76.2L412.1,75.7L414.6,76.4L416,77.8L416.3,79.5L418.9,80L417.8,83.1L418.2,83.6L417,84.1L417.8,84.9L415.8,85.8L418,90.5L417.1,91.4L419,92.1L417.8,92.7L418.7,93.4L416.3,99.5L416.1,100.3L416.7,100.7L416,101.2L416.2,103.3L414.3,107L414.7,107.6L413.9,109L413.8,108.5L413,108.7L412.4,110L411.7,109.7L410.8,111L412.5,111.9L412,112L412.5,114.1L411.5,115L410.5,114.9L410,116.4L408.9,116.3L407.6,117.3L407.1,119L404.3,119.3L404.2,120.5L403.8,120.1L401.5,122.9L402.7,125L402.4,126.5L405.5,126.4L406.2,129.1L404.2,131.5L402.1,131.8L402.6,133.2L401.2,135.9L403.3,137.8L402.3,138.8L403.8,141.1L403.6,143L401.3,143.5L400.1,146.6L398.6,146.9L398.4,147.8L397,147.5L396.5,148L396.5,149.9L397.3,150L397.7,151.2L397.2,151.7L399.6,152.8L398.8,153L400.2,155L398,158.9L399.6,161.8L397.8,163.3L397.7,164.2L395.5,164.8L395.5,165.4L394.9,164.9L395.1,166.2L393,166L390.3,167.6L389.6,167.4L389.2,165.9L386,166L383.6,164.8L382.9,163.4L380.8,163.4L379.8,164.8L379.3,167.6L376.5,169L375.6,168.1L373.7,168.7L374,171.1L377,171.2L376.7,175.5L377.6,176.1L377.8,177.4L374.9,178.7L377.5,182.2L375.2,183.4L374.1,184.9L373.3,184.8L372.8,185.4L373.5,186.7L371.7,187.7L369.1,188.3L367.1,187.3L365.8,192.4L363.8,194L364.4,195.4L363.9,195.1L361.7,196.6L358.9,196.5L357.9,198.6L355.6,199.6L355.4,201.9L357.1,203.8L354.4,205.3L352.6,204.2L353,201.6L352.3,200.1L351.8,200.5L349.6,199.3L345,199.6L344.4,200.3L343.5,199.8L342.6,198.1L345.8,196.9L346.3,195.8L343.3,193.4L339.5,193.4L339.5,192L337.9,190.9L337.4,189.1L336.4,190.3L336.5,192L335.1,192.8L332.1,192.3L331.5,192.1L331.8,189.7L330.4,190.3L329,189.4L327.8,190L321.9,184.7L320.7,186.1L320.4,185L321.6,184.2L318.5,181L317.4,180.8L317.5,180.2L318.2,180.1L319.6,177.9L321.8,176.8L321.5,174.4L322.4,172.1L325.5,173.4L327.6,171.9L326.9,171.1L328,167.8L327,165.4L327.9,164.5L327.8,162.2L324.6,159.1L325.2,157L324.8,156.2L317.5,149.6L312.4,147.4L311.3,145.9L307.7,147.1L306.6,146L304,145.4L302.9,140.7L303.3,137.6L305.1,136.4L305.7,133.5L308.3,134.4L308,135.7L308.6,136.3L312.2,135.2L311.5,134.5L311.6,132.8L310.5,131.7L311,128.9L310,126.5L312.8,126.6L315.3,123.7L317.4,123.6L317.1,122.5L318.1,122L318.6,120.8L317.2,120.3L317.4,119.4L315.5,117.8L316.9,116L316.9,114.9L315.3,112.4L315,109.2L315.9,108.8L315.7,107.8L316.3,108.4L318.9,108.4L320.9,110.2L323.4,109.8L327.1,112.1L333,111.9L337.1,106.2L336.9,104.9L335.3,104.9L334.7,103.3L333.4,102.4L333.7,99.9L332.7,98.5L333.7,95.7L333.4,94.8L336.2,91.9L335,90.8L335.7,88.7L338.4,86L337.8,84.1L338.7,82.6L340.2,83.2L341.2,82.9L341.2,81.6L342.9,80.6L342.1,80.1L343,80.1L343.2,78.3L347.1,78.6L347.7,78.2L348.1,75.9L351.2,75.4L352.2,73.9L353.1,73.9L353.1,73.1L354.5,73L355.2,71.9L354.7,71.4L354.8,69.1L356.3,68.1L356.3,66.6L359.1,65.6ZM344.2,87.2L344.9,87.9L347.1,87.9L345.5,85.8L344.2,87.2ZM343.3,88.3L341.9,88.7L341.7,89.7L342.9,89.4L343.3,88.3Z" fill="#48a853" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Aragón', '3%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('aragon')" />
  <!-- Comunidad de Madrid -->
  <path id="map-geo-madrid" d="M249.1,146.4L251,149.4L253.5,150L253,151.8L254.7,153.7L253.8,154.3L252.8,157.9L252.1,157.9L252.9,159.3L251.6,159.8L251.5,161.7L250.1,163.2L252.2,163.6L252.8,164.5L252.5,165.7L253.1,166L251.8,167.7L254.3,168.6L254.8,167.6L256.7,170L257.8,169.2L256.9,170.6L257.6,171.2L257.3,171.9L259.1,173.1L258.6,174.5L260.4,174.2L262.4,175.6L262.4,178.8L264.1,178.9L265.1,180.4L264.2,182.1L264.7,182.9L263.1,184.8L262.6,187.5L263.4,188.1L264.2,186.8L265.8,185.8L266.3,186.1L267.6,191.5L266.8,191.3L266.5,192.2L268.1,193.5L267.9,194.5L265.6,195.8L263.9,195.6L263.3,194.4L262.4,195.8L259.4,196.4L257.2,194.9L255.5,195.2L255.5,196.2L254.5,197L250.3,196.4L249.8,197.6L247,197.9L245.1,199L245.3,200L240.9,201.2L238.6,203.6L236,202.8L235.8,201.7L237.2,201.1L237.5,201.6L238.4,200.6L239.4,201L240.2,200L239.9,200.7L240.5,200.7L240.4,199.9L241.6,200.1L240.9,199.4L241.9,199.2L242.7,197.7L245.2,196.7L246.3,193.7L244.4,193L244,192.1L243.3,192.6L241.8,191.9L240.9,192.6L239.9,192.3L238.6,190.6L237.4,191.3L235.9,190.1L233.6,189.4L232.8,190L232.7,189L231.2,189.2L229.8,188L229.9,187.4L228,186.7L226.7,187.7L225.1,187.6L225.2,186.7L223.3,185.3L222.8,186.5L221.1,186.3L220.2,188L218.8,188.6L218.7,188.1L217.1,188L216.8,184.8L215.9,184.3L215.7,185.6L213.7,187L213.6,188L210.7,188.8L210,189.6L208.2,189.3L208.8,188.2L208.4,187L210,185.5L209.3,183.3L210.1,183.1L211,184.5L212.9,184.3L213.3,181.3L214.2,180.4L218.2,180.2L217.4,179.4L218.4,177.8L217.9,174.7L219.4,173.3L219.9,171.6L219.4,170.5L220.2,170.4L220.5,171.5L221.2,171.6L224.5,170.7L224,170.4L224.1,168.1L227,165.1L227.8,163.2L231.6,163.4L232.9,159.9L232.7,158.5L234.5,155.7L239.5,153.7L241.1,151.3L243.6,149.8L246.1,147L249.1,146.4ZM255.6,170.1L255.2,170.3L255.6,170.1ZM220.8,169.3L218.2,169.6L218.1,169.1L219.6,168.1L220.8,169.3Z" fill="#f59e0b" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Comunidad de Madrid', '4% (3%-5%)')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('madrid')" />
  <!-- Extremadura -->
  <path id="map-geo-extremadura" d="M142.9,176.8L145.8,178.1L146.2,179.1L147.4,178.8L146.9,179.7L149.4,180.8L148.6,181.1L149.1,181.3L148.7,182.3L147.8,182.6L151.3,183.3L151.7,184.9L154.4,186L155.2,186.1L156.5,184L159.9,182.7L160.6,184.6L159.8,185.3L160.1,185.7L164.1,185.5L165.8,187.4L166.9,187.6L167.2,188.8L169.8,189.7L172.8,189.3L174.5,187.3L177.9,186.8L176.8,188.8L176.9,191.2L178.2,193.4L176.9,194.1L177,199.3L175.4,204L178.6,203.3L180.2,204.6L179.1,206.3L179.3,209.2L181.4,209.5L183.3,207.5L184.8,207.4L185.2,209.2L184.5,209.6L186,211.2L185,214.4L183.3,216.4L193.4,225.4L196.5,226.5L200.3,225.2L203.7,222.9L204.4,224L203.1,226.7L203,228.3L200.8,228.6L202.9,233.5L205.3,235.3L198.9,233.9L197.2,236L196.4,238.3L198.2,239.9L198,240.7L196.1,241.3L192.9,240.1L194.6,244.6L198,245.4L197.4,247.8L194.8,247.6L193.6,249.1L191.8,254.1L187.5,255.5L184.1,255L184.8,256.7L184.1,256.9L184.1,257.6L183.2,257.2L182.9,258.1L182.6,257.7L179.9,259.7L179.2,261.2L176.7,260.9L175.9,262.2L176.3,262.8L175.4,262.6L174.9,264L172.5,265.2L172.3,266.1L170.9,266.1L169,267.5L168.3,269.5L169.4,270.3L168.7,272L169.7,272.8L170.8,275.9L170.1,279.3L168.3,280.5L166.5,280.2L163.4,282.7L162.4,282.5L162.2,280.8L164,279.7L163.6,278.9L164.4,279.4L164,278.2L162.7,277.7L161.5,278.4L157.8,278.8L156.8,279.5L156.7,280.5L154.9,281.7L155.6,283.5L154.6,284.2L153.8,286.4L147.4,287L144.9,288.8L144,287.9L140,287.2L138.1,285.9L137.5,284.1L134.1,283.8L134.5,284.2L133.2,285.9L130.5,285L129,285.3L129.1,284L127.4,282L123.1,282.3L121.6,281.9L121.6,281.4L120.1,281.4L120.6,278.5L118.2,278.5L115,277.3L115,276.7L113.6,277.5L112.1,277.4L111.4,278.3L109.9,278.2L109.2,278.9L106.9,275.8L106.6,274.3L105.8,274.1L101.5,268L99.3,267.4L100.9,265.5L99.8,265.5L100.6,262.6L102.9,259.2L101.9,258.5L102.5,254.7L107.5,250.8L109,250.6L109.8,249.1L111,248.6L110.5,246.8L114.5,241.6L112.7,238.3L111,237.5L108.4,238.3L106.9,237.9L107.2,235.2L103,233.5L102.8,231.6L103.4,230.5L100.4,227.9L99.8,225.9L100.8,222.5L97.5,221L97.1,219.4L95.6,219.2L94.4,217.5L93,216.9L91,213.5L99.4,214.4L102.7,213.3L106.6,213.9L111.9,213.1L113.5,208.6L113.1,207L116.3,204.2L116.1,202.1L117,201.2L117,199.4L117.9,198L114.8,193.5L112.8,193.5L112.1,192.9L111.9,190.6L111.4,190.3L112.1,188.4L113.9,187.9L114.5,187L117.9,186.5L119.1,187.5L122.2,187.9L123.6,186.5L124.8,187.7L125.5,186.8L128.8,186.5L130,185.6L129.9,183.9L130.8,183L134.7,181.9L135.5,180.7L137.3,180.6L138.4,178.8L141.1,178.1L142.9,176.8Z" fill="#48a853" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Extremadura', '3% a 4%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('extremadura')" />
  <!-- Castilla-La Mancha -->
  <path id="map-geo-castilla_mancha" d="M274.2,139L275.4,139.4L275.5,141.3L277.3,142.1L279.1,142.4L279.7,141.3L281.5,141.2L281.7,142.3L284.2,142.8L284.9,144L286.5,143.6L286.3,144.7L285.2,145.2L286.9,145.6L286.2,146.9L287,147.4L288.6,146.4L289.7,148.3L290.7,148.5L291.2,150.2L293.2,151.1L295.7,149.8L297,151.2L299.3,150.7L300.6,149.3L302.8,149.9L304.5,148.4L307.5,150.6L307.8,150.2L307.1,149.4L307.8,146.8L311.3,145.9L312.4,147.4L317.5,149.6L325,156.5L324.6,159.1L327.8,162.2L327.9,164.5L327,165.4L328,167.8L326.9,171.1L327.6,171.9L325.5,173.4L322.4,172.1L321.5,174.4L321.8,176.8L319.6,177.9L317.4,180.8L318.5,181L321.6,184.2L320.4,185L320.8,186.2L321.9,184.7L327.8,190L329,189.4L330.4,190.3L331.8,189.7L331.2,192.7L333.1,194L332.6,194.4L334.3,197.8L339.3,198.7L342.6,198.1L343.5,199.8L340.9,201L341.3,204.8L340.7,207L338.3,210.1L338.8,211.9L337,213.1L334.6,212.3L332.7,213.8L330.9,217.3L328.6,218.5L329.3,218.7L329.5,220.7L328.9,220.9L328.9,222.5L328.3,222.7L328.6,223.7L329.6,223.8L329.2,224.3L330.1,224.1L329.9,225.1L331.3,225.2L331.3,225.8L330.7,225.8L331.5,226.1L331.2,226.8L332.1,226.1L332.4,226.7L332.8,226L335.9,228L336.8,227.6L342.6,229L342,233.4L339.1,238L338.6,240.5L340.2,241.6L343.4,245.8L350.8,245.1L350.9,246.2L352.1,247.4L352,252.2L350.7,252.5L352.5,256L350.6,257.7L347.8,257.7L344.6,255.3L344.4,254.2L341.9,253.4L339.3,254.1L335.6,256.8L334.7,255.6L333.3,256.2L331.5,258L331.3,261L329.9,261.8L329.5,263.1L330.2,265.4L330.3,269.9L325.9,272.8L322.7,272.7L321.8,271.6L322,270.4L321.5,270L320.9,270.5L319.8,269.7L318.8,269.8L318.7,270.4L317.8,270.2L318.2,270.8L313.4,273.3L310.4,274L307.8,273L305.2,275.9L301.3,277.4L299.4,281.3L296.8,283.3L296.2,285.1L295.6,285.3L288,282.6L292.1,278.2L291.8,276.9L292.5,274.8L292.3,273.9L290.6,273.2L290.7,269L287.1,268.2L287.4,265L286.2,264L282.9,264.7L279.4,263.1L279,264.1L275,266.6L274,265.8L271.7,265.8L270.5,266.7L270.2,268.5L267.7,265.5L264.8,267.2L259.3,266.3L257.4,265.3L255.5,265.6L255.4,267.2L252.5,268.9L249.4,268.5L248.9,266.9L247.3,266.7L246.7,269L239.3,267.8L237.5,268.3L237.5,269.2L236.6,270L234.2,270.2L224.7,269.7L220.7,268.8L219.9,269.3L220.2,271.2L219.5,271.4L213,268.9L210.6,266.1L208.5,264.9L207.4,265.1L206.3,263.5L203.1,262.2L202.9,261.4L196.9,259.6L196.3,256.4L191.2,256L191.1,255.2L189.6,254.5L191.8,254.1L193.6,249.1L194.8,247.6L197.4,247.8L198.1,245.5L194.6,244.6L192.9,240.1L196.1,241.3L198,240.7L198.2,239.9L196.4,238.3L197.2,236L198.9,233.9L205.3,235.4L202.9,233.5L200.8,228.6L203,228.3L203.1,226.7L204.4,224L203.6,222.8L200.3,225.2L196.5,226.5L191.5,223.9L183.4,216.6L185.5,213.5L186,211.2L184.5,209.6L185.2,209.2L184.8,207.4L183.3,207.5L181.4,209.5L179.3,209.2L179.1,206.3L180.2,204.6L178.6,203.3L175.4,204L177,199.3L176.9,194.1L178.1,193.4L182.9,193.7L183.4,194.8L184.6,194.7L187.9,193L188.8,191.8L190.7,191.4L190.9,193.6L193.2,193.1L193.1,192.6L194.3,192.5L194.4,191L199,188L198.6,187.2L199.2,186.3L200.8,186.9L203.3,185.9L203.7,189.1L205,189.7L208,188.8L210,189.6L210.7,188.8L213.6,188L213.7,187L215.7,185.6L215.9,184.3L216.8,184.8L217.1,188L218.7,188.1L218.8,188.6L220.2,188L221.1,186.3L222.8,186.5L223.3,185.3L225.2,186.7L225.1,187.6L226.7,187.7L228,186.7L229.9,187.4L229.8,188L231.2,189.2L232.7,189L232.8,190L233.6,189.4L235.9,190.1L237.4,191.3L238.6,190.6L239.9,192.3L240.9,192.6L241.8,191.9L243.3,192.6L244,192.1L244.4,193L246.3,193.7L245.2,196.7L242.7,197.7L241.9,199.2L240.9,199.4L241.6,200.1L240.4,199.9L240.5,200.7L239.9,200.7L240.2,200L239.4,201L238.4,200.6L237.5,201.6L237.2,201.1L235.8,201.7L236,202.7L238.1,203.6L240.9,201.2L245.3,200L245.1,199L247,197.9L249.8,197.6L250.3,196.4L254.5,197L255.5,196.2L255.4,195.2L257.1,194.9L259.4,196.4L262.4,195.8L263.3,194.4L263.9,195.6L266.7,195.4L268.2,194.1L266.5,192.2L266.8,191.3L267.6,191.5L266.9,190.2L266.5,186.4L266,185.8L265.2,186.1L263.4,188.1L262.5,187L263.2,184.6L264.7,182.9L264.2,182.1L265.1,180.4L264.1,178.9L262.4,178.8L262.4,175.6L261.2,174.5L258.6,174.5L259.1,173.1L257.3,171.9L257.6,171.2L256.9,170.6L257.8,169.2L256.7,170L254.8,167.6L254.3,168.6L251.8,167.7L253.1,166L252.5,165.7L252.8,164.5L252.2,163.6L250.1,163.2L251.5,161.7L251.6,159.8L252.9,159.3L252.1,157.9L252.8,157.9L253.8,154.3L254.7,153.7L253,151.8L253.5,150L251,149.4L250.2,147.5L248.9,146.9L249,146.2L251.9,145.4L252.8,144L254.4,144L254.5,142.2L253.9,141.3L254.1,141.9L257,142.3L258.8,142L259.4,141.3L259.5,141.9L260.3,142L261.2,140.4L262.4,139.9L267.8,141.3L272.2,140.5L272.6,139.5L274.2,139ZM255.5,169.9L255.6,170.6L255.5,169.9Z" fill="#f59e0b" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'Castilla-La Mancha', '4%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('castilla_mancha')" />
  <!-- País Vasco -->
  <path id="map-geo-pais_vasco" d="M280.1,40.6L280.6,41.9L282.3,43L284.2,42.5L285.3,43.7L289.6,44.3L291.6,46.3L295.8,48L301.7,47.4L302.2,48.3L304.6,48.6L308.5,47L310.1,47.2L311.2,46.2L312.7,46.5L315,44.6L318,43.5L318,45.5L320.1,46.6L320.4,48.3L318.3,48.6L317.8,50.5L315.2,52.1L314.9,51.2L314.4,51.3L314.4,52.3L313.1,51.5L312.8,55L313.6,55.6L311.8,57.4L308.8,58.9L308.9,59.8L308.1,60.6L308.6,62L308.2,62.9L306,63.3L305.1,64.7L302.6,65L301.9,64.3L300.6,65.2L300.3,66.8L299.7,67L300.4,69.6L298.6,72L299.4,73.7L297.2,74.1L296.9,74.8L297.2,76.7L298.1,76.9L298.3,78L294.7,79L294.9,78.2L293.3,77.7L290.7,80L290,79.8L290.4,81.2L291.5,81.1L291.8,82.1L293.1,80.2L294.1,80.6L294.3,84.4L293,84.7L293.1,85.7L289.4,85.6L289.6,84.3L288.5,85.4L288.8,85.9L287.3,85.5L286.3,86.5L285.9,85L284.2,86L283,84.7L283.1,84L281.9,84.4L282.8,80.8L279.6,79.5L279,81.4L277.2,82.6L276.6,81.4L277.4,81L277.4,80L276.5,80.2L276.5,79.2L274.4,78L274.1,76.2L273,75.5L271,75.7L269.6,74L268.6,74.1L268.3,72.9L264.4,73.3L265.4,71.8L264,71.1L265.8,68.4L265.7,67.1L263.9,68.6L262.8,68.1L261.5,69.5L262.2,70L259.3,67.9L259.3,66.6L260.5,66.1L261.4,64.3L264.6,65L264.4,65.8L266.6,66.7L269.5,66.2L271,64.8L268.6,63.4L267.9,61.8L263.3,61L264.7,60.6L264.7,58.9L264,58.5L265,57.4L263.1,56.3L264.7,54.4L264.4,53.9L261.4,54L260.3,52.7L253.8,55.8L252.6,51.1L256.9,49L257.2,47.9L259.3,48.4L261.4,47.6L262,48.7L264.1,47.9L264.2,45.5L265.8,45.6L266.9,44.3L266.2,44.9L267.1,45.5L267.4,45L268.4,45.2L267.3,45.4L269,45.8L268.6,46.1L269.5,46.8L269.9,46.2L269.2,46L269.8,45.7L268.7,44.5L271.4,42.7L272.4,42.7L272.4,41.6L277.9,41.9L280.1,40.6ZM276.8,71.5L275.5,74.1L278.8,76L279.4,77.5L283.7,77.2L284.7,78.3L289.3,78.4L289.1,76.7L286.9,77.4L285.7,76.1L287.1,74.8L287.7,73L286.5,73.6L286,72.6L284.3,72.9L280.9,71.5L277.1,72L276.8,71.5ZM259.4,49.6L258.2,50.1L259,52.8L259.8,52.4L259.6,51.1L260.4,50.4L259.4,49.6Z" fill="#48a853" stroke="#ffffff" stroke-width="0.9" stroke-linejoin="round" class="cursor-pointer transition-all hover:brightness-115 hover:stroke-[1.8] hover:stroke-white" onmousemove="showMapTooltip(event, 'País Vasco', '3% a 4%')" onmouseleave="hideMapTooltip()" onclick="selectProvinceHonorarios('pais_vasco')" />
</svg>
                </div>

                <!-- Desplegable Detalle por Provincia / CCAA -->
                <div class="space-y-2">
                  <label for="calc-region-select" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Seleccionar por Comunidad o Provincia:
                  </label>
                  <select id="calc-region-select" onchange="selectProvinceHonorarios(this.value)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-navy dark:text-white focus:ring-2 focus:ring-blue/20">
                    <option value="">-- Elige tu provincia o CCAA --</option>
                    <optgroup label="Bloque 3% (Zona Norte)">
                      <option value="galicia">Galicia (A Coruña, Lugo, Ourense, Pontevedra) · 3%</option>
                      <option value="asturias">Asturias · 3%</option>
                      <option value="cantabria">Cantabria · 3%</option>
                      <option value="pais_vasco">País Vasco (Bizkaia, Gipuzkoa, Álava) · 3% a 4%</option>
                      <option value="navarra">Navarra · 3%</option>
                      <option value="la_rioja">La Rioja · 3%</option>
                      <option value="aragon">Aragón (Zaragoza, Huesca, Teruel) · 3%</option>
                    </optgroup>
                    <optgroup label="Bloque 4% (Zona Centro)">
                      <option value="madrid">Comunidad de Madrid · 4% (3% a 5%)</option>
                      <option value="castilla_leon">Castilla y León · 4%</option>
                      <option value="castilla_mancha">Castilla-La Mancha · 4%</option>
                    </optgroup>
                    <optgroup label="Bloque 5% (Zona Este e Islas)">
                      <option value="cataluna">Cataluña (Barcelona, Girona, Lleida, Tarragona) · 5%</option>
                      <option value="baleares">Islas Baleares (Mallorca, Ibiza, Menorca) · 5% a 6%</option>
                      <option value="canarias">Canarias (Las Palmas, Santa Cruz de Tenerife) · 5%</option>
                    </optgroup>
                    <optgroup label="Rango Amplio 3% a 6% (Sur y Levante)">
                      <option value="andalucia">Andalucía (Málaga, Sevilla, Granada, Cádiz...) · 3% a 5%</option>
                      <option value="valencia">Comunidad Valenciana (Valencia, Alicante, Castellón) · 3% a 5%</option>
                      <option value="murcia">Región de Murcia · 3% a 4%</option>
                      <option value="extremadura">Extremadura (Badajoz, Cáceres) · 3% a 4%</option>
                    </optgroup>
                  </select>
                </div>

              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- KPIS VIVOS EN TIEMPO REAL -->
      <section class="home-kpi-section relative -mt-2 md:-mt-3 z-10">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-white/95 dark:bg-slate-900/95 backdrop-blur p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-lg transition-colors">
            <!-- 1. Captaciones -->
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/50 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Captaciones Visibles</span>
                  <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue/10 text-blue">Cartera</span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                  <strong id="home-stat-properties" class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">6</strong>
                  <span class="text-xs text-slate-500 font-medium">inmuebles</span>
                </div>
                <div class="mt-1 text-xs font-bold text-blue" id="home-stat-properties-value">4.850.000 € en volumen</div>
              </div>
              <div class="mt-3 pt-2 border-t border-slate-200/50 dark:border-slate-700/50 flex justify-between items-center">
                <span class="text-xs text-slate-400">Protegidas 50/50</span>
                <a href="#/oportunidades" class="text-xs font-bold text-blue hover:underline">Ver cartera →</a>
              </div>
            </div>

            <!-- 2. Demandas -->
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/50 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Demandas Activas</span>
                  <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500">Solventes</span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                  <strong id="home-stat-needs" class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">4</strong>
                  <span class="text-xs text-slate-500 font-medium">compradores</span>
                </div>
                <div class="mt-1 text-xs font-bold text-emerald-500" id="home-stat-needs-value">2.150.000 € presupuesto</div>
              </div>
              <div class="mt-3 pt-2 border-t border-slate-200/50 dark:border-slate-700/50 flex justify-between items-center">
                <span class="text-xs text-slate-400">Cualificadas</span>
                <a href="#/buscar-captaciones" class="text-xs font-bold text-emerald-500 hover:underline">Ver demandas →</a>
              </div>
            </div>

            <!-- 3. Cobertura -->
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/50 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Zonas Activas</span>
                  <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber/10 text-amber">INE Oficial</span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                  <strong id="home-stat-zones" class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">19 CCAA</strong>
                </div>
                <div class="mt-1 text-xs font-bold text-slate-400">52 Provincias conectadas</div>
              </div>
              <div class="mt-3 pt-2 border-t border-slate-200/50 dark:border-slate-700/50 flex justify-between items-center">
                <span class="text-xs text-slate-400">Nivel nacional</span>
                <button type="button" onclick="scrollToCoverageMap(event)" class="text-xs font-bold text-amber hover:underline">Ver mapa →</button>
              </div>
            </div>

            <!-- 4. Coincidencias de Venta -->
            <div class="p-4 rounded-xl bg-gradient-to-br from-emerald-950/40 to-slate-900 border border-emerald-500/40 shadow-sm flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Coincidencias de Venta</span>
                  <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-400/20 text-emerald-300">Cruces</span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                  <strong id="home-stat-sales-matches" class="text-2xl sm:text-3xl font-black text-emerald-400">5 Cruces</strong>
                </div>
                <div class="mt-1 text-xs font-bold text-emerald-300" id="home-stat-sales-value">1.850.000 € en operaciones</div>
              </div>
              <div class="mt-3 pt-2 border-t border-emerald-500/20 flex justify-between items-center">
                <span class="text-xs text-emerald-400/80">Listas para 50/50</span>
                <a href="#/coincidencias-ventas" class="text-xs font-bold text-emerald-300 hover:underline">Ver cruces →</a>
              </div>
            </div>
          </div>
          <p class="mt-3 text-xs text-slate-400 text-right font-medium">Métricas actualizadas automáticamente en tiempo real.</p>
        </div>
      </section>

      <!-- MAPA ESPAÑA Y RADAR -->
      <section id="mapa-cobertura" class="py-16 bg-slate-50 dark:bg-slate-950 transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">
          <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 mb-8">
            <div class="max-w-3xl">
              <span class="text-xs font-bold tracking-widest text-blue uppercase">Radar nacional de oportunidades</span>
              <h2 class="text-3xl sm:text-4xl font-extrabold text-navy dark:text-white tracking-tight mt-2">Mapa interactivo de captaciones y demandas</h2>
              <p class="text-base text-slate-600 dark:text-slate-300 mt-3">Consulta el mapa de oportunidades y demanda activa. Las direcciones exactas permanecen protegidas.</p>
            </div>
            <div class="flex flex-wrap gap-2" aria-label="Filtros del mapa">
              <button id="map-filter-all" onclick="setHomeMapMode('all')" class="map-filter-active px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold transition-all">Todas</button>
              <button id="map-filter-properties" onclick="setHomeMapMode('properties')" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:border-blue transition-all">● Captaciones</button>
              <button id="map-filter-needs" onclick="setHomeMapMode('needs')" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:border-green transition-all">● Demandas</button>
            </div>
          </div>
          <div class="mb-5 flex flex-col xl:flex-row xl:items-end gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3">
            <div class="w-full xl:max-w-xs">
              <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Buscar por Código Postal en el mapa</label>
              <div class="flex gap-2">
                <input id="home-map-postal-filter" type="search" inputmode="numeric" maxlength="5" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); applyHomeMapPostalFilter(); }" placeholder="Ej.: 32002" class="w-[10ch] shrink-0 px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl focus:outline-none focus:ring-2 focus:ring-blue/20 bg-white" />
                <button onclick="applyHomeMapPostalFilter()" class="px-3 py-2 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-bold transition-all">Buscar CP</button>
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <button onclick="activateHomeAreaDraw()" class="px-3 py-2 rounded-xl border border-blue/30 bg-white text-blue text-xs font-bold hover:bg-blue-light transition-all">▱ Dibujar zona</button>
              <button onclick="clearHomeMapArea()" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold hover:text-navy hover:border-slate-300 transition-all">✕ Limpiar zona</button>
            </div>
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            <div class="lg:col-span-9 rounded-3xl overflow-hidden border border-slate-200 shadow-sm bg-slate-100">
              <div id="home-map" role="application" aria-label="Mapa interactivo de oportunidades inmobiliarias en España"></div>
            </div>
            <aside class="lg:col-span-3 rounded-3xl bg-navy text-white p-6 flex flex-col justify-between">
              <div>
                <span class="text-xs font-black uppercase tracking-wider text-blue-light">Lectura del mapa</span>
                <h3 class="font-extrabold text-xl mt-2">Cobertura territorial</h3>
                <p class="text-xs sm:text-sm text-slate-300 mt-3 leading-relaxed">Cada punto representa una captación o una demanda activa. La geolocalización es orientativa para proteger datos sensibles del expediente.</p>
              </div>
              <div class="mt-8 space-y-3 text-xs sm:text-sm">
                <div class="flex items-center justify-between gap-4"><span class="text-slate-300">Captaciones mapeadas</span><strong id="home-map-properties" class="text-blue-light">—</strong></div>
                <div class="flex items-center justify-between gap-4"><span class="text-slate-300">Demandas mapeadas</span><strong id="home-map-needs" class="text-green-light">—</strong></div>
                <div class="flex items-center justify-between gap-4 border-t border-white/10 pt-3"><span class="text-slate-300">Zonas con actividad</span><strong id="home-map-zones">—</strong></div>
              </div>
            </aside>
          </div>
        </div>
      </section>

      <!-- ÚLTIMAS PUBLICACIONES EN CARTERA -->
      <section class="py-16 bg-white dark:bg-slate-900 border-y border-slate-100 dark:border-slate-800 transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 space-y-14">
          <div>
            <div class="mb-6">
              <span class="text-xs font-bold tracking-widest text-blue uppercase">Producto compartido</span>
              <h2 class="text-2xl sm:text-3xl font-extrabold text-navy dark:text-white mt-2">Últimas captaciones publicadas</h2>
            </div>
            <div class="home-carousel-shell">
              <button type="button" onclick="scrollHomeCarousel('home-latest-properties', -1)" class="home-carousel-nav home-carousel-nav-prev" aria-label="Ver captaciones anteriores">‹</button>
              <div id="home-latest-properties" class="home-carousel-track scrollbar-hidden" aria-label="Carrusel de últimas captaciones"></div>
              <button type="button" onclick="scrollHomeCarousel('home-latest-properties', 1)" class="home-carousel-nav home-carousel-nav-next" aria-label="Ver más captaciones">›</button>
            </div>
            <div class="mt-5 flex justify-center">
              <a href="<?php echo esc_url(home_url('/propiedades')); ?>" class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-bold uppercase tracking-wider shadow-md transition-all">Ver más Propiedades Disponibles →</a>
            </div>
          </div>

          <div>
            <div class="mb-6">
              <span class="text-xs font-bold tracking-widest text-green uppercase">Compradores cualificados</span>
              <h2 class="text-2xl sm:text-3xl font-extrabold text-navy dark:text-white mt-2">Últimas demandas de compra activas</h2>
            </div>
            <div class="home-carousel-shell">
              <button type="button" onclick="scrollHomeCarousel('home-latest-needs', -1)" class="home-carousel-nav home-carousel-nav-prev" aria-label="Ver demandas anteriores">‹</button>
              <div id="home-latest-needs" class="home-carousel-track scrollbar-hidden" aria-label="Carrusel de últimas demandas"></div>
              <button type="button" onclick="scrollHomeCarousel('home-latest-needs', 1)" class="home-carousel-nav home-carousel-nav-next" aria-label="Ver más demandas">›</button>
            </div>
            <div class="mt-5 flex justify-center">
              <a href="<?php echo esc_url(home_url('/demandas')); ?>" class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-navy hover:bg-navy-light text-white text-xs font-bold uppercase tracking-wider shadow-md transition-all">Ver más Demandas Activas →</a>
            </div>
          </div>
        </div>
      </section>

      <!-- REGISTRO / LOGIN (CIERRE DEL EMBUDO) -->
      <section id="home-register-login" class="py-16 md:py-20 bg-slate-50 dark:bg-slate-950 transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
            <div class="lg:col-span-5 rounded-3xl bg-gradient-to-br from-navy to-navy-light p-8 sm:p-10 text-white flex flex-col justify-between shadow-xl">
              <div>
                <span class="text-xs font-bold tracking-widest text-blue-neon uppercase">Acceso profesional verificado</span>
                <h2 class="text-3xl sm:text-4xl font-black mt-3 leading-tight">Únete a la red inmobiliaria que multiplica tus ventas</h2>
                <p class="text-sm sm:text-base text-slate-300 mt-4 leading-relaxed">El objetivo no es solo entrar en una plataforma, sino disponer de un entorno donde captar, filtrar, solicitar información y avanzar oportunidades con total seguridad jurídica.</p>
              </div>
              <div class="mt-8 rounded-2xl border border-white/15 bg-white/5 p-5 text-sm">
                <h3 class="font-black text-white">Ventajas operativas de la red:</h3>
                <ul class="mt-4 grid gap-3 text-slate-200 sm:grid-cols-3 text-xs sm:text-sm">
                  <li>✓ Blindaje registral con Datos Ciegos</li>
                  <li>✓ Cruce automático de demandas con IA Vera</li>
                  <li>✓ Contratos homologados 50/50 y NDA</li>
                </ul>
              </div>
            </div>

            <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm">
              <div id="auth-guest-panel">
                <!-- BANNER DE BONO DE BIENVENIDA SUPERIOR -->
                <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2.5">
                  <span class="text-lg">🎁</span>
                  <span><strong>Bono de bienvenida:</strong> Recibes <strong>3 créditos de bienvenida</strong> para desbloquear expedientes reales, válidos durante tus primeros 30 días (no acumulables) al activar tu cuenta desde tu email.</span>
                </div>

                <!-- FORMULARIO DE REGISTRO DIRECTO -->
                <form id="home-inline-register-form" onsubmit="handleHomeInlineRegister(event)" class="space-y-4">
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Nombre y apellidos *</label>
                      <input type="text" id="inline-reg-name" required placeholder="Ej. Carlos Martínez" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-navy dark:text-white text-xs font-medium focus:border-blue focus:ring-1 focus:ring-blue outline-none transition-all">
                    </div>
                    <div>
                      <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Agencia o Marca Comercial <span class="text-slate-400 font-normal lowercase">(opcional)</span></label>
                      <input type="text" id="inline-reg-agency" placeholder="Ej. Inmobiliaria Costa / Profesional independiente" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-navy dark:text-white text-xs font-medium focus:border-blue focus:ring-1 focus:ring-blue outline-none transition-all">
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Email profesional *</label>
                      <input type="email" id="inline-reg-email" required placeholder="tuemail@agencia.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-navy dark:text-white text-xs font-medium focus:border-blue focus:ring-1 focus:ring-blue outline-none transition-all">
                      <span class="block text-[10px] text-slate-400 mt-1">Recibirás aquí el enlace de activación de cuenta.</span>
                    </div>
                    <div>
                      <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Teléfono / WhatsApp <span class="text-slate-400 font-normal lowercase">(opcional)</span></label>
                      <input type="tel" id="inline-reg-phone" placeholder="+34 600 000 000" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-navy dark:text-white text-xs font-medium focus:border-blue focus:ring-1 focus:ring-blue outline-none transition-all">
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Contraseña de acceso *</label>
                      <input type="password" id="inline-reg-password" required minlength="6" placeholder="Mínimo 6 caracteres" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-navy dark:text-white text-xs font-medium focus:border-blue focus:ring-1 focus:ring-blue outline-none transition-all">
                    </div>
                    <div>
                      <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">Repetir contraseña *</label>
                      <input type="password" id="inline-reg-password-repeat" required minlength="6" placeholder="Repite tu contraseña" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-navy dark:text-white text-xs font-medium focus:border-blue focus:ring-1 focus:ring-blue outline-none transition-all">
                    </div>
                  </div>

                  <div class="flex items-start gap-2 pt-1">
                    <input type="checkbox" id="inline-reg-terms" required class="mt-0.5 rounded border-slate-300 text-blue focus:ring-blue">
                    <label for="inline-reg-terms" class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                      He leído y acepto las <a href="<?php echo esc_url(home_url('/condiciones-de-contratacion')); ?>" class="legal-link text-blue underline">Condiciones de uso</a> y la <a href="<?php echo esc_url(home_url('/privacidad')); ?>" class="legal-link text-blue underline">Política de privacidad</a> de Compra Captación. Acepto también las normas profesionales de colaboración 50/50.
                    </label>
                  </div>

                  <button type="submit" id="btn-inline-register-submit" class="w-full py-4 rounded-xl bg-blue hover:bg-blue-dark text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-blue/20 hover:-translate-y-0.5 transition-all">
                    Crear cuenta y recibir enlace de activación →
                  </button>

                  <div class="text-center pt-2">
                    <span class="text-xs text-slate-500 dark:text-slate-400">¿Ya tienes cuenta profesional?</span>
                    <button type="button" onclick="openProfessionalAccess()" class="text-xs text-blue hover:underline font-bold ml-1">Entrar aquí</button>
                  </div>
                </form>

                <!-- PANTALLA DE ACTIVACIÓN PENDIENTE (POST-REGISTRO) -->
                <div id="inline-reg-success-message" class="hidden text-center py-6 space-y-4">
                  <div class="w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 text-2xl flex items-center justify-center mx-auto shadow-sm">📬</div>
                  <h3 class="text-xl font-black text-navy dark:text-white">¡Revisa tu correo electrónico!</h3>
                  <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 max-w-md mx-auto leading-relaxed">
                    Hemos enviado un enlace de activación a <strong id="reg-sent-email-target" class="text-navy dark:text-white">tu email</strong>.
                  </p>
                  <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 max-w-md mx-auto text-left space-y-2">
                    <p class="font-bold text-navy dark:text-white">Para activar tu cuenta:</p>
                    <p>1. Abre el email que te acabamos de enviar.</p>
                    <p>2. Pulsa en <strong>«Activar mi cuenta»</strong> para verificar tu identidad profesional.</p>
                    <p>3. Tus <strong>3 créditos de bienvenida</strong> (válidos durante tus primeros 30 días, no acumulables) quedarán activados automáticamente.</p>
                  </div>
                  <button type="button" onclick="resetInlineRegisterForm()" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-navy dark:text-white text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Volver / Usar otro email</button>
                </div>
              </div>

              <!-- PANEL DE SESIÓN ACTIVA -->
              <div id="auth-session-panel" class="hidden h-full flex flex-col justify-between">
                <div>
                  <span class="inline-flex px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-500 text-xs font-black uppercase">Sesión activa</span>
                  <h3 id="auth-session-name" class="text-2xl font-black text-navy dark:text-white mt-3">Bienvenido</h3>
                  <p id="auth-session-agency" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"></p>
                  
                  <div class="my-5 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                      <span class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Estado de cuenta</span>
                      <strong class="text-sm font-black text-navy dark:text-white flex items-center gap-1.5 mt-0.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Profesional Verificado 50/50
                      </strong>
                    </div>
                    <span class="px-3 py-1.5 rounded-xl bg-blue/10 text-blue dark:text-blue-neon text-xs font-black">
                      💎 Activo
                    </span>
                  </div>

                  <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">Tu entorno profesional está sincronizado. Puedes publicar inmuebles en exclusiva, cruzar demandas con Vera IA o gestionar tus operaciones compartidas.</p>
                </div>

                <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                  <a href="#/area-privada" class="flex-1 text-center py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black uppercase tracking-wider shadow-md transition-all">
                    Ir al área privada →
                  </a>
                  <button type="button" onclick="logoutDemo()" class="px-5 py-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-red-600 text-xs font-bold uppercase tracking-wider transition-colors">
                    Cerrar sesión
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- [SECCIÓN UNIFICADA]: ADIÓS AL CAOS DE WHATSAPP / COLABORACIÓN BLINDADA -->
      <section class="py-14 sm:py-18 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 space-y-12">
          <div class="text-center max-w-4xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">El fin del ruido inmobiliario</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">Adiós al caos de los grupos de WhatsApp informales</h2>
            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">Centralizamos la colaboración inmobiliaria profesional para que cierres ventas sin exponer datos confidenciales ni perder el tiempo con contactos sin encaje:</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
            <article class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-8 shadow-sm hover:-translate-y-1 transition-all space-y-4">
              <div class="w-12 h-12 rounded-2xl bg-blue/10 text-blue font-black text-2xl flex items-center justify-center">🔒</div>
              <h3 class="text-xl font-bold text-navy dark:text-white">1. Privacidad con Datos Ciegos</h3>
              <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                Muestra solo los datos necesarios para despertar interés comercial. La dirección exacta y datos del dueño permanecen ocultos hasta que ambas partes validen el acuerdo legal.
              </p>
            </article>

            <article class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-8 shadow-sm hover:-translate-y-1 transition-all space-y-4">
              <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-500 font-black text-2xl flex items-center justify-center">⚡</div>
              <h3 class="text-xl font-bold text-navy dark:text-white">2. Algoritmo de Cruce Inmediato</h3>
              <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                Nuestro motor cruza automáticamente tus captaciones con demandas activas filtradas por zona, presupuesto y tipología, conectándote directo con agentes que ya tienen el comprador.
              </p>
            </article>

            <article class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-8 shadow-sm hover:-translate-y-1 transition-all space-y-4">
              <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 font-black text-2xl flex items-center justify-center">⚖️</div>
              <h3 class="text-xl font-bold text-navy dark:text-white">3. Contratos Homologados 50/50</h3>
              <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                Reserva la oportunidad durante 72 horas, acepta la colaboración y firma con la otra parte el acuerdo de honorarios y confidencialidad antes de compartir datos sensibles.
              </p>
            </article>
          </div>
        </div>
      </section>

      <!-- [NUEVO BLOQUE DE EMBUDO]: MINI-FAQ DE DERRIBO DE OBJECIONES ANTES DEL PIE -->
      <section class="py-14 sm:py-18 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 space-y-10">
          <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">Preguntas rápidas</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">¿Tienes dudas antes de unirte?</h2>
            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">Resolvemos las preguntas más habituales de los profesionales antes de registrarse:</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base font-bold text-navy dark:text-white hover:text-blue cursor-pointer">
                <span>¿Es realmente 100% gratis registrarse y empezar?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                Sí. El registro no requiere tarjeta bancaria. Al darte de alta recibes automáticamente 3 créditos de bienvenida válidos durante tus primeros 30 días para desbloquear contactos reales y probar todo el flujo de colaboración.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base font-bold text-navy dark:text-white hover:text-blue cursor-pointer">
                <span>¿Cómo garantizáis que nadie contacte a mi cliente propietario?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                La dirección exacta, puerta y contacto del vendedor están bajo estricto candado digital ("Datos Ciegos"). Para acceder al expediente, el otro profesional debe identificarse, consumir 1 crédito y aceptar el acuerdo legal homologado de colaboración.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base font-bold text-navy dark:text-white hover:text-blue cursor-pointer">
                <span>¿Cómo se asegura legalmente el cobro de mi 50% de honorarios?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                La plataforma genera automáticamente el Acuerdo Oficial de Colaboración de Honorarios Inmobiliarios y el Acuerdo de Confidencialidad (NDA), firmados digitalmente antes de la visita para garantizar el reparto pactado en notaría.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base font-bold text-navy dark:text-white hover:text-blue cursor-pointer">
                <span>¿Qué ocurre cuando consuma los 3 créditos de bienvenida?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                Publicar propiedades y demandas sigue siendo 100% gratuito e ilimitado. Además, gracias a nuestro <strong>Modelo Circular</strong>, cada vez que otra agencia desbloquee tu captación recibirás automáticamente <strong>+0.5 créditos</strong> en tu saldo. También puedes recargar packs según tu volumen.
              </div>
            </details>
          </div>
        </div>
      </section>
    </div>

    <!-- PÁGINA 2: BUSCAR CAPTACIONES (Demandas de Búsqueda Activas) -->
    <div id="page-buscar-captaciones" class="page-section <?php echo $captacion_active_page_id === 'page-buscar-captaciones' ? '' : 'hidden'; ?>">
      <section class="py-12 bg-slate-50 dark:bg-slate-950">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">

          <!-- BREADCRUMBS Y REGRESO A OPORTUNIDADES -->
          <nav aria-label="Breadcrumb" class="mb-5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
            <ol class="flex items-center space-x-2">
              <li><a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-blue transition-colors">Inicio</a></li>
              <li class="text-slate-400">/</li>
              <li><a href="<?php echo esc_url(home_url('/oportunidades')); ?>" class="hover:text-blue transition-colors font-medium">Oportunidades</a></li>
              <li class="text-slate-400">/</li>
              <li class="font-bold text-navy dark:text-white">Marketplace de Demandas</li>
            </ol>
            <a href="<?php echo esc_url(home_url('/oportunidades')); ?>" class="inline-flex items-center gap-1.5 font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 transition-colors">
              <span>← Volver al Hub de Oportunidades</span>
            </a>
          </nav>

          <div class="mb-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
              <h1 class="text-3xl font-black text-navy dark:text-white">Demandas compartidas por profesionales inmobiliarios</h1>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-4xl">Consulta requerimientos reales de compradores e inversores cualificados gestionados por agencias y profesionales en toda España.</p>
            </div>
            <?php if (is_user_logged_in()) : ?>
              <a href="<?php echo esc_url(home_url('/coincidencias-ventas')); ?>" class="shrink-0 px-5 py-3 rounded-xl bg-emerald-600 text-white text-xs font-black shadow-sm hover:bg-emerald-700">Ver mis coincidencias</a>
            <?php else : ?>
              <button type="button" onclick="openProfessionalSubscriptionModal('demandas-colaborar')" class="shrink-0 px-5 py-3 rounded-xl bg-emerald-600 text-white text-xs font-black shadow-sm hover:bg-emerald-700">Crear cuenta gratuita para colaborar</button>
            <?php endif; ?>
          </div>

          <!-- CTA DESTACADO: PUBLICAR DEMANDA DE COMPRADOR -->
          <div class="mb-8 rounded-3xl bg-gradient-to-r from-[#071d18] via-[#092d24] to-[#041612] border border-emerald-500/30 p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-72 h-72 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
              <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 text-[11px] font-bold uppercase tracking-wider">
                  <span>✨ Demanda Activa y Cualificada</span>
                </div>
                <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight">
                  ¿Buscas un inmueble para un comprador o inversor?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                  Publica las necesidades de tus clientes ante miles de agentes colaboradores en toda España. Publicar es 100% gratuito e ilimitado, protegiendo siempre los datos de tu cliente.
                </p>
              </div>
              <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                <a href="<?php echo esc_url(home_url('/publicar?tipo=demanda')); ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-black shadow-lg shadow-emerald-500/30 hover:scale-105 transition-all">
                  <span>✚ Publicar una demanda</span>
                  <span>↗</span>
                </a>
              </div>
            </div>
          </div>

          <!-- BÚSQUEDA SIMPLE DE DEMANDAS: MISMO CRITERIO QUE OPORTUNIDADES -->
          <div id="needs-search-panel" class="captacion-search-panel bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
              <div class="flex items-center gap-3">
                <h3 class="text-base font-extrabold text-navy dark:text-white">Encuentra una demanda de comprador</h3>
                <span id="needs-active-count-badge" class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold">Cargando...</span>
              </div>
              <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl" role="group" aria-label="Modo de visualización de demandas">
                <button onclick="setNeedsLayout('mapa')" id="layout-mapa-btn" class="px-3 py-2 text-xs font-semibold rounded-lg text-slate-500 dark:text-slate-400 hover:text-navy dark:hover:text-white transition-all">Mapa</button>
                <button onclick="setNeedsLayout('bloque')" id="layout-bloque-btn" class="px-3 py-2 text-xs font-semibold rounded-lg bg-white dark:bg-slate-700 text-navy dark:text-white shadow-sm transition-all">▦ Bloques</button>
                <button onclick="setNeedsLayout('lista')" id="layout-lista-btn" class="px-3 py-2 text-xs font-semibold rounded-lg text-slate-500 dark:text-slate-400 hover:text-navy dark:hover:text-white transition-all">☰ Lista</button>
              </div>
            </div>

            <!-- QUICK FILTER PILLS -->
            <div class="mb-5 flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
              <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 mr-1">Filtro rápido:</span>
              <button type="button" onclick="document.getElementById('need-filter-type').value='all';filterNeeds()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white transition-all text-slate-700 dark:text-slate-200">✦ Todas las categorías</button>
              <button type="button" onclick="document.getElementById('need-filter-type').value='Piso';filterNeeds()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white transition-all text-slate-700 dark:text-slate-200">🏢 Pisos</button>
              <button type="button" onclick="document.getElementById('need-filter-type').value='Casa / chalet';filterNeeds()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white transition-all text-slate-700 dark:text-slate-200">🏡 Chalets</button>
              <button type="button" onclick="document.getElementById('need-filter-type').value='Local comercial';filterNeeds()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white transition-all text-slate-700 dark:text-slate-200">🏬 Locales</button>
              <button type="button" onclick="document.getElementById('need-filter-type').value='Nave';filterNeeds()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white transition-all text-slate-700 dark:text-slate-200">🏗️ Naves</button>
              <button type="button" onclick="document.getElementById('need-filter-type').value='Terreno / solar';filterNeeds()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white transition-all text-slate-700 dark:text-slate-200">🌲 Suelo</button>
            </div>

            <div class="captacion-search-grid grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
              <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tipo de inmueble</label>
                <select id="need-filter-type" onchange="filterNeeds()" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white">
                  <option value="all">Todos los tipos</option><option value="Piso">Piso</option><option value="Casa / chalet">Casa / chalet</option><option value="Ático">Ático</option><option value="Dúplex">Dúplex</option><option value="Apartamento">Apartamento</option><option value="Estudio">Estudio</option><option value="Finca rústica con vivienda">Finca rústica con vivienda</option><option value="Edificio residencial">Edificio residencial</option><option value="Local comercial">Local comercial</option><option value="Nave">Nave</option><option value="Oficina">Oficina</option><option value="Terreno / solar">Terreno / solar</option><option value="Garaje">Garaje</option><option value="Trastero">Trastero</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Ubicación</label>
                <select id="need-filter-ccaa" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white"><option value="all">Toda España</option></select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Presupuesto máximo</label>
                <select id="need-filter-price" onchange="filterNeeds()" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm bg-white"><option value="all">Cualquier presupuesto</option><option value="low">Hasta 150.000 €</option><option value="mid">Hasta 500.000 €</option><option value="high">Más de 500.000 €</option></select>
              </div>
              <button type="button" onclick="filterNeeds()" class="px-5 py-2.5 rounded-xl bg-blue text-white text-sm font-semibold shadow-sm hover:bg-blue-dark">Buscar oportunidad</button>
            </div>
            <details class="captacion-advanced-filters mt-4 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
              <summary class="cursor-pointer text-xs font-semibold text-blue">Más filtros</summary>
              <div class="captacion-search-grid captacion-search-grid-advanced mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Orden</label><select id="need-filter-time" onchange="filterNeeds()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white"><option value="newest">Más recientes</option><option value="oldest">Más antiguas</option><option value="reputation">Reputación profesional</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Reputación profesional</label><select id="need-filter-reputation" onchange="filterNeeds()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white"><option value="0">Cualquier nivel</option><option value="30">Desde 30/100</option><option value="50">Desde 50/100</option><option value="70">Desde 70/100</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Provincia</label><select id="need-filter-province" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white"><option value="all">Todas las provincias</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Municipio</label><select id="need-filter-municipality" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm bg-white"><option value="all">Todos los municipios</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Código postal</label><input type="search" id="need-filter-postal-code" oninput="filterNeeds()" inputmode="numeric" maxlength="5" placeholder="Ej.: 32002" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" /></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Barrio o zona</label><input type="text" id="need-filter-locality" onkeyup="filterNeeds()" placeholder="Ej.: Recoletos" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" /></div>
              </div>
              <div class="mt-4 flex justify-end"><button type="button" onclick="clearAdvancedFilters()" class="text-xs font-semibold text-slate-500 hover:text-blue">Limpiar filtros</button></div>
            </details>
          </div>

          <div id="needs-map-panel" class="hidden mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4"><p class="text-xs text-slate-500">Vista mapa aproximada. Las ubicaciones exactas de compradores y profesionales permanecen protegidas.</p></div>
            <div id="needs-map" role="application" aria-label="Mapa aproximado de demandas inmobiliarias activas"></div>
          </div>

          <div id="needs-accordion-sections" class="space-y-4 mb-8"></div>

          <!-- CONTENEDOR DE LA LISTA DE DEMANDAS -->
          <div id="needs-list-container">
            <!-- Se renderiza dinámicamente según el formato de visualización -->
          </div>
        </div>
      </section>
    </div>

    <!-- PÁGINA UNIFICADA DE PUBLICACIÓN: OFRECER CAPTACIÓN Y PUBLICAR DEMANDA EN 3 PASOS -->
    <div id="page-publicar" class="page-section <?php echo $captacion_active_page_id === 'page-publicar' ? '' : 'hidden'; ?>">
      <section class="py-12 bg-slate-50 dark:bg-slate-950">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

          <!-- BREADCRUMBS -->
          <nav aria-label="Breadcrumb" class="mb-6 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
            <ol class="flex items-center space-x-2">
              <li><a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-blue transition-colors">Inicio</a></li>
              <li class="text-slate-400">/</li>
              <li><a href="<?php echo esc_url(home_url('/oportunidades')); ?>" class="hover:text-blue transition-colors">Oportunidades</a></li>
              <li class="text-slate-400">/</li>
              <li class="font-bold text-navy dark:text-white" id="publish-breadcrumb-active">Publicar Oportunidad</li>
            </ol>
            <a href="<?php echo esc_url(home_url('/oportunidades')); ?>" class="inline-flex items-center gap-1.5 font-bold text-blue hover:text-blue-dark transition-colors">
              <span>← Volver a Oportunidades</span>
            </a>
          </nav>

          <!-- SELECTOR DE INTENCIÓN HERO PREMIUM (DUAL TOGGLE TABS) -->
          <div class="flex justify-center mb-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-2 w-full max-w-2xl rounded-3xl bg-slate-200/80 dark:bg-slate-800/80 border border-slate-300/80 dark:border-slate-700 shadow-lg">
              <button type="button" id="publish-tab-offer" onclick="switchPublishMode('oferta')" class="flex items-center gap-3.5 px-6 py-4 rounded-2xl text-left transition-all bg-white dark:bg-slate-900 text-navy dark:text-white shadow-lg border-2 border-blue/50">
                <span class="w-11 h-11 rounded-xl bg-blue/10 dark:bg-blue/20 text-blue flex items-center justify-center text-xl flex-shrink-0">🏢</span>
                <span class="min-w-0">
                  <strong class="block text-sm sm:text-base font-black leading-tight">Compartir Inmueble</strong>
                  <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-bold">Tengo la captación (Oferta 50/50)</span>
                </span>
              </button>
              <button type="button" id="publish-tab-need" onclick="switchPublishMode('demanda')" class="flex items-center gap-3.5 px-6 py-4 rounded-2xl text-left transition-all text-slate-600 dark:text-slate-400 hover:text-navy dark:hover:text-white border-2 border-transparent">
                <span class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl flex-shrink-0">🎯</span>
                <span class="min-w-0">
                  <strong class="block text-sm sm:text-base font-black leading-tight">Publicar Demanda</strong>
                  <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-bold">Tengo al comprador (Busco inmueble)</span>
                </span>
              </button>
            </div>
          </div>

          <!-- BANNER OFERTA (AZUL / NAVY) -->
          <div id="publish-header-offer" class="mb-8 rounded-3xl bg-gradient-to-r from-[#0b192c] via-[#0d223f] to-[#071322] border border-blue/30 p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-72 h-72 bg-blue/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10">
              <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue/20 border border-blue/30 text-blue-neon text-[10px] font-bold uppercase tracking-wider mb-2">✨ Cartera en Exclusiva</span>
              <h1 class="text-2xl sm:text-3xl font-black text-white">Compartir una propiedad o captación</h1>
              <p class="mt-2 text-xs sm:text-sm text-slate-300 leading-relaxed">
                Publica los datos básicos del inmueble sin mostrar la dirección exacta ni datos del propietario. Tú decides cuándo y con quién colaborar.
              </p>
              <div class="mt-3.5 pt-3 border-t border-white/10 flex flex-wrap items-center justify-between gap-3 text-xs">
                <span class="text-slate-300">💡 ¿Dudas sobre cómo proteger tu exclusiva con datos ciegos?</span>
                <button type="button" onclick="openVeraWithContext('academy', 'phase-3-captation', 3, '¿Cómo redacto una captación blindada sin mostrar la calle exacta ni el catastro?')" class="px-3.5 py-1.5 rounded-xl bg-blue/30 hover:bg-blue text-white font-bold transition-all border border-blue-400/40 flex items-center gap-1.5">
                  <span>✨ Preguntar a Vera</span>
                </button>
              </div>
            </div>
          </div>

          <!-- BANNER DEMANDA (VERDE ESMERALDA) -->
          <div id="publish-header-need" class="hidden mb-8 rounded-3xl bg-gradient-to-r from-[#071d18] via-[#092d24] to-[#041612] border border-emerald-500/30 p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-72 h-72 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10">
              <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase tracking-wider mb-2">✨ Demanda Activa</span>
              <h1 class="text-2xl sm:text-3xl font-black text-white">Publicar demanda de comprador o inversor</h1>
              <p class="mt-2 text-xs sm:text-sm text-slate-300 leading-relaxed">
                Registra los requisitos de tu cliente para encontrar captaciones compatibles en la red profesional nacional.
              </p>
              <div class="mt-3.5 pt-3 border-t border-white/10 flex flex-wrap items-center justify-between gap-3 text-xs">
                <span class="text-slate-300">🎯 ¿Cómo calificar la solvencia y presupuesto de tu comprador?</span>
                <button type="button" onclick="openVeraWithContext('academy', 'phase-4-demand', 4, '¿Qué campos son indispensables al publicar la demanda de un comprador solvente?')" class="px-3.5 py-1.5 rounded-xl bg-emerald-500/30 hover:bg-emerald-600 text-white font-bold transition-all border border-emerald-400/40 flex items-center gap-1.5">
                  <span>✨ Preguntar a Vera</span>
                </button>
              </div>
            </div>
          </div>

          <!-- CONTENEDOR DE FORMULARIO DE OFERTA EN 3 PASOS -->
          <div id="publish-offer-wrapper" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 sm:p-8">
            <div id="captation-diagnosis-draft-banner" class="hidden mb-6 rounded-2xl border border-amber-300/60 bg-amber-50 dark:bg-amber-950/20 p-4" role="status">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div><strong class="block text-sm font-black text-amber-900 dark:text-amber-200">Tienes un diagnóstico pendiente de revisión</strong><p id="captation-diagnosis-draft-meta" class="mt-1 text-xs text-amber-800/80 dark:text-amber-200/80">Se ha guardado como borrador y todavía no es público.</p></div>
                <div class="flex flex-wrap gap-2 shrink-0"><button type="button" onclick="loadLastCaptationDiagnosisDraft()" class="rounded-xl border border-amber-600 px-4 py-2.5 text-xs font-black text-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-900/30">Cargar borrador</button><button type="button" onclick="saveCaptationDiagnosisDraft()" class="rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-black text-white hover:bg-amber-700">Guardar cambios</button></div>
              </div>
            </div>
            <!-- STEPPER PROGRESS BAR OFERTA -->
            <div class="mb-8 pb-6 border-b border-slate-100 dark:border-slate-800">
              <div class="flex items-center justify-between max-w-xl mx-auto text-xs font-black">
                <div class="flex items-center gap-2" id="offer-step-ind-1">
                  <span class="w-8 h-8 rounded-full flex items-center justify-center bg-blue text-white shadow-md text-xs font-black step-circle">1</span>
                  <span class="step-label text-navy dark:text-white font-black hidden sm:inline">Datos básicos</span>
                </div>
                <div class="h-0.5 flex-1 mx-3 bg-slate-200 dark:bg-slate-700"></div>
                <div class="flex items-center gap-2 opacity-50" id="offer-step-ind-2">
                  <span class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold step-circle">2</span>
                  <span class="step-label text-slate-400 font-medium hidden sm:inline">Ubicación y detalles</span>
                </div>
                <div class="h-0.5 flex-1 mx-3 bg-slate-200 dark:bg-slate-700"></div>
                <div class="flex items-center gap-2 opacity-50" id="offer-step-ind-3">
                  <span class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold step-circle">3</span>
                  <span class="step-label text-slate-400 font-medium hidden sm:inline">Colaboración y envío</span>
                </div>
              </div>
            </div>

            <form id="offer-publication-form" class="captacion-professional-form space-y-6">
              <!-- PASO 1 OFERTA: DATOS BÁSICOS -->
              <div id="offer-step-1" class="space-y-6">
                <section class="listing-url-import rounded-2xl border border-blue/25 bg-blue-light/30 p-4 sm:p-5" aria-labelledby="listing-url-import-title">
                  <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div class="max-w-3xl"><h4 id="listing-url-import-title" class="text-base font-bold text-navy">¿Ya tienes este inmueble publicado?</h4><p class="mt-1 text-sm text-slate-600">Pega la URL como referencia y después copia los datos públicos de tu anuncio. Prepararemos una vista previa para que decidas qué incorporar.</p></div>
                    <span class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-blue border border-blue/20">Opcional</span>
                  </div>
                  <div class="mt-4 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_auto] gap-3 items-end">
                    <label class="block"><span class="private-field-label">URL de la ficha autorizada</span><input id="offer-source-url" type="url" inputmode="url" autocomplete="url" placeholder="https://www.tuagencia.es/inmueble/..." class="private-field-input" /></label>
                    <button id="offer-source-import-button" type="button" onclick="previewAuthorizedListingUrl()" class="min-h-[44px] px-5 py-3 rounded-xl bg-navy text-white text-sm font-semibold">Continuar</button>
                  </div>
                  <label class="mt-3 flex items-start gap-3 text-sm text-slate-600"><input id="offer-source-rights" type="checkbox" class="mt-1 h-4 w-4 shrink-0" /><span>Confirmo que esta ficha es propia o que tengo autorización para reutilizar su información.</span></label>
                  <p class="mt-2 text-xs text-slate-500">Nunca copiamos teléfonos, emails, datos del propietario, dirección exacta ni imágenes. Siempre revisarás los datos antes de incorporarlos.</p>
                  <div id="offer-source-assisted" class="hidden mt-4 rounded-xl border border-amber/30 bg-amber-light/50 p-4">
                    <h5 class="text-sm font-semibold text-navy">Completar desde otro anuncio</h5>
                    <p class="mt-1 text-xs text-slate-600">Abre tu anuncio, copia únicamente el título, las características y la descripción, y pégalos aquí. No incluyas datos de contacto ni información del propietario.</p>
                    <label class="mt-3 block"><span class="private-field-label">Texto público del anuncio</span><textarea id="offer-source-text" rows="6" maxlength="30000" placeholder="Pega aquí el título, precio, superficie, habitaciones, baños y descripción…" class="private-field-input min-h-[132px]"></textarea></label>
                    <div class="mt-3"><button id="offer-source-text-button" type="button" onclick="analyzeAssistedListingText()" class="min-h-[44px] rounded-xl bg-amber px-5 py-2.5 text-sm font-semibold text-navy">Analizar texto pegado</button></div>
                  </div>
                  <div id="offer-source-import-result" class="hidden mt-4" aria-live="polite"></div>
                </section>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Tipo de Inmueble *</label>
                    <select id="offer-type" required onchange="refreshOfferDefaultImagePreview();updatePropertyFormDynamics('offer')" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20">
                      <option value="Piso">Piso</option>
                      <option value="Casa / chalet">Casa / chalet</option>
                      <option value="Ático">Ático</option>
                      <option value="Dúplex">Dúplex</option>
                      <option value="Apartamento">Apartamento</option>
                      <option value="Estudio">Estudio</option>
                      <option value="Finca rústica con vivienda">Finca rústica con vivienda</option>
                      <option value="Edificio residencial">Edificio residencial</option>
                      <option value="Local comercial">Local comercial</option>
                      <option value="Nave">Nave</option>
                      <option value="Oficina">Oficina</option>
                      <option value="Terreno / solar">Terreno / solar</option>
                      <option value="Garaje">Garaje</option>
                      <option value="Trastero">Trastero</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Operación *</label>
                    <select id="offer-operation" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20">
                      <option value="Venta">Venta</option>
                      <option value="Alquiler">Alquiler</option>
                      <option value="Alquiler con Opción a Compra">Alquiler con Opción a Compra</option>
                      <option value="Otra necesidad inmobiliaria">Otra necesidad inmobiliaria</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                  <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Título de la captación *</label>
                    <input id="offer-title" type="text" required minlength="8" placeholder="Ej: Piso luminoso con terraza en zona centro" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Precio orientativo (€) *</label>
                    <input type="number" id="offer-price" required min="1" inputmode="numeric" placeholder="Ej: 245000" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20" />
                  </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button type="button" onclick="setOfferStep(2)" class="px-7 py-3.5 bg-blue hover:bg-blue-dark text-white rounded-xl text-xs font-black shadow-md shadow-blue/25 hover:scale-105 transition-all">
                    Continuar al Paso 2: Ubicación y Detalles →
                  </button>
                </div>
              </div>

              <!-- PASO 2 OFERTA: UBICACIÓN Y DETALLES -->
              <div id="offer-step-2" class="hidden space-y-6">
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700 space-y-4">
                  <div>
                    <span class="block text-sm font-black text-navy dark:text-white">Ubicación del inmueble</span>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">La dirección exacta y los datos del propietario permanecerán 100% confidenciales.</p>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Comunidad *</label>
                      <select id="offer-ccaa-sel" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20">
                        <option value="">Elegir...</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Provincia *</label>
                      <select id="offer-province-sel" required disabled class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20 disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">Elegir...</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Municipio *</label>
                      <input id="offer-municipality-sel" type="text" list="offer-municipality-list" required disabled autocomplete="off" placeholder="Elegir..." class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20 disabled:bg-slate-100 disabled:text-slate-400" />
                      <datalist id="offer-municipality-list"></datalist>
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">C.P. (opcional)</label>
                      <input type="text" id="offer-postal-code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" placeholder="Ej: 28001" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20" />
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Barrio o zona (opcional)</label>
                      <input type="text" id="offer-locality-input" placeholder="Ej: Salamanca" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20" />
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Número de habitaciones *</label>
                    <input type="number" id="offer-bedrooms" required min="0" max="99" step="1" placeholder="Ej: 3" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Número de baños *</label>
                    <input type="number" id="offer-bathrooms" required min="0" max="99" step="1" placeholder="Ej: 2" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Superficie total (m²) *</label>
                    <input type="number" id="offer-surface" required min="1" max="99999" step="1" placeholder="Ej: 95" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20" />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <div id="offer-elevator-wrap">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Ascensor</label>
                    <select id="offer-elevator" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20">
                      <option value="No indicado">No indicado</option><option value="Sí">Sí</option><option value="No">No</option>
                    </select>
                  </div>
                  <div id="offer-garage-wrap">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Garaje</label>
                    <select id="offer-garage" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20">
                      <option value="No indicado">No indicado</option><option value="Sí">Sí</option><option value="No">No</option><option value="Opcional">Opcional</option>
                    </select>
                  </div>
                  <div id="offer-estate-wrap" class="hidden">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Finca</label>
                    <select id="offer-estate-type" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20">
                      <option value="No indicado">No indicado</option><option value="Urbana">Urbana</option><option value="Rústica">Rústica</option><option value="Independiente">Independiente</option><option value="Pareada">Pareada</option><option value="Adosada">Adosada</option>
                    </select>
                  </div>
                  <div id="offer-estate-surface-wrap" class="hidden">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">m² finca/parcela</label>
                    <input type="number" id="offer-estate-surface" min="1" step="1" placeholder="Ej: 450" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20" />
                  </div>
                </div>

                <!-- IMÁGENES DEL INMUEBLE -->
                <div class="space-y-3 pt-2">
                  <span class="block text-sm font-black text-navy dark:text-white">Imagen del inmueble</span>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 cursor-pointer">
                      <input id="offer-image-mode-upload" type="radio" name="offer-image-mode" value="upload" checked onchange="setOfferImageMode('upload')" class="mt-0.5" />
                      <span><strong class="block text-xs text-navy dark:text-white">Subir fotografía o plano</strong><small class="block text-[10px] text-slate-400 mt-1">Sube imagen real o plano ligero.</small></span>
                    </label>
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 cursor-pointer">
                      <input id="offer-image-mode-default" type="radio" name="offer-image-mode" value="default" onchange="setOfferImageMode('default')" class="mt-0.5" />
                      <span><strong class="block text-xs text-navy dark:text-white">Usar imagen predeterminada</strong><small class="block text-[10px] text-slate-400 mt-1">Se asigna la portada temática según categoría.</small></span>
                    </label>
                  </div>
                  <div id="offer-image-upload-panel" class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 text-center cursor-pointer hover:bg-slate-100 transition-colors" onclick="if (document.getElementById('offer-image-mode-upload').checked) document.getElementById('offer-file-input').click()">
                    <input type="file" id="offer-file-input" accept="image/*,application/pdf" class="hidden" onchange="handleFileSelection(event)">
                    <span class="text-sm font-semibold text-slate-500 block">Fotografía de portada (Opcional)</span>
                    <span class="text-[10px] text-slate-400 block mt-1" id="file-upload-status">JPG, PNG, WEBP o PDF.</span>
                    <button type="button" class="mt-3 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-xs font-bold text-navy dark:text-white">Examinar archivos</button>
                    <div id="file-preview-zone" class="hidden mt-3 flex items-center justify-center gap-2 text-xs font-semibold text-emerald-600">
                      <span id="file-icon">PDF</span> <span id="file-name" class="underline">archivo.pdf</span>
                    </div>
                  </div>
                  <div id="offer-default-image-preview" class="hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-[180px_1fr]">
                      <div class="relative min-h-[140px] bg-slate-100">
                        <img id="offer-default-image-preview-img" src="<?php echo esc_url($captacion_media['property_defaults']['piso']); ?>" data-virtual-type="Piso" alt="Imagen predeterminada" width="640" height="666" class="absolute inset-0 h-full w-full object-cover" loading="lazy" decoding="async" />
                      </div>
                      <div class="p-4">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Portada temática activa</span>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Esta imagen se mostrará automáticamente si no subes una fotografía propia.</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button type="button" onclick="setOfferStep(1)" class="px-5 py-3 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold hover:bg-slate-100 transition-all">
                    ← Volver al Paso 1
                  </button>
                  <button type="button" onclick="setOfferStep(3)" class="px-7 py-3.5 bg-blue hover:bg-blue-dark text-white rounded-xl text-xs font-black shadow-md shadow-blue/25 hover:scale-105 transition-all">
                    Continuar al Paso 3: Colaboración y Envío →
                  </button>
                </div>
              </div>

              <!-- PASO 3 OFERTA: COLABORACIÓN, IA Y ENVÍO -->
              <div id="offer-step-3" class="hidden space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Reparto de honorarios propuesto *</label>
                    <select id="offer-fee" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20">
                      <option value="50/50">Reparto 50/50</option><option value="Porcentaje personalizado">Porcentaje personalizado</option><option value="Importe fijo">Importe fijo</option><option value="A consultar">A consultar</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Tipo de encargo / exclusividad *</label>
                    <select id="offer-mandate" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20">
                      <option value="Sí, con exclusividad">Sí, con exclusividad</option>
                      <option value="Encargo de agente único">Encargo de agente único</option>
                      <option value="Exclusiva compartida">Exclusiva compartida</option>
                      <option value="No, nota de encargo abierta">No, nota de encargo abierta</option>
                      <option value="Sin exclusiva formalizada">Sin exclusiva formalizada</option>
                      <option value="Pendiente de confirmar">Pendiente de confirmar</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Condición de la propiedad *</label>
                    <select id="offer-condition" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20"></select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Urgencia *</label>
                    <select id="offer-urgency" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20">
                      <option value="Media">Media</option><option value="Alta">Alta</option><option value="Baja">Baja</option><option value="Sin urgencia definida">Sin urgencia</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Documentación *</label>
                    <select id="offer-docs" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue/20">
                      <option value="Pendiente de completar">Pendiente de completar</option><option value="Nota simple">Nota simple</option><option value="Nota simple y certificado energético">Nota simple + CEE</option><option value="Expediente completo">Expediente completo</option>
                    </select>
                  </div>
                </div>

                <div>
                  <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400">Descripción de la captación *</label>
                    <button type="button" onclick="runAIEnhanceOfferListing()" class="text-[11px] font-bold text-blue hover:text-blue-dark flex items-center gap-1">
                      <span>✨</span> Mejorar con IA Vera →
                    </button>
                  </div>
                  <textarea id="offer-description" required minlength="30" rows="3" placeholder="Describe los aspectos clave de la oportunidad (altura, estado, vistas, potencial, etc.)" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20"></textarea>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Referencia catastral (opcional)</label>
                  <input id="offer-cadastral-reference" type="text" maxlength="20" placeholder="20 caracteres, sin espacios" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-blue/20" />
                </div>

                <div class="captacion-form-actions flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                  <label class="legal-consent-box flex items-start gap-2 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed max-w-xl">
                    <input id="offer-pub-compliance" type="checkbox" required class="mt-0.5" />
                    <span>Declaro que dispongo de base legítima para compartir esta oportunidad y que acepto las <a href="<?php echo esc_url(home_url('/normas-publicacion')); ?>" class="legal-link text-blue">Normas de publicación</a>. *</span>
                  </label>
                  <div class="flex items-center gap-3 shrink-0">
                    <button type="button" onclick="setOfferStep(2)" class="px-5 py-3.5 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold hover:bg-slate-100 transition-all">
                      ← Volver
                    </button>
                    <button type="submit" class="px-8 py-3.5 bg-blue hover:bg-blue-dark text-white rounded-xl text-xs font-black shadow-lg shadow-blue/25 hover:scale-105 transition-all">
                      ✨ Publicar captación
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- CONTENEDOR DE FORMULARIO DE DEMANDA EN 3 PASOS -->
          <div id="publish-need-wrapper" class="hidden bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 sm:p-8">
            <!-- STEPPER PROGRESS BAR DEMANDA -->
            <div class="mb-8 pb-6 border-b border-slate-100 dark:border-slate-800">
              <div class="flex items-center justify-between max-w-xl mx-auto text-xs font-black">
                <div class="flex items-center gap-2" id="need-step-ind-1">
                  <span class="w-8 h-8 rounded-full flex items-center justify-center bg-emerald-600 text-white shadow-md text-xs font-black step-circle">1</span>
                  <span class="step-label text-emerald-600 dark:text-emerald-400 font-black hidden sm:inline">Criterios clave</span>
                </div>
                <div class="h-0.5 flex-1 mx-3 bg-slate-200 dark:bg-slate-700"></div>
                <div class="flex items-center gap-2 opacity-50" id="need-step-ind-2">
                  <span class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold step-circle">2</span>
                  <span class="step-label text-slate-400 font-medium hidden sm:inline">Ubicación y superficie</span>
                </div>
                <div class="h-0.5 flex-1 mx-3 bg-slate-200 dark:bg-slate-700"></div>
                <div class="flex items-center gap-2 opacity-50" id="need-step-ind-3">
                  <span class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold step-circle">3</span>
                  <span class="step-label text-slate-400 font-medium hidden sm:inline">Perfil y publicación</span>
                </div>
              </div>
            </div>

            <form id="need-publication-form" class="captacion-professional-form space-y-6">
              <!-- PASO 1 DEMANDA: CRITERIOS CLAVE -->
              <div id="need-step-1" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Tipo de inmueble buscado *</label>
                    <select id="need-pub-type" required onchange="updatePropertyFormDynamics('need')" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500/20">
                      <option value="Piso">Piso</option>
                      <option value="Casa / chalet">Casa / chalet</option>
                      <option value="Ático">Ático</option>
                      <option value="Dúplex">Dúplex</option>
                      <option value="Apartamento">Apartamento</option>
                      <option value="Estudio">Estudio</option>
                      <option value="Finca rústica con vivienda">Finca rústica con vivienda</option>
                      <option value="Edificio residencial">Edificio residencial</option>
                      <option value="Local comercial">Local comercial</option>
                      <option value="Nave">Nave</option>
                      <option value="Oficina">Oficina</option>
                      <option value="Terreno / solar">Terreno / solar</option>
                      <option value="Garaje">Garaje</option>
                      <option value="Trastero">Trastero</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Operación *</label>
                    <select id="need-pub-operation" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500/20">
                      <option value="Venta">Venta</option>
                      <option value="Alquiler">Alquiler</option>
                      <option value="Alquiler con Opción a Compra">Alquiler con Opción a Compra</option>
                      <option value="Otra necesidad inmobiliaria">Otra necesidad inmobiliaria</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                  <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Título de la búsqueda *</label>
                    <input type="text" id="need-pub-title" required minlength="8" placeholder="Ej: Busco piso 3 hab en Madrid centro para inversor" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500/20" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Presupuesto máximo (€) *</label>
                    <input type="number" id="need-pub-budget" required min="1" placeholder="Ej: 300000" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500/20" />
                  </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button type="button" onclick="setNeedStep(2)" class="px-7 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md shadow-emerald-600/25 hover:scale-105 transition-all">
                    Continuar al Paso 2: Ubicación y Superficie →
                  </button>
                </div>
              </div>

              <!-- PASO 2 DEMANDA: UBICACIÓN Y SUPERFICIE -->
              <div id="need-step-2" class="hidden space-y-6">
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700 space-y-4">
                  <div>
                    <span class="block text-sm font-black text-navy dark:text-white">Ubicación del inmueble</span>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Define el encaje territorial de la búsqueda. Los datos de contacto de tu cliente no se revelan.</p>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Comunidad *</label>
                      <select id="need-pub-ccaa-sel" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20">
                        <option value="">Cargando...</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Provincia *</label>
                      <select id="need-pub-province-sel" required disabled class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20 disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">Selecciona CCAA</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Municipio *</label>
                      <input id="need-pub-municipality-sel" type="text" list="need-pub-municipality-list" required disabled autocomplete="off" placeholder="Municipio..." class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20 disabled:bg-slate-100 disabled:text-slate-400" />
                      <datalist id="need-pub-municipality-list"></datalist>
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">C.P. (opcional)</label>
                      <input type="text" id="need-pub-postal-code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" placeholder="Ej: 28001" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Barrio o zona (opcional)</label>
                      <input type="text" id="need-pub-locality" placeholder="Ej: Recoletos" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Habitaciones mínimas *</label>
                    <input type="number" id="need-pub-bedrooms" required min="0" step="1" placeholder="Ej: 3" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500/20" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Baños mínimos *</label>
                    <input type="number" id="need-pub-bathrooms" required min="0" step="1" placeholder="Ej: 2" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500/20" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Superficie mínima (m²) *</label>
                    <input type="number" id="need-pub-surface" required min="1" step="1" placeholder="Ej: 85" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500/20" />
                  </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button type="button" onclick="setNeedStep(1)" class="px-5 py-3 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold hover:bg-slate-100 transition-all">
                    ← Volver al Paso 1
                  </button>
                  <button type="button" onclick="setNeedStep(3)" class="px-7 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md shadow-emerald-600/25 hover:scale-105 transition-all">
                    Continuar al Paso 3: Perfil y Publicación →
                  </button>
                </div>
              </div>

              <!-- PASO 3 DEMANDA: PERFIL, CONDICIONES Y PUBLICACIÓN -->
              <div id="need-step-3" class="hidden space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Tipo de cliente *</label>
                    <select id="need-pub-buyer-type" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20">
                      <option value="Profesional">Profesional</option><option value="Particular">Particular</option><option value="Inversor">Inversor</option><option value="Otros">Otros</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Urgencia *</label>
                    <select id="need-pub-urgency" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20">
                      <option value="Alta">Alta</option><option value="Media">Media</option><option value="Baja">Baja</option><option value="Sin urgencia definida">Sin urgencia</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Financiación *</label>
                    <select id="need-pub-funding" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20">
                      <option value="Fondos propios / Al contado">Al contado</option><option value="Financiación preaprobada">Preaprobada</option><option value="Sujeto a hipoteca">Con hipoteca</option><option value="No requiere">No requiere</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Colaboración *</label>
                    <select id="need-pub-fee" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20">
                      <option value="50/50">Reparto 50/50</option><option value="A consultar">A consultar</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Condición del inmueble *</label>
                    <select id="need-pub-condition" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20"></select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Mandato aceptado *</label>
                    <select id="need-pub-mandate" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20">
                      <option value="Con exclusividad">Con exclusividad</option><option value="Encargo de agente único">Encargo de agente único</option><option value="Exclusiva compartida">Exclusiva compartida</option><option value="Nota de encargo abierta">Nota de encargo abierta</option><option value="Sin exclusiva formalizada">Sin exclusiva formalizada</option><option value="Cualquiera">Cualquiera</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Documentación requerida *</label>
                    <select id="need-pub-docs" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-emerald-500/20">
                      <option value="Nota simple únicamente">Nota simple únicamente</option><option value="Nota simple + planos">Nota simple + planos</option><option value="Nota simple + CEE">Nota simple + CEE</option><option value="Expediente completo">Expediente completo</option><option value="No califica">No califica</option>
                    </select>
                  </div>
                </div>

                <div>
                  <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400">Descripción detallada de la necesidad *</label>
                    <button type="button" onclick="runAIEnhanceNeedListing()" class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1 hover:underline">
                      <span>✨</span> Mejorar con IA Vera →
                    </button>
                  </div>
                  <textarea id="need-pub-desc" required minlength="30" rows="3" placeholder="Describe los requisitos esenciales del cliente (altura, salida de humos, orientación, vistas, etc.)" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-emerald-500/20"></textarea>
                </div>

                <div class="captacion-form-actions flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                  <label class="legal-consent-box flex items-start gap-2 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed max-w-xl">
                    <input id="need-pub-compliance" type="checkbox" required class="mt-0.5" />
                    <span>Declaro que la demanda es lícita, exacta y profesional; y acepto las <a href="<?php echo esc_url(home_url('/normas-publicacion')); ?>" class="legal-link text-emerald-600">Normas de publicación</a>. *</span>
                  </label>
                  <div class="flex items-center gap-3 shrink-0">
                    <button type="button" onclick="setNeedStep(2)" class="px-5 py-3.5 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold hover:bg-slate-100 transition-all">
                      ← Volver
                    </button>
                    <button type="submit" class="px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-lg shadow-emerald-600/25 hover:scale-105 transition-all">
                      ✨ Publicar demanda
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- PÁGINA 3 ALIAS: OFRECER CAPTACIÓN (REDIRECCIONA/MUESTRA PUBLICAR EN MODO OFERTA) -->
    <div id="page-ofrecer-captacion" class="page-section hidden"></div>

    <!-- PÁGINA ALIAS: PUBLICAR DEMANDA (REDIRECCIONA/MUESTRA PUBLICAR EN MODO DEMANDA) -->
    <div id="page-publicar-demanda" class="page-section hidden"></div>
    <!-- PÁGINA 4: CÓMO FUNCIONA -->
    <div id="page-como-funciona" class="page-section <?php echo $captacion_active_page_id === 'page-como-funciona' ? '' : 'hidden'; ?>">
      <section class="py-10 sm:py-14 max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 space-y-16 sm:space-y-20">
        
        <!-- Banner Principal Panorámico -->
        <header class="internal-page-banner internal-page-banner--how w-full shadow-lg rounded-3xl overflow-hidden">
          <img class="internal-page-banner__image" src="<?php echo esc_url($captacion_media_url('media/Vera_asi-funciona_01-scaled.png')); ?>" alt="Así funciona Compra Captación" width="1600" height="893" loading="lazy" decoding="async">
          <div class="internal-page-banner__content p-6 sm:p-10 lg:p-12">
            <span class="internal-page-banner__kicker text-xs sm:text-sm font-bold uppercase tracking-wider text-blue-neon">Guía para profesionales inmobiliarios</span>
            <h1 class="internal-page-banner__title text-3xl sm:text-4xl lg:text-5xl font-black text-white mt-2 leading-tight">Así funciona Compra Captación</h1>
            <p class="internal-page-banner__support text-base sm:text-lg text-slate-200 mt-3 max-w-3xl leading-relaxed">La red de colaboración entre profesionales inmobiliarios para cerrar operaciones al 50/50, blindar tus captaciones y multiplicar tus ventas sin intermediarios no autorizados.</p>
          </div>
        </header>

        <!-- SECCIÓN 1: EL PROBLEMA DEL MERCADO VS NUESTRA SOLUCIÓN -->
        <div class="w-full space-y-8">
          <div class="text-center max-w-4xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">El reto del sector</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">¿Por qué se pierden tantas ventas inmobiliarias cada día?</h2>
            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">En el día a día de una agencia inmobiliaria se escapan oportunidades de negocio por no contar con una plataforma segura y directa de colaboración:</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
            <!-- Dolor 1 -->
            <div class="p-7 rounded-3xl bg-rose-50/80 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/50 flex flex-col justify-between space-y-5 transition hover:shadow-md">
              <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-rose-500 text-white font-black text-lg flex items-center justify-center shadow-md">1</div>
                <h3 class="text-lg sm:text-xl font-bold text-navy dark:text-white">Tienes la propiedad, pero te falta el comprador</h3>
                <p class="text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed">Has invertido tiempo y recursos en captar una vivienda fantástica, pero tu cartera de compradores no encaja. Pasan los meses, la exclusiva corre peligro y el propietario se impacienta.</p>
              </div>
              <div class="pt-3 border-t border-rose-200/60 dark:border-rose-900/40">
                <span class="text-xs font-bold text-rose-600 dark:text-rose-400">❌ Oportunidad y honorarios estancados</span>
              </div>
            </div>

            <!-- Dolor 2 -->
            <div class="p-7 rounded-3xl bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 flex flex-col justify-between space-y-5 transition hover:shadow-md">
              <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white font-black text-lg flex items-center justify-center shadow-md">2</div>
                <h3 class="text-lg sm:text-xl font-bold text-navy dark:text-white">Tienes al comprador listo, pero te falta la casa</h3>
                <p class="text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed">Entra un cliente por la puerta con el presupuesto y la hipoteca preaprobada buscando un piso muy concreto. No lo tienes en stock, no encuentras nada en tu zona y el cliente se va a otra agencia.</p>
              </div>
              <div class="pt-3 border-t border-amber-200/60 dark:border-amber-900/40">
                <span class="text-xs font-bold text-amber-600 dark:text-amber-400">❌ Cliente y honorarios perdidos</span>
              </div>
            </div>

            <!-- Dolor 3 -->
            <div class="p-7 rounded-3xl bg-purple-50/80 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-900/50 flex flex-col justify-between space-y-5 transition hover:shadow-md">
              <div class="space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-purple-500 text-white font-black text-lg flex items-center justify-center shadow-md">3</div>
                <h3 class="text-lg sm:text-xl font-bold text-navy dark:text-white">Miedo a que te "puenteen" o quiten la captación</h3>
                <p class="text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed">Colaborar siempre ha generado recelo por el riesgo de compartir datos sensibles, direcciones exactas o teléfonos del propietario sin un marco legal seguro y transparente.</p>
              </div>
              <div class="pt-3 border-t border-purple-200/60 dark:border-purple-900/40">
                <span class="text-xs font-bold text-purple-600 dark:text-purple-400">❌ Falta de protección jurídica</span>
              </div>
            </div>
          </div>

          <!-- Bloque Solución / Objetivo Ancho Completo -->
          <div class="w-full p-8 sm:p-10 rounded-3xl bg-gradient-to-r from-navy via-slate-900 to-blue-900 text-white shadow-xl flex flex-col lg:flex-row items-center justify-between gap-8 border border-blue-700/40">
            <div class="space-y-3 max-w-4xl">
              <span class="text-xs sm:text-sm font-bold text-blue-neon uppercase tracking-wider">🎯 La Solución Definitiva</span>
              <h3 class="text-xl sm:text-2xl lg:text-3xl font-black text-white leading-snug">Unir fuerzas entre profesionales para que nadie pierda una operación</h3>
              <p class="text-sm sm:text-base text-slate-300 leading-relaxed">Compra Captación conecta al profesional que tiene el inmueble con el que tiene al comprador cualificado. Blindamos los datos de tus propietarios, facilitamos los contratos homologados y os repartís los honorarios de forma limpia y transparente.</p>
            </div>
            <a href="#/registro" class="whitespace-nowrap px-8 py-4 rounded-2xl bg-blue hover:bg-blue-600 text-white font-bold text-sm uppercase tracking-wider transition-all shadow-lg hover:shadow-blue/50 flex items-center gap-3">
              <span>Probar gratis con 3 créditos</span>
              <span>→</span>
            </a>
          </div>
        </div>

        <!-- SECCIÓN 2: EL PROCESO PASO A PASO (1 AL 5) -->
        <div class="w-full space-y-10">
          <div class="text-center max-w-4xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">Paso a paso</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">¿Cómo es el proceso de colaboración?</h2>
            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">Un flujo transparente, ágil y 100% legal diseñado para cerrar operaciones en tiempo récord.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 w-full">
            
            <!-- Paso 1 -->
            <div class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4 hover:border-blue transition-all">
              <div class="space-y-3">
                <span class="w-10 h-10 rounded-2xl bg-blue text-white font-black text-base flex items-center justify-center shadow-md">1</span>
                <h3 class="text-base sm:text-lg font-bold text-navy dark:text-white">Publica lo que tienes o buscas</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Sube tus inmuebles con fotos y características, o registra la búsqueda de tu cliente comprador. <strong>Publicar es 100% gratuito</strong>.
                </p>
              </div>
              <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">✓ Sin coste por publicar</span>
            </div>

            <!-- Paso 2 -->
            <div class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4 hover:border-blue transition-all">
              <div class="space-y-3">
                <span class="w-10 h-10 rounded-2xl bg-indigo-600 text-white font-black text-base flex items-center justify-center shadow-md">2</span>
                <h3 class="text-base sm:text-lg font-bold text-navy dark:text-white">Protección con "Datos Ciegos"</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Tus captaciones están protegidas. Dirección exacta, piso y teléfono del vendedor están bajo candado. Solo se muestra la zona orientativa.
                </p>
              </div>
              <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">🔒 Blindaje anti-puenteo</span>
            </div>

            <!-- Paso 3 -->
            <div class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4 hover:border-blue transition-all">
              <div class="space-y-3">
                <span class="w-10 h-10 rounded-2xl bg-amber-500 text-white font-black text-base flex items-center justify-center shadow-md">3</span>
                <h3 class="text-base sm:text-lg font-bold text-navy dark:text-white">Radar y Mapa de España</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Explora el mapa en más de 8.100 municipios. Filtra por zona, precio y tipología con detección automática de coincidencias.
                </p>
              </div>
              <span class="text-xs font-bold text-amber-600 dark:text-amber-400">📍 Cruce automático</span>
            </div>

            <!-- Paso 4 -->
            <div class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4 hover:border-blue transition-all">
              <div class="space-y-3">
                <span class="w-10 h-10 rounded-2xl bg-purple-600 text-white font-black text-base flex items-center justify-center shadow-md">4</span>
                <h3 class="text-base sm:text-lg font-bold text-navy dark:text-white">Desbloqueo y Contrato 50/50</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Desbloqueas el contacto con 1 crédito del monedero y descargas la plantilla del <strong>Acuerdo de Colaboración 50/50</strong> y de Confidencialidad.
                </p>
              </div>
              <span class="text-xs font-bold text-purple-600 dark:text-purple-400">⚖️ Seguridad jurídica 100%</span>
            </div>

            <!-- Paso 5 -->
            <div class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4 hover:border-blue transition-all">
              <div class="space-y-3">
                <span class="w-10 h-10 rounded-2xl bg-emerald-600 text-white font-black text-base flex items-center justify-center shadow-md">5</span>
                <h3 class="text-base sm:text-lg font-bold text-navy dark:text-white">Visita, Notaría y Cobro</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Realizáis la visita conjunta con el comprador, firmáis en notaría y los honorarios pactados se dividen limpiamente entre las dos agencias.
                </p>
              </div>
              <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">💰 Operación cerrada</span>
            </div>

          </div>
        </div>

        <div class="max-w-4xl mx-auto mb-10 p-5 sm:p-6 rounded-3xl border border-blue/20 bg-blue/5 dark:bg-blue/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div><strong class="block text-base font-black text-navy dark:text-white">¿Quieres que Vera te guíe?</strong><p class="text-xs text-slate-600 dark:text-slate-300 mt-1">Te explica publicar, encontrar coincidencias, proteger el contacto y cerrar al 50/50.</p></div>
          <button type="button" onclick="openVeraWithContext('how-it-works', 'flow-overview', 0, 'Vera, guíame paso a paso por el flujo completo de Compra Captación.')" class="shrink-0 px-4 py-2.5 rounded-xl bg-blue text-white text-xs font-black">Abrir Vera</button>
        </div>

        <!-- SECCIÓN 3: LAS 2 MODALIDADES DE GANANCIA -->
        <div class="w-full space-y-8">
          <div class="text-center max-w-4xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">Modelos de acuerdo</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">Dos formas de rentabilizar tus contactos</h2>
            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">Elige en cada operación el modelo que mejor encaje con tu agencia:</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 w-full">
            <!-- Modalidad A: 50/50 -->
            <div class="p-8 sm:p-10 rounded-3xl bg-white dark:bg-slate-900 border-2 border-blue/40 dark:border-blue-800 shadow-md space-y-6 relative overflow-hidden transition hover:shadow-xl">
              <div class="absolute top-0 right-0 bg-blue text-white text-xs font-black uppercase tracking-wider px-5 py-2 rounded-bl-2xl">
                Más utilizada
              </div>
              <div class="w-14 h-14 rounded-2xl bg-blue/10 dark:bg-blue/20 text-blue font-black text-2xl flex items-center justify-center">
                🤝
              </div>
              <div class="space-y-3">
                <h3 class="text-xl sm:text-2xl font-bold text-navy dark:text-white">1. Colaboración 50/50 de Honorarios</h3>
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                  Una agencia aporta la propiedad captada y la otra agencia aporta el cliente comprador cualificado. Al formalizar la venta en notaría, los honorarios profesionales se dividen al 50% de manera acordada y garantizada por contrato.
                </p>
              </div>
              <div class="pt-5 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <div class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 font-semibold">
                  <span class="text-emerald-500 font-bold">✓</span> Multiplica las ventas cerradas en tu misma ciudad
                </div>
                <div class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 font-semibold">
                  <span class="text-emerald-500 font-bold">✓</span> Respaldado por el contrato oficial de colaboración
                </div>
              </div>
            </div>

            <!-- Modalidad B: Cesión 100% -->
            <div class="p-8 sm:p-10 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md space-y-6 relative transition hover:shadow-xl">
              <div class="w-14 h-14 rounded-2xl bg-purple-100 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 font-black text-2xl flex items-center justify-center">
                📦
              </div>
              <div class="space-y-3">
                <h3 class="text-xl sm:text-2xl font-bold text-navy dark:text-white">2. Cesión o Traspaso del 100% de la Operación</h3>
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                  ¿Te ha entrado un encargo de un cliente en otra provincia o en una localidad donde no tienes presencia física? Cede la gestión íntegra del inmueble a una agencia de esa zona a cambio de unos honorarios fijos o porcentaje pactado de traspaso.
                </p>
              </div>
              <div class="pt-5 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <div class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 font-semibold">
                  <span class="text-emerald-500 font-bold">✓</span> Rentabiliza captaciones fuera de tu radio habitual de trabajo
                </div>
                <div class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300 font-semibold">
                  <span class="text-emerald-500 font-bold">✓</span> Da un servicio profesional a tu cliente sin tener que desplazarte
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECCIÓN 4: ASISTENTE VERA CON INTELIGENCIA ARTIFICIAL (PANORÁMICA Y TOTALMENTE ADAPTATIVA) -->
        <div class="w-full p-8 sm:p-12 lg:p-14 rounded-3xl bg-gradient-to-br from-blue-50/90 via-indigo-50/60 to-slate-50 dark:bg-gradient-to-br dark:from-slate-900 dark:via-navy dark:to-slate-950 border border-blue-200/80 dark:border-slate-800 shadow-xl transition-colors">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center w-full">
            
            <div class="lg:col-span-8 space-y-5">
              <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue/10 dark:bg-blue/20 text-blue dark:text-blue-neon text-xs sm:text-sm font-bold">
                <span>🤖</span> Asistente Inmobiliaria con Inteligencia Artificial
              </div>
              <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white leading-tight">
                Conoce a Vera: Tu copiloto inmobiliaria disponible 24/7
              </h2>
              <p class="text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed">
                Vera es una asistente virtual entrenada específicamente en el mercado inmobiliario español, acuerdos entre profesionales y redacción comercial. Te asiste en el día a día directamente desde la plataforma sin coste añadido.
              </p>
              
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-3">
                <div class="p-5 rounded-2xl bg-white/90 dark:bg-slate-800/80 border border-blue-100 dark:border-slate-700 shadow-sm">
                  <div class="text-2xl mb-2">✍️</div>
                  <h4 class="text-sm font-bold text-navy dark:text-white">Redacción de anuncios</h4>
                  <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Textos atractivos y persuasivos para portales y redes en segundos.</p>
                </div>
                <div class="p-5 rounded-2xl bg-white/90 dark:bg-slate-800/80 border border-blue-100 dark:border-slate-700 shadow-sm">
                  <div class="text-2xl mb-2">🧮</div>
                  <h4 class="text-sm font-bold text-navy dark:text-white">Cálculo de honorarios</h4>
                  <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Cálculo exacto del reparto al 50/50 y estimación de rentabilidades.</p>
                </div>
                <div class="p-5 rounded-2xl bg-white/90 dark:bg-slate-800/80 border border-blue-100 dark:border-slate-700 shadow-sm">
                  <div class="text-2xl mb-2">⚖️</div>
                  <h4 class="text-sm font-bold text-navy dark:text-white">Dudas y normativas</h4>
                  <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">Respuestas rápidas sobre acuerdos de arras, ley de vivienda y contratos.</p>
                </div>
              </div>
            </div>

            <div class="lg:col-span-4 flex flex-col items-center justify-center text-center p-8 rounded-3xl bg-white dark:bg-slate-800/60 border border-blue-100 dark:border-slate-700 shadow-lg space-y-4">
              <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-blue to-indigo-600 text-white flex items-center justify-center text-3xl font-black shadow-lg">
                V
              </div>
              <div>
                <h4 class="text-lg font-bold text-navy dark:text-white">Asistente Vera</h4>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Inteligencia Inmobiliaria Española</p>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">Pruébala ahora mismo para resolver cualquier consulta o redactar una ficha de captación.</p>
              <a href="#/panel" class="w-full py-3.5 px-6 rounded-2xl bg-blue hover:bg-blue-600 text-white font-bold text-sm transition-all shadow-md hover:shadow-blue/50 text-center">
                Abrir Vera en el Panel
              </a>
            </div>

          </div>
        </div>

        <!-- SECCIÓN 5: UN ANTES Y UN DESPUÉS PARA TU AGENCIA (BENEFICIOS) -->
        <div class="w-full space-y-8">
          <div class="text-center max-w-4xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">Ventajas para tu negocio</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">Un antes y un después para el día a día de tu agencia</h2>
            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">Por qué miles de profesionales eligen colaborar en Compra Captación:</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 w-full">
            <div class="p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3 transition hover:shadow-md">
              <span class="text-3xl">🚀</span>
              <h3 class="text-lg font-bold text-navy dark:text-white">Multiplica tu catálogo al instante</h3>
              <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Accede a cientos de propiedades compartidas por otros compañeros para ofrecérselas a tus compradores sin gastar meses en captación.</p>
            </div>
            <div class="p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3 transition hover:shadow-md">
              <span class="text-3xl">💰</span>
              <h3 class="text-lg font-bold text-navy dark:text-white">Rentabiliza a todos tus compradores</h3>
              <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">No pierdas a ningún cliente interesado. Si tú no dispones de la vivienda que busca, encuéntrala en la red en cuestión de minutos.</p>
            </div>
            <div class="p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3 transition hover:shadow-md">
              <span class="text-3xl">🛡️</span>
              <h3 class="text-lg font-bold text-navy dark:text-white">Seguridad y tranquilidad total</h3>
              <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Datos ciegos para que nadie te quite clientes, y contratos oficiales homologados para garantizar el cobro de tus honorarios tras la firma.</p>
            </div>
            <div class="p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3 transition hover:shadow-md">
              <span class="text-3xl">⚡</span>
              <h3 class="text-lg font-bold text-navy dark:text-white">Ahorro brutal de tiempo</h3>
              <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">Filtros geográficos exactos, mapa interactivo con más de 8.100 municipios y asistencia de IA para que te enfoques en cerrar operaciones.</p>
            </div>
          </div>
        </div>

        <!-- SECCIÓN 6: PREGUNTAS FRECUENTES (FAQ A ANCHO COMPLETO) -->
        <div class="w-full space-y-10">
          <div class="text-center max-w-4xl mx-auto space-y-3">
            <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">Respuestas claras</span>
            <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">Preguntas frecuentes sobre el funcionamiento</h3>
            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">Todo lo que necesitas saber antes de empezar a colaborar con otros profesionales.</p>
          </div>

          <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-5">
            
            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Qué es Compra Captación y en qué se diferencia de un portal inmobiliario?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Compra Captación no es un portal de anuncios para particulares. Es una plataforma profesional exclusiva para agentes y agencias inmobiliarias. Su propósito es compartir captaciones con datos sensibles protegidos y demandas de compradores para cerrar ventas conjuntas y repartir honorarios al 50/50.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Cómo se protegen mis captaciones para evitar que me roben la propiedad?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                La plataforma implementa un sistema estricto de <strong>datos ciegos</strong>. La dirección exacta, piso, puerta, datos registrales y teléfono del propietario nunca se publican de forma abierta. Los demás profesionales solo ven la zona orientativa y las características. Para acceder al expediente completo, el otro agente debe identificarse, desbloquear la oportunidad y aceptar el acuerdo legal de colaboración.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Cómo se formaliza el reparto de honorarios al 50/50?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Al desbloquear una oportunidad, la plataforma te facilita la plantilla homologada del <strong>Acuerdo de Colaboración de Honorarios Inmobiliarios</strong> y el <strong>Acuerdo de Confidencialidad (NDA)</strong>. Ambas partes firman las condiciones antes de realizar la visita con el cliente, asegurando legalmente el cobro del 50% de los honorarios tras la firma en notaría.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Cuánto cuesta registrarse y cómo funciona el monedero?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                El registro es <strong>100% gratuito e incluye 3 créditos de bienvenida</strong> (válidos durante tus primeros 30 días, no acumulables) para probar la plataforma sin introducir tarjeta bancaria. Publicar inmuebles y registrar demandas es siempre gratis. Además, con nuestro modelo circular, cada vez que otra agencia desbloquea una captación tuya ganas <strong>+0.5 créditos</strong>.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Es obligatorio tener contrato de exclusiva para publicar?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Es altamente recomendable contar con nota de encargo o exclusiva para garantizar la máxima eficacia en la operación. No obstante, puedes compartir cualquier oportunidad en la que cuentes con autorización directa y legal del propietario para gestionar la venta.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Qué ocurre si tengo un inmueble fuera de mi ciudad o provincia?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Puedes recurrir a la modalidad de <strong>Cesión o Traspaso del 100%</strong>. Publicas la captación para que un agente de esa localidad se encargue de las visitas presenciales y trámites a cambio de una tarifa de traspaso o porcentaje previamente acordado.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Cómo me ayuda la asistente de inteligencia artificial "Vera"?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Vera redacta descripciones persuasivas de tus inmuebles para que resalten en portales, calcula repartos de honorarios y tablas de rentabilidad, y responde al instante dudas sobre normativas inmobiliarias, contratos de arras y cláusulas legales en España.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-4 py-3 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Existen cuotas mensuales obligatorias o permanencias?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                No hay permanencias obligatorias. Puedes operar mediante recargas de créditos según tu volumen de trabajo o suscribirte a planes profesionales con ventajas añadidas que puedes cancelar en cualquier momento con un solo clic desde tu panel.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Quién puede formar parte de la plataforma?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Está pensada para agentes inmobiliarios autónomos, agencias independientes, franquicias, captadores profesionales e inversores que buscan dinamizar su cartera mediante acuerdos formales de colaboración entre profesionales.
              </div>
            </details>

            <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
              <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base sm:text-lg font-bold text-navy dark:text-white hover:text-blue dark:hover:text-blue-neon transition-colors cursor-pointer">
                <span>¿Cómo contacto con el equipo de soporte si tengo dudas?</span>
                <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
              </summary>
              <div class="px-5 pb-5 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Puedes escribirnos directamente desde la sección de <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="text-blue font-bold underline hover:text-blue-dark">Contacto y soporte</a> o enviar un correo electrónico a <a href="mailto:<?php echo esc_attr($captacion_contact_email); ?>" class="text-blue font-bold underline hover:text-blue-dark"><?php echo esc_html($captacion_contact_email); ?></a>.
              </div>
            </details>

          </div>
        </div>

        <!-- CTA FINAL PANORÁMICO -->
        <div class="w-full text-center py-12 sm:py-16 px-6 sm:px-12 rounded-3xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-6 shadow-sm">
          <div class="max-w-3xl mx-auto space-y-3">
            <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">¿Listo para cerrar más operaciones colaborando?</h3>
            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
              Crea tu cuenta en menos de 1 minuto, recibe tus 3 créditos de bienvenida (30 días) y empieza a explorar las captaciones y demandas activas en tu zona.
            </p>
          </div>
          <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
            <a href="#/registro" class="px-8 py-4 rounded-2xl bg-blue hover:bg-blue-600 text-white font-bold text-sm uppercase tracking-wider transition-all shadow-lg hover:shadow-blue/50">
              Crear cuenta gratis (3 créditos)
            </a>
            <a href="#/oportunidades" class="px-8 py-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-navy dark:text-white font-bold text-sm uppercase tracking-wider hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
              Ver mapa de oportunidades
            </a>
          </div>
        </div>

      </section>
    </div>

    <!-- PÁGINA 5: HUB DE OPORTUNIDADES -->
    <div id="page-oportunidades" class="page-section hidden">
      <section class="py-12 bg-slate-50 dark:bg-slate-950 transition-colors">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="mb-8 text-center">
            <span class="text-[10px] font-black uppercase tracking-[0.18em] text-blue">Oportunidades</span>
            <h1 class="mt-3 text-3xl sm:text-4xl font-black text-navy">¿Qué quieres encontrar?</h1>
            <p class="mx-auto mt-4 max-w-2xl text-sm sm:text-base leading-relaxed text-slate-600">Aquí puedes consultar inmuebles que otros profesionales han compartido o ver qué necesitan otros profesionales para sus clientes. Elige una opción para continuar.</p>
          </div>
          <div class="grid gap-6 md:grid-cols-2">
            <!-- Tarjeta 1: Propiedades Disponibles -->
            <div class="group rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 shadow-sm transition hover:-translate-y-1 hover:border-blue hover:shadow-xl flex flex-col justify-between">
              <div>
                <div class="w-14 h-14 rounded-2xl bg-blue/10 text-blue dark:text-blue-light flex items-center justify-center text-3xl mb-5">🏠</div>
                <span class="text-[11px] font-black uppercase tracking-wider text-blue">Cartera de Inmuebles en Exclusiva</span>
                <h2 class="mt-2 text-2xl font-black text-navy dark:text-white">Propiedades Disponibles</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Explora captaciones exclusivas compartidas por agencias y profesionales inmobiliarios en toda España con protección integral de datos ciegos.</p>
              </div>
              <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <a href="<?php echo esc_url(home_url('/propiedades')); ?>" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue hover:bg-blue-dark px-6 py-3.5 text-xs font-black text-white shadow-md shadow-blue/20 hover:scale-105 transition-all">
                  <span>Marketplace de Propiedades Disponibles</span>
                  <span>↗</span>
                </a>
                <span class="text-[11px] font-bold text-slate-400 text-center sm:text-right">Nueva pestaña</span>
              </div>
            </div>

            <!-- Tarjeta 2: Demandas de Compradores -->
            <div class="group rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 shadow-sm transition hover:-translate-y-1 hover:border-emerald-500 hover:shadow-xl flex flex-col justify-between">
              <div>
                <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl mb-5">🔎</div>
                <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Demanda Cualificada de Compradores</span>
                <h2 class="mt-2 text-2xl font-black text-navy dark:text-white">Demandas</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Consulta los requerimientos de compradores e inversores solventes gestionados por otros profesionales y comparte una opción compatible.</p>
              </div>
              <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <a href="<?php echo esc_url(home_url('/demandas')); ?>" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-6 py-3.5 text-xs font-black text-white shadow-md shadow-emerald-600/20 hover:scale-105 transition-all">
                  <span>Marketplace de Demandas</span>
                  <span>↗</span>
                </a>
                <span class="text-[11px] font-bold text-slate-400 text-center sm:text-right">Nueva pestaña</span>
              </div>
            </div>
          </div>

        <div class="mt-14 rounded-3xl bg-gradient-to-br from-slate-100 via-blue-50/40 to-slate-200 dark:from-slate-900 dark:via-[#0b192c] dark:to-slate-950 p-8 sm:p-12 text-center text-slate-800 dark:text-white border border-slate-200 dark:border-slate-800 shadow-xl dark:shadow-2xl relative overflow-hidden transition-colors">
          <div class="absolute top-0 right-0 w-96 h-96 bg-blue/10 rounded-full blur-3xl pointer-events-none"></div>
          <div class="relative z-10 max-w-3xl mx-auto space-y-6">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue/10 dark:bg-blue/15 border border-blue/20 dark:border-blue/30 text-blue dark:text-blue-neon text-xs font-semibold uppercase tracking-wider">
              Ecosistema Profesional Integrado
            </span>
            <h3 class="text-2xl sm:text-3xl font-bold text-navy dark:text-white tracking-tight leading-snug">
              Convierte captaciones y demanda activa en acuerdos cerrados
            </h3>
            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-normal">
              Únete a una red profesional diseñada para conectar captaciones exclusivas, demanda calificada y agentes colaboradores en un entorno seguro, confidencial y trazable.
            </p>
            <div class="grid sm:grid-cols-2 gap-3 text-left pt-2">
              <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 flex items-center gap-3 text-xs text-slate-700 dark:text-slate-200 shadow-sm">
                <span class="text-emerald-500 dark:text-emerald-400 font-bold text-sm">✓</span>
                <span>Publicación estructurada con datos ciegos</span>
              </div>
              <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 flex items-center gap-3 text-xs text-slate-700 dark:text-slate-200 shadow-sm">
                <span class="text-emerald-500 dark:text-emerald-400 font-bold text-sm">✓</span>
                <span>Cruce inmediato con demandas solventes</span>
              </div>
              <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 flex items-center gap-3 text-xs text-slate-700 dark:text-slate-200 shadow-sm">
                <span class="text-emerald-500 dark:text-emerald-400 font-bold text-sm">✓</span>
                <span>Control de accesos y trazabilidad registral</span>
              </div>
              <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 flex items-center gap-3 text-xs text-slate-700 dark:text-slate-200 shadow-sm">
                <span class="text-emerald-500 dark:text-emerald-400 font-bold text-sm">✓</span>
                <span>Herramientas, IA Vera y contratos 50/50</span>
              </div>
            </div>
            <div class="pt-4 flex flex-wrap justify-center gap-4">
              <a href="#/oportunidades" class="px-7 py-3.5 rounded-2xl bg-blue hover:bg-blue-dark text-white font-bold text-sm transition-all shadow-lg shadow-blue/30 hover:scale-[1.02] active:scale-95">
                Ver oportunidades disponibles →
              </a>
            </div>
            <div class="pt-8 border-t border-slate-200 dark:border-slate-800/80">
              <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Diseñado específicamente para profesionales del sector</h4>
              <div class="mt-4 flex flex-wrap justify-center gap-2 text-xs">
                <?php foreach (array('Agentes inmobiliarios', 'Agencias y Brokers', 'Captadores en exclusiva', 'Personal Shoppers', 'Inversores', 'Equipos de expansión') as $profile) : ?>
                  <span class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 px-3.5 py-1.5 text-slate-700 dark:text-slate-300 font-medium shadow-sm"><?php echo esc_html($profile); ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
        </div>
      </section>
    </div>

    <!-- PÁGINA 6: MARKETPLACE (Catálogo general de activos) -->
    <div id="page-marketplace" class="page-section <?php echo $captacion_active_page_id === 'page-marketplace' ? '' : 'hidden'; ?>">
      <section class="py-12 bg-slate-50 dark:bg-slate-950">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">

          <!-- BREADCRUMBS Y REGRESO A OPORTUNIDADES -->
          <nav aria-label="Breadcrumb" class="mb-5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
            <ol class="flex items-center space-x-2">
              <li><a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-blue transition-colors">Inicio</a></li>
              <li class="text-slate-400">/</li>
              <li><a href="<?php echo esc_url(home_url('/oportunidades')); ?>" class="hover:text-blue transition-colors font-medium">Oportunidades</a></li>
              <li class="text-slate-400">/</li>
              <li class="font-bold text-navy dark:text-white">Marketplace de Propiedades Disponibles</li>
            </ol>
            <a href="<?php echo esc_url(home_url('/oportunidades')); ?>" class="inline-flex items-center gap-1.5 font-bold text-blue hover:text-blue-dark transition-colors">
              <span>← Volver al Hub de Oportunidades</span>
            </a>
          </nav>

          <div class="mb-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
              <h1 class="text-3xl font-black text-navy dark:text-white">Propiedades compartidas por profesionales inmobiliarios</h1>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-4xl">Busca oportunidades por zona, tipo de inmueble y presupuesto. Los datos sensibles permanecen protegidos hasta que se aprueba la colaboración.</p>
            </div>
            <?php if (is_user_logged_in()) : ?>
              <a href="<?php echo esc_url(home_url('/coincidencias-ventas')); ?>" class="shrink-0 px-5 py-3 rounded-xl bg-blue text-white text-xs font-black shadow-sm hover:bg-blue-dark">Ver mis coincidencias</a>
            <?php else : ?>
              <button type="button" onclick="openProfessionalSubscriptionModal('marketplace-colaborar')" class="shrink-0 px-5 py-3 rounded-xl bg-blue text-white text-xs font-black shadow-sm hover:bg-blue-dark">Crear cuenta gratuita para colaborar</button>
            <?php endif; ?>
          </div>

          <!-- CTA DESTACADO: COMPARTIR PROPIEDAD ENTRE PROFESIONALES -->
          <div class="mb-8 rounded-3xl bg-gradient-to-r from-[#0b192c] via-[#0d223f] to-[#071322] border border-blue/30 p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-72 h-72 bg-blue/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
              <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue/20 border border-blue/30 text-blue-neon text-[11px] font-bold uppercase tracking-wider">
                  <span>✨ Cartera en Exclusiva</span>
                </div>
                <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight">
                  ¿Tienes un inmueble en cartera o captación exclusiva?
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                  Compártelo con miles de profesionales y agencias inmobiliarias verificadas en toda España. Publicar es 100% gratuito e ilimitado, con dirección oculta y protección integral de datos ciegos.
                </p>
              </div>
              <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                <a href="<?php echo esc_url(home_url('/publicar?tipo=oferta')); ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-lg shadow-blue/30 hover:scale-105 transition-all">
                  <span>✚ Compartir una propiedad</span>
                  <span>↗</span>
                </a>
              </div>
            </div>
          </div>

          <!-- FILTROS DE OFERTAS DISPONIBLES -->
          <div id="marketplace-search-panel" class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">
              <div class="flex items-center gap-3">
                <h3 class="text-base font-extrabold text-navy dark:text-white">Encuentra una propiedad disponible</h3>
                <span id="marketplace-active-count-badge" class="px-2.5 py-0.5 rounded-full bg-blue/10 dark:bg-blue/20 text-blue dark:text-blue-light text-xs font-bold">Cargando...</span>
              </div>
              <div class="flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 dark:bg-slate-800 p-1" role="group" aria-label="Modo de visualización del Marketplace">
                <button id="marketplace-view-map-btn" type="button" onclick="setMarketplaceView('map')" class="px-3 py-2 rounded-lg text-xs font-black text-slate-500 dark:text-slate-400 hover:text-navy dark:hover:text-white transition-all">Mapa</button>
                <button id="marketplace-layout-block-btn" type="button" onclick="setMarketplaceLayout('block')" class="map-view-active px-3 py-2 rounded-lg text-xs font-black bg-white dark:bg-slate-700 text-navy dark:text-white shadow-sm transition-all">▦ Bloques</button>
                <button id="marketplace-layout-list-btn" type="button" onclick="setMarketplaceLayout('list')" class="px-3 py-2 rounded-lg text-xs font-black text-slate-500 dark:text-slate-400 hover:text-navy dark:hover:text-white transition-all">☰ Lista</button>
              </div>
            </div>

            <!-- QUICK FILTER PILLS (SQL-VIRTUALIZATION STYLE) -->
            <div class="mb-5 flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
              <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 mr-1">Filtro rápido:</span>
              <button type="button" onclick="document.getElementById('market-category-filter').value='all';refreshMarketplaceView()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-blue hover:text-white transition-all text-slate-700 dark:text-slate-200">✦ Todas las categorías</button>
              <button type="button" onclick="document.getElementById('market-category-filter').value='Piso';refreshMarketplaceView()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-blue hover:text-white transition-all text-slate-700 dark:text-slate-200">🏢 Pisos</button>
              <button type="button" onclick="document.getElementById('market-category-filter').value='Casa/Chalet';refreshMarketplaceView()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-blue hover:text-white transition-all text-slate-700 dark:text-slate-200">🏡 Chalets</button>
              <button type="button" onclick="document.getElementById('market-category-filter').value='Local Comercial';refreshMarketplaceView()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-blue hover:text-white transition-all text-slate-700 dark:text-slate-200">🏬 Locales</button>
              <button type="button" onclick="document.getElementById('market-category-filter').value='Nave';refreshMarketplaceView()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-blue hover:text-white transition-all text-slate-700 dark:text-slate-200">🏗️ Naves</button>
              <button type="button" onclick="document.getElementById('market-category-filter').value='Suelo/Terreno';refreshMarketplaceView()" class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-blue hover:text-white transition-all text-slate-700 dark:text-slate-200">🌲 Suelo</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Tipo de inmueble</label>
                <select id="market-category-filter" onchange="refreshMarketplaceView()" class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl focus:outline-none bg-white">
                  <option value="all">Todas las categorías</option>
                  <option value="Piso">Piso</option>
                  <option value="Casa/Chalet">Casa / Chalet</option>
                  <option value="Local Comercial">Local Comercial</option>
                  <option value="Nave">Nave</option>
                  <option value="Oficina">Oficina</option>
                  <option value="Edificio">Edificio</option>
                  <option value="Suelo/Terreno">Suelo / Terreno</option>
                  <option value="Otros">Otros</option>
                </select>
              </div>
              <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Ubicación</label><select id="market-ccaa-filter" class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl bg-white"></select></div>
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Presupuesto máximo</label>
                <select id="market-price-filter" onchange="refreshMarketplaceView()" class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl focus:outline-none bg-white">
                  <option value="all">Cualquier precio</option>
                  <option value="0-150000">Hasta 150.000 €</option>
                  <option value="150000-300000">150.000 € - 300.000 €</option>
                  <option value="300000-600000">300.000 € - 600.000 €</option>
                  <option value="600000-999999999">Más de 600.000 €</option>
                </select>
              </div>
              <button type="button" onclick="trackConversionEvent('marketplace_search');refreshMarketplaceView()" class="px-5 py-2.5 rounded-xl bg-blue text-white text-xs font-black shadow-sm">Buscar oportunidades</button>
            </div>
            <details class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
              <summary class="cursor-pointer text-xs font-black text-blue">Mostrar más filtros</summary>
              <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">
                <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Texto o zona</label><input id="market-search-filter" type="search" oninput="refreshMarketplaceView()" placeholder="Ej.: piso, Madrid, local..." class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl focus:outline-none focus:ring-2 focus:ring-blue/20 bg-white" /></div>
                <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Provincia</label><select id="market-province-filter" disabled class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl bg-white"></select></div>
                <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Municipio</label><select id="market-municipality-filter" disabled class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl bg-white"></select></div>
                <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Código postal</label><input id="market-postal-code-filter" type="search" oninput="refreshMarketplaceView()" inputmode="numeric" maxlength="5" placeholder="Ej.: 32002" class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl focus:outline-none focus:ring-2 focus:ring-blue/20 bg-white" /></div>
                <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Referencia</label><input id="market-reference-filter" type="search" oninput="refreshMarketplaceView()" placeholder="Ej.: REF-00123456" class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl focus:outline-none focus:ring-2 focus:ring-blue/20 bg-white" /></div>
                <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Reputación profesional</label><select id="market-reputation-filter" onchange="refreshMarketplaceView()" class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl focus:outline-none bg-white"><option value="0">Cualquier nivel</option><option value="30">Desde 30/100</option><option value="50">Desde 50/100</option><option value="70">Desde 70/100</option></select></div>
                <div><label class="block text-xs font-bold text-slate-400 uppercase mb-1">Ordenar por</label><select id="market-sort" onchange="sortMarketplace()" class="w-full px-3 py-2 border border-slate-200 text-xs font-bold rounded-xl focus:outline-none bg-white"><option value="newest">Más recientes</option><option value="oldest">Más antiguos</option><option value="price-low">Precio: menor a mayor</option><option value="price-high">Precio: mayor a menor</option><option value="score">Calidad de verificación</option><option value="reputation">Reputación profesional</option></select></div>
              </div>
              <div class="mt-4 flex justify-end"><button type="button" onclick="clearMarketplaceFilters()" class="text-xs font-bold text-slate-500 hover:text-blue transition-all">Limpiar filtros</button></div>
            </details>
          </div>

          <div id="marketplace-accordion-sections" class="space-y-4 mb-8"></div>

          <div id="marketplace-map-panel" class="hidden mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <p class="text-xs text-slate-500">Vista mapa aproximada. Las ubicaciones exactas permanecen protegidas por confidencialidad.</p>
              <span class="shrink-0 text-[10px] font-black uppercase tracking-wider text-blue">Ubicación protegida</span>
            </div>
            <div id="marketplace-map" role="application" aria-label="Mapa aproximado de captaciones disponibles"></div>
          </div>

          <!-- Grid general de Marketplace -->
          <div id="marketplace-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Dinámico -->
          </div>
        </div>
      </section>
    </div>

    <!-- PÁGINA: COINCIDENCIAS DE VENTAS -->
    <div id="page-coincidencias-ventas" class="page-section <?php echo $captacion_active_page_id === 'page-coincidencias-ventas' ? '' : 'hidden'; ?>">
      <section class="py-12 bg-slate-50/70 dark:bg-slate-950/70 transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">
          <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-8">
            <div class="max-w-4xl"><span class="text-[10px] font-black uppercase tracking-[0.18em] text-green">Cruce oferta-demanda</span><h2 class="text-3xl font-black text-navy mt-2">Coincidencias de Ventas</h2><p class="text-sm text-slate-500 mt-2 leading-relaxed">Visualiza oportunidades donde una captación disponible puede encajar con una demanda activa. Detecta posibles operaciones, revisa coincidencias y activa nuevas colaboraciones profesionales.</p></div>
            <a href="<?php echo esc_url(home_url('/propiedades')); ?>" class="px-4 py-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-navy">Volver a oportunidades</a>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <article class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm"><span class="text-[10px] font-black uppercase text-slate-400">Coincidencias detectadas</span><strong id="sales-match-count" class="block text-xl font-black text-green mt-2">Sin coincidencias aún</strong><span class="text-[11px] text-slate-500">Coincidencias calculadas con los datos disponibles.</span></article>
            <article class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm"><span class="text-[10px] font-black uppercase text-slate-400">Valor económico estimado</span><strong id="sales-match-value" class="block text-xl font-black text-navy mt-2">Sin valor estimado</strong><span class="text-[11px] text-slate-500">Estimación, no representa operaciones cerradas.</span></article>
          </div>
          <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-8 gap-3">
              <input id="sales-match-search" oninput="renderSalesMatches()" type="search" placeholder="Buscar título, zona o referencia" class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs" />
              <select id="sales-match-type" onchange="renderSalesMatches()" class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs bg-white"><option value="all">Todos los tipos</option><option>Piso</option><option>Casa/Chalet</option><option>Local Comercial</option><option>Nave</option><option>Oficina</option><option>Edificio</option><option>Suelo/Terreno</option></select>
              <select id="sales-match-ccaa" class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs bg-white"></select>
              <select id="sales-match-province" disabled class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs bg-white"></select>
              <select id="sales-match-municipality" disabled class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs bg-white"></select>
              <select id="sales-match-level" onchange="renderSalesMatches()" class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs bg-white"><option value="all">Cualquier nivel</option><option value="high">Alta: 75% o más</option><option value="medium">Media: 60% a 74%</option></select>
              <select id="sales-match-sort" onchange="renderSalesMatches()" class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs bg-white"><option value="newest">Más recientes</option><option value="score">Mayor coincidencia</option><option value="value">Mayor valor</option></select>
            </div>
          </div>
          <div id="sales-matches-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"></div>
        </div>
      </section>
    </div>

    <!-- PÁGINA 6: PRECIOS Y CRÉDITOS (EMBUDO DE CONVERSIÓN TRANSPARENTE) -->
    <div id="page-planes" class="page-section <?php echo $captacion_active_page_id === 'page-planes' ? '' : 'hidden'; ?>">
      <section class="py-12 sm:py-16 md:py-20 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 space-y-16">
          
          <!-- 1. HEADER HERO DEL EMBUDO: OFERTA CERO RIESGO -->
          <div class="text-center space-y-5 max-w-4xl mx-auto">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue/10 border border-blue/30 text-blue dark:text-blue-neon text-xs sm:text-sm font-black uppercase tracking-wider">
              <span>🎁</span>
              <span>100% Transparente · Cero Costes Ocultos</span>
            </span>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-navy dark:text-white tracking-tight leading-tight">
              Precios Simples y Claros. <br class="hidden sm:inline" />
              <span class="bg-gradient-to-r from-blue via-indigo-500 to-emerald-500 bg-clip-text text-transparent">Solo inviertes cuando hay negocio real.</span>
            </h1>
            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed font-normal">
              Publicar todas tus propiedades y buscar en el mapa de toda España es <strong class="text-navy dark:text-white">100% GRATIS</strong>. Te regalamos <strong class="text-emerald-600 dark:text-emerald-400">3 créditos de bienvenida</strong> (30 días, no acumulables) para que contactes agencias y cierres tus primeras operaciones sin pagar un solo euro.
            </p>

            <!-- Tarjeta destacada de Bienvenida Gratuita (0 €) -->
            <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border-2 border-emerald-500 shadow-xl max-w-2xl mx-auto text-left space-y-4 relative overflow-hidden">
              <div class="absolute -right-12 -top-12 w-36 h-36 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                  <span class="text-xs font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Paso 1: Empieza sin coste</span>
                  <h3 class="text-xl sm:text-2xl font-black text-navy dark:text-white">Plan Bienvenida Profesional</h3>
                </div>
                <div class="flex items-baseline gap-1">
                  <span class="text-3xl sm:text-4xl font-black text-emerald-600 dark:text-emerald-400">0 €</span>
                  <span class="text-xs text-slate-400 font-medium">para siempre</span>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm text-slate-700 dark:text-slate-300">
                <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span><strong>3 Créditos de bienvenida</strong> (30 días)</span></div>
                <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Publica captaciones ilimitadas</span></div>
                <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Acceso al mapa radar nacional</span></div>
                <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Sin tarjeta bancaria requerida</span></div>
              </div>

              <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                <button type="button" onclick="openProfessionalSubscriptionModal('precios-bienvenida')" class="w-full sm:w-auto flex-1 px-7 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 hover:scale-[1.01] transition-all text-center">
                  Crear cuenta gratis (+3 Créditos) →
                </button>
                <button type="button" onclick="openVeraWithContext('pricing', 'pricing-welcome', 0, 'Vera, explícame cómo funcionan los 3 créditos de bienvenida durante 30 días y cuándo conviene usar un plan profesional.')" class="w-full sm:w-auto px-5 py-3.5 rounded-xl border border-blue/30 bg-blue/5 text-blue hover:bg-blue hover:text-white font-bold text-xs transition-all text-center">
                  Preguntar a Vera
                </button>
                <span class="text-xs text-slate-400 font-medium text-center">Activación en menos de 1 minuto</span>
              </div>
            </div>
          </div>

          <!-- 2. ARITMÉTICA DE VALOR: ¿POR QUÉ ES EL MEJOR NEGOCIO PARA TU AGENCIA? -->
          <div class="p-8 sm:p-10 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-8">
            <div class="text-center max-w-3xl mx-auto space-y-2">
              <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">La lógica económica</span>
              <h2 class="text-2xl sm:text-3xl font-black text-navy dark:text-white">¿Cuánto ganas por cada crédito que utilizas?</h2>
              <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Un crédito es la llave para acceder al expediente completo de un inmueble o comprador. Mira la diferencia frente a la publicidad tradicional:
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-5xl mx-auto">
              <!-- Método Tradicional -->
              <div class="p-6 sm:p-8 rounded-2xl bg-rose-50/70 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/50 space-y-4">
                <div class="flex items-center gap-3">
                  <span class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl font-black">✕</span>
                  <div>
                    <span class="text-xs font-bold uppercase text-rose-600 dark:text-rose-400">El método antiguo</span>
                    <h3 class="text-lg font-bold text-navy dark:text-white">Portales y Anuncios Tradicionales</h3>
                  </div>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Pagas entre <strong>300 € y 600 € al mes</strong> por posicionar anuncios. El 90% de los contactos son curiosos sin financiación y el propietario se impacienta porque no se vende el piso.
                </p>
                <div class="pt-3 border-t border-rose-200 dark:border-rose-900/50 text-xs font-bold text-rose-600 dark:text-rose-400">
                  Gasto fijo alto + Cero garantía de resultados
                </div>
              </div>

              <!-- Método Compra Captación -->
              <div class="p-6 sm:p-8 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/20 border-2 border-emerald-500/80 space-y-4 shadow-md">
                <div class="flex items-center gap-3">
                  <span class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl font-black">✓</span>
                  <div>
                    <span class="text-xs font-bold uppercase text-emerald-600 dark:text-emerald-400">El método Compra Captación</span>
                    <h3 class="text-lg font-bold text-navy dark:text-white">Colaboración Directa 50/50</h3>
                  </div>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Inviertes <strong>1 crédito (~1,45 €)</strong> únicamente cuando encuentras al agente que ya tiene al comprador listo. Formalizáis el contrato y te aseguras <strong>3.150 € o más</strong> de honorarios en notaría.
                </p>
                <div class="pt-3 border-t border-emerald-200 dark:border-emerald-900/50 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                  Inviertes 1,45 € ➔ Ganas miles de euros de honorarios
                </div>
              </div>
            </div>
          </div>

          <!-- 3. CÓMO FUNCIONA EL SISTEMA DE CRÉDITOS EN 5 PASOS -->
          <div class="p-8 sm:p-10 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-8">
            <div class="text-center max-w-3xl mx-auto space-y-2">
              <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">Paso a paso</span>
              <h2 class="text-2xl sm:text-3xl font-black text-navy dark:text-white">¿Cómo se usan los créditos en la práctica?</h2>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">Un circuito cerrado y seguro para proteger tu cartera:</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
              <!-- Paso 1 -->
              <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 flex flex-col justify-between space-y-3">
                <span class="w-8 h-8 rounded-xl bg-blue text-white font-black text-xs flex items-center justify-center shadow-md">1</span>
                <div>
                  <h3 class="text-sm font-bold text-navy dark:text-white">Alta Gratuita</h3>
                  <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                    Creas tu cuenta en 30 segundos y recibes automáticamente <strong>3 créditos de bienvenida</strong> (30 días).
                  </p>
                </div>
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">✓ 0 € invertidos</span>
              </div>

              <!-- Paso 2 -->
              <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 flex flex-col justify-between space-y-3">
                <span class="w-8 h-8 rounded-xl bg-emerald-500 text-white font-black text-xs flex items-center justify-center shadow-md">2</span>
                <div>
                  <h3 class="text-sm font-bold text-navy dark:text-white">Publica sin Límites</h3>
                  <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                    Sube toda tu cartera y demandas. Publicar <strong>nunca gasta créditos</strong> y los datos están blindados.
                  </p>
                </div>
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">✓ Datos ciegos protegidos</span>
              </div>

              <!-- Paso 3 -->
              <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 flex flex-col justify-between space-y-3">
                <span class="w-8 h-8 rounded-xl bg-indigo-500 text-white font-black text-xs flex items-center justify-center shadow-md">3</span>
                <div>
                  <h3 class="text-sm font-bold text-navy dark:text-white">Cruce con IA Vera</h3>
                  <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                    Nuestra IA cruza al instante las características del inmueble con las peticiones activas de compra.
                  </p>
                </div>
                <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">✓ Conexión automática</span>
              </div>

              <!-- Paso 4 -->
              <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 flex flex-col justify-between space-y-3">
                <span class="w-8 h-8 rounded-xl bg-amber text-slate-950 font-black text-xs flex items-center justify-center shadow-md">4</span>
                <div>
                  <h3 class="text-sm font-bold text-navy dark:text-white">Desbloqueo (1 Crédito)</h3>
                  <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                    Canjeas 1 crédito para ver la dirección completa, catastro y WhatsApp/teléfono del agente titular.
                  </p>
                </div>
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">✓ 1 Crédito = 1 Contacto</span>
              </div>

              <!-- Paso 5 -->
              <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 flex flex-col justify-between space-y-3">
                <span class="w-8 h-8 rounded-xl bg-purple-500 text-white font-black text-xs flex items-center justify-center shadow-md">5</span>
                <div>
                  <h3 class="text-sm font-bold text-navy dark:text-white">Contrato 50/50 y Cierre</h3>
                  <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                    Firmáis el acuerdo oficial de colaboración antes de la visita y cobráis los honorarios a partes iguales.
                  </p>
                </div>
                <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400">✓ Reparto garantizado</span>
              </div>
            </div>
          </div>

          <!-- 4. DOS MODALIDADES DE TRABAJO: PAGO POR USO VS SUSCRIPCIÓN CON AHORRO -->
          <div class="space-y-12">
            
            <!-- MODALIDAD A: PAGO POR USO SIN CUOTAS (1 CRÉDITO = 10 €) -->
            <div class="p-8 sm:p-10 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                  <span class="text-xs font-black uppercase tracking-wider text-blue dark:text-blue-neon">Opción A: Sin cuotas ni compromisos</span>
                  <h3 class="text-2xl font-black text-navy dark:text-white mt-1">Pago por Uso (Operaciones Puntuales)</h3>
                  <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-0.5">Ideal si eres agente autónomo o inmobiliaria que solo desea desbloquear un inmueble o comprador específico de forma ocasional.</p>
                </div>
                <span class="px-3.5 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold whitespace-nowrap">Pagas solo cuando hay negocio</span>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <!-- Tarjeta de 1 Crédito = 10 € -->
                <div class="lg:col-span-6 p-6 sm:p-7 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border-2 border-slate-200 dark:border-slate-700 space-y-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <span class="text-[10px] font-black uppercase tracking-wider text-blue">Desbloqueo Individual</span>
                      <h4 class="text-xl font-black text-navy dark:text-white mt-0.5">1 Crédito de Contacto</h4>
                    </div>
                    <div class="text-right">
                      <span class="text-3xl sm:text-4xl font-black text-navy dark:text-white">10 €</span>
                      <span class="block text-[11px] text-slate-400">pago único + IVA</span>
                    </div>
                  </div>

                  <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Desbloquea al instante los datos completos del agente titular (teléfono directo, WhatsApp, notas privadas y catastro) para cerrar una colaboración 50/50.
                  </p>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-400 pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
                    <div class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span><span>Sin suscripción mensual</span></div>
                    <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Garantía de reembolso automático</span></div>
                  </div>

                  <div class="pt-2">
                    <button type="button" onclick="buyCreditsStripe('credit_single')" class="w-full py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md">
                      Comprar 1 Crédito (10 €) →
                    </button>
                  </div>
                </div>

                <!-- Comparativa explicativa de ancla de valor -->
                <div class="lg:col-span-6 p-6 sm:p-7 rounded-2xl bg-blue-50/60 dark:bg-slate-800/30 border border-blue/20 dark:border-slate-700 space-y-3">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue/10 text-blue dark:text-blue-neon text-[11px] font-black uppercase">
                    💡 Maximiza tu Rentabilidad
                  </span>
                  <h4 class="text-base sm:text-lg font-black text-navy dark:text-white">¿Realizas más de 1 cruce al mes?</h4>
                  <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    En la opción de pago puntual pagas 10 € por crédito. Al contratar cualquiera de nuestros <strong>Planes de Suscripción</strong>, el coste se reduce hasta <strong>0,95 € por crédito</strong> (¡un 90% de ahorro directo!) y los créditos acumulados <strong>nunca caducan</strong>.
                  </p>
                  <div class="pt-2">
                    <a href="#planes-suscripcion-section" class="inline-flex items-center gap-1 text-xs font-bold text-blue hover:underline">
                      Ver planes con descuento a continuación ↓
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- MODALIDAD B: PLANES DE SUSCRIPCIÓN CON 33% DTO ANUAL (PARA AUTÓNOMOS, AGENCIAS Y BROKERS) -->
            <div id="planes-suscripcion-section" class="space-y-6 pt-2">
              <div class="text-center max-w-3xl mx-auto space-y-4">
                <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">Opción B: Para Autónomos, Agencias y Brokers</span>
                <h3 class="text-2xl sm:text-3xl font-black text-navy dark:text-white">Planes con Descuento y Ventajas VIP</h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">Diseñados a la medida de profesionales individuales, inmobiliarias consolidadas y redes broker. Los créditos no consumidos <strong class="text-navy dark:text-white">se acumulan como bonos</strong>. Máximo 2 recargas mensuales.</p>
                
                <!-- Toggle Selector de Facturación: Anual (33% DTO por defecto) vs Mensual -->
                <div class="inline-flex items-center p-1.5 rounded-2xl bg-slate-200/80 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 shadow-inner mt-2">
                  <button type="button" id="pricing-toggle-annual" onclick="setPricingBillingCycle('annual')" class="px-5 py-2.5 rounded-xl bg-blue text-white font-bold text-xs sm:text-sm shadow-md transition-all flex items-center gap-2">
                    <span>Facturación Anual</span>
                    <span class="px-2 py-0.5 rounded-md bg-emerald-400 text-slate-950 text-[10px] font-black uppercase tracking-wider">33% DTO</span>
                  </button>
                  <button type="button" id="pricing-toggle-monthly" onclick="setPricingBillingCycle('monthly')" class="px-5 py-2.5 rounded-xl text-slate-600 dark:text-slate-300 hover:text-navy dark:hover:text-white font-bold text-xs sm:text-sm transition-all">
                    Facturación Mensual
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Plan 1: Autónomo / Inicial (5 créditos) -->
                <div class="p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-6">
                  <div>
                    <div class="flex justify-between items-center">
                      <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Profesionales Autónomos</span>
                      <span id="plan-inicial-unit" class="px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">3,80 €/crédito</span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-1">
                      <strong id="plan-inicial-price" class="text-4xl font-black text-navy dark:text-white">19 €</strong>
                      <span id="plan-inicial-period" class="text-sm text-slate-500">/mes</span>
                    </div>
                    <p id="plan-inicial-billed" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold mt-1">Facturado anualmente: 228 €/año (Ahorras 120 €/año · 33% DTO)</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Para agentes autónomos independientes y captadores individuales.</p>
                    
                    <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 space-y-3 text-xs text-slate-700 dark:text-slate-300">
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span><strong>5 créditos mensuales</strong> (acumulables como bonos)</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Publicación ilimitada y gratuita de captaciones</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Cruce automático con demandas solventes</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span><strong>Recargas extra: 5 € por 5 créditos</strong> (máx. 2/mes)</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Trazabilidad jurídica y contratos 50/50</span></div>
                    </div>
                  </div>
                  <button type="button" onclick="openProfessionalSubscriptionModal('plan-inicial')" class="w-full py-3.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-navy dark:text-white font-bold text-xs uppercase tracking-wider transition-all">
                    Elegir Plan Autónomo →
                  </button>
                </div>

                <!-- Plan 2: Agencia / Profesional (10 créditos - Destacado) -->
                <div class="p-7 rounded-3xl bg-white dark:bg-slate-900 border-2 border-blue shadow-xl relative flex flex-col justify-between space-y-6">
                  <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-blue text-white text-[10px] font-black uppercase tracking-wider shadow-md">
                    Más Elegido por Agencias
                  </span>
                  <div>
                    <div class="flex justify-between items-center">
                      <span class="text-xs font-bold uppercase tracking-wider text-blue">Agencias Inmobiliarias</span>
                      <span id="plan-profesional-unit" class="px-2.5 py-0.5 rounded-full bg-blue/10 text-blue text-xs font-bold">2,90 €/crédito</span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-1">
                      <strong id="plan-profesional-price" class="text-4xl font-black text-navy dark:text-white">29 €</strong>
                      <span id="plan-profesional-period" class="text-sm text-slate-500">/mes</span>
                    </div>
                    <p id="plan-profesional-billed" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold mt-1">Facturado anualmente: 348 €/año (Ahorras 180 €/año · 33% DTO)</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Para inmobiliarias y equipos comerciales que cruzan carteras activamente.</p>
                    
                    <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 space-y-3 text-xs text-slate-700 dark:text-slate-300">
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span><strong>10 créditos mensuales</strong> (acumulables como bonos)</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span><strong>Asistente IA Vera ilimitada</strong> para análisis</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span><strong>Recargas VIP: 5 € por 10 créditos</strong> (máx. 2/mes)</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Alertas prioritarias por WhatsApp y Email</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Programa de bonificaciones por cierre</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Trazabilidad jurídica y contratos 50/50</span></div>
                    </div>
                  </div>
                  <button type="button" onclick="openProfessionalSubscriptionModal('plan-profesional')" class="w-full py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-blue/25 hover:scale-[1.01]">
                    Comenzar con Plan Agencia →
                  </button>
                </div>

                <!-- Plan 3: Broker / Premium (15 créditos) -->
                <div class="p-7 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-6">
                  <div>
                    <div class="flex justify-between items-center">
                      <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Brokers y Grandes Agencias</span>
                      <span id="plan-premium-unit" class="px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">3,27 €/crédito</span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-1">
                      <strong id="plan-premium-price" class="text-4xl font-black text-navy dark:text-white">49 €</strong>
                      <span id="plan-premium-period" class="text-sm text-slate-500">/mes</span>
                    </div>
                    <p id="plan-premium-billed" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold mt-1">Facturado anualmente: 588 €/año (Ahorras 300 €/año · 33% DTO)</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Para agencias, brokers y organizaciones inmobiliarias con alto volumen.</p>
                    
                    <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 space-y-3 text-xs text-slate-700 dark:text-slate-300">
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span><strong>15 créditos mensuales</strong> (acumulables como bonos)</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span><strong>Máxima bonificación: 5 € por 15 créditos</strong> (máx. 2/mes)</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Acceso multi-agente para todo tu equipo</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Matching prioritario en toda España</span></div>
                      <div class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✓</span><span>Soporte prioritario y gestor de cuenta</span></div>
                    </div>
                  </div>
                  <button type="button" onclick="openProfessionalSubscriptionModal('plan-premium')" class="w-full py-3.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-navy dark:text-white font-bold text-xs uppercase tracking-wider transition-all">
                    Elegir Plan Broker →
                  </button>
                </div>

              </div>
            </div>
          </div>

          <!-- 5. 4 GARANTÍAS BLINDADAS DE SEGURIDAD Y CONFIANZA -->
          <div class="p-8 sm:p-10 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-8">
            <div class="text-center max-w-3xl mx-auto space-y-2">
              <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">Seguridad sin sorpresas</span>
              <h2 class="text-2xl sm:text-3xl font-black text-navy dark:text-white">Garantías de Transparencia y Protección</h2>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">Todo lo que necesitas saber sobre pagos, reembolsos y honorarios:</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
              <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/60 space-y-3 flex flex-col justify-between">
                <div class="space-y-3">
                  <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">🛡️</div>
                  <h3 class="text-base font-bold text-navy dark:text-white">Garantía de Reembolso</h3>
                  <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    Si un inmueble ya estuviera reservado o los datos no fuesen válidos, el crédito se reintegra a tu saldo automáticamente.
                  </p>
                </div>
                <div class="pt-3 border-t border-slate-200 dark:border-slate-700 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                  ✓ Reintegro automático
                </div>
              </div>

              <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/60 space-y-3 flex flex-col justify-between">
                <div class="space-y-3">
                  <div class="w-10 h-10 rounded-xl bg-blue/20 text-blue flex items-center justify-center text-xl">🚫</div>
                  <h3 class="text-base font-bold text-navy dark:text-white">0% Retención de Honorarios</h3>
                  <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    Compra Captación nunca cobra porcentajes sobre tus ventas. El 100% de los honorarios se reparte entre las agencias que colaboran.
                  </p>
                </div>
                <div class="pt-3 border-t border-slate-200 dark:border-slate-700 text-xs font-semibold text-blue">
                  ✓ Tus honorarios son 100% tuyos
                </div>
              </div>

              <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/60 space-y-3 flex flex-col justify-between">
                <div class="space-y-3">
                  <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl">🧾</div>
                  <h3 class="text-base font-bold text-navy dark:text-white">Factura con IVA Deducible</h3>
                  <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    Emisión automática de factura oficial para empresas y profesionales autónomos tras cada recarga o pago.
                  </p>
                </div>
                <div class="pt-3 border-t border-slate-200 dark:border-slate-700 text-xs font-semibold text-purple-600 dark:text-purple-400">
                  ✓ Gasto 100% deducible
                </div>
              </div>

              <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/60 space-y-3 flex flex-col justify-between">
                <div class="space-y-3">
                  <div class="w-10 h-10 rounded-xl bg-amber/20 text-amber flex items-center justify-center text-xl">🔒</div>
                  <h3 class="text-base font-bold text-navy dark:text-white">Pago Seguro con Stripe</h3>
                  <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    Cifrado bancario SSL de 256 bits. Nunca almacenamos datos de tu tarjeta bancaria en nuestros servidores.
                  </p>
                </div>
                <div class="pt-3 border-t border-slate-200 dark:border-slate-700 text-xs font-semibold text-amber-600 dark:text-amber-400">
                  ✓ Pasarela cifrada bancaria
                </div>
              </div>
            </div>
          </div>

          <!-- 6. PREGUNTAS FRECUENTES DE PRECIOS Y PAGOS -->
          <div class="space-y-8">
            <div class="text-center max-w-3xl mx-auto space-y-2">
              <span class="text-xs sm:text-sm font-black uppercase tracking-widest text-blue dark:text-blue-neon">Preguntas frecuentes</span>
              <h2 class="text-2xl sm:text-3xl font-black text-navy dark:text-white">Dudas habituales sobre Precios y Créditos</h2>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">Todo claro antes de empezar:</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full max-w-5xl mx-auto">
              <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
                <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base font-bold text-navy dark:text-white hover:text-blue cursor-pointer">
                  <span>¿Tengo que pagar una cuota mensual obligatoria?</span>
                  <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  No. Puedes operar 100% con créditos sueltos (Pago por Uso) sin ninguna cuota mensual ni compromiso de permanencia. Las suscripciones mensuales son totalmente opcionales para agencias que quieren abaratar el coste por crédito y tener ventajas VIP.
                </div>
              </details>

              <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
                <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base font-bold text-navy dark:text-white hover:text-blue cursor-pointer">
                  <span>¿Qué pasa con los créditos que no use este mes? ¿Caducan?</span>
                  <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Los <strong>3 créditos de bienvenida</strong> caducan a los 30 días y no son acumulables. Los créditos adquiridos mediante un plan o recarga se rigen por las condiciones específicas que aparecen antes de confirmar la compra.
                </div>
              </details>

              <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
                <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base font-bold text-navy dark:text-white hover:text-blue cursor-pointer">
                  <span>¿Compra Captación se queda algún porcentaje de la venta?</span>
                  <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  No, absolutamente nada (0%). Los honorarios inmobiliarios pactados en la operación se reparten al 100% entre las dos agencias colaboradoras (normalmente 50% para la agencia captadora y 50% para la agencia que aporta al comprador).
                </div>
              </details>

              <details class="opportunity-accordion rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-sm transition hover:border-blue/50">
                <summary class="flex items-center justify-between gap-4 px-5 py-4 text-base font-bold text-navy dark:text-white hover:text-blue cursor-pointer">
                  <span>¿Puedo cancelar o cambiar mi plan mensual en cualquier momento?</span>
                  <span class="opportunity-accordion-chevron text-slate-400 text-base transition-transform">▾</span>
                </summary>
                <div class="px-5 pb-5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  Sí. Puedes subir de plan, bajar de plan o cancelar tu suscripción en cualquier momento con un solo clic desde tu panel de usuario. No hay penalizaciones ni permanencias mínimas.
                </div>
              </details>
            </div>
          </div>

          <!-- 7. CTA FINAL PANORÁMICO -->
          <div class="w-full text-center py-12 sm:py-16 px-6 sm:px-12 rounded-3xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-6 shadow-sm">
            <div class="max-w-3xl mx-auto space-y-3">
              <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-navy dark:text-white">Empieza hoy con 3 créditos gratis</h3>
              <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Sin tarjeta de crédito. Crea tu cuenta en menos de 1 minuto y empieza a desbloquear oportunidades reales en tu zona.
              </p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
              <button type="button" onclick="openProfessionalSubscriptionModal('precios-cta-final')" class="px-8 py-4 rounded-2xl bg-blue hover:bg-blue-dark text-white font-bold text-sm uppercase tracking-wider transition-all shadow-lg shadow-blue/30 hover:scale-105">
                Crear cuenta gratis (3 créditos) →
              </button>
              <a href="<?php echo esc_url(home_url('/propiedades')); ?>" class="px-8 py-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-navy dark:text-white font-bold text-sm uppercase tracking-wider hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
                Explorar oportunidades
              </a>
            </div>
          </div>

        </div>
      </section>
    </div>

    <div id="page-recursos" class="page-section <?php echo $captacion_active_page_id === 'page-recursos' ? '' : 'hidden'; ?>">
      <section class="py-12 sm:py-16 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen transition-colors">
        <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 space-y-12">
          
          <!-- BANNER HERO DE RECURSOS -->
          <header class="internal-page-banner mb-4">
            <img class="internal-page-banner__image" src="<?php echo esc_url($captacion_media_url('media/Vera_recursos_01-scaled.png')); ?>" alt="Recursos profesionales Compra Captación" width="1600" height="893" loading="lazy" decoding="async">
            <div class="internal-page-banner__content">
              <span class="internal-page-banner__kicker">Biblioteca Profesional · Compra Captación</span>
              <h1 class="internal-page-banner__title">Recursos y Herramientas para Colaborar con Blindaje Total</h1>
              <p class="internal-page-banner__support">Descarga modelos jurídicos homologados 50/50, dossiers de captación de exclusivas y matrices de cualificación para cerrar ventas seguras.</p>
            </div>
          </header>

          <!-- BARRA DE BENEFICIOS / ESTADÍSTICAS -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-2xl font-bold">🎁</div>
              <div>
                <span class="text-[10px] font-black uppercase text-slate-400">Acceso Inmediato</span>
                <strong class="block text-base font-bold text-navy dark:text-white mt-0.5">3 Recursos Gratis</strong>
                <span class="text-xs text-emerald-600 dark:text-emerald-400">Incluidos con tu cuenta</span>
              </div>
            </div>
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-blue/10 text-blue flex items-center justify-center text-2xl font-bold">💎</div>
              <div>
                <span class="text-[10px] font-black uppercase text-slate-400">Nivel Pro & VIP</span>
                <strong class="block text-base font-bold text-navy dark:text-white mt-0.5">6 Herramientas VIP</strong>
                <span class="text-xs text-blue">Para planes de pago</span>
              </div>
            </div>
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center text-2xl font-bold">⚖️</div>
              <div>
                <span class="text-[10px] font-black uppercase text-slate-400">Marco Legal España</span>
                <strong class="block text-base font-bold text-navy dark:text-white mt-0.5">100% Homologados</strong>
                <span class="text-xs text-slate-500 dark:text-slate-400">Código Civil y Ley Vivienda</span>
              </div>
            </div>
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-amber/10 text-amber flex items-center justify-center text-2xl font-bold">⚡</div>
              <div>
                <span class="text-[10px] font-black uppercase text-slate-400">Formato Práctico</span>
                <strong class="block text-base font-bold text-navy dark:text-white mt-0.5">PDFs Editables</strong>
                <span class="text-xs text-slate-500 dark:text-slate-400">Listos para usar y firmar</span>
              </div>
            </div>
          </div>

          <!-- FILTROS Y BÚSQUEDA DE RECURSOS -->
          <div class="p-4 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Pestañas de categoría -->
            <div class="flex flex-wrap items-center gap-2" id="resource-filter-tabs">
              <button type="button" onclick="filterResourcesByPlan('all')" id="res-tab-all" class="px-4 py-2.5 rounded-xl bg-blue text-white font-bold text-xs shadow-sm transition-all">
                Todos los Recursos (9)
              </button>
              <button type="button" onclick="filterResourcesByPlan('free')" id="res-tab-free" class="px-4 py-2.5 rounded-xl text-slate-600 dark:text-slate-300 hover:text-navy dark:hover:text-white font-bold text-xs bg-slate-100 dark:bg-slate-800 transition-all flex items-center gap-1.5">
                <span>🎁 Gratuitos (3)</span>
              </button>
              <button type="button" onclick="filterResourcesByPlan('pro')" id="res-tab-pro" class="px-4 py-2.5 rounded-xl text-slate-600 dark:text-slate-300 hover:text-navy dark:hover:text-white font-bold text-xs bg-slate-100 dark:bg-slate-800 transition-all flex items-center gap-1.5">
                <span>💎 Exclusivos Pro (6)</span>
              </button>
            </div>

            <!-- Buscador -->
            <div class="relative w-full md:w-80">
              <input type="search" id="resource-search-input" oninput="filterResourcesSearch()" placeholder="Buscar por título o materia..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue/30" />
              <span class="absolute left-3.5 top-2.5 text-slate-400 text-sm">🔍</span>
            </div>
          </div>

          <!-- GRID DE LAS 9 TARJETAS DE RECURSOS -->
          <div id="professional-downloadable-resources" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"></div>

          <!-- BANNER CTA DE CONTACTO Y ASISTENCIA IA VERA -->
          <div class="p-8 sm:p-10 rounded-3xl bg-gradient-to-br from-blue/10 via-slate-100 to-white dark:from-slate-900 dark:via-blue-950/20 dark:to-slate-900 border border-blue/20 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-2 text-center md:text-left">
              <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue/10 text-blue dark:text-blue-neon text-xs font-black uppercase tracking-wider">
                🤖 Asistente IA Vera Integrada
              </span>
              <h3 class="text-xl sm:text-2xl font-black text-navy dark:text-white">¿Necesitas un documento a medida o ayuda con una operación?</h3>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 max-w-2xl leading-relaxed">
                Nuestra asistente inmobiliaria Vera puede orientarte en tiempo real sobre el tipo de contrato adecuado para tu cruce de propiedades o demandas.
              </p>
            </div>
            <a href="#/contacto" class="px-7 py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md whitespace-nowrap text-center">
              Consultar con IA Vera →
            </a>
          </div>

        </div>
    </div>

    <!-- PÁGINA 8: CONTACTO -->
    <div id="page-contacto" class="page-section <?php echo $captacion_active_page_id === 'page-contacto' ? '' : 'hidden'; ?>">
      <section class="py-12 max-w-5xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10 space-y-2">
          <h1 class="text-3xl font-black text-navy">¿Necesitas ayuda? Te respondemos en menos de 24 horas</h1>
          <p class="text-slate-500 text-sm">Cuéntanos qué necesitas y te ayudaremos con el registro, la publicación de oportunidades o los planes disponibles.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-stretch">
          <!-- Datos -->
          <div class="md:col-span-5 bg-navy text-white p-8 rounded-3xl space-y-6">
            <h3 class="text-xl font-bold">Oficina de Soporte</h3>
            <p class="text-xs text-slate-300 leading-relaxed">Operamos de forma distribuida en toda España, ofreciendo asistencia rápida a agencias asociadas.</p>

            <div class="space-y-4 text-xs">
              <div>
                <span class="text-slate-400 block font-bold">Dirección Fiscal</span>
                <span>Madrid, España</span>
              </div>
              <div>
                <span class="text-slate-400 block font-bold">✉ Correo de contacto</span>
                <span class="text-blue-light">hola@compracaptacion.com</span>
              </div>
              <div>
                <span class="text-slate-400 block font-bold"> Horario</span>
                <span>Lunes a Viernes • 09:00 - 18:00</span>
              </div>
            </div>
          </div>

          <!-- Formulario -->
          <div class="md:col-span-7 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200">
            <form onsubmit="handleContactSubmit(event)" class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Nombre y apellidos *</label>
                <input id="contact-name" type="text" required placeholder="Tu nombre" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue/20" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Correo electrónico *</label>
                <input id="contact-email" type="email" required autocomplete="email" placeholder="tu@email.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue/20" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Teléfono</label>
                <input id="contact-phone" type="tel" autocomplete="tel" placeholder="+34 600 000 000" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue/20" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Preferencia de contacto</label>
                <select id="contact-preference" onchange="updateContactPhoneRequirement()" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-blue/20">
                  <option value="email" selected>Email</option><option value="call">Llamada</option><option value="whatsapp">WhatsApp</option>
                </select>
                <p class="territory-scroll-hint">↕ Desplaza para ver todas</p>
                <p id="contact-phone-help" class="mt-1 text-[10px] text-slate-400">Opcional para contacto por email.</p>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">¿En qué podemos ayudarte? *</label>
                <select id="contact-topic" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-blue/20"><option value="">Selecciona una opción</option><option>Crear mi cuenta</option><option>Publicar una captación</option><option>Buscar una propiedad</option><option>Consultar precios</option><option>Resolver un problema técnico</option><option>Otro asunto</option></select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Mensaje *</label>
                <textarea id="contact-message" rows="3" required placeholder="Escribe aquí tu consulta..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue/20"></textarea>
              </div>
              <p id="contact-form-error" class="hidden rounded-xl bg-red-50 border border-red-100 px-3 py-2 text-xs text-red-700" role="alert"></p>
              <label class="legal-consent-box flex items-start gap-2 text-[11px] text-slate-500 leading-relaxed">
                <input id="contact-privacy-consent" type="checkbox" required class="mt-0.5" />
                <span>He leído la <a href="<?php echo esc_url(home_url('/privacidad')); ?>" class="legal-link">Política de privacidad</a> y autorizo el tratamiento de mis datos para responder a esta consulta. *</span>
              </label>
              <label class="flex items-start gap-2 text-[11px] text-slate-500 leading-relaxed">
                <input id="contact-marketing-consent" type="checkbox" class="mt-0.5" />
                <span>Quiero recibir novedades y comunicaciones comerciales de Compra Captación. Opcional y revocable.</span>
              </label>
              <button type="submit" class="w-full py-3 rounded-xl bg-blue hover:bg-blue-dark text-white font-extrabold text-xs transition-all shadow-md">Enviar mi consulta</button>
            </form>
          </div>
        </div>
      </section>
    </div>



    <!-- PÁGINA 9: AVISO LEGAL -->
    <div id="page-aviso-legal" class="page-section hidden">
      <section class="py-12 max-w-5xl mx-auto px-4 sm:px-6">
        <div class="space-y-3 mb-8">
          <span class="text-xs font-black uppercase tracking-widest text-blue">Centro legal</span>
          <h2 class="text-3xl font-black text-navy">Aviso legal</h2>
          <p class="text-sm text-slate-500 leading-relaxed">Información legal de Compra Captación como plataforma para profesionales inmobiliarios, colaboración protegida y publicación responsable de oportunidades.</p>
        </div>
        <div class="grid gap-5">
          <article class="legal-card">
            <h3 class="text-lg">1. Titular del sitio web</h3>
            <div class="mt-3 grid gap-2 text-sm">
              <p><strong>Nombre comercial:</strong> Compra Captación. La razón social, NIF/CIF, domicilio social, inscripción registral y teléfono legal están <strong>pendientes de completar</strong> antes de iniciar la comercialización definitiva. Para consultas legales o administrativas, contacta en <a class="legal-link" href="mailto:<?php echo esc_attr($captacion_contact_email); ?>"><?php echo esc_html($captacion_contact_email); ?></a>.</p>
              <p><strong>Jurisdicción prevista:</strong> España. Los datos identificativos definitivos deberán ser sustituidos por los reales por la entidad titular y revisados jurídicamente.</p>
              <p>El acceso a información sensible de captaciones, demandas o colaboradores se limita mediante registro, permisos, trazabilidad y flujos de autorización.</p>
            </div>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">2. Objeto y naturaleza del servicio</h3>
            <p class="mt-2 text-sm leading-relaxed">Compra Captación es una plataforma digital orientada a profesionales inmobiliarios. Facilita la publicación de oportunidades y demandas con información pública limitada, el cruce de coincidencias y la preparación de colaboraciones. La plataforma no sustituye la diligencia profesional de las partes, no garantiza la veracidad material de cada anuncio y no actúa como propietaria del inmueble.</p>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">3. Responsabilidad del usuario anunciante</h3>
            <ul class="mt-2 space-y-1.5 text-sm leading-relaxed">
              <li>Publicar información veraz, actualizada, suficiente y lícita.</li>
              <li>Disponer de autorización o base legítima para compartir la oportunidad.</li>
              <li>No revelar públicamente datos personales, direcciones exactas, documentación sensible o referencias que permitan identificar al propietario sin base jurídica.</li>
              <li>Actualizar o retirar el anuncio cuando deje de estar disponible.</li>
              <li>Respetar la normativa inmobiliaria, fiscal, de consumo, igualdad y competencia que resulte aplicable.</li>
            </ul>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">4. Moderación y retirada de contenidos</h3>
            <p class="mt-2 text-sm leading-relaxed">La plataforma podrá revisar, limitar, suspender o retirar publicaciones que sean inexactas, duplicadas, engañosas, ilícitas o contrarias a estas normas. Cualquier usuario puede utilizar el canal de reporte para comunicar una incidencia. La retirada o limitación deberá documentarse internamente y comunicarse al usuario afectado cuando proceda.</p>
            <button type="button" onclick="openReportModal()" class="mt-4 px-4 py-2.5 rounded-xl bg-navy text-white text-xs font-bold">Reportar contenido o incidencia</button>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">5. Condiciones de uso de la plataforma</h3>
            <p class="mt-2 text-sm leading-relaxed">El uso de Compra Captación requiere actuar como profesional identificado, respetar la confidencialidad de los datos sensibles y mantener actualizada la información publicada. La plataforma puede limitar, revisar o retirar contenidos cuando sea necesario para proteger a usuarios, propietarios, colaboradores o terceros.</p>
          </article>
        </div>
      </section>
    </div>

    <!-- PÁGINA 10: PRIVACIDAD -->
    <div id="page-privacidad" class="page-section hidden">
      <section class="py-12 max-w-5xl mx-auto px-4 sm:px-6">
        <div class="space-y-3 mb-8">
          <span class="text-xs font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Reglamento General de Protección de Datos (UE) 2016/679 & LOPDGDD 3/2018</span>
          <h1 class="text-3xl sm:text-4xl font-black text-navy dark:text-white">Política de Privacidad y Protección de Datos</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Compra Captación implementa controles técnicos y organizativos rigurosos basados en la <em>privacidad desde el diseño y por defecto</em> (Art. 25 RGPD), minimización de datos, protección de datos ciegos en MLS, trazabilidad registral y cifrado de extremo a extremo.</p>
        </div>

        <div class="grid gap-6">
          <!-- 1. Responsable del Tratamiento -->
          <article class="legal-card bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm">
            <h2 class="text-xl font-black text-navy dark:text-white mb-3">1. Responsable del Tratamiento</h2>
            <div class="space-y-2 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
              <p><strong>Identidad:</strong> Compra Captación · razón social y NIF/CIF pendientes de completar.</p>
              <p><strong>Domicilio social:</strong> Madrid, España · dirección exacta pendiente de completar.</p>
              <p><strong>Canal Oficial de Privacidad y Delegado de Protección de Datos (DPO):</strong> <a class="text-blue font-bold hover:underline" href="mailto:<?php echo esc_attr($captacion_contact_email); ?>"><?php echo esc_html($captacion_contact_email); ?></a></p>
              <p><strong>Actividad:</strong> Plataforma tecnológica de colaboración entre profesionales para el cruce de oportunidades y acuerdos entre agencias y agentes inmobiliarios cualificados.</p>
              <p><strong>Proveedores:</strong> Hostinger (alojamiento), Stripe (pagos), Google (recursos técnicos y, cuando proceda, analítica), Ollama/VPS (asistencia IA Vera) y Mailchimp (comunicaciones, si está activado). La lista definitiva, ubicaciones y encargados deberán verificarse antes de la puesta en producción comercial.</p>
              <p class="text-xs text-slate-500">Última actualización: 25 de agosto de 2026 · Versión provisional 0.9 · Pendiente de revisión jurídica.</p>
            </div>
          </article>

          <!-- 2. Registro de Actividades de Tratamiento (ROPA - Art. 30 RGPD) -->
          <article class="legal-card bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm">
            <h2 class="text-xl font-black text-navy dark:text-white mb-2">2. Registro de Actividades de Tratamiento (ROPA / Art. 30)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">De conformidad con el Artículo 30 del RGPD, se detallan los tratamientos de datos personales efectuados en la plataforma:</p>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 font-bold">
                    <th class="p-3">Actividad / Finalidad</th>
                    <th class="p-3">Base Jurídica (Art. 6 RGPD)</th>
                    <th class="p-3">Categorías de Datos</th>
                    <th class="p-3">Plazo de Conservación</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-300">
                  <tr>
                    <td class="p-3 font-semibold text-navy dark:text-white">Alta y gestión de cuentas profesionales</td>
                    <td class="p-3">Art. 6.1.b (Ejecución contractual)</td>
                    <td class="p-3">Nombre, email profesional, CIF/NIF, teléfono/WhatsApp, agencia, rol profesional</td>
                    <td class="p-3">Duración de la relación contractual + 5 años (prescripción mercantil/fiscal)</td>
                  </tr>
                  <tr>
                    <td class="p-3 font-semibold text-navy dark:text-white">Publicación en Marketplace y cruces de colaboradores</td>
                    <td class="p-3">Art. 6.1.b (Ejecución contractual) + Art. 6.1.f (Interés legítimo)</td>
                    <td class="p-3">Datos ciegos de propiedades/demandas, características técnicas, precio, honorarios</td>
                    <td class="p-3">Hasta la retirada del anuncio o baja de la cuenta</td>
                  </tr>
                  <tr>
                    <td class="p-3 font-semibold text-navy dark:text-white">Gestión de pagos y suscripciones Stripe</td>
                    <td class="p-3">Art. 6.1.b (Contrato) + Art. 6.1.c (Obligación legal tributaria)</td>
                    <td class="p-3">ID de cliente Stripe, transacciones, facturas (no almacenamos tarjetas completas)</td>
                    <td class="p-3">6 años según Código de Comercio y normativa tributaria</td>
                  </tr>
                  <tr>
                    <td class="p-3 font-semibold text-navy dark:text-white">Procesamiento asistido con IA Vera</td>
                    <td class="p-3">Art. 6.1.a (Consentimiento explícito) / BYOK</td>
                    <td class="p-3">Descripciones anonimizadas de inmuebles, consultas de búsqueda sin PII</td>
                    <td class="p-3">Inmediato (sin almacenamiento permanente de prompts por terceros)</td>
                  </tr>
                  <tr>
                    <td class="p-3 font-semibold text-navy dark:text-white">Comunicaciones informativas y alertas de mercado</td>
                    <td class="p-3">Art. 6.1.a (Consentimiento revocable) / Art. 21.2 LSSI</td>
                    <td class="p-3">Email profesional, historial de intereses territoriales</td>
                    <td class="p-3">Hasta la revocación del consentimiento (baja con un clic)</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>

          <!-- 3. Privacidad por Diseño y Protección de Datos Ciegos (Art. 25) -->
          <article class="legal-card bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm">
            <h2 class="text-xl font-black text-navy dark:text-white mb-3">3. Privacidad desde el Diseño y por Defecto (Art. 25 RGPD)</h2>
            <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
              <p>Compra Captación aplica la regla estricta de <strong>Datos Ciegos y Blindaje Profesional</strong>:</p>
              <ul class="list-disc pl-5 space-y-1.5 text-xs sm:text-sm">
                <li><strong>Dirección exacta oculta:</strong> En el marketplace público nunca se publica la calle, número o piso del inmueble, únicamente municipio, zona/barrio y código postal disociado.</li>
                <li><strong>Desbloqueo seguro bajo acuerdo:</strong> Los datos identificativos del captador y la dirección precisa solo se revelan una vez que ambas agencias aceptan mutuamente la solicitud de colaboración 50/50 y firman electrónicamente el protocolo de confidencialidad.</li>
                <li><strong>Sin PII en demandas de clientes:</strong> Las búsquedas activas de compradores no contienen nombres, DNIs ni datos personales del cliente final del profesional.</li>
              </ul>
            </div>
          </article>

          <!-- 4. Medidas Técnicas y de Seguridad (Art. 32 RGPD) -->
          <article class="legal-card bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm">
            <h2 class="text-xl font-black text-navy dark:text-white mb-3">4. Medidas Técnicas y Organizativas de Seguridad (Art. 32 RGPD)</h2>
            <div class="grid sm:grid-cols-2 gap-4 text-xs sm:text-sm text-slate-600 dark:text-slate-300">
              <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                <h3 class="font-bold text-navy dark:text-white mb-1.5">🔒 Cifrado y Criptografía</h3>
                <p>Cifrado TLS 1.3 en todas las comunicaciones en tránsito. Cifrado AES-256 para almacenamiento de credenciales y claves privadas de IA (BYOK).</p>
              </div>
              <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                <h3 class="font-bold text-navy dark:text-white mb-1.5">🛡️ Control de Accesos (RBAC)</h3>
                <p>Principio de menor privilegio. Aislamiento de perfiles, validación estricta de nonces y sesiones con tokens temporales.</p>
              </div>
              <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                <h3 class="font-bold text-navy dark:text-white mb-1.5">📑 Trazabilidad y Logs Seguros</h3>
                <p>Auditoría de accesos a expedientes sin almacenamiento de contraseñas, tokens ni información personal identificable (PII) en logs de depuración.</p>
              </div>
              <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                <h3 class="font-bold text-navy dark:text-white mb-1.5">🇪🇺 Ubicación en la Unión Europea</h3>
                <p>Servidores e infraestructura física ubicados dentro del Espacio Económico Europeo (Alemania y España), garantizando soberanía de datos.</p>
              </div>
            </div>
          </article>

          <!-- 5. Procedimiento de Notificación de Brechas (Art. 33 y 34 RGPD) -->
          <article class="legal-card bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm">
            <h2 class="text-xl font-black text-navy dark:text-white mb-3">5. Protocolo de Notificación de Brechas de Seguridad (Art. 33 y 34)</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">En cumplimiento de los artículos 33 y 34 del RGPD, Compra Captación mantiene un plan de respuesta ante incidentes de seguridad que contempla la notificación a la Agencia Española de Protección de Datos (AEPD) en un plazo máximo de <strong>72 horas</strong> desde que se tenga constancia de cualquier incidente de seguridad que entrañe un riesgo para los derechos y libertades de las personas, así como la comunicación inmediata a los afectados cuando el riesgo sea elevado.</p>
          </article>

          <!-- 6. Ejercicio de Derechos ARCO-POL (Artículos 15 al 22 RGPD) -->
          <article class="legal-card bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-emerald-500/30 bg-emerald-50/20 dark:bg-emerald-950/20 shadow-sm">
            <h2 class="text-xl font-black text-navy dark:text-white mb-2">6. Ejercicio de Derechos ARCO-POL (Art. 15-22 RGPD)</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-4">Usted dispone de los siguientes derechos de forma gratuita y accesible:</p>
            <div class="grid sm:grid-cols-3 gap-3 text-xs mb-6">
              <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <strong class="text-navy dark:text-white block">Acceso (Art. 15)</strong>
                <span class="text-slate-500 dark:text-slate-400">Conocer qué datos suyos tratamos y obtener copia.</span>
              </div>
              <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <strong class="text-navy dark:text-white block">Rectificación (Art. 16)</strong>
                <span class="text-slate-500 dark:text-slate-400">Modificar datos inexactos o incompletos.</span>
              </div>
              <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <strong class="text-navy dark:text-white block">Supresión / Olvido (Art. 17)</strong>
                <span class="text-slate-500 dark:text-slate-400">Solicitar el borrado de sus datos personales.</span>
              </div>
              <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <strong class="text-navy dark:text-white block">Limitación (Art. 18)</strong>
                <span class="text-slate-500 dark:text-slate-400">Restringir temporalmente el tratamiento.</span>
              </div>
              <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <strong class="text-navy dark:text-white block">Portabilidad (Art. 20)</strong>
                <span class="text-slate-500 dark:text-slate-400">Descargar sus datos en JSON / XML estructurado.</span>
              </div>
              <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <strong class="text-navy dark:text-white block">Oposición (Art. 21)</strong>
                <span class="text-slate-500 dark:text-slate-400">Oponerse al envío de avisos o procesamiento comercial.</span>
              </div>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
              <a href="mailto:<?php echo esc_attr($captacion_contact_email); ?>?subject=Ejercicio%20de%20Derechos%20RGPD%20-%20CompraCaptacion" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md transition-all">
                <span>📧 Ejercer mis Derechos por Email</span>
              </a>
              <?php if (is_user_logged_in()) : ?>
                <button type="button" onclick="window.location.hash='#/panel-privado';setTimeout(()=>switchPrivateDashboardPanel('data'),150);" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-navy hover:bg-navy-light text-white text-xs font-black shadow-md transition-all">
                  <span>⚙️ Gestionar Privacidad en mi Panel</span>
                </button>
              <?php endif; ?>
              <span class="text-xs text-slate-500 dark:text-slate-400">Tiempo de respuesta máximo: 30 días naturales.</span>
            </div>
          </article>
        </div>
      </section>
    </div>

    <!-- PÁGINA 11: COOKIES -->
    <div id="page-cookies" class="page-section hidden">
      <section class="py-12 max-w-5xl mx-auto px-4 sm:px-6">
        <div class="space-y-3 mb-8">
          <span class="text-xs font-black uppercase tracking-widest text-amber">Preferencias de navegación</span>
          <h2 class="text-3xl font-black text-navy">Política de cookies y almacenamiento local</h2>
          <p class="text-sm text-slate-500 leading-relaxed">Complianz es la fuente principal de consentimiento, bloqueo preventivo e inventario de cookies y tecnologías similares en este sitio.</p>
        </div>
        <div class="grid gap-5">
          <article class="legal-card">
            <h3 class="text-lg">1. Tecnologías utilizadas</h3>
            <ul class="mt-2 space-y-1.5 text-sm leading-relaxed">
              <li><strong>Necesarias:</strong> sesión, seguridad, preferencia de tema y almacenamiento técnico imprescindible para prestar el servicio.</li>
              <li><strong>Analítica:</strong> solo se utilizará si está configurada y aceptada conforme al banner de consentimiento.</li>
              <li><strong>Marketing:</strong> solo se utilizará con consentimiento específico cuando corresponda.</li>
            </ul>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">2. Consentimiento</h3>
            <p class="mt-2 text-sm leading-relaxed">Las tecnologías estrictamente necesarias pueden utilizarse para prestar un servicio solicitado. Complianz mantiene desactivadas las finalidades de preferencias, estadísticas o marketing que requieran consentimiento hasta que la persona usuaria decida.</p>
            <button type="button" onclick="captacionOpenCookiePreferences()" class="mt-4 px-4 py-2.5 rounded-xl bg-blue text-white text-xs font-bold">Configurar preferencias</button>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">3. Proveedores externos</h3>
            <p class="mt-2 text-sm leading-relaxed">El mapa usa Leaflet y teselas de OpenStreetMap como servicio técnico para mostrar cobertura territorial. También pueden cargarse recursos técnicos necesarios para interfaz, mapas, estilos o tipografías. El inventario actualizado se gestiona mediante Complianz cuando proceda.</p>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">4. Actualización del inventario</h3>
            <p class="mt-2 text-sm leading-relaxed">La declaración principal de cookies y tecnologías similares se gestiona mediante Complianz y puede actualizarse periódicamente para reflejar cambios técnicos o legales.</p>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">5. Declaración de cookies de Complianz</h3>
            <div class="mt-3 text-sm leading-relaxed"><?php echo shortcode_exists('cmplz-document') ? do_shortcode('[cmplz-document type="cookie-statement" region="eu"]') : '<p>La declaración detallada de cookies no está disponible temporalmente. Puedes solicitarla en hola@compracaptacion.com.</p>'; ?></div>
          </article>
        </div>
      </section>
    </div>

    <!-- PÁGINA 12: NORMAS DE PUBLICACIÓN -->
    <div id="page-normas-publicacion" class="page-section hidden">
      <section class="py-12 max-w-5xl mx-auto px-4 sm:px-6">
        <div class="space-y-3 mb-8">
          <span class="text-xs font-black uppercase tracking-widest text-blue">Publicación responsable</span>
          <h2 class="text-3xl font-black text-navy">Normas de publicación y responsabilidad de la plataforma</h2>
          <p class="text-sm text-slate-500 leading-relaxed">Reglas operativas para reducir riesgos, proteger datos personales y facilitar la moderación de anuncios.</p>
        </div>
        <div class="grid gap-5">
          <article class="legal-card">
            <h3 class="text-lg">Antes de publicar</h3>
            <ul class="mt-2 space-y-1.5 text-sm leading-relaxed">
              <li>Confirmar la legitimidad de la captación o demanda y la autorización necesaria.</li>
              <li>Publicar únicamente información mínima no sensible en la ficha abierta.</li>
              <li>Evitar dirección exacta, teléfonos particulares, emails privados, documentación identificativa, datos catastrales completos y datos bancarios.</li>
              <li>Describir el inmueble con precisión, sin afirmaciones engañosas ni discriminatorias.</li>
              <li>Actualizar disponibilidad, precio y condiciones de colaboración.</li>
            </ul>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">Datos que pueden compartirse de forma limitada</h3>
            <p class="mt-2 text-sm leading-relaxed">Tipo de inmueble, zona aproximada, código postal cuando sea adecuado, precio, superficie, habitaciones, baños, score interno y condiciones generales de colaboración. El expediente completo debe permanecer bloqueado hasta completar el flujo profesional autorizado.</p>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">Datos reservados</h3>
            <p class="mt-2 text-sm leading-relaxed">Identidad y contacto del propietario, dirección exacta, documentación personal, nota simple, referencias catastrales completas, contratos, datos bancarios y cualquier documento que contenga información innecesaria para la vista previa pública.</p>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">Moderación y canal de reporte</h3>
            <p class="mt-2 text-sm leading-relaxed">La plataforma debe ofrecer un mecanismo sencillo para reportar contenido presuntamente ilícito o incorrecto, registrar la incidencia, revisar la publicación y documentar la decisión adoptada.</p>
            <button type="button" onclick="openReportModal()" class="mt-4 px-4 py-2.5 rounded-xl bg-navy text-white text-xs font-bold">Abrir canal de reporte</button>
          </article>
          <article class="legal-card">
            <h3 class="text-lg">Trazabilidad de profesionales</h3>
            <p class="mt-2 text-sm leading-relaxed">Antes de permitir colaboraciones reales, la versión productiva deberá verificar identidad profesional, datos de contacto, organización y evidencias básicas del usuario anunciante, aplicando un enfoque proporcional al servicio prestado.</p>
          </article>
        </div>
      </section>
    </div>

    <!-- PÁGINA 13: CONDICIONES DE CONTRATACIÓN -->
    <div id="page-condiciones-de-contratacion" class="page-section hidden">
      <section class="py-12 max-w-5xl mx-auto px-4 sm:px-6">
        <div class="space-y-3 mb-8">
          <span class="text-xs font-black uppercase tracking-widest text-amber">Información contractual</span>
          <h2 class="text-3xl font-black text-navy">Condiciones de contratación</h2>
          <p class="text-sm text-slate-500 leading-relaxed">Condiciones aplicables al acceso profesional, las publicaciones y las funcionalidades de Compra Captación. La versión comercial definitiva deberá validarse con el asesoramiento jurídico correspondiente.</p>
        </div>
        <div class="grid gap-5">
          <article class="legal-card"><h3 class="text-lg">1. Servicio y usuarios</h3><p class="mt-2 text-sm leading-relaxed">Compra Captación es una plataforma exclusiva de colaboración entre profesionales inmobiliarios, agentes independientes y empresas del sector. El usuario debe aportar información veraz, mantener sus credenciales seguras y utilizar el servicio con una finalidad profesional legítima.</p></article>
          <article class="legal-card"><h3 class="text-lg">2. Fase inicial</h3><p class="mt-2 text-sm leading-relaxed">Durante la fase inicial, el acceso puede ofrecerse gratuitamente durante el periodo comunicado en el alta. Las funcionalidades, límites y condiciones podrán evolucionar mientras se mejora el servicio con usuarios profesionales.</p></article>
          <article class="legal-card"><h3 class="text-lg">3. Publicaciones y colaboración</h3><p class="mt-2 text-sm leading-relaxed">Quien publica una captación, demanda o feed XML declara que dispone de autorización suficiente y es responsable de la exactitud, actualización y licitud de la información aportada. La plataforma puede retirar contenido duplicado, engañoso, ilícito o desactualizado.</p></article>
          <article class="legal-card"><h3 class="text-lg">4. Responsabilidad</h3><p class="mt-2 text-sm leading-relaxed">La plataforma facilita la conexión y la gestión de oportunidades, pero no garantiza el cierre de operaciones ni sustituye la revisión profesional, contractual o legal que corresponda entre las partes.</p></article>
          <article class="legal-card"><h3 class="text-lg">5. Datos y finalización</h3><p class="mt-2 text-sm leading-relaxed">El tratamiento de datos se rige por la Política de privacidad. El usuario podrá solicitar la eliminación de sus publicaciones y datos conforme al flujo disponible, salvo las obligaciones de conservación o los procesos activos que deban documentarse.</p></article>
        </div>
      </section>
    </div>

    <!-- PÁGINA 14: CANAL DE DENUNCIAS -->
    <div id="page-canal-de-denuncias" class="page-section hidden">
      <section class="py-12 max-w-5xl mx-auto px-4 sm:px-6">
        <div class="space-y-3 mb-8">
          <span class="text-xs font-black uppercase tracking-widest text-amber">Canal confidencial</span>
          <h2 class="text-3xl font-black text-navy">Canal de denuncias</h2>
          <p class="text-sm text-slate-500 leading-relaxed">Este canal está destinado a comunicar de buena fe posibles incumplimientos legales, fraudes, conflictos de interés, vulneraciones de confidencialidad o conductas contrarias a las normas internas.</p>
        </div>
        <div class="grid gap-5">
          <article class="legal-card"><h3 class="text-lg">Cuándo utilizarlo</h3><p class="mt-2 text-sm leading-relaxed">Utiliza este canal para asuntos graves que requieran tratamiento confidencial. Para errores de un anuncio, duplicados, contenido inadecuado o incidencias operativas, utiliza “Reportar contenido” o “Contacto y soporte”.</p></article>
          <article class="legal-card"><h3 class="text-lg">Principios de gestión</h3><ul class="mt-2 space-y-1.5 text-sm leading-relaxed"><li>Confidencialidad de la información recibida.</li><li>Prohibición de represalias frente a comunicaciones de buena fe.</li><li>Análisis imparcial y proporcional de los hechos.</li><li>Protección de los derechos de las personas implicadas.</li></ul></article>
            <article class="legal-card"><h3 class="text-lg">Información necesaria</h3><p class="mt-2 text-sm leading-relaxed">Describe los hechos, fechas, anuncios o referencias afectadas y aporta documentación únicamente cuando sea necesaria. No incluyas datos personales irrelevantes ni información sensible de terceros.</p><button type="button" onclick="openReportModal()" class="mt-4 px-4 py-2.5 rounded-xl bg-navy text-white text-xs font-bold">Enviar reporte confidencial</button></article>
            <p class="text-xs text-slate-500">Última actualización: 25 de agosto de 2026 · Versión provisional 0.9 · El procedimiento definitivo debe validarse jurídicamente.</p>
        </div>
      </section>
    </div>

    <div id="page-politica-reembolsos" class="page-section hidden">
      <section class="py-12 max-w-5xl mx-auto px-4 sm:px-6">
        <div class="space-y-3 mb-8"><span class="text-xs font-black uppercase tracking-widest text-amber">Pagos y créditos</span><h2 class="text-3xl font-black text-navy">Política de créditos, recargas y reembolsos</h2><p class="text-sm text-slate-500 leading-relaxed">Información provisional para la fase inicial de la plataforma. La aplicación concreta depende del producto contratado, la condición del cliente y la normativa aplicable.</p></div>
        <div class="grid gap-5">
          <article class="legal-card"><h3 class="text-lg">Precio e impuestos</h3><p class="mt-2 text-sm leading-relaxed">Los precios dirigidos a consumidores se mostrarán como precio final con IVA incluido cuando proceda. En la factura se desglosarán base imponible, tipo y cuota de IVA conforme a la normativa de facturación aplicable. Para operaciones B2B, Stripe y la factura reflejarán los datos fiscales y el tratamiento que corresponda.</p></article>
          <article class="legal-card"><h3 class="text-lg">Créditos</h3><p class="mt-2 text-sm leading-relaxed">El crédito se consume únicamente cuando el usuario confirma una acción que lo utilice, según se indique antes del desbloqueo. Los créditos promocionales pueden tener caducidad, límites y no acumulación; esas condiciones se mostrarán antes de su activación.</p></article>
          <article class="legal-card"><h3 class="text-lg">Reembolsos y cancelación</h3><p class="mt-2 text-sm leading-relaxed">Las solicitudes se tramitarán por soporte en <a class="legal-link" href="mailto:<?php echo esc_attr($captacion_contact_email); ?>">hola@compracaptacion.com</a>, indicando usuario, operación y motivo. Se analizarán los casos de cobro duplicado, error técnico, servicio no prestado y derechos imperativos de consumidores. No se garantiza un reembolso automático cuando el crédito ya haya sido utilizado legítimamente.</p></article>
          <article class="legal-card"><h3 class="text-lg">Suscripciones</h3><p class="mt-2 text-sm leading-relaxed">Cualquier renovación automática, periodo de permanencia, cambio de plan o cancelación deberá mostrarse claramente antes del pago y poder gestionarse por un canal accesible. No se activará una renovación sin información previa suficiente.</p></article>
          <p class="text-xs text-slate-500">Última actualización: 25 de agosto de 2026 · Versión provisional 0.9 · Pendiente de revisión jurídica y fiscal.</p>
        </div>
      </section>
    </div>

    <div id="page-datos-ciegos" class="page-section hidden">
      <section class="py-12 max-w-5xl mx-auto px-4 sm:px-6">
        <div class="space-y-3 mb-8"><span class="text-xs font-black uppercase tracking-widest text-blue">Privacidad de anuncios</span><h2 class="text-3xl font-black text-navy">Política de datos ciegos</h2><p class="text-sm text-slate-500 leading-relaxed">Reglas para publicar oportunidades sin exponer información personal o sensible innecesaria.</p></div>
        <div class="grid gap-5"><article class="legal-card"><h3 class="text-lg">Permitido en la ficha abierta</h3><p class="mt-2 text-sm leading-relaxed">Municipio, zona aproximada, precio, superficie, características, fotografías autorizadas, referencia interna y condiciones generales de colaboración.</p></article><article class="legal-card"><h3 class="text-lg">No permitido</h3><p class="mt-2 text-sm leading-relaxed">Nombre del propietario, teléfono, email, dirección exacta, planta, puerta, DNI/NIE, nota simple completa, catastro identificativo o documentos con datos personales.</p></article><article class="legal-card"><h3 class="text-lg">Incidencias</h3><p class="mt-2 text-sm leading-relaxed">Si se publica información personal por error, utiliza el canal de reporte. La plataforma podrá ocultar temporalmente el anuncio, conservar evidencias mínimas y solicitar aclaraciones al anunciante.</p><button type="button" onclick="openReportModal()" class="mt-4 px-4 py-2.5 rounded-xl bg-navy text-white text-xs font-bold">Reportar exposición de datos</button></article><p class="text-xs text-slate-500">Última actualización: 25 de agosto de 2026 · Versión provisional 0.9 · Pendiente de revisión jurídica.</p></div>
      </section>
    </div>

    <!-- PÁGINA 13: ÁREA PRIVADA · DASHBOARD DEL AGENTE (FULL-WIDTH WORKSPACE) -->
    <div id="page-area-privada" class="page-section <?php echo $captacion_active_page_id === 'page-area-privada' ? '' : 'hidden'; ?> w-full min-h-screen bg-slate-50 dark:bg-[#060d17] text-slate-900 dark:text-slate-100 transition-colors duration-200">
      <section class="py-5 px-3 sm:px-6 lg:px-8 w-full max-w-none">
        
        <!-- TOP WORKSPACE COMMAND BAR (FINTECH CRM STYLE) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 mb-6 rounded-2xl bg-white dark:bg-[#0b192c] border border-slate-200/90 dark:border-slate-800/90 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue to-blue-dark text-white flex items-center justify-center font-black text-lg shadow-md shrink-0">
              ⚡
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-lg sm:text-xl font-black text-navy dark:text-white tracking-tight">Centro de Colaboración 50/50</h2>
                <span id="private-plan-access-badge" class="px-2.5 py-0.5 rounded-full bg-blue/10 dark:bg-blue/20 text-blue dark:text-blue-neon text-[10px] font-black uppercase">Plan Agencia Pro</span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400">Operaciones en exclusiva compartida, cruces con IA Vera y liquidación de honorarios</p>
            </div>
          </div>
          
          <div class="flex flex-wrap items-center gap-2.5">
            <button id="private-profile-header-badge" type="button" onclick="switchPrivateDashboardPanel('profile', true)" class="px-3 py-2 rounded-xl bg-amber-light text-amber text-xs font-bold transition-transform hover:scale-105">Perfil pendiente</button>
            <button type="button" onclick="switchPrivateDashboardPanel('credits', true)" class="px-3.5 py-2 rounded-xl bg-blue/10 hover:bg-blue/20 dark:bg-blue/20 dark:hover:bg-blue/30 text-blue dark:text-blue-neon text-xs font-black flex items-center gap-1.5 transition-all">
              <span>💎</span> <span id="private-topbar-credit-val">3,00</span> cr
            </button>
            <a href="#/ofrecer-captacion" class="px-4 py-2 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-bold shadow-sm transition-all hover:scale-105">+ Publicar captación</a>
            <a href="#/buscar-captaciones" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold shadow-sm transition-all hover:scale-105">+ Publicar demanda</a>
            <button type="button" onclick="openContactSupportModal()" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-xs font-bold shadow-sm transition-all hover:scale-105">?</button>
          </div>
        </div>

        <!-- Selector móvil de sección -->
        <div class="lg:hidden mb-5">
          <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-2">Sección del dashboard</label>
          <select id="private-dashboard-mobile-select" onchange="if(this.value==='contact_modal'){openContactSupportModal()}else{switchPrivateDashboardPanel(this.value, true)}" class="private-dashboard-mobile-select">
            <option value="overview">▦ Resumen ejecutivo</option>
            <option value="academy">🎓 Academia</option>
            <option value="offers">📢 Mis anuncios</option>
            <option value="credits">💎 Créditos</option>
            <option value="referrals">👥 Referidos</option>
            <option value="requests">📩 Avisos</option>
            <option value="operations">⚖️ Operaciones</option>
            <option value="favorites">♥ Favoritos</option>
            <option value="tasks">📅 Calendario</option>
            <option value="communications">💬 Chat</option>
            <option value="feeds">↻ Importaciones</option>
            <option value="profile">👤 Perfil profesional</option>
            <option value="contact_modal">? Contacto</option>
          </select>
        </div>

        <div class="private-dashboard-shell">
          <!-- BARRA LATERAL DEL PANEL PRIVADO -->
          <aside class="private-dashboard-sidebar hidden lg:block">
            <div class="exec-sidebar-brand">
              <span class="exec-brand-mark"></span>
              <span><strong class="block text-[13px] tracking-wide text-navy dark:text-white">COMPRA CAPTACIÓN</strong><small class="block mt-0.5 text-[7px] tracking-wider text-slate-400">PANEL PRIVADO</small></span>
            </div>
            <div class="exec-sidebar-profile px-2 pb-3 mb-2 border-b border-slate-200 dark:border-slate-800">
              <p id="private-dashboard-agent-name" class="text-sm font-black text-navy dark:text-white">Agente profesional</p>
              <p id="private-dashboard-agent-agency" class="text-[11px] text-slate-500 mt-1">Compra Captación</p>
            </div>
            <nav class="space-y-1">
              <button type="button" data-private-panel="overview" onclick="switchPrivateDashboardPanel('overview', true)" class="private-dashboard-nav active"><span>▦</span><span>Resumen ejecutivo</span></button>
              <button type="button" data-private-panel="academy" onclick="switchPrivateDashboardPanel('academy', true)" class="private-dashboard-nav"><span class="text-blue">🎓</span><span>Academia</span><span id="academy-sidebar-badge" class="ml-auto px-2 py-0.5 rounded-full bg-blue/10 text-blue text-[9px] font-black">0/7</span></button>
              <button type="button" data-private-panel="offers" onclick="switchPrivateDashboardPanel('offers', true)" class="private-dashboard-nav"><span>📢</span><span>Mis anuncios</span></button>
              <button type="button" data-private-panel="credits" onclick="switchPrivateDashboardPanel('credits', true)" class="private-dashboard-nav"><span>💎</span><span>Créditos</span><span id="private-sidebar-credit-pill" class="ml-auto px-2 py-0.5 rounded-full bg-blue text-white text-[10px] font-black">3</span></button>
              <button type="button" data-private-panel="referrals" onclick="switchPrivateDashboardPanel('referrals', true)" class="private-dashboard-nav"><span>👥</span><span>Referidos</span><span id="private-sidebar-referrals-badge" class="ml-auto px-2 py-0.5 rounded-full bg-emerald-500 text-white text-[9px] font-black">10% DTO</span></button>
              <button type="button" data-private-panel="requests" onclick="switchPrivateDashboardPanel('requests', true)" class="private-dashboard-nav"><span>📩</span><span>Avisos</span><span id="private-sidebar-alerts" class="ml-auto px-2 py-0.5 rounded-full bg-amber-light text-amber text-[9px] font-black">0</span></button>
              <button type="button" data-private-panel="operations" onclick="switchPrivateDashboardPanel('operations', true)" class="private-dashboard-nav"><span>⚖️</span><span>Operaciones</span><span id="private-sidebar-operations-badge" class="ml-auto px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 text-[9px] font-black">0</span></button>
              <button type="button" data-private-panel="favorites" onclick="switchPrivateDashboardPanel('favorites', true)" class="private-dashboard-nav"><span>♥</span><span>Favoritos</span></button>
              <button id="private-nav-tasks" type="button" data-private-panel="tasks" onclick="switchPrivateDashboardPanel('tasks', true)" class="private-dashboard-nav"><span>📅</span><span>Calendario</span><span id="private-sidebar-tasks" class="ml-auto px-2 py-0.5 rounded-full bg-blue-light text-blue text-[9px] font-black">0</span></button>
              
              <div class="my-2 border-t border-slate-200 dark:border-slate-800"></div>

              <button type="button" data-private-panel="communications" onclick="switchPrivateDashboardPanel('communications', true)" class="private-dashboard-nav"><span>💬</span><span>Chat</span><span id="private-sidebar-messages" class="ml-auto px-2 py-0.5 rounded-full bg-green-light text-green text-[9px] font-black">0</span></button>
              <button type="button" data-private-panel="feeds" onclick="switchPrivateDashboardPanel('feeds', true)" class="private-dashboard-nav"><span>↻</span><span>Importaciones</span></button>
              <button type="button" data-private-panel="profile" onclick="switchPrivateDashboardPanel('profile', true)" class="private-dashboard-nav"><span>👤</span><span>Perfil profesional</span></button>
              <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="private-dashboard-nav"><span>?</span><span>Contacto</span></a>
            </nav>
          </aside>

          <div class="min-w-0">
            <!-- RESUMEN -->
            <div id="private-panel-overview" class="private-dashboard-panel active">
              <div class="exec-dashboard">
                <header class="exec-head">
                  <div><h3 id="exec-greeting-title">Hola, Agente profesional</h3><p>Visión general de tu actividad y oportunidades comerciales</p></div>
                  <div class="exec-head-actions">
                    <button type="button" onclick="logoutDemo()" class="exec-control hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400 transition-colors" aria-label="Cerrar sesión de usuario"><span aria-hidden="true">×</span> Cerrar sesión</button>
                    <label class="exec-control" aria-label="Seleccionar periodo del resumen"><span class="sr-only">Periodo mostrado</span><select id="exec-period-label" onchange="setExecutivePeriod(this.value)" class="bg-transparent border-0 outline-none font-inherit cursor-pointer"><option value="7d">Últimos 7 días</option><option value="30d" selected>Últimos 30 días</option><option value="90d">Últimos 90 días</option><option value="ytd">Año actual</option></select></label>
                    <button id="exec-export-button" type="button" onclick="exportExecutiveDashboard()" class="exec-control" aria-label="Exportar resumen ejecutivo en PDF"><span aria-hidden="true">⇩</span> Exportar PDF</button>
                  </div>
                </header>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                  <!-- 1. Perfil Profesional -->
                  <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                      <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-500">Perfil Profesional</span>
                        <span id="dashboard-profile-completion-value" class="text-xs font-black text-blue">16%</span>
                      </div>
                      <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                        <div id="dashboard-profile-completion-bar" class="h-full rounded-full bg-blue transition-all" style="width: 16%"></div>
                      </div>
                      <p id="dashboard-profile-completion-help" class="text-[10px] text-slate-400 mt-1 truncate">Completa tus datos para obtener la Insignia Verificada 50/50.</p>
                    </div>
                    <button type="button" onclick="switchPrivateDashboardPanel('profile')" class="shrink-0 px-3 py-2 rounded-xl bg-navy dark:bg-blue hover:opacity-90 text-white text-[10px] font-bold shadow-sm">
                      Completar
                    </button>
                  </div>

                  <!-- 2. Créditos y Monedero -->
                  <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                      <span id="dashboard-credit-summary-title" class="text-[11px] font-black uppercase tracking-wider text-slate-500">Créditos Disponibles</span>
                      <div class="flex items-baseline gap-1.5 mt-0.5">
                        <strong id="dashboard-credit-summary-value" class="text-xl font-black text-navy dark:text-white">3,00</strong>
                        <span class="text-[11px] text-slate-400">créditos</span>
                      </div>
                      <p id="dashboard-credit-summary-help" class="text-[10px] text-slate-400 mt-0.5 truncate">3 créditos de bienvenida activos (válidos 30 días, no acumulables)</p>
                    </div>
                    <button type="button" onclick="switchPrivateDashboardPanel('credits')" class="shrink-0 px-3 py-2 rounded-xl bg-blue hover:bg-blue-dark text-white text-[10px] font-bold shadow-sm">
                      Comprar créditos
                    </button>
                  </div>

                  <!-- 2. Reputación y ranking profesional -->
                  <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                      <span class="text-[11px] font-black uppercase tracking-wider text-slate-500">Ranking profesional</span>
                      <div class="flex items-baseline gap-1.5 mt-0.5">
                        <strong id="dashboard-reputation-score" class="text-xl font-black text-navy dark:text-white">—</strong>
                        <span class="text-[11px] text-slate-400">/100</span>
                      </div>
                      <p id="dashboard-reputation-help" class="text-[10px] text-slate-400 mt-0.5 truncate">Calculando tu reputación...</p>
                    </div>
                    <span id="dashboard-reputation-category" class="shrink-0 px-2.5 py-1 rounded-full bg-blue/10 text-blue text-[10px] font-black">—</span>
                  </div>

                  <!-- 3. Plan Activo y Colaboración 50/50 -->
                  <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                      <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Estado de Cuenta</span>
                      <div class="flex items-center gap-1.5 mt-0.5">
                        <strong class="text-xs font-black text-navy dark:text-white">Plan Profesional Fundador</strong>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 text-[9px] font-black">Activo</span>
                      </div>
                      <p class="text-[10px] text-slate-400 mt-0.5 truncate">Fichas ciegas y cruces ilimitados</p>
                    </div>
                    <button type="button" onclick="switchPrivateDashboardPanel('credits')" class="shrink-0 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue text-slate-700 dark:text-slate-300 text-[10px] font-bold">
                      Ver libro mayor
                    </button>
                  </div>
                </div>



                <?php if (defined('CMC_VERSION')) : ?>
                <section class="exec-credit-banner" aria-label="Acceso rápido a créditos">
                  <div class="exec-credit-banner-copy"><span class="exec-credit-banner-kicker">Descubre tus créditos</span><p class="exec-credit-banner-text">Consulta tus créditos, sus ventajas y cómo conseguir más dentro de la plataforma.</p></div>
                  <button type="button" class="exec-credit-banner-action" onclick="switchPrivateDashboardPanel('credits', true)">Ver mi sección de créditos</button>
                </section>
                <?php endif; ?>
                <section class="exec-kpis">
                  <button type="button" onclick="openExecutiveDestination('offers')" class="exec-card exec-kpi exec-kpi-blue" aria-label="Acceder a captaciones publicadas"><div class="exec-kpi-top"><span class="exec-icon">▥</span><div><span class="exec-kpi-label">Captaciones publicadas</span><strong id="exec-kpi-offers">0</strong></div></div><p id="exec-kpi-offers-value" class="exec-kpi-value">0 € en cartera</p><p class="exec-trend neutral"><b>—</b> Datos actuales</p><span class="exec-card-cta">Acceder a captaciones →</span></button>
                  <button type="button" onclick="openExecutiveDestination('demands')" class="exec-card exec-kpi exec-kpi-green" aria-label="Acceder a demandas activas"><div class="exec-kpi-top"><span class="exec-icon">⌘</span><div><span class="exec-kpi-label">Demandas activas</span><strong id="exec-kpi-demands">0</strong></div></div><p id="exec-kpi-demands-value" class="exec-kpi-value">0 € en demandas</p><p class="exec-trend neutral"><b>—</b> Datos actuales</p><span class="exec-card-cta">Acceder a demandas →</span></button>
                  <button type="button" onclick="openExecutiveDestination('matches')" class="exec-card exec-kpi exec-kpi-yellow" aria-label="Ver coincidencias"><div class="exec-kpi-top"><span class="exec-icon">◎</span><div><span class="exec-kpi-label">Coincidencias</span><strong id="exec-kpi-matches">0</strong></div></div><p id="exec-kpi-matches-value" class="exec-kpi-value">0 € en cruces</p><p class="exec-trend neutral"><b>—</b> Datos actuales</p><span class="exec-card-cta">Ver coincidencias →</span></button>
                  <button type="button" onclick="openExecutiveDestination('operations')" class="exec-card exec-kpi exec-kpi-violet" aria-label="Ver operaciones en curso"><div class="exec-kpi-top"><span class="exec-icon">◇</span><div><span class="exec-kpi-label">Operaciones en curso</span><strong id="exec-kpi-operations">0</strong></div></div><p id="exec-kpi-operations-value" class="exec-kpi-value">0 € en operaciones</p><p class="exec-trend neutral"><b>—</b> Datos actuales</p><span class="exec-card-cta">Ver operaciones →</span></button>
                  <button type="button" onclick="openExecutiveDestination('offers')" class="exec-card exec-pipeline exec-clickable" aria-label="Acceder al pipeline de honorarios 50/50"><p class="exec-pipeline-label">Valor total del pipeline</p><strong id="exec-pipeline-value">0 €</strong><p class="text-[10px] text-blue font-bold mt-0.5 tracking-tight">Honorarios 50/50 (Estudio InmoAdvisor 2026)</p><svg class="exec-sparkline" viewBox="0 0 250 60" preserveAspectRatio="none" aria-label="Evolución del pipeline"><defs><linearGradient id="execSparkGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4c83ff" stop-opacity=".38"/><stop offset="1" stop-color="#4c83ff" stop-opacity="0"/></linearGradient></defs><path class="area" d="M3,52 L35,31 L68,38 L99,18 L130,33 L159,12 L190,29 L220,15 L247,4 L247,58 L3,58 Z"/><path class="line" d="M3,52 L35,31 L68,38 L99,18 L130,33 L159,12 L190,29 L220,15 L247,4"/><g fill="#4c83ff"><circle cx="3" cy="52" r="3"/><circle cx="35" cy="31" r="3"/><circle cx="68" cy="38" r="3"/><circle cx="99" cy="18" r="3"/><circle cx="130" cy="33" r="3"/><circle cx="159" cy="12" r="3"/><circle cx="190" cy="29" r="3"/><circle cx="220" cy="15" r="3"/><circle cx="247" cy="4" r="4"/></g></svg><div class="exec-months"><span>Mar</span><span>Abr</span><span>May</span><span>Jun</span><span>Jul</span><span>Ago 2026</span></div><span class="exec-card-cta">Ver pipeline →</span></button>
                </section>
                <section class="exec-central">
                  <!-- DISTRIBUCIÓN GENERAL -->
                  <article class="exec-card exec-panel">
                    <h4 class="exec-panel-title">Distribución general</h4>
                    <div class="exec-distribution">
                      <div class="exec-donut">
                        <svg class="exec-donut-svg" viewBox="0 0 100 100" role="group" aria-label="Distribución general interactiva">
                          <circle tabindex="0" role="link" onclick="openExecutiveDestination('offers')" onkeydown="activateExecutiveKey(event,'offers')" aria-label="Captaciones" class="exec-donut-segment" cx="50" cy="50" r="38" pathLength="100" stroke="#3d78f4" stroke-dasharray="0 100" stroke-dashoffset="0"/>
                          <circle tabindex="0" role="link" onclick="openExecutiveDestination('demands')" onkeydown="activateExecutiveKey(event,'demands')" aria-label="Demandas" class="exec-donut-segment" cx="50" cy="50" r="38" pathLength="100" stroke="#32bd83" stroke-dasharray="0 100" stroke-dashoffset="0"/>
                          <circle tabindex="0" role="link" onclick="openExecutiveDestination('requests')" onkeydown="activateExecutiveKey(event,'requests')" aria-label="Solicitudes" class="exec-donut-segment" cx="50" cy="50" r="38" pathLength="100" stroke="#f0b91c" stroke-dasharray="0 100" stroke-dashoffset="0"/>
                          <circle tabindex="0" role="link" onclick="openExecutiveDestination('matches')" onkeydown="activateExecutiveKey(event,'matches')" aria-label="Coincidencias" class="exec-donut-segment" cx="50" cy="50" r="38" pathLength="100" stroke="#7247e8" stroke-dasharray="0 100" stroke-dashoffset="0"/>
                        </svg>
                        <span class="exec-donut-hole" aria-hidden="true"></span>
                        <div class="exec-donut-center"><strong id="exec-total-opportunities">0</strong><span>Total oportunidades</span></div>
                      </div>
                      
                      <!-- Tabla con columnas: Concepto | Cantidades | Porciento | Valor EUR -->
                      <div class="exec-legend-wrap overflow-x-auto">
                        <table class="exec-metric-table w-full text-xs">
                          <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-400">
                              <th class="py-2 text-left">Concepto</th>
                              <th class="py-2 px-2 text-center">Cant.</th>
                              <th class="py-2 px-2 text-center">%</th>
                              <th class="py-2 text-right">Valor (EUR)</th>
                            </tr>
                          </thead>
                          <tbody id="exec-distribution-rows">
                            <tr onclick="openExecutiveDestination('offers')" class="exec-table-row hover:bg-slate-100 dark:hover:bg-slate-800/60 cursor-pointer border-b border-slate-100 dark:border-slate-800/40">
                              <td class="py-2.5 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#3d78f4] inline-block shrink-0"></span>
                                <span class="font-bold text-navy dark:text-white">Captaciones</span>
                              </td>
                              <td class="py-2.5 px-2 text-center font-extrabold text-navy dark:text-white" id="exec-dist-qty-offers">0</td>
                              <td class="py-2.5 px-2 text-center font-bold text-slate-600 dark:text-slate-300" id="exec-dist-pct-offers">0%</td>
                              <td class="py-2.5 text-right font-black text-blue" id="exec-dist-val-offers">0 €</td>
                            </tr>
                            <tr onclick="openExecutiveDestination('demands')" class="exec-table-row hover:bg-slate-100 dark:hover:bg-slate-800/60 cursor-pointer border-b border-slate-100 dark:border-slate-800/40">
                              <td class="py-2.5 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#32bd83] inline-block shrink-0"></span>
                                <span class="font-bold text-navy dark:text-white">Demandas</span>
                              </td>
                              <td class="py-2.5 px-2 text-center font-extrabold text-navy dark:text-white" id="exec-dist-qty-demands">0</td>
                              <td class="py-2.5 px-2 text-center font-bold text-slate-600 dark:text-slate-300" id="exec-dist-pct-demands">0%</td>
                              <td class="py-2.5 text-right font-black text-green" id="exec-dist-val-demands">0 €</td>
                            </tr>
                            <tr onclick="openExecutiveDestination('requests')" class="exec-table-row hover:bg-slate-100 dark:hover:bg-slate-800/60 cursor-pointer border-b border-slate-100 dark:border-slate-800/40">
                              <td class="py-2.5 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#f0b91c] inline-block shrink-0"></span>
                                <span class="font-bold text-navy dark:text-white">Solicitudes</span>
                              </td>
                              <td class="py-2.5 px-2 text-center font-extrabold text-navy dark:text-white" id="exec-dist-qty-requests">0</td>
                              <td class="py-2.5 px-2 text-center font-bold text-slate-600 dark:text-slate-300" id="exec-dist-pct-requests">0%</td>
                              <td class="py-2.5 text-right font-black text-amber" id="exec-dist-val-requests">0 €</td>
                            </tr>
                            <tr onclick="openExecutiveDestination('matches')" class="exec-table-row hover:bg-slate-100 dark:hover:bg-slate-800/60 cursor-pointer">
                              <td class="py-2.5 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#7247e8] inline-block shrink-0"></span>
                                <span class="font-bold text-navy dark:text-white">Coincidencias</span>
                              </td>
                              <td class="py-2.5 px-2 text-center font-extrabold text-navy dark:text-white" id="exec-dist-qty-matches">0</td>
                              <td class="py-2.5 px-2 text-center font-bold text-slate-600 dark:text-slate-300" id="exec-dist-pct-matches">0%</td>
                              <td class="py-2.5 text-right font-black text-purple-600 dark:text-purple-400" id="exec-dist-val-matches">0 €</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </article>

                  <!-- EMBUDO COMERCIAL -->
                  <article class="exec-card exec-panel">
                    <h4 class="exec-panel-title">Embudo comercial</h4>
                    <div class="exec-funnel-grid">
                      <div class="exec-funnel">
                        <button type="button" onclick="openExecutiveDestination('offers')" class="exec-funnel-step" aria-label="Acceder a captaciones publicadas"></button>
                        <button type="button" onclick="openExecutiveDestination('requests')" class="exec-funnel-step" aria-label="Acceder a solicitudes recibidas"></button>
                        <button type="button" onclick="openExecutiveDestination('matches')" class="exec-funnel-step" aria-label="Acceder a coincidencias"></button>
                        <button type="button" onclick="openExecutiveDestination('operations')" class="exec-funnel-step" aria-label="Acceder a operaciones en curso"></button>
                        <button type="button" onclick="openExecutiveDestination('operations-closed')" class="exec-funnel-step" aria-label="Acceder a operaciones cerradas"></button>
                      </div>
                      
                      <!-- Tabla con columnas: Fase | Cantidades | Porciento | Valor EUR -->
                      <div class="exec-funnel-wrap overflow-x-auto">
                        <table class="exec-funnel-table w-full text-xs">
                          <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-400">
                              <th class="py-2 text-left">Fase del embudo</th>
                              <th class="py-2 px-2 text-center">Cant.</th>
                              <th class="py-2 px-2 text-center">%</th>
                              <th class="py-2 text-right">Valor (EUR)</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr onclick="openExecutiveDestination('offers')" class="exec-table-row hover:bg-slate-100 dark:hover:bg-slate-800/60 cursor-pointer border-b border-slate-100 dark:border-slate-800/40">
                              <td class="py-2 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#0052ec] inline-block shrink-0"></span>
                                <span class="font-bold text-navy dark:text-white">Captaciones publicadas</span>
                              </td>
                              <td class="py-2 px-2 text-center font-extrabold text-navy dark:text-white" id="exec-funnel-offers">0</td>
                              <td class="py-2 px-2 text-center font-bold text-slate-600 dark:text-slate-300" id="exec-funnel-offers-pct">100%</td>
                              <td class="py-2 text-right font-black text-blue" id="exec-funnel-offers-val">0 €</td>
                            </tr>
                            <tr onclick="openExecutiveDestination('requests')" class="exec-table-row hover:bg-slate-100 dark:hover:bg-slate-800/60 cursor-pointer border-b border-slate-100 dark:border-slate-800/40">
                              <td class="py-2 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#10b981] inline-block shrink-0"></span>
                                <span class="font-bold text-navy dark:text-white">Solicitudes recibidas</span>
                              </td>
                              <td class="py-2 px-2 text-center font-extrabold text-navy dark:text-white" id="exec-funnel-requests">0</td>
                              <td class="py-2 px-2 text-center font-bold text-slate-600 dark:text-slate-300" id="exec-funnel-requests-pct">0%</td>
                              <td class="py-2 text-right font-black text-green" id="exec-funnel-requests-val">0 €</td>
                            </tr>
                            <tr onclick="openExecutiveDestination('matches')" class="exec-table-row hover:bg-slate-100 dark:hover:bg-slate-800/60 cursor-pointer border-b border-slate-100 dark:border-slate-800/40">
                              <td class="py-2 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#f59e0b] inline-block shrink-0"></span>
                                <span class="font-bold text-navy dark:text-white">Coincidencias</span>
                              </td>
                              <td class="py-2 px-2 text-center font-extrabold text-navy dark:text-white" id="exec-funnel-matches">0</td>
                              <td class="py-2 px-2 text-center font-bold text-slate-600 dark:text-slate-300" id="exec-funnel-matches-pct">0%</td>
                              <td class="py-2 text-right font-black text-amber" id="exec-funnel-matches-val">0 €</td>
                            </tr>
                            <tr onclick="openExecutiveDestination('operations')" class="exec-table-row hover:bg-slate-100 dark:hover:bg-slate-800/60 cursor-pointer border-b border-slate-100 dark:border-slate-800/40">
                              <td class="py-2 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#8b5cf6] inline-block shrink-0"></span>
                                <span class="font-bold text-navy dark:text-white">Operaciones en curso</span>
                              </td>
                              <td class="py-2 px-2 text-center font-extrabold text-navy dark:text-white" id="exec-funnel-operations">0</td>
                              <td class="py-2 px-2 text-center font-bold text-slate-600 dark:text-slate-300" id="exec-funnel-operations-pct">0%</td>
                              <td class="py-2 text-right font-black text-purple-600 dark:text-purple-400" id="exec-funnel-operations-val">0 €</td>
                            </tr>
                            <tr onclick="openExecutiveDestination('operations-closed')" class="exec-table-row hover:bg-slate-100 dark:hover:bg-slate-800/60 cursor-pointer">
                              <td class="py-2 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#ec4899] inline-block shrink-0"></span>
                                <span class="font-bold text-navy dark:text-white">Operaciones cerradas</span>
                              </td>
                              <td class="py-2 px-2 text-center font-extrabold text-navy dark:text-white" id="exec-funnel-closed">0</td>
                              <td class="py-2 px-2 text-center font-bold text-slate-600 dark:text-slate-300" id="exec-funnel-closed-pct">0%</td>
                              <td class="py-2 text-right font-black text-pink-600 dark:text-pink-400" id="exec-funnel-closed-val">0 €</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </article>
                </section>
                <section class="exec-lower">
                  <article class="exec-card exec-list-card"><div class="exec-list-head"><h4>Últimas solicitudes</h4><button onclick="switchPrivateDashboardPanel('requests')">Ver todas</button></div><div id="exec-latest-requests"></div></article>
                  <article class="exec-card exec-list-card"><div class="exec-list-head"><h4>Últimas coincidencias</h4><button onclick="openExecutiveDestination('matches')" aria-label="Ver todas las coincidencias">Ver todas</button></div><div id="exec-latest-matches"></div></article>
                  <article class="exec-card exec-list-card"><div class="exec-list-head"><h4>Tareas pendientes</h4><button onclick="switchPrivateDashboardPanel('tasks')">Ver todas</button></div><div id="exec-pending-tasks"></div></article>
                </section>
                <section class="exec-card exec-summary">
                  <button type="button" onclick="openExecutiveDestination('requests')" class="exec-summary-item exec-clickable" aria-label="Ver solicitudes recibidas"><span class="exec-summary-icon" style="color:#f0b91c;background:rgba(240,185,28,.12)">⌁</span><div class="exec-summary-copy"><span>Solicitudes recibidas</span><strong id="exec-requests-count">2</strong><span class="exec-card-cta">Ver solicitudes →</span></div></button>
                  <button type="button" onclick="openExecutiveDestination('notifications')" class="exec-summary-item exec-clickable" aria-label="Ver avisos sin leer"><span class="exec-summary-icon" style="color:#f05a9a;background:rgba(240,90,154,.12)">✉</span><div class="exec-summary-copy"><span>Avisos sin leer</span><strong id="exec-unread-count">6</strong><span class="exec-card-cta">Ver avisos →</span></div></button>
                  <button type="button" onclick="openExecutiveDestination('favorites')" class="exec-summary-item exec-clickable" aria-label="Ver favoritos"><span class="exec-summary-icon" style="color:#f43f5e;background:rgba(244,63,94,.12)">♥</span><div class="exec-summary-copy"><span>Favoritos</span><strong id="exec-favorites-count">0</strong><span class="exec-card-cta">Ver favoritos →</span></div></button>
                  <button type="button" onclick="openExecutiveDestination('clients')" class="exec-summary-item exec-clickable" aria-label="Ver clientes asignados"><span class="exec-summary-icon">♙</span><div class="exec-summary-copy"><span>Clientes asignados</span><strong id="exec-clients-count">3</strong><span class="exec-card-cta">Ver clientes →</span></div></button>
                  <button type="button" onclick="openExecutiveDestination('leads')" class="exec-summary-item exec-clickable" aria-label="Ver leads activos"><span class="exec-summary-icon" style="color:#6ce39b;background:rgba(49,190,119,.12)">♟</span><div class="exec-summary-copy"><span>Leads activos</span><strong id="exec-leads-count">3</strong><span class="exec-card-cta">Ver leads →</span></div></button>
                  <button type="button" onclick="openExecutiveDestination('tasks')" class="exec-summary-item exec-clickable" aria-label="Ver tareas pendientes"><span class="exec-summary-icon" style="color:#8bb5ff;background:rgba(61,120,244,.12)">✓</span><div class="exec-summary-copy"><span>Tareas pendientes</span><strong id="exec-tasks-count">0</strong><span class="exec-card-cta">Ver tareas →</span></div></button>
                </section>

                <!-- ASISTENTE IA VERA: INFORMACIÓN PRÓXIMAMENTE (SIN BOTONES DE ACCESO) -->
                <div class="mt-6 p-5 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue via-indigo-500 to-purple-600 flex items-center justify-center text-white text-xl shadow-md shrink-0">
                        ✦
                      </div>
                      <div>
                        <div class="flex flex-wrap items-center gap-2">
                          <h4 class="text-base font-black text-navy dark:text-white">Vera · Asistente con Inteligencia Artificial</h4>
                           <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 text-[10px] font-black uppercase tracking-wider">Disponible</span>
                        </div>
                        <p class="text-xs sm:text-[13px] text-slate-500 dark:text-slate-400 mt-1.5 max-w-2xl leading-relaxed">
                           Vera ya está disponible para ayudarte paso a paso con el onboarding, el cruce de captaciones y demandas, la redacción de fichas ciegas protegidas y la orientación del protocolo 50/50.
                        </p>
                      </div>
                    </div>
                    <div class="shrink-0 flex items-center">
                      <span class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700 shadow-xs">
                         <button type="button" onclick="toggleVeraChat(event)" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-bold shadow-xs transition-colors"><span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span> Preguntar a Vera →</button>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ACADEMIA COMPRA CAPTACIÓN: E-LEARNING DASHBOARD INDEPENDIENTE -->
            <div id="private-panel-academy" class="private-dashboard-panel">
              <div class="mb-6 p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-slate-900 via-navy to-slate-900 text-white shadow-xl border border-slate-700/60 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                  <div class="max-w-2xl">
                    <div class="flex flex-wrap items-center gap-3 mb-2">
                      <span class="p-2.5 rounded-2xl bg-blue/30 text-blue-300 text-xl font-black border border-blue-400/30 shadow-inner">🎓</span>
                      <h3 class="text-2xl sm:text-3xl font-black tracking-tight text-white">Academia Compra Captación</h3>
                      <span id="academy-rank-badge" class="px-3.5 py-1 rounded-full bg-gradient-to-r from-blue to-indigo-600 text-white text-xs font-black shadow-xs uppercase tracking-wider">Iniciado MLS</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">Domina el ecosistema de colaboración al 50/50, protege tus exclusivas con datos ciegos y aprende a cerrar operaciones conjuntas con total seguridad jurídica.</p>
                  </div>
                  <!-- Selector de Perfil y Acciones Rápidas -->
                  <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="inline-flex rounded-2xl bg-slate-800/90 p-1.5 border border-slate-700 text-xs">
                      <button type="button" id="academy-btn-junior" onclick="setAcademyUserLevel('junior')" class="px-4 py-2 rounded-xl font-bold transition-all bg-blue text-white shadow-xs">Junior (Guiado)</button>
                      <button type="button" id="academy-btn-senior" onclick="setAcademyUserLevel('senior')" class="px-4 py-2 rounded-xl font-bold transition-all text-slate-400 hover:text-white">Senior (Ejecutivo)</button>
                    </div>
                    <button type="button" onclick="openVeraWithContext('academy', 'general-help', 0, 'Hola Vera, ¿cómo me recomiendas empezar la Academia de Compra Captación?')" class="px-4 py-2.5 rounded-2xl bg-white hover:bg-slate-100 text-navy text-xs font-black shadow-md transition-all flex items-center justify-center gap-2">
                      <span class="text-blue text-base">✨</span>
                      <span>Consultar a Vera</span>
                    </button>
                  </div>
                </div>

                <!-- Métricas de Progreso E-Learning Hub -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-700/60 relative z-10">
                  <!-- Métrica 1: Progreso Global -->
                  <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/50 flex items-center gap-4">
                    <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                      <svg class="w-12 h-12 -rotate-90" viewBox="0 0 36 36">
                        <path class="text-slate-700" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path id="academy-donut-bar" class="text-blue transition-all duration-500" stroke-width="3.5" stroke-dasharray="0, 100" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                      </svg>
                      <span id="academy-donut-pct" class="absolute text-[11px] font-black text-white">0%</span>
                    </div>
                    <div>
                      <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Progreso Global</span>
                      <strong id="academy-progress-percent" class="text-sm font-black text-white">0% completado</strong>
                    </div>
                  </div>

                  <!-- Métrica 2: Fases Superadas -->
                  <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/50 flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-lg font-black shrink-0">✓</span>
                    <div>
                      <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Fases de Maestría</span>
                      <strong id="academy-progress-text" class="text-sm font-black text-white">0 de 7 superadas</strong>
                    </div>
                  </div>

                  <!-- Métrica 3: Tutoría IA -->
                  <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/50 flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-300 flex items-center justify-center text-lg font-black shrink-0">✨</span>
                    <div>
                      <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Tutora Vera AI</span>
                      <strong class="text-sm font-black text-white">Asistencia Activa 24/7</strong>
                    </div>
                  </div>
                </div>

                <!-- Barra de Progreso Continua -->
                <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden mt-4">
                  <div id="academy-progress-bar" class="h-full bg-gradient-to-r from-blue via-indigo-500 to-emerald-400 transition-all duration-500 rounded-full" style="width: 0%;"></div>
                </div>
              </div>

              <!-- GRID DE LAS 7 FASES MODULARES -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="academy-phases-container">
                
                <!-- Fase 1: Orientación MLS -->
                <div class="academy-phase-card p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between" data-phase="phase-1-orientation">
                  <div>
                    <div class="flex items-center justify-between mb-3">
                      <span class="w-7 h-7 rounded-xl bg-blue/10 text-blue font-black text-xs flex items-center justify-center">1</span>
                      <span class="academy-phase-badge text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" id="badge-phase-1-orientation">Pendiente</span>
                    </div>
                    <strong class="block text-sm font-black text-navy dark:text-white">1. Orientación MLS 50/50</strong>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-junior">Aprende cómo funciona el reparto 50/50, la ética entre agencias y el blindaje de datos ciegos.</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-senior hidden">Protocolo de colaboración ética, comisiones bilaterales y protección registral de cartera.</p>
                  </div>
                  <div class="mt-4 flex flex-col gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="openVeraWithContext('academy', 'phase-1-orientation', 1, 'Explícame cómo funciona el modelo de colaboración 50/50 y la protección de datos ciegos')" class="w-full py-2 px-3 rounded-xl bg-blue/10 hover:bg-blue text-blue hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                      <span>✨ Preguntar a Vera</span>
                    </button>
                    <button type="button" onclick="toggleAcademyPhase('phase-1-orientation')" class="w-full py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-emerald-500 hover:text-white text-slate-700 dark:text-slate-300 text-xs font-bold transition-all" id="btn-toggle-phase-1-orientation">
                      Marcar como completada
                    </button>
                  </div>
                </div>

                <!-- Fase 2: Perfil Profesional -->
                <div class="academy-phase-card p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between" data-phase="phase-2-profile">
                  <div>
                    <div class="flex items-center justify-between mb-3">
                      <span class="w-7 h-7 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-black text-xs flex items-center justify-center">2</span>
                      <span class="academy-phase-badge text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" id="badge-phase-2-profile">Pendiente</span>
                    </div>
                    <strong class="block text-sm font-black text-navy dark:text-white">2. Perfil Profesional</strong>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-junior">Añade NIF, zonas INE de actuación y logotipo para verificar tu agencia (+40% de respuestas).</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-senior hidden">Configuración territorial INE, datos fiscales y credenciales colegiadas para homologación.</p>
                  </div>
                  <div class="mt-4 flex flex-col gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="switchPrivateDashboardPanel('profile')" class="w-full py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-navy hover:text-white text-navy dark:text-slate-200 text-xs font-bold transition-all text-center">
                      Editar perfil →
                    </button>
                    <button type="button" onclick="openVeraWithContext('academy', 'phase-2-profile', 2, '¿Qué datos debo incluir en mi perfil para tener máxima confianza con otras agencias?')" class="w-full py-2 px-3 rounded-xl bg-blue/10 hover:bg-blue text-blue hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                      <span>✨ Preguntar a Vera</span>
                    </button>
                  </div>
                </div>

                <!-- Fase 3: Publicar Captación -->
                <div class="academy-phase-card p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between" data-phase="phase-3-captation">
                  <div>
                    <div class="flex items-center justify-between mb-3">
                      <span class="w-7 h-7 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-black text-xs flex items-center justify-center">3</span>
                      <span class="academy-phase-badge text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" id="badge-phase-3-captation">Pendiente</span>
                    </div>
                    <strong class="block text-sm font-black text-navy dark:text-white">3. Publicar Captación</strong>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-junior">Sube tu inmueble en 3 pasos con dirección ciega para evitar que puenteen tu exclusiva.</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-senior hidden">Ingesta de producto exclusivo, parametrización de honorarios 50/50 y blindaje registral.</p>
                  </div>
                  <div class="mt-4 flex flex-col gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <a href="#/ofrecer-captacion" class="w-full py-2 px-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-bold transition-all text-center shadow-xs">
                      + Publicar Captación
                    </a>
                    <button type="button" onclick="openVeraWithContext('academy', 'phase-3-captation', 3, '¿Cómo redacto una ficha de captación atractiva sin desvelar la dirección exacta?')" class="w-full py-2 px-3 rounded-xl bg-blue/10 hover:bg-blue text-blue hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                      <span>✨ Preguntar a Vera</span>
                    </button>
                  </div>
                </div>

                <!-- Fase 4: Publicar Demanda -->
                <div class="academy-phase-card p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between" data-phase="phase-4-demand">
                  <div>
                    <div class="flex items-center justify-between mb-3">
                      <span class="w-7 h-7 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-black text-xs flex items-center justify-center">4</span>
                      <span class="academy-phase-badge text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" id="badge-phase-4-demand">Pendiente</span>
                    </div>
                    <strong class="block text-sm font-black text-navy dark:text-white">4. Publicar Demanda</strong>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-junior">Registra la búsqueda de tu cliente comprador para recibir avisos de cruce inmediatos.</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-senior hidden">Filtro de demanda solvente con validación financiera para matching automático.</p>
                  </div>
                  <div class="mt-4 flex flex-col gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <a href="#/buscar-captaciones" class="w-full py-2 px-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition-all text-center shadow-xs">
                      + Publicar Demanda
                    </a>
                    <button type="button" onclick="openVeraWithContext('academy', 'phase-4-demand', 4, '¿Qué campos son indispensables al publicar la demanda de un comprador solvente?')" class="w-full py-2 px-3 rounded-xl bg-blue/10 hover:bg-blue text-blue hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                      <span>✨ Preguntar a Vera</span>
                    </button>
                  </div>
                </div>

                <!-- Fase 5: Interpretar Coincidencias -->
                <div class="academy-phase-card p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between" data-phase="phase-5-matches">
                  <div>
                    <div class="flex items-center justify-between mb-3">
                      <span class="w-7 h-7 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 font-black text-xs flex items-center justify-center">5</span>
                      <span class="academy-phase-badge text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" id="badge-phase-5-matches">Pendiente</span>
                    </div>
                    <strong class="block text-sm font-black text-navy dark:text-white">5. Interpretar Cruces</strong>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-junior">Descubre qué significa el % de encaje y cuándo tiene sentido contactar al otro agente.</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-senior hidden">Análisis algorítmico de afinidad por precio, zona INE y tipología de inmueble.</p>
                  </div>
                  <div class="mt-4 flex flex-col gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="navigateTo('/coincidencias-ventas')" class="w-full py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-amber-500 hover:text-white text-navy dark:text-slate-200 text-xs font-bold transition-all text-center">
                      Ver coincidencias →
                    </button>
                    <button type="button" onclick="openVeraWithContext('academy', 'phase-5-matches', 5, '¿Cómo evalúo si una coincidencia tiene alto potencial antes de usar un crédito?')" class="w-full py-2 px-3 rounded-xl bg-blue/10 hover:bg-blue text-blue hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                      <span>✨ Preguntar a Vera</span>
                    </button>
                  </div>
                </div>

                <!-- Fase 6: Solicitar Colaboración y NDA -->
                <div class="academy-phase-card p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between" data-phase="phase-6-collaboration">
                  <div>
                    <div class="flex items-center justify-between mb-3">
                      <span class="w-7 h-7 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 font-black text-xs flex items-center justify-center">6</span>
                      <span class="academy-phase-badge text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" id="badge-phase-6-collaboration">Pendiente</span>
                    </div>
                    <strong class="block text-sm font-black text-navy dark:text-white">6. Colaboración y NDA</strong>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-junior">Solicita colaboración, descarga el acuerdo digital 50/50 y formaliza el pacto antes de la visita.</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-senior hidden">Descarga de acuerdos marco, firma bilateral y liberación segura de contacto registral.</p>
                  </div>
                  <div class="mt-4 flex flex-col gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="navigateTo('/marketplace')" class="w-full py-2 px-3 rounded-xl bg-purple-50 dark:bg-purple-950/40 hover:bg-purple-600 hover:text-white text-purple-700 dark:text-purple-300 text-xs font-bold transition-all text-center">
                      Explorar Marketplace →
                    </button>
                    <button type="button" onclick="openVeraWithContext('academy', 'phase-6-collaboration', 6, '¿Cómo garantizamos que el reparto de honorarios al 50/50 sea vinculante entre ambas agencias?')" class="w-full py-2 px-3 rounded-xl bg-blue/10 hover:bg-blue text-blue hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                      <span>✨ Preguntar a Vera</span>
                    </button>
                  </div>
                </div>

                <!-- Fase 7: Cierre y Métricas -->
                <div class="academy-phase-card p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between" data-phase="phase-7-closing">
                  <div>
                    <div class="flex items-center justify-between mb-3">
                      <span class="w-7 h-7 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 font-black text-xs flex items-center justify-center">7</span>
                      <span class="academy-phase-badge text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400" id="badge-phase-7-closing">Pendiente</span>
                    </div>
                    <strong class="block text-sm font-black text-navy dark:text-white">7. Cierre y Comisiones</strong>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-junior">Registra el estado de la visita, negociación de arras y el cobro de tu comisión compartida.</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed academy-desc-senior hidden">Pipeline de cierre, libro mayor de créditos consumidos y contabilidad de honorarios.</p>
                  </div>
                  <div class="mt-4 flex flex-col gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="openExecutiveDestination('operations')" class="w-full py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-rose-500 hover:text-white text-navy dark:text-slate-200 text-xs font-bold transition-all text-center">
                      Ver Operaciones →
                    </button>
                    <button type="button" onclick="openVeraWithContext('academy', 'phase-7-closing', 7, '¿Qué pasos debo seguir para registrar el cierre de una operación compartida?')" class="w-full py-2 px-3 rounded-xl bg-blue/10 hover:bg-blue text-blue hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                      <span>✨ Preguntar a Vera</span>
                    </button>
                  </div>
                </div>

              </div>
            </div>

            <!-- OFREZCO CAPTACIÓN -->
            <div id="private-panel-offers" class="private-dashboard-panel">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4"><div><h3 class="text-xl font-black text-navy">Mis anuncios</h3><p class="text-xs text-slate-500 mt-1">Gestiona desde un único lugar lo que compartes y lo que buscan tus clientes.</p></div><a href="#/ofrecer-captacion" class="px-4 py-3 rounded-xl bg-blue text-white text-xs font-bold">+ Compartir captación</a></div>
              <div class="private-panel-tabs" role="tablist" aria-label="Tipos de anuncios"><button type="button" class="private-panel-tab active" role="tab" aria-selected="true">Captaciones que comparto</button><button type="button" onclick="switchPrivateDashboardPanel('demands')" class="private-panel-tab" role="tab" aria-selected="false">Búsquedas de mis clientes</button></div>
              <div id="private-offers-summary" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5"></div>
              
              <!-- Barra de Acciones Masivas para Captaciones -->
              <div id="private-offers-bulk-bar" class="hidden mb-4 p-3.5 rounded-2xl bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 shadow-lg animate-fadeIn">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-blue animate-ping"></span>
                  <strong class="text-xs font-black"><span id="private-offers-selected-count">0</span> captación(es) seleccionada(s)</strong>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <button type="button" onclick="bulkUpdateRecordStatus('property', 'active')" class="px-3 py-1.5 rounded-xl bg-green hover:bg-green-dark text-white text-xs font-bold transition-all shadow-xs">▶ Reactivar</button>
                  <button type="button" onclick="bulkUpdateRecordStatus('property', 'paused')" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-all shadow-xs">⏸ Pausar</button>
                  <button type="button" onclick="bulkDeleteRecords('property')" class="px-3.5 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-all shadow-xs">🗑 Eliminar seleccionadas</button>
                  <button type="button" onclick="clearRecordSelection('property')" class="px-3 py-1.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-bold transition-all">Cancelar</button>
                </div>
              </div>

              <div class="private-section-card overflow-hidden"><div class="px-5 py-4 border-b border-slate-200 flex flex-wrap gap-3 items-center justify-between"><h4 class="text-sm font-black text-navy">Mis captaciones</h4><input id="private-offers-search" oninput="renderPrivateOffers()" placeholder="Buscar referencia, título o zona" class="px-3 py-2 rounded-xl border border-slate-200 text-xs min-w-[240px]" /></div><div class="overflow-x-auto"><table class="private-table w-full"><thead><tr><th class="px-3 py-3 w-10 text-center"><input type="checkbox" id="offers-select-all" onchange="toggleSelectAllRecords('property', this)" class="rounded text-blue cursor-pointer" title="Seleccionar todo" /></th><th class="px-4 py-3">Ref.</th><th class="px-4 py-3">Propiedad</th><th class="px-4 py-3">Precio</th><th class="px-4 py-3">Score</th><th class="px-4 py-3">Coincidencias</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Acciones</th></tr></thead><tbody id="private-offers-table"></tbody></table></div></div>
            </div>

            <!-- BUSCO CAPTACIÓN -->
            <div id="private-panel-demands" class="private-dashboard-panel">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4"><div><h3 class="text-xl font-black text-navy">Mis anuncios</h3><p class="text-xs text-slate-500 mt-1">Gestiona desde un único lugar lo que compartes y lo que buscan tus clientes.</p></div><a href="#/buscar-captaciones" class="px-4 py-3 rounded-xl bg-navy text-white text-xs font-bold">+ Buscar inmueble</a></div>
              <div class="private-panel-tabs" role="tablist" aria-label="Tipos de anuncios"><button type="button" onclick="switchPrivateDashboardPanel('offers')" class="private-panel-tab" role="tab" aria-selected="false">Captaciones que comparto</button><button type="button" class="private-panel-tab active" role="tab" aria-selected="true">Búsquedas de mis clientes</button></div>
              <div id="private-demands-summary" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5"></div>
              
              <!-- Barra de Acciones Masivas para Demandas -->
              <div id="private-demands-bulk-bar" class="hidden mb-4 p-3.5 rounded-2xl bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 shadow-lg animate-fadeIn">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                  <strong class="text-xs font-black"><span id="private-demands-selected-count">0</span> demanda(s) seleccionada(s)</strong>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <button type="button" onclick="bulkUpdateRecordStatus('need', 'active')" class="px-3 py-1.5 rounded-xl bg-green hover:bg-green-dark text-white text-xs font-bold transition-all shadow-xs">▶ Reactivar</button>
                  <button type="button" onclick="bulkUpdateRecordStatus('need', 'paused')" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-all shadow-xs">⏸ Pausar</button>
                  <button type="button" onclick="bulkDeleteRecords('need')" class="px-3.5 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-all shadow-xs">🗑 Eliminar seleccionadas</button>
                  <button type="button" onclick="clearRecordSelection('need')" class="px-3 py-1.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-bold transition-all">Cancelar</button>
                </div>
              </div>

              <div class="private-section-card overflow-hidden"><div class="px-5 py-4 border-b border-slate-200 flex flex-wrap gap-3 items-center justify-between"><h4 class="text-sm font-black text-navy">Mis demandas</h4><input id="private-demands-search" oninput="renderPrivateDemands()" placeholder="Buscar intención, referencia o zona" class="px-3 py-2 rounded-xl border border-slate-200 text-xs min-w-[240px]" /></div><div class="overflow-x-auto"><table class="private-table w-full"><thead><tr><th class="px-3 py-3 w-10 text-center"><input type="checkbox" id="demands-select-all" onchange="toggleSelectAllRecords('need', this)" class="rounded text-green cursor-pointer" title="Seleccionar todo" /></th><th class="px-4 py-3">Ref.</th><th class="px-4 py-3">Intención</th><th class="px-4 py-3">Presupuesto</th><th class="px-4 py-3">Coincidencias</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Acciones</th></tr></thead><tbody id="private-demands-table"></tbody></table></div></div>
            </div>

            <!-- SOLICITUDES -->
            <div id="private-panel-requests" class="private-dashboard-panel"><div class="mb-4"><h3 class="text-xl font-black text-navy">Avisos</h3><p class="text-xs text-slate-500 mt-1">Revisa lo que requiere tu atención y continúa cada colaboración.</p></div><div class="private-panel-tabs" role="tablist" aria-label="Tipos de avisos"><button type="button" class="private-panel-tab active" role="tab" aria-selected="true">Solicitudes pendientes</button><button type="button" onclick="switchPrivateDashboardPanel('operations')" class="private-panel-tab" role="tab" aria-selected="false">Operaciones</button><button type="button" onclick="switchPrivateDashboardPanel('notifications')" class="private-panel-tab" role="tab" aria-selected="false">Notificaciones</button></div><div class="grid grid-cols-1 xl:grid-cols-2 gap-5"><section class="private-section-card overflow-hidden"><div class="px-5 py-4 border-b border-slate-200"><h4 class="text-sm font-black text-navy">Solicitudes recibidas</h4></div><div id="private-requests-received" class="p-4 space-y-3"></div></section><section class="private-section-card overflow-hidden"><div class="px-5 py-4 border-b border-slate-200"><h4 class="text-sm font-black text-navy">Solicitudes enviadas</h4></div><div id="private-requests-sent" class="p-4 space-y-3"></div></section></div></div>

            <!-- OPERACIONES -->
            <div id="private-panel-operations" class="private-dashboard-panel">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div><h3 class="text-xl font-black text-navy">Operaciones</h3><p class="text-xs text-slate-500 mt-1">Expedientes, estados y próximas acciones.</p></div>
                <select id="private-operation-status-filter" onchange="renderPrivateOperations()" class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs bg-white"><option value="">Todos los estados</option><option>Nueva</option><option>Confirmación pendiente</option><option>Acuerdo de Confidencialidad (NDA) pendiente</option><option>Pago pendiente</option><option>Datos desbloqueados</option><option>En negociación</option><option>Reserva realizada</option><option>Documentación pendiente</option><option>Completada</option><option>Cancelada</option></select>
              </div>
              <div class="mb-4 p-3.5 rounded-2xl bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/50 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                  <span class="text-base">⚖️</span>
                  <span class="text-xs text-slate-700 dark:text-slate-300 font-semibold">Trazabilidad de acuerdos marco 50/50, firma de NDA y liquidación de honorarios.</span>
                </div>
                <button type="button" onclick="openVeraWithContext('academy', 'phase-6-collaboration', 6, '¿Cómo protegemos los honorarios 50/50 antes de la visita con el acuerdo NDA?')" class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-xs transition-colors">
                  ✨ Preguntar a Vera sobre acuerdos
                </button>
              </div>
              <div class="private-panel-tabs" role="tablist" aria-label="Tipos de avisos"><button type="button" onclick="switchPrivateDashboardPanel('requests')" class="private-panel-tab" role="tab" aria-selected="false">Solicitudes pendientes</button><button type="button" class="private-panel-tab active" role="tab" aria-selected="true">Operaciones</button><button type="button" onclick="switchPrivateDashboardPanel('notifications')" class="private-panel-tab" role="tab" aria-selected="false">Notificaciones</button></div>
              <div class="private-section-card overflow-hidden"><div class="overflow-x-auto"><table class="private-table w-full"><thead><tr><th class="px-4 py-3">Operación</th><th class="px-4 py-3">Propiedad / demanda</th><th class="px-4 py-3">Colaborador</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Actualización</th><th class="px-4 py-3">Próxima acción</th><th class="px-4 py-3"></th></tr></thead><tbody id="private-operations-table"></tbody></table></div></div>
            </div>

            <!-- FAVORITOS -->
            <div id="private-panel-favorites" class="private-dashboard-panel"><div class="mb-5"><h3 class="text-xl font-black text-navy">Mis favoritos</h3><p class="text-xs text-slate-500 mt-1">Demandas, captaciones y coincidencias guardadas para revisarlas desde un único lugar.</p></div><div id="private-favorites-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5"></div></div>

            <!-- TAREAS -->
            <div id="private-panel-tasks" class="private-dashboard-panel"><div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div><h3 class="text-xl font-black text-navy">Calendario y tareas</h3><p class="text-xs text-slate-500 mt-1">Módulo Premium para agenda, recordatorios y calendarios compatibles.</p></div><div class="flex flex-wrap gap-2"><button onclick="linkExternalCalendar()" class="px-4 py-3 rounded-xl border border-slate-200 bg-white text-blue text-xs font-bold">Vincular calendario</button><button onclick="openNewTaskModal()" class="px-4 py-3 rounded-xl bg-blue text-white text-xs font-bold">Añadir nueva tarea</button></div></div><div id="private-tasks-premium-content" class="grid grid-cols-1 xl:grid-cols-[.95fr_1.05fr] gap-5"><section class="private-section-card overflow-hidden"><div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3"><div><h4 class="text-sm font-black text-navy">Calendario de pendientes</h4><p class="text-[11px] text-slate-500 mt-1">Visualiza tareas y alertas por fecha.</p></div><button onclick="exportPrivateAgendaCalendar()" class="px-3 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-blue">Exportar ICS</button></div><div class="p-4"><div id="private-tasks-calendar"></div><div id="private-tasks-calendar-events" class="mt-4 space-y-3"></div></div></section><div id="private-tasks-list" class="space-y-3"></div></div><div id="private-tasks-premium-lock" class="hidden private-section-card p-8 text-center"><h4 class="text-lg font-black text-navy">Calendario avanzado incluido en Premium</h4><p class="text-sm text-slate-500 mt-2">Activa Premium para crear tareas, preparar notificaciones y vincular calendarios externos.</p><a href="#/planes" class="inline-flex mt-5 px-5 py-3 rounded-xl bg-blue text-white text-xs font-black">Ver plan Premium</a></div></div>

            <!-- NOTIFICACIONES -->
            <div id="private-panel-notifications" class="private-dashboard-panel"><div class="mb-4"><h3 class="text-xl font-black text-navy">Avisos</h3><p class="text-xs text-slate-500 mt-1">Oportunidades, operaciones, avisos administrativos y sistema.</p></div><div class="private-panel-tabs" role="tablist" aria-label="Tipos de avisos"><button type="button" onclick="switchPrivateDashboardPanel('requests')" class="private-panel-tab" role="tab" aria-selected="false">Solicitudes pendientes</button><button type="button" onclick="switchPrivateDashboardPanel('operations')" class="private-panel-tab" role="tab" aria-selected="false">Operaciones</button><button type="button" class="private-panel-tab active" role="tab" aria-selected="true">Notificaciones</button></div><div class="flex flex-wrap gap-2 mb-4"><button onclick="markAllPrivateNotificationsRead()" class="px-4 py-2.5 rounded-xl bg-navy text-white text-xs font-bold">Marcar todas como leídas</button></div><div id="private-notifications-list" class="space-y-3"></div></div>

            <!-- FEEDS XML -->

            <div id="private-panel-subscriptions" class="private-dashboard-panel">
              <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
                <div><h3 class="text-xl font-black text-navy">Suscripciones y alertas multicanal</h3><p class="text-xs text-slate-500 mt-1">Recibe coincidencias por plataforma, email y WhatsApp sin revelar datos de contacto entre profesionales.</p></div>
                <button type="button" onclick="simulateProtectedMatchNotification()" class="px-4 py-3 rounded-xl bg-blue text-white text-xs font-bold shadow-sm">Simular nueva coincidencia</button>
              </div>
              <div id="private-comm-stats" class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5"></div>
              <div class="grid grid-cols-1 xl:grid-cols-[.85fr_1.15fr] gap-5">
                <section class="private-section-card p-5">
                  <h4 class="text-sm font-black text-navy">Preferencias de notificación</h4>
                  <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Los canales externos solo envían avisos. La conversación y los documentos permanecen dentro de Compra Captación.</p>
                  <div class="mt-4 space-y-3 text-xs">
                    <label class="flex items-center justify-between gap-4"><span>Notificaciones dentro de la plataforma</span><input id="comm-pref-inapp" type="checkbox" onchange="saveCommunicationPreferences()" class="w-4 h-4" /></label>
                    <label class="flex items-center justify-between gap-4"><span>Avisos operativos por email</span><input id="comm-pref-email" type="checkbox" onchange="saveCommunicationPreferences()" class="w-4 h-4" /></label>
                    <label class="flex items-center justify-between gap-4"><span>Avisos operativos por WhatsApp</span><input id="comm-pref-whatsapp" type="checkbox" onchange="saveCommunicationPreferences()" class="w-4 h-4" /></label>
                    <label class="block"><span class="block mb-2">Frecuencia predeterminada</span><select id="comm-pref-frequency" onchange="saveCommunicationPreferences()" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-white text-xs"><option value="instant">Inmediata</option><option value="daily">Resumen diario</option><option value="weekly">Resumen semanal</option></select></label>
                  </div>
                  <div class="comm-safe-banner mt-4"><strong class="block text-xs text-green">✓ Comunicación protegida</strong><p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Email y WhatsApp conducen siempre a una pantalla segura. No incluyen nombre, teléfono, email ni dirección exacta de la contraparte.</p></div>
                </section>
                <section class="private-section-card overflow-hidden">
                  <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3"><div><h4 class="text-sm font-black text-navy">Mis demandas suscritas</h4><p class="text-[11px] text-slate-500 mt-1">Alertas configuradas para búsquedas activas.</p></div><div class="flex gap-2"><select id="comm-demand-select" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs max-w-[230px]"></select><button onclick="subscribeSelectedDemand()" class="px-3 py-2 rounded-xl bg-navy text-white text-xs font-bold">Añadir</button></div></div>
                  <div class="overflow-x-auto"><table class="comm-table w-full"><thead><tr><th class="px-4 py-3 text-left">Demanda</th><th class="px-4 py-3 text-left">Coincidencias</th><th class="px-4 py-3 text-left">Canales</th><th class="px-4 py-3 text-left">Frecuencia</th><th class="px-4 py-3 text-left">Estado</th><th class="px-4 py-3"></th></tr></thead><tbody id="comm-subscriptions-table"></tbody></table></div>
                </section>
              </div>
              <section class="private-section-card overflow-hidden mt-5"><div class="px-5 py-4 border-b border-slate-200"><h4 class="text-sm font-black text-navy">Historial de envíos operativos</h4><p class="text-[11px] text-slate-500 mt-1">Trazabilidad de avisos generados por coincidencias, solicitudes y operaciones.</p></div><div id="comm-deliveries-list" class="p-4 space-y-3"></div></section>
            </div>

            <div id="private-panel-communications" class="private-dashboard-panel">
              <div class="mb-5"><h3 class="text-xl font-black text-navy">Mensajes</h3><p class="text-xs text-slate-500 mt-1">Las conversaciones se habilitan cuando una solicitud es aceptada o existe una operación activa.</p></div>
              <div class="comm-safe-banner mb-5"><strong class="block text-sm text-green">La plataforma actúa como canal único de comunicación</strong><p class="text-[11px] text-slate-500 mt-1 leading-relaxed">No compartas teléfonos, emails, enlaces externos ni direcciones exactas antes de completar el flujo protegido. Los intentos quedan registrados para preservar la trazabilidad.</p></div>
              <div id="comm-threads-list" class="grid grid-cols-1 lg:grid-cols-2 gap-4"></div>
            </div>

            <div id="private-panel-traceability" class="private-dashboard-panel">
              <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5"><div><h3 class="text-xl font-black text-navy">Trazabilidad de procesos</h3><p class="text-xs text-slate-500 mt-1">Registro cronológico de coincidencias, alertas, mensajes, Acuerdo de Confidencialidad (NDA), pagos y accesos protegidos.</p></div><button onclick="exportCommunicationTrace()" class="px-4 py-3 rounded-xl bg-navy text-white text-xs font-bold">Exportar trazabilidad JSON</button></div>
              <div class="grid grid-cols-1 xl:grid-cols-[.78fr_1.22fr] gap-5">
                <section class="private-section-card p-5"><h4 class="text-sm font-black text-navy">Principios aplicados</h4><div class="mt-4 space-y-3 text-[11px] text-slate-500 leading-relaxed"><p>✓ Identidad de la contraparte oculta antes de la autorización.</p><p>✓ Email y WhatsApp funcionan como avisos, no como canal de contacto directo.</p><p>✓ Los mensajes quedan asociados a una referencia interna.</p><p>✓ El desbloqueo requiere el Acuerdo de Confidencialidad (NDA) y el pago configurado.</p><p>✓ Cada cambio relevante genera un evento de auditoría.</p></div></section>
                <section class="private-section-card overflow-hidden"><div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between"><h4 class="text-sm font-black text-navy">Registro de actividad protegido</h4><select id="comm-trace-filter" onchange="renderCommunicationTrace()" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs"><option value="">Todos</option><option value="MATCH">Coincidencias</option><option value="MESSAGE">Mensajes</option><option value="FLOW">Flujo protegido</option><option value="NOTIFICATION">Notificaciones</option><option value="SECURITY">Seguridad</option></select></div><div id="comm-trace-list" class="p-5 space-y-4 max-h-[640px] overflow-y-auto"></div></section>
              </div>
            </div>

            <div id="private-panel-feeds" class="private-dashboard-panel">
              <div class="mb-4"><h3 class="text-xl font-black text-navy">Importaciones y datos</h3><p class="text-xs text-slate-500 mt-1">Importa propiedades y gestiona tus datos desde un único apartado.</p></div>
              <div class="private-panel-tabs" role="tablist" aria-label="Importaciones y datos"><button type="button" class="private-panel-tab active" role="tab" aria-selected="true">Importar propiedades</button><button type="button" onclick="switchPrivateDashboardPanel('data')" class="private-panel-tab" role="tab" aria-selected="false">Privacidad y exportación</button></div>
              <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 mb-6">
                <div class="mb-5 p-4 rounded-2xl border border-blue/20 bg-blue-light/30 text-xs text-slate-600 leading-relaxed">
                  <strong class="block text-navy mb-1">Antes de importar</strong>
                  Puedes subir XML, CSV o JSON hasta 10 MB y 1.000 propiedades. Los XML no pueden contener DOCTYPE ni ENTITY. Si el sistema detecta datos incompletos, las propiedades quedarán en revisión antes de publicarse.
                </div>
                <div class="mb-5 flex flex-wrap gap-2">
                  <a href="<?php echo esc_url(rest_url('captacion/v1/import/template')); ?>" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-blue text-xs font-black">Descargar plantilla CSV</a>
                  <span class="px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500">Formatos: XML, CSV, JSON</span>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-[1fr_auto] gap-4 items-end">
                  <div>
                    <label for="private-xml-url" class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-2">URL del fichero XML</label>
                    <input id="private-xml-url" type="url" placeholder="https://dominio.es/feed-inmuebles.xml" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue/20" />
                  </div>
                  <button id="private-xml-save-btn" type="button" onclick="savePrivateXmlUrl()" class="px-5 py-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md transition-all">Guardar e importar URL</button>
                </div>
                <div id="private-feed-xml-url-result" class="mt-3 text-xs hidden"></div>
                <div class="mt-5 grid grid-cols-1 xl:grid-cols-[1fr_auto_auto] gap-4 items-end">
                  <div>
                    <label for="private-feed-xml-file-name" class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-2">Archivo local</label>
                    <input id="private-feed-xml-file-name" type="text" readonly placeholder="Selecciona un archivo XML, CSV o JSON desde tu equipo" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm" />
                    <input id="private-feed-xml-file" type="file" accept=".xml,.csv,.json,application/xml,text/xml,text/csv,application/json" class="hidden" onchange="handleFeedXmlFileSelected()" />
                  </div>
                  <button type="button" onclick="chooseFeedXmlFile()" class="px-5 py-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-navy text-xs font-black shadow-sm">Explorar</button>
                  <button id="private-feed-xml-import-btn" type="button" onclick="importFeedXmlFile()" class="px-5 py-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md">Importar archivo</button>
                </div>
                <div id="private-feed-xml-import-result" class="mt-3 text-xs hidden"></div>
                <div class="mt-6 border-t border-slate-200 pt-5">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                    <h4 class="text-sm font-black text-navy">Importaciones realizadas</h4>
                    <div class="flex flex-wrap gap-2"><button type="button" onclick="loadXmlFeeds()" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-blue text-[10px] font-black">Actualizar lista</button><button type="button" onclick="deleteAllMyXmlFeeds()" class="px-3 py-2 rounded-xl bg-red-600 text-white text-[10px] font-black">Eliminar todos mis XML</button></div>
                  </div>
                  <div id="private-feed-import-batches-list" class="space-y-2"><p class="text-xs text-slate-400">Cargando importaciones...</p></div>
                  <?php if (current_user_can('manage_options')) : ?>
                    <div class="mt-6 rounded-2xl border border-red-300 bg-red-50 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div><strong class="block text-xs font-black text-red-700">Reiniciar base del Marketplace</strong><p class="mt-1 text-[11px] text-red-700">Elimina todos los anuncios, demandas, XML y flujos de colaboración de todos los usuarios. Conserva cuentas, perfiles y configuración.</p></div><button type="button" onclick="resetMarketplaceDatabase()" class="shrink-0 px-4 py-3 rounded-xl bg-red-700 text-white text-[10px] font-black">Vaciar base de anuncios</button></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <!-- DATOS Y PRIVACIDAD -->
            <!-- DATOS Y PRIVACIDAD RGPD -->
            <div id="private-panel-data" class="private-dashboard-panel">
              <div class="mb-4">
                <h3 class="text-xl font-black text-navy dark:text-white">Privacidad y Gestión de Datos (RGPD)</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Ejerce tus derechos conforme al Reglamento General de Protección de Datos (UE 2016/679) y LOPDGDD 3/2018.</p>
              </div>
              <div class="private-panel-tabs mb-6" role="tablist" aria-label="Importaciones y datos">
                <button type="button" onclick="switchPrivateDashboardPanel('feeds')" class="private-panel-tab" role="tab" aria-selected="false">Importar propiedades</button>
                <button type="button" class="private-panel-tab active" role="tab" aria-selected="true">Privacidad y derechos RGPD</button>
              </div>

              <!-- 1. Portabilidad de Datos (Art. 20 RGPD) -->
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5 sm:p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-[10px] font-black uppercase tracking-wider">Art. 20 RGPD</span>
                    <h4 class="text-base font-black text-navy dark:text-white mt-1">Portabilidad de Datos Personales</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Descarga una copia completa y estructurada de tu perfil profesional, propiedades, demandas y bitácora.</p>
                  </div>
                  <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button type="button" onclick="exportMyPrivateData()" class="px-4 py-2.5 rounded-xl bg-navy hover:bg-navy-light text-white text-xs font-black shadow-sm transition-all">Exportar XML</button>
                    <button type="button" onclick="exportMyPrivateDataJSON()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-sm transition-all">Exportar JSON</button>
                  </div>
                </div>
              </div>

              <!-- 2. Importación y Normalización -->
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5 sm:p-6 mb-6">
                <h4 class="text-sm font-black text-navy dark:text-white mb-2">Importar archivo privado de cartera</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Sube un archivo XML, CSV o JSON. La plataforma normaliza y protege automáticamente los datos sensibles aplicando el protocolo ciego.</p>
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
                  <div class="flex-grow w-full">
                    <input id="private-data-xml-file" type="file" accept=".xml,.csv,.json,application/xml,text/xml,text/csv,application/json" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm" />
                  </div>
                  <button type="button" onclick="importPrivateUserXml()" class="px-5 py-2.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md shrink-0">Importar archivo</button>
                </div>
                <div id="private-xml-import-result" class="mt-3 text-xs hidden"></div>
              </div>

              <!-- 3. Lotes y Trazabilidad -->
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5 sm:p-6 mb-6">
                <h4 class="text-sm font-black text-navy dark:text-white mb-3">Historial de lotes importados y trazabilidad</h4>
                <div id="private-import-batches-list" class="space-y-2">
                  <p class="text-xs text-slate-400">Cargando...</p>
                </div>
              </div>

              <!-- 4. Derecho de Supresión y Anonimización (Art. 17 RGPD) -->
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-red-200 dark:border-red-900/50 shadow-sm p-5 sm:p-6">
                <div class="flex items-center gap-2 mb-2">
                  <span class="px-2.5 py-0.5 rounded-full bg-red-100 dark:bg-red-950/60 text-red-700 dark:text-red-300 text-[10px] font-black uppercase">Art. 17 RGPD</span>
                  <h4 class="text-sm font-black text-red-600 dark:text-red-400">Derecho de Supresión ("Derecho al Olvido")</h4>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">Elimina de forma irreversible todos tus registros privados, inmuebles compartidos y demandas activas de la base de datos de Compra Captación.</p>
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
                  <div class="flex-grow w-full">
                    <input id="private-delete-confirm-input" type="text" placeholder="Escribe ELIMINAR para confirmar" class="w-full px-4 py-2.5 rounded-xl border border-red-300 dark:border-red-800 dark:bg-slate-800 text-sm" />
                  </div>
                  <button type="button" onclick="deleteAllMyPrivateData()" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-black shadow-md shrink-0">Eliminar mis registros</button>
                </div>
                <div id="private-delete-result" class="mt-3 text-xs hidden"></div>
              </div>
            </div>

            <!-- IA -->
            <div id="private-panel-ai" class="private-dashboard-panel">
              <div class="mb-5"><h3 class="text-xl font-black text-navy">Configuración IA</h3><p class="text-xs text-slate-500 mt-1">Conecta tu propio proveedor para activar funciones asistidas sin que la plataforma asuma el coste variable de tus consultas.</p></div>
              <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6"><div class="flex flex-col xl:flex-row xl:items-start justify-between gap-5"><div class="max-w-4xl"><span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase tracking-wider">Bring your own AI</span><h3 class="text-lg font-black text-navy mt-3">Conecta tu proveedor de inteligencia artificial</h3><p class="text-xs text-slate-500 mt-2 leading-relaxed">Tus credenciales se usan solo para tus solicitudes. La API key se envía al backend y se guarda cifrada para tu usuario de WordPress.</p><p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Puedes usar esta conexión para redactar captaciones, resumir demandas, analizar encajes y lanzar asistentes especializados.</p></div><button type="button" onclick="openAIConnectionModal()" class="shrink-0 px-5 py-3 rounded-xl bg-gradient-to-r from-blue to-purple-600 hover:opacity-90 text-white text-xs font-bold shadow-md">Conectar IA</button></div><div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-3 mt-5"><div class="ai-provider-chip"><strong class="block text-sm text-navy">OpenAI</strong><span class="text-[10px] text-slate-500">GPT y tareas generales</span></div><div class="ai-provider-chip"><strong class="block text-sm text-navy">Anthropic</strong><span class="text-[10px] text-slate-500">Lectura y síntesis</span></div><div class="ai-provider-chip"><strong class="block text-sm text-navy">Google</strong><span class="text-[10px] text-slate-500">Gemini</span></div><div class="ai-provider-chip"><strong class="block text-sm text-navy">Groq</strong><span class="text-[10px] text-slate-500">Alta velocidad</span></div><div class="ai-provider-chip"><strong class="block text-sm text-navy">OpenRouter</strong><span class="text-[10px] text-slate-500">Catálogo amplio</span></div><div class="ai-provider-chip"><strong class="block text-sm text-navy">Compatible</strong><span class="text-[10px] text-slate-500">Endpoint propio</span></div></div><div id="ai-connections-list" class="mt-5 space-y-3"></div></div>
              <section class="ai-manual-section mt-5 rounded-3xl p-5 sm:p-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                  <div class="max-w-3xl">
                    <span class="ai-manual-kicker inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">Manual de conexión IA</span>
                    <h3 class="mt-3 text-xl font-black text-navy">Conecta una IA en 5 pasos sencillos</h3>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">Una API key es como una llave privada: permite que Compra Captación pida ayuda a tu IA para redactar, resumir, analizar encajes o generar puntos de interés. No la compartas y guárdala solo desde esta pantalla.</p>
                  </div>
                  <button type="button" onclick="openAIConnectionModal()" class="shrink-0 px-5 py-3 rounded-2xl bg-blue text-white text-xs font-bold shadow-sm hover:bg-blue-dark">Abrir conexión IA</button>
                </div>
                <div class="mt-5 grid grid-cols-1 md:grid-cols-5 gap-3 text-xs">
                  <div class="ai-manual-step rounded-2xl p-4"><strong class="block">1. Elige</strong><span>Escoge proveedor: fácil, barato, local o potente.</span></div>
                  <div class="ai-manual-step rounded-2xl p-4"><strong class="block">2. Crea API key</strong><span>Copia la clave desde la web del proveedor.</span></div>
                  <div class="ai-manual-step rounded-2xl p-4"><strong class="block">3. Pega la clave</strong><span>Compra Captación la envía al backend y la guarda cifrada.</span></div>
                  <div class="ai-manual-step rounded-2xl p-4"><strong class="block">4. Prueba</strong><span>Usa “Probar conexión” para confirmar que funciona.</span></div>
                  <div class="ai-manual-step rounded-2xl p-4"><strong class="block">5. Activa</strong><span>Ya puedes usar IA dentro de tus flujos profesionales.</span></div>
                </div>
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                  <article class="ai-manual-card is-highlight rounded-3xl p-5"><div class="flex flex-wrap gap-2"><span class="ai-manual-badge green px-2 py-1 rounded-full text-[9px] font-black uppercase">Gratis/local</span><span class="ai-manual-badge green px-2 py-1 rounded-full text-[9px] font-black uppercase">Open-source</span></div><h4 class="mt-3 text-base font-black">Ollama</h4><p class="mt-2 text-xs leading-relaxed">Instalas modelos como Llama, Mistral o Qwen en tu ordenador o servidor. No pagas por token, pero necesitas configurarlo y que el backend pueda acceder al endpoint.</p><a href="https://ollama.com" target="_blank" rel="noopener noreferrer" class="ai-manual-link inline-flex mt-4 text-xs">Ver Ollama</a></article>
                  <article class="ai-manual-card is-highlight rounded-3xl p-5"><div class="flex flex-wrap gap-2"><span class="ai-manual-badge green px-2 py-1 rounded-full text-[9px] font-black uppercase">Gratis/local</span><span class="ai-manual-badge green px-2 py-1 rounded-full text-[9px] font-black uppercase">Visual</span></div><h4 class="mt-3 text-base font-black">LM Studio</h4><p class="mt-2 text-xs leading-relaxed">Interfaz sencilla para descargar modelos open-source y levantarlos como servidor compatible. Ideal si quieres probar IA local sin complicarte tanto.</p><a href="https://lmstudio.ai" target="_blank" rel="noopener noreferrer" class="ai-manual-link inline-flex mt-4 text-xs">Ver LM Studio</a></article>
                  <article class="ai-manual-card rounded-3xl p-5"><div class="flex flex-wrap gap-2"><span class="ai-manual-badge px-2 py-1 rounded-full text-[9px] font-black uppercase">Recomendado</span><span class="ai-manual-badge px-2 py-1 rounded-full text-[9px] font-black uppercase">Fácil</span></div><h4 class="mt-3 text-base font-black">Groq</h4><p class="mt-2 text-xs leading-relaxed">Muy rápido y con modelos abiertos como Llama o Mixtral. Suele ser una buena primera opción para probar con una API sencilla y planes de entrada.</p><a href="https://console.groq.com/keys" target="_blank" rel="noopener noreferrer" class="ai-manual-link inline-flex mt-4 text-xs">Crear API key</a></article>
                  <article class="ai-manual-card rounded-3xl p-5"><span class="ai-manual-badge px-2 py-1 rounded-full text-[9px] font-black uppercase">Catálogo amplio</span><h4 class="mt-3 text-base font-black">OpenRouter</h4><p class="mt-2 text-xs leading-relaxed">Reúne muchos modelos en una sola API. Algunos modelos tienen modalidad gratuita o bajo coste. Útil si quieres comparar sin cambiar la integración.</p><a href="https://openrouter.ai/keys" target="_blank" rel="noopener noreferrer" class="ai-manual-link inline-flex mt-4 text-xs">Crear API key</a></article>
                  <article class="ai-manual-card rounded-3xl p-5"><span class="ai-manual-badge px-2 py-1 rounded-full text-[9px] font-black uppercase">Free tier</span><h4 class="mt-3 text-base font-black">Google AI Studio</h4><p class="mt-2 text-xs leading-relaxed">Gemini es fácil para empezar y suele ofrecer cuotas gratuitas o créditos. Buena opción para textos, resúmenes y tareas generales.</p><a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="ai-manual-link inline-flex mt-4 text-xs">Crear API key</a></article>
                  <article class="ai-manual-card rounded-3xl p-5"><span class="ai-manual-badge px-2 py-1 rounded-full text-[9px] font-black uppercase">Potente</span><h4 class="mt-3 text-base font-black">OpenAI</h4><p class="mt-2 text-xs leading-relaxed">Modelos GPT para redacción, clasificación, razonamiento y tareas generales. Normalmente requiere método de pago y control de consumo.</p><a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer" class="ai-manual-link inline-flex mt-4 text-xs">Crear API key</a></article>
                  <article class="ai-manual-card rounded-3xl p-5"><span class="ai-manual-badge px-2 py-1 rounded-full text-[9px] font-black uppercase">Lectura larga</span><h4 class="mt-3 text-base font-black">Anthropic Claude</h4><p class="mt-2 text-xs leading-relaxed">Muy útil para analizar documentos largos, resumir textos y revisar contexto. Normalmente es de pago por uso.</p><a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener noreferrer" class="ai-manual-link inline-flex mt-4 text-xs">Crear API key</a></article>
                  <article class="ai-manual-card rounded-3xl p-5"><span class="ai-manual-badge px-2 py-1 rounded-full text-[9px] font-black uppercase">Open-source</span><h4 class="mt-3 text-base font-black">Mistral AI</h4><p class="mt-2 text-xs leading-relaxed">Proveedor europeo con modelos abiertos y API propia. Buena alternativa para textos, clasificación y asistentes inmobiliarios.</p><a href="https://console.mistral.ai/api-keys" target="_blank" rel="noopener noreferrer" class="ai-manual-link inline-flex mt-4 text-xs">Crear API key</a></article>
                  <article class="ai-manual-card rounded-3xl p-5"><span class="ai-manual-badge px-2 py-1 rounded-full text-[9px] font-black uppercase">Avanzado</span><h4 class="mt-3 text-base font-black">Hugging Face / Together</h4><p class="mt-2 text-xs leading-relaxed">Permiten usar muchos modelos open-source alojados. Son ideales si quieres más control, aunque requieren entender modelos y endpoints.</p><a href="https://huggingface.co/settings/tokens" target="_blank" rel="noopener noreferrer" class="ai-manual-link inline-flex mt-4 text-xs">Hugging Face</a></article>
                </div>
                <div class="ai-manual-note mt-6 rounded-3xl p-5 text-xs leading-relaxed"><strong class="block mb-2">Explicado fácil:</strong> una API key es como una llave de casa. Si la pegas aquí, Compra Captación puede llamar a tu IA por ti. Si usas OpenAI, Gemini, Groq u OpenRouter, la IA está en internet. Si usas Ollama o LM Studio, la IA vive en tu ordenador o servidor. No todas son gratis: revisa siempre límites, créditos y precios antes de activar.</div>
              </section>
            </div>

            <!-- PERFIL -->
            <div id="private-panel-profile" class="private-dashboard-panel">
              <div class="mb-5"><h3 class="text-xl font-black text-navy">Perfil profesional</h3><p class="text-xs text-slate-500 mt-1">La información pública genera confianza; los datos fiscales permanecen protegidos.</p></div>
              <div id="professional-profile-progress-notice" class="profile-progress-notice mb-5">
                <div class="flex flex-wrap items-center justify-between gap-2"><strong id="professional-profile-progress-title">Tu perfil profesional está pendiente</strong><span id="professional-profile-progress-value" class="font-black">0%</span></div>
                <div class="mt-3 h-2.5 rounded-full bg-slate-200 overflow-hidden" role="progressbar" aria-label="Progreso del perfil profesional" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div id="professional-profile-progress-bar" class="h-full rounded-full transition-all duration-300" style="width:0%"></div></div>
                <p id="professional-profile-progress-help" class="mt-2 text-xs">Los campos obligatorios permiten empezar; completar todo tu perfil genera más confianza y mejores oportunidades.</p>
              </div>
              <div class="p-4 mb-5 rounded-2xl border border-blue/20 bg-blue-light/40 text-xs text-slate-600 leading-relaxed"><strong class="text-navy">Información privada:</strong> Compra Captación no emite facturas entre profesionales. Estos datos sirven para que las partes implicadas en una operación puedan gestionar su facturación directamente.</div>
              <form id="private-fiscal-profile-form" onsubmit="savePrivateFiscalProfile(event)" class="private-profile-form space-y-4">
                <section class="private-profile-section"><div class="private-profile-section-heading"><h4>Datos públicos de contacto</h4><p>Son los datos que podrán aparecer en tus anuncios y colaboraciones.</p></div><div class="private-profile-fields">
                  <label><span class="private-field-label">Nombre *</span><input id="profile-first-name" required class="private-field-input" autocomplete="given-name" placeholder="Tu nombre" /></label>
                  <label><span class="private-field-label">Apellidos</span><input id="profile-last-name" class="private-field-input" autocomplete="family-name" placeholder="Tus apellidos" /></label>
                  <label><span class="private-field-label">Teléfono profesional *</span><input id="fiscal-phone" type="tel" required pattern="[0-9+() .-]{7,20}" class="private-field-input" autocomplete="tel" placeholder="Ej.: 612 345 678" /></label>
                  <label class="md:col-span-2"><span class="private-field-label">Email público de contacto *</span><input id="profile-contact-email" type="email" required class="private-field-input" autocomplete="email" placeholder="Puede ser distinto del email de acceso" /></label>
                </div></section>
                <section class="private-profile-section"><div class="private-profile-section-heading"><h4>Empresa y actividad</h4><p>Ayuda a otros agentes a saber con quién colaboran.</p></div><div class="private-profile-fields">
                  <label><span class="private-field-label">Tipo de profesional</span><select id="fiscal-profile-type" class="private-field-input"><option value="">Selecciona una opción</option><option value="autonomo">Agente autónomo</option><option value="empresa">Empresa</option><option value="agencia">Agencia inmobiliaria</option><option value="colaborador">Colaborador</option></select></label>
                  <label><span class="private-field-label">Nombre comercial</span><input id="fiscal-trade-name" class="private-field-input" placeholder="Nombre de la agencia" /></label>
                  <label><span class="private-field-label">Actividad profesional</span><input id="fiscal-activity" class="private-field-input" placeholder="Ej.: Agencia inmobiliaria" /></label>
                  <label class="md:col-span-2"><span class="private-field-label">Descripción profesional</span><textarea id="profile-description" rows="3" class="private-field-input resize-y" placeholder="Explica de forma breve tu experiencia y cómo trabajas"></textarea></label>
                  <div class="profile-logo-upload"><span class="private-field-label">Logotipo profesional</span><input id="profile-logo-url" type="hidden" /><div class="flex flex-wrap items-center gap-3"><img id="profile-logo-preview" src="" alt="Vista previa del logotipo" class="hidden h-16 w-24 rounded-xl border border-slate-200 bg-white object-contain p-2" /><label for="profile-logo-file" class="inline-flex min-h-[44px] cursor-pointer items-center justify-center rounded-xl border border-blue/30 bg-white px-4 py-2.5 text-sm font-semibold text-blue">Cargar imagen</label><input id="profile-logo-file" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="uploadProfessionalProfileLogo(this.files?.[0])" /><button id="profile-logo-remove" type="button" onclick="removeProfessionalProfileLogo()" class="hidden min-h-[44px] rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600">Quitar</button></div><p id="profile-logo-upload-status" class="mt-2 text-xs text-slate-500">JPG, PNG o WEBP. Máximo 2 MB.</p></div>
                </div></section>
                <section class="private-profile-section"><div class="private-profile-section-heading"><h4>Ubicación y cobertura</h4><p>Indica dónde trabajas para obtener coincidencias más relevantes.</p></div><div class="private-profile-fields">
                  <label><span class="private-field-label">Comunidad autónoma *</span><select id="fiscal-ccaa" required class="private-field-input"></select></label>
                  <label><span class="private-field-label">Provincia *</span><select id="fiscal-province" required class="private-field-input" disabled></select></label>
                  <label><span class="private-field-label">Municipio</span><select id="fiscal-municipality" class="private-field-input" disabled></select></label>
                  <label><span class="private-field-label">Código postal</span><input id="fiscal-postal-code" inputmode="numeric" maxlength="5" class="private-field-input" placeholder="Ej.: 28001" /></label>
                  <label class="md:col-span-2"><span class="private-field-label">Zona profesional</span><input id="profile-coverage" class="private-field-input" placeholder="Ej.: Madrid capital y zona noroeste" /></label>
                </div></section>
                <section class="private-profile-section"><div class="private-profile-section-heading"><h4>Web, redes y especialidades</h4><p>Opcional: añade información que refuerce tu presentación profesional.</p></div><div class="private-profile-fields">
                  <label><span class="private-field-label">Web</span><input id="fiscal-website" type="url" class="private-field-input" placeholder="https://..." /></label>
                  <label><span class="private-field-label">LinkedIn</span><input id="profile-linkedin" type="url" class="private-field-input" placeholder="URL de tu perfil" /></label>
                  <label><span class="private-field-label">Instagram</span><input id="profile-instagram" type="url" class="private-field-input" placeholder="URL de tu perfil" /></label>
                  <label><span class="private-field-label">Facebook</span><input id="profile-facebook" type="url" class="private-field-input" placeholder="URL de tu página" /></label>
                  <label class="md:col-span-2"><span class="private-field-label">Especialidades</span><input id="profile-specialties" class="private-field-input" placeholder="Ej.: residencial, lujo, inversión, obra nueva" /></label>
                </div></section>
                <section class="private-profile-section private-profile-section-private"><div class="private-profile-section-heading"><h4>Datos fiscales privados</h4><p>Solo se usan en flujos autorizados y no se muestran públicamente.</p></div><div class="private-profile-fields">
                  <label class="md:col-span-2"><span class="private-field-label">Nombre y apellidos / Razón social</span><input id="fiscal-legal-name" class="private-field-input" placeholder="Razón social o nombre fiscal" /></label>
                  <label><span class="private-field-label">DNI / NIE / NIF / CIF</span><input id="fiscal-tax-id" class="private-field-input" placeholder="Identificación fiscal" /></label>
                  <label><span class="private-field-label">Email de facturación</span><input id="fiscal-billing-email" type="email" class="private-field-input" placeholder="Email de facturación" /></label>
                  <label class="md:col-span-2"><span class="private-field-label">Dirección fiscal completa</span><input id="fiscal-address" class="private-field-input" placeholder="Dirección fiscal" /></label>
                  <label><span class="private-field-label">País</span><input id="fiscal-country" class="private-field-input" placeholder="España" /></label>
                  <label class="md:col-span-2"><span class="private-field-label">Observaciones fiscales o comerciales</span><textarea id="fiscal-notes" rows="3" class="private-field-input resize-y" placeholder="Información privada adicional"></textarea></label>
                </div></section>
                <div class="mt-5 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between"><div><p id="fiscal-profile-status" class="text-[11px] text-slate-500">Los campos vacíos se mostrarán como “Pendiente de completar”.</p><p id="fiscal-address-validation-status" class="text-[10px] text-slate-500 mt-1"></p></div><div class="flex flex-wrap gap-2"><button type="button" onclick="validateFiscalAddress()" class="px-4 py-3 rounded-xl border border-blue/30 text-blue text-xs font-bold">Validar dirección</button><button type="submit" class="px-5 py-3 rounded-xl bg-blue text-white text-xs font-black shadow-sm">Guardar perfil profesional</button></div></div>
              </form>
            </div>

            <!-- REFERIDOS & PRODUCT-LED GROWTH (PLG) 50/50 -->
            <div id="private-panel-referrals" class="private-dashboard-panel">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-[11px] font-black uppercase tracking-wider">
                      <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Programa de Crecimiento B2B
                    </span>
                    <span id="plg-badge-pill" class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[11px] font-bold">
                      ⭐ Agente Conector
                    </span>
                  </div>
                  <h3 class="text-2xl font-black text-navy dark:text-white mt-2">Red de Referidos y Crecimiento 50/50</h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Gana 10% de descuento recurrente en tu cuota mensual por cada colega activo (hasta 100% gratis) e invita con operaciones reales.</p>
                </div>
                <div class="flex items-center gap-2">
                  <button type="button" onclick="openSendTransactionalInviteModal()" class="px-4 py-2.5 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md flex items-center gap-2">
                    <span>⚡ Invitar con Comprador (50/50)</span>
                  </button>
                </div>
              </div>

              <!-- 4 TARJETAS DE MÉTRICAS CLAVE PLG -->
              <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <!-- 1. Descuento Recurrente -->
                <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-500/10 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-850 border border-emerald-500/20 dark:border-slate-800 shadow-sm space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Descuento Cuota</span>
                    <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 text-sm font-bold">🏷️</span>
                  </div>
                  <strong id="plg-metric-discount" class="block text-3xl font-black text-navy dark:text-white">0% DTO</strong>
                  <span id="plg-metric-discount-sub" class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400">10% de ahorro por cada colega activo</span>
                </div>

                <!-- 2. Créditos Ganados por Hitos -->
                <div class="p-5 rounded-2xl bg-gradient-to-br from-blue/10 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-850 border border-blue/20 dark:border-slate-800 shadow-sm space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-blue">Créditos de Hitos</span>
                    <span class="p-2 rounded-xl bg-blue/10 text-blue text-sm font-bold">🎁</span>
                  </div>
                  <strong id="plg-metric-credits" class="block text-3xl font-black text-navy dark:text-white">+0,00 cr</strong>
                  <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400">+3 cr tras subida de cartera XML</span>
                </div>

                <!-- 3. Colegas con Cartera XML -->
                <div class="p-5 rounded-2xl bg-gradient-to-br from-purple-500/10 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-850 border border-purple-500/20 dark:border-slate-800 shadow-sm space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-purple-600 dark:text-purple-400">Carteras XML</span>
                    <span class="p-2 rounded-xl bg-purple-500/10 text-purple-600 text-sm font-bold">🏢</span>
                  </div>
                  <strong id="plg-metric-xml" class="block text-3xl font-black text-navy dark:text-white">0 Activas</strong>
                  <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400">Mínimo 3 exclusivas verificadas</span>
                </div>

                <!-- 4. Invitaciones Transaccionales -->
                <div class="p-5 rounded-2xl bg-gradient-to-br from-amber/10 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-850 border border-amber/20 dark:border-slate-800 shadow-sm space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Operaciones 50/50</span>
                    <span class="p-2 rounded-xl bg-amber/10 text-amber-600 text-sm font-bold">🤝</span>
                  </div>
                  <strong id="plg-metric-invites" class="block text-3xl font-black text-navy dark:text-white">0 Enviadas</strong>
                  <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400">Invitaciones con comprador real</span>
                </div>
              </div>

              <!-- BLOQUE 1: INVITACIÓN TRANSACCIONAL "CABALLO DE TROYA" -->
              <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm mb-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                  <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-blue">Product-Led Growth</span>
                    <h4 class="text-lg font-black text-navy dark:text-white">El Efecto Caballo de Troya (Invitación con Comprador Real)</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">¿Has visto un inmueble perfecto en un portal externo y tienes al comprador? Invita a ese agente para firmar el acuerdo 50/50 sin riesgo de puenteo.</p>
                  </div>
                  <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs font-black">Alta Conversión</span>
                </div>

                <form id="plg-transactional-invite-form" onsubmit="handleSendTransactionalInvite(event)" class="space-y-4">
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                      <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Email del agente o agencia externa *</label>
                      <input type="email" id="trojan-target-email" required placeholder="agente@otra-agencia.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-xs text-navy dark:text-white outline-none focus:border-blue" />
                    </div>
                    <div>
                      <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Nombre del agente o agencia <em class="font-normal">(opcional)</em></label>
                      <input type="text" id="trojan-target-name" placeholder="Ej: Inmobiliaria Mediterráneo" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-xs text-navy dark:text-white outline-none focus:border-blue" />
                    </div>
                    <div>
                      <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Título o referencia de su inmueble *</label>
                      <input type="text" id="trojan-property-title" required placeholder="Ej: Ático dúplex en Valencia centro" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-xs text-navy dark:text-white outline-none focus:border-blue" />
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                      <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Provincia del inmueble *</label>
                      <input type="text" id="trojan-province" required placeholder="Ej: Valencia / Málaga / Alicante" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-xs text-navy dark:text-white outline-none focus:border-blue" />
                    </div>
                    <div>
                      <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Presupuesto aproximado de tu comprador (€)</label>
                      <input type="number" id="trojan-buyer-budget" placeholder="Ej: 320000" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-xs text-navy dark:text-white outline-none focus:border-blue" />
                    </div>
                    <div>
                      <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Reparto de honorarios pactado</label>
                      <select id="trojan-commission-split" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-xs text-navy dark:text-white outline-none focus:border-blue">
                        <option value="50/50">Colaboración 50/50 (Estándar)</option>
                        <option value="40/60">40% Comprador / 60% Captador</option>
                        <option value="100_traspaso">Traspaso 100% de la operación</option>
                      </select>
                    </div>
                  </div>

                  <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">El destinatario recibirá un email formal y podrás copiar el texto listo para enviar por WhatsApp.</p>
                    <button type="submit" id="btn-submit-trojan-invite" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black uppercase tracking-wider transition-all shadow-md flex items-center justify-center gap-2">
                      <span>Enviar Invitación de Colaboración Blindada →</span>
                    </button>
                  </div>
                </form>

                <div id="trojan-invite-success-box" class="hidden p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 space-y-3">
                  <div class="flex items-center gap-2">
                    <span class="text-base">✅</span>
                    <strong id="trojan-success-title">Invitación enviada por email.</strong>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <button type="button" id="btn-open-whatsapp-invite" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                      <span>📲 Abrir y enviar por WhatsApp</span>
                    </button>
                    <button type="button" id="btn-copy-direct-invite-link" class="px-4 py-2 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 text-emerald-800 dark:text-emerald-200 font-bold text-xs">
                      📋 Copiar enlace directo
                    </button>
                  </div>
                </div>
              </div>

              <!-- BLOQUE 2: GENERADOR DE ENLACES Y PLANTILLAS DE 1-CLIC (3 ENFOQUES ESTRATÉGICOS) -->
              <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm mb-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                  <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-purple-600 dark:text-purple-400">Herramientas de Captación B2B</span>
                    <h4 class="text-lg font-black text-navy dark:text-white">Plantillas de Difusión según tu Objetivo</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Elige el enfoque estratégico para conectar con agencias colaboradoras:</p>
                  </div>
                </div>

                <!-- Selector de Pestañas de Enfoque -->
                <div class="flex flex-wrap gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                  <button type="button" onclick="selectPLGTemplate('interprovincial')" id="plg-tab-interprovincial" class="px-4 py-2 rounded-xl bg-blue text-white text-xs font-bold transition-all">
                    🗺️ Derivación Interprovincial
                  </button>
                  <button type="button" onclick="selectPLGTemplate('trojan_deal')" id="plg-tab-trojan_deal" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                    🤝 Operación Real 50/50
                  </button>
                  <button type="button" onclick="selectPLGTemplate('network_trust')" id="plg-tab-network_trust" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                    🔒 Red de Confianza Blindada
                  </button>
                </div>

                <!-- Caja de Vista Previa de la Plantilla -->
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 space-y-3">
                  <div class="flex items-center justify-between">
                    <strong id="plg-template-title" class="text-xs font-black uppercase tracking-wider text-navy dark:text-white">Derivación Interprovincial</strong>
                    <span id="plg-template-desc" class="text-[11px] text-slate-400">Para referir agencias en la costa</span>
                  </div>
                  <p id="plg-template-text" class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed font-mono p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800"></p>
                  <div class="flex flex-wrap items-center gap-2 pt-1">
                    <button type="button" onclick="shareCurrentPLGTemplate('whatsapp')" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm">
                      <span>📲 Compartir por WhatsApp</span>
                    </button>
                    <button type="button" onclick="shareCurrentPLGTemplate('copy')" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-bold hover:bg-slate-50 transition-colors">
                      📋 Copiar Texto Completo
                    </button>
                    <button type="button" onclick="copyPersonalReferralLink()" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-blue text-xs font-bold hover:bg-blue/10 transition-colors ml-auto">
                      🔗 Copiar solo mi enlace único
                    </button>
                  </div>
                </div>
              </div>

              <!-- BLOQUE 3: FILTRO ANTI-FRAUDE Y VERIFICACIÓN PROFESIONAL (CIF/NIF & REGISTRO) -->
              <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm mb-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                  <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Seguridad & Calidad de Red</span>
                    <h4 class="text-lg font-black text-navy dark:text-white">Verificación de Identidad Profesional (Anti-Fraude)</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Para garantizar que solo profesionales colegiados operan en la plataforma y desbloquear bonos de referidos:</p>
                  </div>
                  <span id="plg-verification-status-pill" class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-black">Pendiente</span>
                </div>

                <form id="plg-license-verification-form" onsubmit="handleVerifyProfessionalLicense(event)" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">CIF de Agencia o NIF de Autónomo *</label>
                    <input type="text" id="plg-tax-id" required placeholder="B12345678 / 12345678Z" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-xs text-navy dark:text-white outline-none focus:border-blue uppercase" />
                  </div>
                  <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Tipo de Registro Profesional *</label>
                    <select id="plg-registry-type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-xs text-navy dark:text-white outline-none focus:border-blue">
                      <option value="AICAT">AICAT (Cataluña - Registro Agentes)</option>
                      <option value="RAIN">RAIN (Comunidad de Madrid)</option>
                      <option value="COAPI">Colegio Oficial de API (Nacional)</option>
                      <option value="AUTONOMO_INMO">Alta IAE Inmobiliario (Epígrafe 721.2 / 834)</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Número de Registro / Colegiado *</label>
                    <input type="text" id="plg-license-number" required placeholder="Ej: 10425 / COAPI-2481" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-xs text-navy dark:text-white outline-none focus:border-blue" />
                  </div>
                  <div class="sm:col-span-3 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-navy hover:bg-navy-light text-white text-xs font-bold transition-all shadow-sm">
                      Validar Registro Profesional ✓
                    </button>
                  </div>
                </form>
              </div>

              <!-- BLOQUE 4: TABLA DE REFERIDOS Y ESTADO DE HITOS -->
              <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                  <h4 class="text-base font-black text-navy dark:text-white">Colegas Invitados y Progreso de Hitos</h4>
                  <span class="text-xs text-slate-400">Premios liberados tras validar 3 exclusivas XML</span>
                </div>
                <div id="plg-milestones-table-container" class="overflow-x-auto">
                  <p class="text-xs text-slate-500 py-4">Cargando estado de tus referidos...</p>
                </div>
              </div>
            </div>

            <!-- CRÉDITOS Y LIBRO MAYOR (LEDGER CONTABLE) - ESTILO FUNDFLOW & PUZZLE -->
            <div id="private-panel-credits" class="private-dashboard-panel">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue/10 border border-blue/20 text-blue text-[11px] font-black uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-blue animate-pulse"></span> Monedero y Ledger Contable
                  </span>
                  <h3 class="text-2xl font-black text-navy mt-2">Centro de Créditos y Transacciones</h3>
                  <p class="text-xs text-slate-500 mt-1">Gestión de saldo, recargas instantáneas con Stripe y auditoría transparente de movimientos.</p>
                </div>
                <div class="flex items-center gap-2">
                  <button type="button" onclick="loadAndRenderCreditsLedger()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-sm flex items-center gap-1.5">
                    <span>↻</span> Actualizar saldo
                  </button>
                  <a href="#/planes" class="px-4 py-2.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md flex items-center gap-1.5">
                    <span>💎</span> Ver todos los planes
                  </a>
                </div>
              </div>

              <!-- FUNDFLOW STYLE KPI CARDS (INTERACTIVAS CON EXPLICACIÓN DETALLADA AL CLIC) -->
              <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <!-- 1. Saldo Disponible -->
                <div role="button" tabindex="0" onclick="openCreditDetailModal('saldo_disponible')" onkeydown="if(event.key==='Enter')openCreditDetailModal('saldo_disponible')" class="p-5 rounded-2xl bg-gradient-to-br from-blue/10 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-850 border border-blue/20 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-blue hover:-translate-y-0.5 transition-all cursor-pointer group relative overflow-hidden">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-blue">Saldo Disponible</span>
                    <span class="p-2 rounded-xl bg-blue/10 text-blue text-sm font-bold group-hover:scale-110 transition-transform">💎</span>
                  </div>
                  <div class="mt-3">
                    <strong id="ledger-avail-balance" class="text-3xl font-black text-navy dark:text-white">3,00 Créditos</strong>
                    <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1">Equivalente a 3 desbloqueos de contacto</span>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue mt-2 group-hover:underline">ℹ️ ¿Cómo funciona? →</span>
                  </div>
                </div>

                <!-- 2. Bono Bienvenida -->
                <div role="button" tabindex="0" onclick="openCreditDetailModal('bono_bienvenida')" onkeydown="if(event.key==='Enter')openCreditDetailModal('bono_bienvenida')" class="p-5 rounded-2xl bg-gradient-to-br from-amber/10 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-850 border border-amber/20 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-amber hover:-translate-y-0.5 transition-all cursor-pointer group relative overflow-hidden">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-amber">Bono Bienvenida</span>
                    <span class="p-2 rounded-xl bg-amber/10 text-amber text-sm font-bold group-hover:scale-110 transition-transform">🎁</span>
                  </div>
                  <div class="mt-3">
                    <strong class="text-3xl font-black text-navy dark:text-white">30 Días</strong>
                    <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1">Vigencia inicial de 3 créditos gratis (no acumulables)</span>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber mt-2 group-hover:underline">ℹ️ Ver detalles del bono →</span>
                  </div>
                </div>

                <!-- 3. Consumo Histórico -->
                <div role="button" tabindex="0" onclick="openCreditDetailModal('consumo_historico')" onkeydown="if(event.key==='Enter')openCreditDetailModal('consumo_historico')" class="p-5 rounded-2xl bg-gradient-to-br from-slate-50 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-850 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-slate-400 hover:-translate-y-0.5 transition-all cursor-pointer group relative overflow-hidden">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Consumo Histórico</span>
                    <span class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-bold group-hover:scale-110 transition-transform">🔓</span>
                  </div>
                  <div class="mt-3">
                    <strong id="ledger-consumed-balance" class="text-3xl font-black text-navy dark:text-white">0,00 Usados</strong>
                    <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1">Expedientes y contactos desbloqueados</span>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 dark:text-slate-300 mt-2 group-hover:underline">ℹ️ Ver trazabilidad →</span>
                  </div>
                </div>

                <!-- 4. Recompensa Referidos -->
                <div role="button" tabindex="0" onclick="openCreditDetailModal('recompensa_referidos')" onkeydown="if(event.key==='Enter')openCreditDetailModal('recompensa_referidos')" class="p-5 rounded-2xl bg-gradient-to-br from-green/10 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-850 border border-green/20 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-green hover:-translate-y-0.5 transition-all cursor-pointer group relative overflow-hidden">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-green">Recompensa Circular</span>
                    <span class="p-2 rounded-xl bg-green/10 text-green text-sm font-bold group-hover:scale-110 transition-transform">👥</span>
                  </div>
                  <div class="mt-3">
                    <strong class="text-3xl font-black text-navy dark:text-white">+0,50 cr / contacto</strong>
                    <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1">Ganas saldo cuando otra agencia desbloquea tu captación</span>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-green mt-2 group-hover:underline">ℹ️ ¿Cómo ganar bonos? →</span>
                  </div>
                </div>
              </div>

              <!-- QUANTEXA / PUZZLE STYLE RECHARGE PACKS -->
              <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4 mb-6">
                  <div>
                    <h4 class="text-lg font-black text-navy">Recarga Rápida de Créditos (Stripe)</h4>
                    <p class="text-xs text-slate-500 mt-0.5">Elige el pack que mejor se adapte a tu volumen de operaciones. Pagos cifrados con tarjeta bancaria.</p>
                  </div>
                  <span class="text-xs font-bold text-slate-400">Sin suscripción obligatoria</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                  <!-- Pack 1 crédito puntual (Recarga Rápida) -->
                  <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-blue/50 hover:shadow-md transition-all flex flex-col justify-between bg-slate-50/50 dark:bg-slate-800/40">
                    <div>
                      <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Recarga Mínima</span>
                      <h5 class="text-base font-black text-navy dark:text-white mt-1">Recarga Rápida</h5>
                      <div class="my-3 flex items-baseline gap-1">
                        <span class="text-3xl font-black text-navy dark:text-white">10 €</span>
                        <span class="text-xs text-slate-500">/ 1 crédito</span>
                      </div>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">10,00 € por contacto. Desbloqueo individual inmediato sin suscripción.</p>
                    </div>
                    <button type="button" onclick="buyCreditsStripe('credit_single')" class="mt-5 w-full py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-blue hover:text-white hover:border-blue text-navy dark:text-white text-xs font-bold transition-colors">
                      Recargar 10 € (1 crédito)
                    </button>
                  </div>

                  <!-- Pack 2: Autónomo (5 créditos) -->
                  <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-blue/50 hover:shadow-md transition-all flex flex-col justify-between bg-slate-50/50 dark:bg-slate-800/40">
                    <div>
                      <span class="text-[10px] font-black uppercase tracking-wider text-blue">Pack Autónomo</span>
                      <h5 class="text-base font-black text-navy dark:text-white mt-1">5 Créditos</h5>
                      <div class="my-3 flex items-baseline gap-1">
                        <span class="text-3xl font-black text-navy dark:text-white">19 €</span>
                        <span class="text-xs text-slate-500">/ pack</span>
                      </div>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">3,80 € por contacto. Para agentes y captadores individuales.</p>
                    </div>
                    <button type="button" onclick="buyCreditsStripe('plan_autonomo')" class="mt-5 w-full py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-blue hover:text-white hover:border-blue text-navy dark:text-white text-xs font-bold transition-colors">
                      Comprar 19 € (5 créditos)
                    </button>
                  </div>

                  <!-- Pack 3: Agencia (10 créditos - Destacado) -->
                  <div class="p-5 rounded-2xl border-2 border-blue relative shadow-lg flex flex-col justify-between bg-blue/5 dark:bg-slate-800/60">
                    <span class="absolute -top-3 right-4 px-3 py-0.5 rounded-full bg-blue text-white text-[10px] font-black uppercase tracking-wider">Más Popular</span>
                    <div>
                      <span class="text-[10px] font-black uppercase tracking-wider text-blue">Pack Agencia</span>
                      <h5 class="text-base font-black text-navy dark:text-white mt-1">10 Créditos</h5>
                      <div class="my-3 flex items-baseline gap-1">
                        <span class="text-3xl font-black text-navy dark:text-white">29 €</span>
                        <span class="text-xs text-slate-500">/ pack</span>
                      </div>
                      <p class="text-[11px] text-slate-600 dark:text-slate-300 font-medium">2,90 € por contacto (Ahorro del 24%). La opción favorita para agencias.</p>
                    </div>
                    <button type="button" onclick="buyCreditsStripe('plan_agencia')" class="mt-5 w-full py-2.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md transition-colors">
                      Comprar 29 € (10 créditos)
                    </button>
                  </div>

                  <!-- Pack 4: Broker (15 créditos) -->
                  <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-blue/50 hover:shadow-md transition-all flex flex-col justify-between bg-slate-50/50 dark:bg-slate-800/40">
                    <div>
                      <span class="text-[10px] font-black uppercase tracking-wider text-purple-600">Pack Broker</span>
                      <h5 class="text-base font-black text-navy dark:text-white mt-1">15 Créditos</h5>
                      <div class="my-3 flex items-baseline gap-1">
                        <span class="text-3xl font-black text-navy dark:text-white">49 €</span>
                        <span class="text-xs text-slate-500">/ pack</span>
                      </div>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">3,27 € por contacto. Máximo volumen para redes MLS y brokers.</p>
                    </div>
                    <button type="button" onclick="buyCreditsStripe('plan_broker')" class="mt-5 w-full py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-blue hover:text-white hover:border-blue text-navy dark:text-white text-xs font-bold transition-colors">
                      Comprar 49 € (15 créditos)
                    </button>
                  </div>
                </div>
              </div>

              <!-- LIVE LEDGER TABLE -->
              <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div>
                    <h4 class="text-base font-black text-navy">Libro Mayor Contable (Ledger)</h4>
                    <p class="text-xs text-slate-500 mt-0.5">Registro inmutable de todas las recargas, bonificaciones y consumos de tu cuenta.</p>
                  </div>
                  <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-green"></span> Sincronizado
                  </span>
                </div>
                <div class="overflow-x-auto">
                  <table class="w-full text-left border-collapse">
                    <thead>
                      <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-black text-slate-400 uppercase tracking-wider">
                        <th class="px-4 py-3.5">Fecha y Hora</th>
                        <th class="px-4 py-3.5">Concepto / Movimiento</th>
                        <th class="px-4 py-3.5">Importe</th>
                        <th class="px-4 py-3.5">Saldo Resultante</th>
                        <th class="px-4 py-3.5">Estado</th>
                      </tr>
                    </thead>
                    <tbody id="ledger-transactions-body">
                      <tr>
                        <td colspan="5" class="text-center py-8 text-slate-400 text-xs">Cargando movimientos contables...</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>


  </main>


  <!-- PIE DE PÁGINA CORPORATIVO GLOBAL -->
  <footer class="bg-slate-100/80 dark:bg-[#050c17] border-t border-slate-200 dark:border-slate-800/80 text-slate-600 dark:text-slate-400 transition-colors">
    <div class="max-w-[1780px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 py-12 sm:py-16 space-y-12">

      <!-- 1. PRE-FOOTER: BARRA DE CONFIANZA Y GARANTÍAS INSTITUCIONALES -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pb-10 border-b border-slate-200 dark:border-slate-800">
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 flex items-center gap-3.5 shadow-sm">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shrink-0">⚖️</div>
          <div>
            <strong class="block text-xs font-bold text-navy dark:text-white">Reparto 50/50 Homologado</strong>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight block">Honorarios vinculantes en notaría</span>
          </div>
        </div>
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 flex items-center gap-3.5 shadow-sm">
          <div class="w-10 h-10 rounded-xl bg-blue/10 text-blue flex items-center justify-center text-xl shrink-0">🛡️</div>
          <div>
            <strong class="block text-xs font-bold text-navy dark:text-white">Custodia RGPD & LSSI</strong>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight block">Datos ciegos y notas simples protegidas</span>
          </div>
        </div>
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 flex items-center gap-3.5 shadow-sm">
          <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center text-xl shrink-0">🔒</div>
          <div>
            <strong class="block text-xs font-bold text-navy dark:text-white">Pasarela Bancaria Cifrada</strong>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight block">Stripe SSL 256 bits sin permanencias</span>
          </div>
        </div>
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 flex items-center gap-3.5 shadow-sm">
          <div class="w-10 h-10 rounded-xl bg-amber/10 text-amber flex items-center justify-center text-xl shrink-0">🇪🇸</div>
          <div>
            <strong class="block text-xs font-bold text-navy dark:text-white">Red Profesional España</strong>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight block">Agentes, agencias y brokers verificados</span>
          </div>
        </div>
      </div>

      <!-- 2. GRID PRINCIPAL: MARCA + 4 MENÚS EQUILIBRADOS -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-12">
        
        <!-- Columna 1: Marca, Misión y Datos Fiscales (5 cols en lg) -->
        <div class="lg:col-span-4 space-y-4">
          <div class="flex items-center gap-3">
            <img src="<?php echo esc_url($captacion_media['logo']); ?>" alt="Compra Captación" width="280" height="92" loading="lazy" decoding="async" class="h-auto w-36 sm:w-44" />
            <span class="px-2.5 py-1 rounded-full bg-blue/10 text-blue dark:text-blue-neon text-[9px] font-black uppercase tracking-wider">
              B2B Profesional
            </span>
          </div>
          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-normal">
            Plataforma profesional líder en España para la colaboración entre agentes inmobiliarios. Conectamos captaciones en exclusiva con demanda activa bajo custodia jurídica y reparto 50/50.
          </p>
          <div class="pt-2 text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed space-y-1 bg-white/60 dark:bg-slate-900/40 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800/60">
            <p><strong>Compra Captación</strong> · Red B2B de Colaboración Inmobiliaria</p>
            <p>Atención y soporte: <a href="mailto:hola@compracaptacion.com" class="text-blue font-semibold hover:underline">hola@compracaptacion.com</a></p>
            <p>Plataforma operada bajo RGPD (UE) 2016/679 y LSSI-CE 34/2002.</p>
          </div>
          <div class="flex items-center gap-2 pt-1" aria-label="Redes sociales">
            <a href="https://www.linkedin.com/company/compracaptacion" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="w-9 h-9 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-blue hover:border-blue transition-all shadow-sm">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M5 8H2V21H5V8ZM3.5 3A1.8 1.8 0 1 0 3.5 6.6 1.8 1.8 0 0 0 3.5 3ZM8 8H11V9.8C11.8 8.5 13.2 7.7 15 7.7c3.5 0 4.5 2.2 4.5 5.7V21h-3v-6.7c0-1.6 0-3.6-2.2-3.6s-2.5 1.7-2.5 3.5V21H8V8Z"/></svg>
            </a>
            <a href="https://www.instagram.com/compra_captacion/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="w-9 h-9 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-pink-600 hover:border-pink-500 transition-all shadow-sm">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
            </a>
            <a href="https://www.facebook.com/compracaptacion/" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="w-9 h-9 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-blue hover:border-blue transition-all shadow-sm">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 8h3V4h-3c-3.3 0-5 1.9-5 5v3H6v4h3v4h4v-4h3l1-4h-4V9c0-.7.3-1 1-1Z"/></svg>
            </a>
          </div>
        </div>

        <!-- Columna 2: Plataforma y Red (2 cols) -->
        <div class="lg:col-span-2 space-y-3">
          <h4 class="text-xs font-black uppercase tracking-wider text-navy dark:text-white">
            Plataforma y Red
          </h4>
          <ul class="space-y-2 text-xs">
            <li><a href="<?php echo esc_url(home_url('/inicio')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Inicio</a></li>
            <li><a href="<?php echo esc_url(home_url('/como-funciona')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Cómo funciona</a></li>
            <li><a href="<?php echo esc_url(home_url('/propiedades')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Propiedades compartidas</a></li>
            <li><a href="<?php echo esc_url(home_url('/demandas')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Demandas de compradores</a></li>
            <li><a href="<?php echo esc_url(home_url('/publicar?tipo=oferta')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Compartir propiedad</a></li>
            <li><a href="<?php echo esc_url(home_url('/publicar?tipo=demanda')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Publicar demanda</a></li>
            <li><a href="<?php echo esc_url(home_url('/coincidencias-ventas')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Cruces y coincidencias</a></li>
          </ul>
        </div>

        <!-- Columna 3: Negocio y Herramientas (2 cols) -->
        <div class="lg:col-span-2 space-y-3">
          <h4 class="text-xs font-black uppercase tracking-wider text-navy dark:text-white">
            Herramientas
          </h4>
          <ul class="space-y-2 text-xs">
            <li><a href="<?php echo esc_url(home_url('/precios')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Precios y créditos</a></li>
            <li><a href="<?php echo esc_url(home_url('/recursos')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Biblioteca de Recursos</a></li>
            <li><a href="<?php echo esc_url(home_url('/inicio#calculadora')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Calculadora 50/50</a></li>
            <li><a href="<?php echo esc_url(home_url('/area-privada')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Asistente IA Vera</a></li>
            <li><a href="<?php echo esc_url(home_url('/area-privada')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Panel privado de agente</a></li>
          </ul>
        </div>

        <!-- Columna 4: Legal (2 cols) -->
        <div class="lg:col-span-2 space-y-3">
          <h4 class="text-xs font-black uppercase tracking-wider text-navy dark:text-white">
            Legal
          </h4>
          <ul class="space-y-2 text-xs">
            <li><a href="<?php echo esc_url(home_url('/aviso-legal')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Aviso legal</a></li>
            <li><a href="<?php echo esc_url(home_url('/privacidad')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Política de privacidad</a></li>
            <li><a href="<?php echo esc_url(home_url('/cookies')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Política de cookies</a></li>
            <li><a href="<?php echo esc_url(home_url('/normas-publicacion')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Normas de publicación</a></li>
            <li><a href="<?php echo esc_url(home_url('/condiciones-de-contratacion')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Condiciones de contratación</a></li>
            <li><a href="<?php echo esc_url(home_url('/politica-reembolsos')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Créditos y reembolsos</a></li>
            <li><a href="<?php echo esc_url(home_url('/datos-ciegos')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Política de datos ciegos</a></li>
          </ul>
        </div>

        <!-- Columna 5: Soporte (2 cols) -->
        <div class="lg:col-span-2 space-y-3">
          <h4 class="text-xs font-black uppercase tracking-wider text-navy dark:text-white">Soporte</h4>
          <ul class="space-y-2 text-xs">
            <li><a href="<?php echo esc_url(home_url('/contacto')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Contacto y atención</a></li>
            <li><a href="<?php echo esc_url(home_url('/canal-de-denuncias')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Canal confidencial</a></li>
            <li><button type="button" onclick="openReportModal()" class="text-slate-600 dark:text-slate-400 hover:text-red-500 font-semibold transition-colors flex items-center gap-1.5 text-left"><span aria-hidden="true">🚩</span> Reportar incidencia</button></li>
            <li><a href="mailto:hola@compracaptacion.com" class="text-slate-600 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon font-semibold transition-colors flex items-center gap-1.5"><span>→</span> Reclamaciones</a></li>
          </ul>
        </div>

      </div>

      <!-- 3. SUB-FOOTER INFERIOR -->
      <div class="pt-8 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400">
        <p>© <?php echo date('Y'); ?> <strong>Compra Captación</strong> · Todos los derechos reservados.</p>
        <div class="flex items-center gap-4 text-[11px]">
          <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Sistemas operativos
          </span>
          <span>·</span>
          <button type="button" onclick="captacionOpenCookiePreferences()" class="hover:underline text-slate-500 dark:text-slate-400">Preferencias de cookies</button>
        </div>
      </div>

    </div>
  </footer>

  <!-- BARRA DE NAVEGACIÓN INFERIOR NATIVA TIPO APP (MOBILE & TABLET BOTTOM TAB BAR) -->
  <nav id="mobile-app-tabbar" class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-[#081526]/95 backdrop-blur-xl border-t border-slate-200/90 dark:border-slate-800/90 px-2 py-1.5 lg:hidden shadow-[0_-4px_20px_rgba(0,0,0,0.06)] dark:shadow-[0_-4px_20px_rgba(0,0,0,0.3)] transition-all safe-area-bottom">
    <div class="max-w-md mx-auto grid grid-cols-5 items-center justify-items-center relative">
      
      <!-- Tab 1: Inicio -->
      <a href="<?php echo esc_url(home_url('/inicio')); ?>" onclick="setActiveMobileTab('inicio')" data-mobile-tab="inicio" class="mobile-tab-btn flex flex-col items-center justify-center py-1 px-1.5 w-full text-slate-500 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon transition-colors group">
        <span class="text-lg transition-transform group-active:scale-90">🏠</span>
        <span class="text-[10px] font-bold mt-0.5 tracking-tight">Inicio</span>
        <span class="tab-active-dot w-1 h-1 rounded-full bg-blue dark:bg-blue-neon mt-0.5 opacity-0 transition-opacity"></span>
      </a>

      <!-- Tab 2: Propiedades en Colaboración -->
      <a href="<?php echo esc_url(home_url('/propiedades')); ?>" onclick="setActiveMobileTab('propiedades')" data-mobile-tab="propiedades" class="mobile-tab-btn flex flex-col items-center justify-center py-1 px-1.5 w-full text-slate-500 dark:text-slate-400 hover:text-blue dark:hover:text-blue-neon transition-colors group">
        <span class="text-lg transition-transform group-active:scale-90">🏢</span>
        <span class="text-[10px] font-bold mt-0.5 tracking-tight">Inmuebles</span>
        <span class="tab-active-dot w-1 h-1 rounded-full bg-blue dark:bg-blue-neon mt-0.5 opacity-0 transition-opacity"></span>
      </a>

      <!-- Tab 3: Super Action (+) Botón Central Elevado -->
      <div class="relative -top-3.5">
        <button type="button" onclick="openMobileActionSheet()" class="w-12 h-12 rounded-full bg-gradient-to-tr from-blue to-blue-dark dark:from-blue dark:to-cyan-400 text-white flex items-center justify-center shadow-lg shadow-blue/40 border-4 border-white dark:border-[#081526] transition-all hover:scale-105 active:scale-95 focus:outline-none" aria-label="Publicar o Crear">
          <span class="text-2xl font-black leading-none">+</span>
        </button>
      </div>

      <!-- Tab 4: Demandas -->
      <a href="<?php echo esc_url(home_url('/demandas')); ?>" onclick="setActiveMobileTab('demandas')" data-mobile-tab="demandas" class="mobile-tab-btn flex flex-col items-center justify-center py-1 px-1.5 w-full text-slate-500 dark:text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors group">
        <span class="text-lg transition-transform group-active:scale-90">🎯</span>
        <span class="text-[10px] font-bold mt-0.5 tracking-tight">Demandas</span>
        <span class="tab-active-dot w-1 h-1 rounded-full bg-emerald-500 mt-0.5 opacity-0 transition-opacity"></span>
      </a>

      <!-- Tab 5: Mi Cuenta / Menú -->
      <button type="button" onclick="handleMobileTabMenuOrAccount()" data-mobile-tab="menu" class="mobile-tab-btn flex flex-col items-center justify-center py-1 px-1.5 w-full text-slate-500 dark:text-slate-400 hover:text-navy dark:hover:text-white transition-colors group">
        <span class="text-lg transition-transform group-active:scale-90">👤</span>
        <span id="mobile-tab-account-label" class="text-[10px] font-bold mt-0.5 tracking-tight">Menú</span>
        <span class="tab-active-dot w-1 h-1 rounded-full bg-navy dark:bg-white mt-0.5 opacity-0 transition-opacity"></span>
      </button>

    </div>
  </nav>

  <!-- ACTION SHEET MODAL PARA EL BOTÓN CENTRAL (+) -->
  <div id="mobile-action-sheet-backdrop" onclick="closeMobileActionSheet()" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden"></div>

  <div id="mobile-action-sheet" class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-[#0b192c] rounded-t-3xl border-t border-slate-200 dark:border-slate-800 shadow-2xl p-6 translate-y-full transition-transform duration-300 ease-out lg:hidden max-w-lg mx-auto">
    <div class="w-12 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 mx-auto mb-5"></div>
    <h3 class="text-sm font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4 text-center">
      ¿Qué deseas publicar o explorar?
    </h3>
    <div class="space-y-3">
      <button type="button" onclick="closeMobileActionSheet(); navigateTo('/publicar?tipo=oferta');" class="w-full flex items-center gap-3.5 p-3.5 rounded-2xl bg-blue/5 dark:bg-blue/10 border border-blue/20 hover:border-blue transition-all text-left group">
        <div class="w-10 h-10 rounded-xl bg-blue text-white flex items-center justify-center font-bold text-lg shadow-sm group-hover:scale-105 transition-transform">🏢</div>
        <div>
          <strong class="block text-sm font-bold text-navy dark:text-white">Compartir Propiedad 50/50</strong>
          <span class="block text-xs text-slate-500 dark:text-slate-400">Publica tu captación en exclusiva para que otras agencias aporten comprador</span>
        </div>
      </button>

      <button type="button" onclick="closeMobileActionSheet(); navigateTo('/publicar?tipo=demanda');" class="w-full flex items-center gap-3.5 p-3.5 rounded-2xl bg-emerald-500/5 dark:bg-emerald-500/10 border border-emerald-500/20 hover:border-emerald-500 transition-all text-left group">
        <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-lg shadow-sm group-hover:scale-105 transition-transform">🎯</div>
        <div>
          <strong class="block text-sm font-bold text-navy dark:text-white">Publicar Demanda de Comprador</strong>
          <span class="block text-xs text-slate-500 dark:text-slate-400">Busca inmuebles cualificados para tu cliente inversor o comprador</span>
        </div>
      </button>

      <button type="button" onclick="closeMobileActionSheet(); navigateTo('/coincidencias-ventas');" class="w-full flex items-center gap-3.5 p-3.5 rounded-2xl bg-purple-500/5 dark:bg-purple-500/10 border border-purple-500/20 hover:border-purple-500 transition-all text-left group">
        <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold text-lg shadow-sm group-hover:scale-105 transition-transform">🤖</div>
        <div>
          <strong class="block text-sm font-bold text-navy dark:text-white">Cruces Automáticos con IA Vera</strong>
          <span class="block text-xs text-slate-500 dark:text-slate-400">Comprueba coincidencias de alta afinidad entre compradores y propiedades</span>
        </div>
      </button>
    </div>
    <button type="button" onclick="closeMobileActionSheet()" class="mt-5 w-full py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
      Cancelar
    </button>
  </div>

  <div id="opportunity-choice-modal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="opportunity-choice-title">
    <div class="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl p-6 sm:p-8" tabindex="-1">
      <div class="flex items-start justify-between gap-4"><div><span class="text-[10px] font-black uppercase tracking-widest text-blue">Explorar oportunidades</span><h2 id="opportunity-choice-title" class="text-xl sm:text-2xl font-black text-navy dark:text-white mt-2">¿Qué quieres explorar?</h2><p class="text-sm text-slate-500 dark:text-slate-300 mt-2">Elige el espacio profesional que necesitas ahora.</p></div><button type="button" onclick="closeOpportunityChoiceModal()" class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 text-xl" aria-label="Cerrar selector">×</button></div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-6"><a href="<?php echo esc_url(home_url('/propiedades')); ?>" class="p-4 rounded-2xl border border-blue/20 bg-blue/5 hover:bg-blue hover:text-white text-left transition-colors"><span class="text-2xl">🏢</span><strong class="block mt-3 text-sm font-black">Propiedades compartidas</strong><span class="block mt-1 text-xs opacity-75">Captaciones protegidas con datos ciegos.</span></a><a href="<?php echo esc_url(home_url('/demandas')); ?>" class="p-4 rounded-2xl border border-emerald-500/20 bg-emerald-500/5 hover:bg-emerald-600 hover:text-white text-left transition-colors"><span class="text-2xl">🎯</span><strong class="block mt-3 text-sm font-black">Demandas compartidas</strong><span class="block mt-1 text-xs opacity-75">Compradores e inversores cualificados.</span></a></div>
      <button type="button" onclick="closeOpportunityChoiceModal()" class="w-full mt-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">Cerrar</button>
    </div>
  </div>

  <aside id="captacion-cookie-notice" class="captacion-cookie-notice is-hidden" role="dialog" aria-modal="false" aria-labelledby="captacion-cookie-title" aria-describedby="captacion-cookie-description">
    <button type="button" class="captacion-cookie-notice-close" aria-label="Cerrar aviso de cookies" onclick="captacionDismissCookieNotice()">×</button>
    <div class="captacion-cookie-notice-inner">
      <div class="captacion-cookie-notice-copy">
        <h2 id="captacion-cookie-title">Este sitio web utiliza cookies</h2>
        <p id="captacion-cookie-description">Usamos cookies necesarias y, si lo autorizas, tecnologías adicionales para mejorar la experiencia. Puedes aceptar, rechazar o elegir tus preferencias.</p>
      </div>
      <div class="captacion-cookie-notice-actions">
        <button type="button" class="is-primary" onclick="captacionAcceptCookies()">Aceptar</button>
        <button type="button" onclick="captacionRejectCookies()">Denegar</button>
        <button type="button" class="is-preferences" onclick="captacionOpenCookiePreferences()">Preferencias</button>
      </div>
    </div>
  </aside>


  <!-- MODAL DE PREVISUALIZACIÓN DE FICHA ANTES DE PUBLICAR -->
  <div id="preview-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-navy-dark/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative overflow-y-auto max-h-[90vh]">
      <button onclick="closePreviewModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>

      <div class="border-b border-slate-100 pb-3 mb-4">
        <h3 class="text-xl font-extrabold text-navy">Revisión de Captación</h3>
        <p class="text-xs text-slate-400 mt-1">Comprueba la ficha tal y como aparecerá en el Marketplace profesional.</p>
      </div>

      <!-- Contenedor dinámico de la tarjeta de previsualización -->
      <div id="card-preview-area" class="mb-6">
        <!-- Inyectado mediante JS de forma idéntica a la imagen del hito -->
      </div>

      <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
        <button id="preview-back-btn" onclick="closePreviewModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50">
          Modificar datos
        </button>
        <button id="preview-publish-btn" onclick="confirmAndPublish()" class="px-5 py-2.5 rounded-xl bg-blue text-white text-xs font-black hover:bg-blue-dark shadow-md flex items-center gap-1.5">
          Aprobar y publicar
        </button>
      </div>
    </div>
  </div>

  <div id="xml-feed-report-modal" class="fixed inset-0 z-[90] hidden flex items-center justify-center p-4 bg-navy-dark/70 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative overflow-y-auto max-h-[90vh]">
      <button type="button" onclick="closeXmlFeedReportModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>
      <span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Informe XML</span>
      <h3 id="xml-feed-report-title" class="text-xl font-extrabold text-navy mt-3">Resumen del XML</h3>
      <div id="xml-feed-report-body" class="mt-5 text-sm text-slate-600"></div>
      <div class="flex justify-end mt-6 pt-4 border-t border-slate-100">
        <button type="button" onclick="closeXmlFeedReportModal()" class="px-5 py-2.5 rounded-xl bg-blue text-white text-xs font-black hover:bg-blue-dark shadow-md">Cerrar</button>
      </div>
    </div>
  </div>

  <div id="xml-feed-pending-modal" class="fixed inset-0 z-[90] hidden flex items-center justify-center p-4 bg-navy-dark/70 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative overflow-y-auto max-h-[90vh]">
      <button type="button" onclick="closeFeedPendingModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>
      <span class="inline-flex px-3 py-1 rounded-full bg-amber-light text-amber text-[10px] font-black uppercase">Revisión</span>
      <h3 id="xml-feed-pending-title" class="text-xl font-extrabold text-navy mt-3">Propiedades pendientes de revisión</h3>

      <!-- Filtros y Selección masiva -->
      <div id="xml-feed-pending-controls" class="mt-4 hidden flex flex-col gap-3 p-4 bg-slate-50 border border-slate-200 rounded-2xl">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-2 border-b border-slate-200">
          <label class="flex items-center gap-2 cursor-pointer text-xs font-black text-navy select-none">
            <input type="checkbox" id="xml-pending-select-all" onchange="toggleSelectAllPending(this)" class="rounded text-blue border-slate-300 focus:ring-blue/20 w-4 h-4 cursor-pointer" />
            Seleccionar todas visibles
          </label>
          <button type="button" onclick="deselectAllPendingProperties()" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 text-[10px] font-black">Deseleccionar todas</button>
          <div class="flex items-center gap-2">
            <span class="text-xs text-navy font-bold">Filtrar vista:</span>
            <select id="xml-pending-category-filter" onchange="filterPendingByCategory(this.value)" class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-navy font-bold focus:ring-2 focus:ring-blue/20 cursor-pointer">
              <option value="all">Todas las categorías</option>
              <option value="Piso">Pisos</option>
              <option value="Casa/Chalet">Casas / Chalets</option>
              <option value="Local Comercial">Locales Comerciales</option>
              <option value="Nave">Naves Industriales</option>
              <option value="Oficina">Oficinas</option>
              <option value="Edificio">Edificios</option>
              <option value="Suelo/Terreno">Suelos / Terrenos</option>
              <option value="Otros">Otros</option>
            </select>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
          <span class="text-xs text-navy font-bold">Seleccionar por categoría:</span>
          <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer select-none">
            <input type="checkbox" data-pending-cat-select="Piso" onchange="toggleSelectPendingCategory('Piso', this.checked)" class="rounded text-blue border-slate-300 focus:ring-blue/20 w-3.5 h-3.5 cursor-pointer" />
            Pisos
          </label>
          <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer select-none">
            <input type="checkbox" data-pending-cat-select="Casa/Chalet" onchange="toggleSelectPendingCategory('Casa/Chalet', this.checked)" class="rounded text-blue border-slate-300 focus:ring-blue/20 w-3.5 h-3.5 cursor-pointer" />
            Casas/Chalets
          </label>
          <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer select-none">
            <input type="checkbox" data-pending-cat-select="Local Comercial" onchange="toggleSelectPendingCategory('Local Comercial', this.checked)" class="rounded text-blue border-slate-300 focus:ring-blue/20 w-3.5 h-3.5 cursor-pointer" />
            Locales Comerciales
          </label>
          <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer select-none">
            <input type="checkbox" data-pending-cat-select="Nave" onchange="toggleSelectPendingCategory('Nave', this.checked)" class="rounded text-blue border-slate-300 focus:ring-blue/20 w-3.5 h-3.5 cursor-pointer" />
            Naves
          </label>
          <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer select-none">
            <input type="checkbox" data-pending-cat-select="Oficina" onchange="toggleSelectPendingCategory('Oficina', this.checked)" class="rounded text-blue border-slate-300 focus:ring-blue/20 w-3.5 h-3.5 cursor-pointer" />
            Oficinas
          </label>
          <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer select-none">
            <input type="checkbox" data-pending-cat-select="Edificio" onchange="toggleSelectPendingCategory('Edificio', this.checked)" class="rounded text-blue border-slate-300 focus:ring-blue/20 w-3.5 h-3.5 cursor-pointer" />
            Edificios
          </label>
          <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer select-none">
            <input type="checkbox" data-pending-cat-select="Suelo/Terreno" onchange="toggleSelectPendingCategory('Suelo/Terreno', this.checked)" class="rounded text-blue border-slate-300 focus:ring-blue/20 w-3.5 h-3.5 cursor-pointer" />
            Suelos/Terrenos
          </label>
          <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer select-none">
            <input type="checkbox" data-pending-cat-select="Otros" onchange="toggleSelectPendingCategory('Otros', this.checked)" class="rounded text-blue border-slate-300 focus:ring-blue/20 w-3.5 h-3.5 cursor-pointer" />
            Otros
          </label>
        </div>

        <div id="xml-feed-pending-actions" class="flex justify-end gap-2 border-t border-slate-200 pt-2 mt-1">
          <button type="button" id="xml-publish-selected-btn" onclick="publishSelectedPendingProperties(this)" class="px-4 py-2 rounded-xl bg-green hover:bg-green-dark text-white text-xs font-black shadow-md transition-all">Publicar seleccionadas tal cual</button>
          <button type="button" id="xml-publish-all-btn" onclick="publishAllPendingProperties()" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-navy text-xs font-black transition-all">Publicar todas</button>
        </div>
      </div>

      <div id="xml-feed-pending-body" class="mt-5 space-y-3 text-sm text-slate-600"></div>
      <div class="flex justify-between items-center mt-6 pt-4 border-t border-slate-100">
        <span id="xml-feed-pending-counter" class="text-xs text-slate-400"></span>
        <button type="button" onclick="closeFeedPendingModal()" class="px-5 py-2.5 rounded-xl bg-blue text-white text-xs font-black hover:bg-blue-dark shadow-md">Cerrar</button>
      </div>
    </div>
  </div>

  <!-- MODAL DE COMPRA DE CAPTACION CON STRIPE -->
  <div id="access-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-navy-dark/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative">
      <button onclick="closeAccessModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>
      <span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Acceso a oportunidad</span>
      <h3 id="access-modal-title" class="text-xl font-extrabold text-navy mt-4">Acceder a captación</h3>
      <div id="access-modal-summary" class="mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-600 leading-relaxed"></div>
      <form onsubmit="handleMarketplaceAccess(event)" class="space-y-4 mt-5">
        <input type="hidden" id="access-property-id" />
        <div class="rounded-2xl border border-amber-200 bg-amber-light/60 p-4 text-[11px] leading-relaxed text-slate-600">
          <span id="access-modal-plan-message">Comprobando accesos disponibles...</span>
        </div>
        <label class="flex items-start gap-2 text-[11px] text-slate-500 leading-relaxed">
          <input type="checkbox" required class="mt-0.5" />
          <span>Acepto mantener la confidencialidad de los datos de la captación y consumir el acceso cuando corresponda.</span>
        </label>
        <button id="stripe-payment-button" class="w-full py-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md">Continuar al pago seguro</button>
      </form>
    </div>
  </div>


  <!-- MODAL: PROPONER CAPTACIÓN COMPATIBLE -->
  <div id="need-collaboration-modal" class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4 bg-navy-dark/70 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative overflow-y-auto max-h-[92vh]">
      <button type="button" onclick="closeNeedCollaborationModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl font-black">×</button>
      <span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Colaboración profesional</span>
      <h3 class="text-xl font-extrabold text-navy mt-4">Proponer captación compatible</h3>
      <p class="text-xs text-slate-500 mt-2 leading-relaxed">Tienes una captación que puede encajar con esta demanda. Selecciona una oportunidad disponible en Marketplace y enviaremos una notificación al agente demandante.</p>
      <div id="need-collaboration-summary" class="mt-5 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600"></div>
      <form onsubmit="submitNeedCollaboration(event)" class="mt-5 space-y-4">
        <input id="need-collaboration-need-id" type="hidden" />
        <div><label class="block text-xs font-bold text-slate-500 mb-1">Captación disponible *</label><select id="need-collaboration-property" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-sm"></select></div>
        <div><label class="block text-xs font-bold text-slate-500 mb-1">Mensaje opcional</label><textarea id="need-collaboration-message" rows="4" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm resize-none">Tengo una captación publicada en Marketplace que podría encajar con tu búsqueda. Puedes revisarla y solicitar acceso si te interesa.</textarea></div>
        <button class="w-full py-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md">Enviar propuesta de colaboración</button>
      </form>
    </div>
  </div>

  <!-- MODAL DE MATCHMAKER IA -->
  <div id="ai-match-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-navy-dark/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative overflow-y-auto max-h-[90vh]">
      <button onclick="closeAiMatchModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>
      <div class="border-b border-slate-100 pb-4">
        <span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Asistente de cruce comercial</span>
        <h3 class="text-xl font-extrabold text-navy mt-3">Informe de compatibilidad de cartera</h3>
      </div>
      <div id="ai-loading" class="py-12 text-center">
        <div class="text-3xl animate-pulse">✨</div>
        <p class="text-sm font-bold text-navy mt-3">Analizando coincidencias de producto y demanda...</p>
      </div>
      <div id="ai-report" class="hidden py-4">
        <div id="ai-report-content" class="text-sm text-slate-600 leading-relaxed"></div>
        <div class="flex justify-end mt-6 pt-4 border-t border-slate-100">
          <button onclick="copyAiReport()" class="px-4 py-2 rounded-xl bg-navy text-white text-xs font-black">Copiar informe</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL DE HERRAMIENTA PROFESIONAL -->
  <div id="resource-tool-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-navy-dark/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative overflow-y-auto max-h-[92vh]">
      <button onclick="closeResourceToolModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>
      <span id="resource-tool-kicker" class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Herramienta inmobiliaria</span>
      <h3 id="resource-tool-title" class="text-xl sm:text-2xl font-black text-navy mt-4">Herramienta profesional</h3>
      <p id="resource-tool-description" class="text-xs text-slate-500 mt-2 leading-relaxed"></p>
      <div id="resource-tool-body" class="mt-5"></div>
    </div>
  </div>

  <!-- MODAL: PREPARAR DOCUMENTO PARA FIRMA ELECTRÓNICA -->
  <div id="legal-signature-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-navy-dark/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative overflow-y-auto max-h-[90vh]">
      <button onclick="closeLegalSignatureModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>
      <span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Flujo documental</span>
      <h3 id="legal-signature-title" class="text-xl font-extrabold text-navy mt-4">Preparar documento para firma electrónica</h3>
      <p class="text-xs text-slate-500 mt-2 leading-relaxed">Completa los datos esenciales. En producción se generará un enlace seguro para revisar el documento, completar los campos restantes y firmar electrónicamente con trazabilidad.</p>
      <form onsubmit="generateLegalSignatureLink(event)" class="space-y-4 mt-5">
        <input type="hidden" id="legal-document-type" value="nda" />
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-bold text-slate-500 mb-1">ID interno de operación *</label><input id="legal-operation-reference" required inputmode="numeric" pattern="[0-9]+" placeholder="Ej.: 123" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></div>
          <div><label class="block text-xs font-bold text-slate-500 mb-1">Código Postal</label><input id="legal-postal-code" inputmode="numeric" maxlength="5" placeholder="Ej.: 32002" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></div>
        </div>
        <div><label class="block text-xs font-bold text-slate-500 mb-1">Nombre o razón social del firmante *</label><input id="legal-signer-name" required placeholder="Profesional o agencia colaboradora" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-bold text-slate-500 mb-1">Correo *</label><input id="legal-signer-email" type="email" required placeholder="agente@agencia.es" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></div>
          <div><label class="block text-xs font-bold text-slate-500 mb-1">WhatsApp *</label><input id="legal-signer-whatsapp" required placeholder="+34 600 000 000" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></div>
        </div>
        <label class="flex items-start gap-2 text-[11px] text-slate-500 leading-relaxed"><input type="checkbox" required class="mt-0.5" /><span>Confirmo que los datos son correctos y autorizo la preparación del enlace de firma electrónica.</span></label>
        <button class="w-full py-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md">Preparar enlace seguro</button>
      </form>
      <div id="legal-signature-result" class="hidden mt-5 p-4 rounded-2xl bg-green-light border border-green/20 text-xs text-slate-600"></div>
    </div>
  </div>

  <!-- MODAL DE CONFIGURACIÓN DE COOKIES -->
  <!-- MODAL: CONECTAR PROVEEDOR DE IA -->
  <div id="ai-connection-modal" class="fixed inset-0 z-[75] hidden flex items-center justify-center p-4 bg-navy-dark/70 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative overflow-y-auto max-h-[92vh]">
      <button type="button" onclick="closeAIConnectionModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>
      <span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Bring your own AI</span>
      <h3 class="text-xl font-extrabold text-navy mt-4">Conecta tu proveedor de inteligencia artificial</h3>
      <p class="text-xs text-slate-500 mt-2 leading-relaxed">
        Guarda tu proveedor, modelo y credencial desde una sesión autenticada. La API key se envía al backend y se conserva cifrada para tu usuario de WordPress.
      </p>
      <form onsubmit="saveAIConnection(event)" class="space-y-4 mt-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Proveedor *</label>
            <select id="ai-provider-select" required onchange="syncAIProviderDefaults()" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm">
              <option value="openai">OpenAI</option>
              <option value="anthropic">Anthropic</option>
              <option value="google">Google</option>
              <option value="groq">Groq</option>
              <option value="openrouter">OpenRouter</option>
              <option value="compatible">Endpoint compatible con OpenAI</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Alias interno *</label>
            <input id="ai-connection-alias" required placeholder="Ej.: IA de mi agencia" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" />
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Perfil de uso *</label>
            <select id="ai-use-profile" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm">
              <option value="general">Asistente general inmobiliario</option>
              <option value="copywriting">Redacción de anuncios</option>
              <option value="matching">Cruce oferta–demanda</option>
              <option value="documentos">Análisis documental</option>
              <option value="automatizacion">Automatizaciones</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Modelo preferido</label>
            <input id="ai-model-name" placeholder="Ej.: modelo recomendado" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" />
          </div>
        </div>
        <div id="ai-endpoint-wrap" class="hidden">
          <label class="block text-xs font-bold text-slate-500 mb-1">Endpoint compatible *</label>
          <input id="ai-backend-endpoint" type="url" placeholder="https://api.tudominio.com/v1/chat/completions" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" />
          <p class="text-[10px] text-slate-400 mt-1">Solo para endpoints compatibles con OpenAI. La llamada siempre se realizará desde backend.</p>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 mb-1">API key o credencial *</label>
          <input id="ai-secret-input" type="password" required autocomplete="off" placeholder="Se almacenará cifrada para tu usuario" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" />
          <p class="text-[10px] text-slate-400 mt-1">Tus credenciales se usan solo para tus solicitudes y nunca se guardan en localStorage.</p>
        </div>
        <label class="flex items-start gap-2 text-[11px] text-slate-500 leading-relaxed">
          <input id="ai-security-confirmation" type="checkbox" required class="mt-0.5" />
          <span>Confirmo que esta conexión me pertenece y autorizo su uso solo para mis acciones asistidas dentro de Compra Captación.</span>
        </label>
        <div class="flex flex-col sm:flex-row gap-3">
          <button id="ai-save-connection-btn" type="submit" class="flex-1 py-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-bold shadow-md">Guardar conexión</button>
          <button type="button" onclick="closeAIConnectionModal()" class="px-5 py-3 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:border-slate-300">Cancelar</button>
        </div>
      </form>
    </div>
  </div>


  <!-- MODAL PRIVADO: EXPEDIENTE DE OPERACIÓN -->

  <div id="comm-thread-modal" class="fixed inset-0 z-[95] hidden flex items-center justify-center p-4 bg-navy-dark/75 backdrop-blur-sm">
    <div class="w-full max-w-4xl max-h-[92vh] overflow-hidden rounded-3xl bg-white shadow-2xl flex flex-col">
      <div class="px-5 py-4 border-b border-slate-200 flex items-start justify-between gap-4"><div><span class="text-[10px] font-black uppercase tracking-wider text-blue">Sala privada de colaboración</span><h3 id="comm-thread-title" class="text-lg font-black text-navy mt-1">Conversación protegida</h3><p id="comm-thread-subtitle" class="text-[11px] text-slate-500 mt-1"></p></div><button onclick="closeProtectedThread()" class="w-9 h-9 rounded-xl border border-slate-200 text-slate-500">✕</button></div>
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_270px] min-h-0 flex-1">
        <section class="min-h-0 flex flex-col border-r border-slate-200"><div class="comm-safe-banner m-4 mb-0"><strong class="block text-xs text-green">🔒 Conversación trazable</strong><p class="text-[10px] text-slate-500 mt-1">No compartas teléfonos, emails ni enlaces externos. La plataforma bloqueará el envío y registrará el intento.</p></div><div id="comm-thread-messages" class="p-4 space-y-3 overflow-y-auto flex-1 min-h-[300px] max-h-[470px]"></div><div class="p-4 border-t border-slate-200"><textarea id="comm-thread-input" rows="3" placeholder="Escribe un mensaje dentro de la sala protegida..." class="w-full px-3 py-3 rounded-xl border border-slate-200 text-xs resize-none"></textarea><div class="flex flex-wrap items-center justify-between gap-2 mt-2"><span class="text-[10px] text-slate-400">Los mensajes se asocian al expediente.</span><button onclick="sendProtectedThreadMessage()" class="px-4 py-2.5 rounded-xl bg-blue text-white text-xs font-bold">Enviar mensaje</button></div></div></section>
        <aside class="p-4 overflow-y-auto"><h4 class="text-xs font-black text-navy">Flujo protegido</h4><div id="comm-thread-flow" class="mt-4 space-y-3"></div><div class="mt-5 p-3 rounded-xl bg-slate-50 border border-slate-200"><span class="block text-[10px] text-slate-500">Contraparte</span><strong class="block text-xs text-navy mt-1">Profesional verificado · identidad protegida</strong><span class="block text-[10px] text-slate-500 mt-1">Contacto directo no disponible</span></div><button id="comm-thread-progress-btn" onclick="advanceProtectedFlow()" class="w-full mt-4 px-4 py-3 rounded-xl bg-navy text-white text-xs font-bold"></button><button onclick="closeProtectedThread();switchPrivateDashboardPanel('traceability')" class="w-full mt-2 px-4 py-3 rounded-xl border border-slate-200 text-navy text-xs font-bold">Ver trazabilidad</button></aside>
      </div>
    </div>
  </div>

  <div id="private-operation-modal" class="fixed inset-0 z-[80] hidden flex items-center justify-center p-4 bg-navy-dark/70 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative overflow-y-auto max-h-[92vh]">
      <button type="button" onclick="closePrivateOperationModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>
      <span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Expediente privado</span>
      <h3 id="private-operation-modal-title" class="text-xl font-black text-navy mt-4">Operación</h3>
      <div id="private-operation-modal-body" class="mt-5"></div>
    </div>
  </div>

  <!-- MODAL DE REPORTE DE CONTENIDO -->
  <div id="content-report-modal" class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4 bg-navy-dark/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 border border-slate-100 shadow-2xl relative">
      <button onclick="closeReportModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-xl font-bold">×</button>
      <span class="inline-flex px-3 py-1 rounded-full bg-amber-light text-amber text-[10px] font-black uppercase">Canal de reporte</span>
      <h3 class="text-xl font-extrabold text-navy mt-4">Reportar contenido o incidencia</h3>
      <p class="text-xs text-slate-500 mt-2 leading-relaxed">Describe la incidencia para que el equipo pueda revisarla. El reporte quedará asociado a tu cuenta.</p>
      <form onsubmit="submitContentReport(event)" class="mt-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Nombre *</span><input id="report-name" required autocomplete="name" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></label><label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Email *</span><input id="report-email" type="email" required autocomplete="email" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></label></div>
        <label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Teléfono</span><input id="report-phone" type="tel" autocomplete="tel" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></label>
        <label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">URL opcional</span><input id="report-content-reference" type="url" placeholder="https://..." class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></label>
        <label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Comentario *</span><textarea id="report-content-description" required minlength="10" rows="4" placeholder="Describe la incidencia con suficiente detalle." class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm"></textarea></label>
        <input id="report-website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
        <label class="flex items-start gap-2 text-[11px] text-slate-500 leading-relaxed"><input type="checkbox" required class="mt-0.5" /><span>Declaro que este reporte se realiza de buena fe y que la información aportada es correcta.</span></label>
        <button class="w-full py-3 rounded-xl bg-navy text-white text-xs font-bold">Enviar reporte</button>
      </form>
    </div>
  </div>

  <script>
    window.CAPTACION_APP_AI = <?php echo wp_json_encode(array(
      'restBase' => rest_url('captacion-app/v1/ai/'),
      'nonce' => $captacion_rest_nonce,
      'isLoggedIn' => $captacion_is_logged_in,
      'userLabel' => $captacion_is_logged_in ? $captacion_current_user->display_name : '',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  </script>
  <script>
    // Respaldo público: si un script externo rompe la inicialización, no dejamos la home en blanco.
    (function ensureVisiblePublicHome() {
      function revealHomeIfRouterDidNotStart() {
        if (document.querySelector('.page-section:not(.hidden)')) return;
        document.getElementById('page-inicio')?.classList.remove('hidden');
      }
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { setTimeout(revealHomeIfRouterDidNotStart, 400); });
      } else {
        setTimeout(revealHomeIfRouterDidNotStart, 400);
      }
    })();
  </script>

  <?php 
    // Fuente única de comportamiento: evita que temas/rutas carguen un bundle antiguo.
    $captacion_app_js_url = !empty($captacion_theme_uri)
        ? (rtrim($captacion_theme_uri, '/') . '/assets/js/app.js')
        : '/assets/js/app.js';
  ?>
  <script src="<?php echo esc_url($captacion_app_js_url . '?ver=20260823-v12'); ?>" defer></script>

<script>
(function(){
  const COMM_STORAGE_KEY='captacion_internal_communications_v1';
  const FLOW_STAGES=[
    {id:'match_detected',label:'Coincidencia detectada',button:'Crear solicitud protegida'},
    {id:'request_sent',label:'Solicitud enviada',button:'Confirmar disponibilidad'},
    {id:'availability_confirmed',label:'Disponibilidad confirmada',button:'Preparar Acuerdo de Confidencialidad (NDA)'},
    {id:'nda_pending',label:'Acuerdo de Confidencialidad (NDA) pendiente',button:'Simular firma del Acuerdo de Confidencialidad (NDA)'},
    {id:'nda_signed',label:'Acuerdo de Confidencialidad (NDA) firmado',button:'Preparar pago de acceso'},
    {id:'payment_pending',label:'Pago pendiente',button:'Simular pago confirmado'},
    {id:'payment_confirmed',label:'Pago confirmado',button:'Activar sala privada'},
    {id:'room_active',label:'Sala privada activa',button:'Flujo protegido completado'}
  ];
  let activeThreadId='';
  const safeEsc=(value)=>typeof escapeHTML==='function'?escapeHTML(String(value ?? '')):String(value ?? '').replace(/[&<>"']/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  const now=()=>Date.now();
  const uid=(prefix)=>`${prefix}-${Date.now()}-${Math.random().toString(36).slice(2,7)}`;
  function getNeedRef(need){return need?.id||'DEM-N/D'}
  function firstNeed(){return (window.needs||[])[0]||null}
  function secondNeed(){return (window.needs||[])[1]||firstNeed()}
  function firstProp(){return (window.properties||[])[0]||null}
  function secondProp(){return (window.properties||[])[1]||firstProp()}
  function seedComm(){
    return {
      preferences:{inApp:true,email:true,whatsapp:true,frequency:'instant'},
      subscriptions:[],
      events:[],
      threads:[],
      trace:[]
    }
  }
  function getComm(){
    try{const parsed=JSON.parse(localStorage.getItem(COMM_STORAGE_KEY));if(parsed&&Array.isArray(parsed.subscriptions)&&Array.isArray(parsed.threads)&&Array.isArray(parsed.trace))return parsed}catch(e){}
    const data=seedComm();saveComm(data);return data
  }
  function saveComm(data){try{localStorage.setItem(COMM_STORAGE_KEY,JSON.stringify(data))}catch(e){}}
  function protectedThreadTitle(propertyId, fallback='Colaboración inmobiliaria'){
    const property=typeof privatePropertyById==='function'?privatePropertyById(propertyId):(window.properties||[]).find(item=>item.id===propertyId);
    return property?.title ? `Conversación sobre ${property.title}` : fallback;
  }
  function createProtectedThread(data,{sourceRequestId='',sourceOperationId='',propertyId='',entityRef='',title='',stage='availability_confirmed'}={}){
    const existing=(data.threads||[]).find(thread=>(sourceRequestId&&thread.sourceRequestId===sourceRequestId)||(sourceOperationId&&thread.sourceOperationId===sourceOperationId));
    if(existing)return existing;
    const createdAt=now();
    const thread={id:uid('ROOM'),sourceRequestId,sourceOperationId,propertyId,entityRef:entityRef||sourceRequestId||sourceOperationId||'EXP-N/D',title:title||protectedThreadTitle(propertyId),stage,createdAt,updatedAt:createdAt,messages:[{id:uid('MSG'),kind:'system',body:'Conversación protegida activada. Mantén toda la comunicación dentro de la plataforma y no compartas datos de contacto hasta que el flujo lo permita.',createdAt}]};
    data.threads.unshift(thread); return thread;
  }
  window.ensureProtectedThreadForRequest=function(request){
    if(!request?.id || String(request.status||'').includes('Pendiente'))return null;
    const data=getComm(); const before=data.threads.length;
    const thread=createProtectedThread(data,{sourceRequestId:request.id,propertyId:request.propertyId||'',entityRef:request.id,title:protectedThreadTitle(request.propertyId,'Conversación sobre solicitud aceptada'),stage:'availability_confirmed'});
    if(data.threads.length!==before)saveComm(data); return thread;
  };
  function syncProtectedThreadsFromDashboard(){
    if(typeof getPrivateDashboardState!=='function')return;
    const state=getPrivateDashboardState(); const data=getComm(); const before=data.threads.length;
    (state.requestsReceived||[]).filter(request=>request?.id&&!String(request.status||'').includes('Pendiente')).forEach(request=>createProtectedThread(data,{sourceRequestId:request.id,propertyId:request.propertyId||'',entityRef:request.id,title:protectedThreadTitle(request.propertyId,'Conversación sobre solicitud aceptada'),stage:'availability_confirmed'}));
    (state.operations||[]).filter(operation=>operation?.id&&!['Completada','Cancelada'].includes(operation.status)).forEach(operation=>createProtectedThread(data,{sourceOperationId:operation.id,propertyId:operation.propertyId||'',entityRef:operation.id,title:protectedThreadTitle(operation.propertyId,`Conversación de ${operation.id}`),stage:String(operation.status||'').includes('Acuerdo')?'nda_pending':'availability_confirmed'}));
    if(data.threads.length!==before)saveComm(data);
  }
  function addTrace(category,action,entity,detail,result='success'){
    const data=getComm();data.trace.unshift({id:uid('TR'),category,action,entity,detail,createdAt:now(),result});saveComm(data);return data
  }
  function channelBadge(delivery){const ok=delivery.status==='Entregada'||delivery.status==='Enviada';return `<span class="comm-channel-badge ${ok?'comm-channel-ok':'comm-channel-pending'}">${safeEsc(delivery.channel)} · ${safeEsc(delivery.status)}</span>`}
  function updateCommSidebar(){const d=getComm();const active=(d.subscriptions||[]).filter(x=>x.status==='active').length;const threads=(d.threads||[]).length;const a=document.getElementById('private-sidebar-subscriptions');if(a)a.textContent=active;const b=document.getElementById('private-sidebar-messages');if(b)b.textContent=threads}
  function renderCommStats(){const d=getComm();const el=document.getElementById('private-comm-stats');if(!el)return;const delivered=(d.events||[]).reduce((n,e)=>n+(e.deliveries||[]).filter(x=>x.status==='Entregada').length,0);el.innerHTML=[['Suscripciones activas',(d.subscriptions||[]).filter(x=>x.status==='active').length],['Salas protegidas',(d.threads||[]).length],['Avisos entregados',delivered],['Eventos auditados',(d.trace||[]).length]].map(([label,value])=>`<article class="comm-stat-card"><span class="block text-[10px] font-black uppercase tracking-wider text-slate-500">${safeEsc(label)}</span><strong class="block text-2xl font-black text-blue mt-1">${safeEsc(value)}</strong></article>`).join('')}
  function fillDemandSelect(){const el=document.getElementById('comm-demand-select');if(!el)return;const d=getComm();const subscribed=new Set((d.subscriptions||[]).map(x=>x.needId));const list=(window.needs||[]).filter(x=>!subscribed.has(x.id)).slice(0,80);el.innerHTML=list.map(x=>`<option value="${safeEsc(x.id)}">${safeEsc(x.title)} · ${safeEsc(x.postalCode||'S/C.P.')}</option>`).join('')||'<option value="">Todas las demandas visibles ya tienen suscripción</option>'}
  window.saveCommunicationPreferences=function(){const d=getComm();d.preferences={inApp:!!document.getElementById('comm-pref-inapp')?.checked,email:!!document.getElementById('comm-pref-email')?.checked,whatsapp:!!document.getElementById('comm-pref-whatsapp')?.checked,frequency:document.getElementById('comm-pref-frequency')?.value||'instant'};saveComm(d);persistWpRecord('user_preferences',d.preferences,{recordKey:'communication-preferences',title:'Preferencias de comunicación',status:'active'});addTrace('NOTIFICATION','PREFERENCES_UPDATED','PROFILE','El usuario actualizó sus canales operativos.');renderCommunicationModules();if(window.showToast)showToast('Preferencias de alertas actualizadas.','success')}
  window.subscribeSelectedDemand=function(){const id=document.getElementById('comm-demand-select')?.value;if(!id)return;const d=getComm();if(d.subscriptions.some(x=>x.needId===id))return;const pref=d.preferences||{};d.subscriptions.unshift({id:uid('SUB'),needId:id,channels:['platform',pref.email?'email':'',pref.whatsapp?'whatsapp':''].filter(Boolean),frequency:pref.frequency||'instant',threshold:70,status:'active',createdAt:now()});saveComm(d);addTrace('NOTIFICATION','DEMAND_SUBSCRIBED',id,'Se activaron alertas para la demanda.');renderCommunicationModules();if(window.showToast)showToast('Suscripción activada. Recibirás alertas de coincidencias.','success')}
  window.toggleDemandSubscription=function(id){const d=getComm();const s=d.subscriptions.find(x=>x.id===id);if(!s)return;s.status=s.status==='active'?'paused':'active';saveComm(d);addTrace('NOTIFICATION','SUBSCRIPTION_STATUS_CHANGED',id,`Estado de suscripción: ${s.status}`);renderCommunicationModules();if(window.showToast)showToast(s.status==='active'?'Suscripción reactivada.':'Suscripción pausada.','success')}
  window.removeDemandSubscription=function(id){const d=getComm();d.subscriptions=d.subscriptions.filter(x=>x.id!==id);saveComm(d);addTrace('NOTIFICATION','SUBSCRIPTION_REMOVED',id,'Suscripción retirada por el usuario.');renderCommunicationModules();if(window.showToast)showToast('Suscripción eliminada.','success')}
  function renderSubscriptions(){const d=getComm();const p=d.preferences||{};const set=(id,v)=>{const el=document.getElementById(id);if(el)el.checked=!!v};set('comm-pref-inapp',p.inApp);set('comm-pref-email',p.email);set('comm-pref-whatsapp',p.whatsapp);const f=document.getElementById('comm-pref-frequency');if(f)f.value=p.frequency||'instant';fillDemandSelect();const body=document.getElementById('comm-subscriptions-table');if(!body)return;body.innerHTML=(d.subscriptions||[]).map(s=>{const need=(window.needs||[]).find(x=>x.id===s.needId);const matches=need&&typeof getCompatiblePropertiesForNeed==='function'?getCompatiblePropertiesForNeed(need,10).length:0;return `<tr class="border-b border-slate-100"><td class="px-4 py-3"><strong class="block text-xs text-navy">${safeEsc(need?.title||s.needId)}</strong><span class="text-[10px] text-slate-500">${safeEsc(need?.id||s.needId)} · C.P. ${safeEsc(need?.postalCode||'N/D')}</span></td><td class="px-4 py-3"><span class="private-status-pill ${matches?'bg-green-light text-green':'bg-amber-light text-amber'}">${matches}</span></td><td class="px-4 py-3"><div class="flex flex-wrap gap-1">${(s.channels||[]).map(c=>`<span class="comm-channel-badge comm-channel-ok">${safeEsc(c)}</span>`).join('')}</div></td><td class="px-4 py-3">${safeEsc(s.frequency==='instant'?'Inmediata':s.frequency==='daily'?'Diaria':'Semanal')}</td><td class="px-4 py-3"><span class="private-status-pill ${s.status==='active'?'bg-green-light text-green':'bg-amber-light text-amber'}">${safeEsc(s.status==='active'?'Activa':'Pausada')}</span></td><td class="px-4 py-3"><div class="flex gap-2"><button onclick="toggleDemandSubscription('${safeEsc(s.id)}')" class="text-[10px] font-bold text-blue">${s.status==='active'?'Pausar':'Activar'}</button><button onclick="removeDemandSubscription('${safeEsc(s.id)}')" class="text-[10px] font-bold text-red-600">Eliminar</button></div></td></tr>`}).join('')||'<tr><td colspan="6" class="p-5 text-xs text-slate-500">No has configurado suscripciones todavía.</td></tr>'}
  function renderDeliveries(){const el=document.getElementById('comm-deliveries-list');if(!el)return;const d=getComm();el.innerHTML=(d.events||[]).map(e=>`<article class="private-mini-card"><div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3"><div><span class="text-[10px] font-black uppercase tracking-wider text-blue">${safeEsc(e.type)} · ${safeEsc(e.entityRef)}</span><p class="text-xs text-navy font-bold mt-1">${safeEsc(e.detail)}</p><span class="block text-[10px] text-slate-400 mt-1">${typeof formatRelativeTime==='function'?formatRelativeTime(e.createdAt):new Date(e.createdAt).toLocaleString('es-ES')}</span></div><div class="flex flex-wrap gap-1">${(e.deliveries||[]).map(channelBadge).join('')}</div></div></article>`).join('')}
  window.simulateProtectedMatchNotification=function(){const d=getComm();const n=firstNeed(),p=firstProp();const event={id:uid('EVT'),type:'Nueva coincidencia',entityRef:getNeedRef(n),detail:'Una nueva captación compatible requiere revisión dentro de la plataforma.',priority:'high',createdAt:now(),deliveries:[{channel:'Plataforma',status:'Entregada'}]};if(d.preferences.email)event.deliveries.push({channel:'Email',status:'Entregada'});if(d.preferences.whatsapp)event.deliveries.push({channel:'WhatsApp',status:'Entregada'});d.events.unshift(event);const dash=typeof getPrivateDashboardState==='function'?getPrivateDashboardState():null;if(dash){dash.notifications.unshift({id:uid('NOT'),category:'Oportunidades',title:'Nueva coincidencia protegida',detail:'Accede a Compra Captación para revisar la oportunidad sin exponer contactos.',createdAt:now(),read:false,target:'subscriptions'});dash.activities.unshift({id:uid('ACT'),icon:'✦',title:'Aviso multicanal generado',detail:'La plataforma notificó una coincidencia por los canales configurados.',createdAt:now()});persistPrivateDashboardState(dash)}saveComm(d);addTrace('MATCH','MATCH_DETECTED',event.id,'Coincidencia detectada y aviso multicanal generado.');renderDashboard();if(window.showToast)showToast('Coincidencia simulada: alertas operativas enviadas.','success')}
  function stageIndex(stage){const i=FLOW_STAGES.findIndex(x=>x.id===stage);return i<0?0:i}
  function renderThreads(){const el=document.getElementById('comm-threads-list');if(!el)return;syncProtectedThreadsFromDashboard();const d=getComm();el.innerHTML=(d.threads||[]).map(t=>{const step=FLOW_STAGES[stageIndex(t.stage)];return `<article class="comm-thread-card"><div class="flex items-start justify-between gap-3"><div><span class="text-[10px] font-black uppercase tracking-wider text-green">Sala protegida · ${safeEsc(t.entityRef)}</span><strong class="block text-sm text-navy mt-1">${safeEsc(t.title)}</strong><span class="block text-[10px] text-slate-500 mt-1">Contraparte: identidad protegida · ${safeEsc(step.label)}</span></div><span class="private-status-pill ${t.stage==='room_active'?'bg-green-light text-green':'bg-blue-light text-blue'}">${safeEsc(step.label)}</span></div><p class="text-[11px] text-slate-500 mt-3 leading-relaxed">Mensajes internos asociados al expediente. El contacto directo continúa oculto hasta completar el flujo configurado.</p><div class="flex flex-wrap gap-2 mt-4"><button onclick="openProtectedThread('${safeEsc(t.id)}')" class="px-3 py-2 rounded-lg bg-navy text-white text-[10px] font-bold">Abrir conversación</button></div></article>`}).join('')||'<div class="private-section-card p-6 lg:col-span-2"><strong class="block text-sm text-navy">Aún no tienes conversaciones activas</strong><p class="mt-2 text-xs text-slate-500">Aquí aparecerán cuando aceptes una solicitud o se inicie una operación. Así cada conversación queda vinculada a una colaboración real y protegida.</p></div>'}
  window.openProtectedThread=function(id){activeThreadId=id;const d=getComm();const t=d.threads.find(x=>x.id===id);if(!t)return;const title=document.getElementById('comm-thread-title');const sub=document.getElementById('comm-thread-subtitle');if(title)title.textContent=t.title;if(sub)sub.textContent=`${t.entityRef} · Mensajería protegida sin contacto directo`;renderThreadModal();document.getElementById('comm-thread-modal')?.classList.remove('hidden');addTrace('MESSAGE','THREAD_OPENED',id,'El usuario abrió la sala protegida.')}
  window.closeProtectedThread=function(){document.getElementById('comm-thread-modal')?.classList.add('hidden');activeThreadId=''}
  function renderThreadModal(){const d=getComm();const t=d.threads.find(x=>x.id===activeThreadId);if(!t)return;const msg=document.getElementById('comm-thread-messages');if(msg){msg.innerHTML=(t.messages||[]).map(m=>`<div class="comm-message ${m.kind==='system'?'comm-message-system':m.kind==='me'?'comm-message-me':'comm-message-other'}"><strong class="block text-[10px] mb-1 opacity-80">${m.kind==='system'?'Sistema Compra Captación':m.kind==='me'?'Tú':'Profesional verificado'}</strong>${safeEsc(m.body)}<span class="block text-[9px] mt-2 opacity-70">${new Date(m.createdAt).toLocaleString('es-ES')}</span></div>`).join('');msg.scrollTop=msg.scrollHeight}const idx=stageIndex(t.stage);const flow=document.getElementById('comm-thread-flow');if(flow)flow.innerHTML=FLOW_STAGES.map((s,i)=>`<div class="comm-flow-step ${i<idx?'done':i===idx?'current':''}">${safeEsc(s.label)}</div>`).join('');const btn=document.getElementById('comm-thread-progress-btn');if(btn){btn.textContent=FLOW_STAGES[idx].button;btn.disabled=t.stage==='room_active';btn.classList.toggle('opacity-50',btn.disabled)}}
  function containsContact(body){return /([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})|(https?:\/\/|www\.)|(\+?\d[\d\s().-]{7,}\d)/i.test(body)}
  window.sendProtectedThreadMessage=function(){const input=document.getElementById('comm-thread-input');const body=(input?.value||'').trim();if(!body)return;if(containsContact(body)){addTrace('SECURITY','CONTACT_SHARE_BLOCKED',activeThreadId,'Se bloqueó un intento de compartir teléfono, email o URL.','blocked');if(window.showToast)showToast('Mensaje bloqueado: no compartas teléfonos, emails ni enlaces externos antes del desbloqueo.','error');return}const d=getComm();const t=d.threads.find(x=>x.id===activeThreadId);if(!t)return;t.messages.push({id:uid('MSG'),kind:'me',body,createdAt:now()});t.updatedAt=now();saveComm(d);addTrace('MESSAGE','MESSAGE_SENT',t.id,'Mensaje interno enviado dentro de la sala protegida.');if(input)input.value='';renderThreadModal();renderThreads();if(window.showToast)showToast('Mensaje enviado dentro de la sala protegida.','success')}
window.advanceProtectedFlow=function(){const d=getComm();const t=d.threads.find(x=>x.id===activeThreadId);if(!t)return;const idx=stageIndex(t.stage);if(idx>=FLOW_STAGES.length-1)return;const next=FLOW_STAGES[idx+1];t.stage=next.id;t.updatedAt=now();t.messages.push({id:uid('MSG'),kind:'system',body:`Flujo actualizado: ${next.label}.`,createdAt:now()});const ev={id:uid('EVT'),type:'Cambio de estado',entityRef:t.entityRef,detail:`La sala protegida avanzó a: ${next.label}.`,priority:'medium',createdAt:now(),deliveries:[{channel:'Plataforma',status:'Entregada'}]};if(d.preferences.email)ev.deliveries.push({channel:'Email',status:'Entregada'});if(d.preferences.whatsapp)ev.deliveries.push({channel:'WhatsApp',status:'Entregada'});d.events.unshift(ev);saveComm(d);addTrace('FLOW','FLOW_STAGE_CHANGED',t.id,`Nuevo estado: ${next.label}.`);renderThreadModal();renderCommunicationModules();if(window.showToast)showToast(`Flujo actualizado: ${next.label}.`,'success')}
  function renderTrace(){const el=document.getElementById('comm-trace-list');if(!el)return;const d=getComm();const filter=document.getElementById('comm-trace-filter')?.value||'';const list=(d.trace||[]).filter(x=>!filter||x.category===filter);el.innerHTML=list.map(x=>`<article class="comm-trace-line"><div class="flex flex-wrap items-center gap-2"><span class="text-[9px] font-black uppercase tracking-wider text-blue">${safeEsc(x.category)}</span><span class="text-[9px] text-slate-400">${new Date(x.createdAt).toLocaleString('es-ES')}</span><span class="private-status-pill ${x.result==='blocked'?'bg-red-50 text-red-600':'bg-green-light text-green'}">${safeEsc(x.result)}</span></div><strong class="block text-xs text-navy mt-1">${safeEsc(x.action)} · ${safeEsc(x.entity)}</strong><p class="text-[11px] text-slate-500 mt-1 leading-relaxed">${safeEsc(x.detail)}</p></article>`).join('')||'<p class="text-xs text-slate-500">No existen eventos para ese filtro.</p>'}
  window.renderCommunicationTrace=renderTrace;
  window.exportCommunicationTrace=function(){const d=getComm();const payload={exportedAt:new Date().toISOString(),notice:'Exportación operativa de trazabilidad. El registro debe generarse desde backend con firma y controles de acceso.',trace:d.trace,events:d.events,threads:d.threads.map(t=>({id:t.id,entityRef:t.entityRef,stage:t.stage,updatedAt:t.updatedAt,messageCount:(t.messages||[]).length}))};const blob=new Blob([JSON.stringify(payload,null,2)],{type:'application/json'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=`captacion-app-trazabilidad-${Date.now()}.json`;a.click();URL.revokeObjectURL(url);addTrace('FLOW','TRACE_EXPORTED','AUDIT','El usuario exportó un registro operativo de trazabilidad.');renderTrace()}
  window.closeProtectedThread=function(){document.getElementById('comm-thread-modal')?.classList.add('hidden');activeThreadId=''}
  function renderThreadModal(){const d=getComm();const t=d.threads.find(x=>x.id===activeThreadId);if(!t)return;const msg=document.getElementById('comm-thread-messages');if(msg){msg.innerHTML=(t.messages||[]).map(m=>`<div class="comm-message ${m.kind==='system'?'comm-message-system':m.kind==='me'?'comm-message-me':'comm-message-other'}"><strong class="block text-[10px] mb-1 opacity-80">${m.kind==='system'?'Sistema Compra Captación':m.kind==='me'?'Tú':'Profesional verificado'}</strong>${safeEsc(m.body)}<span class="block text-[9px] mt-2 opacity-70">${new Date(m.createdAt).toLocaleString('es-ES')}</span></div>`).join('');msg.scrollTop=msg.scrollHeight}const idx=stageIndex(t.stage);const flow=document.getElementById('comm-thread-flow');if(flow)flow.innerHTML=FLOW_STAGES.map((s,i)=>`<div class="comm-flow-step ${i<idx?'done':i===idx?'current':''}">${safeEsc(s.label)}</div>`).join('');const btn=document.getElementById('comm-thread-progress-btn');if(btn){btn.textContent=FLOW_STAGES[idx].button;btn.disabled=t.stage==='room_active';btn.classList.toggle('opacity-50',btn.disabled)}}
  function containsContact(body){return /([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})|(https?:\/\/|www\.)|(\+?\d[\d\s().-]{7,}\d)/i.test(body)}
  window.sendProtectedThreadMessage=function(){const input=document.getElementById('comm-thread-input');const body=(input?.value||'').trim();if(!body)return;if(containsContact(body)){addTrace('SECURITY','CONTACT_SHARE_BLOCKED',activeThreadId,'Se bloqueó un intento de compartir teléfono, email o URL.','blocked');if(window.showToast)showToast('Mensaje bloqueado: no compartas teléfonos, emails ni enlaces externos antes del desbloqueo.','error');return}const d=getComm();const t=d.threads.find(x=>x.id===activeThreadId);if(!t)return;t.messages.push({id:uid('MSG'),kind:'me',body,createdAt:now()});t.updatedAt=now();saveComm(d);addTrace('MESSAGE','MESSAGE_SENT',t.id,'Mensaje interno enviado dentro de la sala protegida.');if(input)input.value='';renderThreadModal();renderThreads();if(window.showToast)showToast('Mensaje enviado dentro de la sala protegida.','success')}
window.advanceProtectedFlow=function(){const d=getComm();const t=d.threads.find(x=>x.id===activeThreadId);if(!t)return;const idx=stageIndex(t.stage);if(idx>=FLOW_STAGES.length-1)return;const next=FLOW_STAGES[idx+1];t.stage=next.id;t.updatedAt=now();t.messages.push({id:uid('MSG'),kind:'system',body:`Flujo actualizado: ${next.label}.`,createdAt:now()});const ev={id:uid('EVT'),type:'Cambio de estado',entityRef:t.entityRef,detail:`La sala protegida avanzó a: ${next.label}.`,priority:'medium',createdAt:now(),deliveries:[{channel:'Plataforma',status:'Entregada'}]};if(d.preferences.email)ev.deliveries.push({channel:'Email',status:'Entregada'});if(d.preferences.whatsapp)ev.deliveries.push({channel:'WhatsApp',status:'Entregada'});d.events.unshift(ev);saveComm(d);addTrace('FLOW','FLOW_STAGE_CHANGED',t.id,`Nuevo estado: ${next.label}.`);renderThreadModal();renderCommunicationModules();if(window.showToast)showToast(`Flujo actualizado: ${next.label}.`,'success')}
  function renderTrace(){const el=document.getElementById('comm-trace-list');if(!el)return;const d=getComm();const filter=document.getElementById('comm-trace-filter')?.value||'';const list=(d.trace||[]).filter(x=>!filter||x.category===filter);el.innerHTML=list.map(x=>`<article class="comm-trace-line"><div class="flex flex-wrap items-center gap-2"><span class="text-[9px] font-black uppercase tracking-wider text-blue">${safeEsc(x.category)}</span><span class="text-[9px] text-slate-400">${new Date(x.createdAt).toLocaleString('es-ES')}</span><span class="private-status-pill ${x.result==='blocked'?'bg-red-50 text-red-600':'bg-green-light text-green'}">${safeEsc(x.result)}</span></div><strong class="block text-xs text-navy mt-1">${safeEsc(x.action)} · ${safeEsc(x.entity)}</strong><p class="text-[11px] text-slate-500 mt-1 leading-relaxed">${safeEsc(x.detail)}</p></article>`).join('')||'<p class="text-xs text-slate-500">No existen eventos para ese filtro.</p>'}
  window.renderCommunicationTrace=renderTrace;
  window.exportCommunicationTrace=function(){const d=getComm();const payload={exportedAt:new Date().toISOString(),notice:'Exportación operativa de trazabilidad. El registro debe generarse desde backend con firma y controles de acceso.',trace:d.trace,events:d.events,threads:d.threads.map(t=>({id:t.id,entityRef:t.entityRef,stage:t.stage,updatedAt:t.updatedAt,messageCount:(t.messages||[]).length}))};const blob=new Blob([JSON.stringify(payload,null,2)],{type:'application/json'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=`captacion-app-trazabilidad-${Date.now()}.json`;a.click();URL.revokeObjectURL(url);addTrace('FLOW','TRACE_EXPORTED','AUDIT','El usuario exportó un registro operativo de trazabilidad.');renderTrace()}
  function appendCommunicationOverview(){const fav=document.getElementById('private-overview-favorites')?.closest('section');if(!fav||document.getElementById('private-overview-communications'))return;const box=document.createElement('section');box.id='private-overview-communications';box.className='private-section-card overflow-hidden mb-6';box.innerHTML=`<div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3"><div><h4 class="text-sm font-black text-navy">Centro de comunicación protegida</h4><p class="text-[11px] text-slate-500 mt-1">Suscripciones, avisos multicanal y salas internas con trazabilidad.</p></div><div class="flex gap-2"><button onclick="switchPrivateDashboardPanel('subscriptions')" class="px-3 py-2 rounded-lg bg-blue text-white text-[10px] font-bold">Gestionar alertas</button><button onclick="switchPrivateDashboardPanel('communications')" class="px-3 py-2 rounded-lg border border-slate-200 text-navy text-[10px] font-bold">Abrir salas</button></div></div><div id="private-overview-comm-stats" class="grid grid-cols-2 lg:grid-cols-4 gap-3 p-4"></div>`;fav.parentNode.insertBefore(box,fav);renderOverviewCommStats()}
  function renderOverviewCommStats(){const el=document.getElementById('private-overview-comm-stats');if(!el)return;const d=getComm();el.innerHTML=[['Suscripciones activas',d.subscriptions.filter(x=>x.status==='active').length],['Salas privadas',d.threads.length],['Avisos multicanal',d.events.length],['Eventos auditados',d.trace.length]].map(([a,b])=>`<div class="private-mini-card"><span class="block text-[10px] uppercase tracking-wider text-slate-500 font-black">${safeEsc(a)}</span><strong class="block text-xl text-blue mt-1">${safeEsc(b)}</strong></div>`).join('')}
  window.renderCommunicationModules=function(){updateCommSidebar();renderCommStats();renderSubscriptions();renderDeliveries();renderThreads();renderTrace();renderOverviewCommStats()}
  window.openCommunicationForDemand=function(needId){const d=getComm();const exists=d.subscriptions.find(x=>x.needId===needId);if(!exists){const p=d.preferences||{};d.subscriptions.unshift({id:uid('SUB'),needId,channels:['platform',p.email?'email':'',p.whatsapp?'whatsapp':''].filter(Boolean),frequency:p.frequency||'instant',threshold:70,status:'active',createdAt:now()});saveComm(d);addTrace('NOTIFICATION','DEMAND_SUBSCRIBED',needId,'Suscripción creada desde la tabla de demandas.')}switchPrivateDashboardPanel('subscriptions');renderCommunicationModules()}
  const baseRenderPrivateDemands=window.renderPrivateDemands;
  if(typeof baseRenderPrivateDemands==='function')window.renderPrivateDemands=function(){baseRenderPrivateDemands();document.querySelectorAll('#private-demands-table tr').forEach(row=>{const btn=row.querySelector("button[onclick^=\"openHomeNeedMatches\"]");if(!btn)return;const match=btn.getAttribute('onclick').match(/'([^']+)'/);const id=match?.[1];if(!id||row.querySelector('[data-comm-subscribe]'))return;const b=document.createElement('button');b.dataset.commSubscribe='1';b.className='block mt-1 text-[10px] font-bold text-green';b.textContent='Gestionar alertas';b.onclick=()=>openCommunicationForDemand(id);btn.parentNode.appendChild(b)})}
  const baseDashboard=window.renderDashboard;
  if(typeof baseDashboard==='function')window.renderDashboard=function(){baseDashboard();appendCommunicationOverview();renderCommunicationModules()}
  const baseConfirm=window.confirmPrivateRequest;
  if(typeof baseConfirm==='function')window.confirmPrivateRequest=function(id){baseConfirm(id);const state=typeof getPrivateDashboardState==='function'?getPrivateDashboardState():null;window.ensureProtectedThreadForRequest?.((state?.requestsReceived||[]).find(item=>item.id===id));addTrace('FLOW','AVAILABILITY_CONFIRMED',id,'Disponibilidad confirmada desde solicitudes. Se mantiene el contacto protegido.');renderCommunicationModules()}
  document.addEventListener('DOMContentLoaded',()=>{appendCommunicationOverview();renderCommunicationModules()});
})();
</script>

<!-- ========================================== -->
<!-- ASISTENTE IA VERA - FLOTANTE -->
<!-- ========================================== -->
<button type="button" id="vera-widget-launcher" onclick="toggleVeraChat(event)" aria-label="Abrir asistente de IA Vera" aria-expanded="false" aria-controls="vera-chat-window">
  <img class="vera-icon" src="<?php echo esc_url($captacion_vera_image_url); ?>" alt="" width="1600" height="1600" loading="lazy" decoding="async">
  <div class="vera-pulse"></div>
</button>

<div id="vera-chat-window">
  <div class="vera-chat-header">
    <div class="vera-header-info">
      <div class="vera-avatar"><img src="<?php echo esc_url($captacion_vera_image_url); ?>" alt="" width="1600" height="1600" loading="lazy" decoding="async"></div>
      <div class="vera-header-title">
        <span class="vera-header-name">Vera</span>
        <span class="vera-header-status">Analista IA en línea</span>
      </div>
    </div>
    <button onclick="toggleVeraChat()" class="vera-close-btn" aria-label="Cerrar chat">✕</button>
  </div>
  
  <div id="vera-chat-messages" class="vera-chat-messages">
    <!-- El mensaje de bienvenida se inyectará dinámicamente según sesión -->
  </div>

  <div id="vera-chat-footer">
    <!-- Se inyecta dinámicamente según estado de sesión -->
  </div>
</div>

<style>
/* Importaciones XML: información esencial primero y acciones agrupadas. */
.xml-feed-card { gap: 1rem !important; }
.xml-feed-card__source { flex: 1 1 280px; }
.xml-feed-card__actions { flex: 0 1 720px; justify-content: flex-end; }
.xml-feed-card__actions > div { min-width: 76px; }
@media (max-width: 1279px) {
  .xml-feed-card__actions { justify-content: flex-start; }
}
/* CSS del widget flotante de Vera */
#vera-widget-launcher {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 56px;
  height: 56px;
  border-radius: 9999px;
  background: linear-gradient(135deg, #0052ec, #002ca7);
  box-shadow: 0 8px 24px rgba(0, 82, 236, 0.3), inset 0 2px 4px rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 99999;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(255, 255, 255, 0.1);
}
#vera-widget-launcher:hover {
  transform: translateY(-4px) scale(1.05);
  box-shadow: 0 12px 30px rgba(0, 82, 236, 0.45), inset 0 2px 4px rgba(255, 255, 255, 0.3);
}
#vera-widget-launcher .vera-icon {
  width: 100%;
  height: 100%;
  border-radius: inherit;
  object-fit: cover;
  object-position: 50% 28%;
  user-select: none;
}
#vera-widget-launcher .vera-pulse {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  border-radius: 9999px;
  background-color: #10b981;
  border: 2px solid #ffffff;
}
#vera-widget-launcher .vera-pulse::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  width: 12px;
  height: 12px;
  border-radius: 9999px;
  border: 2px solid #10b981;
  animation: vera-ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
  opacity: 0.8;
}
@keyframes vera-ping {
  75%, 100% {
    transform: scale(2.2);
    opacity: 0;
  }
}

#vera-chat-window {
  position: fixed;
  bottom: 96px;
  right: 24px;
  width: 380px;
  max-width: calc(100vw - 48px);
  height: 520px;
  max-height: calc(100vh - 140px);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
  display: flex;
  flex-direction: column;
  z-index: 99998;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  transform: translateY(20px) scale(0.95);
  opacity: 0;
  pointer-events: none;
}
#vera-chat-window.is-active {
  transform: translateY(0) scale(1);
  opacity: 1;
  pointer-events: auto;
}
@media (max-width: 767px) {
  #vera-widget-launcher { right: 16px; bottom: 88px; }
  #vera-chat-window { right: 12px; bottom: 156px; max-width: calc(100vw - 24px); max-height: calc(100vh - 180px); }
}

.vera-chat-header {
  padding: 16px 20px;
  background: linear-gradient(135deg, #10233c, #1a365d);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.vera-header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.vera-avatar {
  width: 38px;
  height: 38px;
  border-radius: 9999px;
  background: linear-gradient(135deg, #0052ec, #0037a5);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  color: #ffffff;
  font-size: 18px;
  box-shadow: 0 4px 10px rgba(0, 82, 236, 0.3);
  border: 2px solid rgba(255, 255, 255, 0.2);
  overflow: hidden;
  flex: 0 0 auto;
}
.vera-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: 50% 28%;
}
.vera-header-title {
  display: flex;
  flex-direction: column;
}
.vera-header-name {
  font-size: 14px;
  font-weight: 800;
  letter-spacing: 0.5px;
}
.vera-header-status {
  font-size: 10px;
  color: #10b981;
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 600;
}
.vera-header-status::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 9999px;
  background-color: #10b981;
}
.vera-close-btn {
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  font-size: 20px;
  cursor: pointer;
  padding: 4px;
  line-height: 1;
  transition: color 0.2s;
}
.vera-close-btn:hover {
  color: #ffffff;
}

.vera-chat-messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background-color: #f8fafc;
}
.vera-msg {
  max-width: 85%;
  padding: 12px 16px;
  border-radius: 18px;
  font-size: 13px;
  line-height: 1.5;
}
.vera-msg-assistant {
  align-self: flex-start;
  background-color: #ffffff;
  color: #1e293b;
  border: 1px solid #e2e8f0;
  border-top-left-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}
.vera-msg-user {
  align-self: flex-end;
  background: linear-gradient(135deg, #0052ec, #003fae);
  color: #ffffff;
  border-top-right-radius: 4px;
  box-shadow: 0 4px 10px rgba(0, 82, 236, 0.15);
}
.vera-quick-action {
  display: block;
  width: 100%;
  min-height: 36px;
  text-align: left;
  font-weight: 800 !important;
  line-height: 1.25 !important;
}
.vera-quick-action--primary {
  background: #1b67d6 !important;
  color: #ffffff !important;
  border-color: #1b67d6 !important;
}
.vera-quick-action--secondary {
  background: #ffffff !important;
  color: #1554b3 !important;
  border-color: #1b67d6 !important;
}
.vera-quick-action--neutral {
  background: #ffffff !important;
  color: #10233c !important;
  border-color: #64748b !important;
}
.vera-quick-action:hover,
.vera-quick-action:focus-visible {
  filter: brightness(.96);
  outline: 2px solid #f6c668;
  outline-offset: 1px;
}

.vera-chat-input-area {
  padding: 16px 20px;
  background-color: #ffffff;
  border-top: 1px solid #f1f5f9;
  display: flex;
  gap: 8px;
  align-items: center;
}
.vera-input-field {
  flex: 1;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 13px;
  resize: none;
  height: 38px;
  max-height: 80px;
  line-height: 1.4;
  outline: none;
  transition: all 0.2s;
}
.vera-input-field:focus {
  border-color: #0052ec;
  box-shadow: 0 0 0 3px rgba(0, 82, 236, 0.15);
}
.vera-send-btn {
  background: linear-gradient(135deg, #0052ec, #003fae);
  border: none;
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  color: #ffffff;
}
.vera-send-btn:hover {
  transform: scale(1.03);
}
.vera-send-btn:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}

.vera-lock-container {
  padding: 20px;
  background-color: #ffffff;
  border-top: 1px solid #f1f5f9;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.vera-lock-title {
  font-size: 13px;
  font-weight: 800;
  color: #0f172a;
}
.vera-register-redirect-btn {
  background: linear-gradient(135deg, #0052ec, #003fae);
  color: #ffffff;
  padding: 10px 16px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 800;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(0, 82, 236, 0.2);
}
.vera-register-redirect-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 82, 236, 0.35);
}

/* Experiencia móvil tipo app: Barra inferior y Vera no intrusiva */
@media (max-width: 1023px) {
  body {
    padding-bottom: 74px !important;
  }
  .safe-area-bottom {
    padding-bottom: max(6px, env(safe-area-inset-bottom));
  }
  #vera-widget-launcher { right: 14px; bottom: 84px !important; width: 48px; height: 48px; }
  #vera-chat-window {
    right: 14px;
    bottom: 144px !important;
    width: min(340px, calc(100vw - 28px));
    height: min(420px, calc(100vh - 200px));
    max-height: calc(100vh - 200px);
    border-radius: 18px;
  }
  .vera-chat-header { padding: 11px 14px; }
  .vera-avatar { width: 32px; height: 32px; }
  .vera-header-name { font-size: 13px; }
  .vera-header-status { font-size: 9px; }
  .vera-chat-messages { padding: 12px; gap: 10px; }
  .vera-msg { padding: 9px 11px; font-size: 12px; line-height: 1.4; }
  .vera-chat-input-area { padding: 10px 12px; }
}
html[data-theme="dark"] #page-marketplace details,
html[data-theme="dark"] .captacion-advanced-filters {
  background: #12263c !important;
  border-color: #3f5873 !important;
}
html[data-theme="dark"] #page-marketplace details > summary,
html[data-theme="dark"] .captacion-advanced-filters > summary {
  color: #9fd0ff !important;
}
.marketplace-load-more-control { width: 100%; padding: 1.5rem 0 .5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.marketplace-pagination { display: inline-flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .35rem; padding: .35rem; border-radius: 1rem; }
.marketplace-page-button { min-width: 2.4rem; height: 2.4rem; padding: 0 .65rem; border-radius: .65rem; border: 1px solid #cbd5e1; background: #ffffff; color: #1e293b; font-size: .8rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; transition: all .15s ease; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.marketplace-page-button:hover:not(:disabled) { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
.marketplace-page-button.is-active { border-color: #2563eb; background: #2563eb !important; color: #ffffff !important; box-shadow: 0 4px 10px rgba(37,99,235,.25); }
.marketplace-load-more-status { color: #64748b; font-size: .75rem; font-weight: 600; margin-top: .5rem; }
html[data-theme="dark"] .marketplace-page-button { background: #1e293b; border-color: #334155; color: #f8fafc; }
html[data-theme="dark"] .marketplace-page-button:hover:not(:disabled) { background: #1e3a8a; border-color: #60a5fa; color: #93c5fd; }
html[data-theme="dark"] .marketplace-page-button.is-active { background: #2563eb !important; border-color: #3b82f6; color: #ffffff !important; }
html[data-theme="dark"] .marketplace-load-more-status { color: #94a3b8 !important; }

/* Refuerzo del hero principal: se imprime después de los estilos utilitarios para que la composición sea determinista. */
.home-hero-wow { background: #06132b !important; padding: 68px 0 34px !important; color: #fff !important; }
.home-hero-wow > div.relative.z-10 > div.grid.grid-cols-1:first-child { display: grid !important; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr) !important; align-items: center !important; gap: 64px !important; }
.home-hero-wow__layout > div { grid-column: auto !important; grid-row: auto !important; }
.home-hero-wow__title { max-width: 650px !important; font-size: clamp(3.2rem, 5vw, 5.6rem) !important; line-height: .98 !important; letter-spacing: -.055em !important; }
.home-hero-wow__lead { max-width: 560px !important; margin-top: 24px !important; color: #c4d0df !important; font-size: 1.12rem !important; line-height: 1.65 !important; }
.home-hero-wow__primary, .home-hero-wow__secondary { min-height: 58px !important; border-radius: 14px !important; padding: 0 22px !important; box-shadow: 0 14px 28px rgba(2,8,23,.3) !important; }
.home-hero-wow__primary { background: #2777e8 !important; }
.home-hero-wow__secondary { background: #17a477 !important; }
.home-hero-wow__media #home-featured-card { margin: 0 !important; }
.home-hero-wow__media #home-featured-card > div { border: 1px solid rgba(157,205,230,.34) !important; border-radius: 28px !important; background: #0d213c !important; box-shadow: 0 30px 70px rgba(0,0,0,.38) !important; }
.home-hero-wow__video { min-height: 430px !important; aspect-ratio: 1.46 !important; border-radius: 27px !important; }
.home-hero-wow__video video, .home-hero-wow__video img { object-position: center !important; }
.home-hero-wow__video::after { content: ""; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(180deg, transparent 35%, rgba(3,13,31,.88) 100%); z-index: 1; }
.home-hero-wow__video > div { z-index: 2 !important; }
.home-hero-wow__trust { margin-top: 42px !important; border: 1px solid rgba(148,190,220,.34) !important; border-radius: 16px !important; background: rgba(13,35,62,.92) !important; padding: 18px 22px !important; }
.home-hero-wow__trust > div { display: grid !important; grid-template-columns: repeat(3,minmax(0,1fr)) auto !important; align-items: center !important; gap: 0 !important; }
.home-hero-wow__trust > div > div { min-height: 64px !important; padding: 8px 22px !important; }
.home-hero-wow__trust > div > div + div { border-left: 1px solid rgba(148,190,220,.18) !important; }
.home-hero-wow__trust > div > a { min-height: 54px !important; margin-left: 22px !important; border-radius: 12px !important; background: #2777e8 !important; padding: 0 22px !important; white-space: nowrap !important; }
.home-hero-wow__how-link { color: #f4c95d !important; font-size: 17px !important; font-weight: 500 !important; line-height: 1.4 !important; }
.home-hero-wow__gradient-text { background-image: linear-gradient(100deg,#2f8cff 0%,#22b7d8 46%,#55d6a6 100%) !important; background-clip: text !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; }
.home-hero-wow__trust strong { font-size: 16px !important; line-height: 1.25 !important; }
.home-hero-wow__trust span { font-size: 14px !important; line-height: 1.45 !important; }
.home-kpi-section .home-kpi-card { min-height: 132px; }
.home-kpi-section .home-kpi-card > span { font-size: 12px !important; letter-spacing: .13em !important; }
.home-kpi-section .home-kpi-card strong { font-size: 38px !important; line-height: 1 !important; }
.home-kpi-section .home-kpi-card small,
.home-kpi-section .home-kpi-card span { line-height: 1.45 !important; }
.home-kpi-section .metric-action-link { font-size: 12px !important; line-height: 1.3 !important; }
.home-collab-wow { position: relative; overflow: hidden; padding: 104px 0 120px !important; background: radial-gradient(circle at 88% 48%,rgba(17,92,166,.24),transparent 38%), linear-gradient(118deg,#020b22 0%,#031332 58%,#061b3c 100%) !important; color:#fff; }
.home-collab-wow__layout { width: min(1480px,calc(100% - 96px)); margin:0 auto; display:grid !important; grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important; gap:84px !important; align-items:center !important; }
.home-collab-wow__layout > div { grid-column:auto !important; grid-row:auto !important; }
.home-collab-wow__intro { padding-left:6px; }
.home-collab-wow__rule { display:block; width:62px; height:4px; margin-bottom:22px; border-radius:99px; background:#25e0b2; box-shadow:0 0 18px rgba(37,224,178,.6); }
.home-collab-wow__eyebrow { display:block; color:#25e0b2; font-size:18px; line-height:1; font-weight:600; text-transform:uppercase; letter-spacing:.15em; }
.home-collab-wow__intro h2 { max-width:720px; margin:27px 0 0; color:#f8fbff !important; font-size:clamp(2.8rem,4.25vw,5.25rem) !important; line-height:1.04 !important; letter-spacing:-.045em; font-weight:800 !important; }
.home-collab-wow__intro > p { max-width:680px; margin:28px 0 0; color:#b9c8df !important; font-size:21px !important; line-height:1.48 !important; }
.home-collab-wow__cta { display:inline-flex; align-items:center; justify-content:center; gap:34px; min-width:382px; min-height:78px; margin-top:34px; padding:0 34px; border:1px solid #2a9bff; border-radius:15px; background:linear-gradient(135deg,#1878ff,#0b5fe3) !important; color:#fff !important; font-size:22px; font-weight:800; box-shadow:0 14px 34px rgba(17,107,244,.36),inset 0 1px 0 rgba(255,255,255,.25); transition:transform .25s ease,box-shadow .25s ease; }
.home-collab-wow__cta span { font-size:34px; line-height:1; font-weight:400; }
.home-collab-wow__cta:hover { transform:translateY(-3px); box-shadow:0 18px 42px rgba(17,107,244,.48),inset 0 1px 0 rgba(255,255,255,.3); }
.home-collab-wow__benefits { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); margin-top:48px; padding:29px 22px 27px; border:1px solid rgba(56,167,255,.7); border-radius:16px; background:linear-gradient(135deg,rgba(15,52,100,.7),rgba(4,28,63,.75)); box-shadow:0 0 25px rgba(19,121,236,.12); }
.home-collab-wow__benefit { min-width:0; padding:0 18px; text-align:center; }
.home-collab-wow__benefit + .home-collab-wow__benefit { border-left:1px solid rgba(89,160,235,.42); }
.home-collab-wow__benefit-icon { display:flex; align-items:center; justify-content:center; width:70px; height:70px; margin:0 auto 18px; border-radius:50%; border:1px solid currentColor; background:rgba(2,19,52,.75); box-shadow:0 0 24px currentColor; }
.home-collab-wow__benefit-icon svg { width:45px; height:45px; }
.home-collab-wow__benefit-icon--green { color:#36e4bf; }.home-collab-wow__benefit-icon--blue { color:#2a9bff; }.home-collab-wow__benefit-icon--amber { color:#e7ae28; }
.home-collab-wow__benefit strong { display:block; color:#f5f8ff; font-size:17px; line-height:1.3; font-weight:800; }
.home-collab-wow__benefit span:last-child { display:block; margin-top:8px; color:#a9bbd5; font-size:15px; line-height:1.42; }
.home-collab-wow__steps { position:relative; display:grid; gap:30px; padding-left:0; }
.home-collab-wow__step-line { position:absolute; left:0; top:74px; bottom:74px; width:3px; background:linear-gradient(#2c8cff 0%,#20e0b0 52%,#2b8dff 100%); box-shadow:0 0 13px rgba(33,221,190,.55); }
.home-collab-wow__step { position:relative; display:grid; grid-template-columns:minmax(0,1fr) 142px; align-items:center; min-height:212px; margin-left:0; padding:28px 26px 28px 82px; border:1px solid rgba(57,145,244,.85); border-radius:17px; background:linear-gradient(100deg,rgba(11,39,84,.9),rgba(7,31,70,.76)); box-shadow:0 12px 32px rgba(0,0,0,.16),inset 0 1px 0 rgba(255,255,255,.08); }
.home-collab-wow__step-number { position:absolute; left:-45px; top:50%; display:flex; align-items:center; justify-content:center; width:90px; height:90px; transform:translateY(-50%); border:2px solid #2487ff; border-radius:50%; background:radial-gradient(circle at 35% 25%,#123c72,#06172f 72%); color:#1fe2b0; font-size:31px; line-height:1; font-weight:800; box-shadow:0 0 22px rgba(28,126,255,.4); z-index:2; }
.home-collab-wow__step h3 { margin:0 !important; color:#f7faff !important; font-size:27px !important; line-height:1.16 !important; font-weight:800 !important; }
.home-collab-wow__step p { margin:16px 0 0 !important; color:#b9c8df !important; font-size:18px !important; line-height:1.5 !important; }
.home-collab-wow__step-icon { display:flex; align-items:center; justify-content:center; justify-self:end; width:128px; height:128px; opacity:.95; }
.home-collab-wow__step-icon svg { width:100%; height:100%; filter:drop-shadow(0 0 16px rgba(31,138,255,.25)); }
.home-collab-wow [data-scroll-animate] { opacity: 0; transform: translateY(22px); transition: opacity .7s ease, transform .7s cubic-bezier(.2,.75,.25,1); transition-delay: calc(var(--scroll-delay, 0) * 1ms); }
.home-collab-wow [data-scroll-animate].is-visible { opacity: 1; transform: translateY(0); }
@media (prefers-reduced-motion: reduce) {
  .home-collab-wow [data-scroll-animate] { opacity: 1 !important; transform: none !important; transition: none !important; }
}
html:not([data-theme="dark"]) .home-hero-wow { background: linear-gradient(135deg,#edf6ff 0%,#f8fbff 55%,#eefaf5 100%) !important; color: #10233c !important; }
html:not([data-theme="dark"]) .home-hero-wow__title { color: #10233c !important; }
html:not([data-theme="dark"]) .home-hero-wow__lead { color: #506176 !important; }
html:not([data-theme="dark"]) .home-hero-wow__how-link { color: #a66c08 !important; }
html:not([data-theme="dark"]) .home-hero-wow__trust { background: rgba(255,255,255,.92) !important; border-color: #cbd9e8 !important; }
html:not([data-theme="dark"]) .home-hero-wow__trust strong { color: #10233c !important; }
html:not([data-theme="dark"]) .home-hero-wow__trust span { color: #52657a !important; }
html:not([data-theme="dark"]) .home-kpi-section .home-kpi-card { background: #fff !important; border-color: #dbe5ef !important; }
html:not([data-theme="dark"]) .home-collab-wow { background: radial-gradient(circle at 88% 48%,rgba(71,153,220,.2),transparent 38%),linear-gradient(118deg,#eef7ff 0%,#f9fcff 58%,#effbf6 100%) !important; color:#10233c !important; }
html:not([data-theme="dark"]) .home-collab-wow__intro h2,
html:not([data-theme="dark"]) .home-collab-wow__step h3,
html:not([data-theme="dark"]) .home-collab-wow__benefit strong { color:#10233c !important; }
html:not([data-theme="dark"]) .home-collab-wow__intro > p,
html:not([data-theme="dark"]) .home-collab-wow__step p { color:#52657a !important; }
html:not([data-theme="dark"]) .home-collab-wow__benefits,
html:not([data-theme="dark"]) .home-collab-wow__step { background:rgba(255,255,255,.78) !important; border-color:#86bde8 !important; box-shadow:0 12px 32px rgba(31,91,145,.1),inset 0 1px 0 rgba(255,255,255,.9); }
html:not([data-theme="dark"]) .home-collab-wow__benefit span:last-child { color:#52657a !important; }
html:not([data-theme="dark"]) .home-collab-wow__step-number { background:radial-gradient(circle at 35% 25%,#eff8ff,#dceeff 72%); color:#008b6e; }
html:not([data-theme="dark"]) .home-collab-wow__benefit-icon { background:#f0f8ff; }
@media (max-width: 1024px) {
  .home-hero-wow > div.relative.z-10 > div.grid.grid-cols-1:first-child { gap: 34px !important; }
  .home-hero-wow__title { font-size: clamp(2.8rem,6vw,4.6rem) !important; }
  .home-hero-wow__video { min-height: 330px !important; }
  .home-hero-wow__trust > div { grid-template-columns: repeat(3,minmax(0,1fr)) !important; }
  .home-hero-wow__trust > div > a { grid-column: 1/-1 !important; margin: 12px 0 0 !important; justify-self: start !important; }
  .home-collab-wow { padding:76px 0 90px !important; }
  .home-collab-wow__layout { width:min(calc(100% - 48px),760px); grid-template-columns:1fr !important; gap:70px !important; }
  .home-collab-wow__steps { margin-left:45px; }
}
@media (max-width: 720px) {
  .home-hero-wow { padding: 40px 0 24px !important; }
  .home-hero-wow > div.relative.z-10 > div.grid.grid-cols-1:first-child { display: block !important; }
  .home-hero-wow__title { margin-top: 22px !important; font-size: clamp(2.8rem,13vw,4.1rem) !important; }
  .home-hero-wow__media { margin-top: 34px !important; }
  .home-hero-wow__video { min-height: 0 !important; }
  .home-hero-wow__trust { margin-top: 28px !important; padding: 12px !important; }
  .home-hero-wow__trust > div { display: block !important; }
  .home-hero-wow__trust > div > div { border: 0 !important; border-bottom: 1px solid rgba(148,190,220,.18) !important; padding: 13px 8px !important; }
  .home-hero-wow__trust > div > a { display: flex !important; margin: 12px 0 0 !important; }
  .home-collab-wow { padding:58px 0 70px !important; }
  .home-collab-wow__layout { width:calc(100% - 32px); display:block !important; }
  .home-collab-wow__eyebrow { font-size:14px; letter-spacing:.12em; }
  .home-collab-wow__intro h2 { font-size:clamp(2.35rem,11vw,3.6rem) !important; }
  .home-collab-wow__intro > p { font-size:17px !important; }
  .home-collab-wow__desktop-break { display:none; }
  .home-collab-wow__cta { min-width:0; width:100%; min-height:64px; font-size:18px; }
  .home-collab-wow__benefits { grid-template-columns:1fr; gap:20px; padding:22px 18px; }
  .home-collab-wow__benefit { padding:0 0 20px; }
  .home-collab-wow__benefit:last-child { padding-bottom:0; }
  .home-collab-wow__benefit + .home-collab-wow__benefit { border-left:0; border-top:1px solid rgba(89,160,235,.42); padding-top:20px; }
  .home-collab-wow__steps { margin:64px 0 0 34px; gap:18px; }
  .home-collab-wow__step { grid-template-columns:minmax(0,1fr) 112px; min-height:0; padding:28px 18px 24px 50px; }
  .home-collab-wow__step-number { left:-34px; width:68px; height:68px; font-size:23px; }
  .home-collab-wow__step h3 { font-size:22px !important; }
  .home-collab-wow__step p { font-size:16px !important; }
  .home-collab-wow__step-icon { width:96px; height:96px; justify-self:end; margin-top:10px; }
}
</style>

<script>
window.openVeraChat = function(e) {
  if (e && e.stopPropagation) e.stopPropagation();
  const windowEl = document.getElementById('vera-chat-window');
  if (!windowEl) return;
  windowEl.classList.add('is-active');
  document.getElementById('vera-widget-launcher')?.setAttribute('aria-expanded', 'true');
  if (!veraInitialized) {
    initVeraChatSession();
  }
};

window.toggleVeraChat = function(e) {
  if (e && e.stopPropagation) e.stopPropagation();
  const windowEl = document.getElementById('vera-chat-window');
  if (!windowEl) return;
  const isCurrentlyOpen = windowEl.classList.contains('is-active');
  if (isCurrentlyOpen) {
    window.closeVeraChat(e);
  } else {
    window.openVeraChat(e);
  }
};

window.closeVeraChat = function(e) {
  if (e && e.stopPropagation) e.stopPropagation();
  const windowEl = document.getElementById('vera-chat-window');
  if (!windowEl) return;
  windowEl.classList.remove('is-active');
  document.getElementById('vera-widget-launcher')?.setAttribute('aria-expanded', 'false');
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel();
    if (typeof resetVeraVoiceButton === 'function' && typeof currentVeraVoiceBtn !== 'undefined' && currentVeraVoiceBtn) {
      resetVeraVoiceButton(currentVeraVoiceBtn);
    }
  }
};

document.addEventListener('click', function(e) {
  const windowEl = document.getElementById('vera-chat-window');
  const launcherEl = document.getElementById('vera-widget-launcher');
  if (!windowEl || !windowEl.classList.contains('is-active')) return;
  if (!windowEl.contains(e.target) && !launcherEl?.contains(e.target) && !e.target.closest('[onclick*="openVeraWithContext"]')) {
    window.closeVeraChat();
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    window.closeVeraChat();
  }
});

(() => {
  const animatedItems = document.querySelectorAll('.home-collab-wow [data-scroll-animate]');
  if (!animatedItems.length) return;
  animatedItems.forEach((item) => {
    item.style.setProperty('--scroll-delay', item.dataset.scrollDelay || '0');
  });
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
    animatedItems.forEach((item) => item.classList.add('is-visible'));
    return;
  }
  const animationObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });
  animatedItems.forEach((item) => animationObserver.observe(item));
})();

const veraThemeUri = <?php echo wp_json_encode($captacion_theme_uri); ?>;
let veraInitialized = false;
let veraChatHistory = [];

function goToHomeRegisterLogin() {
  navigateTo('/inicio');
  setTimeout(() => {
    const el = document.getElementById('home-register-login');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, 150);
  if (typeof window.closeVeraChat === 'function') window.closeVeraChat();
}

function initVeraChatSession() {
  const messagesContainer = document.getElementById('vera-chat-messages');
  const footerContainer = document.getElementById('vera-chat-footer');
  
  if (!messagesContainer || !footerContainer) return;
  
  messagesContainer.innerHTML = '';
  
  const welcomeMsg = document.createElement('div');
  welcomeMsg.className = 'vera-msg vera-msg-assistant';
  
  const name = (typeof getDemoSession === 'function' && getDemoSession()?.name) || 'Profesional';
  const state = typeof getPrivateDashboardState === 'function' ? getPrivateDashboardState() : {};
  const tasksCount = (state.tasks || []).filter(item => item.status !== 'done').length;
  const requestsCount = (state.requestsReceived || []).length;
  const salesMatchesCount = typeof getSalesMatchRecords === 'function' ? getSalesMatchRecords().length : 0;
  
  let welcomeHtml = `<strong>Vera:</strong><br><br>¡Hola ${escapeHTML(name)}! `;
  let items = [];
  if (salesMatchesCount > 0) items.push(`<strong>${salesMatchesCount} coincidencias</strong> inmobiliarias`);
  if (tasksCount > 0) items.push(`<strong>${tasksCount} tareas</strong> pendientes`);
  if (requestsCount > 0) items.push(`<strong>${requestsCount} solicitudes</strong> recibidas`);
  
  if (items.length > 0) {
    welcomeHtml += `Tienes ${items.join(' y ')}. ¿En qué te puedo asesorar hoy?`;
  } else {
    welcomeHtml += `Estoy lista para ayudarte con cualquier duda sobre captaciones protegidas, demandas, acuerdos 50/50 o el libro mayor de créditos.`;
  }
  
  welcomeMsg.innerHTML = `${welcomeHtml}<div class="mt-4 grid gap-2"><button type="button" onclick="handleVeraQuickAction('search')" class="vera-quick-action vera-quick-action--primary rounded-lg border px-3 py-2 text-xs">🔍 Buscar inmuebles para clientes</button><button type="button" onclick="handleVeraQuickAction('publish')" class="vera-quick-action vera-quick-action--secondary rounded-lg border px-3 py-2 text-xs">📤 Compartir captación 50/50</button><button type="button" onclick="handleVeraQuickAction('matches')" class="vera-quick-action vera-quick-action--neutral rounded-lg border px-3 py-2 text-xs">⚡ Ver mis coincidencias</button></div>`;
  messagesContainer.appendChild(welcomeMsg);
  
  const cleanWelcomeText = welcomeHtml.replace(/<strong>Vera:<\/strong><br><br>/g, '').replace(/<[^>]*>/g, '');
  veraChatHistory = [{ role: 'assistant', content: cleanWelcomeText }];
  
  footerContainer.innerHTML = `
    <div class="vera-chat-input-area flex items-center gap-1.5 p-2 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700">
      <textarea id="vera-user-input" class="vera-input-field flex-1 text-xs py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-navy dark:text-white outline-none resize-none" rows="1" placeholder="Escribe o dicta tu consulta..."></textarea>
      <button type="button" id="vera-mic-button" onclick="toggleVeraSpeechRecognition()" class="w-9 h-9 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-blue hover:text-white flex items-center justify-center text-sm transition-all shrink-0" title="Dictar por voz">
        🎙️
      </button>
      <button id="vera-send-button" onclick="sendVeraMessage()" class="vera-send-btn w-9 h-9 rounded-xl bg-blue hover:bg-blue-dark text-white flex items-center justify-center transition-all shrink-0 shadow-xs" aria-label="Enviar mensaje">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="22" y1="2" x2="11" y2="13"></line>
          <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
        </svg>
      </button>
    </div>
  `;
  
  document.getElementById('vera-user-input')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendVeraMessage();
    }
  });
  
  veraInitialized = true;
}

function handleVeraQuickAction(action) {
  if (action === 'search') { startIntentFlow('buscar', 'vera'); return; }
  if (action === 'publish') { startIntentFlow('publicar', 'vera'); return; }
  if (action === 'matches') { navigateTo('/coincidencias-ventas'); return; }
}


function formatVeraMarkdown(text) {
  if (!text) return '';
  return text
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/•\s+(.*?)(?=\n|$)/g, '<li>$1</li>')
    .replace(/(<li>.*<\/li>)/gs, '<ul class="list-disc pl-4 space-y-1 my-2">$1</ul>')
    .replace(/\n\n/g, '<br><br>')
    .replace(/\n/g, '<br>');
}

async function sendVeraMessage() {
  const inputEl = document.getElementById('vera-user-input');
  const btnEl = document.getElementById('vera-send-button');
  const container = document.getElementById('vera-chat-messages');
  if (!inputEl || !container) return;
  
  const text = inputEl.value.trim();
  if (!text) return;
  
  inputEl.value = '';
  inputEl.disabled = true;
  if (btnEl) btnEl.disabled = true;

  // Append user bubble
  const userMsg = document.createElement('div');
  userMsg.className = 'vera-msg vera-msg-user';
  userMsg.textContent = text;
  container.appendChild(userMsg);
  container.scrollTop = container.scrollHeight;

  // Indicador de escritura humanizado con fases
  const typingMsg = document.createElement('div');
  typingMsg.className = 'vera-msg vera-msg-assistant';
  typingMsg.id = 'vera-typing-indicator';
  typingMsg.innerHTML = `
    <div class="flex items-center gap-2.5">
      <div class="flex gap-1 items-center">
        <span class="w-1.5 h-1.5 rounded-full bg-blue animate-bounce" style="animation-delay:0ms"></span>
        <span class="w-1.5 h-1.5 rounded-full bg-blue animate-bounce" style="animation-delay:150ms"></span>
        <span class="w-1.5 h-1.5 rounded-full bg-blue animate-bounce" style="animation-delay:300ms"></span>
      </div>
      <span id="vera-typing-text" class="text-[11px] text-slate-400 font-semibold italic">Vera está escribiendo...</span>
    </div>
  `;
  container.appendChild(typingMsg);
  container.scrollTop = container.scrollHeight;

  // Fases progresivas del indicador
  const typingPhrases = [
    'Un momento, déjame revisar...',
    'Consultando la información...',
    'Ya casi lo tengo...'
  ];
  let typingPhaseIdx = 0;
  const typingInterval = setInterval(() => {
    const typingTextEl = document.getElementById('vera-typing-text');
    if (typingTextEl && typingPhaseIdx < typingPhrases.length) {
      typingTextEl.textContent = typingPhrases[typingPhaseIdx];
      typingPhaseIdx++;
    }
  }, 1800);

  const session = typeof getDemoSession === 'function' ? getDemoSession() : null;
  const userName = session?.name || 'Profesional';

  try {
    const basePath = (typeof window.CAPTACION_CONFIG !== 'undefined' && window.CAPTACION_CONFIG?.basePath) ? window.CAPTACION_CONFIG.basePath : '/';
    const veraEndpoint = basePath.replace(/\/$/, '') + '/api/vera.php';

    const res = await fetch(veraEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: text,
        history: typeof veraChatHistory !== 'undefined' ? veraChatHistory : [],
        user_name: userName,
        onboarding: window.CAPTACION_ONBOARDING_CONTEXT || {
          guide_id: 'academy',
          phase_id: 'general-help',
          current_step: 0,
          user_level: localStorage.getItem('captacion_user_level_v1') || 'junior',
          current_route: window.location.hash || window.location.pathname
        }
      })
    });
    
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    const data = await res.json();
    const humanDelay = Math.min(8000, Math.max(1800, 1800 + Math.min(6200, String(data.response || '').length * 35)));
    await new Promise(resolve => window.setTimeout(resolve, humanDelay));
    clearInterval(typingInterval);
    typingMsg.remove();

    const assistantMsg = document.createElement('div');
    assistantMsg.className = 'vera-msg vera-msg-assistant';
    const replyText = data.response || (data.error ? ('Nota: ' + data.error) : 'No pude procesar la consulta en este momento. Por favor, intenta de nuevo.');
    let bubbleHtml = '<strong>Vera:</strong><br><br>' + formatVeraMarkdown(replyText);

    // Si Vera sugiere una acción de UI (navegación o campo)
    if (data.suggested_action && data.suggested_action.label) {
      bubbleHtml += `<div class="mt-3 pt-2.5 border-t border-slate-200/60 dark:border-slate-700/60"><button type="button" onclick='executeVeraAction(${JSON.stringify(data.suggested_action.type)}, ${JSON.stringify(data.suggested_action.target || data.suggested_action.field)})' class="w-full py-2 px-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-bold shadow-xs transition-colors flex items-center justify-center gap-2"><span>🚀 ${escapeHTML(data.suggested_action.label)}</span> →</button></div>`;
    }
    if (data.support_draft) {
      bubbleHtml += `<div class="mt-3 pt-2.5 border-t border-slate-200/60 dark:border-slate-700/60"><button type="button" onclick='openVeraSupportDraft(${JSON.stringify(data.support_draft)})' class="text-blue hover:text-blue-dark underline text-xs font-bold">Enviar consulta al equipo</button></div>`;
    }

    assistantMsg.innerHTML = bubbleHtml;
    container.appendChild(assistantMsg);
    container.scrollTop = container.scrollHeight;

    // Botón de lectura interactivo con pausa y reanudación
    const cleanSpeechText = replyText.replace(/<[^>]*>/g, '').replace(/[*_#•]/g, '');
    const voiceBtn = document.createElement('button');
    voiceBtn.type = 'button';
    voiceBtn.className = 'mt-2.5 text-[11px] text-slate-400 hover:text-blue flex items-center gap-1.5 font-bold transition-all py-1 px-2.5 rounded-lg hover:bg-blue/10 w-fit';
    voiceBtn.innerHTML = '<span>🔊</span> <span>Escuchar respuesta</span>';
    voiceBtn.onclick = function() { toggleVeraAudioSpeech(this, cleanSpeechText); };
    assistantMsg.appendChild(voiceBtn);

    if (typeof veraChatHistory !== 'undefined') {
      veraChatHistory.push({ role: 'user', content: text });
      veraChatHistory.push({ role: 'assistant', content: replyText });
    }
  } catch (err) {
    clearInterval(typingInterval);
    typingMsg.remove();
    const errMsg = document.createElement('div');
    errMsg.className = 'vera-msg vera-msg-assistant text-amber';
    errMsg.innerHTML = '<strong>Vera:</strong><br><br>Perdona, he tenido un pequeño corte de conexión. ¿Puedes repetirme la pregunta?';
    container.appendChild(errMsg);
    container.scrollTop = container.scrollHeight;
  } finally {
    inputEl.disabled = false;
    if (btnEl) btnEl.disabled = false;
    inputEl.focus();
  }
}

function openOpportunityChoiceModal() {
  const modal = document.getElementById('opportunity-choice-modal');
  if (!modal) return;
  modal.classList.remove('hidden'); modal.classList.add('flex');
  modal.querySelector('a,button')?.focus();
}
function closeOpportunityChoiceModal() {
  const modal = document.getElementById('opportunity-choice-modal');
  if (!modal) return;
  modal.classList.add('hidden'); modal.classList.remove('flex');
}
document.addEventListener('keydown', event => { if (event.key === 'Escape') closeOpportunityChoiceModal(); });
window.openOpportunityChoiceModal = openOpportunityChoiceModal;
window.closeOpportunityChoiceModal = closeOpportunityChoiceModal;
window.sendVeraMessage = sendVeraMessage;

let veraSpeechRecognition = null;
let isVeraListening = false;

function toggleVeraSpeechRecognition() {
  const micBtn = document.getElementById('vera-mic-button');
  const inputEl = document.getElementById('vera-user-input');
  if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
    if (typeof showToast === 'function') showToast('Tu navegador no soporta reconocimiento de voz nativo.', 'info');
    return;
  }
  
  if (isVeraListening && veraSpeechRecognition) {
    veraSpeechRecognition.stop();
    isVeraListening = false;
    if (micBtn) micBtn.classList.remove('bg-red-500', 'text-white', 'animate-pulse');
    return;
  }

  const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
  veraSpeechRecognition = new SpeechRec();
  veraSpeechRecognition.lang = 'es-ES';
  veraSpeechRecognition.continuous = false;
  veraSpeechRecognition.interimResults = false;

  veraSpeechRecognition.onstart = function() {
    isVeraListening = true;
    if (micBtn) {
      micBtn.classList.add('bg-red-500', 'text-white', 'animate-pulse');
      micBtn.title = 'Escuchando... pulsa para enviar';
    }
  };

  veraSpeechRecognition.onresult = function(event) {
    const transcript = event.results[0][0].transcript;
    if (inputEl && transcript) {
      inputEl.value = transcript;
      sendVeraMessage();
    }
  };

  veraSpeechRecognition.onerror = function() {
    isVeraListening = false;
    if (micBtn) micBtn.classList.remove('bg-red-500', 'text-white', 'animate-pulse');
  };

  veraSpeechRecognition.onend = function() {
    isVeraListening = false;
    if (micBtn) micBtn.classList.remove('bg-red-500', 'text-white', 'animate-pulse');
  };

  try {
    veraSpeechRecognition.start();
  } catch(e){}
}
window.toggleVeraSpeechRecognition = toggleVeraSpeechRecognition;

let currentVeraUtterance = null;
let currentVeraVoiceBtn = null;
let isVeraSpeaking = false;
let isVeraPaused = false;

function toggleVeraAudioSpeech(btn, text) {
  if (!('speechSynthesis' in window)) {
    if (typeof showToast === 'function') showToast('Tu navegador no soporta reproducción de audio.', 'info');
    return;
  }

  // Si es el mismo botón y ya está activo el reproductor
  if (currentVeraVoiceBtn === btn && isVeraSpeaking) {
    if (isVeraPaused) {
      // Reanudar
      window.speechSynthesis.resume();
      isVeraPaused = false;
      btn.innerHTML = '<span class="animate-pulse">⏸️</span> <span class="text-blue font-bold">Pausar audio</span>';
      btn.classList.add('text-blue');
    } else {
      // Pausar
      window.speechSynthesis.pause();
      isVeraPaused = true;
      btn.innerHTML = '<span>▶️</span> <span class="text-amber-500 font-bold">Reanudar audio</span>';
      btn.classList.remove('text-blue');
    }
    return;
  }

  // Si había otro audio anterior en marcha, resetearlo
  if (currentVeraVoiceBtn && currentVeraVoiceBtn !== btn) {
    resetVeraVoiceButton(currentVeraVoiceBtn);
  }
  window.speechSynthesis.cancel();

  // Iniciar nueva locución
  const clean = (text || '').replace(/<[^>]*>/g, '').replace(/[*_#•]/g, '');
  const utterance = new SpeechSynthesisUtterance(clean);
  utterance.lang = 'es-ES';
  utterance.rate = 1.05;

  currentVeraUtterance = utterance;
  currentVeraVoiceBtn = btn;
  isVeraSpeaking = true;
  isVeraPaused = false;

  btn.innerHTML = '<span class="animate-pulse">⏸️</span> <span class="text-blue font-bold">Pausar audio</span>';
  btn.classList.add('text-blue');

  utterance.onend = function() {
    resetVeraVoiceButton(btn);
  };

  utterance.onerror = function() {
    resetVeraVoiceButton(btn);
  };

  window.speechSynthesis.speak(utterance);
}
window.toggleVeraAudioSpeech = toggleVeraAudioSpeech;
window.speakVeraText = function(text) {
  if (currentVeraVoiceBtn) {
    toggleVeraAudioSpeech(currentVeraVoiceBtn, text);
  }
};

function resetVeraVoiceButton(btn) {
  if (btn) {
    btn.innerHTML = '<span>🔊</span> <span>Escuchar respuesta</span>';
    btn.classList.remove('text-blue');
  }
  if (currentVeraVoiceBtn === btn) {
    currentVeraUtterance = null;
    currentVeraVoiceBtn = null;
    isVeraSpeaking = false;
    isVeraPaused = false;
  }
}

function openVeraWithContext(guideId, phaseId, step, initialQuery) {
  window.CAPTACION_ONBOARDING_CONTEXT = {
    guide_id: guideId || 'academy',
    phase_id: phaseId || 'general-help',
    current_step: step || 0,
    user_level: localStorage.getItem('captacion_user_level_v1') || 'junior',
    current_route: window.location.hash || window.location.pathname
  };

  if (typeof window.openVeraChat === 'function') {
    window.openVeraChat();
  } else {
    const windowEl = document.getElementById('vera-chat-window');
    if (windowEl) {
      windowEl.classList.add('is-active');
      if (!veraInitialized && typeof initVeraChatSession === 'function') initVeraChatSession();
    }
  }

  setTimeout(() => {
    const inputEl = document.getElementById('vera-user-input');
    if (inputEl && initialQuery) {
      inputEl.value = initialQuery;
      sendVeraMessage();
    }
  }, 60);
}
window.openVeraWithContext = openVeraWithContext;

function executeVeraAction(actionType, target) {
  if (!actionType) return;
  if (actionType === 'navigate' && target) {
    if (target.startsWith('#')) {
      window.location.hash = target;
      if (typeof handleHashNavigation === 'function') handleHashNavigation();
    } else if (typeof navigateTo === 'function') {
      navigateTo(target);
    }
    if (typeof window.closeVeraChat === 'function') window.closeVeraChat();
  } else if (actionType === 'focus_field' && target) {
    const el = document.getElementById(target) || document.querySelector(`[name="${target}"]`);
    if (el) {
      el.focus();
      el.classList.add('ring-2', 'ring-blue');
      setTimeout(() => el.classList.remove('ring-2', 'ring-blue'), 3000);
    }
    if (typeof window.closeVeraChat === 'function') window.closeVeraChat();
  }
}
window.executeVeraAction = executeVeraAction;

function setAcademyUserLevel(level) {
  const normLevel = level === 'senior' ? 'senior' : 'junior';
  localStorage.setItem('captacion_user_level_v1', normLevel);

  const btnJunior = document.getElementById('academy-btn-junior');
  const btnSenior = document.getElementById('academy-btn-senior');

  if (normLevel === 'junior') {
    btnJunior?.classList.add('bg-blue', 'text-white', 'shadow-xs');
    btnJunior?.classList.remove('text-slate-400');
    btnSenior?.classList.remove('bg-blue', 'text-white', 'shadow-xs');
    btnSenior?.classList.add('text-slate-400');
    document.querySelectorAll('.academy-desc-junior').forEach(el => el.classList.remove('hidden'));
    document.querySelectorAll('.academy-desc-senior').forEach(el => el.classList.add('hidden'));
  } else {
    btnSenior?.classList.add('bg-blue', 'text-white', 'shadow-xs');
    btnSenior?.classList.remove('text-slate-400');
    btnJunior?.classList.remove('bg-blue', 'text-white', 'shadow-xs');
    btnJunior?.classList.add('text-slate-400');
    document.querySelectorAll('.academy-desc-junior').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.academy-desc-senior').forEach(el => el.classList.remove('hidden'));
  }
}
window.setAcademyUserLevel = setAcademyUserLevel;

function getAcademyProgress() {
  try {
    return JSON.parse(localStorage.getItem('captacion_onboarding_v1') || '{}');
  } catch (e) {
    return {};
  }
}

function saveAcademyProgress(progress) {
  localStorage.setItem('captacion_onboarding_v1', JSON.stringify(progress || {}));
}

function toggleAcademyPhase(phaseId) {
  const progress = getAcademyProgress();
  const current = progress[phaseId] || 'pending';
  progress[phaseId] = current === 'completed' ? 'pending' : 'completed';
  saveAcademyProgress(progress);
  updateAcademyUI();
}
window.toggleAcademyPhase = toggleAcademyPhase;

function updateAcademyUI() {
  const progress = getAcademyProgress();
  const phases = [
    'phase-1-orientation', 'phase-2-profile', 'phase-3-captation',
    'phase-4-demand', 'phase-5-matches', 'phase-6-collaboration', 'phase-7-closing'
  ];
  let completedCount = 0;

  phases.forEach(id => {
    const isCompleted = progress[id] === 'completed';
    if (isCompleted) completedCount++;

    const badge = document.getElementById('badge-' + id);
    const btnToggle = document.getElementById('btn-toggle-' + id);
    const card = document.querySelector(`.academy-phase-card[data-phase="${id}"]`);

    if (badge) {
      badge.textContent = isCompleted ? 'Superada ✓' : 'Pendiente';
      badge.className = isCompleted
        ? 'academy-phase-badge text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300'
        : 'academy-phase-badge text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400';
    }
    if (btnToggle) {
      btnToggle.textContent = isCompleted ? 'Desmarcar' : 'Marcar como superada ✓';
      btnToggle.className = isCompleted
        ? 'w-full py-2 px-3 rounded-xl bg-emerald-500 text-white text-xs font-bold transition-all shadow-xs'
        : 'w-full py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-emerald-500 hover:text-white text-slate-700 dark:text-slate-300 text-xs font-bold transition-all';
    }
    if (card) {
      if (isCompleted) {
        card.classList.add('border-emerald-300', 'dark:border-emerald-800/60', 'bg-emerald-50/20', 'dark:bg-emerald-950/10');
      } else {
        card.classList.remove('border-emerald-300', 'dark:border-emerald-800/60', 'bg-emerald-50/20', 'dark:bg-emerald-950/10');
      }
    }
  });

  const percent = Math.round((completedCount / phases.length) * 100);
  const progressBar = document.getElementById('academy-progress-bar');
  const progressText = document.getElementById('academy-progress-text');
  const progressPercent = document.getElementById('academy-progress-percent');
  const donutBar = document.getElementById('academy-donut-bar');
  const donutPct = document.getElementById('academy-donut-pct');
  const rankBadge = document.getElementById('academy-rank-badge');
  const sidebarBadge = document.getElementById('academy-sidebar-badge');

  if (progressBar) progressBar.style.width = percent + '%';
  if (progressText) progressText.textContent = `${completedCount} de ${phases.length} superadas`;
  if (progressPercent) progressPercent.textContent = `${percent}% completado`;
  if (donutBar) donutBar.setAttribute('stroke-dasharray', `${percent}, 100`);
  if (donutPct) donutPct.textContent = `${percent}%`;
  if (sidebarBadge) sidebarBadge.textContent = `${completedCount}/${phases.length}`;

  // Cálculo de Rango de Certificación
  if (rankBadge) {
    if (completedCount >= 6) {
      rankBadge.textContent = 'Broker Certificado 50/50 🏆';
      rankBadge.className = 'px-3.5 py-1 rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xs font-black shadow-xs uppercase tracking-wider';
    } else if (completedCount >= 3) {
      rankBadge.textContent = 'Especialista MLS 50/50 🎖️';
      rankBadge.className = 'px-3.5 py-1 rounded-full bg-gradient-to-r from-blue to-indigo-600 text-white text-xs font-black shadow-xs uppercase tracking-wider';
    } else {
      rankBadge.textContent = 'Iniciado MLS 🚀';
      rankBadge.className = 'px-3.5 py-1 rounded-full bg-slate-700 text-white text-xs font-black shadow-xs uppercase tracking-wider';
    }
  }
}
window.updateAcademyUI = updateAcademyUI;

function initAcademyOnboarding() {
  const savedLevel = localStorage.getItem('captacion_user_level_v1') || 'junior';
  setAcademyUserLevel(savedLevel);
  updateAcademyUI();
}
window.initAcademyOnboarding = initAcademyOnboarding;

document.addEventListener('DOMContentLoaded', function() {
  initAcademyOnboarding();
});


function toggleHeroVideoAudio() {
  const v = document.getElementById('hero-explainer-video');
  const icon = document.getElementById('hero-video-audio-icon');
  const label = document.getElementById('hero-video-audio-label');
  if (!v) return;
  v.muted = !v.muted;
  if (!v.muted) {
    v.volume = 1.0;
    v.play().catch(()=>{});
    if (icon) icon.textContent = '🔊';
    if (label) label.textContent = 'Silenciar vídeo';
  } else {
    if (icon) icon.textContent = '🔇';
    if (label) label.textContent = 'Activar audio';
  }
}
window.toggleHeroVideoAudio = toggleHeroVideoAudio;
window.toggleHeroVideoSound = toggleHeroVideoAudio;

let currentCalculatorRole = 'captador';
function setCalculatorRole(role) {
  currentCalculatorRole = role;
  const btnCaptador = document.getElementById('calc-role-captador');
  const btnColaborador = document.getElementById('calc-role-colaborador');
  const roleTitle = document.getElementById('calc-role-title');
  const roleDesc = document.getElementById('calc-role-desc');

  if (role === 'captador') {
    if (btnCaptador) btnCaptador.className = 'px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5';
    if (btnColaborador) btnColaborador.className = 'px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-navy dark:hover:text-white text-xs font-bold transition-all flex items-center gap-1.5';
    if (roleTitle) roleTitle.textContent = 'Tus Honorarios Netos (50% de la operación)';
    if (roleDesc) roleDesc.textContent = 'Como agencia con la captación en cartera';
  } else {
    if (btnColaborador) btnColaborador.className = 'px-4 py-2 rounded-xl bg-blue text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5';
    if (btnCaptador) btnCaptador.className = 'px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-navy dark:hover:text-white text-xs font-bold transition-all flex items-center gap-1.5';
    if (roleTitle) roleTitle.textContent = 'Tus Honorarios Netos (50% de la operación)';
    if (roleDesc) roleDesc.textContent = 'Como agencia con el comprador solvente';
  }
}
window.setCalculatorRole = setCalculatorRole;

function setCalculatorPreset(price) {
  const sliderEl = document.getElementById('calc-price-slider');
  if (sliderEl) sliderEl.value = price;
  updateFeeCalculator(price);
}
window.setCalculatorPreset = setCalculatorPreset;

function updateFeeCalculator(price) {
  const sliderEl = document.getElementById('calc-price-slider');
  const p = Number(price !== undefined && price !== null ? price : sliderEl?.value) || 210000;
  const commPct = 3.0;
  const shPct = 50;

  const formattedPrice = p.toLocaleString('es-ES') + ' €';
  const totalCommission = Math.round(p * (commPct / 100));
  const yourShare = Math.round(totalCommission * (shPct / 100));
  const partnerShare = totalCommission - yourShare;

  const priceEl = document.getElementById('calc-price-display');
  const totalEl = document.getElementById('calc-total-commission');
  const shareEl = document.getElementById('calc-your-share');
  const partnerEl = document.getElementById('calc-partner-share');

  if (priceEl) priceEl.textContent = formattedPrice;
  if (totalEl) totalEl.textContent = totalCommission.toLocaleString('es-ES') + ' €';
  if (shareEl) shareEl.textContent = yourShare.toLocaleString('es-ES') + ' €';
  if (partnerEl) partnerEl.textContent = partnerShare.toLocaleString('es-ES') + ' €';
}
window.updateFeeCalculator = updateFeeCalculator;

document.addEventListener('DOMContentLoaded', function() {
  const priceSlider = document.getElementById('calc-price-slider');
  const commSlider = document.getElementById('calc-commission-slider');
  const shareSlider = document.getElementById('calc-share-slider');
  
  if (priceSlider) {
    priceSlider.addEventListener('input', function() { updateFeeCalculator(this.value); });
    priceSlider.addEventListener('change', function() { updateFeeCalculator(this.value); });
  }
  if (commSlider) {
    commSlider.addEventListener('input', function() { updateFeeCalculator(null, this.value, null); });
    commSlider.addEventListener('change', function() { updateFeeCalculator(null, this.value, null); });
  }
  if (shareSlider) {
    shareSlider.addEventListener('input', function() { updateFeeCalculator(null, null, this.value); });
    shareSlider.addEventListener('change', function() { updateFeeCalculator(null, null, this.value); });
  }

  // Initial run to ensure 210.000 € is painted correctly
  updateFeeCalculator(210000, 3, 50);
});

function setupVeraAutoWake() {
  const launcher = document.getElementById('vera-widget-launcher');
  if (launcher) {
    launcher.setAttribute('title', 'Vera AI - Asistente Inmobiliaria');
  }
}
</script>

<script id="captacion-territories-data" type="application/json">
<?php 
$territoryFile = __DIR__ . '/data/territorios-espana.json';
if (file_exists($territoryFile)) {
    echo file_get_contents($territoryFile);
} else {
    echo '[]';
}
?>
</script>
<?php wp_footer(); ?>

  <!-- MODAL EXPLICATIVO DE CRÉDITOS Y BONOS -->
  <div id="credit-detail-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 bg-navy/60 backdrop-blur-sm transition-all" onclick="if(event.target===this)closeCreditDetailModal()">
    <div class="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 sm:p-8 space-y-6 relative animate-fadeIn">
      <button type="button" onclick="closeCreditDetailModal()" class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-navy dark:hover:text-white flex items-center justify-center text-sm font-bold transition-colors" aria-label="Cerrar ventana">×</button>
      
      <div class="flex items-center gap-3.5">
        <div id="credit-modal-icon" class="w-12 h-12 rounded-2xl bg-blue/10 text-blue flex items-center justify-center text-2xl shrink-0">💎</div>
        <div>
          <span id="credit-modal-kicker" class="text-[10px] font-black uppercase tracking-wider text-blue">Información Oficial</span>
          <h3 id="credit-modal-title" class="text-xl font-black text-navy dark:text-white mt-0.5">Saldo de Créditos</h3>
        </div>
      </div>

      <div id="credit-modal-body" class="space-y-4 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
        <!-- Inyectado dinámicamente -->
      </div>

      <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
        <button type="button" onclick="closeCreditDetailModal()" class="px-6 py-2.5 rounded-xl bg-navy text-white text-xs font-bold hover:bg-navy-light transition-all">Entendido</button>
      </div>
    </div>
  </div>

  <!-- PWA SMART FLOATING INSTALL BANNER (Mobile / Desktop) -->
  <div id="pwa-smart-banner" class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:max-w-md z-[9990] hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl rounded-2xl p-4 transition-all duration-300 transform translate-y-8 opacity-0">
    <div class="flex items-start gap-3.5">
      <img src="/assets/media/icon-192.png" alt="Compra Captación" class="w-12 h-12 rounded-xl shrink-0 shadow-md border border-slate-100 dark:border-slate-800" />
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <strong class="text-sm font-black text-navy dark:text-white truncate">Instalar Compra Captación</strong>
          <button type="button" onclick="dismissPWABanner()" class="text-slate-400 hover:text-navy dark:hover:text-white p-1 text-sm font-bold leading-none" aria-label="Cerrar aviso">✕</button>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">Acceso instantáneo desde tu pantalla de inicio, modo pantalla completa y alertas de cruces.</p>
        <div class="flex items-center gap-2 mt-3">
          <button type="button" onclick="triggerPWAInstall()" class="flex-1 py-2 px-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black uppercase tracking-wider transition-all shadow-sm flex items-center justify-center gap-1.5">
            <span>Instalar App</span>
            <span aria-hidden="true">📲</span>
          </button>
          <button type="button" onclick="dismissPWABanner()" class="py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold transition-all">
            Ahora no
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- PWA IOS SAFARI INSTRUCTIONS MODAL -->
  <div id="pwa-ios-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 bg-navy/60 backdrop-blur-sm" onclick="if(event.target===this)closePWAIOSModal()">
    <div class="w-full max-w-sm rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-5 text-center animate-fadeIn relative">
      <button type="button" onclick="closePWAIOSModal()" class="absolute top-4 right-4 w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-navy dark:hover:text-white flex items-center justify-center text-xs font-bold">✕</button>
      
      <img src="/assets/media/apple-touch-icon.png" alt="Compra Captación" class="w-16 h-16 rounded-2xl mx-auto shadow-lg border border-slate-100 dark:border-slate-800" />
      
      <div>
        <h4 class="text-lg font-black text-navy dark:text-white">Instalar en tu iPhone o iPad</h4>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Sigue estos 2 sencillos pasos en Safari:</p>
      </div>

      <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 text-xs text-left space-y-3 text-slate-700 dark:text-slate-200">
        <div class="flex items-center gap-3">
          <span class="w-7 h-7 rounded-xl bg-blue/10 text-blue font-black flex items-center justify-center shrink-0">1</span>
          <p>Toca el botón <strong>Compartir</strong> <span class="text-base">⎋</span> en la barra inferior de Safari.</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="w-7 h-7 rounded-xl bg-blue/10 text-blue font-black flex items-center justify-center shrink-0">2</span>
          <p>Desplaza hacia abajo y pulsa <strong>«Añadir a la pantalla de inicio»</strong> <span class="text-base">➕</span>.</p>
        </div>
      </div>

  <!-- ============================================================== -->
  <!-- MODAL DE ONBOARDING INTERACTIVO CON VERA IA (5 PASOS)           -->
  <!-- ============================================================== -->
  <div id="vera-onboarding-modal" class="fixed inset-0 z-[150] hidden items-center justify-center p-3 sm:p-4 bg-navy-dark/80 backdrop-blur-md overflow-y-auto">
    <div class="relative w-full max-w-xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden my-auto animate-fadeIn">
      
      <!-- Cabecera del Onboarding con Barra de Progreso -->
      <div class="p-5 sm:p-6 bg-gradient-to-r from-blue/10 via-purple-500/10 to-emerald-500/10 dark:from-slate-800 dark:to-slate-850 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue via-indigo-500 to-purple-600 flex items-center justify-center text-white text-lg shadow-md shrink-0 ring-2 ring-blue/20">
              ✦
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-sm sm:text-base font-black text-navy dark:text-white">Vera · Copiloto de Activación</h3>
                <span class="px-2 py-0.5 rounded-full bg-blue text-white text-[9px] font-black uppercase tracking-wider">Asistente IA</span>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Activación asistida de tu cuenta profesional</p>
            </div>
          </div>
          
          <button type="button" onclick="closeVeraOnboardingModal(true)" aria-label="Saltar al panel" class="text-xs font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
            Saltar
          </button>
        </div>

        <!-- Indicador de Paso y Barra de Progreso -->
        <div class="mt-4">
          <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
            <span id="vera-onboarding-step-label">Paso 1 de 5</span>
            <span id="vera-onboarding-progress-pct">20%</span>
          </div>
          <div class="h-2 w-full rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
            <div id="vera-onboarding-progress-bar" class="h-full rounded-full bg-gradient-to-r from-blue via-purple-500 to-emerald-500 transition-all duration-500" style="width: 20%"></div>
          </div>
        </div>
      </div>

      <!-- Cuerpo del Chat Conversacional -->
      <div id="vera-onboarding-chat-body" class="p-5 sm:p-7 space-y-4 min-h-[260px] max-h-[60vh] overflow-y-auto">
        <!-- Contenido dinámico renderizado por JS (burbujas y botones interactivos) -->
      </div>

      <!-- Indicador de "Vera está escribiendo..." -->
      <div id="vera-onboarding-typing" class="hidden px-7 pb-4">
        <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 text-xs font-semibold">
          <span class="w-1.5 h-1.5 rounded-full bg-blue animate-ping"></span>
          <span>Vera está escribiendo</span>
          <span class="flex gap-1 items-center">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:0.2s"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:0.4s"></span>
          </span>
        </div>
      </div>

      <!-- Pie de Ayuda / Garantía -->
      <div class="px-6 py-3.5 bg-slate-50 dark:bg-slate-850 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
        <span class="flex items-center gap-1.5">
          <span>🔒</span> Protocolo de Datos Ciegos y Reparto 50/50
        </span>
        <span>Sin comisiones sobre venta</span>
      </div>

    </div>
  </div>

  <!-- MODAL EXPLICATIVO FLOTANTE: PROTOCOLO DE DATOS CIEGOS (Paso 1) -->
  <div id="vera-datos-ciegos-modal" class="fixed inset-0 z-[160] hidden items-center justify-center p-4 bg-navy-dark/80 backdrop-blur-sm" onclick="if(event.target===this)closeVeraDatosCiegosModal()">
    <div class="relative w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-4 animate-fadeIn">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-xl">🛡️</span>
          <h4 class="text-base font-black text-navy dark:text-white">Protocolo de Datos Ciegos</h4>
        </div>
        <button type="button" onclick="closeVeraDatosCiegosModal()" class="text-slate-400 hover:text-navy dark:hover:text-white font-black text-lg">✕</button>
      </div>
      <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
        <p><strong>¿Cómo protegemos tus inmuebles y captaciones en exclusiva?</strong></p>
        <ul class="space-y-2 list-disc pl-4">
          <li><strong>Dirección Oculta:</strong> Nunca se publica la calle exacta, número, piso o puerta.</li>
          <li><strong>Sin Catastro Público:</strong> La referencia catastral queda encriptada y solo se revela tras la firma del contrato.</li>
          <li><strong>Sin Fotos Sensibles:</strong> Filtro automático para no mostrar fachadas reconocibles ni vistas exteriores directas.</li>
          <li><strong>Contacto Blindado:</strong> Tus datos de contacto solo se desbloquean a profesionales verificados con acuerdo 50/50 y NDA legal previo.</li>
        </ul>
      </div>
      <button type="button" onclick="closeVeraDatosCiegosModal()" class="w-full py-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black uppercase tracking-wider transition-all">
  <!-- MODAL PROFESIONAL: EDITAR CAPTACIÓN O DEMANDA INDIVIDUAL -->
  <div id="private-edit-record-modal" class="fixed inset-0 z-[150] hidden items-center justify-center p-4 bg-navy-dark/80 backdrop-blur-sm" onclick="if(event.target===this)closeEditRecordModal()">
    <div class="relative w-full max-w-2xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden animate-fadeIn flex flex-col max-h-[90vh]">
      
      <!-- Header del Modal -->
      <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/60 dark:bg-slate-850/60">
        <div class="flex items-center gap-3">
          <div id="edit-record-icon-badge" class="w-10 h-10 rounded-2xl bg-blue/10 text-blue flex items-center justify-center text-xl shrink-0">
            ✏️
          </div>
          <div>
            <h3 id="edit-record-modal-title" class="text-base sm:text-lg font-black text-navy dark:text-white">Editar Registro</h3>
            <p id="edit-record-modal-subtitle" class="text-xs text-slate-500 dark:text-slate-400">Modifica los datos de tu captación o demanda protegida</p>
          </div>
        </div>
        <button type="button" onclick="closeEditRecordModal()" class="w-8 h-8 rounded-full bg-slate-200/60 dark:bg-slate-800 text-slate-500 hover:text-navy dark:hover:text-white flex items-center justify-center text-sm font-bold transition-all">
          ✕
        </button>
      </div>

      <!-- Formulario de Edición -->
      <form id="private-edit-record-form" onsubmit="handleSaveEditRecord(event)" class="p-6 space-y-4 overflow-y-auto flex-1 text-xs">
        <input type="hidden" id="edit-record-id" value="" />
        <input type="hidden" id="edit-record-type" value="property" />

        <!-- Título / Intención -->
        <div>
          <label class="block font-bold text-navy dark:text-white mb-1">Título / Intención <span class="text-red-500">*</span></label>
          <input type="text" id="edit-record-title" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue" placeholder="Ej: Piso señorial en Salamanca..." />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Tipología -->
          <div>
            <label class="block font-bold text-navy dark:text-white mb-1">Tipología <span class="text-red-500">*</span></label>
            <select id="edit-record-property-type" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue">
              <option value="Piso">Piso</option>
              <option value="Apartamento">Apartamento</option>
              <option value="Ático">Ático</option>
              <option value="Dúplex">Dúplex</option>
              <option value="Casa / chalet">Casa / Chalet</option>
              <option value="Local comercial">Local comercial</option>
              <option value="Nave industrial">Nave industrial</option>
              <option value="Terreno / Parcela">Terreno / Parcela</option>
              <option value="Oficina">Oficina</option>
              <option value="Edificio">Edificio</option>
              <option value="Garaje">Garaje</option>
              <option value="Trastero">Trastero</option>
            </select>
          </div>

          <!-- Precio / Presupuesto -->
          <div>
            <label id="edit-record-price-label" class="block font-bold text-navy dark:text-white mb-1">Precio (€) <span class="text-red-500">*</span></label>
            <input type="number" step="any" min="0" id="edit-record-price" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue" />
          </div>

          <!-- Estado -->
          <div>
            <label class="block font-bold text-navy dark:text-white mb-1">Estado</label>
            <select id="edit-record-status" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue">
              <option value="active">Activa (Publicada)</option>
              <option value="paused">Pausada (Oculta)</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Provincia -->
          <div>
            <label class="block font-bold text-navy dark:text-white mb-1">Provincia <span class="text-red-500">*</span></label>
            <input type="text" id="edit-record-province" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue" />
          </div>

          <!-- Municipio -->
          <div>
            <label class="block font-bold text-navy dark:text-white mb-1">Municipio <span class="text-red-500">*</span></label>
            <input type="text" id="edit-record-municipality" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue" />
          </div>

          <!-- Zona / Barrio -->
          <div>
            <label class="block font-bold text-navy dark:text-white mb-1">Zona / Barrio</label>
            <input type="text" id="edit-record-zone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue" />
          </div>
        </div>

        <!-- Características Físicas (Habitaciones, Baños, Superficie) -->
        <div id="edit-record-physical-wrap" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block font-bold text-navy dark:text-white mb-1">Habitaciones</label>
            <input type="number" min="0" id="edit-record-bedrooms" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue" />
          </div>
          <div>
            <label class="block font-bold text-navy dark:text-white mb-1">Baños</label>
            <input type="number" min="0" id="edit-record-bathrooms" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue" />
          </div>
          <div>
            <label class="block font-bold text-navy dark:text-white mb-1">Superficie (m²)</label>
            <input type="number" min="0" step="any" id="edit-record-surface" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue" />
          </div>
        </div>

        <!-- Descripción Pública (Datos Ciegos) -->
        <div>
          <label class="block font-bold text-navy dark:text-white mb-1">Descripción pública (Datos Ciegos)</label>
          <textarea id="edit-record-description" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-navy dark:text-white text-xs font-semibold focus:outline-none focus:border-blue leading-relaxed" placeholder="Describe los puntos fuertes del inmueble sin revelar dirección exacta ni datos personales..."></textarea>
        </div>

        <!-- Botones de Acción -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-2">
          <button type="button" onclick="closeEditRecordModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
            Cancelar
          </button>
          <button type="submit" id="edit-record-submit-btn" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-green hover:bg-green-dark text-white font-black shadow-md transition-all">
            Guardar cambios
          </button>
        </div>
      </form>

    </div>
  </div>

</body>
</html>
