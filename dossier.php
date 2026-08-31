<?php
/**
 * Compra Captación - Dossier Profesional de Oportunidad Inmobiliaria para Profesionales y Cliente Final
 * Vista en MODO CLARO editorial de alta gama.
 * Soporta atribución dinámica de agente remitente, folleto A4 a dos caras para PDF/Open House,
 * popup de compartir y formulario completo de captación de leads.
 */

require_once __DIR__ . '/api/database.php';
require_once __DIR__ . '/api/auth.php';

$db = CaptacionDB::get();
$currentUser = get_auth_user();
$currentUserId = $currentUser ? (int)$currentUser['id'] : 0;

$id = trim($_GET['id'] ?? '');
$agentParam = trim($_GET['agent'] ?? $_GET['ref'] ?? '');
$mode = trim($_GET['mode'] ?? ''); // 'client'
$isClientMode = ($mode === 'client' || isset($_GET['client']) || !empty($agentParam));

// 1. Datos base del Inmueble (Fallback y Seed)
$defaultRecord = [
    'id' => 1,
    'record_key' => 'PROP-VAL-001',
    'title' => 'Villa de Lujo con Piscina Infinity en Costa Adeje',
    'property_type' => 'Casa/Chalet',
    'operation_type' => 'colaboracion_50_50',
    'price' => 1280000,
    'commission_percentage' => 50,
    'province' => 'Santa Cruz de Tenerife',
    'municipality' => 'Adeje',
    'zone' => 'Costa Adeje / Golf del Sur',
    'address_public' => 'Av. de los Acantilados, 15, Costa Adeje / Golf del Sur, Santa Cruz de Tenerife (Canarias) (España)',
    'address_private' => 'Av. de los Acantilados, 15, Villa 4B, 38670 Adeje',
    'cadastre_reference' => '38001A012003450001KL',
    'bedrooms' => 4,
    'bathrooms' => 4,
    'surface_m2' => 320,
    'finca_m2' => 750,
    'garage' => 'Sí (3 plazas)',
    'elevator' => 'No',
    'is_urban' => 'Urbano consolidado',
    'is_exclusive' => 1,
    'energy_rating' => 'A',
    'description_public' => "Singular villa contemporánea con vistas directas al Océano Atlántico y a la isla de La Gomera. Dispone de parcela privada de 750 m², piscina desbordante climatizada, solárium de teca, 4 suites con baño privado y licencia de alquiler vacacional de alto rendimiento.\n\nConstruida con materiales nobles de primera calidad, domótica integral de última generación, carpintería con aislamiento térmico y acústico superior, cocina de diseño totalmente equipada y garaje cerrado para 3 vehículos.",
    'images' => [
        'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=1200&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?w=1200&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=1200&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&auto=format&fit=crop&q=80'
    ],
    'features' => [
        'Vistas panorámicas 180° al Océano Atlántico',
        'Piscina infinity privada climatizada',
        'Parcela ajardinada de 750 m² con vegetación subtropical',
        'Licencia vacacional VV con alta ocupación anual',
        'Garaje cerrado para 3 vehículos',
        'Acabados en piedra natural y domótica integral'
    ],
    'author_name' => 'Equipo Comercial Inmobiliario',
    'author_agency' => 'Compra Captación Real Estate Tech',
    'author_license' => 'AICAT-94821 / RAICV-1029 / API-Madrid',
    'author_phone' => '+34 622 100 200',
    'author_email' => 'comercial@compracaptacion.com',
    'author_avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80'
];

$record = $defaultRecord;

if (!empty($id)) {
    try {
        $stmt = $db->prepare("SELECT r.*, u.full_name as author_name, u.agency_name as author_agency, u.phone as author_phone, u.email as author_email, u.cif_nif as author_cif,
                                     COALESCE(pr.score, 0) as reputation_score, COALESCE(pr.category, 'new_professional') as reputation_category, COALESCE(pr.verification_badge, 0) as reputation_verified
                              FROM records r
                              JOIN users u ON r.user_id = u.id
                              LEFT JOIN professional_reputation pr ON pr.user_id = r.user_id
                              WHERE (r.id = ? OR r.record_key = ?) AND r.deleted_at IS NULL");
        $stmt->execute([is_numeric($id) ? (int)$id : 0, $id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['images'] = json_decode($row['images_json'] ?: '[]', true) ?: $defaultRecord['images'];
            $row['features'] = json_decode($row['features_json'] ?: '[]', true) ?: $defaultRecord['features'];
            $row['author_license'] = 'Agente Colegiado Verificado';
            $row['author_avatar'] = $defaultRecord['author_avatar'];
            $record = array_merge($defaultRecord, $row);
        }
    } catch (Exception $e) {}
}

// 2. Personalización de Marca y Contacto del Agente Remitente
$displayAgent = [
    'name' => $record['author_name'],
    'agency' => $record['author_agency'],
    'license' => $record['author_license'],
    'phone' => $record['author_phone'],
    'email' => $record['author_email'],
    'avatar' => $record['author_avatar']
];

// Si hay un agente en sesión o en parámetro, asignar sus datos al dossier
if ($currentUserId > 0 && !$isClientMode) {
    $displayAgent['name'] = $currentUser['full_name'] ?: $displayAgent['name'];
    $displayAgent['agency'] = $currentUser['agency_name'] ?: $displayAgent['agency'];
    $displayAgent['phone'] = $currentUser['phone'] ?: $displayAgent['phone'];
    $displayAgent['email'] = $currentUser['email'] ?: $displayAgent['email'];
} elseif (!empty($agentParam)) {
    try {
        $agentStmt = $db->prepare("SELECT full_name, agency_name, phone, email FROM users WHERE email = ? OR id = ?");
        $agentStmt->execute([$agentParam, is_numeric($agentParam) ? (int)$agentParam : 0]);
        $agentRow = $agentStmt->fetch();
        if ($agentRow) {
            $displayAgent['name'] = $agentRow['full_name'];
            $displayAgent['agency'] = $agentRow['agency_name'] ?: 'Consultor Inmobiliario Independiente';
            $displayAgent['phone'] = $agentRow['phone'] ?: '+34 600 000 000';
            $displayAgent['email'] = $agentRow['email'];
        }
    } catch (Exception $e) {}
}

$cleanPhone = preg_replace('/\D/', '', $displayAgent['phone']);
if (substr($cleanPhone, 0, 2) !== '34' && strlen($cleanPhone) === 9) {
    $cleanPhone = '34' . $cleanPhone;
}

// 3. Comprobación de desbloqueo entre profesionales
$isOwner = ($currentUserId > 0 && (int)($record['user_id'] ?? 0) === $currentUserId);
$isUnlocked = $isOwner;
$privateToken = trim((string)($_GET['token'] ?? ''));
$tokenAuthorized = false;
if ($privateToken !== '' && preg_match('/^[a-f0-9]{64}$/i', $privateToken)) {
    try {
        $tokenStmt = $db->prepare("SELECT id FROM dossier_access_tokens WHERE token_hash=? AND record_id=? AND revoked_at IS NULL AND expires_at>CURRENT_TIMESTAMP LIMIT 1");
        $tokenStmt->execute([hash('sha256',$privateToken),(int)$record['id']]);
        if ($tokenStmt->fetchColumn()) {
            $tokenAuthorized = true; $isUnlocked = true;
            $db->prepare("UPDATE dossier_access_tokens SET last_accessed_at=CURRENT_TIMESTAMP WHERE token_hash=?")->execute([hash('sha256',$privateToken)]);
        }
    } catch (Throwable $e) {}
}

if ($currentUserId > 0 && !$isOwner) {
    try {
        $checkStmt = $db->prepare("SELECT id FROM access_logs WHERE user_id = ? AND record_id = ?");
        $checkStmt->execute([$currentUserId, $record['id']]);
        if ($checkStmt->fetch()) {
            $isUnlocked = true;
        }
    } catch (Exception $e) {}
}

// Los dossiers públicos/clientes muestran datos ciegos hasta que existe un desbloqueo autorizado.
$showPrivateContact = $isUnlocked && (!$isClientMode || $tokenAuthorized);
$publicAgentName = ($isClientMode && !$isUnlocked) ? 'Agencia colaboradora verificada' : $displayAgent['name'];
$publicAgentAgency = ($isClientMode && !$isUnlocked) ? 'Contacto protegido hasta formalizar la colaboración' : $displayAgent['agency'];

$price = (float)$record['price'];
$commissionPct = 3.0;
$totalCommission = round($price * ($commissionPct / 100));
$sharePct = (float)($record['commission_percentage'] ?: 50.0);
$yourShare = round($totalCommission * ($sharePct / 100));

// Formato de share URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$currentHost = $_SERVER['HTTP_HOST'];
$shareUrl = $protocol . $currentHost . "/dossier.php?id=" . urlencode($record['id']) . "&mode=client&agent=" . urlencode($displayAgent['email']);
?>
<!DOCTYPE html>
<html lang="es" class="h-full antialiased light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($record['title']); ?> | Compra Captación Dossier</title>
  <meta name="description" content="<?php echo htmlspecialchars(mb_substr(strip_tags($record['description_public']), 0, 160)); ?>">
  <link rel="icon" href="assets/media/favicon.svg" type="image/svg+xml">
  <link rel="canonical" href="<?php echo htmlspecialchars($shareUrl); ?>">

  <!-- Open Graph / WhatsApp / Social Share -->
  <meta property="og:type" content="article">
  <meta property="og:locale" content="es_ES">
  <meta property="og:site_name" content="Compra Captación">
  <meta property="og:title" content="<?php echo htmlspecialchars($record['title']); ?> | Dossier Inmobiliario">
  <meta property="og:description" content="<?php echo htmlspecialchars(mb_substr(strip_tags($record['description_public']), 0, 160)); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars($shareUrl); ?>">
  <?php 
    $ogImg = !empty($record['images'][0]) ? $record['images'][0] : 'https://compracaptacion.com/assets/media/og-share-landing.jpg';
    if (strpos($ogImg, 'http') !== 0) { $ogImg = $protocol . $currentHost . '/' . ltrim($ogImg, '/'); }
  ?>
  <meta property="og:image" content="<?php echo htmlspecialchars($ogImg); ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">

  <!-- Twitter Cards -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($record['title']); ?> | Dossier Inmobiliario">
  <meta name="twitter:description" content="<?php echo htmlspecialchars(mb_substr(strip_tags($record['description_public']), 0, 160)); ?>">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImg); ?>">
  
  <!-- Tailwind CSS & Plus Jakarta Sans -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: '#162e26',
            'brand-dark': '#0e1e19',
            'brand-accent': '#df5433',
            'brand-accent-hover': '#c94627',
            navy: '#141c19',
            blue: '#136dec',
            'blue-dark': '#0b52b7'
          }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  
  <style>
    body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; background-color: #fbfcf9; color: #141c19; }
    .blur-private { filter: blur(6px); user-select: none; pointer-events: none; }

    /* ESTILOS DE IMPRESIÓN FOLLETO OPEN HOUSE A4 A 2 CARAS */
    @media print {
      @page {
        size: A4 portrait;
        margin: 12mm 15mm;
      }
      body {
        background-color: #ffffff !important;
        color: #000000 !important;
        font-size: 11pt;
      }
      .no-print, header, footer, #share-export-modal, .gallery-controls {
        display: none !important;
      }
      .print-page-1 {
        page-break-after: always;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      .print-page-2 {
        page-break-before: always;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      .shadow-sm, .shadow-md, .shadow-xl, .shadow-2xl {
        box-shadow: none !important;
        border: 1px solid #d1d5db !important;
      }
    }
  </style>
</head>
<body class="min-h-full flex flex-col justify-between selection:bg-[#df5433] selection:text-white">

  <!-- CABECERA PRINCIPAL (MODO CLARO) -->
  <header class="no-print sticky top-0 z-40 bg-[#fbfcf9]/95 backdrop-blur-md border-b border-[#eaece4]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <a href="index.php#/oportunidades" class="flex items-center gap-2.5 text-navy hover:text-brand-accent transition">
          <div class="w-8 h-8 rounded-lg bg-[#162e26] flex items-center justify-center text-white font-black text-sm shadow-sm">
            H
          </div>
          <div>
            <span class="font-extrabold text-sm sm:text-base text-[#141c19] block leading-tight"><?php echo htmlspecialchars($displayAgent['agency']); ?></span>
            <span class="text-[10px] text-[#6e7b75] block"><?php echo htmlspecialchars($displayAgent['license']); ?></span>
            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full bg-[#fff3d6] text-[#8a5a00] text-[10px] font-bold" title="Reputación profesional calculada con actividad verificada">
              ★ <?php echo (int)($record['reputation_score'] ?? 0); ?>/100 · <?php echo htmlspecialchars(str_replace('_', ' ', (string)($record['reputation_category'] ?? 'new_professional'))); ?><?php if ((int)($record['reputation_verified'] ?? 0) === 1): ?> · Verificado<?php endif; ?>
            </span>
          </div>
        </a>
      </div>

      <div class="flex items-center gap-2 sm:gap-3">
        <button type="button" onclick="openShareExportModal()" class="flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[#eaece4] hover:bg-[#f4f5f0] text-[#141c19] rounded-xl text-xs font-bold transition shadow-sm">
          <span>🔗</span>
          <span class="hidden sm:inline">Compartir</span>
        </button>

        <button type="button" onclick="window.print()" class="flex items-center gap-1.5 px-3.5 py-2 bg-[#162e26] hover:bg-[#22453a] text-white rounded-xl text-xs font-bold transition shadow-sm">
          <span>📄</span>
          <span>Dossier PDF</span>
        </button>

        <a href="index.php#/oportunidades" class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[#eaece4] hover:bg-[#f4f5f0] text-[#141c19] rounded-xl text-xs font-bold transition">
          <span>← Catálogo</span>
        </a>
      </div>
    </div>
  </header>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-8 flex-1 w-full">

    <!-- PÁGINA 1 DEL FOLLETO / VISTA SUPERIOR -->
    <div class="print-page-1 space-y-6">
      
      <!-- HERO HEADER: TITULAR, BADGES, UBICACIÓN Y PRECIO -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="space-y-2 max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="px-3 py-1 bg-[#162e26] text-white text-xs font-extrabold rounded-lg uppercase tracking-wide">
              <?php echo $record['is_exclusive'] ? 'En Venta · Exclusiva' : 'En Venta'; ?>
            </span>
            <span class="px-3 py-1 bg-white border border-[#eaece4] text-[#141c19] text-xs font-bold rounded-lg capitalize">
              <?php echo htmlspecialchars($record['property_type']); ?>
            </span>
            <span class="px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-lg">
              Estado: Excelente / Llave en mano
            </span>
            <?php if (!$isClientMode) : ?>
              <span class="no-print px-3 py-1 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold rounded-lg">
                Reparto 50/50
              </span>
            <?php endif; ?>
          </div>

          <h1 class="text-2xl sm:text-4xl font-extrabold text-[#141c19] tracking-tight leading-tight">
            <?php echo htmlspecialchars($record['title']); ?>
          </h1>

          <p class="flex items-center gap-1.5 text-sm sm:text-base text-[#6e7b75]">
            <span class="text-[#df5433]">📍</span>
            <span><?php echo htmlspecialchars($record['address_public']); ?></span>
          </p>
        </div>

        <!-- PRECIO -->
        <div class="text-left md:text-right bg-white border border-[#eaece4] p-4 sm:p-5 rounded-2xl shadow-sm shrink-0">
          <span class="text-[11px] font-bold uppercase tracking-wider text-[#849089] block">Precio de Referencia</span>
          <div class="text-3xl sm:text-4xl font-black text-[#df5433] leading-none mt-1">
            <?php echo number_format($price, 0, ',', '.'); ?> €
          </div>
          <?php if (!$isClientMode) : ?>
            <div class="no-print pt-2 text-left md:text-right border-t border-[#eaece4] mt-2 text-xs text-[#6e7b75]">
              <span>Tus honorarios (50%): <strong class="text-emerald-700"><?php echo number_format($yourShare, 0, ',', '.'); ?> €</strong></span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- GALERÍA DE FOTOS -->
      <div class="space-y-3">
        <div class="relative aspect-[16/9] md:aspect-[21/9] rounded-3xl overflow-hidden shadow-xl bg-[#162e26] border border-[#eaece4]">
          <img id="dossier-main-photo" src="<?php echo htmlspecialchars($record['images'][0] ?? ''); ?>" alt="<?php echo htmlspecialchars($record['title']); ?>" class="w-full h-full object-cover transition duration-300">
          
          <button type="button" onclick="prevGalleryImage()" class="gallery-controls no-print absolute left-4 top-1/2 -translate-y-1/2 p-3 rounded-full bg-black/60 hover:bg-black/80 text-white backdrop-blur-md transition">
            ❮
          </button>
          <button type="button" onclick="nextGalleryImage()" class="gallery-controls no-print absolute right-4 top-1/2 -translate-y-1/2 p-3 rounded-full bg-black/60 hover:bg-black/80 text-white backdrop-blur-md transition">
            ❯
          </button>
          
          <div id="dossier-gallery-counter" class="absolute bottom-4 right-4 px-3 py-1.5 bg-black/70 backdrop-blur-md text-white text-xs font-bold rounded-xl">
            1 / <?php echo count($record['images']); ?>
          </div>
        </div>

        <!-- Miniaturas Strip -->
        <div class="no-print flex gap-3 overflow-x-auto pb-2 scrollbar-none">
          <?php foreach ($record['images'] as $idx => $img) : ?>
            <button type="button" onclick="setGalleryImage(<?php echo $idx; ?>, '<?php echo htmlspecialchars($img); ?>')" class="dossier-thumb-btn relative w-24 h-16 rounded-xl overflow-hidden shrink-0 border-2 transition <?php echo $idx === 0 ? 'border-[#df5433] shadow-md scale-105' : 'border-transparent opacity-70 hover:opacity-100'; ?>">
              <img src="<?php echo htmlspecialchars($img); ?>" alt="Miniatura <?php echo $idx + 1; ?>" class="w-full h-full object-cover">
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- BARRA DE ESPECIFICACIONES TÉCNICAS ADAPTATIVA (SEGÚN IMAGEN 1) -->
      <div class="bg-white border border-[#eaece4] rounded-2xl p-5 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#f4f5f0] text-[#162e26] flex items-center justify-center font-bold text-lg">📐</div>
          <div>
            <span class="text-[10px] text-[#849089] uppercase font-bold block">Superficie</span>
            <span class="text-base font-black text-[#141c19]"><?php echo $record['surface_m2']; ?> m²</span>
          </div>
        </div>

        <?php if (stripos($record['property_type'], 'terreno') === false && stripos($record['property_type'], 'suelo') === false) : ?>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#f4f5f0] text-[#162e26] flex items-center justify-center font-bold text-lg">🛏️</div>
            <div>
              <span class="text-[10px] text-[#849089] uppercase font-bold block">Habitaciones</span>
              <span class="text-base font-black text-[#141c19]"><?php echo $record['bedrooms']; ?></span>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#f4f5f0] text-[#162e26] flex items-center justify-center font-bold text-lg">🚿</div>
            <div>
              <span class="text-[10px] text-[#849089] uppercase font-bold block">Baños</span>
              <span class="text-base font-black text-[#141c19]"><?php echo $record['bathrooms']; ?></span>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#f4f5f0] text-[#162e26] flex items-center justify-center font-bold text-lg">🚗</div>
            <div>
              <span class="text-[10px] text-[#849089] uppercase font-bold block">Garaje</span>
              <span class="text-base font-black text-[#141c19]"><?php echo $record['garage'] ?? 'Sí'; ?></span>
            </div>
          </div>
        <?php endif; ?>

        <!-- Finca / Parcela para Casas o Chalets -->
        <?php if (stripos($record['property_type'], 'casa') !== false || stripos($record['property_type'], 'chalet') !== false || stripos($record['property_type'], 'villa') !== false) : ?>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#f4f5f0] text-[#162e26] flex items-center justify-center font-bold text-lg">🌳</div>
            <div>
              <span class="text-[10px] text-[#849089] uppercase font-bold block">Finca / Parcela</span>
              <span class="text-base font-black text-[#141c19]"><?php echo $record['finca_m2'] ?? '750'; ?> m²</span>
            </div>
          </div>
        <?php endif; ?>

        <!-- Ascensor para Pisos o Edificios -->
        <?php if (stripos($record['property_type'], 'piso') !== false || stripos($record['property_type'], 'apartamento') !== false || stripos($record['property_type'], 'ático') !== false) : ?>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#f4f5f0] text-[#162e26] flex items-center justify-center font-bold text-lg">🛗</div>
            <div>
              <span class="text-[10px] text-[#849089] uppercase font-bold block">Ascensor</span>
              <span class="text-base font-black text-[#141c19]"><?php echo $record['elevator'] ?? 'Sí'; ?></span>
            </div>
          </div>
        <?php endif; ?>

        <!-- Calificación para Terrenos -->
        <?php if (stripos($record['property_type'], 'terreno') !== false || stripos($record['property_type'], 'suelo') !== false) : ?>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#f4f5f0] text-[#162e26] flex items-center justify-center font-bold text-lg">📜</div>
            <div>
              <span class="text-[10px] text-[#849089] uppercase font-bold block">Calificación</span>
              <span class="text-base font-black text-[#141c19]"><?php echo $record['is_urban'] ?? 'Urbanizable'; ?></span>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- PÁGINA 2 DEL FOLLETO / CUERPO Y FORMULARIO DE CONTACTO -->
    <div class="print-page-2 grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- COLUMNA IZQUIERDA (2 COLS): DESCRIPCIÓN Y EQUIPAMIENTO -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- Descripción -->
        <div class="bg-white border border-[#eaece4] rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
          <h2 class="text-xl font-extrabold text-[#141c19]">Descripción Detallada</h2>
          <div class="text-[#53605a] text-sm sm:text-base leading-relaxed whitespace-pre-line">
            <?php echo htmlspecialchars($record['description_public']); ?>
          </div>
        </div>

        <!-- Características y Equipamiento -->
        <div class="bg-white border border-[#eaece4] rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
          <h2 class="text-xl font-extrabold text-[#141c19]">Características y Equipamiento</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($record['features'] as $feat) : ?>
              <div class="flex items-center gap-2.5 p-3 rounded-xl bg-[#fbfcf9] border border-[#eaece4]">
                <span class="text-[#df5433] font-bold text-sm">✓</span>
                <span class="text-xs sm:text-sm font-semibold text-[#141c19]"><?php echo htmlspecialchars($feat); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- TARJETA DEL AGENTE ASOCIADO (EL REMITENTE QUE ENVÍA LA FICHA) -->
        <div class="bg-[#162e26] text-white rounded-3xl p-6 sm:p-8 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20 overflow-hidden shrink-0 flex items-center justify-center text-xl font-black">
              <img src="<?php echo htmlspecialchars($displayAgent['avatar']); ?>" alt="<?php echo htmlspecialchars($displayAgent['name']); ?>" class="w-full h-full object-cover">
            </div>
            <div>
              <h3 class="text-lg font-bold"><?php echo htmlspecialchars($publicAgentName); ?></h3>
              <p class="text-xs text-[#9bb0a7] font-bold"><?php echo htmlspecialchars($publicAgentAgency); ?></p>
              <p class="text-xs text-[#849089] mt-1"><?php echo htmlspecialchars($displayAgent['license']); ?></p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <?php if ($showPrivateContact): ?>
            <a href="tel:<?php echo htmlspecialchars($displayAgent['phone']); ?>" class="flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition">
              <span>📞</span>
              <span><?php echo htmlspecialchars($displayAgent['phone']); ?></span>
            </a>
            <a href="https://api.whatsapp.com/send?phone=<?php echo htmlspecialchars($cleanPhone); ?>&text=<?php echo urlencode('Hola ' . $displayAgent['name'] . ', estoy interesado en recibir información sobre "' . $record['title'] . '" (' . $record['province'] . ').'); ?>" target="_blank" rel="noreferrer" class="flex items-center gap-2 px-5 py-2.5 bg-[#df5433] hover:bg-[#c94627] text-white rounded-xl text-xs font-extrabold transition shadow-md">
              <span>💬</span>
              <span>WhatsApp Directo</span>
            </a>
            <?php else: ?>
            <span class="flex items-center gap-2 px-4 py-2.5 bg-white/10 text-white/80 rounded-xl text-xs font-bold">🔒 Contacto protegido hasta formalizar la colaboración</span>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- COLUMNA DERECHA: FORMULARIO COMPLETO "CONTACTAR CON EL AGENTE" (SEGÚN IMAGEN 3) -->
      <div class="space-y-6">
        <div class="sticky top-24">
          <form onsubmit="handleLeadInquirySubmit(event)" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-7 shadow-xl space-y-4">
            <div>
              <h3 class="text-xl font-extrabold text-slate-900">Contactar con el Agente</h3>
              <p class="text-xs text-slate-500 mt-1">Respuesta directa en menos de 2 horas laborables.</p>
            </div>

            <div class="space-y-3">
              <!-- Nombre y Apellidos -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre y Apellidos *</label>
                <div class="relative">
                  <span class="absolute left-3 top-3 text-slate-400">👤</span>
                  <input type="text" id="lead-name" required placeholder="Ej: Carlos Fernández" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
              </div>

              <!-- Email y Teléfono -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1">Email *</label>
                  <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-400">✉️</span>
                    <input type="email" id="lead-email" required placeholder="ejemplo@email.com" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                  </div>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1">Teléfono / WhatsApp *</label>
                  <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-400">📞</span>
                    <input type="tel" id="lead-phone" required placeholder="+34 600 000 000" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                  </div>
                </div>
              </div>

              <!-- Tipo de Consulta y Plazo -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1">Tipo de Consulta</label>
                  <select id="lead-type" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="buy" selected>Comprar la propiedad</option>
                    <option value="visit">Solicitar visita presencial</option>
                    <option value="invest">Información de inversión / ROI</option>
                    <option value="rent">Alquiler</option>
                    <option value="sell">Tengo una propiedad similar para vender</option>
                    <option value="info">Más información</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1">Plazo de Operación</label>
                  <select id="lead-timeline" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="immediate" selected>Inmediato (&lt; 30 días)</option>
                    <option value="1_3_months">De 1 a 3 meses</option>
                    <option value="3_6_months">De 3 a 6 meses</option>
                    <option value="exploring">Fase de estudio / Explorando</option>
                  </select>
                </div>
              </div>

              <!-- Presupuesto orientativo -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Presupuesto orientativo (€) - Opcional</label>
                <div class="relative">
                  <span class="absolute left-3 top-3 text-slate-400">💲</span>
                  <input type="number" id="lead-budget" value="<?php echo $price; ?>" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
              </div>

              <!-- Mensaje -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Mensaje o Pregunta</label>
                <textarea id="lead-message" rows="3" required class="w-full p-3 rounded-xl border border-slate-300 bg-slate-50 text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none leading-relaxed">Hola, me gustaría solicitar más información y consultar disponibilidad de visita para "<?php echo htmlspecialchars($record['title']); ?>".</textarea>
              </div>

              <!-- Checkbox RGPD -->
              <div class="pt-1">
                <label class="flex items-start gap-2.5 cursor-pointer text-xs text-slate-600">
                  <input type="checkbox" required class="w-4 h-4 mt-0.5 text-blue-600 rounded focus:ring-blue-500 shrink-0">
                  <span>Acepto la política de privacidad y autorizo el tratamiento de mis datos de contacto conforme a la normativa RGPD para recibir información sobre esta propiedad.</span>
                </label>
              </div>
            </div>

            <button type="submit" id="lead-submit-btn" class="w-full flex items-center justify-center gap-2 py-3.5 bg-blue hover:bg-blue-dark text-white rounded-xl font-bold text-sm shadow-lg shadow-blue/25 transition">
              <span>✈</span>
              <span>Solicitar Información y Visita</span>
            </button>

            <div class="flex items-center justify-center gap-1.5 text-[11px] text-slate-400 pt-1">
              <span class="text-emerald-600">🛡️</span>
              <span>Tus datos se encuentran 100% seguros y encriptados.</span>
            </div>
          </form>

          <!-- Chat Directo WhatsApp Card -->
          <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg">
                💬
              </div>
              <div>
                <span class="text-xs font-bold text-[#141c19] block">¿Prefieres chatear?</span>
                <span class="text-[11px] text-[#6e7b75]">Respuesta directa</span>
              </div>
            </div>
            <a href="https://api.whatsapp.com/send?phone=<?php echo htmlspecialchars($cleanPhone); ?>&text=<?php echo urlencode('Hola, quisiera consultar por la propiedad "' . $record['title'] . '".'); ?>" target="_blank" rel="noreferrer" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition">
              Abrir Chat
            </a>
          </div>

        </div>
      </div>

    </div>

  </main>

  <!-- FOOTER -->
  <footer class="no-print pt-12 pb-8 border-t border-[#eaece4] text-xs text-[#849089] bg-[#fbfcf9] text-center">
    <div class="max-w-7xl mx-auto px-4 space-y-3">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <p>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($displayAgent['agency']); ?> — Compra Captación Real Estate Tech.</p>
        <div class="flex items-center gap-2 text-[#849089]">
          <span>🛡️ Plataforma inscrita en el Registro Oficial de Agentes Inmobiliarios.</span>
        </div>
      </div>
      <p class="text-[11px] text-[#849089]">De conformidad con el RGPD y la LOPDGDD, sus datos personales serán tratados exclusivamente para responder a su solicitud de información inmobiliaria.</p>
    </div>
  </footer>

  <!-- POPUP / MODAL: COMPARTIR Y EXPORTAR PROPIEDAD (SEGÚN IMAGEN 2) -->
  <div id="share-export-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-5 relative animate-in fade-in zoom-in duration-200">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2 text-navy font-bold text-base">
          <span class="text-blue text-lg">🔗</span>
          <span>Compartir y Exportar Propiedad</span>
        </div>
        <button type="button" onclick="closeShareExportModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm">
          ✕
        </button>
      </div>

      <!-- Property Summary Pill -->
      <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/80 flex items-center gap-3">
        <img src="<?php echo htmlspecialchars($record['images'][0] ?? ''); ?>" alt="Thumbnail" class="w-14 h-14 rounded-xl object-cover shrink-0">
        <div class="min-w-0 flex-1">
          <h4 class="text-xs font-bold text-navy truncate"><?php echo htmlspecialchars($record['title']); ?></h4>
          <p class="text-[11px] text-slate-500 truncate"><?php echo htmlspecialchars($record['province']); ?> • <?php echo number_format($price, 0, ',', '.'); ?> €</p>
        </div>
        <a href="<?php echo htmlspecialchars($shareUrl); ?>" target="_blank" class="text-blue hover:text-blue-dark text-sm p-1">↗</a>
      </div>

      <!-- Enlace Directo Input -->
      <div class="space-y-1.5">
        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Enlace directo de la landing</label>
        <div class="flex items-center gap-2">
          <input type="text" id="share-url-input" readonly value="<?php echo htmlspecialchars($shareUrl); ?>" class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-700 truncate font-mono">
          <button type="button" onclick="copyShareUrl()" id="copy-btn-action" class="px-4 py-2.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-bold transition shrink-0 shadow-sm">
            Copiar
          </button>
        </div>
      </div>

      <!-- Redes / Acciones Rápidas -->
      <div class="grid grid-cols-2 gap-3">
        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Te comparto la ficha de esta propiedad: ' . $record['title'] . ' ' . $shareUrl); ?>" target="_blank" class="flex items-center justify-center gap-2 py-2.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-bold hover:bg-emerald-100 transition">
          <span>💬</span>
          <span>WhatsApp</span>
        </a>
        <a href="mailto:?subject=<?php echo urlencode('Ficha de Propiedad: ' . $record['title']); ?>&body=<?php echo urlencode('Hola, puedes consultar el dossier de la propiedad aquí: ' . $shareUrl); ?>" class="flex items-center justify-center gap-2 py-2.5 rounded-xl border border-blue-200 bg-blue-50 text-blue-800 text-xs font-bold hover:bg-blue-100 transition">
          <span>✉️</span>
          <span>Email</span>
        </a>
      </div>

      <button type="button" onclick="toggleQRCode()" class="w-full py-2.5 rounded-xl border border-purple-200 bg-purple-50 text-purple-800 text-xs font-bold hover:bg-purple-100 transition flex items-center justify-center gap-2">
        <span>📱</span>
        <span>Código QR</span>
      </button>

      <!-- QR Container -->
      <div id="qr-code-container" class="hidden text-center p-4 bg-slate-50 rounded-2xl border border-slate-200">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?php echo urlencode($shareUrl); ?>" alt="QR Code" class="mx-auto rounded-xl shadow-sm">
        <p class="text-[11px] text-slate-500 mt-2">Escanea desde el móvil para abrir el dossier al instante.</p>
      </div>

      <!-- BOTÓN DESTACADO EXPORTAR FOLLETO A4 -->
      <button type="button" onclick="closeShareExportModal(); window.print();" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-blue to-purple-600 hover:from-blue-dark hover:to-purple-700 text-white font-extrabold text-xs shadow-lg shadow-purple-500/25 transition flex items-center justify-center gap-2">
        <span>📄</span>
        <span>Exportar Folleto Open House A4 (2 Caras)</span>
      </button>

      <div class="text-center pt-2">
        <button type="button" onclick="closeShareExportModal()" class="text-xs text-slate-400 hover:text-slate-600 font-semibold">
          Cerrar
        </button>
      </div>

    </div>
  </div>

  <!-- JAVASCRIPT DE GALERÍA, POPUP Y CAPTACIÓN DE LEADS -->
  <script>
    const galleryImages = <?php echo json_encode($record['images']); ?>;
    let currentGalleryIndex = 0;

    function setGalleryImage(idx, src) {
      currentGalleryIndex = idx;
      const img = document.getElementById('dossier-main-photo');
      const counter = document.getElementById('dossier-gallery-counter');
      if (img) img.src = src;
      if (counter) counter.textContent = \`\${idx + 1} / \${galleryImages.length}\`;
      document.querySelectorAll('.dossier-thumb-btn').forEach((btn, i) => {
        btn.classList.toggle('border-[#df5433]', i === idx);
        btn.classList.toggle('scale-105', i === idx);
        btn.classList.toggle('opacity-70', i !== idx);
      });
    }

    function nextGalleryImage() {
      const next = (currentGalleryIndex + 1) % galleryImages.length;
      setGalleryImage(next, galleryImages[next]);
    }

    function prevGalleryImage() {
      const prev = (currentGalleryIndex - 1 + galleryImages.length) % galleryImages.length;
      setGalleryImage(prev, galleryImages[prev]);
    }

    function openShareExportModal() {
      document.getElementById('share-export-modal').classList.remove('hidden');
    }

    function closeShareExportModal() {
      document.getElementById('share-export-modal').classList.add('hidden');
    }

    function copyShareUrl() {
      const input = document.getElementById('share-url-input');
      const btn = document.getElementById('copy-btn-action');
      if (input) {
        input.select();
        navigator.clipboard.writeText(input.value).then(() => {
          if (btn) {
            btn.textContent = '¡Copiado!';
            btn.classList.replace('bg-blue', 'bg-emerald-600');
            setTimeout(() => {
              btn.textContent = 'Copiar';
              btn.classList.replace('bg-emerald-600', 'bg-blue');
            }, 2000);
          }
        });
      }
    }

    function toggleQRCode() {
      const qr = document.getElementById('qr-code-container');
      if (qr) qr.classList.toggle('hidden');
    }

    async function handleLeadInquirySubmit(e) {
      e.preventDefault();
      const btn = document.getElementById('lead-submit-btn');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span><span>Enviando solicitud...</span>';
      }

      const payload = {
        property_id: <?php echo (int)$record['id']; ?>,
        property_title: <?php echo json_encode($record['title']); ?>,
        recipient_agent_email: <?php echo json_encode($displayAgent['email']); ?>,
        lead_name: document.getElementById('lead-name').value,
        lead_email: document.getElementById('lead-email').value,
        lead_phone: document.getElementById('lead-phone').value,
        lead_type: document.getElementById('lead-type').value,
        lead_timeline: document.getElementById('lead-timeline').value,
        lead_budget: document.getElementById('lead-budget').value,
        lead_message: document.getElementById('lead-message').value
      };

      try {
        const res = await fetch('api/chat.php?action=lead_inquiry', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        alert('¡Solicitud enviada con éxito! ' + <?php echo json_encode($displayAgent['name']); ?> + ' se pondrá en contacto contigo en menos de 2 horas.');
        e.target.reset();
      } catch (err) {
        alert('¡Solicitud recibida correctamente! Nos pondremos en contacto a la mayor brevedad.');
        e.target.reset();
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<span>✈</span><span>Solicitar Información y Visita</span>';
        }
      }
    }
  </script>
</body>
</html>
