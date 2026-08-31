
    // ==========================================
    // 1. CAT?LOGO TERRITORIAL DE ESPAÑA
    // ==========================================
    let TERRITORY_CATALOG = [];
    let territoryCatalog = [];
    let geoDb = {};
    function hydrateTerritoryCatalog(catalog) {
      territoryCatalog = Array.isArray(catalog) ? catalog : [];
      TERRITORY_CATALOG = territoryCatalog;
      geoDb = territoryCatalog.reduce((acc, community) => {
        acc[community.name] = (community.provinces || []).reduce((provinceAcc, province) => {
          provinceAcc[province.name] = (province.municipalities || []).map(municipality => municipality.name);
          return provinceAcc;
        }, {});
        return acc;
      }, {});
      return territoryCatalog;
    }
    function getEmbeddedTerritoryCatalog() {
      const el = document.getElementById('captacion-territories-data');
      if (el && typeof el.textContent === 'string' && el.textContent.trim()) {
        try {
          const parsed = JSON.parse(el.textContent.trim());
          if (Array.isArray(parsed) && parsed.length >= 17) return parsed;
        } catch (e) {}
      }
      return null;
    }

    async function captacionLoadTerritoryCatalog() {
      if (territoryCatalog.length) return territoryCatalog;
      const embedded = getEmbeddedTerritoryCatalog();
      if (embedded) {
        return hydrateTerritoryCatalog(embedded);
      }
      const endpoint = CAPTACION_MAILCHIMP?.territoriesEndpoint || '/api/territories.php';
      try {
        const response = await fetch(endpoint, { credentials:'same-origin', headers: { 'X-WP-Nonce': CAPTACION_MAILCHIMP?.nonce || '' } });
        if (!response.ok) throw new Error('territory_catalog_failed');
        const payload = await response.json();
        const catalogData = payload.data || payload.catalog || payload.normalized || (Array.isArray(payload) ? payload : []);
        return hydrateTerritoryCatalog(catalogData);
      } catch (error) {
        console.warn('No se pudo cargar el catálogo territorial desde API, usando fallback local.', error);
        return hydrateTerritoryCatalog([]);
      }
    }

    function normalizeTerritoryText(value = '') {
      return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }

    const TERRITORY_NAME_ALIASES = {
      'andalucia': 'andalucia',
      'aragon': 'aragon',
      'asturias': 'asturias',
      'baleares': 'illes balears',
      'illes balears': 'illes balears',
      'canarias': 'canarias',
      'cantabria': 'cantabria',
      'castilla y leon': 'castilla y leon',
      'castilla-la mancha': 'castilla-la mancha',
      'castilla la mancha': 'castilla-la mancha',
      'cataluna': 'cataluna',
      'comunidad valenciana': 'comunidad valenciana',
      'extremadura': 'extremadura',
      'galicia': 'galicia',
      'la rioja': 'la rioja',
      'madrid': 'comunidad de madrid',
      'comunidad de madrid': 'comunidad de madrid',
      'murcia': 'region de murcia',
      'region de murcia': 'region de murcia',
      'navarra': 'comunidad foral de navarra',
      'comunidad foral de navarra': 'comunidad foral de navarra',
      'pais vasco': 'pais vasco',
      'ceuta': 'ciudad autonoma de ceuta',
      'ciudad autonoma de ceuta': 'ciudad autonoma de ceuta',
      'melilla': 'ciudad autonoma de melilla',
      'ciudad autonoma de melilla': 'ciudad autonoma de melilla'
    };

    const PROVINCE_NAME_ALIASES = {
      'a coruna': 'a coruna',
      'araba': 'araba/alava',
      'alava': 'araba/alava',
      'castellon': 'castellon',
      'guipuzcoa': 'gipuzkoa',
      'vizcaya': 'bizkaia',
      'valencia': 'valencia',
      'ourense': 'ourense',
      'coruna': 'a coruna'
    };

    function getSortedCommunities() {
      return [...territoryCatalog].sort((a, b) => a.name.localeCompare(b.name, 'es'));
    }

    function getCommunityByName(name = '') {
      const normalized = TERRITORY_NAME_ALIASES[normalizeTerritoryText(name)] || normalizeTerritoryText(name);
      return territoryCatalog.find(item => normalizeTerritoryText(item.name) === normalized) || null;
    }

    function getProvinceByName(communityName = '', provinceName = '') {
      const community = getCommunityByName(communityName);
      if (!community) return null;
      const normalized = PROVINCE_NAME_ALIASES[normalizeTerritoryText(provinceName)] || normalizeTerritoryText(provinceName);
      return (community.provinces || []).find(item => {
        const candidate = normalizeTerritoryText(item.name);
        return candidate === normalized || candidate.split('/').includes(normalized);
      }) || null;
    }

    function getMunicipalityByName(communityName = '', provinceName = '', municipalityName = '') {
      const province = getProvinceByName(communityName, provinceName);
      if (!province) return null;
      const normalized = normalizeTerritoryText(municipalityName);
      return (province.municipalities || []).find(item => {
        const candidate = normalizeTerritoryText(item.name);
        return candidate === normalized || candidate.split('/').includes(normalized);
      }) || null;
    }


    class TerritorySelector {
      static instances = {};
      static existing = {};

      constructor({ name, ccaaId, provinceId, municipalityId, postalCodeId = '', allowAll = false, onChange = null }) {
        this.name = name;
        this.ccaa = document.getElementById(ccaaId);
        this.province = document.getElementById(provinceId);
        this.municipality = document.getElementById(municipalityId);
        this.postalCode = postalCodeId ? document.getElementById(postalCodeId) : null;
        this.allowAll = allowAll;
        this.onChange = onChange;
        if (!this.ccaa || !this.province || !this.municipality) return;
        this.populateCommunities();
        this.ccaa.addEventListener('change', () => { this.populateProvinces(); this.emitChange(); });
        this.province.addEventListener('change', () => { this.populateMunicipalities(); this.emitChange(); });
        this.municipality.addEventListener('change', () => { this.applyPostalCodes(); this.emitChange(); });
        TerritorySelector.instances[name] = this;
      }

      option(value, label) { return `<option value="${escapeHTML(value)}">${escapeHTML(label)}</option>`; }
      placeholder(label) { return this.option(this.allowAll ? 'all' : '', label); }

      populateCommunities(selected = '') {
        if (!this.ccaa) return;
        const current = selected || this.ccaa.dataset.initialValue || this.ccaa.value;
        this.ccaa.innerHTML = this.placeholder(this.allowAll ? 'Todas las CCAA' : 'Selecciona una comunidad autónoma') + getSortedCommunities().map(item => this.option(item.name, item.name)).join('');
        if (current) this.ccaa.value = current;
        this.populateProvinces('', false);
      }

      populateProvinces(selected = '', resetMunicipality = true) {
        const community = getCommunityByName(this.ccaa?.value || '');
        const current = selected || this.province?.dataset.initialValue || this.province?.value || '';
        const provinces = community ? [...(community.provinces || [])].sort((a,b)=>a.name.localeCompare(b.name,'es')) : [];
        this.province.innerHTML = this.placeholder(this.allowAll ? 'Todas las provincias' : 'Selecciona una provincia') + provinces.map(item => this.option(item.name, item.name)).join('');
        this.province.disabled = !community;
        if (current) this.province.value = current;
        this.populateMunicipalities('', resetMunicipality);
      }

      populateMunicipalities(selected = '', reset = true) {
        const province = getProvinceByName(this.ccaa?.value || '', this.province?.value || '');
        const current = selected || this.municipality?.dataset.initialValue || (!reset ? this.municipality?.value : '') || '';
        const municipalities = province ? [...(province.municipalities || [])].sort((a,b)=>a.name.localeCompare(b.name,'es')) : [];
        this.municipality.innerHTML = this.placeholder(this.allowAll ? 'Todos los municipios' : 'Selecciona un municipio') + municipalities.map(item => this.option(item.name, item.name)).join('');
        this.municipality.disabled = !province;
        if (current) this.municipality.value = current;
        this.applyPostalCodes(false);
      }

      applyPostalCodes(overwrite = true) {
        if (!this.postalCode) return;
        const municipality = getMunicipalityByName(this.ccaa?.value || '', this.province?.value || '', this.municipality?.value || '');
        const codes = Array.isArray(municipality?.postalCodes) ? municipality.postalCodes : [];
        this.postalCode.dataset.validPostalCodes = codes.join(',');
        if (overwrite && codes.length === 1 && !this.postalCode.value) this.postalCode.value = codes[0];
        this.postalCode.placeholder = codes.length ? `Ej.: ${codes[0]}` : 'Código postal (5 dígitos)';
      }

      setValues(values = {}) {
        if (!this.ccaa) return;
        this.ccaa.value = values.ccaa || values.autonomousCommunity || '';
        this.populateProvinces(values.province || '', false);
        this.populateMunicipalities(values.municipality || '', false);
        if (this.postalCode && values.postalCode) this.postalCode.value = values.postalCode;
      }

      getValue() {
        const territory = resolveTerritorySelection(this.ccaa?.value || '', this.province?.value || '', this.municipality?.value || '');
        return { ...territory, ccaa:this.ccaa?.value || '', province:this.province?.value || '', municipality:this.municipality?.value || '', postalCode:cleanText(this.postalCode?.value || '') };
      }

      emitChange() { if (typeof this.onChange === 'function') this.onChange(this.getValue()); }

      static attachExisting(name, ids) { TerritorySelector.existing[name] = ids; }
    }

    function initTerritorySelectors() {
      new TerritorySelector({ name:'fiscal-profile', ccaaId:'fiscal-ccaa', provinceId:'fiscal-province', municipalityId:'fiscal-municipality', postalCodeId:'fiscal-postal-code' });
      new TerritorySelector({ name:'marketplace-filter', ccaaId:'market-ccaa-filter', provinceId:'market-province-filter', municipalityId:'market-municipality-filter', postalCodeId:'market-postal-code-filter', allowAll:true, onChange:()=>refreshMarketplaceView() });
      new TerritorySelector({ name:'sales-filter', ccaaId:'sales-match-ccaa', provinceId:'sales-match-province', municipalityId:'sales-match-municipality', allowAll:true, onChange:()=>renderSalesMatches() });
      new TerritorySelector({ name:'offer-form', ccaaId:'offer-ccaa-sel', provinceId:'offer-province-sel', municipalityId:'offer-municipality-sel', postalCodeId:'offer-postal-code' });
      new TerritorySelector({ name:'need-form', ccaaId:'need-pub-ccaa-sel', provinceId:'need-pub-province-sel', municipalityId:'need-pub-municipality-sel', postalCodeId:'need-pub-postal-code' });
      new TerritorySelector({ name:'needs-filter', ccaaId:'need-filter-ccaa', provinceId:'need-filter-province', municipalityId:'need-filter-municipality', postalCodeId:'need-filter-postal-code', allowAll:true });
    }

    function maskPublicPostalCode(value = '') {
      const code = String(value || '').replace(/\D/g, '').slice(0,5);
      return code.length === 5 ? `${code.slice(0,2)}***` : 'Zona protegida';
    }

    async function validateAddressWithCartoCiudad({ address = '', postalCode = '', municipality = '', province = '' } = {}) {
      if (!CAPTACION_MAILCHIMP?.territoryValidationEndpoint) return { ok:false, results:[] };
      try {
        const response = await fetch(CAPTACION_MAILCHIMP.territoryValidationEndpoint, { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body:JSON.stringify({address,postalCode,municipality,province}) });
        if (!response.ok) throw new Error('validation_failed');
        return await response.json();
      } catch (error) { return { ok:false, results:[] }; }
    }

    async function validateFiscalAddress() {
      const selector = TerritorySelector.instances['fiscal-profile']; const value = selector?.getValue() || {};
      const status = document.getElementById('fiscal-address-validation-status'); if (status) status.textContent = 'Validando con CartoCiudad/CNIG...';
      const result = await validateAddressWithCartoCiudad({ address:document.getElementById('fiscal-address')?.value || '', postalCode:value.postalCode, municipality:value.municipality, province:value.province });
      if (status) status.textContent = result.ok && result.results?.length ? 'Dirección fiscal validada de forma aproximada.' : 'No se encontró una coincidencia oficial. Puedes guardar y revisarla manualmente.';
    }

    // ==========================================
    // 2. COORDENADAS GEOGRÁFICAS (mapa público)
    // ==========================================
    // Coordenadas aproximadas para el mapa público. En producción deberán llegar del backend geoespacial.
    const geoCenters = {
      "Andalucía": [37.45, -4.65], "Aragón": [41.35, -0.65], "Asturias": [43.36, -5.85],
      "Baleares": [39.60, 2.95], "Canarias": [28.30, -15.70], "Cantabria": [43.18, -4.05],
      "Castilla y León": [41.65, -4.75], "Castilla-La Mancha": [39.50, -3.00], "Cataluña": [41.75, 1.65],
      "Comunidad Valenciana": [39.50, -0.55], "Extremadura": [39.15, -6.15], "Galicia": [42.75, -8.00],
      "La Rioja": [42.30, -2.50], "Madrid": [40.42, -3.70], "Murcia": [37.98, -1.13],
      "Navarra": [42.70, -1.65], "País Vasco": [43.00, -2.55], "Ceuta": [35.89, -5.32], "Melilla": [35.29, -2.94],
      "Ourense": [42.34, -7.86], "Madrid ciudad": [40.42, -3.70], "Barcelona": [41.39, 2.17],
      "Elche": [38.27, -0.70], "Pozuelo de Alarcón": [40.44, -3.81], "Vigo": [42.24, -8.72],
      "Valencia": [39.47, -0.38], "A Coruña": [43.37, -8.40], "Sevilla": [37.39, -5.99], "Málaga": [36.72, -4.42]
    };

    // ==========================================
    // 3. MAPA DE RUTAS Y VARIABLES DE ESTADO GLOBALES
    // ==========================================
    window.CAPTACION_CONFIG = window.CAPTACION_CONFIG || {};
    const CAPTACION_BASE_PATH = window.CAPTACION_CONFIG.basePath || '/';
    
    const routes = {
      '/inicio': 'page-inicio',
      '/': 'page-inicio',
      '/propiedades': 'page-marketplace',
      '/marketplace': 'page-marketplace',
      '/marketplace/': 'page-marketplace',
      '/marketplace/propiedades': 'page-marketplace',
      '/publicar': 'page-publicar',
      '/marketplace/publicar': 'page-publicar',
      '/marketplace/ofrecer-captacion': 'page-publicar',
      '/marketplace/publicar-propiedad': 'page-publicar',
      '/marketplace/compartir-captacion': 'page-publicar',
      '/marketplace/demandas': 'page-buscar-captaciones',
      '/marketplace/publicar-demanda': 'page-publicar',
      '/demandas': 'page-buscar-captaciones',
      '/buscar-captaciones': 'page-buscar-captaciones',
      '/publicar-propiedad': 'page-publicar',
      '/ofrecer-captacion': 'page-publicar',
      '/compartir-captacion': 'page-publicar',
      '/publicar-demanda': 'page-publicar',
      '/oportunidades': 'page-marketplace',
      '/marketplace/oportunidades': 'page-marketplace',
      '/como-funciona': 'page-como-funciona',
      '/marketplace/como-funciona': 'page-como-funciona',
      '/precios': 'page-planes',
      '/marketplace/precios': 'page-planes',
      '/planes': 'page-planes',
      '/marketplace/planes': 'page-planes',
      '/recursos': 'page-recursos',
      '/marketplace/recursos': 'page-recursos',
      '/coincidencias-ventas': 'page-coincidencias-ventas',
      '/marketplace/coincidencias-ventas': 'page-coincidencias-ventas',
      '/contacto': 'page-contacto',
      '/marketplace/contacto': 'page-contacto',
      '/aviso-legal': 'page-aviso-legal',
      '/marketplace/aviso-legal': 'page-aviso-legal',
      '/privacidad': 'page-privacidad',
      '/marketplace/privacidad': 'page-privacidad',
      '/cookies': 'page-cookies',
      '/marketplace/cookies': 'page-cookies',
      '/normas-publicacion': 'page-normas-publicacion',
      '/marketplace/normas-publicacion': 'page-normas-publicacion',
      '/condiciones-de-contratacion': 'page-condiciones-de-contratacion',
      '/marketplace/condiciones-de-contratacion': 'page-condiciones-de-contratacion',
      '/politica-reembolsos': 'page-politica-reembolsos',
      '/marketplace/politica-reembolsos': 'page-politica-reembolsos',
      '/datos-ciegos': 'page-datos-ciegos',
      '/marketplace/datos-ciegos': 'page-datos-ciegos',
      '/canal-de-denuncias': 'page-canal-de-denuncias',
      '/marketplace/canal-de-denuncias': 'page-canal-de-denuncias',
      '/area-privada': 'page-area-privada',
      '/area-privada/resumen': 'page-area-privada',
      '/area-privada/mis-captaciones': 'page-area-privada',
      '/area-privada/mis-demandas': 'page-area-privada',
      '/area-privada/creditos': 'page-area-privada',
      '/area-privada/solicitudes': 'page-area-privada',
      '/area-privada/operaciones': 'page-area-privada',
      '/area-privada/favoritos': 'page-area-privada',
      '/area-privada/calendario': 'page-area-privada',
      '/area-privada/suscripciones': 'page-area-privada',
      '/area-privada/mensajes': 'page-area-privada',
      '/area-privada/trazabilidad': 'page-area-privada',
      '/area-privada/importaciones': 'page-area-privada',
      '/area-privada/ia-configuracion': 'page-area-privada',
      '/area-privada/perfil': 'page-area-privada',
      '/marketplace/area-privada': 'page-area-privada'
    };

    // Las acciones rápidas del área privada usan enlaces #/ruta. El router debe
    // resolverlos antes que el pathname para que publicar una captación o una
    // demanda funcione también tras recargar o usar Atrás/Adelante.
    function getCurrentAppRoutePath() {
      const hashRoute = String(window.location.hash || '').split('?')[0];
      let pathname = /^#\/[a-z0-9-]+$/i.test(hashRoute) ? hashRoute.substring(1) : window.location.pathname;
      if (CAPTACION_BASE_PATH && CAPTACION_BASE_PATH !== '/' && pathname.startsWith(CAPTACION_BASE_PATH)) {
        pathname = pathname.substring(CAPTACION_BASE_PATH.length);
      }
      pathname = '/' + pathname.replace(/^\/+|\/+$/g, '');
      if (pathname === '/' || pathname === '/index.php' || !pathname) {
        return '/inicio';
      }
      if (!routes[pathname]) {
        const segments = pathname.split('/').filter(Boolean);
        for (let i = segments.length - 1; i >= 0; i--) {
          const testRoute = '/' + segments[i];
          if (routes[testRoute]) {
            return testRoute;
          }
        }
      }
      return routes[pathname] ? pathname : '/inicio';
    }

    function navigateTo(path) {
      if (!path.startsWith('/')) path = '/' + path;
      if (path.startsWith('/marketplace/') && path !== '/marketplace') {
        const stripped = path.substring('/marketplace'.length);
        if (routes[stripped]) path = stripped;
      }
      const fullPath = (CAPTACION_BASE_PATH && CAPTACION_BASE_PATH !== '/' ? CAPTACION_BASE_PATH.replace(/\/+$/, '') : '') + path;
      window.history.pushState(null, '', fullPath);
      handleRoute();
    }

    // Stripe Payment Link real. Sustituye este valor por el enlace creado en Stripe.
    const STRIPE_PAYMENT_LINK_URL = window.CAPTACION_CONFIG?.stripePaymentLink || '';
    const STRIPE_MEMBERSHIP_LINKS = window.CAPTACION_CONFIG?.membershipLinks || {};
    const STRIPE_PROFESSIONAL_PLUS_URL = '';
    const STRIPE_PREMIUM_URL = STRIPE_MEMBERSHIP_LINKS?.premium || '';
    const STRIPE_PAYMENT_PRODUCT_NAME = 'Desbloqueo de captacion profesional';
    const CAPTACION_MAILCHIMP = window.CAPTACION_CONFIG?.mailchimp || window.CAPTACION_MAILCHIMP || {};
    const CAPTACION_SESSION_IMAGE = window.CAPTACION_CONFIG?.sessionImage || '';
    window.CAPTACION_API = window.CAPTACION_CONFIG?.api || window.CAPTACION_API || { nonce: '', endpoints: {} };

    let currentNeedsLayout = 'bloque';
    let currentHash = '#/inicio';
    let tempPropertyToPublish = null; // Almacén temporal para previsualización
    let uploadedFileBase64 = null; // Almacén temporal de la imagen Base64
    let pendingAuthorizedListingImport = null;
    let homeMap = null;
    let homeMapLayer = null;
    let homeMapMode = 'all';
    let homeMapSelectionLayer = null;
    let homeMapSelectedBounds = null;
    let homeMapDrawHandler = null;
    let homeMapPostalCodeFilter = '';
    let marketplaceMap = null;
    let marketplaceMapLayer = null;
    let marketplaceMapSelectionLayer = null;
    let marketplaceMapSelectedBounds = null;
    let marketplaceMapDrawHandler = null;
    let marketplaceViewMode = 'cards';
    let marketplaceLayoutMode = 'block';
    let needsMap = null;
    let needsMapLayer = null;
    let needsMapVisible = false;
    let needsMapSelectionLayer = null;
    let needsMapSelectedBounds = null;
    let needsMapDrawHandler = null;
    let needsMapPostalCodeFilter = '';
    let lastFilteredNeeds = [];
    const LIST_BATCH_SIZE = 12;
    const MARKETPLACE_CAROUSEL_SIZE = 4;
    let marketplaceVisibleLimit = LIST_BATCH_SIZE;
    let marketplaceCurrentPage = 1;
    let marketplaceCarouselOffset = 0;
    let needsVisibleLimit = LIST_BATCH_SIZE;
    let needsCurrentPage = 1;
    const SPAIN_DEFAULT_MAP_CENTER = [40.1, -3.7];
    const SPAIN_DEFAULT_MAP_ZOOM = 5.7;

    // Imágenes virtuales ligeras por tipo de inmueble para la demo y para captaciones sin fotografía.
    // Se generan como SVG embebidos para evitar archivos pesados y conservar una carga rápida.
    const VIRTUAL_IMAGE_PRESETS = {
      'Piso': { label: 'Piso', from: '#143c6d', to: '#4b8fd8', icon: '<rect x="298" y="210" width="304" height="380" rx="18" fill="none" stroke="#ffffff" stroke-width="26"/><path d="M350 285h58m84 0h58m-200 88h58m84 0h58m-200 88h58m84 0h58" stroke="#ffffff" stroke-width="22" stroke-linecap="round"/><path d="M420 590V490h60v100" fill="none" stroke="#ffffff" stroke-width="24"/>' },
      'Casa/Chalet': { label: 'Casa / Chalet', from: '#1b5e57', to: '#61b89f', icon: '<path d="M235 418 450 238l215 180v190a35 35 0 0 1-35 35H270a35 35 0 0 1-35-35Z" fill="none" stroke="#ffffff" stroke-width="28" stroke-linejoin="round"/><path d="M385 642V486h130v156" fill="none" stroke="#ffffff" stroke-width="28"/><path d="M555 324v-76h58v124" fill="none" stroke="#ffffff" stroke-width="24"/>' },
      'Local Comercial': { label: 'Local comercial', from: '#7a3d16', to: '#e49a4b', icon: '<rect x="222" y="284" width="456" height="350" rx="24" fill="none" stroke="#ffffff" stroke-width="26"/><path d="M205 340h490l-48-94H253Z" fill="none" stroke="#ffffff" stroke-width="26" stroke-linejoin="round"/><path d="M300 634V438h300v196M300 438h300" fill="none" stroke="#ffffff" stroke-width="24"/>' },
      'Nave': { label: 'Nave industrial', from: '#334155', to: '#728197', icon: '<path d="M206 414 450 242l244 172v220H206Z" fill="none" stroke="#ffffff" stroke-width="28" stroke-linejoin="round"/><path d="M315 634V450h270v184M360 510h180m-180 64h180" fill="none" stroke="#ffffff" stroke-width="24"/>' },
      'Oficina': { label: 'Oficina', from: '#3e3b79', to: '#8179c7', icon: '<rect x="285" y="208" width="330" height="430" rx="18" fill="none" stroke="#ffffff" stroke-width="26"/><path d="M350 280h50m100 0h50m-200 85h50m100 0h50m-200 85h50m100 0h50m-125 188V520h50v118" stroke="#ffffff" stroke-width="22" stroke-linecap="round"/>' },
      'Edificio': { label: 'Edificio', from: '#12344d', to: '#427899', icon: '<path d="M248 638V258h260v380M508 638V330h145v308" fill="none" stroke="#ffffff" stroke-width="27" stroke-linejoin="round"/><path d="M312 326h54m82 0h-30m-106 82h54m82 0h-30m-106 82h54m82 0h-30m151-92h34m-34 82h34m-34 82h34" stroke="#ffffff" stroke-width="21" stroke-linecap="round"/>' },
      'Suelo/Terreno': { label: 'Suelo / Terreno', from: '#47672a', to: '#91bd54', icon: '<path d="M160 606 328 410l116 118 122-170 174 248Z" fill="none" stroke="#ffffff" stroke-width="28" stroke-linejoin="round"/><circle cx="642" cy="252" r="54" fill="none" stroke="#ffffff" stroke-width="25"/><path d="M186 650h528" stroke="#ffffff" stroke-width="26" stroke-linecap="round"/>' },
      'Garaje': { label: 'Garaje', from: '#374151', to: '#7b8796', icon: '<rect x="220" y="264" width="460" height="370" rx="26" fill="none" stroke="#ffffff" stroke-width="26"/><path d="M292 634V420h316v214M292 490h316m-210 70h104" fill="none" stroke="#ffffff" stroke-width="24" stroke-linecap="round"/>' },
      'Activo inmobiliario': { label: 'Activo inmobiliario', from: '#10233c', to: '#1b67d6', icon: '<path d="M210 458 450 262l240 196v224a42 42 0 0 1-42 42H252a42 42 0 0 1-42-42V458Z" fill="none" stroke="#ffffff" stroke-width="34" stroke-linejoin="round"/><path d="M375 724V514h150v210" fill="none" stroke="#ffffff" stroke-width="34" stroke-linejoin="round"/>' }
    };

    function normalizeVirtualPropertyType(type = '') {
      const value = cleanText(type || '').toLowerCase();
      if (value.includes('piso') || value.includes('apartamento') || value.includes('estudio')) return 'Piso';
      if (value.includes('casa') || value.includes('chalet') || value.includes('villa')) return 'Casa/Chalet';
      if (value.includes('local') || value.includes('comercial')) return 'Local Comercial';
      if (value.includes('nave') || value.includes('industrial')) return 'Nave';
      if (value.includes('oficina') || value.includes('despacho')) return 'Oficina';
      if (value.includes('edificio')) return 'Edificio';
      if (value.includes('suelo') || value.includes('terreno') || value.includes('parcela') || value.includes('solar')) return 'Suelo/Terreno';
      if (value.includes('garaje') || value.includes('parking')) return 'Garaje';
      return 'Activo inmobiliario';
    }

    function buildVirtualMarketplaceImage(type = 'Activo inmobiliario') {
      const normalizedType = normalizeVirtualPropertyType(type);
      const preset = VIRTUAL_IMAGE_PRESETS[normalizedType] || VIRTUAL_IMAGE_PRESETS['Activo inmobiliario'];
      return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 900">
          <defs>
            <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="${preset.from}"/>
              <stop offset="100%" stop-color="${preset.to}"/>
            </linearGradient>
          </defs>
          <rect width="900" height="900" fill="url(#bg)"/>
          <circle cx="744" cy="148" r="170" fill="#ffffff" opacity="0.08"/>
          <circle cx="118" cy="760" r="240" fill="#ffffff" opacity="0.06"/>
          <rect x="50" y="54" width="232" height="62" rx="31" fill="#ffffff" opacity="0.94"/>
          <text x="166" y="94" text-anchor="middle" fill="#10233c" font-family="Arial, sans-serif" font-size="27" font-weight="700">Imagen virtual</text>
          <g opacity="0.97">${preset.icon}</g>
          <text x="450" y="790" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="46" font-weight="700">${preset.label}</text>
          <text x="450" y="842" text-anchor="middle" fill="#ffffff" opacity="0.85" font-family="Arial, sans-serif" font-size="28" font-weight="600">Compra Captación</text>
        </svg>`)};`;
    }

    const VIRTUAL_MARKETPLACE_IMAGES = Object.fromEntries(Object.keys(VIRTUAL_IMAGE_PRESETS).map(type => [type, buildVirtualMarketplaceImage(type)]));
    const _mediaDefs = (window.CAPTACION_CONFIG && window.CAPTACION_CONFIG.mediaDefaults) || {};
    const DEFAULT_PROPERTY_IMAGES = {
      'Piso': _mediaDefs.piso || '',
      'Casa/Chalet': _mediaDefs.casa_chalet || '',
      'Local Comercial': _mediaDefs.comercial || '',
      'Nave': _mediaDefs.nave || '',
      'Oficina': _mediaDefs.oficina || '',
      'Edificio': _mediaDefs.edificio || '',
      'Suelo/Terreno': _mediaDefs.terreno || '',
      'Garaje': _mediaDefs.piso || '',
      'Activo inmobiliario': _mediaDefs.piso || ''
    };
    function getVirtualMarketplaceImage(type = '') {
      const normalizedType = normalizeVirtualPropertyType(type);
      return DEFAULT_PROPERTY_IMAGES[normalizedType] || DEFAULT_PROPERTY_IMAGES['Activo inmobiliario'];
    }
    function getGeneratedMarketplaceImage(type = '') {
      const normalizedType = normalizeVirtualPropertyType(type);
      return VIRTUAL_MARKETPLACE_IMAGES[normalizedType] || VIRTUAL_MARKETPLACE_IMAGES['Activo inmobiliario'];
    }
    function handleMarketplaceImageError(imageElement) {
      if (!imageElement) return;
      imageElement.onerror = null;
      imageElement.src = getVirtualMarketplaceImage(imageElement.dataset.virtualType || 'Activo inmobiliario');
    }
    const DEFAULT_MARKETPLACE_IMAGE = getVirtualMarketplaceImage('Activo inmobiliario');
    window.DEFAULT_MARKETPLACE_IMAGE = DEFAULT_MARKETPLACE_IMAGE;
    window.getVirtualMarketplaceImage = getVirtualMarketplaceImage;
    window.getGeneratedMarketplaceImage = getGeneratedMarketplaceImage;
    window.handleMarketplaceImageError = handleMarketplaceImageError;
    const MAX_MARKETPLACE_IMAGE_SIZE = 900;
    const MARKETPLACE_IMAGE_QUALITY = 0.78;
    const CAPTACION_PRODUCTION_MODE = true;

    // ==========================================
    // 4. CARGA DE LA BASE DE DATOS LOCAL
    // ==========================================
    let properties = [];
    if (!CAPTACION_PRODUCTION_MODE) {
      try { properties = JSON.parse(localStorage.getItem('captacion_properties_v3')); } catch (e) {}
    }
    if (!Array.isArray(properties)) properties = [];

    let needs = [];
    if (!CAPTACION_PRODUCTION_MODE) {
      try { needs = JSON.parse(localStorage.getItem('captacion_needs_v3')); } catch (e) {}
    }
    if (!Array.isArray(needs)) needs = [];

    let closedOperations = [];
    if (!CAPTACION_PRODUCTION_MODE) {
      try { closedOperations = JSON.parse(localStorage.getItem('captacion_closed_operations_v4')); } catch (e) {}
    }
    if (!Array.isArray(closedOperations)) closedOperations = [];

    properties = properties.map((property, index) => normalizePropertyRecord(property, index));
    needs = needs.map((need, index) => normalizeNeedRecord(need, index));

    if (!CAPTACION_PRODUCTION_MODE && !localStorage.getItem('captacion_production_cleanup_v3')) {
      const legacyPropertyIds = new Set(['prop-1', 'prop-2', 'prop-3']);
      const legacyNeedIds = new Set(['need-1', 'need-2', 'need-3']);
      const isLegacyProperty = p => p.demoBatch || String(p.id).startsWith('demo-') || legacyPropertyIds.has(String(p.id));
      const isLegacyNeed = n => n.demoBatch || String(n.id).startsWith('demo-') || legacyNeedIds.has(String(n.id));
      const oldPropsCount = properties.filter(isLegacyProperty).length;
      const oldNeedsCount = needs.filter(isLegacyNeed).length;
      if (oldPropsCount || oldNeedsCount) {
        properties = properties.filter(p => !isLegacyProperty(p));
        needs = needs.filter(n => !isLegacyNeed(n));
        closedOperations = [];
        try { localStorage.removeItem('captacion_properties_v3'); } catch (e) {}
        try { localStorage.removeItem('captacion_needs_v3'); } catch (e) {}
        try { localStorage.removeItem('captacion_closed_operations_v4'); } catch (e) {}
        try { localStorage.removeItem('captacion_agent_private_dashboard_v2'); } catch (e) {}
        try { localStorage.removeItem('captacion_internal_communications_v1'); } catch (e) {}
        try { localStorage.removeItem('captacion_spain_scale_demo_v1'); } catch (e) {}
        try { localStorage.removeItem('captacion_requested_demo_v1'); } catch (e) {}
        try { localStorage.removeItem('captacion_demo_owners_v1'); } catch (e) {}
        try { localStorage.removeItem('captacion_demo_demanders_v1'); } catch (e) {}
      }
      try { localStorage.removeItem('captacion_agent_private_dashboard_v2'); } catch (e) {}
      try { localStorage.removeItem('captacion_internal_communications_v1'); } catch (e) {}
      try { localStorage.setItem('captacion_production_cleanup_v3', '1'); } catch (e) {}
    }
    if (!CAPTACION_PRODUCTION_MODE) persistDemoState();


    // ==========================================
    // 5. ENRUTADOR INTERNO (CON RESPALDO SEGURO ANTE SANDBOX IFRAMES)
    // ==========================================
    function handleRoute() {
      try {
        let pathname = getCurrentAppRoutePath();

        currentHash = pathname;

        const routeTitles = {
          '/inicio': 'Compra Captación | Plataforma de Colaboración entre Profesionales Inmobiliarios',
          '/propiedades': 'Inmuebles en Colaboración | Cartera Compartida | Compra Captación',
          '/marketplace': 'Inmuebles en Colaboración | Cartera Compartida | Compra Captación',
          '/marketplace/propiedades': 'Inmuebles en Colaboración | Cartera Compartida | Compra Captación',
          '/marketplace/ofrecer-captacion': 'Publicar Captación en Colaboración | Compra Captación',
          '/marketplace/publicar-propiedad': 'Publicar Captación en Colaboración | Compra Captación',
          '/marketplace/compartir-captacion': 'Publicar Captación en Colaboración | Compra Captación',
          '/marketplace/demandas': 'Demandas de Compradores Activas | Compra Captación',
          '/marketplace/publicar-demanda': 'Publicar Demanda de Comprador | Compra Captación',
          '/demandas': 'Demandas de Compradores Activas | Compra Captación',
          '/buscar-captaciones': 'Demandas de Compradores Activas | Compra Captación',
          '/publicar': 'Publicar Captación o Demanda | Compra Captación',
          '/publicar-propiedad': 'Publicar Captación en Colaboración | Compra Captación',
          '/ofrecer-captacion': 'Publicar Captación en Colaboración | Compra Captación',
          '/compartir-captacion': 'Publicar Captación en Colaboración | Compra Captación',
          '/publicar-demanda': 'Publicar Demanda de Comprador | Compra Captación',
          '/como-funciona': 'Cómo Funciona Compra Captación | Protocolo de Colaboración Inmobiliaria 50/50',
          '/precios': 'Planes y Precios | Acceso Flexible para Profesionales | Compra Captación',
          '/planes': 'Planes y Precios | Acceso Flexible para Profesionales | Compra Captación',
          '/recursos': 'Recursos y Contratos Inmobiliarios | Plantillas 50/50 y NDA | Compra Captación',
          '/coincidencias-ventas': 'Coincidencias Inteligentes de Venta | Cruce Inmuebles y Compradores | Compra Captación',
          '/contacto': 'Contacto y Soporte Profesional | Compra Captación',
          '/aviso-legal': 'Aviso Legal y Condiciones de Uso | Compra Captación',
          '/privacidad': 'Política de Privacidad y Protección de Datos RGPD | Compra Captación',
          '/cookies': 'Política de Cookies | Compra Captación',
          '/normas-publicacion': 'Normas de Publicación y Conducta Profesional | Compra Captación',
          '/condiciones-de-contratacion': 'Condiciones Generales de Contratación | Compra Captación',
          '/canal-de-denuncias': 'Canal Ético y de Denuncias | Compra Captación',
          '/area-privada': 'Centro de Mando | Área Privada | Compra Captación',
          '/area-privada/resumen': 'Resumen Ejecutivo | Panel Privado | Compra Captación',
          '/area-privada/mis-captaciones': 'Mis Inmuebles 50/50 | Panel Privado | Compra Captación',
          '/area-privada/mis-demandas': 'Mis Demandas de Compradores | Panel Privado | Compra Captación',
          '/area-privada/creditos': 'Créditos y Libro Mayor | Panel Privado | Compra Captación',
          '/area-privada/solicitudes': 'Bandeja de Solicitudes | Panel Privado | Compra Captación',
          '/area-privada/operaciones': 'Pipeline de Operaciones 50/50 | Panel Privado | Compra Captación',
          '/area-privada/favoritos': 'Mis Favoritos | Panel Privado | Compra Captación',
          '/area-privada/calendario': 'Agenda y Calendario | Panel Privado | Compra Captación',
          '/area-privada/suscripciones': 'Suscripciones y Alertas de Cartera | Panel Privado | Compra Captación',
          '/area-privada/mensajes': 'Mensajes Protegidos | Panel Privado | Compra Captación',
          '/area-privada/trazabilidad': 'Trazabilidad Legal 50/50 | Panel Privado | Compra Captación',
          '/area-privada/importaciones': 'Importaciones y XML | Panel Privado | Compra Captación',
          '/area-privada/ia-configuracion': 'Configuración IA Vera | Panel Privado | Compra Captación',
          '/area-privada/perfil': 'Perfil Profesional y Facturación | Panel Privado | Compra Captación'
        };

        const routeDescriptions = {
          '/inicio': 'Conecta con agentes inmobiliarios en España, comparte captaciones al 50/50 y encuentra inmuebles para tus compradores con datos protegidos. Empieza gratis.',
          '/propiedades': 'Explora inmuebles verificados para tus compradores. Colabora directamente con agencias y agentes en toda España con reparto de honorarios garantizado.',
          '/marketplace': 'Explora inmuebles verificados para tus compradores. Colabora directamente con agencias y agentes en toda España con reparto de honorarios garantizado.',
          '/demandas': 'Accede a demandas de compradores solventes con fondos listos. Da salida a tus captaciones contactando directamente con el agente del comprador.',
          '/buscar-captaciones': 'Accede a demandas de compradores solventes con fondos listos. Da salida a tus captaciones contactando directamente con el agente del comprador.',
          '/publicar': 'Publica tus captaciones o demandas de compradores en minutos. Conecta con agentes cualificados y multiplica tus cierres con acuerdos protegidos.',
          '/publicar-propiedad': 'Publica tus captaciones en exclusiva para compartir con agencias colaboradoras en toda España con reparto de honorarios 50/50.',
          '/publicar-demanda': 'Registra la demanda activa de tu cliente comprador cualificado para recibir captaciones compatibles de inmediato.',
          '/como-funciona': 'Descubre cómo colaborar con seguridad: acuerdos 50/50 vinculantes, registro de visitas, protección de honorarios y cruces automáticos de compradores.',
          '/precios': 'Consulta nuestros planes de acceso profesional y paquetes de créditos para desbloquear contactos y colaborar con total libertad y sin cuotas ocultas.',
          '/planes': 'Consulta nuestros planes de acceso profesional y paquetes de créditos para desbloquear contactos y colaborar con total libertad y sin cuotas ocultas.',
          '/recursos': 'Descarga contratos oficiales de colaboración 50/50, acuerdos de confidencialidad NDA y hojas de visita homologadas para operaciones seguras.',
          '/coincidencias-ventas': 'Motor inteligente de cruce entre captaciones y compradores cualificados. Acelera tus ventas detectando agentes con clientes compatibles al instante.',
          '/contacto': '¿Tienes dudas o necesitas soporte para tu agencia? Contacta con el equipo de Compra Captación y te ayudaremos a potenciar tus colaboraciones.'
        };

        document.title = routeTitles[pathname] || 'Compra Captación | Plataforma para Profesionales Inmobiliarios';
        const metaDesc = document.querySelector('meta[name="description"]');
        if (metaDesc && routeDescriptions[pathname]) {
          metaDesc.setAttribute('content', routeDescriptions[pathname]);
        }
        const canonical = document.getElementById('captacion-canonical') || document.querySelector('link[rel="canonical"]');
        if (canonical) {
          const canonicalPath = `${pathname.replace(/\/+$/, '')}/`;
          canonical.href = `${window.location.origin}${CAPTACION_BASE_PATH.replace(/\/+$/, '')}${canonicalPath}`;
        }

        // Asegurar que el menú móvil y action sheet se cierren y el scroll esté siempre desbloqueado
        toggleMenu(true);
        closeMobileActionSheet();
        document.body.style.overflow = '';

        // Ocultar todas las páginas
        document.querySelectorAll('.page-section').forEach(section => {
          section.classList.add('hidden');
        });

        // Mostrar la activa
        let activePageId = routes[pathname] || 'page-inicio';
        const activeSection = document.getElementById(activePageId);
        if (activeSection) {
          activeSection.classList.remove('hidden');
          window.scrollTo({ top: 0 });
        try { repairMojibakeInDOM(activeSection); } catch(e){}
        }

        if (activePageId === 'page-area-privada') {
          const session = getDemoSession();
          if (!session) {
            showToast('Para acceder al panel privado debes identificarte o registrarte como profesional.', 'info');
            navigateTo('/');
            setTimeout(() => {
              const registerSection = document.getElementById('home-register-login');
              if (registerSection) {
                registerSection.scrollIntoView({ behavior: 'smooth' });
              } else if (typeof openProfessionalAccess === 'function') {
                openProfessionalAccess();
              }
            }, 100);
            return;
          }
          const subPathToPanel = {
            '/area-privada': 'overview',
            '/area-privada/resumen': 'overview',
            '/area-privada/mis-captaciones': 'offers',
            '/area-privada/mis-demandas': 'demands',
            '/area-privada/creditos': 'credits',
            '/area-privada/solicitudes': 'requests',
            '/area-privada/operaciones': 'operations',
            '/area-privada/favoritos': 'favorites',
            '/area-privada/calendario': 'tasks',
            '/area-privada/suscripciones': 'subscriptions',
            '/area-privada/mensajes': 'communications',
            '/area-privada/trazabilidad': 'traceability',
            '/area-privada/importaciones': 'feeds',
            '/area-privada/ia-configuracion': 'ai',
            '/area-privada/perfil': 'profile'
          };
          const targetPanel = subPathToPanel[pathname] || 'overview';
          try { switchPrivateDashboardPanel(targetPanel, false); } catch(e){}
        }

        if (activePageId === 'page-inicio') {
          setTimeout(() => {
            try { renderHome(); initHomeMap(); } catch(e){}
          }, 0);
        }
        if (activePageId === 'page-marketplace') {
          setTimeout(() => {
            try {
              refreshMarketplaceView();
              if (marketplaceViewMode === 'map') initMarketplaceMap();
            } catch(e){}
          }, 0);
        }
        if (activePageId === 'page-coincidencias-ventas') {
          setTimeout(() => { try { renderSalesMatches(); } catch(e){} }, 0);
        }
        if (activePageId === 'page-buscar-captaciones' && currentNeedsLayout === 'mapa') {
          setTimeout(() => { try { initNeedsMap(); } catch(e){} }, 0);
        }
        if (activePageId === 'page-publicar' || activePageId === 'page-ofrecer-captacion' || activePageId === 'page-publicar-demanda') {
          setTimeout(() => {
            try {
              const urlParams = new URLSearchParams(window.location.search);
              const tipoParam = urlParams.get('tipo');
              const isDemandRoute = pathname.includes('demanda') || tipoParam === 'demanda' || window.location.hash.includes('demanda');
              switchPublishMode(isDemandRoute ? 'demanda' : 'oferta');
              setOfferStep(1);
              setNeedStep(1);
              if (!isDemandRoute) loadLastCaptationDiagnosisDraft();
            } catch(e){}
          }, 0);
        }
        if (activePageId === 'page-area-privada') {
          setTimeout(() => { try { renderDashboard(); renderAIConnections(); renderPrivateXmlFeeds(); } catch(e){} }, 0);
        }
        if (activePageId === 'page-recursos') {
          setTimeout(() => { try { renderDownloadableResources(); } catch(e){} }, 0);
        }

        // Sincronizar clases visuales de los links del menú
        try {
          document.querySelectorAll('.nav-link').forEach(link => {
            let href = link.getAttribute('href') || '';
            if (href.startsWith('#/')) {
              href = href.substring(1);
            }
            if (href.startsWith(window.location.origin)) {
              href = href.substring(window.location.origin.length);
            }
            if (CAPTACION_BASE_PATH && CAPTACION_BASE_PATH !== '/' && href.startsWith(CAPTACION_BASE_PATH)) {
              href = href.substring(CAPTACION_BASE_PATH.length);
            }
            href = '/' + href.replace(/^\/+|\/+$/g, '');
            if (href.startsWith('/marketplace/') && href !== '/marketplace') {
              const stripped = href.substring('/marketplace'.length);
              if (routes[stripped]) href = stripped;
            }
            
            if (href === pathname) {
              link.classList.add('text-blue', 'border-blue');
              link.classList.remove('text-slate-600', 'border-transparent');
            } else {
              link.classList.remove('text-blue', 'border-blue');
              link.classList.add('text-slate-600', 'border-transparent');
            }
          });
        } catch(e){}

        // Sincronizar Bottom Tab Bar móvil
        try {
          if (pathname === '/inicio' || pathname === '/') setActiveMobileTab('inicio');
          else if (pathname.includes('propiedad') || pathname.includes('oferta')) setActiveMobileTab('propiedades');
          else if (pathname.includes('demanda') || pathname.includes('buscar')) setActiveMobileTab('demandas');
          else if (pathname.includes('area-privada')) setActiveMobileTab('menu');
          else setActiveMobileTab('');
        } catch(e){}
      } catch (error) {
        console.warn('[captacion] handleRoute', error);
        try {
          const currentPath = getCurrentAppRoutePath();
          const target = routes[currentPath] || 'page-inicio';
          document.querySelectorAll('.page-section').forEach(s => {
            if (s.id !== target) s.classList.add('hidden');
          });
          document.getElementById(target)?.classList.remove('hidden');
        } catch(e){}
      }
    }

    // --- MENÚ MÓVIL Y TABLET (OFF-CANVAS DRAWER) ---
    let _lastMenuToggleTime = 0;
    function toggleMenu(forceClose = false) {
      const now = Date.now();
      if (forceClose !== true && (now - _lastMenuToggleTime < 250)) return;
      _lastMenuToggleTime = now;

      const mobileNav = document.getElementById('mobile-nav');
      const backdrop = document.getElementById('mobile-nav-backdrop');
      const menuBtn = document.getElementById('menu-btn');
      const textEl = document.getElementById('menu-icon-text');
      if (!mobileNav) return;

      if (forceClose === true) {
        mobileNav.classList.add('translate-x-full');
        if (backdrop) {
          backdrop.classList.remove('opacity-100', 'pointer-events-auto');
          backdrop.classList.add('opacity-0', 'pointer-events-none');
        }
        menuBtn?.setAttribute('aria-expanded', 'false');
        if (textEl) textEl.innerText = '☰';
        document.body.style.overflow = '';
        return;
      }

      const isCurrentlyOpen = !mobileNav.classList.contains('translate-x-full');
      const shouldOpen = !isCurrentlyOpen;

      if (shouldOpen) {
        mobileNav.classList.remove('translate-x-full');
        if (backdrop) {
          backdrop.classList.remove('opacity-0', 'pointer-events-none');
          backdrop.classList.add('opacity-100', 'pointer-events-auto');
        }
        menuBtn?.setAttribute('aria-expanded', 'true');
        if (textEl) textEl.innerText = '✕';
        document.body.style.overflow = 'hidden';
      } else {
        mobileNav.classList.add('translate-x-full');
        if (backdrop) {
          backdrop.classList.remove('opacity-100', 'pointer-events-auto');
          backdrop.classList.add('opacity-0', 'pointer-events-none');
        }
        menuBtn?.setAttribute('aria-expanded', 'false');
        if (textEl) textEl.innerText = '☰';
        document.body.style.overflow = '';
      }
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        toggleMenu(true);
        closeMobileActionSheet();
      }
    });

    // --- APP MOBILE TAB BAR & ACTION SHEET CONTROLLERS ---
    function openMobileActionSheet() {
      const sheet = document.getElementById('mobile-action-sheet');
      const backdrop = document.getElementById('mobile-action-sheet-backdrop');
      if (!sheet) return;
      sheet.classList.remove('translate-y-full');
      if (backdrop) {
        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        backdrop.classList.add('opacity-100', 'pointer-events-auto');
      }
      document.body.style.overflow = 'hidden';
    }

    function closeMobileActionSheet() {
      const sheet = document.getElementById('mobile-action-sheet');
      const backdrop = document.getElementById('mobile-action-sheet-backdrop');
      if (!sheet) return;
      sheet.classList.add('translate-y-full');
      if (backdrop) {
        backdrop.classList.remove('opacity-100', 'pointer-events-auto');
        backdrop.classList.add('opacity-0', 'pointer-events-none');
      }
      document.body.style.overflow = '';
    }

    function handleMobileTabMenuOrAccount() {
      if (hasActiveProfessionalSession()) {
        navigateTo('/area-privada');
      } else {
        toggleMenu();
      }
    }

    function setActiveMobileTab(tabKey) {
      document.querySelectorAll('[data-mobile-tab]').forEach(btn => {
        const key = btn.dataset.mobileTab;
        const isActive = (key === tabKey);
        const dot = btn.querySelector('.tab-active-dot');
        if (isActive) {
          btn.classList.add('text-blue', 'dark:text-blue-neon', 'font-black');
          btn.classList.remove('text-slate-500', 'dark:text-slate-400');
          if (dot) dot.classList.remove('opacity-0');
        } else {
          btn.classList.remove('text-blue', 'dark:text-blue-neon', 'font-black');
          btn.classList.add('text-slate-500', 'dark:text-slate-400');
          if (dot) dot.classList.add('opacity-0');
        }
      });
    }

    window.toggleMenu = toggleMenu;
    window.openMobileActionSheet = openMobileActionSheet;
    window.closeMobileActionSheet = closeMobileActionSheet;
    window.handleMobileTabMenuOrAccount = handleMobileTabMenuOrAccount;
    window.setActiveMobileTab = setActiveMobileTab;

    // ==========================================
    // ==========================================
    // 6. GESTIÓN GEOGR?FICA DE FORMULARIOS
    // ==========================================
    function fillSelectOptions(select, options, placeholder, allowAll = false) {
      if (!select) return;
      const baseValue = allowAll ? 'all' : '';
      select.innerHTML = `<option value="${baseValue}">${placeholder}</option>`;
      options.forEach(option => {
        select.innerHTML += `<option value="${escapeHTML(option.name)}">${escapeHTML(option.name)}</option>`;
      });
    }

    function setFieldDisabled(field, disabled) {
      if (!field) return;
      field.disabled = disabled;
    }

    function fillMunicipalityDatalist(input, list, municipalities = []) {
      if (!input || !list) return;
      list.innerHTML = municipalities.map(item => `<option value="${escapeHTML(item.name)}"></option>`).join('');
    }

    function resetMunicipalityInput(input, list, placeholder, disabled = true) {
      if (!input) return;
      input.value = '';
      input.placeholder = placeholder;
      input.disabled = disabled;
      input.setCustomValidity('');
      if (list) list.innerHTML = '';
    }

    function validateMunicipalityInput(input, communityName, provinceName) {
      if (!input || input.disabled) return true;
      const rawValue = cleanText(input.value);
      if (!rawValue) {
        input.setCustomValidity('Selecciona o busca un municipio.');
        return false;
      }
      const municipality = getMunicipalityByName(communityName, provinceName, rawValue);
      if (!municipality) {
        input.setCustomValidity('Selecciona un municipio válido de la provincia elegida.');
        return false;
      }
      input.value = municipality.name;
      input.setCustomValidity('');
      return true;
    }

    function resolveTerritorySelection(ccaaName = '', provinceName = '', municipalityName = '') {
      const community = getCommunityByName(ccaaName);
      if (!community) return { valid: false, message: 'Selecciona una comunidad o ciudad autónoma válida.' };
      const province = getProvinceByName(community.name, provinceName);
      if (!province) return { valid: false, message: 'Selecciona una provincia válida dentro de la comunidad elegida.' };
      const municipality = getMunicipalityByName(community.name, province.name, municipalityName);
      if (!municipality) return { valid: false, message: 'Selecciona un municipio válido de la provincia elegida.' };
      return {
        valid: true,
        autonomous_community_id: String(community.id || ''),
        autonomous_community_name: community.name,
        province_id: String(province.id || ''),
        province_name: province.name,
        municipality_id: String(municipality.id || municipality.ine_code || ''),
        municipality_ine_code: String(municipality.ine_code || municipality.id || ''),
        municipality_name: municipality.name
      };
    }

    function initGeoSelectors() {
      const needPubCcaa = document.getElementById('need-pub-ccaa-sel');
      const needPubProvince = document.getElementById('need-pub-province-sel');
      const needPubMunicipality = document.getElementById('need-pub-municipality-sel');
      const needPubMunicipalityList = document.getElementById('need-pub-municipality-list');

      const needFilterCcaa = document.getElementById('need-filter-ccaa');
      const needFilterProvince = document.getElementById('need-filter-province');
      const needFilterMunicipality = document.getElementById('need-filter-municipality');

      const offerCcaa = document.getElementById('offer-ccaa-sel');
      const offerProvince = document.getElementById('offer-province-sel');
      const offerMunicipality = document.getElementById('offer-municipality-sel');
      const offerMunicipalityList = document.getElementById('offer-municipality-list');

      const communities = getSortedCommunities();

      if (needPubCcaa) {
        fillSelectOptions(needPubCcaa, communities, 'Selecciona una comunidad autónoma');
        fillSelectOptions(needPubProvince, [], 'Selecciona una comunidad autónoma');
        setFieldDisabled(needPubProvince, true);
        resetMunicipalityInput(needPubMunicipality, needPubMunicipalityList, 'Selecciona una provincia', true);
        needPubCcaa.onchange = () => updateGeoDropdowns('form-need');
        needPubProvince.onchange = () => updateGeoDropdowns('form-need', true);
        if (needPubMunicipality) {
          needPubMunicipality.oninput = () => validateMunicipalityInput(needPubMunicipality, needPubCcaa.value, needPubProvince.value);
          needPubMunicipality.onblur = () => validateMunicipalityInput(needPubMunicipality, needPubCcaa.value, needPubProvince.value);
        }
      }

      if (offerCcaa) {
        fillSelectOptions(offerCcaa, communities, 'Selecciona una comunidad autónoma');
        fillSelectOptions(offerProvince, [], 'Selecciona una comunidad autónoma');
        setFieldDisabled(offerProvince, true);
        resetMunicipalityInput(offerMunicipality, offerMunicipalityList, 'Selecciona una provincia', true);
        offerCcaa.onchange = () => updateGeoDropdowns('form-offer');
        offerProvince.onchange = () => updateGeoDropdowns('form-offer', true);
        if (offerMunicipality) {
          offerMunicipality.oninput = () => validateMunicipalityInput(offerMunicipality, offerCcaa.value, offerProvince.value);
          offerMunicipality.onblur = () => validateMunicipalityInput(offerMunicipality, offerCcaa.value, offerProvince.value);
        }
      }

      if (needFilterCcaa) {
        fillSelectOptions(needFilterCcaa, communities, 'Todas las CCAA', true);
        fillSelectOptions(needFilterProvince, [], 'Todas las provincias', true);
        fillSelectOptions(needFilterMunicipality, [], 'Todos los municipios', true);
        needFilterCcaa.onchange = () => {
          updateGeoDropdowns('filter');
          filterNeeds();
        };
        needFilterProvince.onchange = () => {
          updateGeoDropdowns('filter', true);
          filterNeeds();
        };
        if (needFilterMunicipality) needFilterMunicipality.onchange = filterNeeds;
      }
    }

    function updateGeoDropdowns(context, provinceChangedOnly = false) {
      const needPubCcaa = document.getElementById('need-pub-ccaa-sel');
      const needPubProvince = document.getElementById('need-pub-province-sel');
      const needPubMunicipality = document.getElementById('need-pub-municipality-sel');
      const needPubMunicipalityList = document.getElementById('need-pub-municipality-list');

      const offerCcaa = document.getElementById('offer-ccaa-sel');
      const offerProvince = document.getElementById('offer-province-sel');
      const offerMunicipality = document.getElementById('offer-municipality-sel');
      const offerMunicipalityList = document.getElementById('offer-municipality-list');

      const needFilterCcaa = document.getElementById('need-filter-ccaa');
      const needFilterProvince = document.getElementById('need-filter-province');
      const needFilterMunicipality = document.getElementById('need-filter-municipality');

      if (context === 'form-need') {
        const community = getCommunityByName(needPubCcaa?.value || '');
        if (!provinceChangedOnly) {
          fillSelectOptions(needPubProvince, community ? [...(community.provinces || [])].sort((a, b) => a.name.localeCompare(b.name, 'es')) : [], 'Selecciona una provincia');
          setFieldDisabled(needPubProvince, !community);
          resetMunicipalityInput(needPubMunicipality, needPubMunicipalityList, community ? 'Selecciona o busca un municipio' : 'Selecciona una provincia', true);
        }
        const province = getProvinceByName(needPubCcaa?.value || '', needPubProvince?.value || '');
        fillMunicipalityDatalist(needPubMunicipality, needPubMunicipalityList, province ? [...(province.municipalities || [])].sort((a, b) => a.name.localeCompare(b.name, 'es')) : []);
        setFieldDisabled(needPubMunicipality, !province);
        needPubMunicipality.placeholder = province ? 'Selecciona o busca un municipio' : 'Selecciona una provincia';
        needPubMunicipality.value = '';
        needPubMunicipality.setCustomValidity('');
        return;
      }

      if (context === 'form-offer') {
        const community = getCommunityByName(offerCcaa?.value || '');
        if (!provinceChangedOnly) {
          fillSelectOptions(offerProvince, community ? [...(community.provinces || [])].sort((a, b) => a.name.localeCompare(b.name, 'es')) : [], 'Selecciona una provincia');
          setFieldDisabled(offerProvince, !community);
          resetMunicipalityInput(offerMunicipality, offerMunicipalityList, community ? 'Selecciona o busca un municipio' : 'Selecciona una provincia', true);
        }
        const province = getProvinceByName(offerCcaa?.value || '', offerProvince?.value || '');
        fillMunicipalityDatalist(offerMunicipality, offerMunicipalityList, province ? [...(province.municipalities || [])].sort((a, b) => a.name.localeCompare(b.name, 'es')) : []);
        setFieldDisabled(offerMunicipality, !province);
        offerMunicipality.placeholder = province ? 'Selecciona o busca un municipio' : 'Selecciona una provincia';
        offerMunicipality.value = '';
        offerMunicipality.setCustomValidity('');
        return;
      }

      const community = getCommunityByName(needFilterCcaa?.value || '');
      if (!provinceChangedOnly) {
        fillSelectOptions(needFilterProvince, community ? [...(community.provinces || [])].sort((a, b) => a.name.localeCompare(b.name, 'es')) : [], 'Todas las provincias', true);
        fillSelectOptions(needFilterMunicipality, [], 'Todos los municipios', true);
      }
      const province = getProvinceByName(needFilterCcaa?.value || '', needFilterProvince?.value || '');
      fillSelectOptions(needFilterMunicipality, province ? [...(province.municipalities || [])].sort((a, b) => a.name.localeCompare(b.name, 'es')) : [], 'Todos los municipios', true);
    }
    // ==========================================
    // 7. RENDERIZACIÓN DE MOCKUPS Y DASHBOARDS
    // ==========================================

    // ==========================================
    // 7.1 UTILIDADES, HOME DINMICA Y FUNCIONES DE APOYO
    // ==========================================
    function cleanText(value = "") {
      return String(value).replace(/[<>]/g, '').replace(/[\u0000-\u001F\u007F]/g, ' ').trim();
    }

    const UI_MOJIBAKE_REPLACEMENTS = [];

    function repairMojibakeString(value = '') {
      let result = String(value);
      UI_MOJIBAKE_REPLACEMENTS.forEach(([from, to]) => {
        result = result.split(from).join(to);
      });
      return result;
    }

    function repairMojibakeInDOM(root = document.body) {
      if (!root) return;
      const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
      const textNodes = [];
      while (walker.nextNode()) textNodes.push(walker.currentNode);
      textNodes.forEach(node => {
        if (!node.nodeValue || !node.nodeValue.trim()) return;
        const repaired = repairMojibakeString(node.nodeValue);
        if (repaired !== node.nodeValue) node.nodeValue = repaired;
      });
      root.querySelectorAll('input, textarea, button, a, span, p, label, option, select, summary').forEach(element => {
        ['placeholder', 'title', 'aria-label'].forEach(attribute => {
          const current = element.getAttribute(attribute);
          if (!current) return;
          const repaired = repairMojibakeString(current);
          if (repaired !== current) element.setAttribute(attribute, repaired);
        });
      });
    }

    function enrichTerritoryFields(record = {}) {
      const territory = resolveTerritorySelection(record.autonomous_community_name || record.ccaa || '', record.province_name || record.province || '', record.municipality_name || record.municipality || '');
      if (!territory.valid) {
        return {
          autonomous_community_id: cleanText(record.autonomous_community_id || ''),
          autonomous_community_name: cleanText(record.autonomous_community_name || record.ccaa || ''),
          province_id: cleanText(record.province_id || ''),
          province_name: cleanText(record.province_name || record.province || ''),
          municipality_id: cleanText(record.municipality_id || ''),
          municipality_ine_code: cleanText(record.municipality_ine_code || ''),
          municipality_name: cleanText(record.municipality_name || record.municipality || '')
        };
      }
      return territory;
    }

    function escapeHTML(value = "") {
      return String(value).replace(/[&<>'"]/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
      }[char]));
    }

    const CATASTRO_HOME_URL = 'https://www1.sedecatastro.gob.es/';

    function normalizeCadastralReference(value = '') {
      return cleanText(value).toUpperCase().replace(/[^A-Z0-9]/g, '');
    }

    function isCadastralReferenceValid(value = '') {
      return /^[A-Z0-9]{20}$/.test(normalizeCadastralReference(value));
    }

    function maskCadastralReference(value = '') {
      const reference = normalizeCadastralReference(value);
      if (!reference) return '';
      if (reference.length < 20) {
        return `${reference.slice(0, 4)}...${reference.slice(-3)}`;
      }
      return `${reference.slice(0, 4)} ${reference.slice(4, 8)} ${reference.slice(8, 12)} ${reference.slice(12, 16)} ${reference.slice(16, 20)}`;
    }

    function buildCatastroLinks() {
      return {
        home: CATASTRO_HOME_URL,
        search: CATASTRO_HOME_URL,
        map: CATASTRO_HOME_URL
      };
    }

    function openCatastroPortal() {
      window.open(CATASTRO_HOME_URL, '_blank', 'noopener,noreferrer');
    }

    function copyCadastralReference(reference = '') {
      const normalized = normalizeCadastralReference(reference);
      if (!normalized) {
        showToast('No hay una referencia catastral para copiar.', 'info');
        return;
      }
      if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(normalized).then(() => {
          showToast('Referencia catastral copiada al portapapeles.', 'success');
        }).catch(() => {
          showToast('No se pudo copiar la referencia.', 'info');
        });
        return;
      }
      showToast('Tu navegador no permite copiar automaticamente.', 'info');
    }

    function updateOfferCatastroPreview() {
      const input = document.getElementById('offer-cadastral-reference');
      const preview = document.getElementById('offer-cadastral-preview');
      if (!input || !preview) return;
      const reference = normalizeCadastralReference(input.value || '');
      if (!reference) {
        preview.classList.add('hidden');
        preview.innerHTML = '';
        return;
      }
      const isValid = isCadastralReferenceValid(reference);
      preview.classList.remove('hidden');
      preview.innerHTML = `
        <div class="rounded-2xl border ${isValid ? 'border-green/20 bg-green-light/30' : 'border-amber/20 bg-amber-light/30'} p-4 space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
              <span class="block text-[10px] font-black uppercase tracking-[0.16em] ${isValid ? 'text-green' : 'text-amber'}">Catastro privado</span>
              <p class="mt-1 text-xs text-slate-600">La referencia completa no se publica. Se guarda solo para trazabilidad interna y validacion documental.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase ${isValid ? 'bg-green-light text-green' : 'bg-amber-light text-amber'}">${isValid ? 'Formato valido' : 'Pendiente de validar'}</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end">
            <div>
              <span class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-1">Referencia protegida</span>
              <p class="font-mono text-sm font-bold text-navy tracking-[0.18em]">${escapeHTML(maskCadastralReference(reference))}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <button type="button" onclick="openCatastroPortal()" class="px-3 py-2 rounded-xl border border-blue/20 bg-white text-[10px] font-black text-blue hover:bg-blue-light">Abrir sede Catastro</button>
              <button type="button" onclick="copyCadastralReference('${escapeHTML(reference)}')" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-[10px] font-black text-navy hover:bg-slate-50">Copiar referencia</button>
            </div>
          </div>
          <div class="flex flex-wrap gap-2 text-[10px] font-bold text-slate-600">
            <span class="px-2.5 py-1 rounded-full bg-white border border-slate-200">Home oficial</span>
            <span class="px-2.5 py-1 rounded-full bg-white border border-slate-200">Visor publico</span>
            <span class="px-2.5 py-1 rounded-full bg-white border border-slate-200">Trazabilidad interna</span>
          </div>
          ${!isValid ? '<p class="text-[11px] text-amber-700">Si pegas una referencia catastral, debe tener 20 caracteres alfanumericos.</p>' : ''}
        </div>
      `;
    }

    function renderCatastroPropertyBlock(property = {}) {
      const reference = normalizeCadastralReference(property.cadastral_reference || property.cadastralReference || '');
      const maskedReference = cleanText(property.cadastral_reference_masked || (reference ? maskCadastralReference(reference) : 'No aportada'));
      const status = cleanText(property.cadastral_status || (reference ? 'format_ok' : 'not_provided'));
      const statusLabel = status === 'format_ok' ? 'Formato validado' : status === 'verified' ? 'Validado' : 'No aportada';
      const checkedAt = property.cadastral_last_checked_at ? formatRelativeTime(property.cadastral_last_checked_at) : 'Sin comprobacion';
      const hasReference = Boolean(reference);
      const links = buildCatastroLinks();
      return `
        <div class="rounded-2xl border border-blue/15 bg-blue-light/20 p-4 space-y-3">
          <div class="flex items-start justify-between gap-3">
            <div>
              <span class="block text-[10px] font-black uppercase tracking-[0.16em] text-blue">Catastro</span>
              <p class="mt-1 text-xs text-slate-600">Dato privado para trazabilidad y validacion documental. No se expone completo en la ficha publica.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase ${hasReference ? 'bg-green-light text-green' : 'bg-slate-100 text-slate-500'}">${escapeHTML(statusLabel)}</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end">
            <div>
              <span class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-1">Referencia protegida</span>
              <p class="font-mono text-sm font-bold text-navy tracking-[0.18em]">${escapeHTML(maskedReference)}</p>
              <p class="mt-1 text-[11px] text-slate-500">Ultima comprobacion: ${escapeHTML(checkedAt)}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <button type="button" onclick="openCatastroPortal()" class="px-3 py-2 rounded-xl border border-blue/20 bg-white text-[10px] font-black text-blue hover:bg-blue-light">Abrir sede Catastro</button>
              ${hasReference ? `<button type="button" onclick="copyCadastralReference('${escapeHTML(reference)}')" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-[10px] font-black text-navy hover:bg-slate-50">Copiar ref. protegida</button>` : ''}
            </div>
          </div>
          <div class="flex flex-wrap gap-2 text-[10px] font-bold text-slate-600">
            <a href="${escapeHTML(links.home)}" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1 rounded-full bg-white border border-slate-200 hover:border-blue/30">Sede electronica</a>
            <a href="${escapeHTML(links.search)}" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1 rounded-full bg-white border border-slate-200 hover:border-blue/30">Consulta publica</a>
            <span class="px-2.5 py-1 rounded-full bg-white border border-slate-200">Datos protegidos</span>
          </div>
        </div>
      `;
    }

    function formatCurrency(value) {
      return new Intl.NumberFormat('es-ES', { maximumFractionDigits: 0 }).format(Number(value) || 0) + ' €';
    }

    function formatPropertyFeatures(record = {}, compact = false) {
      const bedrooms = Number(record.bedrooms) || 0;
      const bathrooms = Number(record.bathrooms) || 0;
      const surface = Number(record.surface) || 0;
      const elevator = cleanText(record.elevator || record.has_elevator || '');
      const garage = cleanText(record.garage || record.has_garage || '');
      const estateType = cleanText(record.estate_type || '');
      const estateSurface = Number(record.estate_surface_m2) || 0;
      const features = [];
      if (bedrooms > 0) features.push(compact ? `${bedrooms} hab.` : `Habitaciones: ${bedrooms}`);
      if (bathrooms > 0) features.push(compact ? `${bathrooms} baños` : `Baños: ${bathrooms}`);
      if (surface > 0) features.push(compact ? `${surface} m²` : `Superficie: ${surface} m²`);
      const extraFeatures = [];
      if (elevator && elevator !== 'No indicado') extraFeatures.push(compact ? `Ascensor: ${elevator}` : `Ascensor: ${elevator}`);
      if (garage && garage !== 'No indicado') extraFeatures.push(compact ? `Garaje: ${garage}` : `Garaje: ${garage}`);
      if (estateType && estateType !== 'No indicado') extraFeatures.push(compact ? `Finca: ${estateType}` : `Finca: ${estateType}`);
      if (estateSurface) extraFeatures.push(compact ? `${estateSurface} m² finca` : `Superficie finca/parcela: ${estateSurface} m²`);
      return [...features, ...extraFeatures].join(' · ') || 'Características no indicadas';
    }

    function safeHasConnectedAI() {
      try { return typeof hasConnectedAI === 'function' && hasConnectedAI(); } catch (error) { return false; }
    }

    function buildEstimatedInterestPoints(property = {}) {
      const zone = [property.municipality, property.locality].filter(Boolean).join(' · ') || property.province || 'la zona';
      return [
        `Servicios de transporte y movilidad próximos a ${zone}`,
        `Comercios, restauración y servicios diarios en el entorno`,
        `Centros educativos, sanitarios o administrativos de referencia cercanos`,
        `Zonas verdes, equipamientos deportivos o espacios de ocio próximos`
      ];
    }

    function renderPropertyNearbyInterests(property = {}) {
      const cached = Array.isArray(property.nearby_interest_points) ? property.nearby_interest_points.filter(Boolean) : [];
      if (cached.length || safeHasConnectedAI()) {
        const points = cached.length ? cached : buildEstimatedInterestPoints(property);
        return `<div class="rounded-2xl border border-blue/15 bg-blue-light/25 p-4">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
              <strong class="block text-navy">Sitios de interés cercanos</strong>
              <p class="mt-1 text-[11px] text-slate-500 leading-relaxed">Estimación por zona aproximada: municipio, barrio/localidad y código postal protegido.</p>
            </div>
            <button type="button" onclick="generatePropertyInterestPoints('${escapeHTML(String(property.id || ''))}')" class="shrink-0 px-3 py-2 rounded-xl bg-blue text-white text-[10px] font-black">Mejorar con IA</button>
          </div>
          <ul class="mt-3 grid gap-2 sm:grid-cols-2">${points.slice(0, 6).map(point => `<li class="rounded-xl bg-white/80 border border-blue/10 px-3 py-2 text-[11px] text-slate-600">${escapeHTML(point)}</li>`).join('')}</ul>
        </div>`;
      }
      return `<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
        <strong class="block text-navy">Sitios de interés cercanos</strong>
        <p class="mt-1 text-[11px] text-slate-500 leading-relaxed">Activa el modo IA para analizar puntos de interés cercanos usando solo la ubicación aproximada de la captación.</p>
        <button type="button" onclick="activateAIFromPropertyCard()" class="mt-3 px-3 py-2 rounded-xl bg-navy text-white text-[10px] font-black">Activar modo IA</button>
      </div>`;
    }

    function parseFlexibleNumber(value) {
      if (value === null || value === undefined || value === '') return 0;
      if (Array.isArray(value)) {
        for (const item of value) {
          const parsed = parseFlexibleNumber(item);
          if (parsed) return parsed;
        }
        return 0;
      }
      if (typeof value === 'object') {
        const preferredKeys = ['value', 'amount', 'price', 'number', 'text', 'url', 'src', 'href', 'link', 'file', 'path', 'source', 'download', 'image', 'image_url', 'imageUrl', 'attachment_url', 'attachmentUrl', 'guid', 'media_url', 'mediaUrl'];
        for (const key of preferredKeys) {
          if (Object.prototype.hasOwnProperty.call(value, key)) {
            const parsed = parseFlexibleNumber(value[key]);
            if (parsed) return parsed;
          }
        }
        for (const nestedValue of Object.values(value)) {
          const parsed = parseFlexibleNumber(nestedValue);
          if (parsed) return parsed;
        }
        return 0;
      }
      if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
      }
      const raw = cleanText(String(value));
      if (!raw) return 0;
      const numericChunkMatch = raw.replace(/\s+/g, '').match(/-?\d[\d.,]*/);
      let normalized = numericChunkMatch ? numericChunkMatch[0] : raw.replace(/\s+/g, '');
      normalized = normalized.replace(/[^0-9,.-]/g, '');
      const hasComma = normalized.includes(',');
      const hasDot = normalized.includes('.');
      if (hasComma && hasDot) {
        if (normalized.lastIndexOf(',') > normalized.lastIndexOf('.')) {
          normalized = normalized.replace(/\./g, '').replace(',', '.');
        } else {
          normalized = normalized.replace(/,/g, '');
        }
      } else if (hasComma) {
        const parts = normalized.split(',');
        if (parts.length === 2 && parts[1].length > 0 && parts[1].length <= 2) {
          normalized = normalized.replace(',', '.');
        } else {
          normalized = normalized.replace(/,/g, '');
        }
      } else if (hasDot) {
        const parts = normalized.split('.');
        if (parts.length > 2 || (parts.length === 2 && parts[1].length > 2)) {
          normalized = normalized.replace(/\./g, '');
        }
      }
      const parsed = Number.parseFloat(normalized);
      return Number.isFinite(parsed) ? parsed : 0;
    }

    function parseFlexibleInteger(value) {
      const parsed = parseFlexibleNumber(value);
      return parsed > 0 ? Math.round(parsed) : 0;
    }

    function extractImageUrlFromValue(value) {
      if (!value) return '';
      if (Array.isArray(value)) {
        for (const item of value) {
          const url = extractImageUrlFromValue(item);
          if (url) return url;
        }
        return '';
      }
      if (typeof value === 'object') {
        const preferredKeys = ['image', 'image_url', 'imageUrl', 'images', 'gallery', 'media', 'enclosure', 'attachment', 'content', 'url', 'src', 'href', 'link', 'file', 'path', 'source', 'download', 'attachment_url', 'attachmentUrl', 'guid', 'media_url', 'mediaUrl', 'thumbnail', 'thumb', 'photo', 'foto', 'picture'];
        for (const key of preferredKeys) {
          if (Object.prototype.hasOwnProperty.call(value, key)) {
            const url = extractImageUrlFromValue(value[key]);
            if (url) return url;
          }
        }
        return '';
      }
      const raw = cleanText(String(value));
      if (!raw) return '';
      if (/^(https?:)?\/\//i.test(raw) || /^data:image\//i.test(raw) || /^\/(?:wp-content|uploads)\//i.test(raw)) {
        return raw;
      }
      const match = raw.match(/https?:\/\/[^\s"'<>]+/i);
      return match ? match[0] : '';
    }

    function extractImageUrlsFromValue(value, urls = []) {
      if (!value) return urls;
      if (Array.isArray(value)) {
        value.forEach(item => extractImageUrlsFromValue(item, urls));
        return urls;
      }
      if (typeof value === 'object') {
        const preferredKeys = ['image', 'image_url', 'imageUrl', 'images', 'gallery', 'media', 'enclosure', 'attachment', 'content', 'url', 'src', 'href', 'link', 'file', 'path', 'source', 'download', 'attachment_url', 'attachmentUrl', 'guid', 'media_url', 'mediaUrl', 'thumbnail', 'thumb', 'photo', 'foto', 'picture'];
        preferredKeys.forEach(key => {
          if (Object.prototype.hasOwnProperty.call(value, key)) {
            extractImageUrlsFromValue(value[key], urls);
          }
        });
        return urls;
      }
      const url = extractImageUrlFromValue(value);
      if (url && !urls.includes(url)) urls.push(url);
      return urls;
    }

    function resolveMarketplaceImage(image = '', type = 'Activo inmobiliario') {
      const value = String(image || '').trim();
      return value || getVirtualMarketplaceImage(type);
    }

    function formatRelativeTime(timestamp) {
      const hours = Math.max(0, Math.round((Date.now() - Number(timestamp || Date.now())) / 3600000));
      if (hours < 1) return 'Hace unos minutos';
      if (hours < 24) return `Hace ${hours} h`;
      const days = Math.round(hours / 24);
      return `Hace ${days} día${days === 1 ? '' : 's'}`;
    }

    function formatRelativeTime(timestamp) {
      const diffMs = Math.max(0, Date.now() - Number(timestamp || Date.now()));
      const minutes = Math.round(diffMs / 60000);
      if (minutes < 60) return minutes <= 1 ? 'Hace 1 minuto' : `Hace ${minutes} minutos`;
      const hours = Math.round(diffMs / 3600000);
      if (hours < 24) return `Hace ${hours} h`;
      const days = Math.round(hours / 24);
      if (days < 30) return `Hace ${days} día${days === 1 ? '' : 's'}`;
      const months = Math.max(1, Math.round(days / 30));
      return months === 1 ? 'Hace 1 mes' : `Hace ${months} meses`;
    }

    const OPPORTUNITY_CATEGORY_ORDER = ['Piso', 'Casa/Chalet', 'Local Comercial', 'Nave', 'Oficina', 'Edificio', 'Suelo/Terreno', 'Otros'];

    function normalizeOpportunityCategory(value = '') {
      const raw = cleanText(value);
      if (!raw) return 'Otros';
      const normalized = normalizeMatchText(raw);
      if (normalized.includes('piso') || normalized.includes('apartamento') || normalized.includes('atico') || normalized.includes('duplex') || normalized.includes('estudio')) return 'Piso';
      if (normalized.includes('casa') || normalized.includes('chalet')) return 'Casa/Chalet';
      if (normalized.includes('local')) return 'Local Comercial';
      if (normalized.includes('nave')) return 'Nave';
      if (normalized.includes('oficina')) return 'Oficina';
      if (normalized.includes('edificio')) return 'Edificio';
      if (normalized.includes('suelo') || normalized.includes('terreno') || normalized.includes('solar')) return 'Suelo/Terreno';
      return 'Otros';
    }

    function getOpportunityCategoryRank(value = '') {
      const category = normalizeOpportunityCategory(value);
      const index = OPPORTUNITY_CATEGORY_ORDER.indexOf(category);
      return index >= 0 ? index : OPPORTUNITY_CATEGORY_ORDER.length;
    }

    function calculatePublicationOpportunityScore(record = {}, kind = 'property') {
      let score = kind === 'need' ? 50 : 48;
      const description = cleanText(record.description || '');
      const price = Number(record.price || record.budget) || 0;
      if (cleanText(record.title).length >= 18) score += 8;
      if (description.length >= 90) score += 10;
      if (price > 0) score += 8;
      if (cleanText(record.postalCode).length === 5) score += 8;
      if (cleanText(record.province)) score += 6;
      if (cleanText(record.municipality)) score += 6;
      if (Number(record.surface) > 0) score += 4;
      if (Number(record.bedrooms) > 0) score += 3;
      if (Number(record.bathrooms) > 0) score += 3;
      if (kind === 'property') {
        if (cleanText(record.docs) && !/pendiente/i.test(String(record.docs))) score += 8;
        if (record.exclusive) score += 6;
        if (cleanText(record.urgency).toLowerCase() === 'alta') score += 4;
      } else {
        if (cleanText(record.funding)) score += 6;
        if (cleanText(record.feeSplit)) score += 4;
        if (cleanText(record.buyerType)) score += 4;
        if (cleanText(record.urgency).toLowerCase() === 'alta') score += 3;
      }
      return Math.max(55, Math.min(98, score));
    }

    function createPropertyReference(property = {}, index = 0) {
      const explicitReference = cleanText(property.reference || property.xmlReference || '');
      if (explicitReference) return explicitReference;
      const source = String(property.id || `${property.title || 'captacion'}-${property.location || property.province || 'espana'}-${index}`);
      let hash = 2166136261;
      for (let charIndex = 0; charIndex < source.length; charIndex++) {
        hash ^= source.charCodeAt(charIndex);
        hash = Math.imul(hash, 16777619);
      }
      return `REF-${String(hash >>> 0).slice(-8).padStart(8, '0')}`;
    }

    const RESIDENTIAL_PROPERTY_TYPES = ['Piso', 'Casa / chalet', 'Ático', 'Dúplex', 'Apartamento', 'Estudio', 'Finca rústica con vivienda', 'Edificio residencial'];
    const BATHROOM_PROPERTY_TYPES = [...RESIDENTIAL_PROPERTY_TYPES, 'Local comercial', 'Nave', 'Oficina'];
    const ALL_PROPERTY_CONDITIONS = ['Lista para entrar / operar', 'Buen estado', 'De origen', 'Sin reforma necesaria', 'Necesita actualización', 'Reforma menor', 'Reforma mayor', 'Reforma integral', 'En obras', 'Obra nueva', 'No califica'];
    const COMMERCIAL_PROPERTY_CONDITIONS = ['Lista para entrar / operar', 'Buen estado', 'Necesita actualización', 'Reforma menor', 'Reforma mayor', 'Reforma integral', 'En obras', 'No califica'];
    const STORAGE_PROPERTY_CONDITIONS = ['Buen estado', 'Necesita actualización', 'No califica'];

    function normalizePropertyType(value = '', ref = '', title = '', desc = '') {
      const context = `${value} ${ref} ${title} ${desc}`.toLowerCase();
      if (/(nave|industrial|almacen|almacén|poligono|polígono|talleres|fábrica|fabrica|cristaleria|cristalería)/i.test(context)) {
        return 'Nave industrial';
      }
      if (/(terreno|solar|parcela|finca rústica|finca rustica|suelo|urbanizable)/i.test(context)) {
        return 'Terreno / Parcela';
      }
      if (/(local|comercial|negocio|tienda|bar|restaurante|hosteleria|hostelería)/i.test(context)) {
        return 'Local comercial';
      }
      if (/(oficina|despacho|coworking)/i.test(context)) {
        return 'Oficina';
      }
      if (/(edificio|bloque|promocion|promoción)/i.test(context)) {
        return 'Edificio residencial';
      }
      if (/(chalet|villa|casa|adosado|pareado|finca|cortijo|masia|masía|bungalow|torre)/i.test(context)) {
        return 'Casa / chalet';
      }
      if (/(ático|atico|penthouse)/i.test(context)) {
        return 'Ático';
      }
      if (/(dúplex|duplex)/i.test(context)) {
        return 'Dúplex';
      }
      if (/(estudio|loft)/i.test(context)) {
        return 'Estudio';
      }
      if (/(garaje|parking|cochera)/i.test(context)) {
        return 'Garaje';
      }
      if (/(trastero)/i.test(context)) {
        return 'Trastero';
      }

      const dict = {
        'apartment': 'Piso / Apartamento',
        'flat': 'Piso / Apartamento',
        'piso': 'Piso',
        'apartamento': 'Apartamento',
        'house': 'Casa / chalet',
        'villa': 'Casa / chalet',
        'chalet': 'Casa / chalet',
        'townhouse': 'Casa / chalet',
        'penthouse': 'Ático',
        'duplex': 'Dúplex',
        'studio': 'Estudio',
        'commercial': 'Local comercial',
        'business': 'Local comercial',
        'office': 'Oficina',
        'warehouse': 'Nave industrial',
        'industrial': 'Nave industrial',
        'land': 'Terreno / Parcela',
        'plot': 'Terreno / Parcela',
        'building': 'Edificio residencial',
        'garage': 'Garaje',
        'storage': 'Trastero',
        'casa/chalet': 'Casa / chalet',
        'casa / chalet': 'Casa / chalet',
        'local comercial': 'Local comercial',
        'edificio': 'Edificio residencial',
        'suelo/terreno': 'Terreno / Parcela',
        'suelo / terreno': 'Terreno / Parcela'
      };
      const lower = String(value || '').toLowerCase().trim();
      return dict[lower] || cleanText(value || 'Activo inmobiliario');
    }

    function conditionsForPropertyType(type = '') {
      const normalizedType = normalizePropertyType(type);
      if (RESIDENTIAL_PROPERTY_TYPES.includes(normalizedType)) return ALL_PROPERTY_CONDITIONS;
      if (['Local comercial', 'Nave', 'Oficina'].includes(normalizedType)) return COMMERCIAL_PROPERTY_CONDITIONS;
      if (normalizedType === 'Terreno / solar') return ['No califica'];
      if (['Garaje', 'Trastero'].includes(normalizedType)) return STORAGE_PROPERTY_CONDITIONS;
      return ['No califica'];
    }

    function selectedValues(select) {
      return select ? Array.from(select.selectedOptions || []).map(option => cleanText(option.value)).filter(Boolean) : [];
    }

    function updatePropertyFormDynamics(mode = 'offer') {
      const isNeed = mode === 'need';
      const prefix = isNeed ? 'need-pub' : 'offer';
      const type = normalizePropertyType(document.getElementById(`${prefix}-type`)?.value || '');
      const rooms = document.getElementById(`${prefix}-${isNeed ? 'bedrooms' : 'bedrooms'}`);
      const bathrooms = document.getElementById(`${prefix}-${isNeed ? 'bathrooms' : 'bathrooms'}`);
      const roomWrap = rooms?.closest('div');
      const bathroomWrap = bathrooms?.closest('div');
      let requiresRooms = RESIDENTIAL_PROPERTY_TYPES.includes(type);
      let requiresBathrooms = BATHROOM_PROPERTY_TYPES.includes(type);
      if (type === 'Finca rústica' || type === 'Finca rústica con vivienda' || type === 'Finca rústica con vivienda' || type === 'Finca rústica') {
        requiresRooms = false;
        requiresBathrooms = false;
      }
      if (roomWrap) roomWrap.classList.toggle('hidden', !requiresRooms);
      if (rooms) {
        rooms.required = requiresRooms;
        rooms.min = type === 'Estudio' ? '0' : '1';
        if (!requiresRooms) rooms.value = '';
      }
      if (bathroomWrap) bathroomWrap.classList.toggle('hidden', !requiresBathrooms);
      if (bathrooms) {
        bathrooms.required = requiresBathrooms;
        bathrooms.min = requiresBathrooms ? '1' : '0';
        if (!requiresBathrooms) bathrooms.value = '';
      }
      const conditionSelect = document.getElementById(isNeed ? 'need-pub-condition' : 'offer-condition');
      if (conditionSelect) {
        const previous = selectedValues(conditionSelect);
        const options = conditionsForPropertyType(type);
        conditionSelect.innerHTML = options.map(option => `<option value="${escapeHTML(option)}">${escapeHTML(option)}</option>`).join('');
        options.forEach((option, index) => {
          conditionSelect.options[index].selected = previous.includes(option) || (!previous.length && index === 0);
        });
      }
      if (!isNeed) {
        let isResidential = RESIDENTIAL_PROPERTY_TYPES.includes(type);
if (type === 'Finca rústica con vivienda' || type === 'Finca rústica' || type === 'Terreno / solar') {
    isResidential = false;
}
        const isHouse = type === 'Casa / chalet';
        const elevatorWrap = document.getElementById('offer-elevator-wrap');
        const garageWrap = document.getElementById('offer-garage-wrap');
        const estateWrap = document.getElementById('offer-estate-wrap');
        const estateSurfaceWrap = document.getElementById('offer-estate-surface-wrap');
        const elevator = document.getElementById('offer-elevator');
        const garage = document.getElementById('offer-garage');
        const estateType = document.getElementById('offer-estate-type');
        const estateSurface = document.getElementById('offer-estate-surface');
        elevatorWrap?.classList.toggle('hidden', !isResidential);
        garageWrap?.classList.toggle('hidden', !isResidential);
        estateWrap?.classList.toggle('hidden', !isHouse);
        estateSurfaceWrap?.classList.toggle('hidden', !isHouse);
        if (!isResidential) {
          if (elevator) elevator.value = 'No indicado';
          if (garage) garage.value = 'No indicado';
        }
        if (!isHouse) {
          if (estateType) estateType.value = 'No indicado';
          if (estateSurface) estateSurface.value = '';
        }
      }
    }

    function normalizePropertyRecord(property = {}, index = 0) {
      const neighborhoodParts = String(property.neighborhood || '').split('·').map(part => part.trim());
      const rawLocation = property.location || property.province || 'España';
      let ccaa = property.ccaa || rawLocation;
      if (rawLocation === 'Barcelona') ccaa = 'Cataluña';
      if (rawLocation === 'Ourense') ccaa = 'Galicia';
      const province = property.province || (rawLocation === 'Galicia' ? 'Ourense' : rawLocation);
      const municipality = property.municipality || neighborhoodParts[0] || province;
      const locality = property.locality || neighborhoodParts[1] || '';
      const territory = enrichTerritoryFields({
        ...property,
        ccaa,
        province,
        municipality
      });
      const cadastralReference = normalizeCadastralReference(property.cadastral_reference || property.cadastralReference || property.cadastre_reference || '');
      const cadastralReferenceMasked = cleanText(property.cadastral_reference_masked || (cadastralReference ? maskCadastralReference(cadastralReference) : ''));
      const cadastralReferenceHash = cleanText(property.cadastral_reference_hash || '');
      const catastroStatus = cleanText(property.cadastral_status || (cadastralReference ? 'format_ok' : 'not_provided'));
      const catastroSource = cleanText(property.cadastral_source || '');
      const catastroCheckedAt = Number(property.cadastral_last_checked_at) || 0;
      const catastroLinks = buildCatastroLinks();
      const propRef = createPropertyReference(property, index);
      const rawTitle = cleanText(property.title || 'Captación inmobiliaria');
      const propDesc = cleanText(property.description || property.desc || '');
      const normalizedType = normalizePropertyType(property.property_type || property.type || 'Activo inmobiliario', propRef, rawTitle, propDesc);
      let title = rawTitle;
      if (/^(apartment|flat|piso|activo)\s+en\s+/i.test(title) && normalizedType !== 'Piso' && normalizedType !== 'Piso / Apartamento') {
        title = title.replace(/^(apartment|flat|piso|activo)\s+en\s+/i, `${normalizedType} en `);
      }

      return {
        ...property,
        id: cleanText(property.id || `prop-${Date.now()}-${index}`),
        reference: propRef,
        title: title,
        type: normalizedType,
        property_type: normalizedType,
        ccaa: cleanText(territory.autonomous_community_name || ccaa),
        province: cleanText(territory.province_name || province),
        municipality: cleanText(territory.municipality_name || municipality),
        autonomous_community_id: cleanText(territory.autonomous_community_id || ''),
        autonomous_community_name: cleanText(territory.autonomous_community_name || ccaa),
        province_id: cleanText(territory.province_id || ''),
        province_name: cleanText(territory.province_name || province),
        municipality_id: cleanText(territory.municipality_id || ''),
        municipality_ine_code: cleanText(territory.municipality_ine_code || ''),
        municipality_name: cleanText(territory.municipality_name || municipality),
        locality: cleanText(locality),
        postalCode: cleanText(property.postalCode || property.postal_code || property.postcode || property.zipCode || property.zip || property.codigoPostal || property.codigo_postal || ''),
        cadastral_reference: cadastralReference,
        cadastral_reference_masked: cadastralReferenceMasked,
        cadastral_reference_hash: cadastralReferenceHash,
        cadastral_status: catastroStatus,
        cadastral_source: catastroSource,
        cadastral_last_checked_at: catastroCheckedAt,
        catastro_home_url: cleanText(property.catastro_home_url || catastroLinks.home),
        catastro_search_url: cleanText(property.catastro_search_url || catastroLinks.search),
        catastro_map_url: cleanText(property.catastro_map_url || catastroLinks.map),
        bedrooms: parseFlexibleInteger(property.rooms ?? property.bedrooms ?? property.habitaciones ?? property.dormitorios),
        rooms: parseFlexibleInteger(property.rooms ?? property.bedrooms ?? property.habitaciones ?? property.dormitorios),
        bathrooms: parseFlexibleInteger(property.bathrooms ?? property.banos ?? property['baños']),
        surface: parseFlexibleNumber(property.total_area_m2 ?? property.superficie_construida ?? property.surface ?? property.surfaceM2 ?? property.superficie ?? property.metros),
        total_area_m2: parseFlexibleNumber(property.total_area_m2 ?? property.superficie_construida ?? property.surface ?? property.surfaceM2 ?? property.superficie ?? property.metros),
        location: cleanText(property.location || province),
        neighborhood: cleanText(property.neighborhood || `${province}${locality ? ' · ' + locality : ''}`),
        fee: cleanText(property.offered_commission || property.fee || 'A consultar'),
        description: cleanText(property.description || ''),
        badgeText: cleanText(property.badgeText || 'Colaboración profesional'),
        property_condition: cleanText(property.property_condition || (property.necesita_reforma_integral || property.rehab ? 'Reforma integral' : '')),
        mandate_type: cleanText(property.mandate_type || (property.exclusive ? 'Exclusiva compartida' : 'No, nota de encargo abierta')),
        urgency: cleanText(property.sale_urgency || property.urgency || 'Media'),
        sale_urgency: cleanText(property.sale_urgency || property.urgency || 'Media'),
        docs: cleanText(property.documentation_level || property.docs || 'No califica'),
        documentation_level: cleanText(property.documentation_level || property.docs || 'No califica'),
        fundingConditions: cleanText(property.fundingConditions || ''),
        date: Number(property.date) || Date.now() - (index + 1) * 3600000 * 8,
        score: Number(property.score) || 80,
        price: parseFlexibleNumber(property.indicative_price ?? property.price),
        indicative_price: parseFlexibleNumber(property.indicative_price ?? property.price),
        offered_commission: cleanText(property.offered_commission || property.fee || 'A consultar'),
        image: extractImageUrlFromValue(property.image || property.images || property.gallery || property.source_data || property.sourceData || ''),
        images: extractImageUrlsFromValue(property.images || property.gallery || property.source_data || property.sourceData || []),
        gallery: extractImageUrlsFromValue(property.gallery || property.images || property.source_data || property.sourceData || []),
        source_data: property.source_data && typeof property.source_data === 'object' ? property.source_data : (property.sourceData && typeof property.sourceData === 'object' ? property.sourceData : {}),
        sourceData: property.source_data && typeof property.source_data === 'object' ? property.source_data : (property.sourceData && typeof property.sourceData === 'object' ? property.sourceData : {}),
        imageIsDefault: Boolean(property.imageIsDefault || !extractImageUrlFromValue(property.image || property.images || property.gallery || property.source_data || property.sourceData || ''))
      };
    }

    function normalizeNeedRecord(need = {}, index = 0) {
      const territory = enrichTerritoryFields(need);
      return {
        ...need,
        id: cleanText(need.id || `need-${Date.now()}-${index}`),
        title: cleanText(need.title || 'Demanda inmobiliaria activa'),
        type: normalizePropertyType(need.property_type || need.type || 'Activo inmobiliario'),
        property_type: normalizePropertyType(need.property_type || need.type || 'Activo inmobiliario'),
        operation: cleanText(need.operation || 'Venta'),
        buyerType: cleanText(need.buyerType || 'Comprador'),
        urgency: cleanText(need.search_urgency || need.urgency || 'Media'),
        search_urgency: cleanText(need.search_urgency || need.urgency || 'Media'),
        funding: cleanText(need.funding || 'A consultar'),
        ccaa: cleanText(territory.autonomous_community_name || need.ccaa || 'España'),
        province: cleanText(territory.province_name || need.province || ''),
        municipality: cleanText(territory.municipality_name || need.municipality || ''),
        autonomous_community_id: cleanText(territory.autonomous_community_id || ''),
        autonomous_community_name: cleanText(territory.autonomous_community_name || need.ccaa || ''),
        province_id: cleanText(territory.province_id || ''),
        province_name: cleanText(territory.province_name || need.province || ''),
        municipality_id: cleanText(territory.municipality_id || ''),
        municipality_ine_code: cleanText(territory.municipality_ine_code || ''),
        municipality_name: cleanText(territory.municipality_name || need.municipality || ''),
        locality: cleanText(need.locality || ''),
        postalCode: cleanText(need.postalCode || need.zipCode || need.zip || need.codigoPostal || need.codigo_postal || ''),
        bedrooms: parseFlexibleInteger(need.min_rooms ?? need.bedrooms ?? need.rooms ?? need.habitaciones ?? need.dormitorios),
        min_rooms: parseFlexibleInteger(need.min_rooms ?? need.bedrooms ?? need.rooms ?? need.habitaciones ?? need.dormitorios),
        bathrooms: parseFlexibleInteger(need.min_bathrooms ?? need.bathrooms ?? need.banos ?? need['baños']),
        min_bathrooms: parseFlexibleInteger(need.min_bathrooms ?? need.bathrooms ?? need.banos ?? need['baños']),
        surface: parseFlexibleNumber(need.desired_area_min_m2 ?? need.surface ?? need.surfaceM2 ?? need.superficie ?? need.metros),
        desired_area_min_m2: Number(need.desired_area_min_m2 ?? need.surface ?? need.surfaceM2 ?? need.superficie ?? need.metros) || 0,
        feeSplit: cleanText(need.accepted_commission || need.feeSplit || 'A consultar'),
        description: cleanText(need.description || ''),
        accepted_commission: cleanText(need.accepted_commission || need.feeSplit || 'A consultar'),
        accepted_property_conditions: Array.isArray(need.accepted_property_conditions) ? need.accepted_property_conditions : [],
        accepted_mandate_types: Array.isArray(need.accepted_mandate_types) ? need.accepted_mandate_types : [],
        required_documentation_level: cleanText(need.required_documentation_level || 'No califica'),
        budget: Number(need.max_budget ?? need.budget) || 0,
        max_budget: Number(need.max_budget ?? need.budget) || 0,
        date: Number(need.date) || Date.now() - (index + 1) * 3600000 * 6,
        agency: cleanText(need.agency || 'Agencia verificada')
      };
    }

    function persistDemoState() {
      if (CAPTACION_PRODUCTION_MODE) return;
      try {
        localStorage.setItem('captacion_properties_v3', JSON.stringify(properties));
        localStorage.setItem('captacion_needs_v3', JSON.stringify(needs));
        localStorage.setItem('captacion_closed_operations_v4', JSON.stringify(closedOperations));
      } catch (error) {
        console.warn('No se pudo persistir el estado local de la demo.', error);
      }
    }

    function renderHome() {
      renderHomeCounters();
      renderHomeFeaturedProperty();
      renderHomeLatestProperties();
      renderHomeLatestNeeds();
      updateAuthModule();
      if (homeMap) renderHomeMapMarkers();
    }

    function renderHomeCounters() {
      const mappings = [
        ['home-stat-properties', properties.length || 'Sin publicaciones aún'],
        ['home-stat-needs', needs.length || 'Sin búsquedas aún'],
        ['home-map-properties', properties.length || 'Sin datos'],
        ['home-map-needs', needs.length || 'Sin datos']
      ];
      mappings.forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el) {
          el.textContent = value;
          const isNumeric = typeof value === 'number';
          el.classList.toggle('text-4xl', isNumeric);
          el.classList.toggle('text-base', !isNumeric);
        }
      });
      const propertiesValueEl = document.getElementById('home-stat-properties-value');
      if (propertiesValueEl) {
        const totalPropertiesValue = properties.reduce((sum, item) => sum + (Number(item.price) || 0), 0);
        propertiesValueEl.textContent = totalPropertiesValue ? `${formatCurrency(totalPropertiesValue)} en valor visible` : 'Comienza publicando tu primera captación';
      }
      const needsValueEl = document.getElementById('home-stat-needs-value');
      if (needsValueEl) {
        const totalNeedsValue = needs.reduce((sum, item) => sum + (Number(item.budget) || 0), 0);
        needsValueEl.textContent = totalNeedsValue ? `${formatCurrency(totalNeedsValue)} en demanda activa` : 'Activa tu primera demanda de búsqueda';
      }
      const salesMatches = getSalesMatchRecords();
      const salesCountEl = document.getElementById('home-stat-sales-matches'); if (salesCountEl) {
        salesCountEl.textContent = salesMatches.length || 'Sin coincidencias aún';
        salesCountEl.classList.toggle('text-4xl', salesMatches.length > 0);
        salesCountEl.classList.toggle('text-base', salesMatches.length === 0);
      }
      const salesValueEl = document.getElementById('home-stat-sales-value'); if (salesValueEl) {
        const totalSalesValue = salesMatches.reduce((sum,item)=>sum+item.estimatedValue,0);
        salesValueEl.textContent = totalSalesValue ? `${formatCurrency(totalSalesValue)} estimados` : 'Publica para generar coincidencias';
      }
      const zones = new Set([
        ...properties.map(item => item.province || item.location).filter(Boolean),
        ...needs.map(item => item.province || item.ccaa).filter(Boolean)
      ]);
      const zonesEl = document.getElementById('home-map-zones');
      if (zonesEl) zonesEl.textContent = zones.size || 'Sin datos';
      const statZonesEl = document.getElementById('home-stat-zones');
      if (statZonesEl) {
        statZonesEl.textContent = zones.size || 'Sin cobertura publicada';
        statZonesEl.classList.toggle('text-4xl', zones.size > 0);
        statZonesEl.classList.toggle('text-base', zones.size === 0);
      }
    }

    function renderHomeFeaturedProperty() {
      const container = document.getElementById('home-featured-card');
      if (!container) return;
      const video = container.querySelector('video');
      const muteBtn = container.querySelector('#home-video-mute-btn');
      const iconMuted = container.querySelector('#icon-muted');
      const iconUnmuted = container.querySelector('#icon-unmuted');
      
      if (video) {
        const tryPlay = () => {
          const isMobileDevice =
            /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent) ||
            window.innerWidth < 768 ||
            !window.matchMedia('(min-width: 768px)').matches;
          if (isMobileDevice) return;
          const source = video.querySelector('source[data-src]');
          if (source && !source.src) {
            source.src = source.dataset.src;
            video.load();
          }
          video.play().catch(() => {});
        };
        video.muted = true;
        video.defaultMuted = true;
        video.loop = true;
        video.autoplay = true;
        
        if (muteBtn) {
          muteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            video.muted = !video.muted;
            if (video.muted) {
              iconMuted.classList.remove('hidden');
              iconUnmuted.classList.add('hidden');
            } else {
              iconMuted.classList.add('hidden');
              iconUnmuted.classList.remove('hidden');
            }
          });
        }

        if ('IntersectionObserver' in window) {
          const observer = new IntersectionObserver((entries) => {
            if (entries.some(entry => entry.isIntersecting)) {
              tryPlay();
              observer.disconnect();
            }
          }, { rootMargin: '120px' });
          observer.observe(video);
        } else {
          tryPlay();
        }
      }
    }

    function renderHomeLatestProperties() {
      const container = document.getElementById('home-latest-properties');
      if (!container) return;
      const validStatuses = ['active', 'pending_review', 'published'];
      const latest = properties.filter(p => isMarketplaceVisibleProperty(p) && validStatuses.includes(String(p.status || p.publication_status || 'active').toLowerCase())).sort((a, b) => b.date - a.date).slice(0, 30);
      if (!latest.length) {
        container.innerHTML = '<div class="home-carousel-card p-8 rounded-2xl bg-white border border-slate-200 text-sm text-slate-500">No hay captaciones activas publicadas.</div>';
        return;
      }
      container.innerHTML = latest.map(property => {
        const cardImage = escapeHTML(resolveMarketplaceImage(property.image, property.type));
        return `
        <article class="home-carousel-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-all">
          <div>
            <div class="relative h-36 overflow-hidden bg-slate-100">
              <img src="${cardImage}" data-virtual-type="${escapeHTML(property.type || 'Activo inmobiliario')}" width="640" height="666" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" class="absolute inset-0 w-full h-full object-cover" alt="Imagen de ${escapeHTML(property.title)}" />
              <div class="absolute inset-0 bg-gradient-to-t from-navy/75 via-transparent to-transparent"></div>
              <span class="absolute left-3 bottom-3 px-2 py-1 rounded-full bg-white/90 text-blue text-[10px] font-bold uppercase">${escapeHTML(property.type || 'Activo')}</span>
            </div>
            <div class="p-4">
              <div class="flex items-center justify-between gap-3">
                <span class="text-[10px] text-slate-400">${formatRelativeTime(property.date)}</span>
                <span class="text-[10px] text-blue font-bold">C.P. ${escapeHTML(maskPublicPostalCode(property.postalCode))}</span>
              </div>
              <h3 class="text-sm font-extrabold text-navy leading-snug mt-3 line-clamp-2">${escapeHTML(property.title)}</h3>
              <p class="text-[10px] text-slate-500 mt-2">${formatPropertyFeatures(property, true)}</p>
              <div class="flex items-end justify-between gap-3 mt-4 pt-3 border-t border-slate-100">
                <div><span class="metric-label">Precio</span><strong class="metric-value text-sm">${formatCurrency(property.price)}</strong></div>
                <button onclick="openAccessModal('${property.id}')" class="px-3 py-2 rounded-lg bg-navy text-white text-[10px] font-bold">Solicitar acceso</button>
              </div>
            </div>
          </div>
        </article>`;
      }).join('');
      container.scrollLeft = 0;
      initAccessibleCarousel(container);
    }

    function renderHomeLatestNeeds() {
      const container = document.getElementById('home-latest-needs');
      if (!container) return;
      const latest = [...needs].sort((a, b) => b.date - a.date).slice(0, 30);
      if (!latest.length) {
        container.innerHTML = '<div class="home-carousel-card p-8 rounded-2xl bg-white border border-slate-200 text-sm text-slate-500">No hay demandas activas publicadas.</div>';
        return;
      }
      container.innerHTML = latest.map(need => `
        <article class="home-carousel-card bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition-all">
          <div>
            <div class="flex items-center justify-between gap-3">
              <span class="px-2 py-1 rounded-full bg-green-light text-green text-[10px] font-black uppercase">${escapeHTML(need.buyerType || 'Comprador')}</span>
              <span class="text-[10px] text-slate-400">${formatRelativeTime(need.date)}</span>
            </div>
            <h3 class="text-base font-extrabold text-navy leading-snug mt-4">${escapeHTML(need.title)}</h3>
            <p class="text-xs text-slate-500 mt-2 line-clamp-2">${escapeHTML(need.description)}</p>
            <p class="text-[10px] text-green font-black mt-3">C.P. ${escapeHTML(maskPublicPostalCode(need.postalCode))} · ${formatPropertyFeatures(need, true)}</p>
          </div>
          <div class="flex items-end justify-between gap-4 mt-5 pt-4 border-t border-slate-100">
            <div><span class="block text-[9px] text-slate-400 uppercase font-black">Presupuesto máximo</span><strong class="text-sm text-navy">${formatCurrency(need.budget)}</strong></div>
            <button type="button" onclick="openHomeNeedMatches('${need.id}')" class="px-3 py-2 rounded-lg bg-blue text-white text-[10px] font-black">Ver demanda y coincidencias</button>
          </div>
        </article>`).join('');
      container.scrollLeft = 0;
      initAccessibleCarousel(container);
    }

    const accessibleCarouselTimers = new WeakMap();
    function initAccessibleCarousel(container) {
      if (!container) return;
      const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;
      const oldTimer = accessibleCarouselTimers.get(container);
      if (oldTimer) clearInterval(oldTimer);
      if (reducedMotion || container.querySelectorAll('.home-carousel-card, article').length < 2) return;
      const start = () => {
        if (accessibleCarouselTimers.has(container)) return;
        const timer = setInterval(() => {
          if (document.hidden || container.matches(':hover,:focus-within')) return;
          const step = container.querySelector('.home-carousel-card, article')?.getBoundingClientRect().width || container.clientWidth;
          container.scrollBy({ left: step + 16, behavior: 'smooth' });
          if (container.scrollLeft + container.clientWidth >= container.scrollWidth - 8) container.scrollTo({ left: 0, behavior: 'smooth' });
        }, 5500);
        accessibleCarouselTimers.set(container, timer);
      };
      const stop = () => { const timer = accessibleCarouselTimers.get(container); if (timer) { clearInterval(timer); accessibleCarouselTimers.delete(container); } };
      container.addEventListener('mouseenter', stop, { once:false });
      container.addEventListener('mouseleave', start, { once:false });
      container.addEventListener('focusin', stop, { once:false });
      container.addEventListener('focusout', start, { once:false });
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => { if (entries.some(entry => entry.isIntersecting)) { start(); observer.disconnect(); } }, { threshold:0.35 });
        observer.observe(container);
      } else start();
    }

    function getApproximatePoint(item, index = 0) {
      const keyCandidates = [item.municipality, item.province === 'Madrid' ? 'Madrid ciudad' : item.province, item.ccaa, item.location];
      let point = null;
      for (const key of keyCandidates) {
        if (key && geoCenters[key]) {
          point = geoCenters[key];
          break;
        }
      }
      point = point || [40.2, -3.7];
      const seed = String(item.id || index).split('').reduce((sum, char) => sum + char.charCodeAt(0), 0);
      const latOffset = ((seed % 7) - 3) * 0.035;
      const lngOffset = (((seed * 3) % 7) - 3) * 0.035;
      return [point[0] + latOffset, point[1] + lngOffset];
    }

    function formatMapAmount(value) {
      const amount = Number(value) || 0;
      if (amount >= 1000000) {
        const millions = amount / 1000000;
        return `${millions >= 10 ? Math.round(millions) : millions.toFixed(1).replace('.0', '')}M`;
      }
      if (amount >= 1000) return `${Math.round(amount / 1000)}K`;
      return `${Math.round(amount)}€`;
    }

    const LEAFLET_ASSETS = {
      css: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
      drawCss: 'https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css',
      js: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
      drawJs: 'https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js'
    };
    let leafletAssetsPromise = null;

    function loadStylesheetOnce(id, href) {
      if (document.getElementById(id)) return;
      const link = document.createElement('link');
      link.id = id;
      link.rel = 'stylesheet';
      link.href = href;
      document.head.appendChild(link);
    }

    function loadScriptOnce(id, src) {
      return new Promise((resolve, reject) => {
        const existing = document.getElementById(id);
        if (existing?.dataset.loaded === 'true') { resolve(); return; }
        if (existing) {
          existing.addEventListener('load', () => resolve(), { once: true });
          existing.addEventListener('error', reject, { once: true });
          return;
        }
        const script = document.createElement('script');
        script.id = id;
        script.src = src;
        script.async = true;
        script.onload = () => { script.dataset.loaded = 'true'; resolve(); };
        script.onerror = reject;
        document.head.appendChild(script);
        window.setTimeout(() => reject(new Error(`Tiempo agotado cargando ${id}`)), 8000);
      });
    }

    function ensureLeafletAssets() {
      if (window.L?.Draw) return Promise.resolve(true);
      if (leafletAssetsPromise) return leafletAssetsPromise;
      loadStylesheetOnce('captacion-leaflet-css', LEAFLET_ASSETS.css);
      loadStylesheetOnce('captacion-leaflet-draw-css', LEAFLET_ASSETS.drawCss);
      leafletAssetsPromise = loadScriptOnce('captacion-leaflet-js', LEAFLET_ASSETS.js)
        .then(() => loadScriptOnce('captacion-leaflet-draw-js', LEAFLET_ASSETS.drawJs))
        .then(() => true)
        .catch(() => false);
      return leafletAssetsPromise;
    }

    function createMapAmountIcon(value, kind = 'property') {
      const pillClass = kind === 'property' ? 'map-price-pill' : 'map-demand-pill';
      const label = escapeHTML(formatMapAmount(value));
      return L.divIcon({
        className: 'map-label-div-icon',
        html: `<span class="${pillClass}">${label}</span>`,
        iconSize: [64, 28],
        iconAnchor: [32, 14]
      });
    }

    function addBaseTileLayer(map) {
      const providerKey = 'openstreet' + 'map';
      L.tileLayer('https://{s}.tile.' + providerKey + '.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; ' + 'OpenStreet' + 'Map contributors'
      }).addTo(map);
    }

    function fitMapToApproximatePoints(map, points, fallbackZoom = 5.7) {
      if (!map || !points.length) {
        map?.setView(SPAIN_DEFAULT_MAP_CENTER, fallbackZoom);
        return;
      }
      if (points.length === 1) {
        map.setView(points[0], 11);
        return;
      }
      map.fitBounds(L.latLngBounds(points), { padding: [32, 32], maxZoom: 12 });
    }

    function resetMapToSpain(map) {
      if (!map) return;
      setTimeout(() => {
        map.invalidateSize?.();
        map.setView(SPAIN_DEFAULT_MAP_CENTER, SPAIN_DEFAULT_MAP_ZOOM, { animate: true });
      }, 80);
    }

    function updateHomeMapAreaStatus(message = '') {
      const status = document.getElementById('home-map-area-status');
      if (!status) return;
      status.textContent = message || (homeMapSelectedBounds
        ? 'Zona dibujada activa. Solo se muestran las oportunidades incluidas dentro del perímetro seleccionado.'
        : 'Sin zona dibujada. Se muestran las oportunidades compatibles con los filtros activos del mapa.');
    }

    function homeMapItemMatchesFilters(item, index) {
      const postalCode = cleanText(homeMapPostalCodeFilter || '');
      const postalMatches = !postalCode || String(item.postalCode || '').includes(postalCode);
      const withinSelectedArea = !homeMapSelectedBounds || !window.L
        || homeMapSelectedBounds.contains(L.latLng(getApproximatePoint(item, index)));
      return postalMatches && withinSelectedArea;
    }

    function applyHomeMapPostalFilter() {
      const input = document.getElementById('home-map-postal-filter');
      const postalCode = cleanText(input?.value || '').replace(/\D/g, '').slice(0, 5);
      if (input) input.value = postalCode;
      if (!postalCode) {
        showToast('Introduce un Código Postal para filtrar el mapa de oportunidades.', 'info');
        return;
      }
      homeMapPostalCodeFilter = postalCode;
      renderHomeMapMarkers();
      updateHomeMapAreaStatus(`Filtro por C.P. ${postalCode} activo. Puedes combinarlo con una zona dibujada.`);
    }

    function bindHomeAreaDrawEvents() {
      if (!homeMap || homeMap._captacionAreaDrawBound || !window.L?.Draw) return;
      homeMap._captacionAreaDrawBound = true;
      homeMap.on(L.Draw.Event.CREATED, event => {
        if (event.layerType !== 'rectangle') return;
        if (homeMapSelectionLayer) homeMap.removeLayer(homeMapSelectionLayer);
        homeMapSelectionLayer = event.layer.addTo(homeMap);
        homeMapSelectedBounds = homeMapSelectionLayer.getBounds();
        updateHomeMapAreaStatus('Zona dibujada activa. Solo se muestran las oportunidades incluidas dentro del rectángulo seleccionado.');
        renderHomeMapMarkers();
      });
    }

    async function activateHomeAreaDraw() {
      if (!homeMap) await initHomeMap();
      if (!homeMap || !window.L?.Draw?.Rectangle) {
        showToast('No se pudo activar el dibujo de zona. Revisa la conexión cartográfica.', 'info');
        return;
      }
      if (homeMapDrawHandler) homeMapDrawHandler.disable();
      homeMapDrawHandler = new L.Draw.Rectangle(homeMap, {
        shapeOptions: { color: '#1b67d6', weight: 2, fillColor: '#1b67d6', fillOpacity: 0.12 }
      });
      homeMapDrawHandler.enable();
      updateHomeMapAreaStatus('Dibujo activado: arrastra el ratón sobre el mapa para delimitar la zona que deseas consultar.');
    }

    function clearHomeMapArea() {
      if (homeMap && homeMapSelectionLayer) homeMap.removeLayer(homeMapSelectionLayer);
      homeMapSelectionLayer = null;
      homeMapSelectedBounds = null;
      if (homeMapDrawHandler) homeMapDrawHandler.disable();
      homeMapDrawHandler = null;
      homeMapPostalCodeFilter = '';
      const input = document.getElementById('home-map-postal-filter');
      if (input) input.value = '';
      updateHomeMapAreaStatus();
      renderHomeMapMarkers();
      resetMapToSpain(homeMap);
    }

    async function initHomeMap() {
      const mapEl = document.getElementById('home-map');
      if (!mapEl) return;
      if (!window.L) {
        mapEl.innerHTML = '<div class="p-8 text-sm text-slate-500">Cargando mapa interactivo...</div>';
        const loaded = await ensureLeafletAssets();
        if (!loaded || !window.L) {
          mapEl.innerHTML = '<div class="p-8 text-sm text-slate-500">No se pudo cargar el mapa interactivo. Revisa la conexión.</div>';
          return;
        }
      }
      if (!homeMap) {
        try {
          // El contenedor puede conservar el estado de carga aunque Leaflet ya esté listo.
          mapEl.innerHTML = '';
          homeMap = L.map('home-map', { scrollWheelZoom: false, boxZoom: true }).setView(SPAIN_DEFAULT_MAP_CENTER, SPAIN_DEFAULT_MAP_ZOOM);
          addBaseTileLayer(homeMap);
          homeMapLayer = L.layerGroup().addTo(homeMap);
          homeMap.scrollWheelZoom.disable();
          bindHomeAreaDrawEvents();
        } catch (e) {
          console.warn('Leaflet init warning:', e);
        }
      }
      setTimeout(() => homeMap?.invalidateSize(), 50);
      setTimeout(() => homeMap?.invalidateSize(), 350);
      renderHomeMapMarkers();
    }

    function renderHomeMapMarkers() {
      if (!homeMap || !homeMapLayer || !window.L) return;
      homeMapLayer.clearLayers();
      const points = [];
      const addMarker = (item, kind, index) => {
        if (!homeMapItemMatchesFilters(item, index)) return;
        const point = getApproximatePoint(item, index);
        points.push(point);
        const isProperty = kind === 'property';
        const openFullCardAction = isProperty ? `openMapPropertyCard('${escapeHTML(item.id)}')` : `openMapNeedCard('${escapeHTML(item.id)}')`;
        const marker = L.marker(point, { icon: createMapAmountIcon(isProperty ? item.price : item.budget, kind) });
        marker.bindPopup(`
          <div style="min-width:220px">
            <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:${isProperty ? '#b00016' : '#087653'}">${isProperty ? 'Captación disponible' : 'Demanda activa'}</div>
            <div style="font-size:13px;font-weight:800;color:#10233c;margin-top:5px">${escapeHTML(item.title)}</div>
            <div style="font-size:11px;color:#64748b;margin-top:5px">${escapeHTML(item.province || item.location || item.ccaa || 'España')} · C.P. ${escapeHTML(maskPublicPostalCode(item.postalCode))} · ubicación aproximada</div>
            <div style="font-size:11px;color:#64748b;margin-top:5px">${formatPropertyFeatures(item, true)}</div>
            <div style="font-size:12px;font-weight:800;color:#10233c;margin-top:7px">${formatCurrency(isProperty ? item.price : item.budget)}</div>
            <button onclick="${openFullCardAction}" style="margin-top:9px;width:100%;border:0;border-radius:9px;background:#1b67d6;color:#fff;padding:8px 10px;font-size:11px;font-weight:700;cursor:pointer">Ver ficha completa</button>
          </div>`);
        marker.addTo(homeMapLayer);
      };
      if (homeMapMode === 'all' || homeMapMode === 'properties') properties.forEach((item, index) => addMarker(item, 'property', index));
      if (homeMapMode === 'all' || homeMapMode === 'needs') needs.forEach((item, index) => addMarker(item, 'need', index + properties.length));
      if (homeMapSelectedBounds) {
        homeMap.fitBounds(homeMapSelectedBounds.pad(0.05), { maxZoom: 14 });
      } else {
        fitMapToApproximatePoints(homeMap, points);
      }
    }

    function setHomeMapMode(mode) {
      homeMapMode = mode;
      ['all', 'properties', 'needs'].forEach(option => {
        const button = document.getElementById(`map-filter-${option}`);
        if (!button) return;
        button.classList.toggle('map-filter-active', option === mode);
      });
      renderHomeMapMarkers();
    }

    function getMarketplaceVisibleProperties() {
      const searchQuery = cleanText(document.getElementById('market-search-filter')?.value || '').toLowerCase();
      const referenceQuery = cleanText(document.getElementById('market-reference-filter')?.value || '').toLowerCase();
      const postalCodeQuery = cleanText(document.getElementById('market-postal-code-filter')?.value || '');
      const categoryFilter = document.getElementById('market-category-filter')?.value || 'all';
      const ccaaFilter = document.getElementById('market-ccaa-filter')?.value || 'all';
      const provinceFilter = document.getElementById('market-province-filter')?.value || 'all';
      const municipalityFilter = document.getElementById('market-municipality-filter')?.value || 'all';
      const priceFilter = document.getElementById('market-price-filter')?.value || 'all';
      const sortValue = document.getElementById('market-sort')?.value || 'newest';
      const [minPrice, maxPrice] = priceFilter === 'all' ? [0, Number.POSITIVE_INFINITY] : priceFilter.split('-').map(Number);
      const filtered = properties.filter((prop, index) => {
        if (!isMarketplaceVisibleProperty(prop)) return false;
        const publicationStatus = String(prop.status || prop.publication_status || 'active').toLowerCase();
        if (!['active', 'pending_review', 'published'].includes(publicationStatus)) return false;
        const price = Number(prop.price) || 0;
        const haystack = [prop.title, prop.reference, prop.cadastral_reference_masked, prop.type, prop.ccaa, prop.province, prop.municipality, prop.location, prop.postalCode, prop.description]
          .map(value => String(value || '').toLowerCase()).join(' ');
        const withinSelectedMapArea = !marketplaceMapSelectedBounds || !window.L
          || marketplaceMapSelectedBounds.contains(L.latLng(getApproximatePoint(prop, index)));
        return (!searchQuery || haystack.includes(searchQuery))
          && (!referenceQuery || String(prop.reference || '').toLowerCase().includes(referenceQuery))
          && (!postalCodeQuery || String(prop.postalCode || '').includes(postalCodeQuery))
          && (categoryFilter === 'all' || normalizeOpportunityCategory(prop.type) === categoryFilter)
          && (ccaaFilter === 'all' || prop.ccaa === ccaaFilter)
          && (provinceFilter === 'all' || prop.province === provinceFilter)
          && (municipalityFilter === 'all' || prop.municipality === municipalityFilter)
          && price >= minPrice && price <= maxPrice
          && withinSelectedMapArea;
      });
      return filtered.sort((a, b) => {
        if (sortValue === 'price-low') return (Number(a.price) || 0) - (Number(b.price) || 0);
        if (sortValue === 'price-high') return (Number(b.price) || 0) - (Number(a.price) || 0);
        if (sortValue === 'score') return (Number(b.score || calculatePublicationOpportunityScore(b, 'property')) || 0) - (Number(a.score || calculatePublicationOpportunityScore(a, 'property')) || 0);
        if (sortValue === 'category') {
          return getOpportunityCategoryRank(a.type) - getOpportunityCategoryRank(b.type)
            || (Number(b.date) || 0) - (Number(a.date) || 0);
        }
        if (sortValue === 'oldest') return (Number(a.date) || 0) - (Number(b.date) || 0);
        return (Number(b.date) || 0) - (Number(a.date) || 0);
      });
    }

    function updateMarketplaceViewButtons() {
      const mapBtn = document.getElementById('marketplace-view-map-btn');
      const blockBtn = document.getElementById('marketplace-layout-block-btn');
      const listBtn = document.getElementById('marketplace-layout-list-btn');
      const states = [
        [mapBtn, marketplaceViewMode === 'map'],
        [blockBtn, marketplaceViewMode === 'cards' && marketplaceLayoutMode === 'block'],
        [listBtn, marketplaceViewMode === 'cards' && marketplaceLayoutMode === 'list']
      ];
      states.forEach(([button, active]) => {
        button?.classList.toggle('map-view-active', active);
        button?.classList.toggle('text-slate-500', !active);
      });
    }

    function setMarketplaceView(mode) {
      marketplaceViewMode = mode === 'map' ? 'map' : 'cards';
      const mapPanel = document.getElementById('marketplace-map-panel');
      const cardsGrid = document.getElementById('marketplace-grid');
      mapPanel?.classList.toggle('hidden', marketplaceViewMode !== 'map');
      cardsGrid?.classList.toggle('hidden', marketplaceViewMode === 'map');
      updateMarketplaceViewButtons();
      if (marketplaceViewMode === 'map') setTimeout(initMarketplaceMap, 0);
    }

    function setMarketplaceLayout(layout = 'block') {
      marketplaceLayoutMode = layout === 'list' ? 'list' : 'block';
      marketplaceViewMode = 'cards';
      renderMarketplace();
      setMarketplaceView('cards');
    }

    function refreshMarketplaceView() {
      marketplaceVisibleLimit = LIST_BATCH_SIZE;
      marketplaceCurrentPage = 1;
      marketplaceCarouselOffset = 0;
      const mapPostalInput = document.getElementById('market-map-postal-filter');
      const mainPostalInput = document.getElementById('market-postal-code-filter');
      if (mapPostalInput && document.activeElement !== mapPostalInput) mapPostalInput.value = mainPostalInput?.value || '';
      renderMarketplaceDashboard();
      renderMarketplace();
      if (marketplaceViewMode === 'map') setTimeout(initMarketplaceMap, 0);
    }

    function clearMarketplaceFilters() {
      const setters = {
        'market-search-filter': '', 'market-reference-filter': '', 'market-postal-code-filter': '', 'market-map-postal-filter': '', 'market-price-filter': 'all', 'market-category-filter': 'all', 'market-ccaa-filter': 'all', 'market-province-filter': 'all', 'market-municipality-filter': 'all', 'market-sort': 'newest'
      };
      Object.entries(setters).forEach(([id, value]) => { const element = document.getElementById(id); if (element) element.value = value; });
      TerritorySelector.instances['marketplace-filter']?.setValues({ccaa:'all',province:'all',municipality:'all',postalCode:''});
      clearMarketplaceMapArea(true);
      refreshMarketplaceView();
    }

    function filterMarketplaceByDashboard(type, value) {
      const searchEl = document.getElementById('market-search-filter');
      const refEl = document.getElementById('market-reference-filter');
      const cpEl = document.getElementById('market-postal-code-filter');
      if (refEl) refEl.value = '';
      if (cpEl) cpEl.value = '';
      if (searchEl) searchEl.value = value || '';
      refreshMarketplaceView();
    }

    function updateMarketplaceMapAreaStatus(message = '') {
      const status = document.getElementById('marketplace-map-area-status');
      if (!status) return;
      status.textContent = message || (marketplaceMapSelectedBounds
        ? 'Zona dibujada activa. Solo se muestran las ofertas situadas dentro del perímetro seleccionado.'
        : 'Sin zona dibujada. Se muestran las ofertas compatibles con los filtros activos.');
    }

    function applyMarketplaceMapPostalFilter() {
      const mapPostalInput = document.getElementById('market-map-postal-filter');
      const mainPostalInput = document.getElementById('market-postal-code-filter');
      const postalCode = cleanText(mapPostalInput?.value || '').replace(/\D/g, '').slice(0, 5);
      if (mapPostalInput) mapPostalInput.value = postalCode;
      if (!postalCode) {
        showToast('Introduce un Código Postal para filtrar las ofertas del mapa.', 'info');
        return;
      }
      if (mainPostalInput) mainPostalInput.value = postalCode;
      refreshMarketplaceView();
      setMarketplaceView('map');
      updateMarketplaceMapAreaStatus(`Filtro por C.P. ${postalCode} activo. Puedes combinarlo con una zona dibujada.`);
    }

    function bindMarketplaceAreaDrawEvents() {
      if (!marketplaceMap || marketplaceMap._captacionAreaDrawBound || !window.L?.Draw) return;
      marketplaceMap._captacionAreaDrawBound = true;
      marketplaceMap.on(L.Draw.Event.CREATED, event => {
        if (event.layerType !== 'rectangle') return;
        if (marketplaceMapSelectionLayer) marketplaceMap.removeLayer(marketplaceMapSelectionLayer);
        marketplaceMapSelectionLayer = event.layer.addTo(marketplaceMap);
        marketplaceMapSelectedBounds = marketplaceMapSelectionLayer.getBounds();
        marketplaceVisibleLimit = LIST_BATCH_SIZE;
        marketplaceCurrentPage = 1;
        updateMarketplaceMapAreaStatus('Zona dibujada activa. Solo se muestran las ofertas incluidas dentro del rectángulo seleccionado.');
        renderMarketplace();
        renderMarketplaceMapMarkers();
      });
    }

    async function activateMarketplaceAreaDraw() {
      if (!marketplaceMap) await initMarketplaceMap();
      if (!marketplaceMap || !window.L?.Draw?.Rectangle) {
        showToast('No se pudo activar el dibujo de zona. Revisa la conexión cartográfica.', 'info');
        return;
      }
      if (marketplaceMapDrawHandler) marketplaceMapDrawHandler.disable();
      marketplaceMapDrawHandler = new L.Draw.Rectangle(marketplaceMap, {
        shapeOptions: { color: '#1b67d6', weight: 2, fillColor: '#1b67d6', fillOpacity: 0.12 }
      });
      marketplaceMapDrawHandler.enable();
      updateMarketplaceMapAreaStatus('Dibujo activado: arrastra el ratón sobre el mapa para delimitar la zona que deseas consultar.');
    }

    function clearMarketplaceMapArea(skipRefresh = false) {
      if (marketplaceMap && marketplaceMapSelectionLayer) marketplaceMap.removeLayer(marketplaceMapSelectionLayer);
      marketplaceMapSelectionLayer = null;
      marketplaceMapSelectedBounds = null;
      if (marketplaceMapDrawHandler) marketplaceMapDrawHandler.disable();
      marketplaceMapDrawHandler = null;
      updateMarketplaceMapAreaStatus();
      if (!skipRefresh) refreshMarketplaceView();
      resetMapToSpain(marketplaceMap);
    }

    async function initMarketplaceMap() {
      const mapEl = document.getElementById('marketplace-map');
      if (!mapEl || mapEl.offsetParent === null) return;
      if (!window.L) {
        mapEl.innerHTML = '<div class="p-8 text-sm text-slate-500">Cargando mapa de captaciones...</div>';
        const loaded = await ensureLeafletAssets();
        if (!loaded || !window.L || mapEl.offsetParent === null) {
          mapEl.innerHTML = '<div class="p-8 text-sm text-slate-500">No se pudo cargar el mapa de captaciones. Revisa la conexión cartográfica.</div>';
          return;
        }
      }
      if (!marketplaceMap) {
        marketplaceMap = L.map('marketplace-map', { scrollWheelZoom: false, boxZoom: true }).setView(SPAIN_DEFAULT_MAP_CENTER, SPAIN_DEFAULT_MAP_ZOOM);
        addBaseTileLayer(marketplaceMap);
        marketplaceMapLayer = L.layerGroup().addTo(marketplaceMap);
        marketplaceMap.scrollWheelZoom.disable();
        bindMarketplaceAreaDrawEvents();
      }
      setTimeout(() => marketplaceMap.invalidateSize(), 60);
      renderMarketplaceMapMarkers();
    }

    function renderMarketplaceMapMarkers() {
      if (!marketplaceMap || !marketplaceMapLayer || !window.L) return;
      marketplaceMapLayer.clearLayers();
      const points = [];
      getMarketplaceVisibleProperties().forEach((property, index) => {
        const point = getApproximatePoint(property, index);
        points.push(point);
        const marker = L.marker(point, { icon: createMapAmountIcon(property.price, 'property') });
        marker.bindPopup(`
          <div style="min-width:230px">
            <div style="font-size:10px;font-weight:900;text-transform:uppercase;color:#b00016">Oferta disponible · Ref. ${escapeHTML(property.reference || '')}</div>
            <div style="font-size:13px;font-weight:900;color:#10233c;margin-top:5px">${escapeHTML(property.title)}</div>
            <div style="font-size:11px;color:#64748b;margin-top:5px">${escapeHTML(property.province || property.location || 'España')} · C.P. ${escapeHTML(maskPublicPostalCode(property.postalCode))} · ubicación aproximada</div>
            <div style="font-size:11px;color:#64748b;margin-top:5px">${formatPropertyFeatures(property, true)}</div>
            <div style="font-size:13px;font-weight:900;color:#10233c;margin-top:7px">${formatCurrency(property.price)}</div>
            <button onclick="openMapPropertyCard('${escapeHTML(property.id)}')" style="margin-top:9px;width:100%;border:0;border-radius:9px;background:#1b67d6;color:#fff;padding:8px 10px;font-size:11px;font-weight:700;cursor:pointer">Ver ficha completa</button>
          </div>`);
        marker.addTo(marketplaceMapLayer);
      });
      if (marketplaceMapSelectedBounds) {
        marketplaceMap.fitBounds(marketplaceMapSelectedBounds.pad(0.05), { maxZoom: 14 });
      } else {
        fitMapToApproximatePoints(marketplaceMap, points);
      }
    }

    function updateNeedsMapAreaStatus(message = '') {
      const status = document.getElementById('needs-map-area-status');
      if (!status) return;
      status.textContent = message || (needsMapSelectedBounds
        ? 'Zona dibujada activa. Solo se muestran las demandas situadas dentro del perímetro seleccionado.'
        : 'Sin zona dibujada. Se muestran las demandas compatibles con los filtros activos.');
    }

    function getNeedsMapVisibleList(list = needs) {
      const postalCode = cleanText(needsMapPostalCodeFilter || '');
      return list.filter((need, index) => {
        const globalIndex = needs.findIndex(item => String(item.id) === String(need.id));
        const coordinateIndex = properties.length + (globalIndex >= 0 ? globalIndex : index);
        const postalMatches = !postalCode || String(need.postalCode || '').includes(postalCode);
        const withinSelectedArea = !needsMapSelectedBounds || !window.L
          || needsMapSelectedBounds.contains(L.latLng(getApproximatePoint(need, coordinateIndex)));
        return postalMatches && withinSelectedArea;
      });
    }

    function applyNeedsMapPostalFilter() {
      const mapInput = document.getElementById('needs-map-postal-filter');
      const mainInput = document.getElementById('need-filter-postal-code');
      const postalCode = cleanText(mapInput?.value || '').replace(/\D/g, '').slice(0, 5);
      if (mapInput) mapInput.value = postalCode;
      if (!postalCode) {
        showToast('Introduce un Código Postal para filtrar el mapa de demandas.', 'info');
        return;
      }
      needsMapPostalCodeFilter = postalCode;
      if (mainInput) mainInput.value = postalCode;
      filterNeeds();
      updateNeedsMapAreaStatus(`Filtro por C.P. ${postalCode} activo. Puedes combinarlo con una zona dibujada.`);
    }

    function bindNeedsAreaDrawEvents() {
      if (!needsMap || needsMap._captacionAreaDrawBound || !window.L?.Draw) return;
      needsMap._captacionAreaDrawBound = true;
      needsMap.on(L.Draw.Event.CREATED, event => {
        if (event.layerType !== 'rectangle') return;
        if (needsMapSelectionLayer) needsMap.removeLayer(needsMapSelectionLayer);
        needsMapSelectionLayer = event.layer.addTo(needsMap);
        needsMapSelectedBounds = needsMapSelectionLayer.getBounds();
        updateNeedsMapAreaStatus('Zona dibujada activa. Solo se muestran las demandas incluidas dentro del rectángulo seleccionado.');
        filterNeeds();
      });
    }

    async function activateNeedsAreaDraw() {
      if (!needsMap) await initNeedsMap();
      if (!needsMap || !window.L?.Draw?.Rectangle) {
        showToast('No se pudo activar el dibujo de zona. Revisa la conexión cartográfica.', 'info');
        return;
      }
      if (needsMapDrawHandler) needsMapDrawHandler.disable();
      needsMapDrawHandler = new L.Draw.Rectangle(needsMap, {
        shapeOptions: { color: '#15936a', weight: 2, fillColor: '#15936a', fillOpacity: 0.12 }
      });
      needsMapDrawHandler.enable();
      updateNeedsMapAreaStatus('Dibujo activado: arrastra el ratón sobre el mapa para delimitar la zona que deseas consultar.');
    }

    function clearNeedsMapArea() {
      if (needsMap && needsMapSelectionLayer) needsMap.removeLayer(needsMapSelectionLayer);
      needsMapSelectionLayer = null;
      needsMapSelectedBounds = null;
      if (needsMapDrawHandler) needsMapDrawHandler.disable();
      needsMapDrawHandler = null;
      needsMapPostalCodeFilter = '';
      const mapInput = document.getElementById('needs-map-postal-filter');
      const mainInput = document.getElementById('need-filter-postal-code');
      if (mapInput) mapInput.value = '';
      if (mainInput) mainInput.value = '';
      updateNeedsMapAreaStatus();
      filterNeeds();
      resetMapToSpain(needsMap);
    }

    function toggleNeedsMap() {
      needsMapVisible = !needsMapVisible;
      const panel = document.getElementById('needs-map-panel');
      const button = document.getElementById('needs-map-toggle-btn');
      panel?.classList.toggle('hidden', !needsMapVisible);
      if (button) button.textContent = needsMapVisible ? '✕ Ocultar mapa de demandas' : '🗺 Mostrar mapa de demandas';
      if (needsMapVisible) setTimeout(initNeedsMap, 0);
    }

    async function initNeedsMap() {
      const mapEl = document.getElementById('needs-map');
      if (!mapEl || mapEl.offsetParent === null) return;
      if (!window.L) {
        mapEl.innerHTML = '<div class="p-8 text-sm text-slate-500">Cargando mapa de demandas...</div>';
        const loaded = await ensureLeafletAssets();
        if (!loaded || !window.L || mapEl.offsetParent === null) {
          mapEl.innerHTML = '<div class="p-8 text-sm text-slate-500">No se pudo cargar el mapa de demandas. Revisa la conexión cartográfica.</div>';
          return;
        }
      }
      if (!needsMap) {
        needsMap = L.map('needs-map', { scrollWheelZoom: false, boxZoom: true }).setView(SPAIN_DEFAULT_MAP_CENTER, SPAIN_DEFAULT_MAP_ZOOM);
        addBaseTileLayer(needsMap);
        needsMapLayer = L.layerGroup().addTo(needsMap);
        needsMap.scrollWheelZoom.disable();
        bindNeedsAreaDrawEvents();
      }
      setTimeout(() => needsMap.invalidateSize(), 60);
      renderNeedsMapMarkers(lastFilteredNeeds);
    }

    function renderNeedsMapMarkers(list = needs) {
      if (!needsMap || !needsMapLayer || !window.L) return;
      needsMapLayer.clearLayers();
      const points = [];
      getNeedsMapVisibleList(list).forEach((need, index) => {
        const globalIndex = needs.findIndex(item => String(item.id) === String(need.id));
        const coordinateIndex = properties.length + (globalIndex >= 0 ? globalIndex : index);
        const point = getApproximatePoint(need, coordinateIndex);
        points.push(point);
        const marker = L.marker(point, { icon: createMapAmountIcon(need.budget, 'need') });
        marker.bindPopup(`
          <div style="min-width:230px">
            <div style="font-size:10px;font-weight:900;text-transform:uppercase;color:#087653">Demanda activa</div>
            <div style="font-size:13px;font-weight:900;color:#10233c;margin-top:5px">${escapeHTML(need.title)}</div>
            <div style="font-size:11px;color:#64748b;margin-top:5px">${escapeHTML(need.province || need.ccaa || 'España')} · C.P. ${escapeHTML(maskPublicPostalCode(need.postalCode))} · ubicación aproximada</div>
            <div style="font-size:11px;color:#64748b;margin-top:5px">${formatPropertyFeatures(need, true)}</div>
            <div style="font-size:13px;font-weight:900;color:#10233c;margin-top:7px">Presupuesto: ${formatCurrency(need.budget)}</div>
            <button onclick="openMapNeedCard('${escapeHTML(need.id)}')" style="margin-top:9px;width:100%;border:0;border-radius:9px;background:#10233c;color:#fff;padding:8px 10px;font-size:11px;font-weight:700;cursor:pointer">Ver ficha completa</button>
          </div>`);
        marker.addTo(needsMapLayer);
      });
      if (needsMapSelectedBounds) {
        needsMap.fitBounds(needsMapSelectedBounds.pad(0.05), { maxZoom: 14 });
      } else {
        fitMapToApproximatePoints(needsMap, points);
      }
    }

    function toggleAuthPanel(mode) {
      const loginForm = document.getElementById('auth-login-form');
      const registerForm = document.getElementById('auth-register-form');
      const loginTab = document.getElementById('auth-login-tab');
      const registerTab = document.getElementById('auth-register-tab');
      if (!loginForm || !registerForm) return;
      const isLogin = mode === 'login';
      loginForm.classList.toggle('hidden', !isLogin);
      registerForm.classList.toggle('hidden', isLogin);
      loginTab?.classList.toggle('auth-tab-active', isLogin);
      registerTab?.classList.toggle('auth-tab-active', !isLogin);
      loginTab?.classList.toggle('text-slate-500', !isLogin);
      registerTab?.classList.toggle('text-slate-500', isLogin);
    }

    async function hashText(text) {
      if (window.crypto?.subtle) {
        const buffer = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
        return Array.from(new Uint8Array(buffer)).map(byte => byte.toString(16).padStart(2, '0')).join('');
      }
      let hash = 0;
      for (let index = 0; index < text.length; index++) hash = ((hash << 5) - hash) + text.charCodeAt(index) | 0;
      return `fallback-${Math.abs(hash)}`;
    }

    function getDemoUsers() {
      if (CAPTACION_PRODUCTION_MODE) return {};
      try { return JSON.parse(localStorage.getItem('captacion_demo_users_v4')) || {}; }
      catch (error) { return {}; }
    }

    function getDemoSession() {
      try {
        const sess = JSON.parse(sessionStorage.getItem('captacion_app_session_v1')) || JSON.parse(localStorage.getItem('captacion_demo_session_v4'));
        if (sess && (sess.email || sess.name)) return sess;
      } catch (error) { }
      return null;
    }


    let registrationPromptTimer = null;
    let registrationPromptDismissedAt = 0;
    let registrationPromptDismissedForSession = false;
    let registrationPromptStarted = false;
    let registrationExitIntentShown = false;
    let registrationMobileIntentTimer = null;

    function hasActiveProfessionalSession() {
      const session = getDemoSession();
      if (session) return true;
      return Boolean(CAPTACION_MAILCHIMP?.loggedIn && CAPTACION_MAILCHIMP?.emailVerified);
    }

    function getRegistrationPrompt() {
      let modal = document.getElementById('registration-required-modal');
      if (modal) return modal;
      modal = document.createElement('div');
      modal.id = 'registration-required-modal';
      modal.className = 'fixed inset-0 z-[120] hidden flex items-center justify-center p-4 bg-navy-dark/70 backdrop-blur-sm';
      modal.innerHTML = `
        <div class="relative w-full max-w-md rounded-3xl bg-white border border-slate-200 shadow-2xl p-6 text-center">
          <button type="button" onclick="dismissRegistrationPrompt()" aria-label="Cerrar" class="absolute top-3 right-4 text-slate-400 hover:text-slate-700 text-xl font-black">x</button>
          <span class="inline-flex px-3 py-1 rounded-full bg-green-light text-green text-[10px] font-black uppercase tracking-wider">Acceso profesional</span>
          <h3 class="text-xl font-black text-navy mt-4">Accede a oportunidades inmobiliarias profesionales</h3>
          <p class="text-sm text-slate-500 mt-3 leading-relaxed">Únete a Compra Captación y conecta captaciones, demandas activas y otros profesionales con más control.</p>
          <div class="mt-6 grid grid-cols-1 gap-3">
            <button type="button" onclick="goToRegisterFromPrompt()" class="px-4 py-3 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md">Crear cuenta gratis</button>
            <button type="button" onclick="dismissRegistrationPrompt()" class="px-4 py-3 rounded-xl border border-slate-200 text-slate-600 text-xs font-black">Ahora no</button>
          </div>
        </div>`;
      document.body.appendChild(modal);
      return modal;
    }

    function socialLoginButtonsHtml(fullLabels = true) {
      if (!CAPTACION_MAILCHIMP?.socialLoginEnabled) {
        return '';
      }
      const googleLabel = fullLabels ? 'Continuar con Google' : 'Google';
      const appleLabel = fullLabels ? 'Continuar con Apple' : 'Apple';
      return `<div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
        <a href="${CAPTACION_MAILCHIMP.loginUrl}?action=siwe&provider=google" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-slate-200 bg-white text-xs font-black text-navy hover:bg-slate-50">${googleLabel}</a>
        <a href="${CAPTACION_MAILCHIMP.loginUrl}?action=siwe&provider=apple" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-slate-200 bg-white text-xs font-black text-navy hover:bg-slate-50">${appleLabel}</a>
      </div>`;
    }

    function getProfessionalSubscriptionModal() {
      let modal = document.getElementById('professional-subscription-modal');
      if (modal) return modal;
      modal = document.createElement('div');
      modal.id = 'professional-subscription-modal';
      modal.className = 'fixed inset-0 z-[130] hidden flex items-center justify-center p-4 bg-navy-dark/65 backdrop-blur-sm';
      modal.innerHTML = `
        <div class="relative w-full max-w-lg max-h-[92vh] overflow-y-auto rounded-3xl bg-white border border-slate-200 shadow-2xl p-6 sm:p-8">
          <button type="button" onclick="closeProfessionalSubscriptionModal()" aria-label="Cerrar" class="absolute top-3 right-4 text-slate-400 hover:text-slate-700 text-xl font-black">x</button>
          <span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase tracking-wider">Cuenta profesional</span>
          <h3 class="text-2xl font-black text-navy mt-4">Crea tu cuenta gratuita</h3>
          <p class="text-sm text-slate-500 mt-2">Empieza a buscar y compartir oportunidades profesionales. No necesitas tarjeta de crédito.</p>
          
          <!-- 3 Pasos del siguiente paso -->
          <div class="mt-4 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 grid grid-cols-3 gap-2 text-center text-[10px]">
            <div>
              <span class="w-5 h-5 rounded-full bg-blue/10 text-blue font-black inline-flex items-center justify-center mb-1">1</span>
              <strong class="block text-slate-800 font-bold">Alta en 30s</strong>
              <span class="text-slate-500">Sin tarjeta</span>
            </div>
            <div>
              <span class="w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-600 font-black inline-flex items-center justify-center mb-1">2</span>
              <strong class="block text-slate-800 font-bold">3 Créditos</strong>
              <span class="text-slate-500">30 días</span>
            </div>
            <div>
              <span class="w-5 h-5 rounded-full bg-navy/10 text-navy font-black inline-flex items-center justify-center mb-1">3</span>
              <strong class="block text-slate-800 font-bold">Pacto 50/50</strong>
              <span class="text-slate-500">Cobro notaría</span>
            </div>
          </div>

          ${socialLoginButtonsHtml(true)}
          <div class="mt-5 flex items-center gap-3 text-[10px] font-black uppercase tracking-[0.16em] text-slate-400"><span class="h-px flex-1 bg-slate-200"></span><span>o con email profesional</span><span class="h-px flex-1 bg-slate-200"></span></div>
          <form id="professional-subscription-form" onsubmit="handleProfessionalRegistration(event)" class="mt-5 space-y-3.5">
<label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Nombre completo *</span><input id="professional-register-name" type="text" required autocomplete="name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" /></label>
<label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Tipo de perfil *</span><select id="professional-register-profile-type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm"><option value="independent">Profesional independiente</option><option value="agency">Agencia inmobiliaria</option></select></label>
<label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Nombre comercial o agencia <em class="font-normal">(opcional)</em></span><input id="professional-register-business-name" type="text" autocomplete="organization" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" /></label>
<label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Correo electrónico profesional *</span><input id="professional-register-email" type="email" required autocomplete="email" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" /></label>
<label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Contraseña *</span><div class="relative"><input id="professional-register-password" type="password" required autocomplete="new-password" minlength="8" placeholder="Mínimo 8 caracteres" class="w-full px-4 py-2.5 pr-24 rounded-xl border border-slate-200 text-sm" /><button type="button" onclick="togglePasswordVisibility('professional-register-password', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[10px] font-black text-blue hover:bg-blue-light">Mostrar</button></div></label>
<label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Teléfono móvil (Opcional)</span><input id="professional-register-phone" type="tel" pattern="[0-9]{9,15}" autocomplete="tel" placeholder="Ej: 600123456" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm" /></label>
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 text-xs leading-relaxed text-slate-600 cursor-pointer"><input id="professional-register-privacy" type="checkbox" required class="mt-0.5 h-4 w-4 shrink-0" /><span>Acepto la <a href="${CAPTACION_BASE_PATH.replace(/\/+$/, '')}/privacidad" class="legal-link">política de privacidad</a> y el tratamiento de datos para mi cuenta profesional. *</span></label>
            <p id="professional-register-error" class="hidden rounded-xl bg-red-50 border border-red-100 px-3 py-2 text-xs text-red-700" role="alert"></p>
            <p class="rounded-xl bg-green-light px-3 py-2 text-xs font-bold text-green">Acceso gratuito · Sin tarjeta · 3 créditos de bienvenida incluidos (válidos 30 días, no acumulables).</p><button id="professional-register-submit" type="submit" class="w-full py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black shadow-md">Crear mi cuenta gratuita</button>
            <button type="button" onclick="closeProfessionalSubscriptionModal();openProfessionalAccess()" class="w-full py-2 text-xs font-black text-blue">¿Ya tienes cuenta? Iniciar sesión</button>
          </form>
        </div>`;
      document.body.appendChild(modal);
      return modal;
    }

    const CONVERSION_INTENT_KEY = 'captacion_conversion_intent_v1';
    const RESOURCE_INTENT_KEY = 'captacion_resource_intent_v1';

    function trackConversionEvent(eventName, details = {}) {
      const allowedEvents = new Set(['home_search_intent_click','home_publish_intent_click','signup_started','signup_completed','demand_started','demand_published','offer_started','offer_published','marketplace_search','access_requested','resource_downloaded','contact_submitted']);
      if (!allowedEvents.has(eventName)) return;
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ event:eventName, ...details });
    }

    function setConversionIntent(intent) {
      if (!['buscar', 'publicar'].includes(intent)) return;
      localStorage.setItem(CONVERSION_INTENT_KEY, intent);
    }

    function getConversionIntent() {
      const intent = localStorage.getItem(CONVERSION_INTENT_KEY);
      return ['buscar', 'publicar'].includes(intent) ? intent : '';
    }

    function clearConversionIntent() {
      localStorage.removeItem(CONVERSION_INTENT_KEY);
    }

    function openIntentForm(path, accordionId, formId) {
      trackConversionEvent(formId === 'need-publication-form' ? 'demand_started' : 'offer_started');
      navigateTo(path);
      window.setTimeout(() => {
        const accordion = document.getElementById(accordionId);
        if (accordion) accordion.open = true;
        document.getElementById(formId)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    }

    function startIntentFlow(intent, source = 'manual') {
      if (source === 'hero') trackConversionEvent(intent === 'buscar' ? 'home_search_intent_click' : 'home_publish_intent_click');
      setConversionIntent(intent);
      if (hasActiveProfessionalSession()) {
        clearConversionIntent();
        if (intent === 'buscar') {
          navigateTo('/publicar?tipo=demanda');
          switchPublishMode('demanda');
          setNeedStep(1);
          return;
        }
        if (intent === 'publicar') {
          navigateTo('/publicar?tipo=oferta');
          switchPublishMode('oferta');
          setOfferStep(1);
          return;
        }
        return;
      }
      openProfessionalSubscriptionModal(source, intent);
    }

    function completePostAuthIntent() {
      const intent = getConversionIntent();
      if (!intent) return false;
      clearConversionIntent();
      if (intent === 'buscar') {
        navigateTo('/publicar?tipo=demanda');
        switchPublishMode('demanda');
        setNeedStep(1);
        return true;
      }
      if (intent === 'publicar') {
        navigateTo('/publicar?tipo=oferta');
        switchPublishMode('oferta');
        setOfferStep(1);
        return true;
      }
      return true;
    }

    function startResourceDownload(resourceId) {
      const allResources = (CAPTACION_MAILCHIMP?.resources && CAPTACION_MAILCHIMP.resources.length >= 3)
        ? CAPTACION_MAILCHIMP.resources
        : [
            { id: 'colaboracion', title: 'Contrato Oficial 50/50', has_static_pdf: true, pdf_url: 'assets/docs/plantilla-acuerdo-colaboracion-honorarios-captacion-app.pdf', plan_access: 'free' },
            { id: 'nda', title: 'Acuerdo NDA', has_static_pdf: true, pdf_url: 'assets/docs/plantilla-nda-confidencialidad-captacion-app.pdf', plan_access: 'free' },
            { id: 'parte_visita', title: 'Parte de Visita 50/50', has_static_pdf: true, pdf_url: 'assets/docs/plantilla-parte-visita-colaboracion-captacion-app.pdf', plan_access: 'free' },
            { id: 'pitch_exclusiva', title: 'Dossier Exclusiva Propietario', has_static_pdf: true, pdf_url: 'assets/docs/dossier-exclusiva-compartida-propietario-captacion.pdf', plan_access: 'professional' },
            { id: 'score_comprador', title: 'Matriz Cualificación Financiera', has_static_pdf: true, pdf_url: 'assets/docs/matriz-precualificacion-financiera-comprador.pdf', plan_access: 'professional' },
            { id: 'acm_vera', title: 'Informe ACM con IA Vera', has_static_pdf: true, pdf_url: 'assets/docs/informe-acm-valoracion-mercado-ia-vera.pdf', plan_access: 'professional' },
            { id: 'oferta_reserva', title: 'Propuesta Formal Reserva', has_static_pdf: true, pdf_url: 'assets/docs/propuesta-formal-compra-deposito-reserva.pdf', plan_access: 'professional' },
            { id: 'guia_fiscal', title: 'Guía Fiscalidad Compraventas', has_static_pdf: true, pdf_url: 'assets/docs/guia-fiscalidad-inmobiliaria-liquidaciones.pdf', plan_access: 'professional' },
            { id: 'arras_1454', title: 'Contrato Arras Art. 1454 CC', has_static_pdf: true, pdf_url: 'assets/docs/contrato-arras-penitenciales-art1454-cc.pdf', plan_access: 'professional' }
          ];

      const resource = allResources.find(item => String(item.id || item.resource_id) === String(resourceId));
      if (!resource) return;

      if (!CAPTACION_MAILCHIMP?.loggedIn) {
        localStorage.setItem(RESOURCE_INTENT_KEY, String(resourceId));
        openProfessionalSubscriptionModal('recurso-descargable');
        return;
      }

      if (resource.plan_access !== 'free' && typeof hasProfessionalMembershipAccess === 'function' && !hasProfessionalMembershipAccess()) {
        openProfessionalSubscriptionModal('recurso-pro-locked');
        return;
      }

      if (typeof trackConversionEvent === 'function') {
        trackConversionEvent('resource_downloaded');
      }
      if (['colaboracion', 'nda', 'parte_visita'].includes(String(resource.id || resource.resource_id))) {
        openEditableLegalTemplate(String(resource.id || resource.resource_id));
        return;
      }
      showToast(`Descargando documento: ${resource.title || 'Recurso'}...`, 'success');
      if (resource.pdf_url) {
        window.open(resource.pdf_url, '_blank');
      }
    }

    function openEditableLegalTemplate(templateId) {
      const catalog = {
        colaboracion: { title: 'Acuerdo de colaboración 50/50', description: 'Completa los datos básicos para preparar una copia de trabajo. La firma y revisión legal deben realizarse por las partes.', partyA: 'Agencia captadora', partyB: 'Agencia colaboradora' },
        nda: { title: 'Acuerdo de confidencialidad (NDA)', description: 'Genera una copia editable con los datos de las partes y el expediente protegido.', partyA: 'Parte divulgadora', partyB: 'Parte receptora' },
        parte_visita: { title: 'Parte de visita y presentación', description: 'Registra la visita profesional y la presentación del comprador sin exponer datos innecesarios.', partyA: 'Agencia captadora', partyB: 'Agencia colaboradora' }
      };
      const item = catalog[templateId];
      const body = document.getElementById('resource-tool-body');
      const modal = document.getElementById('resource-tool-modal');
      if (!item || !body || !modal) return;
      document.getElementById('resource-tool-title').textContent = item.title;
      document.getElementById('resource-tool-description').textContent = item.description;
      body.innerHTML = `<form onsubmit="generateEditableLegalTemplate(event, '${templateId}')" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="text-xs font-bold text-slate-500">Fecha<input id="template-date" type="date" required value="${new Date().toISOString().slice(0,10)}" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label>
          <label class="text-xs font-bold text-slate-500">Referencia interna<input id="template-reference" maxlength="60" placeholder="Ej.: OP-2026-001" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="text-xs font-bold text-slate-500">${item.partyA}<input id="template-party-a" required maxlength="120" placeholder="Nombre o razón social" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label>
          <label class="text-xs font-bold text-slate-500">${item.partyB}<input id="template-party-b" required maxlength="120" placeholder="Nombre o razón social" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label>
        </div>
        <label class="block text-xs font-bold text-slate-500">Descripción breve del expediente<textarea id="template-summary" required maxlength="600" rows="3" placeholder="Zona orientativa, tipo de operación y observaciones no sensibles" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea></label>
        <label class="flex items-start gap-2 text-[11px] text-slate-500"><input id="template-confirm" type="checkbox" required class="mt-0.5"><span>Confirmo que no introduciré contraseñas, datos bancarios, dirección exacta ni datos personales innecesarios.</span></label>
        <div class="flex flex-wrap gap-2"><button type="submit" class="flex-1 min-w-[12rem] py-3 rounded-xl bg-blue text-white text-xs font-black">Generar vista previa</button><button type="button" onclick="closeResourceToolModal()" class="px-4 py-3 rounded-xl border border-slate-200 text-navy text-xs font-bold">Cancelar</button></div>
      </form><div id="editable-template-preview" class="hidden mt-5"></div>`;
      modal.classList.remove('hidden');
    }

    function generateEditableLegalTemplate(event, templateId) {
      event?.preventDefault();
      const values = {
        date: document.getElementById('template-date')?.value || '',
        reference: document.getElementById('template-reference')?.value || 'Sin referencia',
        partyA: document.getElementById('template-party-a')?.value || '',
        partyB: document.getElementById('template-party-b')?.value || '',
        summary: document.getElementById('template-summary')?.value || ''
      };
      const names = { colaboracion: 'Acuerdo de colaboración 50/50', nda: 'Acuerdo de confidencialidad (NDA)', parte_visita: 'Parte de visita y presentación' };
      const preview = document.getElementById('editable-template-preview');
      if (!preview || !names[templateId]) return;
      preview.innerHTML = `<div id="editable-template-print" class="bg-white text-slate-900 border border-slate-200 rounded-2xl p-6 sm:p-8" style="font-family:Arial,sans-serif"><div style="border-bottom:3px solid #12345b;padding-bottom:14px;margin-bottom:22px"><div style="font-size:11px;color:#15936a;font-weight:700;letter-spacing:.12em;text-transform:uppercase">COMPRA CAPTACIÓN · DOCUMENTO DE TRABAJO</div><h4 style="font-size:24px;margin:8px 0;color:#12345b">${escapeHTML(names[templateId])}</h4><div style="font-size:11px;color:#64748b">Fecha: ${escapeHTML(values.date)} · Referencia: ${escapeHTML(values.reference)}</div></div><p style="font-size:13px;line-height:1.65">Las partes identificadas abajo manifiestan su intención de colaborar profesionalmente bajo las condiciones que acuerden y conforme a la normativa aplicable.</p><div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:22px 0"><div style="padding:12px;background:#f1f5f9;border-radius:8px"><b>${templateId === 'nda' ? 'Parte divulgadora' : 'Parte A'}</b><br>${escapeHTML(values.partyA)}</div><div style="padding:12px;background:#f1f5f9;border-radius:8px"><b>${templateId === 'nda' ? 'Parte receptora' : 'Parte B'}</b><br>${escapeHTML(values.partyB)}</div></div><h5 style="font-size:14px;color:#12345b">Resumen del expediente</h5><p style="font-size:13px;line-height:1.65;white-space:pre-wrap">${escapeHTML(values.summary)}</p><div style="margin-top:34px;padding-top:18px;border-top:1px solid #cbd5e1;display:grid;grid-template-columns:1fr 1fr;gap:30px;font-size:11px;color:#475569"><div>Firma parte A:<br><br>________________________</div><div>Firma parte B:<br><br>________________________</div></div><p style="font-size:10px;color:#94a3b8;margin-top:28px">Borrador generado por Compra Captación. Revisar y completar las cláusulas específicas antes de firmar. No sustituye asesoramiento jurídico.</p></div><div class="flex flex-wrap gap-2 mt-3"><button type="button" onclick="downloadEditableLegalTemplate('${templateId}')" class="flex-1 min-w-[12rem] py-3 rounded-xl bg-navy text-white text-xs font-black">Descargar PDF</button><button type="button" onclick="openEditableLegalTemplate('${templateId}')" class="px-4 py-3 rounded-xl border border-slate-200 text-navy text-xs font-bold">Editar datos</button></div>`;
      preview.classList.remove('hidden');
      preview.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      showToast('Vista previa preparada. Revisa los datos antes de descargar.', 'success');
    }

    async function downloadEditableLegalTemplate(templateId) {
      const element = document.getElementById('editable-template-print');
      if (!element) return;
      try {
        const html2pdf = await loadExecutivePdfLibrary();
        await html2pdf().set({ margin: 10, filename: `plantilla-${templateId}-${new Date().toISOString().slice(0,10)}.pdf`, image: { type: 'jpeg', quality: .96 }, html2canvas: { scale: 2, backgroundColor: '#ffffff' }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }, pagebreak: { mode: ['css', 'legacy'] } }).from(element).save();
        showToast('PDF generado y descargado.', 'success');
      } catch (error) {
        showToast(error?.message || 'No se pudo generar el PDF.', 'error');
      }
    }

    function completePostAuthResourceIntent() {
      const resourceId = localStorage.getItem(RESOURCE_INTENT_KEY);
      if (!resourceId) return false;
      localStorage.removeItem(RESOURCE_INTENT_KEY);
      navigateTo('/recursos');
      window.setTimeout(() => startResourceDownload(resourceId), 150);
      return true;
    }

    // ==============================================================
    // ONBOARDING CONVERSACIONAL INTERACTIVO CON VERA IA (5 PASOS)
    // ==============================================================
    let currentVeraOnboardingStep = 1;
    let isVeraTyping = false;

    function openVeraOnboardingModal(force = false) {
      const session = typeof getDemoSession === 'function' ? getDemoSession() : null;
      if (!session && !force) return false;
      
      const isCompleted = localStorage.getItem('captacion_onboarding_completed_v1') === '1' || session?.onboardingCompleted;
      if (isCompleted && !force) return false;

      const modal = document.getElementById('vera-onboarding-modal');
      if (!modal) return false;

      currentVeraOnboardingStep = 1;
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      renderVeraOnboardingStep(1);
      return true;
    }

    async function closeVeraOnboardingModal(markDone = true) {
      const modal = document.getElementById('vera-onboarding-modal');
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
      if (markDone) {
        localStorage.setItem('captacion_onboarding_completed_v1', '1');
        const session = typeof getDemoSession === 'function' ? getDemoSession() : null;
        if (session) {
          session.onboardingCompleted = true;
          try {
            sessionStorage.setItem('captacion_app_session_v1', JSON.stringify(session));
            localStorage.setItem('captacion_demo_session_v4', JSON.stringify(session));
          } catch(e){}
        }
        try {
          fetch('/api/auth.php?action=complete_onboarding', { method: 'POST', credentials: 'same-origin' });
        } catch(e){}
      }
      navigateTo('/area-privada');
    }

    function openVeraDatosCiegosModal() {
      const m = document.getElementById('vera-datos-ciegos-modal');
      if (m) {
        m.classList.remove('hidden');
        m.classList.add('flex');
      }
    }

    function closeVeraDatosCiegosModal() {
      const m = document.getElementById('vera-datos-ciegos-modal');
      if (m) {
        m.classList.add('hidden');
        m.classList.remove('flex');
      }
    }

    function showVeraTypingIndicator(show = true) {
      const typingEl = document.getElementById('vera-onboarding-typing');
      if (typingEl) {
        typingEl.classList.toggle('hidden', !show);
      }
    }

    function renderVeraOnboardingStep(step) {
      currentVeraOnboardingStep = step;
      window.CAPTACION_ONBOARDING_CONTEXT = {
        guide_id: 'vera-onboarding',
        phase_id: `phase-${step}`,
        current_step: step,
        user_level: localStorage.getItem('captacion_user_level_v1') || 'junior',
        current_route: window.location.hash || window.location.pathname
      };
      const chatBody = document.getElementById('vera-onboarding-chat-body');
      const stepLabel = document.getElementById('vera-onboarding-step-label');
      const progressPct = document.getElementById('vera-onboarding-progress-pct');
      const progressBar = document.getElementById('vera-onboarding-progress-bar');
      if (!chatBody) return;

      const pcts = { 1: 20, 2: 40, 3: 60, 4: 80, 5: 100 };
      const currentPct = pcts[step] || 20;

      if (stepLabel) stepLabel.textContent = `Paso ${step} de 5`;
      if (progressPct) progressPct.textContent = `${currentPct}%`;
      if (progressBar) progressBar.style.width = `${currentPct}%`;

      // Simulación de escritura interactiva (typing effect)
      chatBody.innerHTML = '';
      showVeraTypingIndicator(true);
      isVeraTyping = true;

      setTimeout(() => {
        showVeraTypingIndicator(false);
        isVeraTyping = false;
        
        let html = '';
        const session = (typeof getDemoSession === 'function' ? getDemoSession() : null) || {};
        const userName = session.name || session.agency || 'colega';
        const userId = session.email ? Math.abs(session.email.split('').reduce((a,b)=>(((a<<5)-a)+b.charCodeAt(0))|0, 0) % 90000 + 10000) : '7721';
        const referralUrl = `https://compracaptacion.com/registro?ref=CC-${userId}`;

        if (step === 1) {
          html = `
            <div class="space-y-4 animate-fadeIn">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue to-purple-600 flex items-center justify-center text-white text-xs font-black shrink-0 mt-0.5 shadow-sm">✦</div>
                <div class="p-4 rounded-2xl rounded-tl-none bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs sm:text-sm leading-relaxed border border-slate-200/60 dark:border-slate-700/60 shadow-xs">
                  <p>¡Hola <strong>${escapeHTML(userName)}</strong>! Soy <strong>Vera</strong>, tu copiloto en Compra Captación.</p>
                  <p class="mt-2.5">Aquí no vas a perder el tiempo filtrando chats caóticos de WhatsApp ni respondiendo a curiosos sin dinero.</p>
                  <p class="mt-2.5">Mi único trabajo es hacer que <strong>cruces tus inmuebles en exclusiva o tus compradores solventes</strong> con otros profesionales de España para cerrar operaciones al <strong>50/50</strong> con total seguridad jurídica. No cobramos comisiones de venta; todo lo que cierres en notaría es para vosotros.</p>
                  <p class="mt-2.5 font-bold text-navy dark:text-white">¿Empezamos por activar tu cuenta?</p>
                </div>
              </div>

              <!-- Botones de Acción Paso 1 -->
              <div class="pt-2 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <button type="button" onclick="renderVeraOnboardingStep(2)" class="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm transition-all shadow-md flex items-center justify-center gap-2 hover:scale-[1.02]">
                  <span>Sí, vamos al grano</span>
                  <span>→</span>
                </button>
                <button type="button" onclick="openVeraDatosCiegosModal()" class="w-full py-3.5 px-4 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-navy dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2">
                  <span>🛡️</span>
                  <span>¿Cómo protegéis mis datos?</span>
                </button>
              </div>
            </div>
          `;
        } else if (step === 2) {
          html = `
            <div class="space-y-4 animate-fadeIn">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue to-purple-600 flex items-center justify-center text-white text-xs font-black shrink-0 mt-0.5 shadow-sm">✦</div>
                <div class="p-4 rounded-2xl rounded-tl-none bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs sm:text-sm leading-relaxed border border-slate-200/60 dark:border-slate-700/60 shadow-xs">
                  <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-[11px] font-black uppercase mb-2">
                    🎁 Monedero Activado: +3 Créditos
                  </div>
                  <p>Por registrarte hoy, te acabo de activar <strong>3 créditos de bienvenida en tu monedero</strong>.</p>
                  <p class="mt-2.5">Cada crédito es la llave para desbloquear el expediente completo y los datos de contacto directo de un agente verificado que tiene la contraparte de tu operación. Dispones de <strong>30 días naturales de validez</strong> para gastar estos créditos gratis antes de que expiren (no acumulables).</p>
                  <p class="mt-2.5 font-bold text-navy dark:text-white">Queremos que cierres tu primera operación compartida 50/50 sin arriesgar un solo euro.</p>
                </div>
              </div>

              <!-- Botones de Acción Paso 2 -->
              <div class="pt-2 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <button type="button" onclick="renderVeraOnboardingStep(3)" class="w-full py-3.5 px-4 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs sm:text-sm transition-all shadow-md flex items-center justify-center gap-2 hover:scale-[1.02]">
                  <span>Entendido, ¿cómo los uso?</span>
                  <span>→</span>
                </button>
                <button type="button" onclick="showToast('Suscripciones desde 29€/mes o recargas sueltas con descuentos para miembros.', 'info'); setTimeout(()=>renderVeraOnboardingStep(3), 900);" class="w-full py-3.5 px-4 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2">
                  <span>💎</span>
                  <span>¿Cuánto cuesta recargar después?</span>
                </button>
              </div>
            </div>
          `;
        } else if (step === 3) {
          html = `
            <div class="space-y-4 animate-fadeIn">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue to-purple-600 flex items-center justify-center text-white text-xs font-black shrink-0 mt-0.5 shadow-sm">✦</div>
                <div class="p-4 rounded-2xl rounded-tl-none bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs sm:text-sm leading-relaxed border border-slate-200/60 dark:border-slate-700/60 shadow-xs">
                  <p>Para que yo empiece a trabajar buscando cruces, necesito saber qué tienes en cartera.</p>
                  <p class="mt-2 font-bold text-navy dark:text-white">Dime qué opción se adapta mejor a tu jornada de hoy:</p>
                </div>
              </div>

              <!-- Rutas condicionales Paso 3 -->
              <div class="space-y-2.5">
                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-blue transition-all">
                  <div class="flex items-center justify-between gap-2">
                    <strong class="text-xs sm:text-sm font-black text-navy dark:text-white flex items-center gap-2">
                      <span>🏢</span> Tengo inmuebles en exclusiva (Oferta)
                    </strong>
                    <span class="px-2 py-0.5 rounded-full bg-blue/10 text-blue text-[9px] font-black">Recomendado</span>
                  </div>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                    Sube tu propiedad. Ocultaré la dirección exacta, piso, catastro y fotos sensibles bajo <strong>Datos Ciegos</strong>. Solo verán tipología, zona y reparto al 50%.
                  </p>
                  <div class="mt-3 flex gap-2">
                    <input id="vera-onboarding-portal-url" type="url" placeholder="Pega aquí la URL de tu anuncio en Idealista / Fotocasa" class="flex-1 px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-navy dark:text-white focus:outline-none focus:border-blue" />
                    <button type="button" onclick="handleVeraImportPortalUrl()" class="px-3.5 py-2 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-bold shrink-0">Subir</button>
                  </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-emerald-500 transition-all">
                  <strong class="text-xs sm:text-sm font-black text-navy dark:text-white flex items-center gap-2">
                    <span>🎯</span> Tengo compradores cualificados (Demanda)
                  </strong>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                    Registra los requisitos de zona, presupuesto y tipología. Sin datos personales de tu comprador. Escanearé la red para encontrar su vivienda ideal.
                  </p>
                  <button type="button" onclick="closeVeraOnboardingModal(true); navigateTo('/buscar-captaciones');" class="mt-2.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold">
                    + Registrar Demanda
                  </button>
                </div>

                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-purple-500 transition-all">
                  <strong class="text-xs sm:text-sm font-black text-navy dark:text-white flex items-center gap-2">
                    <span>↻</span> Quiero importar mi cartera masiva vía XML
                  </strong>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                    Pega la pasarela XML de tu CRM (Inmovilla, Witei, etc.). Normalizaré las fichas y aplicaré el blindaje de Datos Ciegos automáticamente en minutos.
                  </p>
                  <button type="button" onclick="closeVeraOnboardingModal(true); switchPrivateDashboardPanel('feeds', true); navigateTo('/area-privada');" class="mt-2.5 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold">
                    Conectar Pasarela XML
                  </button>
                </div>
              </div>

              <div class="pt-2 text-right">
                <button type="button" onclick="renderVeraOnboardingStep(4)" class="w-full py-3.5 px-4 rounded-xl bg-navy dark:bg-blue text-white font-bold text-xs sm:text-sm transition-all shadow-md flex items-center justify-center gap-2">
                  <span>Continuar al Paso 4: Cruces y Blindaje Legal</span>
                  <span>→</span>
                </button>
              </div>
            </div>
          `;
        } else if (step === 4) {
          html = `
            <div class="space-y-4 animate-fadeIn">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue to-purple-600 flex items-center justify-center text-white text-xs font-black shrink-0 mt-0.5 shadow-sm">✦</div>
                <div class="p-4 rounded-2xl rounded-tl-none bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs sm:text-sm leading-relaxed border border-slate-200/60 dark:border-slate-700/60 shadow-xs">
                  <p>¡Hecho! Ahora mismo mi algoritmo está buscando coincidencias en la base de datos nacional.</p>
                  <p class="mt-2 font-bold text-navy dark:text-white">¿Qué pasa cuando encuentre un cruce de un 90% o más?</p>
                  <div class="mt-3 space-y-2.5 pl-1">
                    <div class="flex items-start gap-2 text-xs">
                      <span class="w-5 h-5 rounded-full bg-blue/10 text-blue font-bold flex items-center justify-center shrink-0">1</span>
                      <p>Te enviaré un <strong>aviso instantáneo</strong> por WhatsApp o email con la ficha ciega.</p>
                    </div>
                    <div class="flex items-start gap-2 text-xs">
                      <span class="w-5 h-5 rounded-full bg-blue/10 text-blue font-bold flex items-center justify-center shrink-0">2</span>
                      <p>Si decides colaborar, reservarás <strong>1 crédito</strong> durante 72 horas. El contacto directo solo se habilita tras la aceptación y firma de ambas partes.</p>
                    </div>
                    <div class="flex items-start gap-2 text-xs">
                      <span class="w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-600 font-bold flex items-center justify-center shrink-0">3</span>
                      <p><strong>Antes de levantar el teléfono:</strong> Te generaré el <em>Contrato de Colaboración Homologado 50/50</em> y el acuerdo de confidencialidad (NDA) para firmarlos digitalmente en 1 clic.</p>
                    </div>
                  </div>
                  <p class="mt-3 text-emerald-600 dark:text-emerald-400 font-bold text-xs">Tus honorarios de miles de euros quedan blindados por ley antes de hacer la primera visita.</p>
                </div>
              </div>

              <!-- Botones de Acción Paso 4 -->
              <div class="pt-2">
                <button type="button" onclick="renderVeraOnboardingStep(5)" class="w-full py-3.5 px-4 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs sm:text-sm transition-all shadow-md flex items-center justify-center gap-2 hover:scale-[1.02]">
                  <span>Siguiente paso: Bucle de Referidos</span>
                  <span>→</span>
                </button>
              </div>
            </div>
          `;
        } else if (step === 5) {
          html = `
            <div class="space-y-4 animate-fadeIn">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue to-purple-600 flex items-center justify-center text-white text-xs font-black shrink-0 mt-0.5 shadow-sm">✦</div>
                <div class="p-4 rounded-2xl rounded-tl-none bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs sm:text-sm leading-relaxed border border-slate-200/60 dark:border-slate-700/60 shadow-xs">
                  <p>Por último, la colaboración inmobiliaria funciona mejor cuando confías en la red. Si traes a tus colegas del sector, os premiamos a ambos:</p>
                  <ul class="mt-2.5 space-y-2 text-xs">
                    <li class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-bold">
                      <span>⭐</span> <span><strong>Gana +3 créditos extra</strong> cuando tu referido se registre y cargue su primera cartera XML (Hito A).</span>
                    </li>
                    <li class="flex items-center gap-2 text-purple-600 dark:text-purple-400 font-bold">
                      <span>🏷️</span> <span><strong>Consigue un 50% de descuento recurrente</strong> en tu cuota cuando pase a plan de pago (Hito B).</span>
                    </li>
                  </ul>
                  <p class="mt-3 text-slate-500 dark:text-slate-400 text-xs">Copia tu enlace personalizado de invitación:</p>
                  
                  <div class="mt-2 flex items-center gap-2">
                    <input id="vera-onboarding-ref-input" type="text" readonly value="${referralUrl}" class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono font-bold text-navy dark:text-white select-all" />
                    <button type="button" onclick="copyVeraReferralLink()" class="px-3.5 py-2 rounded-xl bg-slate-200 dark:bg-slate-700 hover:bg-blue hover:text-white text-navy dark:text-white text-xs font-bold shrink-0 transition-colors">
                      Copiar 📋
                    </button>
                  </div>
                </div>
              </div>

              <!-- Botones de Cierre Paso 5 -->
              <div class="pt-2 space-y-2">
                <button type="button" onclick="closeVeraOnboardingModal(true)" class="w-full py-4 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm uppercase tracking-wider transition-all shadow-lg flex items-center justify-center gap-2 hover:scale-[1.02]">
                  <span>Completado. ¡Ir al panel principal!</span>
                  <span>🚀</span>
                </button>
              </div>
            </div>
          `;
        }

        chatBody.innerHTML = html;
        chatBody.scrollTop = 0;
      }, 1200);
    }

    function copyVeraReferralLink() {
      const input = document.getElementById('vera-onboarding-ref-input');
      if (input) {
        input.select();
        navigator.clipboard?.writeText?.(input.value);
        showToast('¡Enlace de referido copiado al portapapeles!', 'success');
      }
    }

    async function createCaptationDiagnosisDraft(payload = {}) {
      const response = await fetch('/api/diagnoses.php?action=create', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          record_type: payload.record_type || 'property',
          payload: payload.payload || {}
        })
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.ok) {
        throw new Error(data.error || 'No se pudo guardar el diagnóstico.');
      }
      return data;
    }

    async function handleVeraImportPortalUrl() {
      const urlInput = document.getElementById('vera-onboarding-portal-url');
      const val = cleanText(urlInput?.value || '');
      if (!val) {
        showToast('Por favor introduce la URL del anuncio en portal.', 'info');
        return;
      }
      try {
        showToast('Guardando un borrador seguro para revisarlo con Vera...', 'info');
        const diagnosis = await createCaptationDiagnosisDraft({
          record_type: 'property',
          payload: {
            source: 'portal_import',
            source_url: val,
            imported_at: new Date().toISOString(),
            status: 'draft'
          }
        });
        sessionStorage.setItem('captation_last_diagnosis_id', String(diagnosis.id || ''));
        closeVeraOnboardingModal(true);
        navigateTo('/ofrecer-captacion');
        showToast('Borrador guardado. Revísalo antes de publicar.', 'success');
      } catch (error) {
        showToast(error?.message || 'No se pudo guardar el borrador.', 'error');
      }
    }

    function showFirstActionChooserIfNeeded() {
      if (openVeraOnboardingModal()) return true;
      return false;
    }

    function openProfessionalSubscriptionModal(source = 'manual', intent = '') {
      if (hasActiveProfessionalSession()) {
        if (intent) startIntentFlow(intent, source);
        else navigateTo('/area-privada');
        return;
      }
      if (intent) setConversionIntent(intent);
      trackConversionEvent('signup_started', { source:String(source).slice(0, 60) });
      const modal = getProfessionalSubscriptionModal();
      modal.dataset.source = source;
      modal.classList.remove('hidden');
      setTimeout(() => document.getElementById('professional-register-email')?.focus(), 50);
    }

    function closeProfessionalSubscriptionModal() {
      getProfessionalSubscriptionModal().classList.add('hidden');
    }

    function countryCodeOptionsHtml(selected = '+34') {
      const countries = [
        ['+34','España'], ['+351','Portugal'], ['+33','Francia'], ['+39','Italia'], ['+49','Alemania'], ['+44','Reino Unido'],
        ['+1','Estados Unidos/Canadá'], ['+52','México'], ['+54','Argentina'], ['+56','Chile'], ['+57','Colombia'], ['+51','Perú'],
        ['+58','Venezuela'], ['+593','Ecuador'], ['+598','Uruguay'], ['+595','Paraguay'], ['+55','Brasil'], ['+212','Marruecos']
      ];
      return countries.map(([code, name]) => `<option value="${code}" ${code === selected ? 'selected' : ''}>${name} (${code})</option>`).join('');
    }

    function buildInternationalPhone(countryId, phoneId) {
      const countryCode = cleanText(document.getElementById(countryId)?.value || '+34').replace(/[^0-9+]/g, '');
      let phone = cleanText(document.getElementById(phoneId)?.value || '').replace(/[^0-9+]/g, '');
      if (phone.startsWith('+')) return phone;
      phone = phone.replace(/^0+/, '');
      return `${countryCode}${phone}`;
    }

    function togglePasswordVisibility(inputId, button) {
      const input = document.getElementById(inputId);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      if (button) button.textContent = show ? 'Ocultar' : 'Mostrar';
    }

    async function registerProfessionalAccount(fields, ui = {}) {
      const { name, email, phone, password, privacyAccepted, commercialConsent, profileType = 'independent', businessName = '' } = fields;
      const fail = message => { if (ui.errorBox) { ui.errorBox.textContent = message; ui.errorBox.classList.remove('hidden'); } };
      if (!/^\S+@\S+\.\S+$/.test(email)) return fail('Revisa el correo electrónico.');
      if (password.length < 8) return fail('La contraseña debe tener al menos 8 caracteres.');
      if (!privacyAccepted) return fail('Debes aceptar la Política de privacidad.');
      ui.errorBox?.classList.add('hidden');
      if (ui.submit) { ui.submit.disabled = true; ui.submit.textContent = 'Creando cuenta...'; }
      let backendReached = false;
      try {
        if (!CAPTACION_MAILCHIMP?.registerEndpoint) throw new Error('backend_unavailable');
        const referralCode = new URLSearchParams(window.location.search).get('ref') || '';
        const response = await fetch(CAPTACION_MAILCHIMP.registerEndpoint, { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-WP-Nonce':CAPTACION_MAILCHIMP.nonce}, body:JSON.stringify({name,email,phone,password,privacyAccepted,commercialConsent,profileType,businessName,referralCode}) });
        backendReached = true;
        const data = await response.json();
        if (!response.ok || !data?.ok) throw new Error(data?.message || 'No se pudo crear la cuenta.');
        closeProfessionalSubscriptionModal();
        document.getElementById('professional-access-modal')?.classList.add('hidden');
        getRegistrationPrompt().classList.add('hidden');
        ui.form?.reset();
        if (!getConversionIntent() && !localStorage.getItem(RESOURCE_INTENT_KEY)) localStorage.setItem('captacion_pending_first_action_v1', '1');
        trackConversionEvent('signup_completed');
        showToast(data.message || 'Cuenta creada. Revisa tu correo y confirma el registro. Cuando inicies sesión, te llevaremos directamente a la acción que elegiste.', 'success');
        return true;
      } catch (error) {
        if (backendReached) fail(error.message || 'No se pudo crear la cuenta.');
        else fail('No se pudo conectar con WordPress. El registro no se ha creado.');
        return false;
      } finally {
        if (ui.submit) { ui.submit.disabled = false; ui.submit.textContent = 'Crear mi cuenta gratis'; }
      }
    }

    async function handleProfessionalRegistration(event) {
      event.preventDefault();
      return registerProfessionalAccount({
        name: cleanText(document.getElementById('professional-register-name')?.value || ''),
        email: cleanText(document.getElementById('professional-register-email')?.value || '').toLowerCase(),
        phone: cleanText(document.getElementById('professional-register-phone')?.value || ''),
        profileType: document.getElementById('professional-register-profile-type')?.value || 'independent',
        businessName: cleanText(document.getElementById('professional-register-business-name')?.value || ''),
        password: document.getElementById('professional-register-password')?.value || '',
        privacyAccepted: Boolean(document.getElementById('professional-register-privacy')?.checked),
        commercialConsent: false
      }, { form:event.target, errorBox:document.getElementById('professional-register-error'), submit:document.getElementById('professional-register-submit') });
    }

    async function handleInlineProfessionalRegistration(event) {
      event.preventDefault();
      const email = cleanText(document.getElementById('inline-register-email')?.value || '').toLowerCase();
      return registerProfessionalAccount({
        name: email.split('@')[0] || 'Profesional',
        email,
        phone: '',
        password: document.getElementById('inline-register-password')?.value || '',
        privacyAccepted: Boolean(document.getElementById('inline-register-privacy')?.checked),
        commercialConsent: false,
        profileType: 'independent',
        businessName: ''
      }, { form:event.target, errorBox:document.getElementById('inline-register-error'), submit:document.getElementById('inline-register-submit') });
    }

    function showRegistrationPrompt(force = false) {
      if (hasActiveProfessionalSession()) return;
      if (captacionIsComplianzVisible()) {
        if (!force) scheduleRegistrationPrompt(15000);
        return;
      }
      if (force) {
        if (registrationExitIntentShown || sessionStorage.getItem('captacion_exit_prompt_seen') === '1') return;
        registrationExitIntentShown = true;
        sessionStorage.setItem('captacion_exit_prompt_seen','1');
      } else if (registrationPromptDismissedForSession || sessionStorage.getItem('captacion_subscription_prompt_dismissed') === '1') {
        return;
      }
      const modal = getRegistrationPrompt();
      modal.dataset.exitIntent = force ? '1' : '0';
      modal.classList.remove('hidden');
    }

    function dismissRegistrationPrompt() {
      registrationPromptDismissedAt = Date.now();
      registrationPromptDismissedForSession = true;
      sessionStorage.setItem('captacion_subscription_prompt_dismissed','1');
      getRegistrationPrompt().classList.add('hidden');
    }

    function goToRegisterFromPrompt() {
      getRegistrationPrompt().classList.add('hidden');
      openProfessionalSubscriptionModal('subscription-prompt');
    }

    async function handlePromptLogin(event) {
      event.preventDefault();
      const email = cleanText(document.getElementById('prompt-login-email')?.value || '').toLowerCase();
      const password = document.getElementById('prompt-login-password')?.value || '';
      const user = getDemoUsers()[email];
      if (!user || user.passwordHash !== await hashText(password)) {
        showToast('Credenciales no válidas.', 'info');
        return;
      }
      localStorage.setItem('captacion_demo_session_v4', JSON.stringify({ name: user.name, agency: user.agency, email, whatsapp: user.whatsapp || '', startedAt: Date.now() }));
      getRegistrationPrompt().classList.add('hidden');
      updateAuthModule();
      showToast('Sesión iniciada correctamente.', 'success');
    }

    function scheduleRegistrationPrompt(delay = 45000) {
      if (registrationPromptTimer) clearTimeout(registrationPromptTimer);
      registrationPromptTimer = setTimeout(() => showRegistrationPrompt(false), delay);
    }

    function startRegistrationPromptCycle() {
      if (registrationPromptStarted) return;
      registrationPromptStarted = true;
      if (hasActiveProfessionalSession()) return;
      scheduleRegistrationPrompt(45000);
      document.addEventListener('mouseleave', event => {
        if (event.clientY <= 8 && !document.hidden) showRegistrationPrompt(true);
      });
      const scheduleMobileExitIntent = () => {
        if (window.innerWidth > 768 || registrationExitIntentShown || hasActiveProfessionalSession()) return;
        const progress = (window.scrollY + window.innerHeight) / Math.max(document.documentElement.scrollHeight, 1);
        if (progress < 0.65) return;
        if (registrationMobileIntentTimer) clearTimeout(registrationMobileIntentTimer);
        registrationMobileIntentTimer = setTimeout(() => showRegistrationPrompt(true), 20000);
      };
      window.addEventListener('scroll', scheduleMobileExitIntent, {passive:true});
      window.addEventListener('touchstart', () => {
        if (registrationMobileIntentTimer) clearTimeout(registrationMobileIntentTimer);
      }, {passive:true});
    }

    function requireRegisteredAction(actionLabel = 'realizar esta accion') {
      if (hasActiveProfessionalSession()) return true;
      showRegistrationPrompt(true);
      showToast('Crea tu cuenta gratuita para ' + actionLabel + '.', 'info');
      return false;
    }



    function syncMailchimpContact(payload) {
      if (!CAPTACION_MAILCHIMP?.endpoint || !payload?.email || payload?.commercialConsent !== true) return Promise.resolve(false);
      return fetch(CAPTACION_MAILCHIMP.endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
      })
        .then(response => response.ok ? response.json() : Promise.reject(response))
        .then(data => Boolean(data?.ok))
        .catch(() => false);
    }


    function sendNotificationEmail(type, payload = {}) {
      const session = getDemoSession?.();
      const email = payload.email || session?.email || '';
      if (!CAPTACION_MAILCHIMP?.notificationsEndpoint || !email) return Promise.resolve(false);
      return fetch(CAPTACION_MAILCHIMP.notificationsEndpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          type,
          email,
          name: payload.name || session?.name || '',
          agency: payload.agency || session?.agency || '',
          reference: payload.reference || '',
          message: payload.message || ''
        })
      })
        .then(response => response.ok ? response.json() : Promise.reject(response))
        .then(data => Boolean(data?.ok))
        .catch(() => false);
    }

    function persistWpRecord(recordType, payload = {}, options = {}) {
      if (!CAPTACION_MAILCHIMP?.recordsEndpoint) return Promise.resolve(false);
      const session = getDemoSession?.();
      const recordKey = options.recordKey || payload?.id || payload?.reference || `${recordType}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
      return fetch(CAPTACION_MAILCHIMP.recordsEndpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          ...(CAPTACION_MAILCHIMP.nonce ? { 'X-WP-Nonce': CAPTACION_MAILCHIMP.nonce } : {})
        },
        body: JSON.stringify({
          record_type: recordType,
          record_key: String(recordKey),
          user_email: options.userEmail || payload?.userEmail || session?.email || '',
          title: options.title || payload?.title || payload?.reference || payload?.id || recordType,
          status: options.status || payload?.status || '',
          related_id: options.relatedId || payload?.relatedId || payload?.propertyId || payload?.needId || '',
          privacy_scope: options.privacyScope || '',
          payload
        })
      })
        .then(response => response.ok ? response.json() : Promise.reject(response))
        .then(data => Boolean(data?.ok))
        .catch(() => false);
    }

    function canUseWordPressRecords() {
      return Boolean(CAPTACION_MAILCHIMP && CAPTACION_MAILCHIMP.recordsEndpoint);
    }

    function canUsePublicWordPressRecords() {
      return Boolean(CAPTACION_MAILCHIMP && CAPTACION_MAILCHIMP.publicRecordsEndpoint);
    }

    async function fetchWpRecords(recordType, limit = 5000, options = {}) {
      const isPublic = options.public === true;
      if (isPublic ? !canUsePublicWordPressRecords() : !canUseWordPressRecords()) return [];
      const endpoint = isPublic ? CAPTACION_MAILCHIMP.publicRecordsEndpoint : CAPTACION_MAILCHIMP.recordsEndpoint;
      const url = new URL(endpoint, window.location.origin);
      url.searchParams.set('record_type', recordType);
      url.searchParams.set('limit', String(limit));
      const response = await fetch(url.toString(), {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-WP-Nonce': CAPTACION_MAILCHIMP.nonce
        }
      });
      if (!response.ok) throw new Error(`No se pudieron cargar registros ${recordType}.`);
      const data = await response.json();
      return Array.isArray(data?.records) ? data.records : [];
    }

    function payloadFromWpRecord(record = {}) {
      const payload = record.payload && typeof record.payload === 'object' ? record.payload : {};
      return {
        ...record,
        ...payload,
        id: payload.id || record.record_key || `record-${record.id}`,
        recordKey: record.record_key || payload.recordKey || payload.record_key || '',
        title: payload.title || record.title || '',
        propertyType: payload.propertyType || payload.property_type || record.property_type || 'Piso',
        operationType: payload.operationType || payload.operation_type || record.operation_type || 'colaboracion_50_50',
        price: Number(payload.price !== undefined ? payload.price : record.price) || 0,
        budget: Number(payload.budget !== undefined ? payload.budget : (record.price || 0)) || 0,
        commission: Number(payload.commission !== undefined ? payload.commission : (record.commission_amount || 0)) || 0,
        commissionPercentage: Number(payload.commissionPercentage || record.commission_percentage || 50),
        province: payload.province || record.province || '',
        municipality: payload.municipality || record.municipality || '',
        zone: payload.zone || record.zone || '',
        addressPublic: payload.addressPublic || payload.address_public || record.address_public || '',
        bedrooms: Number(payload.bedrooms !== undefined ? payload.bedrooms : record.bedrooms) || 0,
        bathrooms: Number(payload.bathrooms !== undefined ? payload.bathrooms : record.bathrooms) || 0,
        surface: Number(payload.surface !== undefined ? payload.surface : record.surface_m2) || 0,
        isExclusive: Boolean(payload.isExclusive !== undefined ? payload.isExclusive : record.is_exclusive),
        description: payload.description || payload.description_public || record.description_public || '',
        features: Array.isArray(payload.features) ? payload.features : (Array.isArray(record.features) ? record.features : []),
        images: Array.isArray(payload.images) && payload.images.length > 0 ? payload.images : (Array.isArray(record.images) ? record.images : []),
        userEmail: payload.userEmail || record.user_email || '',
        ownerUserId: Number(record.owner_user_id || record.user_id || payload.ownerUserId || payload.owner_user_id || 0),
        ownerEmail: payload.ownerEmail || payload.owner_email || record.user_email || '',
        wpRecordId: record.id || '',
        importBatchId: record.import_batch_id || '',
        dataOrigin: record.data_origin || '',
        wpStatus: record.status || '',
        wpUpdatedAt: record.updated_at || ''
      };
    }

    function mergeRecordsById(currentRows, serverRows, normalizeFn) {
      const merged = [];
      const seen = new Set();
      serverRows.map(payloadFromWpRecord).map(normalizeFn).forEach(row => {
        if (!row?.id || seen.has(row.id)) return;
        seen.add(row.id);
        merged.push(row);
      });
      currentRows.filter(row => !row.wpRecordId && !row.importBatchId).forEach(row => {
        if (!row?.id || seen.has(row.id)) return;
        seen.add(row.id);
        merged.push(row);
      });
      return merged;
    }

    async function loadWordPressRealEstateRecords() {
      if (!canUseWordPressRecords() && !canUsePublicWordPressRecords()) return false;
      try {
        const publicRecords = await Promise.all([
          fetchWpRecords('property', 5000, { public: true }),
          fetchWpRecords('need', 5000, { public: true })
        ]);
        // La consulta privada no debe bloquear los anuncios públicos del marketplace.
        // Una sesión recién hidratada puede tardar o no tener aún permisos completos.
        let privateRecords = [[], []];
        if (CAPTACION_MAILCHIMP?.loggedIn) {
          try {
            privateRecords = await Promise.all([
              fetchWpRecords('property'),
              fetchWpRecords('need')
            ]);
          } catch (privateError) {
            console.warn('[Compra Captación] No se pudieron cargar los registros privados; se mantienen los anuncios públicos.', privateError);
          }
        }
        const propertyRecords = [...privateRecords[0], ...publicRecords[0]];
        const needRecords = [...privateRecords[1], ...publicRecords[1]];
        properties = mergeRecordsById(properties, propertyRecords, normalizePropertyRecord);
        needs = mergeRecordsById(needs, needRecords, normalizeNeedRecord);
        persistDemoState();
        renderMarketplace();
        renderDashboard();
        filterNeeds();
        renderHome();
        renderPrivateOffers();
        renderPrivateDemands();
        return true;
      } catch (error) {
        console.warn('[Compra Captación] Sincronización finalizada.', error);
        return false;
      }
    }

    function syncMailchimpSession(tag, source, extra = {}) {
      if (CAPTACION_MAILCHIMP?.commercialConsent !== true) return Promise.resolve(false);
      const session = getDemoSession?.();
      return syncMailchimpContact({
        email: extra.email || session?.email || '',
        name: extra.name || session?.name || '',
        agency: extra.agency || session?.agency || '',
        phone: extra.phone || session?.whatsapp || '',
        source,
        tags: [tag],
        commercialConsent: true
      });
    }

    async function handleLogin(event) {
      event.preventDefault();
      const email = cleanText(document.getElementById('auth-login-email').value).toLowerCase();
      const password = document.getElementById('auth-login-password').value;
      const user = getDemoUsers()[email];
      if (!user || user.passwordHash !== await hashText(password)) {
        showToast('Credenciales no válidas.', 'info');
        return;
      }
      localStorage.setItem('captacion_demo_session_v4', JSON.stringify({ name: user.name, agency: user.agency, email, whatsapp: user.whatsapp || '', startedAt: Date.now() }));
      event.target.reset();
      updateAuthModule();
      showToast('Sesión iniciada correctamente.', 'success');
    }

    async function logoutDemo() {
      try {
        await fetch('/api/auth.php?action=logout', { method: 'POST', credentials: 'same-origin' });
      } catch(e) {}
      if (CAPTACION_MAILCHIMP?.loggedIn && CAPTACION_MAILCHIMP?.logoutEndpoint) {
        try { await fetch(CAPTACION_MAILCHIMP.logoutEndpoint,{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':CAPTACION_MAILCHIMP.nonce}}); } catch(error) {}
      }
      if (typeof CAPTACION_MAILCHIMP !== 'undefined') CAPTACION_MAILCHIMP.loggedIn = false;
      localStorage.removeItem('captacion_demo_session_v4');
      sessionStorage.removeItem('captacion_app_session_v1');
      sessionStorage.removeItem('captacion_professional_registered');
      updateAuthModule();
      showToast('Sesión cerrada. Acceso al panel privado revocado.', 'info');
      navigateTo('/');
    }

    async function handleHomeInlineRegister(event) {
      event.preventDefault();
      const name = cleanText(document.getElementById('inline-reg-name')?.value || '');
      const agency = cleanText(document.getElementById('inline-reg-agency')?.value || '');
      const email = cleanText(document.getElementById('inline-reg-email')?.value || '').toLowerCase();
      const phone = cleanText(document.getElementById('inline-reg-phone')?.value || '');
      const password = document.getElementById('inline-reg-password')?.value || '';
      const passwordRepeat = document.getElementById('inline-reg-password-repeat')?.value || '';
      const submitBtn = document.getElementById('btn-inline-register-submit');
      const regForm = document.getElementById('home-inline-register-form');
      const successBox = document.getElementById('inline-reg-success-message');
      const emailTarget = document.getElementById('reg-sent-email-target');

      if (!name || !email || !password || !passwordRepeat) {
        showToast('Por favor completa los campos obligatorios: Nombre, Email y Contraseñas.', 'error');
        return;
      }
      if (password.length < 6) {
        showToast('La contraseña debe tener al menos 6 caracteres.', 'error');
        return;
      }
      if (password !== passwordRepeat) {
        showToast('Las contraseñas no coinciden. Por favor verifícalas.', 'error');
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando enlace de activación...';
      }

      try {
        let resData = null;
        try {
          const res = await fetch('/api/auth.php?action=register', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password, full_name: name, agency_name: agency, phone })
          });
          resData = await res.json();
        } catch (apiErr) {}

        if (resData && resData.ok === false && resData.error) {
          showToast(resData.error, 'error');
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Crear cuenta y recibir enlace de activación →';
          }
          return;
        }

        // Mostrar pantalla de confirmación por email
        if (emailTarget) emailTarget.textContent = email;
        if (regForm) regForm.classList.add('hidden');
        if (successBox) successBox.classList.remove('hidden');

        showToast('¡Registro recibido! Revisa tu email para activar la cuenta y recibir tus 3 créditos de bienvenida.', 'success');
      } catch (err) {
        showToast('Error al registrar. Inténtalo de nuevo.', 'error');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Crear cuenta y recibir enlace de activación →';
        }
      }
    }

    function resetInlineRegisterForm() {
      const regForm = document.getElementById('home-inline-register-form');
      const successBox = document.getElementById('inline-reg-success-message');
      if (regForm) {
        regForm.reset();
        regForm.classList.remove('hidden');
      }
      if (successBox) successBox.classList.add('hidden');
    }

    async function handleHomeInlineLogin(event) {
      event.preventDefault();
      const email = cleanText(document.getElementById('inline-login-email')?.value || '').toLowerCase();
      const password = document.getElementById('inline-login-password')?.value || '';
      const submitBtn = document.getElementById('btn-inline-login-submit');

      if (!email || !password) {
        showToast('Introduce tu email y contraseña.', 'error');
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Verificando credenciales...';
      }

      try {
        let userProfile = null;
        try {
          const res = await fetch('/api/auth.php?action=login', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
          });
          const data = await res.json();
          if (data && data.ok && data.user) {
            userProfile = data.user;
          }
        } catch(apiErr) {}

        const displayName = userProfile?.full_name || userProfile?.name || email.split('@')[0];
        const displayAgency = userProfile?.agency_name || userProfile?.agency || 'Profesional inmobiliario';
        const sessionData = {
          name: displayName,
          agency: displayAgency,
          email,
          whatsapp: userProfile?.phone || '',
          startedAt: Date.now()
        };
        localStorage.setItem('captacion_demo_session_v4', JSON.stringify(sessionData));
        sessionStorage.setItem('captacion_app_session_v1', JSON.stringify(sessionData));
        sessionStorage.setItem('captacion_professional_registered', 'true');

        updateAuthModule();
        showToast('Sesión iniciada correctamente. Redirigiendo al panel...', 'success');
        setTimeout(() => {
          navigateTo('/area-privada');
        }, 300);
      } catch (err) {
        showToast('Error al iniciar sesión.', 'error');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Iniciar sesión en el Panel Privado →';
        }
      }
    }

    function syncHeaderAuthState() {
      const session = typeof getDemoSession === 'function' ? getDemoSession() : null;
      const isLoggedIn = Boolean(session && (session.email || session.name));

      const unauthDesktop = document.getElementById('header-auth-unauthenticated');
      const authDesktop = document.getElementById('header-auth-authenticated');
      const unauthDrawer = document.getElementById('drawer-auth-unauthenticated');
      const authDrawer = document.getElementById('drawer-auth-authenticated');
      const agentNameEl = document.getElementById('header-agent-display-name');

      if (unauthDesktop) {
        unauthDesktop.classList.toggle('hidden', isLoggedIn);
        unauthDesktop.classList.toggle('inline-flex', !isLoggedIn);
      }
      if (authDesktop) {
        authDesktop.classList.toggle('hidden', !isLoggedIn);
        authDesktop.classList.toggle('inline-flex', isLoggedIn);
      }

      if (unauthDrawer) unauthDrawer.classList.toggle('hidden', isLoggedIn);
      if (authDrawer) authDrawer.classList.toggle('hidden', !isLoggedIn);

      if (agentNameEl && session) {
        agentNameEl.textContent = session.name || session.agency || 'Mi Panel';
      }
    }
    window.syncHeaderAuthState = syncHeaderAuthState;

    function updateAuthModule() {
      const guest = document.getElementById('auth-guest-panel');
      const sessionPanel = document.getElementById('auth-session-panel');
      const homeRegisterLogin = document.getElementById('home-register-login');
      const session = getDemoSession();
      
      // Sincronizar botones de cabecera y drawer según autenticación
      syncHeaderAuthState();

      // Ocultar sección completa de registro para usuarios ya identificados
      if (homeRegisterLogin) {
        homeRegisterLogin.classList.toggle('hidden', Boolean(session));
      }
      
      if (!guest || !sessionPanel) return;
      guest.classList.toggle('hidden', Boolean(session));
      sessionPanel.classList.toggle('hidden', !session);
      if (session) {
        if (!sessionPanel.querySelector('.auth-session-visual')) {
          const visual = document.createElement('div');
          visual.className = 'auth-session-visual overflow-hidden rounded-2xl border border-slate-200 bg-slate-100';
          visual.innerHTML = `<img src="${escapeHTML(CAPTACION_SESSION_IMAGE)}" alt="Entorno profesional inmobiliario" width="640" height="480" loading="lazy" decoding="async" class="h-40 w-full object-cover"><div class="px-4 py-3 text-xs font-semibold text-slate-600">Tu espacio profesional está listo para continuar.</div>`;
          sessionPanel.querySelector('div')?.appendChild(visual);
        }
        const name = document.getElementById('auth-session-name');
        const agency = document.getElementById('auth-session-agency');
        const displayName = session?.name || session?.display_name || session?.username || session?.email?.split('@')[0] || 'Profesional';
        const displayAgency = session?.agency || (session?.profileType === 'agency' ? 'Agencia inmobiliaria' : 'Profesional independiente');
        if (name) name.textContent = `Hola, ${displayName}`;
        if (agency) agency.textContent = `${displayAgency}${session?.email ? ` · ${session.email}` : ''}${session?.whatsapp ? ` · WhatsApp ${session.whatsapp}` : ''}`;
      }
      
      if (typeof initVeraChatSession === 'function') {
        veraInitialized = false;
        initVeraChatSession();
      }
      
      if (session && !window.veraAutoWakeTriggered) {
        window.veraAutoWakeTriggered = true;
        setTimeout(() => {
          const windowEl = document.getElementById('vera-chat-window');
          if (windowEl && !windowEl.classList.contains('is-active')) {
            windowEl.classList.add('is-active');
            if (typeof initVeraChatSession === 'function' && !veraInitialized) {
              initVeraChatSession();
            }
          }
        }, 12000);
      }
    }

    function ensureWordPressSession() {
      // En producción la sesión de WordPress también debe hidratar la sesión
      // de la SPA. Sin este puente, WordPress puede mostrar al usuario como
      // autenticado mientras getDemoSession() permanece vacío y bloquea las
      // acciones profesionales (publicar, demandar y solicitar acceso).
      if (!CAPTACION_MAILCHIMP?.loggedIn || !CAPTACION_MAILCHIMP?.emailVerified || !CAPTACION_MAILCHIMP?.currentUser || getDemoSession()) return;
      const user = CAPTACION_MAILCHIMP.currentUser;
      const appSession = { name:user.name || 'Profesional', email:user.email || '', whatsapp:user.phone || '', agency:user.businessName || (user.profileType === 'agency' ? 'Agencia inmobiliaria' : 'Profesional independiente'), profileType:user.profileType || 'independent', planType:CAPTACION_MAILCHIMP.accessState?.plan_type || 'beta', emailVerified:true, startedAt:Date.now(), source:'wordpress' };
      // En producción getDemoSession() lee sessionStorage. Mantener aquí la
      // misma capa de persistencia evita que WordPress esté autenticado pero
      // la SPA siga considerando al profesional como visitante.
      sessionStorage.setItem('captacion_app_session_v1', JSON.stringify(appSession));
      sessionStorage.setItem('captacion_professional_registered','1');
    }

    // Hidrata la SPA y carga las publicaciones compartidas públicas y privadas
    ensureWordPressSession();
    loadWordPressRealEstateRecords();

    function getProfessionalAccessModal() {
      let modal = document.getElementById('professional-access-modal');
      if (modal) return modal;
      modal = document.createElement('div');
      modal.id = 'professional-access-modal';
      modal.className = 'fixed inset-0 z-[135] hidden flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm transition-all duration-300';
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeProfessionalAccessModal();
      });
      modal.innerHTML = `
        <div class="relative w-full max-w-md max-h-[92vh] overflow-y-auto rounded-3xl bg-white dark:bg-[#0c192c] border border-slate-200 dark:border-slate-800 shadow-2xl p-6 sm:p-8 text-slate-800 dark:text-white transition-all">
          <button type="button" onclick="closeProfessionalAccessModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center text-sm font-black transition-colors" aria-label="Cerrar ventana">✕</button>
          
          <div class="flex items-center gap-2 mb-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue/10 text-blue dark:text-blue-neon text-[10px] font-black uppercase tracking-wider">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Acceso Profesional
            </span>
          </div>

          <h3 id="professional-access-title" class="text-2xl font-black text-navy dark:text-white tracking-tight">Inicia sesión</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Entra en tu panel privado para gestionar tu cartera, cruces de compradores y solicitudes de colaboración.</p>
          
          <div class="mt-4 grid grid-cols-2 gap-1 rounded-2xl bg-slate-100 dark:bg-slate-800/80 p-1 border border-slate-200/60 dark:border-slate-700/50">
            <button id="professional-access-login-tab" type="button" onclick="toggleProfessionalAccessMode('login')" class="px-3 py-2 rounded-xl bg-white dark:bg-slate-900 text-navy dark:text-white text-xs font-black shadow-sm transition-all">Iniciar sesión</button>
            <button id="professional-access-register-tab" type="button" onclick="toggleProfessionalAccessMode('register')" class="px-3 py-2 rounded-xl text-slate-500 dark:text-slate-400 text-xs font-black transition-all">Crear cuenta</button>
          </div>
          
          ${socialLoginButtonsHtml(false)}
          
          <!-- FORMULARIO DE INICIO DE SESIÓN -->
          <form id="professional-access-login-form" onsubmit="handleProfessionalLogin(event)" class="mt-4 space-y-4">
            <label class="block">
              <span class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Correo electrónico profesional *</span>
              <input id="professional-login-email" type="email" required autocomplete="email" placeholder="tu@inmobiliaria.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-slate-800 dark:text-white text-sm focus:border-blue focus:bg-white dark:focus:bg-slate-800 outline-none transition-all" />
            </label>
            
            <label class="block">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Contraseña *</span>
                <a href="${CAPTACION_MAILCHIMP.lostPasswordUrl || '#'}" class="text-[11px] font-semibold text-blue dark:text-blue-neon hover:underline">¿Olvidaste tu contraseña?</a>
              </div>
              <div class="relative">
                <input id="professional-login-password" type="password" required autocomplete="current-password" placeholder="Tu contraseña" class="w-full px-4 py-3 pr-24 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-slate-800 dark:text-white text-sm focus:border-blue focus:bg-white dark:focus:bg-slate-800 outline-none transition-all" />
                <button type="button" onclick="togglePasswordVisibility('professional-login-password', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[10px] font-black text-blue dark:text-blue-neon hover:bg-blue/10 transition-colors">Mostrar</button>
              </div>
            </label>

            <p id="professional-login-error" class="hidden rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-100 dark:border-red-900/50 px-3 py-2 text-xs text-red-700 dark:text-red-300" role="alert"></p>
            
            <button id="professional-login-submit" type="submit" class="w-full py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-blue/20 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2">
              <span>Iniciar sesión</span>
              <span aria-hidden="true">→</span>
            </button>
            
            <div class="text-center pt-1">
              <span class="text-xs text-slate-500 dark:text-slate-400">¿Aún no tienes cuenta?</span>
              <button type="button" onclick="toggleProfessionalAccessMode('register')" class="text-xs font-bold text-blue dark:text-blue-neon hover:underline ml-1">Crear cuenta gratis</button>
            </div>
          </form>
          
          <!-- FORMULARIO DE REGISTRO RÁPIDO -->
          <form id="professional-access-register-form" onsubmit="handleAccessProfessionalRegistration(event)" class="hidden mt-4 space-y-3.5">
            <label class="block"><span class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Nombre completo *</span><input id="access-register-name" type="text" required autocomplete="name" placeholder="Tu nombre y apellidos" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-sm" /></label>
            <label class="block"><span class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Tipo de perfil *</span><select id="access-register-profile-type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-sm"><option value="agency">Agencia inmobiliaria</option><option value="independent">Profesional independiente / Agente</option></select></label>
            <label class="block"><span class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Nombre de la agencia <em class="font-normal">(opcional)</em></span><input id="access-register-business-name" type="text" autocomplete="organization" placeholder="Ej: Inmobiliaria Centro" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-sm" /></label>
            <label class="block"><span class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Correo electrónico profesional *</span><input id="access-register-email" type="email" required autocomplete="email" placeholder="tu@agencia.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-sm" /></label>
            <label class="block"><span class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Contraseña *</span><div class="relative"><input id="access-register-password" type="password" required minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres" class="w-full px-4 py-2.5 pr-24 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-sm" /><button type="button" onclick="togglePasswordVisibility('access-register-password', this)" class="absolute inset-y-1 right-1 px-3 rounded-lg text-[10px] font-black text-blue hover:bg-blue/10">Mostrar</button></div></label>
            <label class="block"><span class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Teléfono de contacto (Opcional)</span><input id="access-register-phone" type="tel" pattern="[0-9]{9,15}" autocomplete="tel" placeholder="600123456" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-sm" /></label>
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-3 text-xs text-slate-600 dark:text-slate-300 cursor-pointer"><input id="access-register-privacy" type="checkbox" required class="mt-0.5 h-4 w-4 shrink-0" /><span>He leído y acepto la <a href="${CAPTACION_BASE_PATH.replace(/\/+$/, '')}/privacidad" class="legal-link text-blue underline">Política de privacidad</a> y el tratamiento de mis datos profesionales. *</span></label>
            <p id="access-register-error" class="hidden rounded-xl bg-red-50 border border-red-100 px-3 py-2 text-xs text-red-700" role="alert"></p>
            <button id="access-register-submit" type="submit" class="w-full py-3.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-xs font-black uppercase tracking-wider shadow-md">Crear mi cuenta gratuita (3 créditos de bienvenida)</button>
            <button type="button" onclick="toggleProfessionalAccessMode('login')" class="w-full text-xs font-bold text-blue dark:text-blue-neon hover:underline">¿Ya tienes cuenta? Iniciar sesión</button>
          </form>
        </div>`;
      document.body.appendChild(modal);
      return modal;
    }

    function toggleProfessionalAccessMode(mode = 'login') {
      const isLogin = mode === 'login';
      const title = document.getElementById('professional-access-title');
      if (title) title.textContent = isLogin ? 'Inicia sesión' : 'Crea tu cuenta gratuita';
      document.getElementById('professional-access-login-form')?.classList.toggle('hidden', !isLogin);
      document.getElementById('professional-access-register-form')?.classList.toggle('hidden', isLogin);
      const loginTab = document.getElementById('professional-access-login-tab');
      const registerTab = document.getElementById('professional-access-register-tab');
      if (loginTab && registerTab) {
        loginTab.className = isLogin 
          ? 'px-3 py-2 rounded-xl bg-white dark:bg-slate-900 text-navy dark:text-white text-xs font-black shadow-sm transition-all'
          : 'px-3 py-2 rounded-xl text-slate-500 dark:text-slate-400 text-xs font-black transition-all';
        registerTab.className = !isLogin
          ? 'px-3 py-2 rounded-xl bg-white dark:bg-slate-900 text-navy dark:text-white text-xs font-black shadow-sm transition-all'
          : 'px-3 py-2 rounded-xl text-slate-500 dark:text-slate-400 text-xs font-black transition-all';
      }
    }

    function closeProfessionalAccessModal() { 
      const modal = getProfessionalAccessModal();
      if (modal) modal.classList.add('hidden'); 
    }
    window.closeProfessionalAccessModal = closeProfessionalAccessModal;

    async function handleProfessionalLogin(event) {
      event.preventDefault();
      const email = cleanText(document.getElementById('professional-login-email')?.value || '').toLowerCase();
      const password = document.getElementById('professional-login-password')?.value || '';
      const errorBox = document.getElementById('professional-login-error');
      const submit = document.getElementById('professional-login-submit');
      const fail = message => { errorBox.textContent = message; errorBox.classList.remove('hidden'); };
      if (!/^\S+@\S+\.\S+$/.test(email) || !password) return fail('Completa correo y contraseña.');
      errorBox.classList.add('hidden'); submit.disabled = true; submit.innerHTML = '<span>Accediendo...</span>';
      try {
        const loginUrl = CAPTACION_MAILCHIMP?.loginEndpoint || '/api/auth.php?action=login';
        let response = await fetch(loginUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': CAPTACION_MAILCHIMP?.nonce || ''
          },
          body: JSON.stringify({ email, password })
        });
        let data = null;
        try {
          data = await response.json();
        } catch (err) {
          // Fallback directo al endpoint nativo si Mailchimp endpoint difiere
          response = await fetch('/api/auth.php?action=login', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
          });
          data = await response.json();
        }
        if (!response.ok || !data?.ok) throw new Error(data?.error || data?.message || 'No se pudo iniciar sesión.');
        if (CAPTACION_MAILCHIMP) {
          CAPTACION_MAILCHIMP.loggedIn = true; 
          CAPTACION_MAILCHIMP.emailVerified = true; 
          CAPTACION_MAILCHIMP.accessState = data.accessState; 
          CAPTACION_MAILCHIMP.nonce = data.nonce || CAPTACION_MAILCHIMP.nonce;
        }
        if (window.CAPTACION_API) window.CAPTACION_API.nonce = data.nonce || window.CAPTACION_API.nonce;
        const appSession = {
          name: data.displayName || data.user?.full_name || email,
          email: data.email || data.user?.email || email,
          whatsapp: data.phone || data.user?.phone || '',
          agency: data.businessName || data.user?.agency_name || (data.profileType === 'agency' ? 'Agencia inmobiliaria' : 'Profesional independiente'),
          profileType: data.profileType || (data.user?.role === 'admin' || data.user?.role === 'agency' ? 'agency' : 'independent'),
          profileComplete: data.profileComplete !== undefined ? data.profileComplete : true,
          planType: data.accessState?.plan_type || 'beta',
          credits: data.user?.credits !== undefined ? data.user?.credits : 10,
          emailVerified: true,
          startedAt: Date.now(),
          source: 'native_auth'
        };
        sessionStorage.setItem('captacion_app_session_v1', JSON.stringify(appSession));
        localStorage.setItem('captacion_demo_session_v4', JSON.stringify(appSession));
        sessionStorage.setItem('captacion_professional_registered', '1');
        event.target.reset(); 
        closeProfessionalAccessModal(); 
        updateAuthModule(); 
        applyDashboardPlanAccess(); 
        loadWordPressRealEstateRecords(); 
        const resumedAction = completePostAuthIntent() || completePostAuthResourceIntent(); 
        if (!resumedAction && !showFirstActionChooserIfNeeded()) navigateTo('/area-privada'); 
        showToast('¡Sesión iniciada correctamente! Bienvenido a Compra Captación.', 'success');
      } catch (error) {
        fail(error.message === 'backend_unavailable' ? 'El acceso no está disponible temporalmente. Inténtalo de nuevo.' : (error.message || 'No se pudo iniciar sesión.'));
        if (/confirmar tu correo|correo electronico antes/i.test(error.message || '')) {
          errorBox.innerHTML = `${escapeHTML(error.message)} <button type="button" onclick="resendVerificationEmail('${escapeHTML(email)}')" class="block mt-2 font-black text-blue">Reenviar correo de verificación</button>`;
        }
      } finally { submit.disabled = false; submit.innerHTML = '<span>Iniciar sesión</span> <span aria-hidden="true">→</span>'; }
    }

    async function handleAccessProfessionalRegistration(event) {
      event.preventDefault();
      const email = cleanText(document.getElementById('access-register-email')?.value||'').toLowerCase();
      return registerProfessionalAccount({name:cleanText(document.getElementById('access-register-name')?.value||''),email,phone:cleanText(document.getElementById('access-register-phone')?.value||''),password:document.getElementById('access-register-password')?.value||'',privacyAccepted:Boolean(document.getElementById('access-register-privacy')?.checked),commercialConsent:false,profileType:document.getElementById('access-register-profile-type')?.value||'independent',businessName:cleanText(document.getElementById('access-register-business-name')?.value||'')}, {form:event.target,errorBox:document.getElementById('access-register-error'),submit:document.getElementById('access-register-submit')});
    }

    async function resendVerificationEmail(email) {
      const errorBox = document.getElementById('professional-login-error');
      if (!errorBox) return;
      try {
        errorBox.classList.remove('hidden');
        errorBox.innerHTML = 'Enviando...';
        errorBox.className = "rounded-xl bg-red-50 border border-red-100 px-3 py-2 text-xs text-red-700";
        
        const response = await fetch(CAPTACION_MAILCHIMP.resendVerificationEndpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': CAPTACION_MAILCHIMP.nonce
          },
          body: JSON.stringify({ email })
        });
        const data = await response.json();
        if (!response.ok || !data?.ok) throw new Error(data?.message || 'No se pudo reenviar el correo.');
        
        // Success style: green!
        errorBox.className = "rounded-xl bg-green-50 border border-green-100 px-3 py-2 text-xs text-green-700";
        errorBox.innerHTML = `<strong>Correo enviado</strong><br>${escapeHTML(data.message)}`;
        showToast(data.message, 'success');
      } catch (error) {
        // Fail style: red!
        errorBox.className = "rounded-xl bg-red-50 border border-red-100 px-3 py-2 text-xs text-red-700";
        errorBox.innerHTML = `<strong>Ha fallado el reenvío:</strong> ${escapeHTML(error.message)} <button type="button" onclick="resendVerificationEmail('${escapeHTML(email)}')" class="block mt-2 font-black text-blue underline">Intentar de nuevo</button>`;
        showToast(error.message, 'error');
      }
    }

    // ==========================================
    // 7.2 FUNCIONES QUE FALTABAN EN EL PROTOTIPO ORIGINAL
    // ==========================================
    function openProfessionalAccess(mode = 'login') {
      const modal = getProfessionalAccessModal();
      modal.classList.remove('hidden');
      toggleProfessionalAccessMode(mode);
      setTimeout(() => {
        const input = document.getElementById(mode === 'login' ? 'professional-login-email' : 'access-register-name');
        if (input) input.focus();
      }, 100);
    }
    window.openProfessionalAccess = openProfessionalAccess;

    function scrollToPlatformForm(formId) {
      const form = document.getElementById(formId);
      if (!form) return;
      const accordion = form.closest('details.captacion-accordion');
      if (accordion && !accordion.open) accordion.open = true;
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          form.scrollIntoView({ behavior:'smooth', block:'start' });
          setTimeout(() => form.querySelector('input,select,textarea')?.focus({ preventScroll:true }), 450);
        });
      });
    }

    const progressiveFormControllers = {};

    function initializeProgressiveForm(config) {
      const form = document.getElementById(config.formId);
      if (!form || form.dataset.progressiveReady === '1') return;
      form.dataset.progressiveReady = '1';
      const originalChildren = Array.from(form.children);
      const storageKey = `captacion_form_progress_${config.formId}_v1`;
      const shell = document.createElement('div');
      shell.className = 'space-y-6';
      shell.innerHTML = `<div class="grid grid-cols-3 gap-2" role="list" aria-label="Progreso del formulario">${config.steps.map((step, index) => `<div data-progress-indicator="${index + 1}" class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-center"><span class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Paso ${index + 1} de 3</span><strong class="mt-1 block text-xs text-navy">${step.shortTitle}</strong></div>`).join('')}</div>`;

      const panels = config.steps.map((step, index) => {
        const panel = document.createElement('fieldset');
        panel.dataset.progressiveStep = String(index + 1);
        panel.className = 'space-y-4 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5';
        panel.innerHTML = `<legend class="sr-only">Paso ${index + 1} de 3: ${step.title}</legend><div tabindex="-1" data-step-heading><span class="text-[10px] font-black uppercase tracking-[0.16em] text-blue">Paso ${index + 1} de 3</span><h4 class="mt-2 text-lg font-black text-navy">${step.title}</h4>${step.help ? `<p class="mt-2 text-xs leading-relaxed text-slate-500">${step.help}</p>` : ''}</div><div data-step-fields class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"></div>`;
        shell.appendChild(panel);
        return panel;
      });

      const moved = new Set();
      const moveField = (fieldId, panel) => {
        const field = document.getElementById(fieldId);
        if (!field) return;
        let unit = field.closest('label');
        if (!unit || (unit !== field.parentElement && !['checkbox', 'radio'].includes(field.type))) unit = field.parentElement;
        if (!unit || moved.has(unit)) return;
        moved.add(unit);
        panel.querySelector('[data-step-fields]')?.appendChild(unit);
        if (unit.querySelector('textarea, input[type="checkbox"], input[type="file"]')) unit.classList.add('md:col-span-2', 'xl:col-span-3');
      };

      config.steps.forEach((step, index) => step.fields.forEach(id => moveField(id, panels[index])));
      (config.specialGroups || []).forEach(group => {
        const source = document.querySelector(group.selector);
        const element = group.closest ? source?.closest(group.closest) : source;
        if (!element || moved.has(element)) return;
        moved.add(element);
        panels[group.step - 1].querySelector('[data-step-fields]')?.appendChild(element);
        element.classList.add('md:col-span-2');
      });

      originalChildren.forEach(child => {
        if (!shell.contains(child)) child.classList.add('hidden');
      });

      panels.forEach((panel, index) => {
        const actions = document.createElement('div');
        actions.className = 'flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2';
        const back = index > 0 ? `<button type="button" data-step-back class="px-5 py-3 rounded-xl border border-slate-200 text-navy text-xs font-black">Volver</button>` : '<span></span>';
        const next = index < 2
          ? `<button type="button" data-step-next class="px-6 py-3 rounded-xl bg-blue text-white text-xs font-black shadow-sm">Continuar</button>`
          : `<button type="submit" class="px-6 py-3 rounded-xl bg-blue text-white text-xs font-black shadow-sm">${config.submitLabel}</button>`;
        actions.innerHTML = `${back}${next}`;
        panel.appendChild(actions);
      });

      form.appendChild(shell);
      let currentStep = Math.min(3, Math.max(1, Number(sessionStorage.getItem(`${storageKey}:step`)) || 1));

      try {
        const storedValues = JSON.parse(sessionStorage.getItem(storageKey) || '{}');
        form.querySelectorAll('input[id],select[id],textarea[id]').forEach(field => {
          if (!(field.id in storedValues) || field.type === 'file' || field.type === 'password') return;
          if (field.type === 'checkbox' || field.type === 'radio') field.checked = Boolean(storedValues[field.id]);
          else field.value = storedValues[field.id];
          field.dispatchEvent(new Event('change', { bubbles:true }));
        });
      } catch (error) {}

      const saveProgress = () => {
        const values = {};
        form.querySelectorAll('input[id],select[id],textarea[id]').forEach(field => {
          if (field.type === 'file' || field.type === 'password') return;
          values[field.id] = field.type === 'checkbox' || field.type === 'radio' ? field.checked : field.value;
        });
        try { sessionStorage.setItem(storageKey, JSON.stringify(values)); } catch (error) {}
      };

      const showStep = (step, focusHeading = true) => {
        currentStep = Math.min(3, Math.max(1, step));
        panels.forEach((panel, index) => panel.classList.toggle('hidden', index + 1 !== currentStep));
        shell.querySelectorAll('[data-progress-indicator]').forEach(indicator => {
          const active = Number(indicator.dataset.progressIndicator) === currentStep;
          indicator.classList.toggle('border-blue', active);
          indicator.classList.toggle('bg-blue-light', active);
          indicator.setAttribute('aria-current', active ? 'step' : 'false');
        });
        sessionStorage.setItem(`${storageKey}:step`, String(currentStep));
        saveProgress();
        if (focusHeading) panels[currentStep - 1].querySelector('[data-step-heading]')?.focus({ preventScroll:true });
      };

      const validateCurrentStep = () => {
        const fields = panels[currentStep - 1].querySelectorAll('input,select,textarea');
        for (const field of fields) {
          if (field.disabled || !field.required) continue;
          if (!field.checkValidity()) { field.reportValidity(); field.focus(); return false; }
        }
        return true;
      };

      shell.addEventListener('click', event => {
        if (event.target.closest('[data-step-next]')) {
          if (validateCurrentStep()) showStep(currentStep + 1);
        }
        if (event.target.closest('[data-step-back]')) showStep(currentStep - 1);
      });
      form.addEventListener('input', saveProgress);
      form.addEventListener('change', saveProgress);
      progressiveFormControllers[config.formId] = {
        showStep,
        reset() {
          sessionStorage.removeItem(storageKey);
          sessionStorage.removeItem(`${storageKey}:step`);
          showStep(1, false);
        }
      };
      showStep(currentStep, false);
    }

    function initializeProgressiveForms() {
      initializeProgressiveForm({
        formId: 'need-publication-form',
        submitLabel: 'Publicar búsqueda de mi cliente',
        steps: [
          { shortTitle:'Inmueble y ubicación', title:'Inmueble y ubicación', fields:['need-pub-title','need-pub-type','need-pub-operation','need-pub-ccaa-sel','need-pub-province-sel','need-pub-municipality-sel','need-pub-postal-code','need-pub-locality'] },
          { shortTitle:'Presupuesto', title:'Presupuesto y características', fields:['need-pub-budget','need-pub-bedrooms','need-pub-bathrooms','need-pub-surface','need-pub-condition'] },
          { shortTitle:'Colaboración', title:'Comprador y colaboración', help:'No incluyas nombres, teléfonos ni otros datos personales del comprador.', fields:['need-pub-buyer-type','need-pub-funding','need-pub-urgency','need-pub-fee','need-pub-mandate','need-pub-docs','need-pub-desc','need-pub-compliance'] }
        ]
      });
    }

    function resetProgressiveForm(formId) {
      progressiveFormControllers[formId]?.reset();
    }

    function getPublicationConfirmationModal() {
      let modal = document.getElementById('publication-confirmation-modal');
      if (modal) return modal;
      modal = document.createElement('div');
      modal.id = 'publication-confirmation-modal';
      modal.className = 'fixed inset-0 z-[145] hidden items-center justify-center bg-navy-dark/70 p-4 backdrop-blur-sm';
      modal.innerHTML = `<div class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-7 text-center shadow-2xl"><button type="button" onclick="closePublicationConfirmation()" aria-label="Cerrar" class="absolute right-4 top-3 text-xl font-black text-slate-400">×</button><span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-green-light text-xl text-green">✓</span><h3 id="publication-confirmation-title" class="mt-4 text-xl font-black text-navy"></h3><p id="publication-confirmation-copy" class="mt-3 text-sm leading-relaxed text-slate-500"></p><button id="publication-confirmation-action" type="button" class="mt-6 w-full rounded-xl bg-blue px-5 py-3 text-xs font-black text-white"></button></div>`;
      document.body.appendChild(modal);
      return modal;
    }

    function showPublicationConfirmation(kind, recordId) {
      const isNeed = kind === 'need';
      const modal = getPublicationConfirmationModal();
      modal.dataset.kind = kind;
      modal.dataset.recordId = recordId;
      document.getElementById('publication-confirmation-title').textContent = isNeed ? 'Tu búsqueda se ha publicado correctamente' : 'Tu captación se ha publicado correctamente';
      document.getElementById('publication-confirmation-copy').textContent = isNeed ? 'Te avisaremos cuando aparezcan oportunidades compatibles.' : 'Los datos sensibles permanecerán protegidos.';
      const action = document.getElementById('publication-confirmation-action');
      action.textContent = isNeed ? 'Ver mi búsqueda publicada' : 'Ver mi captación publicada';
      action.onclick = viewPublishedRecord;
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      action.focus();
    }

    function closePublicationConfirmation() {
      const modal = getPublicationConfirmationModal();
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    function viewPublishedRecord() {
      const modal = getPublicationConfirmationModal();
      const isNeed = modal.dataset.kind === 'need';
      const recordId = modal.dataset.recordId || '';
      closePublicationConfirmation();
      navigateTo(isNeed ? '/demandas' : '/propiedades');
      window.setTimeout(() => document.getElementById(`${isNeed ? 'need-card' : 'market-card'}-${recordId}`)?.scrollIntoView({ behavior:'smooth', block:'center' }), 150);
    }

    function renderBetaPlanCards() {
      // Obsolete legacy injection removed. The pricing page is rendered natively and dynamically in index.php
      return;
    }

    renderBetaPlanCards();

    function handleFreePlanAccess() {
      if (getDemoSession?.()) {
        navigateTo('/area-privada');
        return;
      }
      showRegistrationPrompt(true);
    }

    function getActiveMarketplaceProperties() {
      const closedIds = new Set((closedOperations || []).map(item => item.propertyId).filter(Boolean));
      return properties.filter(property => {
        const status = normalizeMatchText(property.status || 'activa');
        return isMarketplaceVisibleProperty(property) && !closedIds.has(property.id) && !['cerrada','cerrado','caducada','caducado','bloqueada','bloqueado','vendida','vendido'].some(value => status.includes(value));
      });
    }

    function openNeedCollaborationModal(needId) {
      if (!requireRegisteredAction('colaborar con esta demanda')) return;
      const need = needs.find(item => item.id === needId);
      const modal = document.getElementById('need-collaboration-modal');
      const select = document.getElementById('need-collaboration-property');
      if (!need || !modal || !select) return;
      document.getElementById('need-collaboration-need-id').value = need.id;
      document.getElementById('need-collaboration-summary').innerHTML = `<strong class="block text-navy mb-2">${escapeHTML(need.title)}</strong><div class="grid grid-cols-1 sm:grid-cols-2 gap-2"><span><strong>Tipo:</strong> ${escapeHTML(need.type || 'No disponible')}</span><span><strong>Zona:</strong> ${escapeHTML([need.province, need.municipality].filter(Boolean).join(' · ') || 'No disponible')}</span><span><strong>Presupuesto:</strong> ${formatCurrency(need.budget)}</span><span><strong>Urgencia:</strong> ${escapeHTML(need.urgency || 'Media')}</span><span class="sm:col-span-2"><strong>Criterios:</strong> ${formatPropertyFeatures(need, true)}</span></div>`;
      const active = getActiveMarketplaceProperties();
      const compatible = getCompatiblePropertiesForNeed(need, active.length, false).map(item => item.property.id);
      const ordered = [...active].sort((a,b) => Number(compatible.includes(b.id)) - Number(compatible.includes(a.id)) || Number(b.date||0)-Number(a.date||0));
      select.innerHTML = ordered.map(property => `<option value="${escapeHTML(property.id)}">${compatible.includes(property.id) ? 'Compatible · ' : ''}${escapeHTML(property.title)} · ${escapeHTML(property.province || property.location || 'España')} · ${formatCurrency(property.price)}</option>`).join('') || '<option value="">No hay captaciones activas disponibles</option>';
      select.disabled = !ordered.length;
      modal.classList.remove('hidden');
    }

    function closeNeedCollaborationModal() {
      document.getElementById('need-collaboration-modal')?.classList.add('hidden');
    }

    function submitNeedCollaboration(event) {
      event.preventDefault();
      const need = needs.find(item => item.id === document.getElementById('need-collaboration-need-id')?.value);
      const property = properties.find(item => item.id === document.getElementById('need-collaboration-property')?.value);
      const message = cleanText(document.getElementById('need-collaboration-message')?.value || '');
      if (!need || !property) { showToast('Selecciona una captación activa para enviar la propuesta.', 'info'); return; }
      const proposal = { id:`COL-${Date.now()}`, needId:need.id, propertyId:property.id, title:'Nueva propuesta de colaboración', message, status:'pendiente', createdAt:Date.now() };
      persistWpRecord('access_request', proposal, { recordKey:proposal.id, userEmail:need.userEmail || '', title:proposal.title, status:'pendiente', relatedId:need.id });
      persistWpRecord('notification', { ...proposal, detail:'Un profesional tiene una captación disponible en Marketplace que podría encajar con tu demanda.' }, { recordKey:`notification-${proposal.id}`, userEmail:need.userEmail || '', title:'Nueva propuesta de colaboración', status:'unread', relatedId:property.id });
      addPrivateNotification({ category:'Colaboración', title:'Nueva propuesta de colaboración', detail:'Un profesional tiene una captación disponible en Marketplace que podría encajar con tu demanda. Revisa la coincidencia y continúa con el flujo de Comprar captación si te interesa.', target:'demands', propertyId:property.id, needId:need.id, dueAt:Date.now(), dedupeKey:`collaboration-${need.id}-${property.id}` });
      addPrivateActivity('✓','Propuesta de colaboración enviada',`${property.title} se ha propuesto para la demanda ${need.title}.`);
      closeNeedCollaborationModal();
      showToast('Propuesta de colaboración enviada correctamente.', 'success');
      setTimeout(() => openMapPropertyCard(property.id), 180);
    }

    async function handleNewNeed(event) {
      event.preventDefault();
      if (!requireRegisteredAction('publicar una demanda')) return;
      const title = cleanText(document.getElementById('need-pub-title').value);
      const description = cleanText(document.getElementById('need-pub-desc').value);
    const spamRegex = /([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)|(https?:\/\/[^\s]+)|(www\.[^\s]+)/i;
    if (spamRegex.test(description)) {
      showToast('No está permitido incluir enlaces o correos electrónicos en la descripción.', 'error');
      return;
    }
      const type = normalizePropertyType(document.getElementById('need-pub-type').value);
      const acceptedConditions = selectedValues(document.getElementById('need-pub-condition'));
      const acceptedMandates = selectedValues(document.getElementById('need-pub-mandate'));
      if (title.length < 8) { showToast('El título de la búsqueda debe tener al menos 8 caracteres.', 'info'); return; }
      if (description.length < 30) { showToast('La descripción de la necesidad debe tener al menos 30 caracteres.', 'info'); return; }
      if (!acceptedConditions.length || !acceptedMandates.length) { showToast('Selecciona una condición y un tipo de captación aceptada.', 'info'); return; }
      const territory = resolveTerritorySelection(
        document.getElementById('need-pub-ccaa-sel').value,
        document.getElementById('need-pub-province-sel').value,
        document.getElementById('need-pub-municipality-sel').value
      );
      if (!territory.valid) {
        showToast(territory.message, 'info');
        return;
      }
      const need = normalizeNeedRecord({
        id: `user-need-${Date.now()}`,
        title,
        type,
        property_type: type,
        operation: cleanText(document.getElementById('need-pub-operation').value),
        ccaa: territory.autonomous_community_name,
        province: territory.province_name,
        municipality: territory.municipality_name,
        autonomous_community_id: territory.autonomous_community_id,
        community_code: territory.autonomous_community_id,
        autonomous_community_name: territory.autonomous_community_name,
        province_id: territory.province_id,
        province_code: territory.province_id,
        province_name: territory.province_name,
        municipality_id: territory.municipality_id,
        municipality_ine_code: territory.municipality_ine_code,
        municipality_code: territory.municipality_ine_code || territory.municipality_id,
        municipality_name: territory.municipality_name,
        locality: cleanText(document.getElementById('need-pub-locality').value),
        postalCode: cleanText(document.getElementById('need-pub-postal-code').value),
        bedrooms: Number(document.getElementById('need-pub-bedrooms').value) || 0,
        min_rooms: Number(document.getElementById('need-pub-bedrooms').value) || 0,
        bathrooms: Number(document.getElementById('need-pub-bathrooms').value) || 0,
        min_bathrooms: Number(document.getElementById('need-pub-bathrooms').value) || 0,
        surface: Number(document.getElementById('need-pub-surface').value),
        desired_area_min_m2: Number(document.getElementById('need-pub-surface').value),
        budget: Number(document.getElementById('need-pub-budget').value),
        max_budget: Number(document.getElementById('need-pub-budget').value),
        buyerType: cleanText(document.getElementById('need-pub-buyer-type').value),
        urgency: cleanText(document.getElementById('need-pub-urgency').value),
        search_urgency: cleanText(document.getElementById('need-pub-urgency').value),
        funding: cleanText(document.getElementById('need-pub-funding').value),
        feeSplit: cleanText(document.getElementById('need-pub-fee').value),
        accepted_commission: cleanText(document.getElementById('need-pub-fee').value),
        accepted_property_conditions: acceptedConditions,
        accepted_mandate_types: acceptedMandates,
        required_documentation_level: cleanText(document.getElementById('need-pub-docs').value),
        description,
        agency: getDemoSession()?.agency || 'Perfil profesional',
        userEmail: getDemoSession()?.email || '',
        date: Date.now()
      });
      const persisted = await persistWpRecord('need', need, { recordKey: need.id, title: need.title, status: 'activa', privacyScope: 'global_public' });
      if (canUseWordPressRecords() && !persisted) {
        showToast('No se pudo guardar la demanda en WordPress. No se ha publicado.', 'error');
        return;
      }
      needs.unshift(need);
      persistDemoState();
      syncMailchimpSession('busco-captacion', 'busco-captacion');
      syncAlertsForNeed(need);
      event.target.reset();
      resetProgressiveForm('need-publication-form');
      updateGeoDropdowns('form-need');
      updatePropertyFormDynamics('need');
      filterNeeds();
      renderHome();
      showToast('Tu búsqueda se ha publicado correctamente.', 'success');
      trackConversionEvent('demand_published');
      showPublicationConfirmation('need', need.id);
    }

    function switchPublishMode(mode) {
      const isOffer = mode === 'oferta';
      const tabOffer = document.getElementById('publish-tab-offer');
      const tabNeed = document.getElementById('publish-tab-need');
      const wrapOffer = document.getElementById('publish-offer-wrapper');
      const wrapNeed = document.getElementById('publish-need-wrapper');
      const bannerOffer = document.getElementById('publish-header-offer');
      const bannerNeed = document.getElementById('publish-header-need');
      const breadcrumbActive = document.getElementById('publish-breadcrumb-active');

      if (breadcrumbActive) {
        breadcrumbActive.textContent = isOffer ? 'Compartir Propiedad' : 'Publicar Demanda';
      }

      if (tabOffer && tabNeed) {
        tabOffer.className = isOffer
          ? 'flex items-center gap-3.5 px-6 py-4 rounded-2xl text-left transition-all bg-white dark:bg-slate-900 text-navy dark:text-white shadow-lg border-2 border-blue/50'
          : 'flex items-center gap-3.5 px-6 py-4 rounded-2xl text-left transition-all text-slate-600 dark:text-slate-400 hover:text-navy dark:hover:text-white border-2 border-transparent';
        tabNeed.className = !isOffer
          ? 'flex items-center gap-3.5 px-6 py-4 rounded-2xl text-left transition-all bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-lg border-2 border-emerald-500/50'
          : 'flex items-center gap-3.5 px-6 py-4 rounded-2xl text-left transition-all text-slate-600 dark:text-slate-400 hover:text-navy dark:hover:text-white border-2 border-transparent';
      }

      if (wrapOffer && wrapNeed) {
        wrapOffer.classList.toggle('hidden', !isOffer);
        wrapNeed.classList.toggle('hidden', isOffer);
      }
      if (bannerOffer && bannerNeed) {
        bannerOffer.classList.toggle('hidden', !isOffer);
        bannerNeed.classList.toggle('hidden', isOffer);
      }
    }
    window.switchPublishMode = switchPublishMode;

    function setOfferStep(step) {
      const step1 = document.getElementById('offer-step-1');
      const step2 = document.getElementById('offer-step-2');
      const step3 = document.getElementById('offer-step-3');

      if (step === 2) {
        const title = document.getElementById('offer-title');
        const price = document.getElementById('offer-price');
        if (title && !title.checkValidity()) { title.reportValidity(); return; }
        if (price && !price.checkValidity()) { price.reportValidity(); return; }
      }
      if (step === 3) {
        const ccaa = document.getElementById('offer-ccaa-sel');
        const prov = document.getElementById('offer-province-sel');
        const mun = document.getElementById('offer-municipality-sel');
        const bed = document.getElementById('offer-bedrooms');
        const bath = document.getElementById('offer-bathrooms');
        const surf = document.getElementById('offer-surface');
        if (ccaa && !ccaa.checkValidity()) { ccaa.reportValidity(); return; }
        if (prov && !prov.checkValidity()) { prov.reportValidity(); return; }
        if (mun && !mun.checkValidity()) { mun.reportValidity(); return; }
        if (bed && !bed.checkValidity()) { bed.reportValidity(); return; }
        if (bath && !bath.checkValidity()) { bath.reportValidity(); return; }
        if (surf && !surf.checkValidity()) { surf.reportValidity(); return; }
      }

      [step1, step2, step3].forEach((el, idx) => {
        if (el) el.classList.toggle('hidden', (idx + 1) !== step);
      });

      for (let i = 1; i <= 3; i++) {
        const ind = document.getElementById(`offer-step-ind-${i}`);
        const circle = ind?.querySelector('.step-circle');
        const label = ind?.querySelector('.step-label');
        if (ind) {
          ind.classList.toggle('opacity-50', i > step);
          if (circle) {
            circle.className = i <= step
              ? 'w-8 h-8 rounded-full flex items-center justify-center bg-blue text-white shadow-md text-xs font-black step-circle'
              : 'w-8 h-8 rounded-full flex items-center justify-center bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold step-circle';
          }
          if (label) {
            label.className = i <= step ? 'step-label text-navy dark:text-white font-black hidden sm:inline' : 'step-label text-slate-400 font-medium hidden sm:inline';
          }
        }
      }
      window.scrollTo({ top: 150, behavior: 'smooth' });
    }
    window.setOfferStep = setOfferStep;

    async function loadLastCaptationDiagnosisDraft() {
      const banner = document.getElementById('captation-diagnosis-draft-banner');
      const diagnosisId = sessionStorage.getItem('captation_last_diagnosis_id');
      if (!banner || !diagnosisId) return false;
      try {
        const response = await fetch(`/api/diagnoses.php?action=get&id=${encodeURIComponent(diagnosisId)}`, { credentials: 'same-origin' });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok || !data.diagnosis) {
          sessionStorage.removeItem('captation_last_diagnosis_id');
          return false;
        }
        const diagnosis = data.diagnosis;
        const payload = diagnosis.payload || {};
        sessionStorage.setItem('captation_last_diagnosis_version', String(diagnosis.version || 1));
        const sourceUrl = payload.source_url || '';
        const sourceInput = document.getElementById('offer-source-url');
        if (sourceInput && sourceUrl) sourceInput.value = sourceUrl;
        const meta = document.getElementById('captation-diagnosis-draft-meta');
        if (meta) meta.textContent = `Borrador ${diagnosis.id} · estado ${diagnosis.status}. La URL se ha cargado como referencia; nada se publicará sin confirmación.`;
        banner.classList.remove('hidden');
        return true;
      } catch (error) {
        console.warn('No se pudo cargar el diagnóstico pendiente:', error);
        return false;
      }
    }

    window.loadLastCaptationDiagnosisDraft = loadLastCaptationDiagnosisDraft;

    async function saveCaptationDiagnosisDraft() {
      const diagnosisId = sessionStorage.getItem('captation_last_diagnosis_id');
      if (!diagnosisId) {
        showToast('No hay ningún diagnóstico pendiente seleccionado.', 'info');
        return false;
      }
      const field = id => document.getElementById(id)?.value || '';
      const payload = {
        source: 'professional_form',
        source_url: field('offer-source-url'),
        title: field('offer-title'),
        price: field('offer-price'),
        property_type: field('offer-type'),
        operation: field('offer-operation'),
        community: field('offer-ccaa-sel'),
        province: field('offer-province-sel'),
        municipality: field('offer-municipality-sel'),
        surface: field('offer-surface'),
        bedrooms: field('offer-bedrooms'),
        bathrooms: field('offer-bathrooms'),
        description: field('offer-description'),
        saved_at: new Date().toISOString()
      };
      try {
        const response = await fetch('/api/diagnoses.php?action=update', {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: Number(diagnosisId), version: Number(sessionStorage.getItem('captation_last_diagnosis_version') || 1), payload, status: 'draft' })
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) throw new Error(data.error || 'No se pudo actualizar el borrador.');
        sessionStorage.setItem('captation_last_diagnosis_version', String(data.version || (Number(sessionStorage.getItem('captation_last_diagnosis_version') || 1) + 1)));
        showToast('Cambios guardados en el diagnóstico. Todavía no se ha publicado.', 'success');
        const meta = document.getElementById('captation-diagnosis-draft-meta');
        if (meta) meta.textContent = `Borrador ${diagnosisId} · guardado ${new Date().toLocaleString('es-ES')}. La publicación requiere confirmación.`;
        return true;
      } catch (error) {
        showToast(error?.message || 'No se pudo guardar el borrador.', 'error');
        return false;
      }
    }

    window.saveCaptationDiagnosisDraft = saveCaptationDiagnosisDraft;

    function setNeedStep(step) {
      const step1 = document.getElementById('need-step-1');
      const step2 = document.getElementById('need-step-2');
      const step3 = document.getElementById('need-step-3');

      if (step === 2) {
        const title = document.getElementById('need-pub-title');
        const budget = document.getElementById('need-pub-budget');
        if (title && !title.checkValidity()) { title.reportValidity(); return; }
        if (budget && !budget.checkValidity()) { budget.reportValidity(); return; }
      }
      if (step === 3) {
        const ccaa = document.getElementById('need-pub-ccaa-sel');
        const prov = document.getElementById('need-pub-province-sel');
        const mun = document.getElementById('need-pub-municipality-sel');
        const bed = document.getElementById('need-pub-bedrooms');
        const bath = document.getElementById('need-pub-bathrooms');
        const surf = document.getElementById('need-pub-surface');
        if (ccaa && !ccaa.checkValidity()) { ccaa.reportValidity(); return; }
        if (prov && !prov.checkValidity()) { prov.reportValidity(); return; }
        if (mun && !mun.checkValidity()) { mun.reportValidity(); return; }
        if (bed && !bed.checkValidity()) { bed.reportValidity(); return; }
        if (bath && !bath.checkValidity()) { bath.reportValidity(); return; }
        if (surf && !surf.checkValidity()) { surf.reportValidity(); return; }
      }

      [step1, step2, step3].forEach((el, idx) => {
        if (el) el.classList.toggle('hidden', (idx + 1) !== step);
      });

      for (let i = 1; i <= 3; i++) {
        const ind = document.getElementById(`need-step-ind-${i}`);
        const circle = ind?.querySelector('.step-circle');
        const label = ind?.querySelector('.step-label');
        if (ind) {
          ind.classList.toggle('opacity-50', i > step);
          if (circle) {
            circle.className = i <= step
              ? 'w-8 h-8 rounded-full flex items-center justify-center bg-emerald-600 text-white shadow-md text-xs font-black step-circle'
              : 'w-8 h-8 rounded-full flex items-center justify-center bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold step-circle';
          }
          if (label) {
            label.className = i <= step ? 'step-label text-emerald-600 dark:text-emerald-400 font-black hidden sm:inline' : 'step-label text-slate-400 font-medium hidden sm:inline';
          }
        }
      }
      window.scrollTo({ top: 150, behavior: 'smooth' });
    }
    window.setNeedStep = setNeedStep;

    function filterNeeds() {
      needsCurrentPage = 1;
      const time = document.getElementById('need-filter-time')?.value || 'newest';
      const category = document.getElementById('need-filter-type')?.value || 'all';
      const ccaa = document.getElementById('need-filter-ccaa')?.value || 'all';
      const province = document.getElementById('need-filter-province')?.value || 'all';
      const municipality = document.getElementById('need-filter-municipality')?.value || 'all';
      const locality = cleanText(document.getElementById('need-filter-locality')?.value || '').toLowerCase();
      const postalCode = cleanText(document.getElementById('need-filter-postal-code')?.value || '');
      const price = document.getElementById('need-filter-price')?.value || 'all';
      let list = needs.filter(need => {
        const localityText = `${need.locality || ''} ${need.municipality || ''}`.toLowerCase();
        const priceOk = price === 'all' || (price === 'low' && need.budget < 150000) || (price === 'mid' && need.budget >= 150000 && need.budget <= 500000) || (price === 'high' && need.budget > 500000);
        return (ccaa === 'all' || need.ccaa === ccaa)
          && (category === 'all' || normalizeOpportunityCategory(need.type) === category)
          && (province === 'all' || need.province === province)
          && (municipality === 'all' || need.municipality === municipality)
          && (!locality || localityText.includes(locality))
          && (!postalCode || String(need.postalCode || '').includes(postalCode))
          && (!needsMapSelectedBounds || !window.L || needsMapSelectedBounds.contains(L.latLng(getApproximatePoint(need, properties.length + needs.indexOf(need)))))
          && priceOk;
      });
      list.sort((a, b) => time === 'oldest' ? a.date - b.date : b.date - a.date);
      lastFilteredNeeds = list;
      renderNeedsDashboard();
      const needsBadge = document.getElementById('needs-active-count-badge');
      if (needsBadge) {
        needsBadge.textContent = `${list.length} ${list.length === 1 ? 'demanda activa' : 'demandas activas'}`;
      }
      const needsAccordion = document.getElementById('needs-accordion-sections');
      if (needsAccordion) needsAccordion.innerHTML = '';
      renderNeedsUI(list);
      if (needsMap) renderNeedsMapMarkers(list);
    }

    function setNeedsLayout(layout) {
      currentNeedsLayout = ['mapa','lista'].includes(layout) ? layout : 'bloque';
      const mapBtn = document.getElementById('layout-mapa-btn');
      const blockBtn = document.getElementById('layout-bloque-btn');
      const listBtn = document.getElementById('layout-lista-btn');
      [[mapBtn,'mapa'],[blockBtn,'bloque'],[listBtn,'lista']].forEach(([button,mode]) => {
        const active = currentNeedsLayout === mode;
        button?.classList.toggle('bg-white', active);
        button?.classList.toggle('shadow-sm', active);
        button?.classList.toggle('text-navy', active);
        button?.classList.toggle('text-slate-500', !active);
      });
      const panel = document.getElementById('needs-map-panel');
      const listContainer = document.getElementById('needs-list-container');
      panel?.classList.toggle('hidden', currentNeedsLayout !== 'mapa');
      listContainer?.classList.toggle('hidden', currentNeedsLayout === 'mapa');
      filterNeeds();
      if (currentNeedsLayout === 'mapa') setTimeout(initNeedsMap, 0);
    }

    function clearAdvancedFilters() {
      const setters = {
        'need-filter-time': 'newest', 'need-filter-type': 'all', 'need-filter-ccaa': 'all', 'need-filter-province': 'all',
        'need-filter-municipality': 'all', 'need-filter-postal-code': '', 'need-filter-locality': '', 'need-filter-price': 'all'
      };
      Object.entries(setters).forEach(([id, value]) => { const element = document.getElementById(id); if (element) element.value = value; });
      updateGeoDropdowns('filter');
      if (needsMap && needsMapSelectionLayer) needsMap.removeLayer(needsMapSelectionLayer);
      needsMapSelectionLayer = null;
      needsMapSelectedBounds = null;
      needsMapPostalCodeFilter = '';
      const needsMapPostalInput = document.getElementById('needs-map-postal-filter');
      if (needsMapPostalInput) needsMapPostalInput.value = '';
      updateNeedsMapAreaStatus();
      filterNeeds();
    }

    function findCcaaForProvince(province) {
      return Object.keys(geoDb).find(ccaa => Object.prototype.hasOwnProperty.call(geoDb[ccaa], province)) || 'all';
    }

    function findProvinceForMunicipality(municipality) {
      for (const [ccaa, provinces] of Object.entries(geoDb)) {
        for (const [province, municipalities] of Object.entries(provinces)) {
          if (municipalities.includes(municipality)) return { ccaa, province };
        }
      }
      return { ccaa: 'all', province: 'all' };
    }

    function filterByDashboard(type, value) {
      const ccaaEl = document.getElementById('need-filter-ccaa');
      const provinceEl = document.getElementById('need-filter-province');
      const municipalityEl = document.getElementById('need-filter-municipality');
      if (type === 'ccaa') {
        ccaaEl.value = value;
        updateGeoDropdowns('filter');
      } else if (type === 'province') {
        ccaaEl.value = findCcaaForProvince(value);
        updateGeoDropdowns('filter');
        provinceEl.value = value;
        updateGeoDropdowns('filter', true);
      } else if (type === 'municipality') {
        const location = findProvinceForMunicipality(value);
        ccaaEl.value = location.ccaa;
        updateGeoDropdowns('filter');
        provinceEl.value = location.province;
        updateGeoDropdowns('filter', true);
        municipalityEl.value = value;
      }
      filterNeeds();
    }

    function toggleCardDetails(id) {
      if (!requireRegisteredAction('ver la información relacionada con este anuncio')) return;
      const details = document.getElementById(`details-${id}`);
      const button = document.getElementById(`toggle-btn-${id}`);
      if (!details) return;
      details.classList.toggle('hidden');
      if (button) button.textContent = details.classList.contains('hidden') ? 'Ver más detalles ▾' : 'Ocultar detalles ▴';
    }

    function openMapPropertyCard(propertyId) {
      if (!requireRegisteredAction('solicitar acceso a esta captación')) return;
      const selectedProperty = properties.find(item => item.id === propertyId);
      if (!selectedProperty) return;
      navigateTo('/propiedades');
      currentHash = '#/propiedades';
      handleRoute();
      setTimeout(() => {
        const referenceInput = document.getElementById('market-reference-filter');
        const postalInput = document.getElementById('market-postal-code-filter');
        let visibleProperties = getMarketplaceVisibleProperties();
        if (!visibleProperties.some(item => item.id === propertyId)) {
          if (referenceInput) referenceInput.value = '';
          if (postalInput) postalInput.value = '';
          visibleProperties = getMarketplaceVisibleProperties();
        }
        marketplaceVisibleLimit = Math.max(LIST_BATCH_SIZE, visibleProperties.findIndex(item => item.id === propertyId) + 1);
        setMarketplaceView('cards');
        renderMarketplace();
        const card = document.getElementById(`market-card-${propertyId}`);
        card?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        const details = document.getElementById(`details-${propertyId}`);
        if (details?.classList.contains('hidden')) toggleCardDetails(propertyId);
      }, 140);
    }

    function openMapNeedCard(needId) {
      const selectedNeed = needs.find(item => item.id === needId);
      if (!selectedNeed) return;
      navigateTo('/demandas');
      currentHash = '#/demandas';
      handleRoute();
      setTimeout(() => {
        const candidateList = lastFilteredNeeds.some(item => item.id === needId) ? lastFilteredNeeds : needs;
        needsVisibleLimit = Math.max(LIST_BATCH_SIZE, candidateList.findIndex(item => item.id === needId) + 1);
        renderNeedsUI(candidateList);
        const card = document.getElementById(`need-card-${needId}`);
        card?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        const details = document.getElementById(`details-${needId}`);
        if (details?.classList.contains('hidden')) toggleCardDetails(needId);
      }, 140);
    }

    let marketplaceAccessState = CAPTACION_MAILCHIMP?.accessState || { plan_type:'basic', included_marketplace_accesses:0, used_marketplace_accesses:0, extra_marketplace_accesses:0, remaining_marketplace_accesses:0 };
    let marketplaceAccessHistory = [];
    let activeOpportunityUnlocked = false;

    function marketplacePlanLabel(plan = marketplaceAccessState?.plan_type) {
      return plan === 'premium' ? 'Premium' : plan === 'professional_plus' ? 'Profesional' : 'Starter';
    }

    function marketplaceAccessCta(state = marketplaceAccessState, unlocked = false) {
      if (unlocked) return 'Acceso ya desbloqueado';
      if (Number(state?.remaining_marketplace_accesses) > 0) return 'Usar 1 acceso disponible';
      if (state?.plan_type === 'premium') return 'Comprar 15 accesos extra por 5 €';
      if (state?.plan_type === 'professional_plus') return 'Comprar 10 accesos extra por 5 €';
      return 'Comprar acceso por 10 €';
    }

    async function fetchMarketplaceAccessState(opportunityId = '') {
      if (!CAPTACION_MAILCHIMP?.loggedIn) return { accessState:marketplaceAccessState, opportunityUnlocked:false };
      const response = await fetch('/api/credits.php?action=status', {credentials:'same-origin'});
      const data = await response.json();
      if (!response.ok || !data?.ok) throw new Error(data?.error || 'No se pudo consultar el saldo de créditos.');
      const wallet = data.wallet || {};
      marketplaceAccessState = { ...marketplaceAccessState, remaining_marketplace_accesses:Number(wallet.available_balance || 0), used_marketplace_accesses:Number(wallet.consumed_balance || 0), reserved_marketplace_accesses:Number(wallet.reserved_balance || 0), plan_type:'starter' };
      CAPTACION_MAILCHIMP.accessState = marketplaceAccessState;
      updateDashboardCreditSummary();
      return { accessState:marketplaceAccessState, opportunityUnlocked:false };
    }

    async function openAccessModal(propertyId) {
      if (!requireRegisteredAction('solicitar acceso a una captacion')) return;
      trackConversionEvent('access_requested');
      const property = properties.find(item => item.id === propertyId);
      if (!property) return;
      const modal = document.getElementById('access-modal');
      document.getElementById('access-property-id').value = property.id;
      document.getElementById('access-modal-title').textContent = `Acceder a captación: ${property.title}`;
      let statusError = '';
      try {
        const status = await fetchMarketplaceAccessState(property.id);
        activeOpportunityUnlocked = Boolean(status.opportunityUnlocked);
      } catch (error) { statusError = error.message; activeOpportunityUnlocked = false; }
      document.getElementById('access-modal-summary').innerHTML = `
        <strong class="text-navy">${escapeHTML(property.type || 'Activo inmobiliario')}</strong><br>
        Zona aproximada: ${escapeHTML(property.province || property.location || 'España')} - C.P. ${escapeHTML(property.postalCode || 'N/D')}<br>
        Precio orientativo: <strong class="text-navy">${formatCurrency(property.price)}</strong><br>
        Honorarios de colaboración: <strong class="text-blue">${escapeHTML(property.fee || 'A consultar')}</strong><br>
        Referencia: <strong class="text-navy">${escapeHTML(property.reference || property.id)}</strong><br>
        ${property.cadastral_reference_masked ? `Catastro: <strong class="text-navy">${escapeHTML(property.cadastral_reference_masked)}</strong><br>` : ''}
        Plan: <strong class="text-navy">${escapeHTML(marketplacePlanLabel())}</strong><br>
        Accesos disponibles: <strong class="text-blue">${Number(marketplaceAccessState?.remaining_marketplace_accesses || 0)}</strong>${statusError ? `<br><span class="text-amber">${escapeHTML(statusError)}</span>` : ''}`;
      const planMessage = document.getElementById('access-modal-plan-message');
      if (planMessage) planMessage.textContent = activeOpportunityUnlocked ? 'Esta oportunidad ya está desbloqueada para tu usuario y no volverá a consumir crédito.' : Number(marketplaceAccessState?.remaining_marketplace_accesses) > 0 ? 'Se consumirá una unidad de acceso del marketplace al confirmar.' : 'No hay accesos disponibles. Contacta con soporte para activar accesos del marketplace o revisar la configuración de pago.';
      const stripeButton = document.getElementById('stripe-payment-button');
      if (stripeButton) stripeButton.textContent = marketplaceAccessCta(marketplaceAccessState, activeOpportunityUnlocked);
      modal?.classList.remove('hidden');
    }

    async function handleMarketplaceAccess(event) {
      event.preventDefault();
      const opportunityId = document.getElementById('access-property-id')?.value || '';
      const property = properties.find(item => item.id === opportunityId);
      if (!property) return;
      if (activeOpportunityUnlocked) { closeAccessModal(); return; }
      try {
        const idempotencyKey = `reserve-${Date.now()}-${Math.random().toString(36).slice(2, 12)}`;
        const body = new URLSearchParams({record_id:String(Number(opportunityId))});
        const response = await fetch('/api/credits.php?action=reserve', {method:'POST',credentials:'same-origin',headers:{'Idempotency-Key':idempotencyKey},body});
        const data = await response.json();
        if (!response.ok || !data?.ok) throw new Error(data?.error || 'No se pudo reservar el crédito.');
        await fetchMarketplaceAccessState();
        closeAccessModal();
        addPrivateNotification({category:'Operaciones',title:'Solicitud protegida enviada',detail:`La oportunidad ${property.title} queda reservada durante 72 horas. La otra parte debe aceptarla y ambas partes deben firmar antes de revelar los datos.`,target:'operations',dedupeKey:`reserved-${property.id}-${data.operation_id}`});
        showToast('Solicitud enviada. El crédito queda reservado durante 72 horas.', 'success');
      } catch (error) { showToast(error.message || 'No se pudo procesar el acceso.', 'info'); }
    }

    function closeAccessModal() {
      document.getElementById('access-modal')?.classList.add('hidden');
    }

    function isStripePaymentConfigured() {
      return /^https:\/\/(buy\.stripe\.com|checkout\.stripe\.com)\//.test(STRIPE_PAYMENT_LINK_URL)
        && !STRIPE_PAYMENT_LINK_URL.includes('REEMPLAZA_ESTE_ENLACE');
    }

    let currentBillingCycle = 'monthly';

    function isStripeMembershipConfigured(plan, cycle = currentBillingCycle) {
      const url = getStripeMembershipBaseUrl(plan, cycle);
      return /^https:\/\/(buy\.stripe\.com|checkout\.stripe\.com)\//.test(url)
        && !url.includes('REEMPLAZA_');
    }

    function getStripeMembershipBaseUrl(plan, cycle = currentBillingCycle) {
      const normalizedCycle = cycle === 'annual' ? 'annual' : 'monthly';
      const annualKey = `${plan}_annual`;
      const annualUrl = normalizedCycle === 'annual' ? (STRIPE_MEMBERSHIP_LINKS?.[annualKey] || '') : '';
      if (annualUrl && !annualUrl.includes('REEMPLAZA_')) return annualUrl;
      if (plan === 'premium') return STRIPE_PREMIUM_URL;
      const configuredUrl = STRIPE_MEMBERSHIP_LINKS?.[plan] || '';
      if ((plan === 'professional' || plan === 'initial') && (!configuredUrl || configuredUrl.includes('REEMPLAZA_'))) {
        return '';
      }
      return configuredUrl;
    }

    function getStripeMembershipUrl(plan, cycle = currentBillingCycle) {
      const url = new URL(getStripeMembershipBaseUrl(plan, cycle));
      url.searchParams.set('utm_source', 'captacion_app');
      url.searchParams.set('utm_medium', 'membership');
      url.searchParams.set('utm_campaign', `${plan}_${cycle === 'annual' ? 'annual' : 'monthly'}`);
      if (plan === 'professional') {
        url.searchParams.set('client_reference_id', getDemoSession?.()?.email || 'profesional_plus');
      }
      return url.toString();
    }

    function setBillingCycle(cycle = 'monthly') {
      const annual = cycle === 'annual';
      currentBillingCycle = annual ? 'annual' : 'monthly';
      document.querySelectorAll('[data-price-monthly]').forEach(node => {
        node.textContent = annual ? node.dataset.priceAnnual : node.dataset.priceMonthly;
      });
      document.querySelectorAll('[data-period-monthly]').forEach(node => {
        node.textContent = annual ? node.dataset.periodAnnual : node.dataset.periodMonthly;
      });
      document.querySelectorAll('[data-annual-note]').forEach(node => node.classList.toggle('hidden', !annual));
      const monthlyBtn = document.getElementById('billing-monthly-btn');
      const annualBtn = document.getElementById('billing-annual-btn');
      monthlyBtn?.classList.toggle('bg-white', !annual);
      monthlyBtn?.classList.toggle('text-navy', !annual);
      monthlyBtn?.classList.toggle('shadow-sm', !annual);
      monthlyBtn?.classList.toggle('text-slate-500', annual);
      annualBtn?.classList.toggle('bg-white', annual);
      annualBtn?.classList.toggle('text-navy', annual);
      annualBtn?.classList.toggle('shadow-sm', annual);
      annualBtn?.classList.toggle('text-slate-500', !annual);
      annualBtn?.classList.toggle('border-green/60', annual);
      annualBtn?.classList.toggle('border-dashed', !annual);
    }

    function openMembershipPayment(plan, planName, cycle = currentBillingCycle) {
      if (CAPTACION_MAILCHIMP?.betaProgram?.plansLocked) {
        showToast('Los planes se pueden consultar, pero se activarán después del periodo inicial de 60 días.', 'info');
        return false;
      }
      const selectedCycle = cycle === 'annual' ? 'annual' : 'monthly';
      if (plan === 'initial') {
        handleFreePlanAccess();
        return false;
      }
      const annualRequestedWithoutLink = selectedCycle === 'annual' && !(STRIPE_MEMBERSHIP_LINKS?.[`${plan}_annual`] || '');
      if (!isStripeMembershipConfigured(plan, selectedCycle)) {
        showToast('Pega primero el Payment Link real de Stripe para este plan en el panel Compra Captación.', 'info');
        return false;
      }
      window.open(getStripeMembershipUrl(plan, selectedCycle), '_blank', 'noopener,noreferrer');
      showToast(annualRequestedWithoutLink ? `Pago mensual iniciado para ${planName}. Configura el enlace anual para cobrar el precio anual.` : `Pago ${selectedCycle === 'annual' ? 'anual' : 'mensual'} iniciado para ${planName}.`, 'success');
      return false;
    }

    function hasProfessionalMembershipAccess() {
      try {
        const session = getDemoSession?.();
        return Boolean(session && ['professional_plus','premium'].includes(CAPTACION_MAILCHIMP?.accessState?.plan_type || marketplaceAccessState?.plan_type));
      } catch (error) {
        return false;
      }
    }

    function requireProfessionalMembership(itemTitle = 'este recurso') {
      if (CAPTACION_MAILCHIMP?.betaProgram?.plansLocked) {
        showToast('Professional estará disponible después del periodo inicial de 60 días.', 'info');
        return;
      }
      if (!getDemoSession?.()) {
        showToast('Crea o inicia sesión profesional antes de activar Professional.', 'info');
        navigateTo('/inicio');
        return;
      }
      if (!isStripeMembershipConfigured('professional')) {
        showToast('El enlace de Stripe para Professional todavía no está configurado.', 'info');
        return;
      }
      openMembershipPayment('professional', 'Professional');
      showToast(`Completa el pago para desbloquear ${itemTitle} y el resto de recursos profesionales.`, 'info');
    }

    function activateProfessionalMembershipFromReturn() {
      try {
        const params = new URLSearchParams(window.location.search);
        const hashParams = new URLSearchParams((window.location.hash.split('?')[1] || ''));
        const membership = params.get('membership') || params.get('plan') || hashParams.get('membership') || hashParams.get('plan');
        if (membership !== 'professional') return;
        if (params.has('membership') || params.has('plan')) {
          window.history.replaceState({}, document.title, CAPTACION_BASE_PATH.replace(/\/+$/, '') + '/recursos');
        }
        setTimeout(() => {
          showToast('Retorno de checkout detectado. La activación queda pendiente de confirmación segura por webhook.', 'info');
        }, 800);
      } catch (error) {}
    }

    function getStripePaymentUrl(property) {
      const url = new URL(STRIPE_PAYMENT_LINK_URL);
      const reference = property.reference || property.id;
      url.searchParams.set('client_reference_id', reference);
      url.searchParams.set('utm_source', 'captacion_app');
      url.searchParams.set('utm_medium', 'marketplace');
      url.searchParams.set('utm_campaign', 'compra_captacion');
      url.searchParams.set('captacion_ref', reference);
      url.searchParams.set('captacion_id', property.id);
      return url.toString();
    }

    async function confirmStripePayment(event) {
      event.preventDefault();
      if (!requireRegisteredAction('continuar con el pago')) return;
      const propertyId = document.getElementById('access-property-id').value;
      const property = properties.find(item => item.id === propertyId);
      if (!property) return;
      
      const btn = event.currentTarget;
      const oldText = btn.innerHTML;
      btn.innerHTML = 'Validando...';
      btn.disabled = true;

      const demandId = window.CAPTACION_CURRENT_MATCH_DEMAND_ID || '';
      if (demandId) {
        try {
          const endpoint = window.CAPTACION_API?.endpoints?.listXmlFeeds?.replace('/xml-feeds', '/matches/persist') || '/wp-json/captacion/v1/matches/persist';
          await fetch(endpoint, {
            method: 'POST',
            headers: { 'X-WP-Nonce': window.CAPTACION_API?.nonce || '', 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
              demand_id: demandId, 
              property_id: propertyId,
              user_property_id: property.userId || 0,
              user_demand_id: window.CAPTACION_API?.userId || 0
            })
          });
        } catch(e) {
          console.error('Error persist match', e);
        }
      }

      btn.innerHTML = oldText;
      btn.disabled = false;

      if (!isStripePaymentConfigured()) {
        showToast('Pega primero tu Payment Link real de Stripe en STRIPE_PAYMENT_LINK_URL.', 'info');
        return;
      }
      window.open(getStripePaymentUrl(property), '_blank', 'noopener,noreferrer');
      persistWpRecord('access_request', { id:`access-${property.id}-${Date.now()}`, propertyId:property.id, title:property.title, reference:property.reference || property.id, status:'payment_started', createdAt:Date.now() }, { title:property.title, status:'payment_started', relatedId:property.id });
      closeAccessModal();
      addPrivateNotification({ category:'Operaciones', title:'Pago iniciado para desbloquear captación', detail:`Se ha iniciado el pago de acceso para ${property.title}.`, target:'operations', dueAt:Date.now()+3600000*2, dedupeKey:`payment-${property.id}` });
      addPrivateTask({ title:'Confirmar pago y acceso protegido', detail:`Revisa el estado del pago y del expediente de ${property.title}.`, priority:'high', due:'Hoy', dueAt:Date.now()+3600000*4, target:'operations', dedupeKey:`task-payment-${property.id}` });
      showToast(`Pago iniciado para ${property.title}.`, 'success');
    }
    function renderNeedsDashboard() {
      const dashContainer = document.getElementById('needs-dashboard');
      if (!dashContainer) return;

      const totalNeeds = needs.length;
      const totalNeedsValue = needs.reduce((sum, item) => sum + (Number(item.budget) || 0), 0);
      const ccaaCounts = {};
      const provinceCounts = {};
      const municipalityCounts = {};

      needs.forEach(n => {
        if (n.ccaa) ccaaCounts[n.ccaa] = (ccaaCounts[n.ccaa] || 0) + 1;
        if (n.province) provinceCounts[n.province] = (provinceCounts[n.province] || 0) + 1;
        if (n.municipality) municipalityCounts[n.municipality] = (municipalityCounts[n.municipality] || 0) + 1;
      });

      let ccaasHtml = '<span class="text-xs text-slate-400">Sin datos de CCAA</span>';
      if (Object.keys(ccaaCounts).length > 0) {
        ccaasHtml = Object.entries(ccaaCounts).map(([ccaa, count]) => `
          <button onclick="filterByDashboard('ccaa', '${ccaa}')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-blue-light hover:text-blue border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 transition-all flex items-center">
            ${ccaa} <span class="bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded-full ml-1.5 text-[9px] font-black">${count}</span>
          </button>
        `).join('');
      }

      let provincesHtml = '<span class="text-xs text-slate-400">Sin datos</span>';
      if (Object.keys(provinceCounts).length > 0) {
        provincesHtml = Object.entries(provinceCounts).map(([prov, count]) => `
          <button onclick="filterByDashboard('province', '${prov}')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-blue-light hover:text-blue border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 transition-all flex items-center">
            ${prov} <span class="bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded-full ml-1.5 text-[9px] font-black">${count}</span>
          </button>
        `).join('');
      }

      let municipalitiesHtml = '<span class="text-xs text-slate-400">Sin datos</span>';
      if (Object.keys(municipalityCounts).length > 0) {
        municipalitiesHtml = Object.entries(municipalityCounts).map(([mun, count]) => `
          <button onclick="filterByDashboard('municipality', '${mun}')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-blue-light hover:text-blue border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 transition-all flex items-center">
            ${mun} <span class="bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded-full ml-1.5 text-[9px] font-black">${count}</span>
          </button>
        `).join('');
      }

      dashContainer.innerHTML = `
        <div class="bg-gradient-to-br from-navy to-navy-light text-white p-6 rounded-3xl shadow-sm flex flex-col justify-between">
          <div>
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-300">Demanda Total Activa</span>
            <strong class="block text-4xl sm:text-5xl font-black mt-2 text-white">${totalNeeds}</strong>
            <span class="block text-xs text-slate-200 mt-2">Potencial estimado de operaciones: <strong class="text-white">${formatCurrency(totalNeedsValue)}</strong></span>
          </div>
          <p class="text-[11px] text-slate-300 mt-4 leading-relaxed font-semibold">Necesidades de compra activas en la red profesional nacional.</p>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col">
          <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-3 block">Por Comunidad Autónoma</span>
          <div class="flex flex-wrap gap-2 overflow-y-auto max-h-28 scrollbar-custom">${ccaasHtml}</div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col">
          <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-3 block">Por Provincia</span>
          <div class="flex flex-wrap gap-2 overflow-y-auto max-h-28 scrollbar-custom">${provincesHtml}</div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col">
          <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-3 block">Por Municipio</span>
          <div class="flex flex-wrap gap-2 overflow-y-auto max-h-28 scrollbar-custom">${municipalitiesHtml}</div>
        </div>
      `;
    }

    function renderMarketplaceDashboard() {
      const dashContainer = document.getElementById('marketplace-dashboard');
      if (!dashContainer) return;

      const totalProperties = properties.length;
      const totalPropertiesValue = properties.reduce((sum, item) => sum + (Number(item.price) || 0), 0);
      const ccaaCounts = {};
      const provinceCounts = {};
      const municipalityCounts = {};

      properties.forEach(prop => {
        if (prop.ccaa) ccaaCounts[prop.ccaa] = (ccaaCounts[prop.ccaa] || 0) + 1;
        if (prop.province) provinceCounts[prop.province] = (provinceCounts[prop.province] || 0) + 1;
        if (prop.municipality) municipalityCounts[prop.municipality] = (municipalityCounts[prop.municipality] || 0) + 1;
      });

      const renderPills = (entries, type, emptyText = 'Sin datos') => {
        if (!entries.length) return `<span class="text-xs text-slate-400">${emptyText}</span>`;
        return entries.slice(0, 8).map(([label, count]) => `
          <button onclick="filterMarketplaceByDashboard('${type}', '${escapeHTML(String(label))}')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-blue-light hover:text-blue border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 transition-all flex items-center">
            ${escapeHTML(label)} <span class="bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded-full ml-1.5 text-[9px] font-black">${count}</span>
          </button>
        `).join('');
      };

      const topCcaa = Object.entries(ccaaCounts).sort((a, b) => b[1] - a[1]);
      const topProvinces = Object.entries(provinceCounts).sort((a, b) => b[1] - a[1]);
      const topMunicipalities = Object.entries(municipalityCounts).sort((a, b) => b[1] - a[1]);

      dashContainer.innerHTML = `
        <div class="bg-gradient-to-br from-navy to-navy-light text-white p-6 rounded-3xl shadow-sm flex flex-col justify-between">
          <div>
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-300">Captación Total Activa</span>
            <strong class="block text-4xl sm:text-5xl font-black mt-2 text-white">${totalProperties}</strong>
            <span class="block text-xs text-slate-200 mt-2">Valor estimado de captaciones: <strong class="text-white">${formatCurrency(totalPropertiesValue)}</strong></span>
          </div>
          <p class="text-[11px] text-slate-300 mt-4 leading-relaxed font-semibold">Captaciones disponibles en la red profesional nacional.</p>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col">
          <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-3 block">Por Comunidad Autónoma</span>
          <div class="flex flex-wrap gap-2 overflow-y-auto max-h-28 scrollbar-hidden">${renderPills(topCcaa, 'ccaa', 'Sin datos de CCAA')}</div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col">
          <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-3 block">Por Provincia</span>
          <div class="flex flex-wrap gap-2 overflow-y-auto max-h-28 scrollbar-hidden">${renderPills(topProvinces, 'province')}</div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col">
          <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-3 block">Por Municipio</span>
          <div class="flex flex-wrap gap-2 overflow-y-auto max-h-28 scrollbar-hidden">${renderPills(topMunicipalities, 'municipality')}</div>
        </div>
      `;
    }

    function appendLoadMoreControl(container, shown, total, clickHandler, noun) {
      if (!container || total <= LIST_BATCH_SIZE) return;
      const totalPages = Math.ceil(total / LIST_BATCH_SIZE);
      const currentPage = Math.max(1, Math.ceil(shown / LIST_BATCH_SIZE));
      
      const control = document.createElement('div');
      control.className = 'marketplace-load-more-control col-span-full mt-10 mb-4 flex flex-col items-center justify-center gap-3 text-center';

      let pagesHtml = '';
      if (totalPages > 1) {
        const prevDisabled = currentPage <= 1;
        pagesHtml += `<button type="button" onclick="${clickHandler}(${currentPage - 1})" ${prevDisabled ? 'disabled' : ''} class="marketplace-page-button flex items-center gap-1 ${prevDisabled ? 'opacity-40 cursor-not-allowed' : 'hover:border-blue hover:text-blue'}" aria-label="Página anterior"><span>‹</span><span>Ant</span></button>`;

        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
          startPage = Math.max(1, endPage - 4);
        }

        if (startPage > 1) {
          pagesHtml += `<button type="button" onclick="${clickHandler}(1)" class="marketplace-page-button">1</button>`;
          if (startPage > 2) {
            pagesHtml += `<span class="px-1 text-slate-400 font-bold text-xs">...</span>`;
          }
        }

        for (let p = startPage; p <= endPage; p++) {
          const isActive = p === currentPage;
          pagesHtml += `<button type="button" onclick="${clickHandler}(${p})" class="marketplace-page-button ${isActive ? 'is-active' : ''}" aria-label="Página ${p}">${p}</button>`;
        }

        if (endPage < totalPages) {
          if (endPage < totalPages - 1) {
            pagesHtml += `<span class="px-1 text-slate-400 font-bold text-xs">...</span>`;
          }
          pagesHtml += `<button type="button" onclick="${clickHandler}(${totalPages})" class="marketplace-page-button">${totalPages}</button>`;
        }

        const nextDisabled = currentPage >= totalPages;
        pagesHtml += `<button type="button" onclick="${clickHandler}(${currentPage + 1})" ${nextDisabled ? 'disabled' : ''} class="marketplace-page-button flex items-center gap-1 ${nextDisabled ? 'opacity-40 cursor-not-allowed' : 'hover:border-blue hover:text-blue'}" aria-label="Página siguiente"><span>Sig</span><span>›</span></button>`;
      }

      control.innerHTML = `
        <div class="marketplace-pagination flex flex-wrap items-center justify-center gap-2 p-2 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm" aria-label="Paginación de ${noun}">
          ${pagesHtml}
        </div>
        <span class="marketplace-load-more-status text-xs font-bold text-slate-500 dark:text-slate-400">
          Mostrando <strong class="text-navy dark:text-white">${Math.min(shown, total)}</strong> de <strong class="text-navy dark:text-white">${total}</strong> ${noun}
        </span>
      `;
      container.appendChild(control);
    }

    function loadMoreNeeds() {
      needsVisibleLimit += LIST_BATCH_SIZE;
      renderNeedsUI(lastFilteredNeeds.length ? lastFilteredNeeds : needs);
    }

    function appendNeedsPagination(container, totalItems) {
      if (!container || totalItems <= 9) return;
      const totalPages = Math.ceil(totalItems / 9);
      const control = document.createElement('div');
      control.className = 'mt-8 flex flex-wrap items-center justify-center gap-1.5 py-4';
      
      let html = '';
      for (let i = 1; i <= totalPages; i++) {
        const isActive = i === needsCurrentPage;
        const activeClass = isActive 
          ? 'bg-blue text-white shadow-md border-blue' 
          : 'bg-white hover:bg-slate-50 text-navy border-slate-200';
        html += `
          <button type="button" onclick="setNeedsPage(${i})" class="px-3.5 py-2 rounded-xl border text-xs font-black transition-all ${activeClass}">
            ${i}
          </button>
        `;
        if (i < totalPages) {
          html += `<span class="text-slate-300 text-xs px-0.5">-</span>`;
        }
      }
      
      if (needsCurrentPage < totalPages) {
        html += `
          <button type="button" onclick="setNeedsPage(${needsCurrentPage + 1})" class="ml-2 px-4 py-2 rounded-xl bg-navy hover:bg-navy-light text-white text-xs font-black shadow-md transition-all">
            Ver más
          </button>
        `;
      }
      
      control.innerHTML = html;
      container.appendChild(control);
    }

    function setNeedsPage(page) {
      needsCurrentPage = page;
      renderNeedsUI(lastFilteredNeeds.length ? lastFilteredNeeds : needs);
      document.getElementById('needs-list-container')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function loadMoreMarketplace(page = 0) {
      marketplaceCurrentPage = page > 0 ? page : marketplaceCurrentPage + 1;
      marketplaceVisibleLimit = marketplaceCurrentPage * LIST_BATCH_SIZE;
      renderMarketplace();
    }

    function normalizeMatchText(value) {
      return String(value || '').trim().toLocaleLowerCase('es');
    }

    function numbersWithinTolerance(value, target, toleranceRatio) {
      const current = Number(value) || 0;
      const expected = Number(target) || 0;
      if (!expected) return true;
      if (!current) return false;
      const min = expected * (1 - toleranceRatio);
      const max = expected * (1 + toleranceRatio);
      return current >= min && current <= max;
    }

    function integerWithinDelta(value, target, delta = 1) {
      const current = Number(value) || 0;
      const expected = Number(target) || 0;
      if (!expected) return true;
      if (!current) return false;
      return Math.abs(current - expected) <= delta;
    }

    function getCompatibilityHardChecks(property, need) {
      const propertyType = normalizeMatchText(normalizePropertyType(property.property_type || property.type));
      const needType = normalizeMatchText(normalizePropertyType(need.property_type || need.type));
      const propertyCcaa = normalizeMatchText(property.ccaa || property.autonomous_community || property.autonomousCommunity);
      const needCcaa = normalizeMatchText(need.ccaa || need.autonomous_community || need.autonomousCommunity);
      const propertyProvince = normalizeMatchText(property.province || property.location);
      const needProvince = normalizeMatchText(need.province || need.location);
      const propertyMunicipality = normalizeMatchText(property.municipality || property.municipality_name);
      const needMunicipality = normalizeMatchText(need.municipality || need.municipality_name);
      const propertyPrice = Number(property.indicative_price ?? property.price) || 0;
      const needBudget = Number(need.max_budget ?? need.budget) || 0;
      const propertyBedrooms = Number(property.rooms ?? property.bedrooms) || 0;
      const needBedrooms = Number(need.min_rooms ?? need.bedrooms) || 0;
      const propertyBathrooms = Number(property.bathrooms) || 0;
      const needBathrooms = Number(need.min_bathrooms ?? need.bathrooms) || 0;
      const propertySurface = Number(property.total_area_m2 ?? property.surface) || 0;
      const needSurface = Number(need.desired_area_min_m2 ?? need.surface) || 0;
      const acceptedConditions = Array.isArray(need.accepted_property_conditions) ? need.accepted_property_conditions : [];
      const acceptedMandates = Array.isArray(need.accepted_mandate_types) ? need.accepted_mandate_types : [];
      const propertyCondition = cleanText(property.property_condition || '');
      const propertyMandate = cleanText(property.mandate_type || '');
      const requiredDocs = cleanText(need.required_documentation_level || '');
      const propertyDocs = cleanText(property.documentation_level || property.docs || '');

      return {
        type: !propertyType || !needType || propertyType === needType,
        ccaa: Boolean(propertyCcaa && needCcaa && propertyCcaa === needCcaa),
        province: Boolean(propertyProvince && needProvince && propertyProvince === needProvince),
        municipality: !propertyMunicipality || !needMunicipality || propertyMunicipality === needMunicipality,
        bedrooms: !needBedrooms || propertyBedrooms >= needBedrooms,
        bathrooms: !needBathrooms || propertyBathrooms >= needBathrooms,
        surface: !needSurface || propertySurface >= needSurface,
        budget: !needBudget || (propertyPrice > 0 && propertyPrice <= needBudget),
        condition: !acceptedConditions.length || !propertyCondition || acceptedConditions.includes(propertyCondition),
        mandate: !acceptedMandates.length || !propertyMandate || acceptedMandates.includes('Cualquiera') || acceptedMandates.includes(propertyMandate) || (propertyMandate === 'Sí, con exclusividad' && acceptedMandates.includes('Con exclusividad')) || (propertyMandate === 'No, nota de encargo abierta' && acceptedMandates.includes('Nota de encargo abierta')),
        documentation: !requiredDocs || requiredDocs === 'No califica' || !propertyDocs || propertyDocs === requiredDocs
      };
    }

    function calculatePropertyNeedCompatibility(property, need) {
      if (!property || !need) return 0;
      const checks = getCompatibilityHardChecks(property, need);
      if (!Object.values(checks).every(Boolean)) return 0;

      let score = 55;
      const propertyPostalCode = String(property.postalCode || '').trim();
      const needPostalCode = String(need.postalCode || '').trim();
      const propertyMunicipality = normalizeMatchText(property.municipality);
      const needMunicipality = normalizeMatchText(need.municipality);
      const propertyPrice = Number(property.indicative_price ?? property.price) || 0;
      const needBudget = Number(need.max_budget ?? need.budget) || 0;
      const propertySurface = Number(property.total_area_m2 ?? property.surface) || 0;
      const needSurface = Number(need.desired_area_min_m2 ?? need.surface) || 0;

      if (checks.type) score += 10;
      score += 20; // CCAA, provincia y municipio validados como requisitos territoriales.
      if (propertyPostalCode && needPostalCode && propertyPostalCode === needPostalCode) score += 10;
      else if (propertyMunicipality && needMunicipality && propertyMunicipality === needMunicipality) score += 6;

      if (propertyPrice && needBudget) {
        score += Math.round(15 * Math.min(1, propertyPrice / needBudget));
      }
      if (propertySurface && needSurface) {
        score += Math.round(10 * Math.min(1, needSurface / propertySurface));
      }

      return Math.max(60, Math.min(100, score));
    }

    function getCompatiblePropertiesForNeed(need, limit = 3, ownedOnly = true) {
      return (ownedOnly ? privateProperties() : getActiveMarketplaceProperties())
        .map(property => ({ property, score: calculatePropertyNeedCompatibility(property, need) }))
        .filter(match => match.score > 0)
        .sort((a, b) => b.score - a.score || Number(a.property.price || 0) - Number(b.property.price || 0))
        .slice(0, limit);
    }

    function getCompatibleNeedsForProperty(property, limit = 3, ownedOnly = true) {
      return (ownedOnly ? privateNeeds() : needs)
        .map(need => ({ need, score: calculatePropertyNeedCompatibility(property, need) }))
        .filter(match => match.score > 0)
        .sort((a, b) => b.score - a.score || Number(b.need.budget || 0) - Number(a.need.budget || 0))
        .slice(0, limit);
    }

    function getFavoriteStorageKey(type) {
      const email = (getDemoSession?.()?.email || 'guest').toLowerCase().replace(/[^a-z0-9@._-]/g, '');
      const names = { demand:'favoriteDemands', capture:'favoriteCaptures', match:'favoriteMatches' };
      return `captacion_${names[type] || 'favorites'}_${email}`;
    }

    function getFavoriteIds(type) {
      try { return JSON.parse(localStorage.getItem(getFavoriteStorageKey(type))) || []; }
      catch (error) { return []; }
    }

    function isFavorite(type, id) { return getFavoriteIds(type).includes(String(id)); }

    function persistFavoriteCollections() {
      const userEmail = getDemoSession?.()?.email || '';
      const payload = { favoriteDemands:getFavoriteIds('demand'), favoriteCaptures:getFavoriteIds('capture'), favoriteMatches:getFavoriteIds('match'), updatedAt:Date.now() };
      persistWpRecord('user_preferences', payload, { recordKey:`favorites-${userEmail || 'guest'}`, userEmail, title:'Mis favoritos', status:'active' });
    }

    function toggleFavorite(type, id) {
      if (!requireRegisteredAction('guardar favoritos')) return;
      const key = getFavoriteStorageKey(type);
      const values = getFavoriteIds(type);
      const normalizedId = String(id);
      const index = values.indexOf(normalizedId);
      const added = index < 0;
      if (added) values.unshift(normalizedId); else values.splice(index, 1);
      localStorage.setItem(key, JSON.stringify(values));
      persistFavoriteCollections();
      if (type === 'demand') renderNeedsUI(lastFilteredNeeds.length ? lastFilteredNeeds : needs);
      if (type === 'capture') renderMarketplace();
      if (type === 'match') renderSalesMatches();
      renderPrivateFavorites();
      showToast(added ? 'Añadido a Mis favoritos.' : 'Eliminado de Mis favoritos.', 'success');
    }

    function favoriteButton(type, id, label = 'Guardar en favoritos') {
      const active = isFavorite(type, id);
      const accessibleLabel = active ? 'Quitar de favoritos' : 'Añadir a favoritos';
      return `<button type="button" onclick="event.stopPropagation();toggleFavorite('${type}','${escapeHTML(String(id))}')" class="favorite-toggle ${active ? 'is-active' : ''}" aria-label="${accessibleLabel}" aria-pressed="${active ? 'true' : 'false'}" title="${accessibleLabel}">${active ? '♥' : '♡'}</button>`;
    }

    let backendMatchRows = [];
    async function loadBackendMatches() {
      try { const response = await fetch('/api/matches.php?action=list', { credentials:'same-origin' }); if (!response.ok) return; const data = await response.json(); if (data.ok && Array.isArray(data.matches)) { backendMatchRows = data.matches; renderSalesMatches(); renderPrivateMatches(); } } catch (error) { /* El matching local sigue disponible. */ }
    }
    function getSalesMatchRecords(ownedOnly = true) {
      const rows = [];
      (ownedOnly ? privateProperties().filter(isMarketplaceVisibleProperty) : getActiveMarketplaceProperties()).forEach(property => {
        getCompatibleNeedsForProperty(property, 200, ownedOnly).forEach(({ need, score }) => rows.push({
          id:`${property.id}-${need.id}`, property, need, score,
          date:Math.max(Number(property.date)||0, Number(need.date)||0),
          estimatedValue:Number(property.price)||Number(need.budget)||0
        }));
      });
      return rows;
    }

    function openSalesMatchDetails(matchId) {
      if (!requireRegisteredAction('ver los detalles de esta coincidencia')) return;
      const match = getSalesMatchRecords(false).find(item => item.id === matchId);
      if (!match) return;
      openPostPublishCompatibilityReport('property', match.property);
    }

    function renderSalesMatches() {
      const container = document.getElementById('sales-matches-grid');
      if (!container) return;
      const search = normalizeMatchText(document.getElementById('sales-match-search')?.value || '');
      const type = document.getElementById('sales-match-type')?.value || 'all';
      const ccaa = document.getElementById('sales-match-ccaa')?.value || 'all';
      const province = document.getElementById('sales-match-province')?.value || 'all';
      const municipality = document.getElementById('sales-match-municipality')?.value || 'all';
      const level = document.getElementById('sales-match-level')?.value || 'all';
      const sort = document.getElementById('sales-match-sort')?.value || 'newest';
      let matches = getSalesMatchRecords(false).filter(item => {
        const haystack = normalizeMatchText(`${item.property.title} ${item.property.reference} ${item.property.province} ${item.property.municipality} ${item.need.title}`);
        const levelOk = level === 'all' || (level === 'high' ? item.score >= 75 : item.score >= 60 && item.score < 75);
        return (!search || haystack.includes(search)) && (type === 'all' || item.property.type === type) && (ccaa === 'all' || item.property.ccaa === ccaa) && (province === 'all' || item.property.province === province) && (municipality === 'all' || item.property.municipality === municipality) && levelOk;
      });
      matches.sort((a,b) => sort === 'score' ? b.score-a.score : sort === 'value' ? b.estimatedValue-a.estimatedValue : b.date-a.date);
      const allMatches = getSalesMatchRecords(false);
      const count = document.getElementById('sales-match-count');
      if (count) {
        count.textContent = allMatches.length ? String(allMatches.length) : 'Sin coincidencias aún';
        count.classList.toggle('text-3xl', allMatches.length > 0);
        count.classList.toggle('text-xl', allMatches.length === 0);
      }
      const value = document.getElementById('sales-match-value');
      if (value) {
        value.textContent = allMatches.length ? formatCurrency(allMatches.reduce((sum,item)=>sum+item.estimatedValue,0)) : 'Sin valor estimado';
        value.classList.toggle('text-3xl', allMatches.length > 0);
        value.classList.toggle('text-xl', allMatches.length === 0);
      }
      const localCards = matches.map(item => `<article class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5"><div class="flex items-start justify-between gap-3"><div><span class="text-[10px] font-black uppercase text-blue">${escapeHTML(item.property.reference || item.property.id)}</span><h3 class="text-sm font-black text-navy mt-1">${escapeHTML(item.property.title)}</h3></div><div class="flex items-center gap-2"><span class="px-2.5 py-1 rounded-full border text-[10px] font-black ${getCompatibilityBadgeClasses(item.score)}">${item.score}%</span>${favoriteButton('match', item.id, 'Guardar coincidencia en favoritos')}</div></div><div class="mt-4 space-y-2 text-[11px] text-slate-500"><p><strong class="text-navy">Demanda:</strong> ${escapeHTML(item.need.title)}</p><p><strong class="text-navy">Zona:</strong> ${escapeHTML([item.property.province,item.property.municipality].filter(Boolean).join(' · '))}</p><p><strong class="text-navy">Valor estimado:</strong> ${formatCurrency(item.estimatedValue)}</p><p><strong class="text-navy">Encaje:</strong> tipo, ubicación y parámetros económicos compatibles.</p></div><button type="button" onclick="openSalesMatchDetails('${item.id}')" class="mt-4 w-full py-2.5 rounded-xl bg-blue text-white text-xs font-black">Ver detalles</button></article>`).join('');
      const backendCards = backendMatchRows.filter(item => Number(item.score) >= 60).map(item => `<article class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-5"><div class="flex items-start justify-between gap-3"><div><span class="text-[10px] font-black uppercase text-emerald-600">Matching backend · #${Number(item.record_id)}</span><h3 class="text-sm font-black text-navy mt-1">${escapeHTML(item.record_title || 'Oportunidad compatible')}</h3></div><span class="px-2.5 py-1 rounded-full border text-[10px] font-black ${getCompatibilityBadgeClasses(Number(item.score))}">${Number(item.score)}%</span></div><div class="mt-4 space-y-2 text-[11px] text-slate-500"><p><strong class="text-navy">Contraparte:</strong> ${escapeHTML(item.matched_title || 'Registro compatible')}</p><p><strong class="text-navy">Origen:</strong> coincidencia persistida por el motor backend.</p></div><button type="button" onclick="navigateTo('/area-privada')" class="mt-4 w-full py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-black">Abrir panel protegido</button></article>`).join('');
      container.innerHTML = localCards + backendCards || '<div class="md:col-span-2 xl:col-span-3 p-8 rounded-2xl bg-white border border-slate-200 text-sm text-slate-500">No hay coincidencias con los filtros seleccionados.</div>';
    }

    function getCompatibilityBadgeClasses(score) {
      if (score >= 75) return 'bg-green-light text-green border-green/20';
      if (score >= 55) return 'bg-blue-light text-blue border-blue/20';
      return 'bg-amber-light text-amber border-amber/20';
    }
    function renderReputationBadge(item = {}) {
      const score = Number(item.reputation_score ?? item.reputationScore);
      if (!Number.isFinite(score) || score <= 0) return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 shrink-0">Ranking pendiente</span>';
      const category = String(item.reputation_category || item.reputationCategory || 'professional').replaceAll('_', ' ');
      return `<span title="Reputación profesional calculada por actividad verificada" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">★ ${score}/100 · ${escapeHTML(category)}</span>`;
    }

    function renderLinkedPropertiesForNeed(need) {
      const matches = getCompatiblePropertiesForNeed(need, 3);
      if (!matches.length) {
        return `<div class="mt-3 p-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 text-[11px] text-slate-500"><strong class="block text-navy mb-1">Cruce automático de cartera</strong>No se han detectado captaciones compatibles en la base local. La demanda puede mantenerse activa para recibir nuevas propuestas.</div>`;
      }
      return `<div class="mt-3 p-3 rounded-xl border border-blue/15 bg-blue-light/35">
        <div class="flex items-center justify-between gap-3 mb-2"><strong class="text-[11px] text-navy">Captaciones compatibles detectadas</strong><span class="text-[10px] font-bold text-blue">${matches.length} coincidencia${matches.length === 1 ? '' : 's'}</span></div>
        <div class="space-y-2">${matches.map(({ property, score }) => `
          <div class="p-2.5 rounded-lg bg-white border border-slate-200 flex items-center justify-between gap-3">
             <div class="min-w-0"><span class="block text-[10px] font-bold text-blue">Ref. ${escapeHTML(property.reference || property.id)}</span><strong class="block text-[11px] text-navy truncate">${escapeHTML(property.title)}</strong><span class="block text-[10px] text-slate-500">${formatCurrency(property.price)} · C.P. ${escapeHTML(maskPublicPostalCode(property.postalCode))}</span></div>
            <div class="shrink-0 text-right"><span class="inline-flex px-2 py-1 rounded-full border text-[10px] font-bold ${getCompatibilityBadgeClasses(score)}">${score}% match</span><button type="button" onclick="openMapPropertyCard('${property.id}')" class="block mt-1 ml-auto text-[10px] font-bold text-blue hover:underline">Ver propiedad</button></div>
          </div>`).join('')}</div>
      </div>`;
    }

    function renderLinkedNeedsForProperty(property) {
      const matches = getCompatibleNeedsForProperty(property, 3);
      if (!matches.length) {
        return `<div class="mt-3 p-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 text-[11px] text-slate-500"><strong class="block text-navy mb-1">Demandas vinculables</strong>No se han detectado demandas compatibles en la base local. La captación seguirá disponible para futuras coincidencias.</div>`;
      }
      return `<div class="mt-3 p-3 rounded-xl border border-green/15 bg-green-light/35">
        <div class="flex items-center justify-between gap-3 mb-2"><strong class="text-[11px] text-navy">Demandas vinculables detectadas</strong><span class="text-[10px] font-bold text-green">${matches.length} coincidencia${matches.length === 1 ? '' : 's'}</span></div>
        <div class="space-y-2">${matches.map(({ need, score }) => `
          <div class="p-2.5 rounded-lg bg-white border border-slate-200 flex items-center justify-between gap-3">
             <div class="min-w-0"><span class="block text-[10px] font-bold text-green">Intención de búsqueda</span><strong class="block text-[11px] text-navy truncate">${escapeHTML(need.title)}</strong><span class="block text-[10px] text-slate-500">Hasta ${formatCurrency(need.budget)} · C.P. ${escapeHTML(maskPublicPostalCode(need.postalCode))}</span></div>
            <div class="shrink-0 text-right"><span class="inline-flex px-2 py-1 rounded-full border text-[10px] font-bold ${getCompatibilityBadgeClasses(score)}">${score}% match</span><button type="button" onclick="openMapNeedCard('${need.id}')" class="block mt-1 ml-auto text-[10px] font-bold text-green hover:underline">Ver demanda</button></div>
          </div>`).join('')}</div>
      </div>`;
    }

    function openHomeNeedMatches(needId) {
      if (!requireRegisteredAction('consultar la información relacionada con esta demanda')) return;
      const selectedNeed = needs.find(item => item.id === needId);
      if (!selectedNeed) return;
      window.CAPTACION_CURRENT_MATCH_DEMAND_ID = needId;
      const matches = getCompatiblePropertiesForNeed(selectedNeed, 3);
      openMapNeedCard(needId);
      setTimeout(() => showToast(matches.length ? `Demanda abierta: se han detectado ${matches.length} captaciones compatibles.` : 'Demanda abierta: no se han detectado captaciones compatibles todavía.', matches.length ? 'success' : 'info'), 220);
    }

    function buildOpportunityAccordion(title, subtitle, rowsHtml, buttonHtml = '', open = false) {
      return `<details class="opportunity-accordion" ${open ? 'open' : ''}>
        <summary class="px-5 py-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <strong class="block text-sm text-navy font-black">${escapeHTML(title)}</strong>
            <span class="block text-[11px] text-slate-500 mt-1">${escapeHTML(subtitle)}</span>
          </div>
          <span class="opportunity-accordion-chevron text-slate-400 text-sm transition-transform">▾</span>
        </summary>
        <div class="px-4 pb-4 space-y-3">
          ${rowsHtml}
          ${buttonHtml}
        </div>
      </details>`;
    }

    function renderMarketplaceAccordionSections(list) {
      const container = document.getElementById('marketplace-accordion-sections');
      if (!container) return;
      if (!list.length) {
        container.innerHTML = '';
        return;
      }
      const latestRows = list.slice(0, 6).map(prop => {
        const score = Number(prop.score || calculatePublicationOpportunityScore(prop, 'property')) || 0;
        return `<article class="opportunity-mini-row flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          <div class="min-w-0">
            <span class="block text-[10px] font-black uppercase tracking-wider text-blue">${escapeHTML(normalizeOpportunityCategory(prop.type))}</span>
            <strong class="block text-sm text-navy mt-1 truncate">${escapeHTML(prop.title)}</strong>
            <span class="block text-[11px] text-slate-500 mt-1">${escapeHTML(prop.province || prop.location)} · C.P. ${escapeHTML(maskPublicPostalCode(prop.postalCode))} · ${formatRelativeTime(prop.date)}</span>
          </div>
          <div class="flex flex-wrap items-center gap-2 shrink-0">
            <span class="private-status-pill bg-blue-light text-blue">★ ${score}/100</span>
            <button onclick="openMapPropertyCard('${prop.id}')" class="px-3 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-navy">Abrir</button>
          </div>
        </article>`;
      }).join('');
      const groups = OPPORTUNITY_CATEGORY_ORDER.map(category => ({
        category,
        items: list.filter(item => normalizeOpportunityCategory(item.type) === category)
      })).filter(group => group.items.length);
      const accordions = [
        buildOpportunityAccordion('Últimas captaciones publicadas', 'Ordenadas por tiempo de publicación para detectar producto nuevo con rapidez.', latestRows, `<div class="pt-1 flex justify-end"><button onclick="document.getElementById('market-sort').value='newest';refreshMarketplaceView();document.getElementById('marketplace-grid')?.scrollIntoView({behavior:'smooth',block:'start'});" class="px-4 py-2 rounded-xl bg-blue text-white text-[11px] font-bold">Ver todas las recientes</button></div>`, true)
      ];
      groups.forEach(group => {
        const rows = group.items.slice(0, 4).map(prop => `<article class="opportunity-mini-row flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          <div class="min-w-0">
            <strong class="block text-sm text-navy truncate">${escapeHTML(prop.title)}</strong>
            <span class="block text-[11px] text-slate-500 mt-1">${formatCurrency(prop.price)} · ${escapeHTML(prop.province || prop.location)} · ${formatRelativeTime(prop.date)}</span>
          </div>
          <div class="flex flex-wrap items-center gap-2 shrink-0">
            <span class="private-status-pill bg-green-light text-green">${getCompatibleNeedsForProperty(prop, 10).length} match</span>
            <button onclick="openMapPropertyCard('${prop.id}')" class="px-3 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-navy">Abrir</button>
          </div>
        </article>`).join('');
        accordions.push(buildOpportunityAccordion(group.category, `${group.items.length} propiedad${group.items.length === 1 ? '' : 'es'} en esta categoría.`, rows, `<div class="pt-1 flex justify-end"><button onclick="applyMarketplaceCategoryFilter('${group.category}')" class="px-4 py-2 rounded-xl border border-slate-200 text-[11px] font-bold text-blue">Ver todas las de ${escapeHTML(group.category)}</button></div>`));
      });
      container.innerHTML = accordions.join('');
    }

    function renderNeedsAccordionSections(list) {
      const container = document.getElementById('needs-accordion-sections');
      if (!container) return;
      if (!list.length) {
        container.innerHTML = '';
        return;
      }
      const latestRows = list.slice(0, 6).map(need => {
        const score = calculatePublicationOpportunityScore(need, 'need');
        return `<article class="opportunity-mini-row flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          <div class="min-w-0">
            <span class="block text-[10px] font-black uppercase tracking-wider text-green">${escapeHTML(normalizeOpportunityCategory(need.type))}</span>
            <strong class="block text-sm text-navy mt-1 truncate">${escapeHTML(need.title)}</strong>
            <span class="block text-[11px] text-slate-500 mt-1">Hasta ${formatCurrency(need.budget)} · C.P. ${escapeHTML(maskPublicPostalCode(need.postalCode))} · ${formatRelativeTime(need.date)}</span>
          </div>
          <div class="flex flex-wrap items-center gap-2 shrink-0">
            <span class="private-status-pill bg-green-light text-green">★ ${score}/100</span>
            <button onclick="openHomeNeedMatches('${need.id}')" class="px-3 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-navy">Abrir</button>
          </div>
        </article>`;
      }).join('');
      const groups = OPPORTUNITY_CATEGORY_ORDER.map(category => ({
        category,
        items: list.filter(item => normalizeOpportunityCategory(item.type) === category)
      })).filter(group => group.items.length);
      const accordions = [
        buildOpportunityAccordion('Últimas captaciones solicitadas', 'Demandas nuevas agrupadas por recencia para detectar encajes cuanto antes.', latestRows, `<div class="pt-1 flex justify-end"><button onclick="document.getElementById('need-filter-time').value='newest';filterNeeds();document.getElementById('needs-list-container')?.scrollIntoView({behavior:'smooth',block:'start'});" class="px-4 py-2 rounded-xl bg-navy text-white text-[11px] font-bold">Ver todas las recientes</button></div>`, true)
      ];
      groups.forEach(group => {
        const rows = group.items.slice(0, 4).map(need => `<article class="opportunity-mini-row flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          <div class="min-w-0">
            <strong class="block text-sm text-navy truncate">${escapeHTML(need.title)}</strong>
            <span class="block text-[11px] text-slate-500 mt-1">Hasta ${formatCurrency(need.budget)} · ${escapeHTML(need.province || 'España')} · ${formatRelativeTime(need.date)}</span>
          </div>
          <div class="flex flex-wrap items-center gap-2 shrink-0">
            <span class="private-status-pill bg-blue-light text-blue">${getCompatiblePropertiesForNeed(need, 10).length} match</span>
            <button onclick="openHomeNeedMatches('${need.id}')" class="px-3 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-navy">Abrir</button>
          </div>
        </article>`).join('');
        accordions.push(buildOpportunityAccordion(group.category, `${group.items.length} solicitud${group.items.length === 1 ? '' : 'es'} en esta categoría.`, rows, `<div class="pt-1 flex justify-end"><button onclick="applyNeedsCategoryFilter('${group.category}')" class="px-4 py-2 rounded-xl border border-slate-200 text-[11px] font-bold text-green">Ver todas las de ${escapeHTML(group.category)}</button></div>`));
      });
      container.innerHTML = accordions.join('');
    }

    function createOpportunityRailId(prefix, key = '') {
      return `${prefix}-rail-${String(key || 'latest').toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
    }

    function scrollOpportunityRail(railId, direction = 1) {
      const rail = document.getElementById(railId);
      if (!rail) return;
      const amount = Math.max(rail.clientWidth * 0.85, 260) * direction;
      rail.scrollBy({ left: amount, behavior:'smooth' });
    }

    function buildOpportunityCategoryNav(groups, mode = 'market') {
      if (!groups.length) return '';
      const chipClass = mode === 'market' ? 'is-market' : 'is-need';
      const action = mode === 'market' ? 'applyMarketplaceCategoryFilter' : 'applyNeedsCategoryFilter';
      return `<div class="opportunity-category-nav">${groups.map(group => `<button type="button" onclick="${action}('${escapeHTML(group.category)}')" class="opportunity-category-chip ${chipClass}">${escapeHTML(group.category)} <span class="ml-1 opacity-60">${group.items.length}</span></button>`).join('')}</div>`;
    }

    function buildOpportunityAccordion(title, subtitle, rowsHtml, buttonHtml = '', open = false, railId = '') {
      const safeRailId = railId || createOpportunityRailId('opportunity', title);
      return `<section class="opportunity-showcase">
        <div class="opportunity-showcase-toolbar mb-4">
          <div>
            <span class="block text-[10px] font-black uppercase tracking-[0.18em] text-blue">${escapeHTML(open ? 'Producto compartido' : 'Categoria activa')}</span>
            <strong class="block text-xl text-navy font-black mt-1">${escapeHTML(title)}</strong>
            <span class="block text-[11px] text-slate-500 mt-1">${escapeHTML(subtitle)}</span>
          </div>
          <div class="opportunity-showcase-controls">
            ${buttonHtml}
            <button type="button" onclick="scrollOpportunityRail('${safeRailId}', -1)" class="opportunity-showcase-arrow" aria-label="Ver anteriores">‹</button>
            <button type="button" onclick="scrollOpportunityRail('${safeRailId}', 1)" class="opportunity-showcase-arrow" aria-label="Ver siguientes">›</button>
          </div>
        </div>
        <div id="${safeRailId}" class="opportunity-showcase-rail">${rowsHtml}</div>
      </section>`;
    }

    function buildOpportunityCategoryNav(groups, mode = 'market') {
      if (!groups.length) return '';
      const scopeId = `opportunity-category-${mode}`;
      const action = mode === 'market' ? 'applyMarketplaceCategoryFilter' : 'applyNeedsCategoryFilter';
      const actionLabel = mode === 'market' ? 'Abrir ofertas' : 'Abrir demandas';
      const searchPlaceholder = mode === 'market' ? 'Buscar categoria: piso, nave, local...' : 'Buscar demanda: piso, casa, oficina...';
      const descriptions = {
        'Piso': 'Accesos rapidos a pisos y viviendas urbanas activas.',
        'Casa/Chalet': 'Demandas o captaciones de vivienda unifamiliar y chalet.',
        'Local Comercial': 'Producto comercial para negocio, retail o rentabilidad.',
        'Nave': 'Activos industriales y logisticos con uso profesional.',
        'Oficina': 'Espacios de trabajo y despachos para actividad empresarial.',
        'Edificio': 'Bloques completos y activos de mayor escala.',
        'Suelo/Terreno': 'Parcelas y suelo con potencial de desarrollo.',
        'Otros': 'Activos no encajados en una categoria principal.'
      };
      return `<section class="opportunity-category-explorer" id="${scopeId}">
        <div class="opportunity-category-explorer-toolbar">
          <div>
            <span class="block text-[10px] font-black uppercase tracking-[0.18em] ${mode === 'market' ? 'text-blue' : 'text-green'}">${mode === 'market' ? 'Grupos de captacion' : 'Grupos de demanda'}</span>
            <strong class="block text-xl text-navy font-black mt-1">Categorias explorables</strong>
            <span class="block text-[11px] text-slate-500 mt-1">Cada tipo aparece como ficha seleccionable y puedes filtrarlo por nombre antes de abrir el listado completo.</span>
          </div>
          <input type="search" class="opportunity-category-search" placeholder="${searchPlaceholder}" oninput="filterOpportunityCategoryCards('${scopeId}', this.value)" />
        </div>
        <div class="opportunity-category-grid">${groups.map(group => {
          const image = escapeHTML(getVirtualMarketplaceImage(group.category));
          const copy = descriptions[group.category] || descriptions['Otros'];
          return `<article class="opportunity-category-card" data-category-card data-search="${escapeHTML((group.category + ' ' + copy).toLowerCase())}">
            <div class="opportunity-category-card-image">
              <img src="${image}" data-virtual-type="${escapeHTML(group.category)}" width="640" height="666" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" alt="Categoria ${escapeHTML(group.category)}" />
              <span class="opportunity-category-card-badge">${mode === 'market' ? 'Categoria' : 'Grupo'}</span>
              <span class="opportunity-category-card-count">${group.items.length}</span>
            </div>
            <div class="opportunity-category-card-body">
              <strong class="opportunity-category-card-title">${escapeHTML(group.category)}</strong>
              <span class="opportunity-category-card-copy">${escapeHTML(copy)}</span>
              <div class="opportunity-category-card-footer">
                <div><span class="opportunity-category-card-note">${group.items.length} ${mode === 'market' ? 'captaciones' : 'solicitudes'}</span></div>
                <button type="button" onclick="${action}('${escapeHTML(group.category)}')" class="opportunity-category-card-action">${actionLabel}</button>
              </div>
            </div>
          </article>`;
        }).join('')}</div>
        <div class="opportunity-category-empty hidden" data-category-empty>No hay categorias que coincidan con la busqueda.</div>
      </section>`;
    }

    function filterOpportunityCategoryCards(scopeId, query = '') {
      const scope = document.getElementById(scopeId);
      if (!scope) return;
      const value = cleanText(query || '').toLowerCase();
      let visible = 0;
      scope.querySelectorAll('[data-category-card]').forEach(card => {
        const haystack = card.getAttribute('data-search') || '';
        const matches = value === '' || haystack.includes(value);
        card.classList.toggle('is-hidden', !matches);
        if (matches) visible += 1;
      });
      const empty = scope.querySelector('[data-category-empty]');
      if (empty) empty.classList.toggle('hidden', visible !== 0);
    }

    function buildOpportunityAccordion(title, subtitle, rowsHtml, buttonHtml = '', open = false, railId = '') {
      const safeRailId = railId || createOpportunityRailId('opportunity', title);
      return `<section class="opportunity-showcase">
        <div class="opportunity-showcase-toolbar mb-4">
          <div>
            <span class="block text-[10px] font-black uppercase tracking-[0.18em] text-blue">${escapeHTML(open ? 'Producto compartido' : 'Categoria activa')}</span>
            <strong class="block text-xl text-navy font-black mt-1">${escapeHTML(title)}</strong>
            <span class="block text-[11px] text-slate-500 mt-1">${escapeHTML(subtitle)}</span>
          </div>
          <div class="opportunity-showcase-controls">
            <button type="button" onclick="scrollOpportunityRail('${safeRailId}', -1)" class="opportunity-showcase-arrow" aria-label="Ver anteriores"><span aria-hidden="true">&lsaquo;</span><span class="opportunity-showcase-arrow-label">Izquierda</span></button>
            ${buttonHtml}
            <button type="button" onclick="scrollOpportunityRail('${safeRailId}', 1)" class="opportunity-showcase-arrow" aria-label="Ver siguientes"><span class="opportunity-showcase-arrow-label">Derecha</span><span aria-hidden="true">&rsaquo;</span></button>
          </div>
        </div>
        <div id="${safeRailId}" class="opportunity-showcase-rail">${rowsHtml}</div>
      </section>`;
    }

    function renderMarketplaceShowcaseCard(prop, variant = 'latest') {
      const score = Number(prop.score || calculatePublicationOpportunityScore(prop, 'property')) || 0;
      const image = escapeHTML(resolveMarketplaceImage(prop.image, prop.type));
      const location = escapeHTML(prop.province || prop.location || 'Ubicación reservada');
      const postalCode = escapeHTML(maskPublicPostalCode(prop.postalCode));
      const publishedText = formatRelativeTime(prop.date);
      const price = formatCurrency(prop.price);
      const note = variant === 'latest' ? `${location} · C.P. ${postalCode}` : `${getCompatibleNeedsForProperty(prop, 10).length} match · ${location}`;
      return `<article class="opportunity-showcase-card">
        <div class="opportunity-showcase-card-image">
          <img src="${image}" data-virtual-type="${escapeHTML(prop.type || 'Activo inmobiliario')}" width="640" height="666" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" alt="Imagen de ${escapeHTML(prop.title)}" />
          <span class="opportunity-showcase-badge">${escapeHTML(normalizeOpportunityCategory(prop.type))}</span>
          <span class="opportunity-showcase-score">★ ${score}/100</span>
          <div class="absolute left-3 top-3 z-20">${favoriteButton('capture', prop.id, 'Guardar captación en favoritos')}</div>
        </div>
        <div class="opportunity-showcase-body">
          <div class="opportunity-showcase-meta"><span>${escapeHTML(publishedText)}</span><span>C.P. ${postalCode}</span></div>
          <strong class="opportunity-showcase-title">${escapeHTML(prop.title)}</strong>
          <span class="opportunity-showcase-copy">${note}</span>
          <div class="opportunity-showcase-footer">
            <div>
              <span class="opportunity-showcase-note">Precio</span>
              <strong class="opportunity-showcase-price">${price}</strong>
            </div>
            <button onclick="openMapPropertyCard('${prop.id}')" class="px-4 py-2 rounded-xl bg-white/12 border border-white/12 text-white text-[11px] font-black">Solicitar acceso</button>
          </div>
        </div>
      </article>`;
    }

    function renderNeedShowcaseCard(need, variant = 'latest') {
      const score = calculatePublicationOpportunityScore(need, 'need');
      const image = escapeHTML(getVirtualMarketplaceImage(need.type || 'Demanda activa'));
      const province = escapeHTML(need.province || 'España');
      const postalCode = escapeHTML(maskPublicPostalCode(need.postalCode));
      const publishedText = formatRelativeTime(need.date);
      const budget = formatCurrency(need.budget);
      const note = variant === 'latest' ? `${province} · C.P. ${postalCode}` : `${getCompatiblePropertiesForNeed(need, 10).length} match · ${province}`;
      return `<article class="opportunity-showcase-card">
        <div class="opportunity-showcase-card-image">
          <img src="${image}" data-virtual-type="${escapeHTML(need.type || 'Demanda activa')}" width="640" height="666" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" alt="Referencia visual de ${escapeHTML(need.title)}" />
          <span class="opportunity-showcase-badge">${escapeHTML(normalizeOpportunityCategory(need.type))}</span>
          <span class="opportunity-showcase-score">★ ${score}/100</span>
          <div class="absolute left-3 top-3 z-20">${favoriteButton('demand', need.id, 'Guardar demanda en favoritos')}</div>
        </div>
        <div class="opportunity-showcase-body">
          <div class="opportunity-showcase-meta"><span>${escapeHTML(publishedText)}</span><span>C.P. ${postalCode}</span></div>
          <strong class="opportunity-showcase-title">${escapeHTML(need.title)}</strong>
          <span class="opportunity-showcase-copy">${note}</span>
          <div class="opportunity-showcase-footer">
            <div>
              <span class="opportunity-showcase-note">Presupuesto</span>
              <strong class="opportunity-showcase-price">${budget}</strong>
            </div>
            <button onclick="openHomeNeedMatches('${need.id}')" class="px-4 py-2 rounded-xl bg-white/12 border border-white/12 text-white text-[11px] font-black">${variant === 'latest' ? 'Ver demanda' : 'Ver compatibles'}</button>
          </div>
        </div>
      </article>`;
    }

    function renderMarketplaceAccordionSections(list) {
      const container = document.getElementById('marketplace-accordion-sections');
      if (!container) return;
      if (!list.length) {
        container.innerHTML = '';
        return;
      }
      const latestRows = list.slice(0, 6).map(prop => renderMarketplaceShowcaseCard(prop, 'latest')).join('');
      const groups = OPPORTUNITY_CATEGORY_ORDER.map(category => ({
        category,
        items: list.filter(item => normalizeOpportunityCategory(item.type) === category)
      })).filter(group => group.items.length);
      const sections = [
        `<div class="opportunity-showcase-shell">${buildOpportunityAccordion('Últimas captaciones publicadas', 'Ordenadas por tiempo de publicación para detectar producto nuevo con rapidez.', latestRows, `<button onclick="document.getElementById('market-sort').value='newest';refreshMarketplaceView();document.getElementById('marketplace-grid')?.scrollIntoView({behavior:'smooth',block:'start'});" class="px-4 py-2 rounded-xl bg-blue text-white text-[11px] font-bold">Ver todas las recientes</button>`, true, createOpportunityRailId('market', 'latest'))}<div>${buildOpportunityCategoryNav(groups, 'market')}</div></div>`
      ];
      groups.forEach(group => {
        const rows = group.items.slice(0, 5).map(prop => renderMarketplaceShowcaseCard(prop, 'category')).join('');
        sections.push(buildOpportunityAccordion(group.category, `${group.items.length} propiedad${group.items.length === 1 ? '' : 'es'} en esta categoria.`, rows, `<button onclick="applyMarketplaceCategoryFilter('${group.category}')" class="px-4 py-2 rounded-xl border border-slate-200 text-[11px] font-bold text-blue">Ver todas las de ${escapeHTML(group.category)}</button>`, false, createOpportunityRailId('market', group.category)));
      });
      container.innerHTML = sections.join('');
    }

    function renderNeedsAccordionSections(list) {
      const container = document.getElementById('needs-accordion-sections');
      if (!container) return;
      if (!list.length) {
        container.innerHTML = '';
        return;
      }
      const latestRows = list.slice(0, 6).map(need => renderNeedShowcaseCard(need, 'latest')).join('');
      const groups = OPPORTUNITY_CATEGORY_ORDER.map(category => ({
        category,
        items: list.filter(item => normalizeOpportunityCategory(item.type) === category)
      })).filter(group => group.items.length);
      const sections = [
        `<div class="opportunity-showcase-shell">${buildOpportunityAccordion('Últimas captaciones solicitadas', 'Demandas nuevas agrupadas por recencia para detectar encajes cuanto antes.', latestRows, `<button onclick="document.getElementById('need-filter-time').value='newest';filterNeeds();document.getElementById('needs-list-container')?.scrollIntoView({behavior:'smooth',block:'start'});" class="px-4 py-2 rounded-xl bg-navy text-white text-[11px] font-bold">Ver todas las recientes</button>`, true, createOpportunityRailId('need', 'latest'))}<div>${buildOpportunityCategoryNav(groups, 'need')}</div></div>`
      ];
      groups.forEach(group => {
        const rows = group.items.slice(0, 5).map(need => renderNeedShowcaseCard(need, 'category')).join('');
        sections.push(buildOpportunityAccordion(group.category, `${group.items.length} solicitud${group.items.length === 1 ? '' : 'es'} en esta categoria.`, rows, `<button onclick="applyNeedsCategoryFilter('${group.category}')" class="px-4 py-2 rounded-xl border border-slate-200 text-[11px] font-bold text-green">Ver todas las de ${escapeHTML(group.category)}</button>`, false, createOpportunityRailId('need', group.category)));
      });
      container.innerHTML = sections.join('');
    }

    function applyMarketplaceCategoryFilter(category) {
      const categorySelect = document.getElementById('market-category-filter');
      const sortSelect = document.getElementById('market-sort');
      if (categorySelect) categorySelect.value = category;
      if (sortSelect) sortSelect.value = 'category';
      refreshMarketplaceView();
      document.getElementById('marketplace-grid')?.scrollIntoView({ behavior:'smooth', block:'start' });
    }

    function applyNeedsCategoryFilter(category) {
      const categorySelect = document.getElementById('need-filter-type');
      const timeSelect = document.getElementById('need-filter-time');
      if (categorySelect) categorySelect.value = category;
      if (timeSelect) timeSelect.value = 'newest';
      filterNeeds();
      document.getElementById('needs-list-container')?.scrollIntoView({ behavior:'smooth', block:'start' });
    }

    function renderNeedsUI(list) {
      const container = document.getElementById('needs-list-container');
      if (!container) return;
      container.innerHTML = '';

      if (list.length === 0) {
        const needsAccordion = document.getElementById('needs-accordion-sections');
        if (needsAccordion) needsAccordion.innerHTML = '';
        container.innerHTML = `
          <div class="text-center py-16 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-4xl block mb-3">&#128269;</span>
            <h4 class="text-navy dark:text-white font-bold text-base">No hay demandas con estos criterios</h4>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Prueba a cambiar los filtros o publica tu mismo una demanda arriba.</p>
          </div>`;
        return;
      }

      const visibleNeeds = list.slice((needsCurrentPage - 1) * 12, needsCurrentPage * 12);
      const isListLayout = currentNeedsLayout === 'lista';
      const grid = document.createElement('div');
      grid.className = isListLayout ? "grid grid-cols-1 gap-4" : "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-3 gap-6";

      visibleNeeds.forEach(need => {
        const timeText = formatRelativeTime(need.date);
        const locationLabel = need.locality ? `${need.municipality} (${need.locality})` : need.municipality;
        const categoryImg = escapeHTML(getVirtualMarketplaceImage(need.type || 'Activo inmobiliario'));
        const score = calculatePublicationOpportunityScore(need, 'need');
        const card = document.createElement('article');
        card.id = `need-card-${need.id}`;

        if (isListLayout) {
          card.className = "bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5 hover:border-emerald-500/40 hover:shadow-md transition-all flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4";
          card.innerHTML = `
            <div class="flex items-center gap-4 min-w-0">
              <div class="relative w-20 h-20 shrink-0 rounded-xl overflow-hidden bg-slate-950">
                <img src="${categoryImg}" data-virtual-type="${escapeHTML(need.type || 'Activo inmobiliario')}" width="160" height="160" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" class="h-full w-full object-cover opacity-90" alt="${escapeHTML(need.title)}" />
                <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded bg-black/60 text-white text-[9px] font-black">★ ${score}</span>
              </div>
              <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold">
                  <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">Demanda</span>
                  <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">${escapeHTML(need.type)}</span>
                  <span class="text-slate-400 font-semibold">${timeText}</span>
                </div>
                <h3 class="text-base font-black text-navy dark:text-white truncate">${escapeHTML(need.title)}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">📍 ${escapeHTML(locationLabel || need.province || 'España')} · C.P. ${escapeHTML(maskPublicPostalCode(need.postalCode))}</p>
              </div>
            </div>
            <div class="flex items-center justify-between md:justify-end gap-4 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100 dark:border-slate-800">
              <div class="text-right">
                <span class="block text-[10px] uppercase font-black text-slate-400">Presupuesto</span>
                <strong class="block text-lg font-black text-emerald-600 dark:text-emerald-400">${formatCurrency(need.budget)}</strong>
              </div>
              <div class="flex items-center gap-2">
                <a href="dossier.php?id=${encodeURIComponent(need.id || need.reference || '')}&type=demand" target="_blank" rel="noopener" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-navy dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">Dossier</a>
                <button onclick="openNeedCollaborationModal('${need.id}')" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-sm transition-all">Colaborar</button>
              </div>
            </div>
          `;
        } else {
          card.className = "bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden hover:border-emerald-500/40 hover:shadow-xl transition-all flex flex-col justify-between";
          card.innerHTML = `
            <div class="relative h-48 w-full overflow-hidden bg-slate-950">
              <img src="${categoryImg}" data-virtual-type="${escapeHTML(need.type || 'Activo inmobiliario')}" width="640" height="400" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" class="h-full w-full object-cover opacity-90 transition-transform duration-300 hover:scale-105" alt="Demanda para ${escapeHTML(need.title)}" />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-black/20 to-black/40"></div>
              <div class="absolute top-3 left-3 flex items-center gap-1.5">
                <span class="px-2.5 py-1 rounded-full bg-emerald-600/90 text-white text-[10px] font-black uppercase tracking-wider backdrop-blur-sm shadow-sm">Demanda Activa</span>
                <span class="px-2 py-0.5 rounded-full bg-black/50 text-white/90 text-[10px] font-bold backdrop-blur-sm">★ ${score}/100</span>
              </div>
              <div class="absolute top-3 right-3 z-20">${favoriteButton('demand', need.id, 'Guardar demanda en favoritos')}</div>
              <div class="absolute bottom-3 left-4 right-4 flex items-end justify-between">
                <span class="text-xs font-bold text-slate-200 drop-shadow">${escapeHTML(need.type)}</span>
                <strong class="text-lg font-black text-emerald-400 drop-shadow">${formatCurrency(need.budget)}</strong>
              </div>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
              <div>
                <div class="flex items-center justify-between gap-2 text-[11px] text-slate-400 font-semibold">
                  <span>${timeText}</span>
                  <span class="px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold">Encargo profesional</span>
                </div>
                <h3 class="text-base font-black text-navy dark:text-white mt-1.5 leading-snug">${escapeHTML(need.title)}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 line-clamp-2">${escapeHTML(need.description || 'Comprador cualificado en búsqueda activa en la zona seleccionada.')}</p>
              </div>
              <div class="flex flex-wrap gap-1.5 text-[10px] font-bold pt-3 border-t border-slate-100 dark:border-slate-800">
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">${escapeHTML(need.operation || 'Venta')}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">📍 ${escapeHTML(locationLabel || need.province || 'España')}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">C.P. ${escapeHTML(maskPublicPostalCode(need.postalCode))}</span>
              </div>
              <div class="flex items-center gap-2 pt-2">
                <a href="dossier.php?id=${encodeURIComponent(need.id || need.reference || '')}&type=demand" target="_blank" rel="noopener" class="flex-1 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white text-navy dark:text-white text-xs font-bold text-center flex items-center justify-center gap-1 transition-all">
                  <span>📄</span><span>Dossier ↗</span>
                </a>
                <button onclick="openNeedCollaborationModal('${need.id}')" class="flex-1 px-3 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-sm transition-all">Colaborar 50/50</button>
              </div>
            </div>
          `;
        }
        grid.appendChild(card);
      });

      container.appendChild(grid);
      appendNeedsPagination(container, list.length);
    }

    function getMarketplaceScoreVisual(score) {
      const value = Math.max(0, Math.min(100, Number(score) || 0));
      if (value < 20) return { value, label: 'Ranking bajo', classes: 'bg-red-600 text-white border-red-700' };
      if (value < 60) return { value, label: 'Ranking bajo medio', classes: 'bg-amber-400 text-navy border-amber-500' };
      if (value < 85) return { value, label: 'Ranking medio alto', classes: 'bg-blue text-white border-blue-dark' };
      return { value, label: 'Ranking alto', classes: 'bg-green text-white border-emerald-700' };
    }


    function buildMarketplaceCarouselDetails(prop) {
      const location = [prop.province || prop.location, prop.municipality, prop.locality].filter(Boolean).join(' · ');
      const condition = typeof prop.rehab === 'boolean' ? (prop.rehab ? 'Reforma declarada' : 'Sin reforma integral declarada') : '';
      const rows = [
        ['Tipo de inmueble', prop.type],
        ['Zona aproximada', location],
        ['Precio', Number(prop.price) ? formatCurrency(prop.price) : ''],
        ['Estado', condition],
        ['Características', formatPropertyFeatures(prop, true)],
        ['Urgencia', prop.urgency],
        ['Colaboración', prop.fee ? `Honorarios: ${prop.fee}` : ''],
        ['Estado documental', prop.docs],
        ['Score de calidad', prop.score ? `${prop.score}/100` : ''],
        ['Descripción', prop.description]
      ].filter(([, value]) => value && String(value).trim());
      return rows.map(([label, value]) => `<div><strong class="text-navy">${escapeHTML(label)}:</strong> ${escapeHTML(value)}</div>`).join('');
    }

    function toggleMarketplaceCarouselDetails(propertyId) {
      if (!requireRegisteredAction('ver la información relacionada con este anuncio')) return;
      const panel = document.getElementById(`marketplace-carousel-details-${propertyId}`);
      const button = document.getElementById(`marketplace-carousel-details-btn-${propertyId}`);
      if (!panel) return;
      const shouldOpen = panel.classList.contains('hidden');
      document.querySelectorAll('.marketplace-carousel-detail-panel').forEach(item => item.classList.add('hidden'));
      document.querySelectorAll('.marketplace-carousel-detail-button').forEach(item => { item.textContent = 'Ver más detalles'; });
      panel.classList.toggle('hidden', !shouldOpen);
      if (button) button.textContent = shouldOpen ? 'Ocultar detalles' : 'Ver más detalles';
    }

    function getMarketplaceMatchLevel(score) {
      if (score >= 75) return 'Alto';
      if (score >= 55) return 'Medio';
      return 'Bajo';
    }

    function buildMarketplacePropertyMatchReport(property) {
      if (!Array.isArray(needs) || !needs.length) {
        return `<h3 class="text-lg font-black text-blue mt-4 mb-2">Coincidencias de la captación</h3><p>Todavía no hay demandas activas suficientes para calcular coincidencias.</p>`;
      }
      const matches = getCompatibleNeedsForProperty(property, 5, false);
      if (!matches.length) {
        return `<h3 class="text-lg font-black text-blue mt-4 mb-2">Coincidencias de la captación</h3><p>No se han encontrado demandas compatibles con esta captación en este momento.</p>${buildMatchNotificationNotice('property')}`;
      }
      return `<h3 class="text-lg font-black text-blue mt-4 mb-2">Demandas compatibles</h3><p>El cruce utiliza los criterios activos de ubicación, tipología, presupuesto, superficie, dormitorios y baños.</p><div class="mt-4 space-y-3">${matches.map(({ need, score }) => {
        const sameMunicipality = normalizeMatchText(property.municipality) && normalizeMatchText(property.municipality) === normalizeMatchText(need.municipality);
        const reason = `${sameMunicipality ? 'Mismo municipio' : 'Misma comunidad autónoma y provincia'}, presupuesto compatible y características principales dentro de los márgenes definidos.`;
        return `<article class="p-4 rounded-2xl border border-slate-200 bg-slate-50"><div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3"><div class="min-w-0"><span class="block text-[10px] font-black text-green">${escapeHTML(need.buyerType || 'Demanda activa')}</span><strong class="block text-sm text-navy mt-1">${escapeHTML(need.title)}</strong><span class="block text-[11px] text-slate-500 mt-1">${escapeHTML([need.province, need.municipality].filter(Boolean).join(' · ') || 'Zona no disponible')} · Hasta ${formatCurrency(need.budget)}</span></div><span class="shrink-0 inline-flex px-3 py-1 rounded-full border text-[10px] font-black ${getCompatibilityBadgeClasses(score)}">${getMarketplaceMatchLevel(score)} · ${score}%</span></div><p class="text-[11px] text-slate-500 mt-3">${escapeHTML(reason)}</p><button type="button" onclick="openMapNeedCard('${need.id}')" class="mt-3 px-3 py-2 rounded-lg bg-navy text-white text-[10px] font-bold">Ver demanda</button></article>`;
      }).join('')}</div>`;
    }

    async function runMarketplacePropertyMatch(propertyId) {
      if (!requireRegisteredAction('consultar coincidencias')) return;
      const property = properties.find(item => item.id === propertyId);
      const modal = document.getElementById('ai-match-modal');
      const loader = document.getElementById('ai-loading');
      const report = document.getElementById('ai-report');
      const reportContent = document.getElementById('ai-report-content');
      if (!property || !modal || !reportContent) return;
      modal.classList.remove('hidden');
      loader?.classList.add('hidden');
      report?.classList.remove('hidden');
      reportContent.innerHTML = buildMarketplacePropertyMatchReport(property);

      const matchNeeds = getCompatibleNeedsForProperty(property, 3, false);
      if (!matchNeeds.length) return;
      const explanationContainer = document.createElement('div');
      explanationContainer.id = 'ai-match-explanations';
      explanationContainer.className = 'mt-4 space-y-2';
      reportContent.appendChild(explanationContainer);

      for (const { need, score } of matchNeeds) {
        const wrapper = document.createElement('div');
        wrapper.className = 'p-3 rounded-xl bg-blue/5 border border-blue/10 text-xs text-slate-600 leading-relaxed';
        wrapper.innerHTML = `<span class="block text-[10px] font-black text-blue mb-1">Explicacion IA para "${escapeHTML(need.title)}"</span><span class="text-slate-400">Cargando...</span>`;
        explanationContainer.appendChild(wrapper);

        fetchMatchExplanation(property, need).then(explanation => {
          if (explanation) {
            wrapper.innerHTML = `<span class="block text-[10px] font-black text-blue mb-1">Explicacion IA</span>${escapeHTML(explanation)}`;
          } else {
            wrapper.remove();
          }
        }).catch(() => wrapper.remove());
      }
    }

    function renderMarketplaceCarousel(list = []) {
      const container = document.getElementById('marketplace-carousel');
      if (!container) return;
      const latest = [...list].sort((a, b) => (Number(b.date) || 0) - (Number(a.date) || 0));
      if (!latest.length) {
        container.innerHTML = '';
        return;
      }
      if (marketplaceCarouselOffset >= latest.length) marketplaceCarouselOffset = 0;
      const page = Array.from({ length: Math.min(MARKETPLACE_CAROUSEL_SIZE, latest.length) }, (_, index) => latest[(marketplaceCarouselOffset + index) % latest.length]);
      container.innerHTML = `
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div>
              <span class="text-[10px] font-black uppercase tracking-[0.18em] text-blue">Últimas publicadas</span>
              <h3 class="text-xl font-black text-navy mt-1">Carrusel de captaciones recientes</h3>
            </div>
            <div class="flex gap-2">
              <button type="button" aria-label="Captaciones anteriores" onclick="moveMarketplaceCarousel(-1)" class="w-10 h-10 rounded-xl border border-slate-200 text-navy font-black hover:border-blue hover:text-blue">‹</button>
              <button type="button" aria-label="Captaciones siguientes" onclick="moveMarketplaceCarousel(1)" class="w-10 h-10 rounded-xl border border-slate-200 text-navy font-black hover:border-blue hover:text-blue">›</button>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
            ${page.map(prop => {
              const image = escapeHTML(resolveMarketplaceImage(prop.image, prop.type));
              return `<article class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 shadow-sm">
                <div class="aspect-[4/3] relative bg-slate-100">
                  <img src="${image}" data-virtual-type="${escapeHTML(prop.type)}" width="640" height="666" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" class="absolute inset-0 w-full h-full object-cover" alt="Imagen de ${escapeHTML(prop.title)}" />
                  <div class="absolute inset-0 bg-gradient-to-t from-navy/80 via-transparent to-transparent"></div>
                  <div class="absolute right-3 top-3 z-20">${favoriteButton('capture', prop.id, 'Guardar captación en favoritos')}</div>
                  <span class="absolute left-3 bottom-3 px-2 py-1 rounded-full bg-white/90 text-blue text-[10px] font-black uppercase">${escapeHTML(prop.type)}</span>
                </div>
                <div class="p-4">
                  <h4 class="text-sm font-black text-navy leading-snug line-clamp-2">${escapeHTML(prop.title)}</h4>
                  <p class="text-[11px] text-slate-500 mt-2">${escapeHTML(prop.province || prop.location || 'España')} · ${formatCurrency(prop.price)}</p>
                  <div class="mt-3 grid grid-cols-1 gap-2">
                    <a href="dossier.php?id=${encodeURIComponent(prop.id || prop.reference || '')}" target="_blank" rel="noopener" class="w-full py-2 rounded-xl border border-blue/30 bg-blue/5 text-blue hover:bg-blue hover:text-white text-xs font-black text-center flex items-center justify-center gap-1 transition-all">
                      <span>📄</span><span>Ver ficha dossier ↗</span>
                    </a>
                    <button type="button" onclick="runMarketplacePropertyMatch('${prop.id}')" class="w-full py-2 rounded-xl bg-blue text-white text-xs font-black hover:bg-blue-dark">Ver coincidencias</button>
                  </div>
                  <div id="marketplace-carousel-details-${prop.id}" class="marketplace-carousel-detail-panel hidden mt-3 pt-3 border-t border-slate-200 text-[11px] text-slate-600 leading-relaxed space-y-1.5">${buildMarketplaceCarouselDetails(prop)}</div>
                </div>
              </article>`;
            }).join('')}
          </div>
        </section>`;
      initAccessibleCarousel(container.querySelector('.grid'));
    }

    function moveMarketplaceCarousel(direction = 1) {
      const list = getMarketplaceVisibleProperties();
      if (!list.length) return;
      marketplaceCarouselOffset = (marketplaceCarouselOffset + direction * MARKETPLACE_CAROUSEL_SIZE + list.length) % list.length;
      renderMarketplaceCarousel(list);
    }

    function renderMarketplace() {
      const grid = document.getElementById('marketplace-grid');
      if (!grid) return;
      grid.innerHTML = '';

      const marketplaceProperties = getMarketplaceVisibleProperties();
      renderMarketplaceDashboard();
      renderMarketplaceCarousel(marketplaceProperties);
      const marketBadge = document.getElementById('marketplace-active-count-badge');
      if (marketBadge) {
        marketBadge.textContent = `${marketplaceProperties.length} ${marketplaceProperties.length === 1 ? 'captación activa' : 'captaciones activas'}`;
      }
      const marketplaceAccordion = document.getElementById('marketplace-accordion-sections');
      if (marketplaceAccordion) marketplaceAccordion.innerHTML = '';
      if (marketplaceMap) renderMarketplaceMapMarkers();

      grid.className = marketplaceLayoutMode === 'list' ? 'grid grid-cols-1 gap-4' : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-6';
      grid.classList.toggle('hidden', marketplaceViewMode === 'map');
      updateMarketplaceViewButtons();

      if (!marketplaceProperties.length) {
        const marketplaceAccordion = document.getElementById('marketplace-accordion-sections');
        if (marketplaceAccordion) marketplaceAccordion.innerHTML = '';
        grid.innerHTML = `<div class="md:col-span-2 lg:col-span-4 p-8 rounded-2xl bg-white border border-slate-200 text-center"><h3 class="text-lg font-black text-navy">No hemos encontrado oportunidades con estos criterios</h3><p class="mt-2 text-sm text-slate-500">Prueba con otra zona o publica lo que busca tu cliente para recibir oportunidades compatibles.</p><div class="mt-5 flex flex-wrap justify-center gap-3"><button type="button" onclick="startIntentFlow('buscar','marketplace-sin-resultados')" class="px-5 py-3 rounded-xl bg-blue text-white text-xs font-black">Publicar búsqueda de mi cliente</button><button type="button" onclick="clearMarketplaceFilters()" class="px-5 py-3 rounded-xl border border-slate-200 text-navy text-xs font-black">Limpiar filtros</button></div></div>`;
        return;
      }

      const visibleMarketplaceProperties = marketplaceProperties.slice(0, marketplaceVisibleLimit);

      visibleMarketplaceProperties.forEach(prop => {
        const scoreVisual = getMarketplaceScoreVisual(prop.score || calculatePublicationOpportunityScore(prop, 'property'));
        const marketplaceImage = escapeHTML(resolveMarketplaceImage(prop.image, prop.type));
        const publishedText = formatRelativeTime(prop.date);
        const authenticated = hasActiveProfessionalSession();
        const accessText = authenticated ? 'Solicitar acceso' : 'Solicitar acceso';
        const accessAction = authenticated ? `openAccessModal('${prop.id}')` : `openProfessionalSubscriptionModal('marketplace-detalles')`;
        const detailsText = authenticated ? 'Ver detalles ▾' : 'Crear cuenta para ver información';
        const detailsAction = authenticated ? `toggleCardDetails('${prop.id}')` : `openProfessionalSubscriptionModal('marketplace-informacion')`;
        const formatOptionalCount = (value, singular, plural) => Number(value) > 0 ? `${Number(value)} ${Number(value) === 1 ? singular : plural}` : 'No indicado';
        const surfaceText = Number(prop.surface) > 0 ? `${Number(prop.surface)} m²` : 'No indicado';
        const headerHtml = `
          <div class="aspect-square relative overflow-hidden bg-slate-100">
            <img src="${marketplaceImage}" data-virtual-type="${escapeHTML(prop.type)}" width="640" height="666" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" class="absolute inset-0 w-full h-full object-cover" alt="Imagen de ${escapeHTML(prop.title)}" />
            <div class="absolute inset-0 bg-gradient-to-t from-navy/85 via-navy/15 to-transparent"></div>
            <div class="absolute top-3 left-3 z-20">${favoriteButton('capture', prop.id, 'Guardar captación en favoritos')}</div>
            <div class="absolute top-3 right-3 z-20 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full border text-[10px] font-black shadow-lg ${scoreVisual.classes}" title="${scoreVisual.label}">★ ${scoreVisual.value}/100</div>
            <span class="absolute left-3 bottom-3 z-10 px-2 py-1 rounded-full bg-white/90 text-blue text-[10px] font-bold uppercase">${escapeHTML(prop.type || 'Activo')}</span>
          </div>
        `;

        const listHeaderHtml = `
          <div class="relative h-32 w-full md:h-auto md:w-44 shrink-0 overflow-hidden bg-slate-100 rounded-2xl md:rounded-r-none">
            <img src="${marketplaceImage}" data-virtual-type="${escapeHTML(prop.type)}" width="640" height="666" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" class="absolute inset-0 w-full h-full object-cover" alt="Imagen de ${escapeHTML(prop.title)}" />
            <div class="absolute inset-0 bg-gradient-to-t from-navy/80 via-transparent to-transparent"></div>
            <div class="absolute top-3 left-3 z-20">${favoriteButton('capture', prop.id, 'Guardar captación en favoritos')}</div>
            <div class="absolute top-3 right-3 z-20 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full border text-[10px] font-black shadow-lg ${scoreVisual.classes}" title="${scoreVisual.label}">★ ${scoreVisual.value}/100</div>
            <span class="absolute left-3 bottom-3 z-10 px-2 py-1 rounded-full bg-white/90 text-blue text-[10px] font-bold uppercase">${escapeHTML(prop.type || 'Activo')}</span>
          </div>
        `;

        const detailsHtml = `
          <div id="details-${prop.id}" class="hidden pt-3 border-t border-slate-100 text-xs text-slate-600 space-y-2">
            <div><strong>Referencia:</strong> ${escapeHTML(prop.reference)}</div>
            ${renderCatastroPropertyBlock(prop)}
            <div><strong>Código Postal:</strong> ${escapeHTML(maskPublicPostalCode(prop.postalCode))}</div>
            <div><strong>Características:</strong> ${formatPropertyFeatures(prop)}</div>
            ${renderPropertyNearbyInterests(prop)}
            <div><strong>Comentarios técnicos:</strong> ${prop.description}</div>
            <div><strong>Condiciones de Financiación:</strong> ${prop.fundingConditions || "Sujeto a verificación del perfil de riesgo."}</div>
            <div><strong>Nivel de Documentación:</strong> ${prop.docs || "Completo"}</div>
            <div><strong>Urgencia:</strong> ${prop.urgency || "Media"}</div>
            ${renderLinkedNeedsForProperty(prop)}
          </div>
        `;

        const metricsHtml = marketplaceLayoutMode === 'list' ? `
          <div class="grid grid-cols-2 md:grid-cols-4 gap-2 border border-slate-150 rounded-xl bg-slate-50/50 p-2.5 text-left text-xs">
            <div class="px-2 py-1"><strong class="metric-value text-[11px]">${new Intl.NumberFormat('de-DE').format(prop.price)} €</strong><span class="metric-label">Precio</span></div>
            <div class="px-2 py-1"><strong class="metric-value text-[11px]">${escapeHTML(prop.fee)}</strong><span class="metric-label">Honorarios</span></div>
            <div class="px-2 py-1"><strong class="metric-value text-[11px] truncate">${escapeHTML(prop.location)}</strong><span class="metric-label">Zona</span></div>
            <div class="px-2 py-1"><strong class="metric-value text-[11px] truncate">${escapeHTML(maskPublicPostalCode(prop.postalCode))}</strong><span class="metric-label">C.P.</span></div>
          </div>
        ` : `
          <div class="grid grid-cols-4 divide-x divide-slate-200 border border-slate-150 rounded-xl bg-slate-50/50 p-2.5 text-center text-xs">
            <div><strong class="metric-value text-[11px]">${new Intl.NumberFormat('de-DE').format(prop.price)} €</strong><span class="metric-label">Precio</span></div>
            <div><strong class="metric-value text-[11px]">${escapeHTML(prop.fee)}</strong><span class="metric-label">Honorarios</span></div>
            <div><strong class="metric-value text-[11px] truncate">${escapeHTML(prop.location)}</strong><span class="metric-label">Zona</span></div>
            <div><strong class="metric-value text-[11px] truncate">${escapeHTML(maskPublicPostalCode(prop.postalCode))}</strong><span class="metric-label">C.P.</span></div>
          </div>
        `;

        const card = document.createElement('div');
        card.id = `market-card-${prop.id}`;

        if (marketplaceLayoutMode === 'list') {
          card.className = "bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-all";
          card.innerHTML = `
            <div class="flex flex-col md:flex-row">
              ${listHeaderHtml}
              <div class="flex-1 p-4 space-y-3">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                  <div>
                    <div class="text-[10px] font-black uppercase tracking-wider text-blue">Ref. ${escapeHTML(prop.reference)}</div>
                    <h3 class="text-base font-extrabold text-navy leading-snug mt-1">${escapeHTML(prop.title)}</h3>
                    <p class="text-[10px] text-slate-400 mt-1 font-semibold">${publishedText}</p>
                    <p class="text-xs text-slate-500 mt-2 line-clamp-2">${prop.description}</p>
                  </div>
                  ${renderReputationBadge(prop)}
                </div>
                <div class="flex flex-wrap gap-2 text-[10px] font-bold text-slate-600"><span class="px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200">${formatOptionalCount(prop.bedrooms, 'habitación', 'habitaciones')}</span><span class="px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200">${formatOptionalCount(prop.bathrooms, 'baño', 'baños')}</span><span class="px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200">${surfaceText}</span></div>
                ${metricsHtml}
                ${detailsHtml}
                <div class="flex flex-col sm:flex-row gap-2 justify-end">
                  <a href="dossier.php?id=${encodeURIComponent(prop.id || prop.reference || '')}" target="_blank" rel="noopener" class="px-4 py-2 text-center text-xs font-bold text-blue hover:text-white border border-blue/30 hover:border-blue bg-blue/5 hover:bg-blue rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <span>📄</span><span>Ver ficha dossier ↗</span>
                  </a>
                  <button type="button" onclick="runMarketplacePropertyMatch('${prop.id}')" class="px-4 py-2 rounded-xl bg-navy text-white text-xs font-black">Ver coincidencias</button>
                  <button onclick="${accessAction}" id="btn-market-${prop.id}" class="px-4 py-2.5 bg-blue hover:bg-blue-dark text-white font-extrabold text-xs rounded-xl shadow-md">${accessText}</button>
                </div>
              </div>
            </div>
          `;
        } else {
          card.className = "bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-all";
          card.innerHTML = `
            <div>
              ${headerHtml}
              <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-[10px] font-black uppercase tracking-wider text-blue">Ref. ${escapeHTML(prop.reference)}</div>
                    <h3 class="text-sm font-extrabold text-navy leading-snug mt-2 line-clamp-2">${escapeHTML(prop.title)}</h3>
                    <p class="text-[10px] text-slate-400 mt-1 font-semibold">${publishedText}</p>
                  </div>
                  ${renderReputationBadge(prop)}
                </div>
                <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">${prop.description}</p>
                <div class="flex flex-wrap gap-2 text-[10px] font-bold text-slate-600"><span class="px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200">${formatOptionalCount(prop.bedrooms, 'habitación', 'habitaciones')}</span><span class="px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200">${formatOptionalCount(prop.bathrooms, 'baño', 'baños')}</span><span class="px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200">${surfaceText}</span></div>
                ${metricsHtml}
                ${detailsHtml}
              </div>
            </div>
            <div class="p-4 pt-0 space-y-2">
              <a href="dossier.php?id=${encodeURIComponent(prop.id || prop.reference || '')}" target="_blank" rel="noopener" class="w-full py-2 text-center text-xs font-bold text-blue hover:text-white border border-blue/30 hover:border-blue bg-blue/5 hover:bg-blue rounded-xl transition-all flex items-center justify-center gap-1.5">
                <span>📄</span><span>Ver ficha dossier ↗</span>
              </a>
              <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="runMarketplacePropertyMatch('${prop.id}')" class="w-full py-2 rounded-xl bg-navy text-white text-xs font-black truncate">Coincidencias</button>
                <button onclick="${accessAction}" id="btn-market-${prop.id}" class="w-full py-2 bg-blue hover:bg-blue-dark text-white font-extrabold text-xs rounded-xl shadow-md truncate">${accessText}</button>
              </div>
            </div>
          `;
        }
        grid.appendChild(card);
      });

      appendLoadMoreControl(grid, visibleMarketplaceProperties.length, marketplaceProperties.length, 'loadMoreMarketplace', 'captaciones');
    }

    function sortMarketplace() {
      refreshMarketplaceView();
    }

    // ==========================================
    // 7. GESTIÓN DEL ARCHIVO ADJUNTO (Ofrecer Captación)
    // ==========================================
    function setOfferImageMode(mode) {
      const uploadPanel = document.getElementById('offer-image-upload-panel');
      const input = document.getElementById('offer-file-input');
      const statusText = document.getElementById('file-upload-status');
      const previewZone = document.getElementById('file-preview-zone');
      const fileNameSpan = document.getElementById('file-name');
      const fileIconSpan = document.getElementById('file-icon');
      const useDefault = mode === 'default';

      if (uploadPanel) uploadPanel.classList.toggle('opacity-60', useDefault);
      if (uploadPanel) uploadPanel.classList.toggle('cursor-not-allowed', useDefault);
      if (input && useDefault) input.value = '';
      if (useDefault) uploadedFileBase64 = null;

      if (statusText) {
        statusText.classList.remove('hidden');
        statusText.textContent = useDefault
          ? 'Se utilizará la imagen predeterminada optimizada para esta captación.'
          : 'Selecciona JPG, PNG, WEBP o PDF. Las imágenes se convierten a formato web ligero automáticamente.';
      }
      if (previewZone) previewZone.classList.toggle('hidden', !useDefault);
      if (fileIconSpan && useDefault) fileIconSpan.textContent = 'Portada';
      if (fileNameSpan && useDefault) fileNameSpan.textContent = 'Imagen predeterminada Compra Captación';
      refreshOfferDefaultImagePreview();
    }

    function refreshOfferDefaultImagePreview() {
      const wrapper = document.getElementById('offer-default-image-preview');
      const image = document.getElementById('offer-default-image-preview-img');
      const mode = document.querySelector('input[name="offer-image-mode"]:checked')?.value || 'upload';
      const type = document.getElementById('offer-type')?.value || 'Activo inmobiliario';
      if (!wrapper || !image) return;
      const useDefault = mode === 'default';
      wrapper.classList.toggle('hidden', !useDefault);
      if (!useDefault) return;
      image.onerror = () => {
        image.onerror = null;
        image.src = getVirtualMarketplaceImage(type);
      };
      image.src = resolveMarketplaceImage('', type);
      image.alt = `Imagen predeterminada para ${type}`;
    }

    function loadImageFromFile(file) {
      return new Promise((resolve, reject) => {
        const image = new Image();
        const objectUrl = URL.createObjectURL(file);
        image.onload = () => {
          URL.revokeObjectURL(objectUrl);
          resolve(image);
        };
        image.onerror = () => {
          URL.revokeObjectURL(objectUrl);
          reject(new Error('No se pudo leer la imagen seleccionada.'));
        };
        image.src = objectUrl;
      });
    }

    async function optimizeMarketplaceImage(file) {
      const image = await loadImageFromFile(file);
      const width = image.naturalWidth || image.width;
      const height = image.naturalHeight || image.height;
      const cropSize = Math.min(width, height);
      if (!cropSize) throw new Error('La imagen seleccionada no tiene dimensiones válidas.');

      const outputSize = Math.min(MAX_MARKETPLACE_IMAGE_SIZE, cropSize);
      const canvas = document.createElement('canvas');
      canvas.width = outputSize;
      canvas.height = outputSize;
      const context = canvas.getContext('2d', { alpha: false });
      if (!context) throw new Error('No se pudo optimizar la imagen en este navegador.');

      const sourceX = (width - cropSize) / 2;
      const sourceY = (height - cropSize) / 2;
      context.drawImage(image, sourceX, sourceY, cropSize, cropSize, 0, 0, outputSize, outputSize);

      let dataUrl = canvas.toDataURL('image/webp', MARKETPLACE_IMAGE_QUALITY);
      if (!dataUrl.startsWith('data:image/webp')) {
        dataUrl = canvas.toDataURL('image/jpeg', MARKETPLACE_IMAGE_QUALITY);
      }
      return dataUrl;
    }

    async function handleFileSelection(e) {
      const file = e.target.files[0];
      const statusText = document.getElementById('file-upload-status');
      const previewZone = document.getElementById('file-preview-zone');
      const fileNameSpan = document.getElementById('file-name');
      const fileIconSpan = document.getElementById('file-icon');
      const uploadRadio = document.getElementById('offer-image-mode-upload');

      if (!file) return;
      if (uploadRadio) uploadRadio.checked = true;
      setOfferImageMode('upload');

      if (fileNameSpan) fileNameSpan.innerText = file.name;
      if (previewZone) previewZone.classList.remove('hidden');
      if (statusText) statusText.classList.remove('hidden');

      if (file.type.startsWith('image/')) {
        if (fileIconSpan) fileIconSpan.innerText = 'Imagen';
        if (statusText) statusText.textContent = 'Optimizando la imagen para web…';
        try {
          uploadedFileBase64 = await optimizeMarketplaceImage(file);
          if (statusText) statusText.textContent = 'Imagen optimizada en formato web y lista para publicar.';
          showToast('Imagen optimizada para web correctamente.', 'success');
        } catch (error) {
          uploadedFileBase64 = null;
          if (statusText) statusText.textContent = 'No se pudo optimizar la imagen. Se utilizará la imagen predeterminada.';
          showToast(error.message + ' Se utilizará la imagen predeterminada.', 'info');
        }
      } else {
        if (fileIconSpan) fileIconSpan.innerText = 'PDF';
        uploadedFileBase64 = null;
        if (statusText) statusText.textContent = 'Documento adjuntado. Como no es una fotografía, se utilizará la imagen predeterminada en Marketplace.';
        showToast('Documento PDF adjuntado. Marketplace utilizará la imagen predeterminada.', 'success');
      }
    }

    // ==========================================
    // 8. CONTROL DE PREVISUALIZACIÓN ANTES DE PUBLICAR
    // ==========================================
    function authorizedListingImportLabels() {
      return { title:'Título', propertyType:'Tipo de inmueble', operation:'Operación', price:'Precio', surface:'Superficie', bedrooms:'Habitaciones', bathrooms:'Baños', description:'Descripción', locality:'Zona o localidad', externalReference:'Referencia externa' };
    }

    function renderAuthorizedListingImportPreview(data) {
      const result=document.getElementById('offer-source-import-result'); if(!result)return;
      const labels=authorizedListingImportLabels();
      const fields=Object.entries(labels).filter(([key])=>String(data?.fields?.[key] ?? '').trim()!=='');
      const imageNotice=data?.imageDetected?'<p class="mt-3 text-xs text-slate-500">Se ha detectado una imagen, pero no se copiará. Carga tu logotipo o fotografía autorizada directamente.</p>':'';
      result.className='mt-4 rounded-xl border border-green/25 bg-white p-4';
      result.innerHTML=`<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3"><div><strong class="block text-sm text-green">Datos encontrados: ${fields.length}</strong><p class="mt-1 text-xs text-slate-500">Revisa la vista previa antes de completar el formulario.</p></div><span class="rounded-full bg-green-light px-3 py-1 text-xs font-semibold text-green">Fuente del anuncio</span></div><div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">${fields.map(([key,label])=>`<div class="rounded-lg border border-slate-200 bg-slate-50 p-3"><span class="block text-[11px] font-semibold text-slate-500">${escapeHTML(label)}</span><strong class="mt-1 block text-sm text-navy line-clamp-3">${escapeHTML(String(data.fields[key]))}</strong></div>`).join('')}</div>${imageNotice}<div class="mt-4 flex flex-wrap gap-2"><button type="button" onclick="applyAuthorizedListingImport()" class="min-h-[44px] rounded-xl bg-green px-4 py-2.5 text-sm font-semibold text-white">Usar estos datos</button><button type="button" onclick="clearAuthorizedListingImport()" class="min-h-[44px] rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-600">Cancelar</button></div>`;
    }

    function showAssistedListingImport(message='Copia los datos públicos de tu anuncio para continuar.') {
      const assisted=document.getElementById('offer-source-assisted');
      const result=document.getElementById('offer-source-import-result');
      if(assisted) assisted.classList.remove('hidden');
      if(result){result.className='mt-4 rounded-xl border border-amber/30 bg-amber-light p-4 text-sm text-slate-700';result.innerHTML=`<strong class="block text-amber">Pega el texto del anuncio</strong><p class="mt-1">${escapeHTML(message)}</p><p class="mt-2 text-xs">La URL quedará guardada como referencia. No se publicará nada automáticamente.</p>`;}
      document.getElementById('offer-source-text')?.focus();
    }

    async function previewAuthorizedListingUrl(sourceText='') {
      if (!requireRegisteredAction('importar datos de una ficha autorizada')) return;
      const url=cleanText(document.getElementById('offer-source-url')?.value||'');
      const rights=!!document.getElementById('offer-source-rights')?.checked;
      const result=document.getElementById('offer-source-import-result'); const button=document.getElementById('offer-source-import-button');
      if(!url && !sourceText){showToast('Pega la URL de la ficha que quieres analizar.','info');return;}
      if(!rights){showToast('Confirma que tienes autorización para reutilizar la información.','info');return;}
      pendingAuthorizedListingImport=null;
      if(result){result.className='mt-4 rounded-xl border border-blue/20 bg-white dark:bg-slate-800 p-4 text-sm text-slate-600 dark:text-slate-300';result.textContent='Estamos leyendo la ficha autorizada…';}
      const textButton=document.getElementById('offer-source-text-button');
      if(button){button.disabled=true;button.textContent='Analizando…';}
      if(textButton) textButton.disabled=true;
      
      const apiEndpoints = [
        (typeof CAPTACION_BASE_PATH === 'string' && CAPTACION_BASE_PATH !== '/' ? CAPTACION_BASE_PATH.replace(/\/+$/, '') : '') + '/api/import_preview.php',
        '/api/import_preview.php',
        typeof CAPTACION_API !== 'undefined' ? CAPTACION_API?.endpoints?.listingImportPreview : null
      ].filter(Boolean);

      let data = null;
      let lastError = null;

      for (const endpoint of apiEndpoints) {
        try {
          const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': (typeof CAPTACION_API !== 'undefined' ? CAPTACION_API?.nonce : '') || ''
            },
            body: JSON.stringify({ source_url: url, source_text: sourceText, rights_confirmed: true })
          });
          const json = await response.json();
          if (response.ok && json.ok) {
            data = json;
            break;
          }
        } catch(e) {
          lastError = e;
        }
      }

      if(button){button.disabled=false;button.textContent='Continuar';}
      if(textButton) textButton.disabled=false;

      if(data) {
        if(data.assistedRequired){showAssistedListingImport(data.message);return;}
        pendingAuthorizedListingImport=data;
        renderAuthorizedListingImportPreview(data);
        showToast('Ficha leída con éxito. Revisa la vista previa antes de incorporar los datos.', 'success');
      } else {
        showAssistedListingImport(lastError?.message || 'No se pudo leer la URL de forma automática.');
        showToast('Pega el texto del anuncio a continuación para continuar con la importación asistida.', 'info');
      }
    }

    function analyzeAssistedListingText() {
      const sourceText=String(document.getElementById('offer-source-text')?.value||'').trim();
      if(sourceText.length<40){showToast('Pega al menos el título y las características principales del anuncio.','info');return;}
      previewAuthorizedListingUrl(sourceText);
    }

    function applyAuthorizedListingImport() {
      const fields=pendingAuthorizedListingImport?.fields||{}; if(!Object.keys(fields).length)return;
      const setValue=(id,value)=>{const el=document.getElementById(id);if(!el||value===undefined||value===null||String(value).trim()==='')return;el.value=String(value);el.dispatchEvent(new Event('change',{bubbles:true}));};
      setValue('offer-title',fields.title); setValue('offer-price',fields.price); setValue('offer-surface',fields.surface); setValue('offer-bedrooms',fields.bedrooms); setValue('offer-bathrooms',fields.bathrooms); setValue('offer-description',fields.description); setValue('offer-locality-input',fields.locality);
      if(fields.propertyType){const normalized=normalizePropertyType(fields.propertyType);const select=document.getElementById('offer-type');if(select&&[...select.options].some(option=>option.value===normalized)){setValue('offer-type',normalized);refreshOfferDefaultImagePreview();updatePropertyFormDynamics('offer');}}
      if(fields.operation){const operation=String(fields.operation);const select=document.getElementById('offer-operation');if(select&&[...select.options].some(option=>option.value===operation))setValue('offer-operation',operation);}
      const result=document.getElementById('offer-source-import-result');if(result)result.innerHTML='<strong class="block text-sm text-green">Datos incorporados al formulario</strong><p class="mt-1 text-xs text-slate-500">Revisa especialmente la ubicación, la descripción y las condiciones antes de publicar.</p>';
      showToast('Datos incorporados. Revísalos antes de publicar.','success');
    }

    function clearAuthorizedListingImport() { pendingAuthorizedListingImport=null; const result=document.getElementById('offer-source-import-result');if(result){result.innerHTML='';result.className='hidden mt-4';} }

    function handleNewOffer(e) {
      e.preventDefault();
      if (!requireRegisteredAction('publicar una captacion')) return;

      const type = normalizePropertyType(document.getElementById('offer-type').value);
const operation = cleanText(document.getElementById('offer-operation').value);
      const territory = resolveTerritorySelection(
        document.getElementById('offer-ccaa-sel').value,
        document.getElementById('offer-province-sel').value,
        document.getElementById('offer-municipality-sel').value
      );
      if (!territory.valid) {
        showToast(territory.message, 'info');
        return;
      }
      const ccaa = territory.autonomous_community_name;
      const province = territory.province_name;
      const municipality = territory.municipality_name;
      const locality = document.getElementById('offer-locality-input').value.trim();
      const postalCode = cleanText(document.getElementById('offer-postal-code').value);
      const bedrooms = Number(document.getElementById('offer-bedrooms').value) || 0;
      const bathrooms = Number(document.getElementById('offer-bathrooms').value) || 0;
      const surface = Number(document.getElementById('offer-surface').value) || 0;
      const isResidentialOffer = RESIDENTIAL_PROPERTY_TYPES.includes(type);
      const isHouseOffer = type === 'Casa / chalet';
      const elevator = isResidentialOffer ? cleanText(document.getElementById('offer-elevator')?.value || 'No indicado') : 'No indicado';
      const garage = isResidentialOffer ? cleanText(document.getElementById('offer-garage')?.value || 'No indicado') : 'No indicado';
      const estateType = isHouseOffer ? cleanText(document.getElementById('offer-estate-type')?.value || 'No indicado') : 'No indicado';
      const estateSurface = isHouseOffer ? (Number(document.getElementById('offer-estate-surface')?.value) || 0) : 0;
      const price = parseFloat(document.getElementById('offer-price').value);
      const fee = cleanText(document.getElementById('offer-fee').value);
      const propertyCondition = cleanText(document.getElementById('offer-condition').value);
      const mandateType = cleanText(document.getElementById('offer-mandate').value);
      const rehab = propertyCondition === 'Reforma integral';
      const exclusive = ['Sí, con exclusividad', 'Encargo de agente único', 'Exclusiva compartida'].includes(mandateType);
      const urgency = cleanText(document.getElementById('offer-urgency').value);
      const docs = cleanText(document.getElementById('offer-docs').value);
      const sourceUrl = cleanText(document.getElementById('offer-source-url')?.value || '');
      const title = cleanText(document.getElementById('offer-title').value);
      const description = cleanText(document.getElementById('offer-description').value);
    const spamRegex = /([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)|(https?:\/\/[^\s]+)|(www\.[^\s]+)/i;
    if (spamRegex.test(description)) {
      showToast('No está permitido incluir enlaces o correos electrónicos en la descripción.', 'error');
      return;
    }
      const cadastralReferenceRaw = cleanText(document.getElementById('offer-cadastral-reference')?.value || '');
      const cadastralReference = normalizeCadastralReference(cadastralReferenceRaw);
      if (title.length < 8) { showToast('El título de la captación debe tener al menos 8 caracteres.', 'info'); return; }
      if (description.length < 30) { showToast('La descripción debe tener al menos 30 caracteres.', 'info'); return; }
      if (cadastralReferenceRaw && !isCadastralReferenceValid(cadastralReference)) {
        showToast('La referencia catastral debe tener 20 caracteres alfanumericos.', 'info');
        return;
      }
      if (!surface || !price || !fee || !propertyCondition || !mandateType || !urgency || !docs) {
        showToast('Completa superficie, precio, comisión, condición, encargo, urgencia y documentación.', 'info');
        return;
      }

      const locationLabel = locality ? `${municipality} (${locality})` : municipality;
      const selectedImageMode = document.querySelector('input[name="offer-image-mode"]:checked')?.value || 'upload';
      const hasCustomImage = selectedImageMode === 'upload' && Boolean(uploadedFileBase64);

      // Creamos la captación temporal para la vista previa, incluyendo la foto adjunta
      tempPropertyToPublish = {
        id: 'user-prop-' + Date.now(),
        title: title || "Sin título definido",
        type: cleanText(type),
        property_type: cleanText(type),
operation: operation,
        ccaa: cleanText(ccaa),
        province: cleanText(province),
        municipality: cleanText(municipality),
        autonomous_community_id: territory.autonomous_community_id,
        community_code: territory.autonomous_community_id,
        autonomous_community_name: territory.autonomous_community_name,
        province_id: territory.province_id,
        province_code: territory.province_id,
        province_name: territory.province_name,
        municipality_id: territory.municipality_id,
        municipality_ine_code: territory.municipality_ine_code,
        municipality_code: territory.municipality_ine_code || territory.municipality_id,
        municipality_name: territory.municipality_name,
        locality: cleanText(locality),
        postalCode,
        bedrooms,
        rooms: bedrooms,
        bathrooms,
        surface,
        total_area_m2: surface,
        elevator,
        has_elevator: elevator,
        garage,
        has_garage: garage,
        estate_type: estateType,
        estate_surface_m2: estateSurface,
        location: cleanText(province),
        neighborhood: `${cleanText(province)} · ${cleanText(locationLabel)}`,
        date: Date.now(),
        price,
        indicative_price: price,
        fee,
        offered_commission: fee,
        rehab,
        exclusive,
        property_condition: propertyCondition,
        mandate_type: mandateType,
        urgency,
        sale_urgency: urgency,
        docs,
        documentation_level: docs,
        source_url: sourceUrl,
        source_import_method: pendingAuthorizedListingImport?.importMethod || (pendingAuthorizedListingImport ? 'authorized_metadata_preview' : (sourceUrl ? 'reference_only' : '')),
        source_checked_at: pendingAuthorizedListingImport ? Date.now() : 0,
        external_reference: cleanText(pendingAuthorizedListingImport?.fields?.externalReference || ''),
        cadastral_reference: cadastralReference,
        cadastral_reference_masked: cadastralReference ? maskCadastralReference(cadastralReference) : '',
        cadastral_status: cadastralReference ? 'format_ok' : 'not_provided',
        cadastral_source: cadastralReference ? 'manual' : '',
        cadastral_last_checked_at: cadastralReference ? Date.now() : 0,
        catastro_home_url: CATASTRO_HOME_URL,
        catastro_search_url: CATASTRO_HOME_URL,
        catastro_map_url: CATASTRO_HOME_URL,
        score: calculatePublicationOpportunityScore({
          title,
          description,
          price,
          postalCode,
          province,
          municipality,
          surface,
          bedrooms,
          bathrooms,
          docs,
          exclusive,
          urgency
        }, 'property'),
        description,
        badgeColor: "blue",
        badgeText: exclusive ? "Exclusiva compartida" : "Abierta a colaboración",
        fundingConditions: "Sujeto a viabilidad y estudio de solvencia del perfil inversor.",
        image: hasCustomImage ? uploadedFileBase64 : '', // Solo guardamos la imagen personalizada optimizada; la predeterminada se reutiliza sin duplicar memoria.
        imageIsDefault: !hasCustomImage,
        agency: getDemoSession()?.agency || 'Perfil profesional',
        userEmail: getDemoSession()?.email || CAPTACION_MAILCHIMP?.currentUser?.email || ''
      };

      // Cabecera dinámica que muestra la foto previa si se ha subido
      const previewImage = escapeHTML(resolveMarketplaceImage(tempPropertyToPublish.image, tempPropertyToPublish.type));
      const headerHtml = `
        <div class="aspect-square relative overflow-hidden flex flex-col justify-end p-6 bg-slate-100">
          <img src="${previewImage}" data-virtual-type="${escapeHTML(tempPropertyToPublish.type)}" width="640" height="666" loading="lazy" decoding="async" onerror="window.handleMarketplaceImageError(this);" class="absolute inset-0 w-full h-full object-cover" alt="Imagen de portada" />
          <div class="absolute inset-0 bg-gradient-to-t from-navy/90 via-navy/30 to-transparent"></div>
          <h3 class="text-2xl font-extrabold text-white leading-tight relative z-10">${tempPropertyToPublish.title}</h3>
        </div>
      `;
      const previewFeatureChips = [
        `${bedrooms} hab.`,
        `${bathrooms} baños`,
        `${surface || 'N/D'} m²`,
        elevator && elevator !== 'No indicado' ? `Ascensor: ${elevator}` : '',
        garage && garage !== 'No indicado' ? `Garaje: ${garage}` : '',
        estateType && estateType !== 'No indicado' ? `Finca: ${estateType}` : '',
        estateSurface ? `${estateSurface} m² finca` : ''
      ].filter(Boolean).map(item => `<span class="px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200">${escapeHTML(item)}</span>`).join('');

      // Generar el bloque visual del modal fiel a la foto
      const previewArea = document.getElementById('card-preview-area');
      if (previewArea) {
        previewArea.innerHTML = `
          <div class="bg-white rounded-[24px] border border-slate-200/80 shadow-lg overflow-hidden">
            ${headerHtml}
            <div class="p-6 space-y-4">
              <div class="flex items-center justify-between">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-light text-green">
                  Verificada
                </span>
                <div class="w-10 h-10 rounded-xl bg-navy text-white flex items-center justify-center font-extrabold text-sm shadow-md">
                  ${tempPropertyToPublish.score}
                </div>
              </div>

              <p class="text-xs text-slate-500 leading-relaxed">
                Zona aproximada visible. Datos sensibles de contacto disponibles mediante solicitud válida.
              </p>

              <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-blue-light text-blue">${tempPropertyToPublish.badgeText}</span>
                <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-light text-amber">${urgency === 'Alta' ? 'Alta motivación' : 'Plazo ordinario'}</span>
              </div>
              <div class="flex flex-wrap gap-2 text-[10px] font-bold text-slate-600">${previewFeatureChips}</div>

              <!-- Tres columnas del hito de referencia -->
              <div class="grid grid-cols-4 divide-x divide-slate-200 border border-slate-150 rounded-xl bg-slate-50/50 p-3 text-center">
                <div>
                  <strong class="block text-sm text-navy font-black">${new Intl.NumberFormat('de-DE').format(price)} €</strong>
                  <span class="metric-label">Precio orientativo</span>
                </div>
                <div>
                  <strong class="block text-sm text-navy font-black">${fee}</strong>
                  <span class="metric-label">Honorarios</span>
                </div>
                <div>
                  <strong class="block text-sm text-navy font-black truncate">${province}</strong>
                  <span class="metric-label">Zona</span>
                </div>
                <div>
                  <strong class="block text-sm text-navy font-black truncate">${escapeHTML(postalCode || 'N/D')}</strong>
                  <span class="metric-label">C.P.</span>
                </div>
              </div>

              <button type="button" class="w-full py-3 bg-blue text-white font-extrabold text-xs rounded-xl shadow-md cursor-not-allowed opacity-85" disabled>
                Solicitar acceso
              </button>
            </div>
          </div>
        `;
      }

      // Desplegar el modal de Previsualización
      const modal = document.getElementById('preview-modal');
      if (modal) {
        // El formulario puede dispararse dentro de contenedores que vuelven a
        // aplicar la clase hidden. La visibilidad explícita evita que la
        // revisión quede generada pero no se pueda aprobar.
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.style.display = 'flex';
      }
    }

    function closePreviewModal() {
      const modal = document.getElementById('preview-modal');
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = '';
      }
    }

    // --- MEJORA UX: CONFIRMACIÓN Y TRANSICIÓN "ENVIADO" DEL MODAL ---
    async function confirmAndPublish() {
      if (!tempPropertyToPublish) return;
      const diagnosisId = sessionStorage.getItem('captation_last_diagnosis_id');
      if (diagnosisId) {
        const confirmed = window.confirm('Antes de publicar, confirma:\n\n1. He revisado que no hay dirección exacta ni datos personales innecesarios.\n2. Tengo autorización para reutilizar la información del anuncio.\n3. La ficha pública refleja correctamente mi encargo y condiciones.\n\n¿Quieres convertir este diagnóstico en una captación pública?');
        if (!confirmed) return;
        try {
          const diagnosisResponse = await fetch(`/api/diagnoses.php?action=get&id=${encodeURIComponent(diagnosisId)}`, { credentials: 'same-origin' });
          const diagnosisData = await diagnosisResponse.json().catch(() => ({}));
          const diagnosis = diagnosisData.diagnosis;
          if (!diagnosisData.ok || !diagnosis) throw new Error('No se pudo verificar el diagnóstico pendiente.');
          const updateResponse = await fetch('/api/diagnoses.php?action=update', {
            method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: Number(diagnosisId), version: Number(diagnosis.version || 1), status: 'ready_for_publication', payload: { ...(diagnosis.payload || {}), publication_candidate: { title: tempPropertyToPublish.title, type: tempPropertyToPublish.type, price: tempPropertyToPublish.price, province: tempPropertyToPublish.province, municipality: tempPropertyToPublish.municipality }, privacy_confirmed_at: new Date().toISOString() } })
          });
          const updateData = await updateResponse.json().catch(() => ({}));
          if (!updateResponse.ok || !updateData.ok) throw new Error(updateData.error || 'No se pudo cerrar la revisión del diagnóstico.');
          sessionStorage.removeItem('captation_last_diagnosis_id');
          sessionStorage.removeItem('captation_last_diagnosis_version');
        } catch (error) {
          showToast(error?.message || 'No se ha podido verificar el diagnóstico. No se ha publicado.', 'error');
          return;
        }
      }
      const publishBtn = document.getElementById('preview-publish-btn');
      const backBtn = document.getElementById('preview-back-btn');
      if (publishBtn) { publishBtn.disabled = true; publishBtn.textContent = 'Guardando…'; }
      if (backBtn) backBtn.disabled = true;
      const publishedProperty = tempPropertyToPublish;
      const persisted = await persistWpRecord('property', publishedProperty, { recordKey: publishedProperty.id, title: publishedProperty.title, status: 'publicada', privacyScope: 'global_public' });
      if (canUseWordPressRecords() && !persisted) {
        if (publishBtn) { publishBtn.disabled = false; publishBtn.textContent = 'Aprobar y publicar'; }
        if (backBtn) backBtn.disabled = false;
        showToast('No se pudo guardar la captación en WordPress. No se ha publicado.', 'error');
        return;
      }
      properties.unshift(publishedProperty);
      if (!CAPTACION_PRODUCTION_MODE) localStorage.setItem('captacion_properties_v3', JSON.stringify(properties));
      syncMailchimpSession('ofrecer-captacion', 'ofrecer-captacion');
      syncAlertsForProperty(publishedProperty);
      renderMarketplace();
      renderDashboard();
      renderHome();
      const form = document.querySelector('#page-ofrecer-captacion form');
      if (form) form.reset();
      resetProgressiveForm('offer-publication-form');
      updatePropertyFormDynamics('offer');
      const cadastralInput = document.getElementById('offer-cadastral-reference');
      if (cadastralInput) cadastralInput.value = '';
      updateOfferCatastroPreview();
      const statusText = document.getElementById('file-upload-status');
      const previewZone = document.getElementById('file-preview-zone');
      if (statusText) statusText.classList.remove('hidden');
      if (previewZone) previewZone.classList.add('hidden');
      uploadedFileBase64 = null;
      const uploadRadio = document.getElementById('offer-image-mode-upload');
      if (uploadRadio) uploadRadio.checked = true;
      setOfferImageMode('upload');
      closePreviewModal();
      if (publishBtn) { publishBtn.disabled = false; publishBtn.textContent = 'Aprobar y publicar'; }
      if (backBtn) backBtn.disabled = false;
      showToast('Tu captación se ha publicado correctamente.', 'success');
      trackConversionEvent('offer_published');
      showPublicationConfirmation('property', publishedProperty.id);
    }
    // ==========================================
    // 9. CONTROL DE EVENTOS Y ACCIONES COMUNES
    // ==========================================
    function scrollToCoverageMap(event) {
      event?.preventDefault?.();
      const section = document.getElementById('mapa-cobertura');
      if (!section) {
        console.warn('[Compra Captación] No se encontró la sección #mapa-cobertura.');
        return;
      }
      section.scrollIntoView({behavior:'smooth',block:'start'});
      setTimeout(() => homeMap?.invalidateSize?.(), 350);
    }

    function initContactFormExperience() {
      const form = document.querySelector('#page-contacto form[onsubmit*="handleContactSubmit"]');
      if (!form) return;
      const loggedIn = Boolean(CAPTACION_MAILCHIMP?.loggedIn && CAPTACION_MAILCHIMP?.currentUser);
      const user = CAPTACION_MAILCHIMP?.currentUser || {};
      const hideField = id => document.getElementById(id)?.closest('div')?.classList.add('hidden');
      hideField('contact-phone');
      hideField('contact-preference');
      const name = document.getElementById('contact-name');
      const email = document.getElementById('contact-email');
      const topic = document.getElementById('contact-topic');
      const topicField = topic?.closest('div');
      let pendingDraft = null;
      try { pendingDraft = JSON.parse(sessionStorage.getItem('captacion_support_draft_v1') || 'null'); } catch (error) {}
      if (pendingDraft) {
        const messageEl = document.getElementById('contact-message');
        if (messageEl && !messageEl.value) messageEl.value = pendingDraft.message || '';
        if (topic && pendingDraft.topic) topic.value = pendingDraft.topic;
        sessionStorage.removeItem('captacion_support_draft_v1');
      }
      if (loggedIn) {
        if (name && !name.value) name.value = user.name || user.displayName || '';
        if (email && !email.value) email.value = user.email || '';
        [name, email].forEach(field => { if (field) { field.readOnly = true; field.classList.add('bg-slate-50'); } });
        if (topicField) topicField.classList.remove('hidden');
        if (topic) topic.required = true;
        if (!document.getElementById('contact-urgency')) {
          const messageField = document.getElementById('contact-message')?.closest('div');
          const urgencyField = document.createElement('div');
          urgencyField.innerHTML = '<label class="block text-xs font-bold text-slate-500 mb-1" for="contact-urgency">Urgencia *</label><select id="contact-urgency" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-blue/20"><option value="normal">Normal</option><option value="alta">Alta</option><option value="urgente">Urgente</option></select>';
          messageField?.before(urgencyField);
        }
      } else {
        if (topicField) topicField.classList.add('hidden');
        if (topic) topic.required = false;
        document.getElementById('contact-urgency')?.closest('div')?.remove();
      }
    }

    function updateContactPhoneRequirement() {
      const preference = document.getElementById('contact-preference')?.value || 'email';
      const phone = document.getElementById('contact-phone');
      const help = document.getElementById('contact-phone-help');
      const required = preference === 'call' || preference === 'whatsapp';
      if (phone) phone.required = required;
      if (help) help.textContent = required ? 'El telefono es obligatorio para esta preferencia.' : 'Opcional para contacto por email.';
    }

    async function handleContactSubmit(e) {
      e.preventDefault();
      const contactName = cleanText(document.getElementById('contact-name')?.value || '');
      const contactEmail = cleanText(document.getElementById('contact-email')?.value || '').toLowerCase();
      const contactPhone = cleanText(document.getElementById('contact-phone')?.value || '');
      const preference = document.getElementById('contact-preference')?.value || 'email';
      const topic = cleanText(document.getElementById('contact-topic')?.value || '');
      const urgency = document.getElementById('contact-urgency')?.value || '';
      const message = cleanText(document.getElementById('contact-message')?.value || '');
      const privacyAccepted = Boolean(document.getElementById('contact-privacy-consent')?.checked);
      const commercialConsent = Boolean(document.getElementById('contact-marketing-consent')?.checked);
      const errorBox = document.getElementById('contact-form-error');
      const fail = text => { if(errorBox){errorBox.textContent=text;errorBox.classList.remove('hidden');} };
      const loggedIn = Boolean(CAPTACION_MAILCHIMP?.loggedIn && CAPTACION_MAILCHIMP?.currentUser);
      if (!contactName || !/^\S+@\S+\.\S+$/.test(contactEmail) || !message || (loggedIn && (!topic || !urgency))) return fail(loggedIn ? 'Selecciona el motivo, la urgencia y escribe tu comentario.' : 'Completa nombre, correo y mensaje.');
      if (!privacyAccepted) return fail('Debes aceptar la política de privacidad.');
      if ((preference === 'call' || preference === 'whatsapp') && !/^\+?[0-9][0-9\s().-]{7,19}$/.test(contactPhone)) return fail('Indica un teléfono válido para llamada o WhatsApp.');
      if (errorBox) errorBox.classList.add('hidden');
      try {
        const response = await fetch(CAPTACION_MAILCHIMP.contactEndpoint, {
          method:'POST', credentials:'same-origin',
          headers:{'Content-Type':'application/json'},
           body:JSON.stringify({name:contactName,email:contactEmail,phone:contactPhone,preference,topic,urgency,message:loggedIn ? `${topic} [Urgencia: ${urgency}]: ${message}` : message,privacyAccepted})
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data?.message || 'No se pudo enviar la consulta.');
         syncMailchimpContact({ email: contactEmail, name: contactName, phone:contactPhone, source: 'contacto', tags: ['contacto'], message:`${loggedIn ? `Asunto: ${topic}. Urgencia: ${urgency}. ` : ''}${message}`, commercialConsent });
        trackConversionEvent('contact_submitted');
        showToast('Hemos recibido tu consulta. Te responderemos en menos de 24 horas laborables.', data?.ok ? 'success' : 'info');
         e.target.reset(); initContactFormExperience(); updateContactPhoneRequirement();
      } catch (error) {
        fail(error.message || 'No hemos podido completar la acción. Revisa tu conexión e inténtalo de nuevo.');
      }
    }

    function captacionOpenCookiePreferences() {
      const open = () => {
        if (typeof window.cmplz_open_preferences === 'function') { window.cmplz_open_preferences(); return true; }
        if (typeof window.cmplz_show_banner === 'function') { window.cmplz_show_banner(); return true; }
        document.dispatchEvent(new Event('cmplz_open_preferences'));
        const target = document.querySelector('#cmplz-cookiebanner-1-optin, .cmplz-cookiebanner, #cmplz-cookiebanner-container');
        if (target) { target.removeAttribute('aria-hidden'); target.classList.add('cmplz-show'); target.style.display = 'block'; target.style.visibility = 'visible'; return true; }
        return false;
      };
      if (open()) return;
      let tries = 0;
      const retry = window.setInterval(() => { tries += 1; if (open() || tries >= 10) window.clearInterval(retry); }, 250);
    }

    function captacionHideCookieNotice() {
      document.getElementById('captacion-cookie-notice')?.classList.add('is-hidden');
    }

    function captacionDismissCookieNotice() {
      try { localStorage.setItem('captacion_cookie_notice_dismissed_v1', '1'); } catch (error) {}
      captacionHideCookieNotice();
    }

    function captacionTriggerCookieAction(action) {
      const selectors = action === 'accept'
        ? ['.cmplz-accept', '[data-cmplz="accept"]', '.cmplz-btn.cmplz-accept']
        : ['.cmplz-deny', '[data-cmplz="deny"]', '.cmplz-btn.cmplz-deny'];
      const button = selectors.map(selector => document.querySelector(selector)).find(Boolean);
      if (button) button.click();
      captacionHideCookieNotice();
    }

    function captacionAcceptCookies() { captacionTriggerCookieAction('accept'); }
    function captacionRejectCookies() { captacionTriggerCookieAction('deny'); }

    function captacionInitCookieNotice() {
      const notice = document.getElementById('captacion-cookie-notice');
      if (!notice) return;
      let dismissed = false;
      try { dismissed = localStorage.getItem('captacion_cookie_notice_dismissed_v1') === '1'; } catch (error) {}
      if (dismissed || captacionIsComplianzVisible()) return;
      const hasConsentCookie = document.cookie.split(';').some(cookie => /(^|\s)cmplz_|(^|\s)cmplz_consented_services/i.test(cookie));
      if (!hasConsentCookie) notice.classList.remove('is-hidden');
    }

    // Alias de compatibilidad: Complianz es la única fuente de consentimiento.
    function openCookieSettings() {
      captacionOpenCookiePreferences();
    }

    function captacionIsComplianzVisible() {
      const banner = document.querySelector('.cmplz-cookiebanner, #cmplz-cookiebanner-container');
      if (!banner) return false;
      const style = window.getComputedStyle(banner);
      return style.display !== 'none' && style.visibility !== 'hidden' && banner.getAttribute('aria-hidden') !== 'true';
    }

    function removeLegacyCookiePreferences() {
      try {
        localStorage.removeItem('captacion_cookie_preferences_v1');
        localStorage.removeItem('captacion_cookies_v3_accepted');
      } catch (error) {}
    }

    function openReportModal(reference = '') {
      if (!requireRegisteredAction('abrir un reporte')) return;
      const session = getDemoSession?.() || {};
      const input = document.getElementById('report-content-reference');
      if (input && /^https?:\/\//i.test(reference)) input.value = cleanText(reference);
      const name = document.getElementById('report-name'); if (name) name.value = session.name || CAPTACION_MAILCHIMP?.currentUser?.name || '';
      const email = document.getElementById('report-email'); if (email) email.value = session.email || CAPTACION_MAILCHIMP?.currentUser?.email || '';
      const phone = document.getElementById('report-phone'); if (phone) phone.value = session.whatsapp || CAPTACION_MAILCHIMP?.currentUser?.phone || '';
      document.getElementById('content-report-modal')?.classList.remove('hidden');
    }

    function closeReportModal() {
      document.getElementById('content-report-modal')?.classList.add('hidden');
    }

    async function submitContentReport(event) {
      event.preventDefault();
      try {
        const response = await fetch(CAPTACION_MAILCHIMP.reportEndpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':CAPTACION_MAILCHIMP.nonce},body:JSON.stringify({name:document.getElementById('report-name')?.value,email:document.getElementById('report-email')?.value,phone:document.getElementById('report-phone')?.value,url:document.getElementById('report-content-reference')?.value,comment:document.getElementById('report-content-description')?.value,website:document.getElementById('report-website')?.value,copy_to_reporter:true,support_email:CAPTACION_MAILCHIMP.supportEmail || 'hola@compracaptacion.com'})});
        const data = await response.json(); if(!response.ok||!data?.ok) throw new Error(data?.message||'No se pudo enviar el reporte.');
        event.target.reset(); closeReportModal(); showToast(data.message || 'Reporte enviado. Se ha enviado una copia al email indicado.','success');
      } catch(error) { showToast(error.message||'No se pudo enviar el reporte.','info'); }
    }

    function subscribeToast(planName) {
      showToast(`¡Solicitud enviada! Has elegido activar el ${planName}.`, "info");
    }

    function downloadResource(name) {
      if (!requireRegisteredAction('acceder a recursos')) return;
      showToast("Descarga de documento: " + name + " completada con éxito.", "success");
    }

    function prepareLegalSignature(type = 'nda') {
      const normalizedType = type === 'collaboration' ? 'collaboration' : 'nda';
      const modal = document.getElementById('legal-signature-modal');
      const title = document.getElementById('legal-signature-title');
      const typeInput = document.getElementById('legal-document-type');
      const result = document.getElementById('legal-signature-result');
      if (typeInput) typeInput.value = normalizedType;
      if (title) title.textContent = normalizedType === 'nda' ? 'Preparar Acuerdo de Confidencialidad (NDA) para firma electrónica' : 'Preparar acuerdo de colaboración para firma electrónica';
      result?.classList.add('hidden');
      if (result) result.innerHTML = '';
      modal?.classList.remove('hidden');
    }

    function closeLegalSignatureModal() {
      document.getElementById('legal-signature-modal')?.classList.add('hidden');
    }

    function scheduleAgreementCalendarPlan(type = 'nda', reference = '', postalCode = '') {
      const label = type === 'nda' ? 'Acuerdo de Confidencialidad (NDA)' : 'acuerdo de colaboración';
      const refText = reference || 'sin referencia';
      addPrivateTask({ title:`Revisar ${label}`, detail:`Validar el documento ${label} asociado a ${refText}.`, priority:'high', due:'Hoy', dueAt:Date.now()+3600000*3, target:'operations', dedupeKey:`agreement-review-${type}-${refText}` });
      addPrivateTask({ title:'Solicitar firma a las partes', detail:`Coordina la firma del expediente ${refText}${postalCode ? ` · C.P. ${postalCode}` : ''}.`, priority:'high', due:'Mañana', dueAt:Date.now()+86400000, target:'communications', dedupeKey:`agreement-sign-${type}-${refText}` });
      addPrivateTask({ title:'Confirmar próxima acción operativa', detail:`Registra el siguiente paso comercial tras generar el ${label}.`, priority:'medium', due:'Esta semana', dueAt:Date.now()+86400000*3, target:'operations', dedupeKey:`agreement-followup-${type}-${refText}` });
      addPrivateNotification({ category:'Operaciones', title:'Agenda creada tras generar acuerdo', detail:`Se ha creado una agenda operativa para ${label}${reference ? ` · ${reference}` : ''}.`, target:'tasks', dueAt:Date.now()+3600000*2, dedupeKey:`agreement-notif-${type}-${refText}` });
      exportPrivateAgendaCalendar();
      renderDashboard();
      showToast('Tareas creadas y agenda exportada al calendario.', 'success');
    }

    function generateLegalSignatureLink(event) {
      event.preventDefault();
      const type = document.getElementById('legal-document-type')?.value || 'nda';
      const reference = cleanText(document.getElementById('legal-operation-reference')?.value || '');
      const postalCode = cleanText(document.getElementById('legal-postal-code')?.value || '');
      const token = `${type}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
      const demoLink = `${window.location.origin}${window.location.pathname}#/firma/${encodeURIComponent(token)}`;
      const result = document.getElementById('legal-signature-result');
      if (result) {
        result.innerHTML = `<strong class="text-green">Enlace preparado</strong><br><span class="break-all">${escapeHTML(demoLink)}</span><br><span class="block mt-2 text-[10px]">Documento: ${type === 'nda' ? 'Acuerdo de Confidencialidad (NDA)' : 'Acuerdo de colaboración'} · Ref. ${escapeHTML(reference)}${postalCode ? ` · C.P. ${escapeHTML(postalCode)}` : ''}. El enlace se genera en local. Cuando la firma electrónica esté activada, el documento se generará en servidor con registro de auditoría.</span>`;
        result.classList.remove('hidden');
      }
      showToast('Enlace seguro preparado.', 'success');
    }

    function generateLegalSignatureLink(event) {
      event.preventDefault();
      const type = document.getElementById('legal-document-type')?.value || 'nda';
      const reference = cleanText(document.getElementById('legal-operation-reference')?.value || '');
      const postalCode = cleanText(document.getElementById('legal-postal-code')?.value || '');
      const token = `${type}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
      const demoLink = `${window.location.origin}${window.location.pathname}#/firma/${encodeURIComponent(token)}`;
      const result = document.getElementById('legal-signature-result');
      if (result) {
        result.innerHTML = `<strong class="text-green">Enlace preparado</strong><br><span class="break-all">${escapeHTML(demoLink)}</span><br><span class="block mt-2 text-[10px]">Documento: ${type === 'nda' ? 'Acuerdo de Confidencialidad (NDA)' : 'Acuerdo de colaboración'} · Ref. ${escapeHTML(reference)}${postalCode ? ` · C.P. ${escapeHTML(postalCode)}` : ''}. El enlace se genera en local. Cuando la firma electrónica esté activada, el documento se generará en servidor con registro de auditoría.</span><div class="mt-3 flex flex-wrap gap-2"><button type="button" onclick="scheduleAgreementCalendarPlan('${type}','${escapeHTML(reference)}','${escapeHTML(postalCode)}')" class="px-3 py-2 rounded-lg bg-navy text-white text-[10px] font-bold">Agendar tareas pendientes</button><button type="button" onclick="switchPrivateDashboardPanel('tasks')" class="px-3 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-blue">Ver agenda</button></div>`;
        result.classList.remove('hidden');
      }
      addPrivateNotification({ category:'Operaciones', title:'Documento preparado para firma', detail:`Se ha generado un enlace seguro para ${type === 'nda' ? 'Acuerdo de Confidencialidad (NDA)' : 'acuerdo de colaboración'}${reference ? ` · ${reference}` : ''}.`, target:'operations', dueAt:Date.now()+3600000*4, dedupeKey:`legal-link-${type}-${reference || token}` });
      showToast('Enlace seguro preparado.', 'success');
    }

    function scheduleAgreementCalendarPlan(type = 'nda', reference = '', postalCode = '') {
      const label = type === 'nda' ? 'Acuerdo de Confidencialidad (NDA)' : 'acuerdo de colaboración';
      const refText = reference || 'sin referencia';
      addPrivateTask({ title:`Revisar ${label}`, detail:`Validar el documento ${label} asociado a ${refText}.`, priority:'high', due:'Hoy', dueAt:Date.now() + 3600000 * 3, target:'operations', dedupeKey:`agreement-review-${type}-${refText}` });
      addPrivateTask({ title:'Solicitar firma a las partes', detail:`Coordina la firma del expediente ${refText}${postalCode ? ` · C.P. ${postalCode}` : ''}.`, priority:'high', due:'Manana', dueAt:Date.now() + 86400000, target:'communications', dedupeKey:`agreement-sign-${type}-${refText}` });
      addPrivateTask({ title:'Confirmar proxima accion operativa', detail:`Registra el siguiente paso comercial tras generar el ${label}.`, priority:'medium', due:'Esta semana', dueAt:Date.now() + 86400000 * 3, target:'operations', dedupeKey:`agreement-followup-${type}-${refText}` });
      addPrivateNotification({ category:'Operaciones', title:'Agenda creada tras generar acuerdo', detail:`Se ha creado una agenda operativa para ${label}${reference ? ` · ${reference}` : ''}.`, target:'tasks', dueAt:Date.now() + 3600000 * 2, dedupeKey:`agreement-notif-${type}-${refText}` });
      exportPrivateAgendaCalendar();
      renderDashboard();
      showToast('Tareas creadas y agenda exportada al calendario.', 'success');
    }

    function generateLegalSignatureLink(event) {
      event.preventDefault();
      const type = document.getElementById('legal-document-type')?.value || 'nda';
      const reference = cleanText(document.getElementById('legal-operation-reference')?.value || '');
      const postalCode = cleanText(document.getElementById('legal-postal-code')?.value || '');
      const result = document.getElementById('legal-signature-result');
      if (!/^\d+$/.test(reference)) { showToast('Introduce el ID numérico de una operación real.', 'info'); return; }
      const signerName = cleanText(document.getElementById('legal-signer-name')?.value || '');
      const signerEmail = cleanText(document.getElementById('legal-signer-email')?.value || '').toLowerCase();
      const signerPhone = cleanText(document.getElementById('legal-signer-whatsapp')?.value || '');
      const documentPayload = JSON.stringify({ type, operation_id:Number(reference), postal_code:postalCode, signer_name:signerName, signer_email:signerEmail, signer_phone:signerPhone });
      crypto.subtle.digest('SHA-256', new TextEncoder().encode(documentPayload)).then(async digest => {
        const documentHash = Array.from(new Uint8Array(digest)).map(byte => byte.toString(16).padStart(2, '0')).join('');
        const endpoint = '/api/operations.php?action=sign_contract';
        const response = await fetch(endpoint, { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-WP-Nonce':window.CAPTACION_API?.nonce || ''}, body:JSON.stringify({operation_id:Number(reference), document_hash:documentHash}) });
        const data = await response.json();
        if (!response.ok || !data?.ok) throw new Error(data?.error || 'No se pudo registrar la firma.');
        if (result) { result.innerHTML = `<strong class="text-green">Firma registrada en servidor</strong><br><span class="block mt-2 text-[10px]">Huella documental: ${documentHash}<br>Estado: ${data.contract?.contract_signed ? 'Firmado por ambas partes' : 'Pendiente de la firma de la otra parte'}.</span>`; result.classList.remove('hidden'); }
        addPrivateNotification({ category:'Operaciones', title:'Firma registrada', detail:`La firma de la operación ${reference} ha quedado registrada con huella documental.`, target:'operations', dueAt:Date.now()+3600000*4, dedupeKey:`legal-sign-${reference}-${documentHash}` });
        showToast('Firma registrada en servidor.', 'success');
      }).catch(error => showToast(error.message || 'No se pudo registrar la firma.', 'error'));
    }

    // --- NOTIFICACIONES INTERNAS TOAST ---
    function showToast(message, type = "success") {
      const container = document.getElementById('toast-container');
      if (!container) return;
      const toast = document.createElement('div');

      let bg = "bg-white border-slate-200 text-slate-800";
      let icon = "•";

      if (type === "success") {
        bg = "bg-emerald-50 border-emerald-200 text-emerald-900";
        icon = "✓";
      } else if (type === "info") {
        // Avisos de conexión: amarillo corporativo para reforzar el carácter de alerta.
        bg = "bg-blue-light border-blue/20 text-amber";
        icon = "ℹ";
      }

      toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl border shadow-lg ${bg} transition-all duration-300 transform translate-y-2 opacity-0 pointer-events-auto`;
      toast.innerHTML = `
        <span class="font-black text-sm flex items-center justify-center w-5 h-5 rounded-full bg-white/40">${icon}</span>
        <span class="text-xs font-semibold">${message}</span>
      `;

      container.appendChild(toast);

      setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
      }, 50);

      setTimeout(() => {
        toast.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => {
          toast.remove();
        }, 300);
      }, 4000);
    }

    // ==========================================
    // 10. CONSULTAS CON IA DEL USUARIO
    // ==========================================
    async function callUserAI(taskType, prompt, systemInstruction = "", context = {}) {
      if (!getAIClientConfig().isLoggedIn) throw new Error('Debes iniciar sesión en WordPress para usar tu conexión IA.');
      await loadAIConnection();
      if (!hasConnectedAI()) throw new Error('AI_NOT_CONNECTED');
      const response = await captacionAIRequest('request', 'POST', {
        task_type: taskType,
        prompt,
        system_instruction: systemInstruction,
        context,
        temperature: 0.3,
        max_tokens: 700
      });
      return response?.text || '';
    }

    async function callAdminAI(prompt, systemInstruction = "", context = {}, temperature = 0.3, maxTokens = 700) {
      if (!getAIClientConfig().isLoggedIn) throw new Error('Debes iniciar sesión en WordPress.');
      const response = await captacionAIRequest('admin-request', 'POST', {
        prompt,
        system_instruction: systemInstruction,
        context,
        temperature,
        max_tokens: maxTokens
      });
      return response?.text || '';
    }

    async function fetchMatchExplanation(property, need) {
      if (!getAIClientConfig().isLoggedIn) return null;
      try {
        const response = await captacionAIRequest('match-explanation', 'POST', { property, need });
        return response?.explanation || null;
      } catch (err) {
        return null;
      }
    }

    async function enhancePropertyListing(title, description, propertyType, location, price, features) {
      if (!getAIClientConfig().isLoggedIn) throw new Error('Debes iniciar sesión en WordPress.');
      const response = await captacionAIRequest('enhance-listing', 'POST', {
        title, description, property_type: propertyType, location, price, features
      });
      return response;
    }

    async function runAIEnhanceListing() {
      if (!requireRegisteredAction('mejorar la descripción con IA')) return;
      const title = document.getElementById('offer-title')?.value || '';
      const description = document.getElementById('offer-description')?.value || '';
      const propertyType = document.getElementById('offer-type')?.value || '';
      const province = document.getElementById('offer-province-sel')?.value || '';
      const municipality = document.getElementById('offer-municipality-sel')?.value || '';
      const price = document.getElementById('offer-price')?.value || '';
      const location = [province, municipality].filter(Boolean).join(', ');
      const features = [document.getElementById('offer-bedrooms')?.value ? document.getElementById('offer-bedrooms').value + ' hab' : '', document.getElementById('offer-bathrooms')?.value ? document.getElementById('offer-bathrooms').value + ' baños' : '', document.getElementById('offer-surface')?.value ? document.getElementById('offer-surface').value + ' m²' : ''].filter(Boolean).join(', ');

      try {
        const result = await enhancePropertyListing(title, description, propertyType, location, price, features);
        if (!result?.title && !result?.description) {
          showToast('No se pudo mejorar el listing.', 'info');
          return;
        }
        const modal = document.getElementById('ai-match-modal');
        const report = document.getElementById('ai-report');
        const reportContent = document.getElementById('ai-report-content');
        if (!modal || !reportContent) return;
        modal.classList.remove('hidden');
        document.getElementById('ai-loading')?.classList.add('hidden');
        if (report) report.classList.remove('hidden');
        reportContent.innerHTML = `
          <h3 class="text-lg font-black text-blue mt-4 mb-2">Mejora de descripción</h3>
          <p class="text-xs text-slate-500 mb-4">La IA ha generado una versión mejorada del título y la descripción. Revisa los cambios y acepta o cancela.</p>
          <div class="space-y-4">
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase mb-1">Título sugerido</label>
              <div class="p-3 rounded-xl bg-blue/5 border border-blue/10 text-sm text-navy font-semibold">${escapeHTML(result.title)}</div>
            </div>
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase mb-1">Descripción sugerida</label>
              <div class="p-3 rounded-xl bg-blue/5 border border-blue/10 text-xs text-slate-600 leading-relaxed">${escapeHTML(result.description).replace(/\n/g, '<br>')}</div>
            </div>
            <div class="flex gap-3">
              <button onclick="acceptAIEnhancement('${escapeHTML(result.title).replace(/'/g, "\\'")}', '${escapeHTML(result.description).replace(/'/g, "\\'").replace(/\n/g, '\\n')}')" class="flex-1 px-4 py-3 rounded-xl bg-green text-white text-xs font-bold hover:bg-green-dark">Aceptar cambios</button>
              <button onclick="closeAiMatchModal()" class="flex-1 px-4 py-3 rounded-xl border border-slate-200 text-navy text-xs font-bold hover:border-slate-300">Cancelar</button>
            </div>
          </div>`;
      } catch (err) {
        showToast(err.message || 'Error al mejorar la descripción.', 'info');
      }
    }

    function acceptAIEnhancement(newTitle, newDescription) {
      const titleInput = document.getElementById('offer-title') || document.getElementById('need-pub-title');
      const descTextarea = document.getElementById('offer-description') || document.getElementById('need-pub-desc');
      if (titleInput) titleInput.value = newTitle;
      if (descTextarea) descTextarea.value = newDescription;
      closeAiMatchModal();
      showToast('Título y descripción actualizados con IA.', 'success');
    }

    async function runAIEnhanceNeedListing() {
      if (!requireRegisteredAction('mejorar la descripción con IA')) return;
      const title = document.getElementById('need-pub-title')?.value || '';
      const description = document.getElementById('need-pub-desc')?.value || '';
      const propertyType = document.getElementById('need-pub-type')?.value || '';
      const province = document.getElementById('need-pub-province-sel')?.value || '';
      const municipality = document.getElementById('need-pub-municipality-sel')?.value || '';
      const budget = document.getElementById('need-pub-budget')?.value || '';
      const location = [province, municipality].filter(Boolean).join(', ');

      try {
        const result = await enhancePropertyListing(title, description, propertyType, location, budget, '');
        if (!result?.title && !result?.description) {
          showToast('No se pudo mejorar la demanda.', 'info');
          return;
        }
        const modal = document.getElementById('ai-match-modal');
        const report = document.getElementById('ai-report');
        const reportContent = document.getElementById('ai-report-content');
        if (!modal || !reportContent) return;
        modal.classList.remove('hidden');
        document.getElementById('ai-loading')?.classList.add('hidden');
        if (report) report.classList.remove('hidden');
        reportContent.innerHTML = `
          <h3 class="text-lg font-black text-blue mt-4 mb-2">Mejora de demanda</h3>
          <p class="text-xs text-slate-500 mb-4">La IA ha generado una versión mejorada del título y la descripción de la demanda.</p>
          <div class="space-y-4">
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase mb-1">Título sugerido</label>
              <div class="p-3 rounded-xl bg-blue/5 border border-blue/10 text-sm text-navy font-semibold">${escapeHTML(result.title)}</div>
            </div>
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase mb-1">Descripción sugerida</label>
              <div class="p-3 rounded-xl bg-blue/5 border border-blue/10 text-xs text-slate-600 leading-relaxed">${escapeHTML(result.description).replace(/\n/g, '<br>')}</div>
            </div>
            <div class="flex gap-3">
              <button onclick="acceptAIEnhancement('${escapeHTML(result.title).replace(/'/g, "\\'")}', '${escapeHTML(result.description).replace(/'/g, "\\'").replace(/\n/g, '\\n')}')" class="flex-1 px-4 py-3 rounded-xl bg-green text-white text-xs font-bold hover:bg-green-dark">Aceptar cambios</button>
              <button onclick="closeAiMatchModal()" class="flex-1 px-4 py-3 rounded-xl border border-slate-200 text-navy text-xs font-bold hover:border-slate-300">Cancelar</button>
            </div>
          </div>`;
      } catch (err) {
        showToast(err.message || 'Error al mejorar la descripción.', 'info');
      }
    }

    function activateAIFromPropertyCard() {
      if (!getAIClientConfig().isLoggedIn) {
        showToast('Inicia sesión para configurar el modo IA.', 'info');
        openProfessionalAccess?.();
        return;
      }
      openAIConnectionModal();
    }

    async function generatePropertyInterestPoints(propertyId) {
      const property = properties.find(item => String(item.id) === String(propertyId));
      if (!property) return;
      try {
        const zone = [property.province, property.municipality, property.locality].filter(Boolean).join(' · ');
        const prompt = `Genera 5 sitios o servicios de interés cercanos para una ficha inmobiliaria entre profesionales usando solo esta ubicación aproximada: ${zone || 'España'}, código postal aproximado ${property.postalCode || 'no disponible'}. No inventes direcciones exactas ni nombres de propietarios. Devuelve solo una lista breve, una línea por punto.`;
        const text = await callUserAI('nearby_interest_points', prompt, 'Eres un asistente inmobiliario. Responde en español profesional, sin revelar ni inferir direcciones exactas privadas.', { property });
        const points = text.split(/\r?\n/).map(line => cleanText(line.replace(/^[-*\d.)\s]+/, ''))).filter(Boolean).slice(0, 6);
        property.nearby_interest_points = points.length ? points : buildEstimatedInterestPoints(property);
        localStorage.setItem('captacion_properties_v3', JSON.stringify(properties));
        renderMarketplace();
        showToast('Sitios de interés actualizados con IA.', 'success');
      } catch (error) {
        if (error.message === 'AI_NOT_CONNECTED') {
          showToast('Activa el modo IA para generar sitios de interés.', 'info');
          openAIConnectionModal();
          return;
        }
        showToast(error.message || 'No se pudieron generar los sitios de interés.', 'info');
      }
    }


    function buildLocalPropertyCopy({ type, province, municipality, locality, postalCode, price, fee, rehab, exclusive, urgency }) {
      const title = `${type} con potencial comercial en ${municipality || province}`;
      const area = [municipality, locality].filter(Boolean).join(' · ');
      const description = `Oportunidad inmobiliaria orientada a colaboración profesional en ${area || province}${postalCode ? ' (C.P. ' + postalCode + ')' : ''}. Precio de salida aproximado: ${formatCurrency(price)}. ${rehab === 'yes' ? 'El activo admite una estrategia de reforma y reposicionamiento comercial.' : 'El inmueble puede comercializarse sin una reforma integral previa.'} ${exclusive === 'yes' ? 'Existe exclusiva compartida para ordenar el proceso de venta.' : 'La colaboración deberá formalizarse mediante un acuerdo específico antes de revelar información sensible.'} Honorarios para el colaborador: ${fee || 'a consultar'}. Urgencia declarada: ${urgency}.`;
      return { title, description };
    }

    function buildMatchNotificationNotice(kind = 'need') {
      const subject = kind === 'property' ? 'esta captación' : 'esta demanda';
      const target = kind === 'property' ? 'una demanda compatible' : 'una captación compatible';
      return `<div class="mt-4 p-4 rounded-2xl border border-blue/20 bg-blue-light/35 text-xs text-slate-600 leading-relaxed"><strong class="block text-navy mb-1">Alerta activada en tu panel privado</strong>Si mas adelante aparece ${target} para ${subject}, recibiras un aviso en tu Panel Privado, dentro de la seccion <strong>Notificaciones</strong>, para que puedas revisarlo y actuar desde alli.</div>`;
    }

    function buildNeedCompatibilityReport(need) {
      const matches = getCompatiblePropertiesForNeed(need, 5, false);
      if (!matches.length) {
        return `<h3 class="text-lg font-black text-blue mt-4 mb-2">Resultado del cruce local</h3><p>No se ha identificado una coincidencia directa con el mismo tipo y territorio, superficie y estancias mínimas, precio dentro del presupuesto y condiciones profesionales aceptadas.</p><h4 class="text-base font-extrabold text-navy mt-3 mb-1">Propuesta comercial</h4><p>Mantener la demanda activa para nuevas captaciones que sí cumplan esos parámetros.</p>${buildMatchNotificationNotice('need')}`;
      }
      return `<h3 class="text-lg font-black text-blue mt-4 mb-2">Coincidencias disponibles</h3><p>Se han detectado ${matches.length} captación${matches.length === 1 ? '' : 'es'} compatible${matches.length === 1 ? '' : 's'} con esta demanda. Puedes revisar la ficha y avanzar con la accion correspondiente.</p><div class="mt-4 space-y-3">${matches.map(({ property, score }) => `<article class="p-4 rounded-2xl border border-slate-200 bg-slate-50"><div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3"><div class="min-w-0"><span class="block text-[10px] font-black text-blue">Ref. ${escapeHTML(property.reference || property.id)}</span><strong class="block text-sm text-navy mt-1">${escapeHTML(property.title)}</strong><span class="block text-[11px] text-slate-500 mt-1">${escapeHTML(property.province || property.location || 'N/D')} ? ${formatCurrency(property.price)} ? ${formatPropertyFeatures(property, true)}</span></div><span class="shrink-0 inline-flex px-3 py-1 rounded-full border text-[10px] font-black ${getCompatibilityBadgeClasses(score)}">${score}% match</span></div><div class="mt-3 flex flex-wrap gap-2"><button onclick="openMapPropertyCard('${property.id}')" class="px-3 py-2 rounded-lg bg-blue text-white text-[10px] font-bold">Ver propiedad</button><button onclick="openAccessModal('${property.id}')" class="px-3 py-2 rounded-lg border border-slate-200 text-navy text-[10px] font-bold">Solicitar acceso</button></div></article>`).join('')}</div>${buildMatchNotificationNotice('need')}`;
    }

    function buildPropertyCompatibilityReport(property) {
      const matches = getCompatibleNeedsForProperty(property, 5);
      if (!matches.length) {
        return `<h3 class="text-lg font-black text-blue mt-4 mb-2">Resultado del cruce local</h3><p>No se ha identificado una demanda activa con el mismo tipo y territorio, superficie y estancias mínimas, presupuesto suficiente y condiciones profesionales compatibles.</p><h4 class="text-base font-extrabold text-navy mt-3 mb-1">Propuesta comercial</h4><p>Mantener la captación activa en Marketplace para que pueda enlazarse cuando aparezca una demanda compatible.</p>${buildMatchNotificationNotice('property')}`;
      }
      return `<h3 class="text-lg font-black text-blue mt-4 mb-2">Demandas compatibles disponibles</h3><p>Se han detectado ${matches.length} demanda${matches.length === 1 ? '' : 's'} compatible${matches.length === 1 ? '' : 's'} con esta captación. Puedes revisar la demanda y actuar desde el panel.</p><div class="mt-4 space-y-3">${matches.map(({ need, score }) => `<article class="p-4 rounded-2xl border border-slate-200 bg-slate-50"><div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3"><div class="min-w-0"><span class="block text-[10px] font-black text-green">Intención de búsqueda</span><strong class="block text-sm text-navy mt-1">${escapeHTML(need.title)}</strong><span class="block text-[11px] text-slate-500 mt-1">${escapeHTML(need.province || need.location || 'N/D')} ? Hasta ${formatCurrency(need.budget)} ? ${formatPropertyFeatures(need, true)}</span></div><span class="shrink-0 inline-flex px-3 py-1 rounded-full border text-[10px] font-black ${getCompatibilityBadgeClasses(score)}">${score}% match</span></div><div class="mt-3 flex flex-wrap gap-2"><button onclick="openMapNeedCard('${need.id}')" class="px-3 py-2 rounded-lg bg-navy text-white text-[10px] font-bold">Ver demanda</button><button onclick="switchPrivateDashboardPanel('demands'); navigateTo('/area-privada');" class="px-3 py-2 rounded-lg border border-slate-200 text-navy text-[10px] font-bold">Ir al panel</button></div></article>`).join('')}</div>${buildMatchNotificationNotice('property')}`;
    }

    async function openPostPublishCompatibilityReport(kind, record) {
      const modal = document.getElementById('ai-match-modal');
      const loader = document.getElementById('ai-loading');
      const report = document.getElementById('ai-report');
      const reportContent = document.getElementById('ai-report-content');
      if (!modal || !reportContent) return;
      modal.classList.remove('hidden');
      if (loader) loader.classList.add('hidden');
      if (report) report.classList.remove('hidden');
      reportContent.innerHTML = kind === 'property' ? buildPropertyCompatibilityReport(record) : buildNeedCompatibilityReport(record);

      const matches = kind === 'property' ? getCompatibleNeedsForProperty(record, 3) : getCompatiblePropertiesForNeed(record, 3);
      if (!matches.length) return;
      const explanationContainer = document.createElement('div');
      explanationContainer.id = 'ai-post-publish-explanations';
      explanationContainer.className = 'mt-4 space-y-2';
      reportContent.appendChild(explanationContainer);

      for (const match of matches) {
        const property = kind === 'property' ? record : match.property;
        const need = kind === 'property' ? match.need : record;
        const targetTitle = kind === 'property' ? match.need.title : match.property.title;
        const wrapper = document.createElement('div');
        wrapper.className = 'p-3 rounded-xl bg-blue/5 border border-blue/10 text-xs text-slate-600 leading-relaxed';
        wrapper.innerHTML = `<span class="block text-[10px] font-black text-blue mb-1">Explicacion IA para "${escapeHTML(targetTitle)}"</span><span class="text-slate-400">Cargando...</span>`;
        explanationContainer.appendChild(wrapper);

        fetchMatchExplanation(property, need).then(explanation => {
          if (explanation) {
            wrapper.innerHTML = `<span class="block text-[10px] font-black text-blue mb-1">Explicacion IA</span>${escapeHTML(explanation)}`;
          } else {
            wrapper.remove();
          }
        }).catch(() => wrapper.remove());
      }
    }

    function buildLocalMatchReport(need) {
      return buildNeedCompatibilityReport(need);
    }

        async function runAIMatchmaker(needId) {
      if (!requireRegisteredAction('usar el match inteligente')) return;
      const userPhone = getDemoSession()?.whatsapp || CAPTACION_MAILCHIMP?.currentUser?.phone || '';
      if (!userPhone) {
          showPhoneRequiredModal(needId);
          return;
      }
      const need = needs.find(n => n.id === needId);
      if (!need) return;

      // 1. Abrir chat de Vera
      const windowEl = document.getElementById('vera-chat-window');
      if (windowEl && !windowEl.classList.contains('is-active')) {
        windowEl.classList.add('is-active');
      }
      if (!veraInitialized) {
        initVeraChatSession();
      }

      const messagesContainer = document.getElementById('vera-chat-messages');
      if (!messagesContainer) return;

      // 2. Insertar mensaje del usuario en el chat
      const questionText = `Vera, busca coincidencias para la demanda: "${need.title}" (${need.type}, en ${need.municipality}, presupuesto ${need.budget} €).`;
      const userMsg = document.createElement('div');
      userMsg.className = 'vera-msg vera-msg-user';
      userMsg.textContent = questionText;
      messagesContainer.appendChild(userMsg);

      // Guardar en historial
      veraChatHistory.push({ role: 'user', content: questionText });
      if (veraChatHistory.length > 12) {
        veraChatHistory.shift();
      }

      // 3. Insertar indicador de escribiendo
      const typingMsg = document.createElement('div');
      typingMsg.className = 'vera-msg vera-msg-assistant';
      typingMsg.id = 'vera-typing-indicator';
      typingMsg.innerHTML = '<strong>Vera:</strong><br><br><em style="color:#64748b;">Buscando coincidencias en el Marketplace...</em>';
      messagesContainer.appendChild(typingMsg);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;

      // 4. Preparar el prompt especial para Vera
      const localMatches = getCompatiblePropertiesForNeed(need, 5);
      const prompt = `Analiza si hay coincidencias para esta demanda de búsqueda:
- Título: ${need.title}
- Tipo: ${need.type}
- Municipio: ${need.municipality} (${need.province})
- Presupuesto: ${need.budget} €
- Requisitos: ${need.description}

Nuestra cartera actual tiene: ${JSON.stringify(properties.map(p => ({ id: p.id, title: p.title, type: p.type, price: p.price, municipality: p.municipality })))}

Matches locales precalculados: ${JSON.stringify(localMatches.map(m => ({ id: m.property.id, title: m.property.title, score: m.score })))}

Por favor, analízalo y dime si hay coincidencias (Matches) o no. Si las hay, menciona cuáles y el porcentaje de compatibilidad. Si no las hay, indícalo claramente de forma proactiva y dile al usuario qué pasos dar (ej: ampliar presupuesto o zona). Sé breve, directa y profesional.`;

      // Hacemos la llamada al endpoint de Vera
      try {
        const res = await fetch(veraThemeUri + '/api-vera.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            historial: [...veraChatHistory.slice(0, -1), { role: 'user', content: prompt }],
            contexto: {
              nombre: (typeof getDemoSession === 'function' && getDemoSession()?.name) || 'Profesional',
              demanda_id: need.id
            }
          })
        });
        
        const data = await res.json();
        const indicator = document.getElementById('vera-typing-indicator');
        if (indicator) {
          indicator.removeAttribute('id');
          const textResponse = data.respuesta ? data.respuesta.replace(/\\n/g, '<br>').replace(/\n/g, '<br>') : (data.error || "No he podido calcular el cruce en este momento.");
          indicator.innerHTML = `<strong>Vera:</strong><br><br>${textResponse}`;
          
          if (data.respuesta) {
            veraChatHistory.push({ role: 'assistant', content: data.respuesta });
            if (veraChatHistory.length > 12) {
              veraChatHistory.shift();
            }
          }
        }
      } catch (error) {
        const indicator = document.getElementById('vera-typing-indicator');
        if (indicator) {
          indicator.removeAttribute('id');
          indicator.innerHTML = `<strong>Vera:</strong><br><br><span style="color:#e11d48;">Error de conexión. Asegúrate de que el servidor de IA esté accesible.</span>`;
        }
      } finally {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      }
    }

    function closeAiMatchModal() {
      const modal = document.getElementById('ai-match-modal');
      if (modal) modal.classList.add('hidden');
    }

    // --- COPIAR REPORTE AL PORTAPAPELES (Respaldo por execCommand para iframes) ---
    function copyAiReport() {
      const content = document.getElementById('ai-report-content');
      if (!content) return;
      const text = content.innerText;
      const tempInput = document.createElement("textarea");
      tempInput.value = text;
      document.body.appendChild(tempInput);
      tempInput.select();
      document.execCommand("copy");
      document.body.removeChild(tempInput);
      showToast("¡Informe copiado al portapapeles! 📋", "success");
    }

    // ==========================================
    // 11. EVENTOS GENERALES & UTILITIES
    // ==========================================
    function renderDashboard() {
      const tbody = document.getElementById('dash-table-body');
      if (!tbody) return;
      const activeCount = document.getElementById('dash-active-count');
      const totalFees = document.getElementById('dash-total-fees');
      const closedCount = document.getElementById('dash-closed-count');

      tbody.innerHTML = '';
      if (activeCount) activeCount.innerText = properties.length;
      if (closedCount) closedCount.innerText = closedOperations.length;

      let calculatedVolume = 0;
      properties.forEach(p => {
        const percentageValue = parseFloat(p.fee) || 3.5;
        calculatedVolume += p.price * (percentageValue / 100);
      });

      if (totalFees) totalFees.innerText = new Intl.NumberFormat('de-DE').format(Math.round(calculatedVolume)) + " €";

      properties.forEach((prop) => {
        const tr = document.createElement('tr');
        tr.className = "border-b border-slate-100 hover:bg-slate-50 transition-colors";
        tr.innerHTML = `
          <td class="py-4 px-6 font-extrabold text-navy truncate max-w-xs">${prop.title}<span class="block text-[10px] text-slate-400 mt-0.5 font-semibold">${formatPropertyFeatures(prop, true)}</span></td>
          <td class="py-4 px-6">${prop.location}<span class="block text-[10px] text-slate-400 mt-0.5">C.P. ${escapeHTML(prop.postalCode || 'N/D')}</span></td>
          <td class="py-4 px-6 font-semibold">${new Intl.NumberFormat('de-DE').format(prop.price)} €</td>
          <td class="py-4 px-6 text-blue font-bold">${prop.fee}</td>
          <td class="py-4 px-6 text-center">
            <div class="flex items-center justify-center gap-2">
              <button onclick="closeListing('${prop.id}')" class="px-2.5 py-1.5 rounded-lg bg-green-light text-green hover:bg-emerald-100 font-bold transition-all">Cerrar</button>
              <button onclick="deleteListing('${prop.id}')" class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-bold transition-all">Baja</button>
            </div>
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    function closeListing(id) {
      const property = properties.find(p => p.id === id);
      if (!property) return;
      closedOperations.unshift({ id: `closed-${Date.now()}`, title: property.title, zone: property.province || property.location, price: property.price, date: Date.now() });
      properties = properties.filter(p => p.id !== id);
      persistDemoState();
      renderMarketplace();
      renderDashboard();
      renderHome();
      showToast("Operación marcada como cerrada y retirada del inventario activo.", "success");
    }

    function deleteListing(id) {
      properties = properties.filter(p => p.id !== id);
      persistDemoState();
      renderMarketplace();
      renderDashboard();
      renderHome();
      showToast("La captación se ha dado de baja del sistema.", "info");
    }

    function calculateSplit() {
      const priceInput = document.getElementById('calc-price');
      const pctInput = document.getElementById('calc-pct');
      const splitInput = document.getElementById('calc-split');

      if (!priceInput || !pctInput || !splitInput) return;

      const price = parseFloat(priceInput.value) || 0;
      const pct = parseFloat(pctInput.value) || 0;
      const split = parseFloat(splitInput.value) || 0;

      const totalCommission = price * (pct / 100);
      const yourShare = totalCommission * (split / 100);

      const totalAmountEl = document.getElementById('calc-total-amount');
      const yourShareEl = document.getElementById('calc-your-share');

      if (totalAmountEl) totalAmountEl.innerText = new Intl.NumberFormat('de-DE').format(totalCommission) + " €";
      if (yourShareEl) yourShareEl.innerText = new Intl.NumberFormat('de-DE').format(yourShare) + " €";
    }

    function getPrivateXmlFeeds() {
      try {
        return JSON.parse(localStorage.getItem('captacion_private_xml_feeds_v2')) || [];
      } catch (error) {
        return [];
      }
    }

    function setPrivateXmlFeeds(feeds) {
      localStorage.setItem('captacion_private_xml_feeds_v2', JSON.stringify(feeds));
    }

    function createXmlFeedId(xmlUrl) {
      let hash = 0;
      for (let index = 0; index < xmlUrl.length; index++) {
        hash = ((hash << 5) - hash) + xmlUrl.charCodeAt(index);
        hash |= 0;
      }
      return `xml-feed-${Math.abs(hash)}`;
    }

    function getXmlNodeText(node, tagNames = []) {
      for (const tagName of tagNames) {
        const element = node.querySelector(tagName);
        const value = element?.textContent?.trim();
        if (value) return cleanText(value);
      }
      return '';
    }

    function getXmlNodeAttribute(node, attributeNames = []) {
      for (const attributeName of attributeNames) {
        const value = node.getAttribute(attributeName);
        if (value) return cleanText(value);
      }
      return '';
    }

    function sanitizeXmlPublicText(value = '') {
      return cleanText(value)
        .replace(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi, '[dato de contacto protegido]')
        .replace(/(?:https?:\/\/|www\.)\S+/gi, '[enlace protegido]')
        .replace(/(?:\+?34[\s.-]?)?(?:[6789]\d{2}|9\d{2})[\s.-]?\d{3}[\s.-]?\d{3}/g, '[teléfono protegido]');
    }

    function xmlNodeToObject(node) {
      const result = {};
      if (!node) return result;
      Array.from(node.attributes || []).forEach(attr => {
        result[`@${attr.name}`] = cleanText(attr.value || '');
      });
      Array.from(node.children || []).forEach(child => {
        const name = child.tagName;
        const value = child.children && child.children.length ? xmlNodeToObject(child) : cleanText(child.textContent || '');
        if (Object.prototype.hasOwnProperty.call(result, name)) {
          if (!Array.isArray(result[name])) result[name] = [result[name]];
          result[name].push(value);
        } else {
          result[name] = value;
        }
      });
      return result;
    }

    function extractXmlImageUrls(node) {
      const urls = [];
      if (!node) return urls;
      const allowedTags = ['image', 'imagen', 'photo', 'foto', 'picture', 'pictures', 'photos', 'media', 'enclosure', 'attachment', 'content', 'attachment_url', 'media_url'];
      const urlAttrs = ['url', 'src', 'href', 'link', 'file', 'archivo', 'ruta', 'path', 'source', 'download', 'guid', 'attachment_url', 'media_url'];
      const walk = currentNode => {
        if (!currentNode || !currentNode.children) return;
        Array.from(currentNode.children).forEach(child => {
          const rawTag = String(child.tagName || '').toLowerCase();
          const tag = rawTag.includes(':') ? rawTag.split(':').pop() : rawTag;
          const isCandidate = allowedTags.includes(tag) || rawTag.includes('image') || rawTag.includes('photo') || rawTag.includes('media') || rawTag.includes('enclosure') || rawTag.includes('attachment');
          if (isCandidate) {
            const direct = [
              child.querySelector?.('url')?.textContent?.trim(),
              child.querySelector?.('src')?.textContent?.trim(),
              child.querySelector?.('href')?.textContent?.trim(),
              child.querySelector?.('link')?.textContent?.trim(),
              child.querySelector?.('file')?.textContent?.trim(),
              child.querySelector?.('archivo')?.textContent?.trim(),
              child.querySelector?.('ruta')?.textContent?.trim(),
              child.querySelector?.('path')?.textContent?.trim(),
              child.querySelector?.('source')?.textContent?.trim(),
              child.getAttribute?.('url'),
              child.getAttribute?.('src'),
              child.getAttribute?.('href'),
              child.getAttribute?.('file'),
              child.getAttribute?.('attachment_url'),
              child.getAttribute?.('media_url'),
              child.textContent?.trim()
            ].find(Boolean);
            if (direct) urls.push(cleanText(direct));
            Array.from(child.attributes || []).forEach(attr => {
              if (urlAttrs.includes(String(attr.name || '').toLowerCase())) {
                const attrValue = cleanText(attr.value || '');
                if (attrValue) urls.push(attrValue);
              }
            });
          }
          walk(child);
        });
      };
      walk(node);
      return [...new Set(urls.filter(Boolean))];
    }

    function parseXmlProperties(xmlText, xmlUrl) {
      const parser = new DOMParser();
      const xmlDocument = parser.parseFromString(xmlText, 'application/xml');
      if (xmlDocument.querySelector('parsererror')) {
        throw new Error('El fichero recibido no contiene un XML válido.');
      }

      const feedId = createXmlFeedId(xmlUrl);
      const candidateSelectors = ['property', 'inmueble', 'listing', 'anuncio', 'oferta', 'item'];
      let nodes = [];
      for (const selector of candidateSelectors) {
        const matches = Array.from(xmlDocument.querySelectorAll(selector));
        if (matches.length) {
          nodes = matches;
          break;
        }
      }
      if (!nodes.length) {
        throw new Error('No se han encontrado propiedades en el fichero XML.');
      }

      return nodes.map((node, index) => {
        const reference = getXmlNodeText(node, ['reference', 'referencia', 'ref', 'id']) || getXmlNodeAttribute(node, ['id', 'ref']) || String(index + 1);
        const province = getXmlNodeText(node, ['province', 'provincia']) || 'España';
        const municipality = getXmlNodeText(node, ['municipality', 'municipio', 'city', 'localidad']) || province;
        const locality = getXmlNodeText(node, ['neighborhood', 'barrio', 'zona']);
        const postalCode = getXmlNodeText(node, ['postal_code', 'postalCode', 'codigo_postal', 'codigopostal', 'cp', 'zip', 'zipcode']);
        const bedrooms = parseFlexibleInteger(getXmlNodeText(node, ['bedrooms', 'habitaciones', 'dormitorios', 'rooms']));
        const bathrooms = parseFlexibleInteger(getXmlNodeText(node, ['bathrooms', 'banos', 'baños', 'aseos']));
        const surface = parseFlexibleNumber(getXmlNodeText(node, ['surface', 'surface_m2', 'superficie', 'metros', 'm2']));
        const description = sanitizeXmlPublicText(getXmlNodeText(node, ['description', 'descripcion', 'observations', 'observaciones']));
        const title = sanitizeXmlPublicText(getXmlNodeText(node, ['title', 'titulo', 'name', 'nombre'])) || `Propiedad importada en ${municipality}`;
        const type = getXmlNodeText(node, ['type', 'tipo', 'property_type', 'tipo_inmueble']) || 'Activo inmobiliario';
        const rawPrice = getXmlNodeText(node, ['price', 'precio', 'importe']);
        const sourceData = xmlNodeToObject(node);
        const images = [...new Set([...extractXmlImageUrls(node), ...extractImageUrlsFromValue(sourceData, [])])].filter(Boolean);
        const image = images[0] || extractImageUrlFromValue(getXmlNodeText(node, ['image', 'imagen', 'photo', 'foto', 'picture']));
        const fee = getXmlNodeText(node, ['fee', 'comision', 'honorarios']) || 'A consultar';
        return normalizePropertyRecord({
          id: `xml-${feedId}-${reference}`,
          title,
          type,
          location: province,
          province,
          municipality,
          locality,
          postalCode,
          bedrooms,
          bathrooms,
          surface,
          neighborhood: `${municipality}${locality ? ' · ' + locality : ''}`,
          price: parseFlexibleNumber(rawPrice),
          fee,
          images,
          gallery: images,
          sourceData,
          score: 80,
          rehab: false,
          exclusive: false,
          urgency: 'Media',
          docs: 'Pendiente de validación documental',
          description: description || 'Propiedad importada mediante fichero XML. La información privada permanece protegida.',
          badgeColor: 'blue',
          badgeText: 'Importada desde XML',
          fundingConditions: 'Condiciones disponibles mediante solicitud validada.',
          image,
          date: Date.now() - index,
          xmlFeedId: feedId,
          xmlSourceUrl: xmlUrl,
          xmlReference: reference
        }, index);
      });
    }

    function saveImportedXmlPropertiesToMarketplace(importedProperties, feedId) {
      let storedProperties = [];
      try {
        storedProperties = JSON.parse(localStorage.getItem('captacion_properties_v3')) || [];
      } catch (error) {
        storedProperties = [...properties];
      }

      const existingProperties = storedProperties
        .map((property, index) => normalizePropertyRecord(property, index))
        .filter(property => property.xmlFeedId !== feedId);

      const marketplaceProperties = importedProperties.map((property, index) => normalizePropertyRecord({
        ...property,
        status: 'active',
        marketplaceVisible: true,
        importedFromXml: true
      }, index));

      properties = [...marketplaceProperties, ...existingProperties];
      localStorage.setItem('captacion_properties_v3', JSON.stringify(properties));
      return marketplaceProperties.length;
    }

    function deletePrivateXmlFeed(feedId) {
      const feeds = getPrivateXmlFeeds();
      const feed = feeds.find(item => item.id === feedId);
      if (!feed) {
        showToast('La fuente XML seleccionada ya no existe.', 'info');
        return;
      }

      const propertiesToRemove = properties.filter(property => property.xmlFeedId === feedId);
      const confirmed = window.confirm(`¿Eliminar esta fuente XML y retirar sus ${propertiesToRemove.length} propiedades del Marketplace?`);
      if (!confirmed) return;

      properties = properties.filter(property => property.xmlFeedId !== feedId);
      persistDemoState();

      const remainingFeeds = feeds.filter(item => item.id !== feedId);
      setPrivateXmlFeeds(remainingFeeds);

      localStorage.removeItem('captacion_private_xml_url_v1');
      const input = document.getElementById('private-xml-url');
      if (input) input.value = '';

      renderPrivateXmlFeeds();
      renderMarketplace();
      renderDashboard();
      renderHome();
      showToast(`XML eliminado correctamente: ${propertiesToRemove.length} propiedades retiradas del Marketplace.`, 'info');
    }

    function renderPrivateXmlFeeds() {
      const container = document.getElementById('private-xml-feeds-list');
      if (!container) return;
      const feeds = getPrivateXmlFeeds();
      if (!feeds.length) {
        container.innerHTML = '<p class="text-xs text-slate-400">Todavía no se ha creado ninguna fuente XML.</p>';
        return;
      }
      container.innerHTML = feeds.map(feed => `
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl bg-slate-50 border border-slate-200">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex px-2 py-1 rounded-full bg-green-light text-green text-[10px] font-black uppercase">XML creado</span>
              <span class="text-[10px] text-slate-400">${new Date(feed.updatedAt).toLocaleString('es-ES')}</span>
            </div>
            <p class="text-xs font-bold text-navy mt-2 truncate" title="${escapeHTML(feed.url)}">${escapeHTML(feed.url)}</p>
          </div>
          <div class="shrink-0 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <div class="px-3 py-2 rounded-xl bg-white border border-slate-200 text-center">
              <strong class="block text-xl font-black text-blue">${feed.propertyCount}</strong>
              <span class="block text-[9px] uppercase tracking-wider font-black text-slate-400">Propiedades subidas</span>
            </div>
            <button type="button" onclick="deletePrivateXmlFeed('${feed.id}')" class="px-3 py-2 rounded-xl border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 text-[10px] font-black uppercase tracking-wider transition-all">
              Eliminar
            </button>
          </div>
        </div>
      `).join('');
    }

    // Proxy XML del mismo dominio. Debe subirse junto a este HTML como xml-proxy.php.
    // También puede sobrescribirse antes de cargar la app:
    // window.CAPTACION_XML_PROXY_URL = '/ruta-personalizada/xml-proxy.php?url={url}';
    function buildXmlProxyUrl(proxyTemplate, xmlUrl) {
      const encodedUrl = encodeURIComponent(xmlUrl);
      if (proxyTemplate.includes('{url}')) return proxyTemplate.replace('{url}', encodedUrl);
      const separator = proxyTemplate.includes('?') ? '&' : '?';
      return `${proxyTemplate}${separator}url=${encodedUrl}`;
    }

    async function fetchXmlResponseText(requestUrl, timeoutMs = 18000) {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
      try {
        const response = await fetch(requestUrl, { cache: 'no-store', signal: controller.signal });
        if (!response.ok) {
          const detail = await response.text().catch(() => '');
          throw new Error(`HTTP ${response.status}${detail ? ': ' + detail.slice(0, 180) : ''}`);
        }
        const xmlText = await response.text();
        if (!xmlText.trim()) throw new Error('El servidor ha devuelto una respuesta vacía.');
        const contentType = String(response.headers.get('content-type') || '').toLowerCase();
        const preview = xmlText.trim().slice(0, 500).toLowerCase();
        if (contentType.includes('html') || /^<!doctype\s+html/i.test(xmlText) || /^<html\b/i.test(xmlText) || /ha fallado la comprobaci[oó]n de la cookie|cookie|consent|login|iniciar sesi[oó]n|acceso restringido/i.test(preview)) {
          throw new Error('La URL devuelve HTML o una pantalla intermedia de cookies/login, no XML válido.');
        }
        return xmlText;
      } finally {
        clearTimeout(timeoutId);
      }
    }

    async function fetchXmlTextWithFallback(xmlUrl) {
      const customProxy = String(window.CAPTACION_XML_PROXY_URL || '').trim();
      const sameOriginProxy = customProxy || window.CAPTACION_CONFIG.xmlProxyUrl;
      const attempts = [
        { mode: 'server-proxy', label: 'proxy XML del servidor', url: buildXmlProxyUrl(sameOriginProxy, xmlUrl) },
        { mode: 'direct', label: 'descarga directa', url: xmlUrl },
        // Respaldos solo para pruebas estáticas. En producción debe funcionar el proxy propio anterior.
        { mode: 'demo-proxy-corsproxy', label: 'proxy público alternativo 1', url: `https://corsproxy.io/?url=${encodeURIComponent(xmlUrl)}` },
        { mode: 'demo-proxy-allorigins', label: 'proxy público alternativo 2', url: `https://api.allorigins.win/raw?url=${encodeURIComponent(xmlUrl)}` }
      ];

      let lastError = null;
      const errors = [];
      for (const attempt of attempts) {
        try {
          const xmlText = await fetchXmlResponseText(attempt.url);
          return { xmlText, fetchMode: attempt.mode, fetchLabel: attempt.label };
        } catch (error) {
          lastError = error;
          errors.push(`${attempt.label}: ${error.message || error.name}`);
          console.warn(`Falló la ${attempt.label} del XML.`, error);
        }
      }

      const technicalReason = lastError?.name === 'AbortError'
        ? 'La descarga superó el tiempo máximo de espera.'
        : 'La URL puede ser pública, pero el navegador no puede leerla por CORS. Sube xml-proxy.php al mismo directorio que este HTML y comprueba que PHP esté activo en el hosting.';
      console.warn('Intentos de descarga XML fallidos:', errors);
      throw new Error(`No se pudo descargar el XML. ${technicalReason}`);
    }

    async function savePrivateXmlUrl() {
      const input = document.getElementById('private-xml-url');
      const button = document.getElementById('private-xml-save-btn');
      const resultDiv = document.getElementById('private-feed-xml-url-result');
      if (!input) return;
      const xmlUrl = input.value.trim();
      if (!xmlUrl) {
        showToast('Introduce la URL del fichero XML.', 'info');
        return;
      }
      try {
        const parsedUrl = new URL(xmlUrl);
        if (!['http:', 'https:'].includes(parsedUrl.protocol)) throw new Error('Protocolo no permitido');
      } catch (error) {
        showToast('Introduce una URL pública válida que empiece por http:// o https://.', 'info');
        return;
      }

      const originalButtonText = button?.textContent;
      if (button) {
        button.disabled = true;
        button.textContent = 'Importando XML...';
      }
      resultDiv?.classList.remove('hidden');
      if (resultDiv) resultDiv.innerHTML = '<span class="text-blue">Validando e importando XML...</span>';

      try {
        const endpoint = (window.CAPTACION_API?.endpoints?.importXmlUrl) || '/api/xml_feeds.php?action=import_url';
        const response = await fetch(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.CAPTACION_API?.nonce || '' },
          body: JSON.stringify({ url: xmlUrl })
        });
        const rawText = await response.text();
        let data;
        try {
          data = JSON.parse(rawText);
        } catch(jsonErr) {
          throw new Error('El servidor devolvió una respuesta no válida o la ruta del importador no está accesible.');
        }
        if (!response.ok || !data.ok) throw Object.assign(new Error(data.message || 'No se pudo importar el XML desde backend.'), { code: data.code, data });
        localStorage.removeItem('captacion_private_xml_url_v1');
        input.value = '';
        await loadXmlFeeds();
        if (typeof loadWordPressRealEstateRecords === 'function') {
          try { await loadWordPressRealEstateRecords(); } catch(e){}
        }
        const pending = Number(data.pending_review || 0);
        const feedId = data.import_batch_id || '';
        if (resultDiv) resultDiv.innerHTML = `<div class="p-3 rounded-xl border border-green/20 bg-green-light text-xs text-slate-700"><strong class="text-green">XML importado y publicado automáticamente.</strong> ${Number(data.imported || 0)} importadas, ${Number(data.updated || 0)} actualizadas. ${pending ? `${pending} requieren atención.` : 'No hay incidencias detectadas.'}</div>`;
        showToast(pending ? `XML publicado con ${pending} incidencias para soporte.` : `XML importado y publicado: ${data.imported} propiedades.`, pending ? 'info' : 'success');
      } catch (error) {
        renderXmlImportError(resultDiv, error);
        showToast(error.message || 'No se pudo importar el fichero XML desde el servidor.', 'info');
        prepareXmlSupportRequest(error.message || 'No se pudo importar el XML.', { source: xmlUrl });
      } finally {
        if (button) {
          button.disabled = false;
          button.textContent = originalButtonText || 'Guardar e importar XML';
        }
      }
    }

    function loadPrivateXmlUrl() {
      const input = document.getElementById('private-xml-url');
      if (!input) return;
      localStorage.removeItem('captacion_private_xml_url_v1');
      input.value = '';
      renderPrivateXmlFeeds();
    }


    // ==========================================
    // 12. CAJA DE HERRAMIENTAS PARA PROFESIONALES INMOBILIARIOS
    // ==========================================
    const resourceCatalog = [
      { id:'sale-readiness', category:'captacion', icon:'✅', title:'Informe de preparación para la venta', description:'Evalúa documentación, cargas, certificado energético, fotografías, precio, ocupación e incidencias.', time:'5 min', result:'Puntúa 0–100', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'marketing-authorisation', category:'captacion', icon:'', title:'Autorización de comercialización', description:'Prepara una autorización de publicación o un mandato de colaboración entre profesionales.', time:'5 min', result:'Genera documento', access:'profesional', revision:'Pendiente revisión jurídica', status:'roadmap' },
      { id:'capture-call-script', category:'captacion', icon:'☎', title:'Guion inteligente para captación', description:'Sugiere preguntas y argumentos según el perfil del propietario y la situación del inmueble.', time:'2 min', result:'Genera guion', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'owner-feedback', category:'captacion', icon:'', title:'Informe de feedback para propietarios', description:'Convierte comentarios de visitas en objeciones repetidas y acciones recomendadas.', time:'5 min', result:'Genera informe', access:'registro', revision:'Diseño funcional', status:'roadmap' },

      { id:'max-budget', category:'compraventa', icon:'', title:'Presupuesto máximo de compra', description:'Calcula capacidad económica a partir de ahorros, ingresos, financiación y gastos estimados.', time:'3 min', result:'Calcula presupuesto', access:'publico', revision:'Diseño funcional', status:'roadmap' },
      { id:'mortgage-scenarios', category:'compraventa', icon:'⌂', title:'Calculadora hipotecaria con escenarios', description:'Compara cuota ordinaria y escenario prudente si cambian las condiciones financieras.', time:'3 min', result:'Compara cuotas', access:'publico', revision:'Disponible', status:'demo' },
      { id:'purchase-costs', category:'compraventa', icon:'🧾', title:'Gastos de compra por comunidad autónoma', description:'Diferencia vivienda nueva, usada, residencia habitual e inversión con parámetros actualizables.', time:'4 min', result:'Calcula gastos', access:'publico', revision:'Revisión normativa necesaria', status:'roadmap' },
      { id:'buyer-qualification', category:'compraventa', icon:'🎯', title:'Cualificación del comprador', description:'Recoge financiación, zona, plazo y criterios esenciales para asignar prioridad comercial.', time:'4 min', result:'Calcula prioridad', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'visit-sheet', category:'compraventa', icon:'📋', title:'Hoja de visita transparente', description:'Prepara una hoja con identificación, fecha, agente interviniente, protección de datos y honorarios.', time:'4 min', result:'Genera PDF', access:'registro', revision:'Pendiente revisión jurídica', status:'roadmap' },
      { id:'property-comparison', category:'compraventa', icon:'⚖', title:'Comparador de inmuebles', description:'Crea una tabla visual con precio, metros, ubicación, gastos, estado y ventajas.', time:'5 min', result:'Crea comparativa', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'purchase-offer', category:'compraventa', icon:'📨', title:'Propuesta de compra ordenada', description:'Genera una oferta con importe, vigencia, financiación, condiciones y observaciones.', time:'5 min', result:'Genera propuesta', access:'profesional', revision:'Pendiente revisión jurídica', status:'roadmap' },

      { id:'rental-type', category:'alquileres', icon:'🔑', title:'Identificador del tipo de alquiler', description:'Diferencia vivienda habitual, temporal, turístico, habitación y uso distinto de vivienda.', time:'3 min', result:'Crea orientación', access:'publico', revision:'Revisión normativa necesaria', status:'roadmap' },
      { id:'rental-ad-check', category:'alquileres', icon:'', title:'Verificador básico de anuncio inmobiliario', description:'Comprueba si el texto de venta o alquiler incorpora datos esenciales como precio, ubicación aproximada, superficie y certificado energético.', time:'3 min', result:'Crea revisión', access:'publico', revision:'Disponible', status:'demo', priority:7 },
      { id:'rent-update', category:'alquileres', icon:'📆', title:'Actualización de renta', description:'Calcula una revisión orientativa y prepara una carta de comunicación al inquilino.', time:'3 min', result:'Calcula y redacta', access:'publico', revision:'Revisión normativa necesaria', status:'roadmap' },
      { id:'deposit-calculator', category:'alquileres', icon:'🛡', title:'Fianza y garantías adicionales', description:'Distingue supuestos y organiza la documentación para gestionar garantías.', time:'3 min', result:'Calcula garantía', access:'publico', revision:'Revisión normativa necesaria', status:'roadmap' },
      { id:'rental-profitability', category:'alquileres', icon:'📈', title:'Rentabilidad neta del alquiler', description:'Incluye IBI, comunidad, seguro, mantenimiento, vacíos, gestión y reformas estimadas.', time:'3 min', result:'Calcula rentabilidad', access:'publico', revision:'Disponible', status:'demo', priority:9 },
      { id:'sell-or-rent', category:'alquileres', icon:'🔄', title:'Comparador vender o alquilar', description:'Muestra liquidez inmediata frente a ingresos periódicos, gastos y horizonte temporal.', time:'5 min', result:'Compara escenarios', access:'registro', revision:'Diseño funcional', status:'roadmap', priority:9 },
      { id:'rental-calendar', category:'alquileres', icon:'', title:'Calendario de vencimientos y avisos', description:'Organiza fin de contrato, renta, seguros, depósitos, certificados y comunicaciones.', time:'5 min', result:'Crea calendario', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'short-stay-checklist', category:'alquileres', icon:'🧳', title:'Checklist de corta duración', description:'Organiza advertencias y documentación antes de anunciar un alquiler de corta duración.', time:'4 min', result:'Crea checklist', access:'registro', revision:'Revisión normativa necesaria', status:'roadmap' },

      { id:'pbc-kyc', category:'legal', icon:'🪪', title:'Asistente PBC/KYC', description:'Guía la identificación, titular real, representación, actividad y documentación del cliente.', time:'6 min', result:'Crea expediente', access:'profesional', revision:'Revisión jurídica necesaria', status:'roadmap', priority:8 },
      { id:'funds-origin', category:'legal', icon:'💳', title:'Checklist de origen de fondos', description:'Organiza aportaciones familiares, transferencias internacionales, sociedades y pagos complejos.', time:'5 min', result:'Crea checklist', access:'profesional', revision:'Revisión jurídica necesaria', status:'roadmap' },
      { id:'pbc-file', category:'legal', icon:'', title:'Portada de expediente PBC', description:'Resume tareas realizadas, documentos pendientes, fechas y responsable interno.', time:'4 min', result:'Genera portada', access:'profesional', revision:'Revisión jurídica necesaria', status:'roadmap' },
      { id:'rgpd-pack', category:'legal', icon:'🔒', title:'Pack RGPD inmobiliario', description:'Agrupa cláusulas para formularios, visitas, compradores, propietarios y colaboradores.', time:'8 min', result:'Descarga pack', access:'profesional', revision:'Revisión jurídica necesaria', status:'roadmap' },
      { id:'energy-certificate', category:'legal', icon:'⚡', title:'Verificador del certificado energético', description:'Registra calificación, vigencia y alertas si falta información en el anuncio.', time:'3 min', result:'Crea alerta', access:'registro', revision:'Revisión normativa necesaria', status:'roadmap' },
      { id:'cadastral-reference', category:'legal', icon:'', title:'Asistente de valor de referencia catastral', description:'Organiza el dato oficial y prepara una nota informativa comprensible para el cliente.', time:'4 min', result:'Crea nota', access:'registro', revision:'Revisión normativa necesaria', status:'roadmap' },
      { id:'simple-note-reader', category:'legal', icon:'📑', title:'Lector asistido de nota simple', description:'Resume titulares, hipotecas, embargos, usufructos y cargas para revisión profesional.', time:'4 min', result:'Resume documento', access:'profesional', revision:'Revisión jurídica necesaria', status:'roadmap' },
      { id:'operation-checklist', category:'legal', icon:'☑', title:'Checklist por tipo de operación', description:'Genera pasos para herencia, hipoteca, inmueble alquilado, VPO, solar, local o sociedad.', time:'4 min', result:'Crea checklist', access:'registro', revision:'Revisión normativa necesaria', status:'roadmap' },
      { id:'keys-delivery', category:'legal', icon:'', title:'Acta de entrega de llaves', description:'Incluye estado, suministros, lecturas, llaves y observaciones.', time:'4 min', result:'Genera acta', access:'registro', revision:'Pendiente revisión jurídica', status:'roadmap' },
      { id:'photo-inventory', category:'legal', icon:'📷', title:'Inventario fotográfico para alquiler', description:'Organiza estancias, mobiliario y desperfectos con aceptación de las partes.', time:'8 min', result:'Crea inventario', access:'registro', revision:'Diseño funcional', status:'roadmap' },

      { id:'fee-split', category:'colaboración', icon:'🧮', title:'Reparto avanzado de honorarios', description:'Calcula comisión, IVA configurable y reparto entre intervinientes.', time:'2 min', result:'Calcula importes', access:'publico', revision:'Disponible', status:'demo', priority:5 },
      { id:'collaboration-generator', category:'colaboración', icon:'', title:'Generador de acuerdo de colaboración', description:'Recoge inmueble, captador, agente comprador, porcentajes, hitos y condiciones de cobro.', time:'6 min', result:'Genera acuerdo', access:'profesional', revision:'Pendiente revisión jurídica', status:'legal', priority:6 },
      { id:'lead-referral', category:'colaboración', icon:'🔗', title:'Acta de derivación de contacto', description:'Registra cuándo se entrega un propietario y bajo qué condiciones se remunera.', time:'4 min', result:'Genera acta', access:'profesional', revision:'Pendiente revisión jurídica', status:'roadmap' },
      { id:'owner-sharing', category:'colaboración', icon:'📤', title:'Autorización para compartir oportunidad', description:'Documenta el consentimiento para publicar información mínima no sensible.', time:'4 min', result:'Genera autorización', access:'profesional', revision:'Pendiente revisión jurídica', status:'roadmap' },
      { id:'private-operation-room', category:'colaboración', icon:'🚪', title:'Sala privada de operación', description:'Reúne participantes, Acuerdo de Confidencialidad (NDA), documentos, tareas, ofertas y actividad reciente.', time:'Continuo', result:'Gestiona operación', access:'profesional', revision:'Planificado', status:'roadmap', priority:10 },
      { id:'interaction-log', category:'colaboración', icon:'🕒', title:'Registro cronológico de interacciones', description:'Deja trazabilidad de presentación, visitas y transmisión de ofertas.', time:'Continuo', result:'Registra actividad', access:'profesional', revision:'Planificado', status:'roadmap' },
      { id:'internal-liquidation', category:'colaboración', icon:'🧾', title:'Factura o liquidación interna', description:'Prepara la liquidación del reparto pactado con conceptos configurables.', time:'4 min', result:'Genera liquidación', access:'profesional', revision:'Pendiente revisión fiscal', status:'roadmap' },
      { id:'reputation', category:'colaboración', icon:'', title:'Reputación del colaborador', description:'Muestra identidad verificada, operaciones, respuesta, documentación y valoraciones.', time:'Continuo', result:'Consulta perfil', access:'profesional', revision:'Planificado', status:'roadmap' },
      { id:'incidents', category:'colaboración', icon:'⚠', title:'Incidencias y mediación', description:'Registra discrepancias sobre honorarios, duplicidad de contactos o incumplimientos.', time:'5 min', result:'Abre incidencia', access:'profesional', revision:'Planificado', status:'roadmap' },
      { id:'what-if', category:'colaboración', icon:'', title:'Asistente “¿qué ocurre si…?', description:'Organiza situaciones frecuentes: retirada, venta directa, expiración o financiación fallida.', time:'3 min', result:'Consulta escenarios', access:'profesional', revision:'Pendiente revisión jurídica', status:'roadmap' },

      { id:'portal-ads', category:'marketing', icon:'📣', title:'Generador de anuncios por portal', description:'Crea versiones para web, portales, redes sociales y WhatsApp con revisión previa.', time:'4 min', result:'Genera textos', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'photo-checklist', category:'marketing', icon:'📸', title:'Checklist fotográfico por inmueble', description:'Indica fotografías, orden recomendado y errores a evitar según el activo.', time:'3 min', result:'Crea checklist', access:'publico', revision:'Diseño funcional', status:'roadmap' },
      { id:'home-staging', category:'marketing', icon:'🛋', title:'Plan básico de home staging', description:'Propone mejoras de bajo coste antes de fotografiar o enseñar el inmueble.', time:'4 min', result:'Crea plan', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'follow-up-pack', category:'marketing', icon:'💬', title:'Mensajes de seguimiento', description:'Prepara textos para propietarios, compradores y colaboradores con tareas pendientes.', time:'3 min', result:'Genera mensajes', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'weekly-owner-report', category:'marketing', icon:'📬', title:'Informe semanal para propietarios', description:'Resume visitas, consultas, origen de leads, comentarios y recomendaciones.', time:'5 min', result:'Genera informe', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'daily-agenda', category:'marketing', icon:'📅', title:'Agenda diaria de oportunidades', description:'Ordena leads, documentación incompleta, visitas y operaciones próximas al cierre.', time:'Continuo', result:'Prioriza tareas', access:'registro', revision:'Planificado', status:'roadmap' },
      { id:'reviews-request', category:'marketing', icon:'🌟', title:'Solicitador de reseñas', description:'Prepara mensajes posteriores a la operación y registra solicitudes enviadas.', time:'2 min', result:'Genera mensaje', access:'registro', revision:'Diseño funcional', status:'roadmap' },
      { id:'objections-library', category:'marketing', icon:'🧠', title:'Biblioteca de objeciones comerciales', description:'Ayuda a responder a objeciones sobre exclusiva, precio y honorarios.', time:'2 min', result:'Consulta respuestas', access:'publico', revision:'Diseño funcional', status:'roadmap' }
    ];

    let activeResourceCategory = 'captacion';
    const accessLabels = { publico:'Público', registro:'Registro gratuito', profesional:'Profesional verificado' };

    function getResourcesForActiveCategory() {
      return resourceCatalog.filter(item => item.category === 'captacion');
    }

    function updateResourceCategoryVisibility() {
      const scopedResources = getResourcesForActiveCategory();
      const total = document.getElementById('resource-stat-total');
      const demos = document.getElementById('resource-stat-demo');
      if (total) total.textContent = scopedResources.length;
      if (demos) demos.textContent = scopedResources.filter(item => item.status === 'demo').length;

      const legalSection = document.getElementById('resources-legal-documents');
      const showLegalSection = false;
      legalSection?.classList.toggle('hidden', !showLegalSection);
      document.querySelectorAll('[data-legal-resource-category]').forEach(card => {
        const cardCategory = card.dataset.legalResourceCategory;
        const showCard = activeResourceCategory === 'all' || cardCategory === activeResourceCategory;
        card.classList.toggle('hidden', !showCard);
      });
    }

    function initResourcesToolbox() {
      renderDownloadableResources();
      updateResourceCategoryVisibility();
      renderResourceFeatured();
      renderResourceCatalog();
    }

    let currentResourcePlanFilter = 'all';
    let currentResourceSearchQuery = '';

    function filterResourcesByPlan(plan) {
      currentResourcePlanFilter = plan;
      ['all', 'free', 'pro'].forEach(p => {
        const btn = document.getElementById(`res-tab-${p}`);
        if (btn) {
          const isActive = p === plan;
          btn.className = isActive
            ? 'px-4 py-2.5 rounded-xl bg-blue text-white font-bold text-xs shadow-sm transition-all'
            : 'px-4 py-2.5 rounded-xl text-slate-600 dark:text-slate-300 hover:text-navy dark:hover:text-white font-bold text-xs bg-slate-100 dark:bg-slate-800 transition-all flex items-center gap-1.5';
        }
      });
      renderDownloadableResources();
    }

    function filterResourcesSearch() {
      const input = document.getElementById('resource-search-input');
      currentResourceSearchQuery = (input?.value || '').toLowerCase().trim();
      renderDownloadableResources();
    }

    window.filterResourcesByPlan = filterResourcesByPlan;
    window.filterResourcesSearch = filterResourcesSearch;

    function renderDownloadableResources() {
      const container = document.getElementById('professional-downloadable-resources');
      if (!container) return;

      const fallbackResources = [
        {
          id: 'colaboracion',
          resource_id: 'colaboracion',
          title: 'Contrato Oficial de Colaboración 50/50 y Reparto de Honorarios',
          description: 'Documento vinculante homologado para formalizar el reparto de comisiones al 50% antes de la visita con devengo en notaría.',
          tag: 'Seguridad Jurídica',
          plan_access: 'free',
          has_static_pdf: true,
          pdf_url: 'assets/docs/plantilla-acuerdo-colaboracion-honorarios-captacion-app.pdf'
        },
        {
          id: 'nda',
          resource_id: 'nda',
          title: 'Acuerdo de Confidencialidad y Custodia de Datos (NDA)',
          description: 'Blindaje frente a contacto directo con el cliente propietario y protección estricta de notas simples y catastro.',
          tag: 'Protección de Datos',
          plan_access: 'free',
          has_static_pdf: true,
          pdf_url: 'assets/docs/plantilla-nda-confidencialidad-captacion-app.pdf'
        },
        {
          id: 'parte_visita',
          resource_id: 'parte_visita',
          title: 'Hoja Oficial de Registro y Reconocimiento de Visita Compartida',
          description: 'Parte de visita 50/50 firmado en el acto que acredita la presentación del comprador por la agencia colaboradora durante 12 meses.',
          tag: 'Acreditación de Visitas',
          plan_access: 'free',
          has_static_pdf: true,
          pdf_url: 'assets/docs/plantilla-parte-visita-colaboracion-captacion-app.pdf'
        },
        {
          id: 'pitch_exclusiva',
          resource_id: 'pitch_exclusiva',
          title: 'Dossier Ejecutivo: Cómo Vender tu Casa con Exclusiva Compartida',
          description: 'Presentación comercial de alto impacto para convencer al propietario reacio de las ventajas de la red colaborativa.',
          tag: 'Captación de Exclusivas',
          plan_access: 'professional',
          has_static_pdf: true,
          pdf_url: 'assets/docs/dossier-exclusiva-compartida-propietario-captacion.pdf'
        },
        {
          id: 'score_comprador',
          resource_id: 'score_comprador',
          title: 'Matriz y Checklist de Pre-Cualificación Financiera del Comprador',
          description: 'Sistema de scoring financiero, validación de fondos propios (30%) y cálculo del ratio de endeudamiento DTI.',
          tag: 'Cualificación Solvente',
          plan_access: 'professional',
          has_static_pdf: true,
          pdf_url: 'assets/docs/matriz-precualificacion-financiera-comprador.pdf'
        },
        {
          id: 'acm_vera',
          resource_id: 'acm_vera',
          title: 'Generador de Análisis Comparativo de Mercado (ACM) con IA Vera',
          description: 'Informe técnico de valoración con testigos de venta reales y justificación de precio óptimo ante el vendedor.',
          tag: 'Valoración con IA',
          plan_access: 'professional',
          has_static_pdf: true,
          pdf_url: 'assets/docs/informe-acm-valoracion-mercado-ia-vera.pdf'
        },
        {
          id: 'oferta_reserva',
          resource_id: 'oferta_reserva',
          title: 'Propuesta Formal de Compra con Depósito de Reserva Blindada',
          description: 'Documento vinculante de intención firme de compra con consignación de señal y plazo de aceptación por el vendedor.',
          tag: 'Cierre de Ofertas',
          plan_access: 'professional',
          has_static_pdf: true,
          pdf_url: 'assets/docs/propuesta-formal-compra-deposito-reserva.pdf'
        },
        {
          id: 'guia_fiscal',
          resource_id: 'guia_fiscal',
          title: 'Guía y Calculadora Fiscal de Compraventas (ITP, Plusvalía e IRPF)',
          description: 'Dossier con tablas autonómicas actualizadas para liquidar con precisión los gastos de notaría, registro e impuestos.',
          tag: 'Fiscalidad Inmobiliaria',
          plan_access: 'professional',
          has_static_pdf: true,
          pdf_url: 'assets/docs/guia-fiscalidad-inmobiliaria-liquidaciones.pdf'
        },
        {
          id: 'arras_1454',
          resource_id: 'arras_1454',
          title: 'Modelo de Contrato de Arras Penitenciales (Art. 1454 CC con Hipoteca)',
          description: 'Contrato blindado con cláusula resolutoria de financiación bancaria para proteger a las partes y los honorarios.',
          tag: 'Seguridad Jurídica Pro',
          plan_access: 'professional',
          has_static_pdf: true,
          pdf_url: 'assets/docs/contrato-arras-penitenciales-art1454-cc.pdf'
        }
      ];

      const rawResources = (CAPTACION_MAILCHIMP?.resources && CAPTACION_MAILCHIMP.resources.length >= 3)
        ? CAPTACION_MAILCHIMP.resources
        : fallbackResources;

      const registered = Boolean(CAPTACION_MAILCHIMP?.loggedIn);
      const isProMember = typeof hasProfessionalMembershipAccess === 'function' ? hasProfessionalMembershipAccess() : false;

      // Filter by plan
      let filtered = rawResources.filter(item => {
        if (currentResourcePlanFilter === 'free') return item.plan_access === 'free';
        if (currentResourcePlanFilter === 'pro') return item.plan_access !== 'free';
        return true;
      });

      // Filter by search query
      if (currentResourceSearchQuery) {
        filtered = filtered.filter(item => {
          const text = `${item.title} ${item.description} ${item.tag || ''}`.toLowerCase();
          return text.includes(currentResourceSearchQuery);
        });
      }

      if (filtered.length === 0) {
        container.innerHTML = `
          <div class="col-span-full p-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800">
            <span class="text-3xl">🔍</span>
            <h4 class="text-base font-bold text-navy dark:text-white mt-3">No se encontraron recursos</h4>
            <p class="text-xs text-slate-500 mt-1">Prueba con otros términos de búsqueda o cambia la categoría.</p>
            <button type="button" onclick="filterResourcesByPlan('all')" class="mt-4 px-4 py-2 rounded-xl bg-blue text-white text-xs font-bold">Ver todos los recursos</button>
          </div>
        `;
        return;
      }

      container.innerHTML = filtered.map((item, idx) => {
        const isFree = item.plan_access === 'free';
        const resourceId = JSON.stringify(String(item.id || item.resource_id || '').replace(/[^a-z0-9_-]/gi, ''));
        const tag = item.tag || (isFree ? 'Legal y Seguridad' : 'Exclusivo Pro');

        const badge = isFree
          ? `<span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 text-[10px] font-black uppercase tracking-wider">✓ Gratuito</span>`
          : `<span class="px-2.5 py-1 rounded-full bg-blue/10 text-blue dark:text-blue-neon border border-blue/20 text-[10px] font-black uppercase tracking-wider">💎 Plan Pro</span>`;

        let actionHtml = '';
        if (isFree) {
          if (!registered) {
            actionHtml = `<button type="button" onclick='startResourceDownload(${resourceId})' class="w-full py-3 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs uppercase tracking-wider transition-all shadow-sm">Descargar Modelo PDF Gratis →</button>`;
          } else {
            actionHtml = `<button type="button" onclick='startResourceDownload(${resourceId})' class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-sm flex items-center justify-center gap-2"><span>⬇</span> <span>Descargar Modelo PDF</span></button>`;
          }
        } else {
          if (isProMember) {
            actionHtml = `
              <div class="grid grid-cols-2 gap-2 w-full">
                <button type="button" onclick='startResourceDownload(${resourceId})' class="py-2.5 rounded-xl bg-blue hover:bg-blue-dark text-white font-bold text-xs transition-all shadow-sm">Descargar PDF</button>
                <a href="${item.create_url || '#/recursos'}" class="py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-navy dark:text-white font-bold text-xs text-center hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">Personalizar</a>
              </div>
            `;
          } else {
            actionHtml = `<button type="button" onclick="openProfessionalSubscriptionModal('recurso-pro-locked')" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue to-navy hover:opacity-95 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md flex items-center justify-center gap-2"><span>🔒</span> <span>Desbloquear con Plan Pro</span></button>`;
          }
        }

        return `
          <article class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl hover:border-blue/40 dark:hover:border-blue/50 transition-all flex flex-col justify-between group">
            <div class="space-y-4">
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg">#${idx + 1} · ${escapeHTML(tag)}</span>
                ${badge}
              </div>
              <div class="space-y-2">
                <h3 class="text-base font-bold text-navy dark:text-white leading-snug group-hover:text-blue transition-colors">
                  ${escapeHTML(item.title)}
                </h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                  ${escapeHTML(item.description)}
                </p>
              </div>
              <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-600 dark:text-slate-300">
                <span>Formato: <strong>PDF Editable</strong></span>
                <span>Validez: <strong>España (2026)</strong></span>
              </div>
            </div>
            <div class="pt-5 mt-4 border-t border-slate-100 dark:border-slate-800/80">
              ${actionHtml}
            </div>
          </article>
        `;
      }).join('');
    }

    function setResourceCategory(category) {
      activeResourceCategory = 'captacion';
      document.querySelectorAll('.resource-category-btn').forEach(button => {
        const isActive = button.dataset.resourceCategory === activeResourceCategory;
        button.classList.toggle('bg-navy', isActive);
        button.classList.toggle('text-white', isActive);
        button.classList.toggle('border', !isActive);
        button.classList.toggle('border-slate-200', !isActive);
        button.classList.toggle('text-slate-600', !isActive);
      });
      updateResourceCategoryVisibility();
      renderResourceFeatured();
      renderResourceCatalog();
    }

    function resourceActionLabel(item) {
      if (item.access === 'profesional' && !hasProfessionalMembershipAccess()) return 'Activar Professional';
      if (item.status === 'demo') return 'Abrir herramienta';
      if (item.status === 'legal') return 'Ver plantilla';
      return 'Ver alcance';
    }

    function resourceStatusBadge(item) {
      if (item.access === 'profesional' && !hasProfessionalMembershipAccess()) return '<span class="px-2 py-1 rounded-full bg-amber-light text-amber text-[9px] font-black uppercase">Professional</span>';
      if (item.status === 'demo') return '<span class="px-2 py-1 rounded-full bg-green-light text-green text-[9px] font-black uppercase">Disponible</span>';
      if (item.status === 'legal') return '<span class="px-2 py-1 rounded-full bg-blue-light text-blue text-[9px] font-black uppercase">Plantilla disponible</span>';
      return '<span class="px-2 py-1 rounded-full bg-slate-100 text-slate-500 text-[9px] font-black uppercase">Planificado</span>';
    }

    function renderResourceFeatured() {
      const section = document.getElementById('resources-featured-section');
      const container = document.getElementById('resource-featured-grid');
      if (!container) return;
      const featured = getResourcesForActiveCategory()
        .filter(item => item.priority)
        .sort((a, b) => a.priority - b.priority)
        .slice(0, 8);
      section?.classList.toggle('hidden', featured.length === 0);
      container.innerHTML = featured.map(item => `
        <article class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
          <div><div class="flex items-start justify-between gap-3"><span class="text-2xl">${item.icon}</span><span class="flex h-7 w-7 items-center justify-center rounded-full bg-navy text-white text-[10px] font-black">${item.priority}</span></div><h4 class="text-sm font-black text-navy mt-4 leading-snug">${escapeHTML(item.title)}</h4><p class="text-[11px] text-slate-500 leading-relaxed mt-2">${escapeHTML(item.description)}</p></div>
          <button onclick="openResourceTool('${item.id}')" class="mt-4 w-full py-2 rounded-lg bg-blue text-white text-[10px] font-black hover:bg-blue-dark">${resourceActionLabel(item)}</button>
        </article>`).join('');
    }

    function renderResourceCatalog() {
      const container = document.getElementById('resources-catalog-grid');
      if (!container) return;
      const search = cleanText(document.getElementById('resource-search')?.value || '').toLowerCase();
      const access = document.getElementById('resource-access-filter')?.value || 'all';
      const filtered = resourceCatalog.filter(item => {
        const matchCategory = item.category === 'captacion';
        const matchAccess = access === 'all' || item.access === access;
        const content = `${item.title} ${item.description} ${item.result}`.toLowerCase();
        return matchCategory && matchAccess && (!search || content.includes(search));
      });
      const count = document.getElementById('resource-count');
      if (count) count.textContent = `${filtered.length} recurso${filtered.length === 1 ? '' : 's'}`;
      container.innerHTML = filtered.length ? filtered.map(item => `
        <article class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:border-blue/40 hover:shadow-md transition-all flex flex-col justify-between">
          <div><div class="flex items-start justify-between gap-3"><span class="text-2xl">${item.icon}</span>${resourceStatusBadge(item)}</div><h4 class="text-sm font-black text-navy mt-4 leading-snug">${escapeHTML(item.title)}</h4><p class="text-[11px] text-slate-500 leading-relaxed mt-2">${escapeHTML(item.description)}</p><div class="grid grid-cols-2 gap-2 mt-4 text-[10px]"><span class="px-2 py-1.5 rounded-lg bg-slate-50 border border-slate-100 text-slate-500"><b class="text-navy">Tiempo:</b> ${escapeHTML(item.time)}</span><span class="px-2 py-1.5 rounded-lg bg-slate-50 border border-slate-100 text-slate-500"><b class="text-navy">Resultado:</b> ${escapeHTML(item.result)}</span><span class="col-span-2 px-2 py-1.5 rounded-lg bg-slate-50 border border-slate-100 text-slate-500"><b class="text-navy">Acceso:</b> ${accessLabels[item.access]} · <b class="text-navy">Revisión:</b> ${escapeHTML(item.revision)}</span></div></div>
          <button onclick="openResourceTool('${item.id}')" class="mt-4 w-full py-2.5 rounded-xl ${item.status === 'demo' ? 'bg-blue text-white hover:bg-blue-dark' : 'border border-slate-200 text-navy hover:bg-slate-50'} text-[10px] font-black transition-all">${resourceActionLabel(item)}</button>
        </article>`).join('') : '<div class="md:col-span-2 xl:col-span-3 p-8 rounded-2xl bg-white border border-slate-200 text-center text-sm text-slate-500">No hay herramientas que coincidan con los filtros seleccionados.</div>';
    }

    function closeResourceToolModal() { document.getElementById('resource-tool-modal')?.classList.add('hidden'); }

    function openResourceTool(id) {
      if (!requireRegisteredAction('usar herramientas profesionales')) return;
      const item = resourceCatalog.find(resource => resource.id === id);
      if (!item) return;
      if (item.access === 'profesional' && !hasProfessionalMembershipAccess()) {
        requireProfessionalMembership(item.title);
        return;
      }
      if (item.status === 'legal') {
        if (['colaboracion', 'nda', 'parte_visita'].includes(String(item.id))) {
          openEditableLegalTemplate(String(item.id));
        } else {
          document.getElementById('resources-legal-documents')?.scrollIntoView({ behavior:'smooth', block:'start' });
          showToast('Consulta las plantillas legales y prepara la firma cuando exista una colaboración confirmada.', 'info');
        }
        return;
      }
      const modal = document.getElementById('resource-tool-modal');
      const title = document.getElementById('resource-tool-title');
      const description = document.getElementById('resource-tool-description');
      const body = document.getElementById('resource-tool-body');
      if (!modal || !title || !description || !body) return;
      title.textContent = item.title;
      description.textContent = item.description;
      body.innerHTML = getResourceToolMarkup(item);
      modal.classList.remove('hidden');
      if (id === 'seller-net') calculateSellerNet();
      if (id === 'fee-split') calculateAdvancedFeeSplit();
      if (id === 'mortgage-scenarios') calculateMortgageScenarios();
      if (id === 'rental-profitability') calculateRentalProfitability();
    }

    function getResourceToolMarkup(item) {
      if (item.id === 'seller-net') return `<div class="space-y-4"><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><label class="text-xs font-bold text-slate-500">Precio de venta (€)<input id="seller-net-price" type="number" value="280000" oninput="calculateSellerNet()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Honorarios agencia (%)<input id="seller-net-fee" type="number" value="4" step="0.1" oninput="calculateSellerNet()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">IVA sobre honorarios (%)<input id="seller-net-vat" type="number" value="21" oninput="calculateSellerNet()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Cancelación hipotecaria (€)<input id="seller-net-mortgage" type="number" value="0" oninput="calculateSellerNet()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Otros gastos configurables (€)<input id="seller-net-other" type="number" value="0" oninput="calculateSellerNet()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label></div><div id="seller-net-result" class="p-4 rounded-2xl bg-green-light border border-green/20"></div><button onclick="showToast('Informe PDF de neto vendedor preparado.', 'success')" class="w-full py-3 rounded-xl bg-navy text-white text-xs font-black">Preparar informe PDF</button><p class="text-[10px] text-slate-400">Cálculo orientativo. No incluye impuestos personales del vendedor salvo que se incorporen posteriormente como parámetros configurables.</p></div>`;
      if (item.id === 'fee-split') return `<div class="space-y-4"><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><label class="text-xs font-bold text-slate-500">Precio de venta (€)<input id="adv-fee-price" type="number" value="300000" oninput="calculateAdvancedFeeSplit()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Comisión total (%)<input id="adv-fee-pct" type="number" value="5" step="0.1" oninput="calculateAdvancedFeeSplit()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">IVA (%)<input id="adv-fee-vat" type="number" value="21" oninput="calculateAdvancedFeeSplit()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Parte captador (%)<input id="adv-fee-share-a" type="number" value="50" oninput="calculateAdvancedFeeSplit()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label></div><div id="adv-fee-result" class="p-4 rounded-2xl bg-blue-light border border-blue/20"></div><button onclick="prepareLegalSignature('collaboration')" class="w-full py-3 rounded-xl bg-navy text-white text-xs font-black">Preparar acuerdo de colaboración</button></div>`;
      if (item.id === 'blocked-radar') return `<div class="space-y-3"><p class="text-xs text-slate-500">Marca las incidencias detectadas para obtener un semáforo preliminar.</p><div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">${['Falta nota simple actualizada','Discrepancias catastrales','Herencia pendiente','Hipoteca sin revisar','Falta certificado energético','Arrendatario vigente','Copropietarios sin confirmar','Documentación PBC incompleta','Ocupación o posesión dudosa','Precio sin validar'].map((label,index)=>`<label class="flex items-start gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50"><input type="checkbox" class="radar-issue mt-0.5" onchange="calculateBlockedOperationRadar()"><span>${label}</span></label>`).join('')}</div><div id="blocked-radar-result" class="p-4 rounded-2xl bg-green-light border border-green/20 text-xs"><strong class="text-green">Verde · Preparado para comercializar</strong><p class="mt-1 text-slate-600">No se han marcado incidencias.</p></div></div>`;
      if (item.id === 'document-checklist') return `<div class="space-y-3"><p class="text-xs text-slate-500">Selecciona las circunstancias del expediente.</p><div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">${['Existe hipoteca','Procede de herencia','Separación o divorcio','Existe usufructo','Hay inquilino vigente','Vivienda protegida','Varios titulares','Venta por sociedad'].map(label=>`<label class="flex items-start gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50"><input type="checkbox" class="doc-checklist-case mt-0.5" value="${label}"><span>${label}</span></label>`).join('')}</div><button onclick="generateDocumentChecklist()" class="w-full py-3 rounded-xl bg-blue text-white text-xs font-black">Generar checklist personalizado</button><div id="document-checklist-result" class="hidden p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600"></div></div>`;
      if (item.id === 'mortgage-scenarios') return `<div class="space-y-4"><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><label class="text-xs font-bold text-slate-500">Capital financiado (€)<input id="mortgage-capital" type="number" value="220000" oninput="calculateMortgageScenarios()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Plazo (años)<input id="mortgage-years" type="number" value="30" oninput="calculateMortgageScenarios()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Interés ordinario (%)<input id="mortgage-rate" type="number" value="2.8" step="0.1" oninput="calculateMortgageScenarios()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Escenario prudente (%)<input id="mortgage-rate-stress" type="number" value="4" step="0.1" oninput="calculateMortgageScenarios()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label></div><div id="mortgage-result" class="p-4 rounded-2xl bg-blue-light border border-blue/20"></div><p class="text-[10px] text-slate-400">Simulación informativa: no constituye una oferta bancaria ni una recomendación financiera personalizada.</p></div>`;
      if (item.id === 'rental-profitability') return `<div class="space-y-4"><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><label class="text-xs font-bold text-slate-500">Precio de compra (€)<input id="rent-price" type="number" value="180000" oninput="calculateRentalProfitability()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Renta mensual (€)<input id="rent-monthly" type="number" value="850" oninput="calculateRentalProfitability()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Gastos anuales (€)<input id="rent-costs" type="number" value="1800" oninput="calculateRentalProfitability()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">Meses vacíos estimados<input id="rent-vacancy" type="number" min="0" max="12" value="1" oninput="calculateRentalProfitability()" class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></label></div><div id="rent-profitability-result" class="p-4 rounded-2xl bg-green-light border border-green/20"></div></div>`;
      if (item.id === 'rental-ad-check') return `<div class="space-y-4"><label class="block text-xs font-bold text-slate-500">Texto del anuncio<textarea id="ad-check-text" rows="7" placeholder="Pega aquí el texto del anuncio para comprobar si incluye la información esencial..." class="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea></label><button onclick="generateRentalAdCheck()" class="w-full py-3 rounded-xl bg-blue text-white text-xs font-black">Revisar anuncio</button><div id="ad-check-result" class="hidden p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600"></div><p class="text-[10px] text-slate-400">Revisión preliminar. El control normativo definitivo debe adaptarse al tipo de operación, territorio y fecha de publicación.</p></div>`;
      return `<div class="p-5 rounded-2xl bg-slate-50 border border-slate-200"><span class="inline-flex px-2 py-1 rounded-full bg-slate-200 text-slate-600 text-[9px] font-black uppercase">Planificado</span><p class="text-sm text-slate-600 leading-relaxed mt-4">Esta utilidad está incluida en la planificación de producto y se activará cuando el flujo esté completo, revisado y preparado para uso profesional.</p><div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-4 text-[10px]"><span class="p-2 rounded-lg bg-white border border-slate-200"><b class="text-navy">Tiempo:</b><br>${escapeHTML(item.time)}</span><span class="p-2 rounded-lg bg-white border border-slate-200"><b class="text-navy">Resultado:</b><br>${escapeHTML(item.result)}</span><span class="p-2 rounded-lg bg-white border border-slate-200"><b class="text-navy">Acceso:</b><br>${accessLabels[item.access]}</span></div><button onclick="showToast('Interés registrado para priorizar este recurso.', 'success'); closeResourceToolModal();" class="w-full py-3 mt-4 rounded-xl bg-navy text-white text-xs font-black">Registrar interés</button></div>`;
    }

    function calculateSellerNet() {
      const price = Number(document.getElementById('seller-net-price')?.value) || 0;
      const feePct = Number(document.getElementById('seller-net-fee')?.value) || 0;
      const vatPct = Number(document.getElementById('seller-net-vat')?.value) || 0;
      const mortgage = Number(document.getElementById('seller-net-mortgage')?.value) || 0;
      const other = Number(document.getElementById('seller-net-other')?.value) || 0;
      const fee = price * feePct / 100;
      const vat = fee * vatPct / 100;
      const net = Math.max(0, price - fee - vat - mortgage - other);
      const result = document.getElementById('seller-net-result');
      if (result) result.innerHTML = `<span class="text-[10px] uppercase font-black text-green">Neto orientativo para el vendedor</span><strong class="block text-2xl font-black text-navy mt-1">${formatCurrency(net)}</strong><p class="text-[11px] text-slate-600 mt-2">Honorarios: ${formatCurrency(fee)} · IVA honorarios: ${formatCurrency(vat)} · Otros conceptos: ${formatCurrency(mortgage + other)}</p>`;
    }

    function calculateAdvancedFeeSplit() {
      const price = Number(document.getElementById('adv-fee-price')?.value) || 0;
      const pct = Number(document.getElementById('adv-fee-pct')?.value) || 0;
      const vat = Number(document.getElementById('adv-fee-vat')?.value) || 0;
      const shareA = Math.min(100, Math.max(0, Number(document.getElementById('adv-fee-share-a')?.value) || 0));
      const base = price * pct / 100;
      const tax = base * vat / 100;
      const gross = base + tax;
      const result = document.getElementById('adv-fee-result');
      if (result) result.innerHTML = `<span class="text-[10px] uppercase font-black text-blue">Liquidación orientativa</span><strong class="block text-xl font-black text-navy mt-1">Comisión base: ${formatCurrency(base)}</strong><p class="text-[11px] text-slate-600 mt-2">IVA: ${formatCurrency(tax)} · Total factura: ${formatCurrency(gross)}</p><div class="grid grid-cols-2 gap-2 mt-3"><span class="p-2 rounded-lg bg-white text-xs"><b>Captador ${shareA}%</b><br>${formatCurrency(base * shareA / 100)}</span><span class="p-2 rounded-lg bg-white text-xs"><b>Colaborador ${100-shareA}%</b><br>${formatCurrency(base * (100-shareA) / 100)}</span></div>`;
    }

    function calculateBlockedOperationRadar() {
      const selected = document.querySelectorAll('.radar-issue:checked').length;
      const result = document.getElementById('blocked-radar-result');
      if (!result) return;
      let state='Verde · Preparado para comercializar', color='green', text='No se han marcado incidencias relevantes.';
      if (selected >= 1 && selected <= 3) { state='Ámbar · Publicable con tareas pendientes'; color='amber'; text=`Se han detectado ${selected} incidencias. Conviene asignar responsables y plazos.`; }
      if (selected >= 4) { state='Rojo · Resolver antes de aceptar ofertas'; color='red'; text=`Se han detectado ${selected} incidencias. Es recomendable revisar el expediente antes de continuar.`; }
      result.className = `p-4 rounded-2xl ${color === 'green' ? 'bg-green-light border-green/20' : color === 'amber' ? 'bg-amber-light border-amber/20' : 'bg-red-50 border-red-200'} border text-xs`;
      result.innerHTML = `<strong class="${color === 'green' ? 'text-green' : color === 'amber' ? 'text-amber' : 'text-red-600'}">${state}</strong><p class="mt-1 text-slate-600">${text}</p>`;
    }

    function generateDocumentChecklist() {
      const selected = Array.from(document.querySelectorAll('.doc-checklist-case:checked')).map(input => input.value);
      const base = ['Documento de identidad de titulares', 'Nota simple actualizada', 'Título de propiedad', 'Certificado energético', 'Últimos recibos de IBI y comunidad'];
      const extra = [];
      if (selected.includes('Existe hipoteca')) extra.push('Certificado de deuda pendiente y condiciones de cancelación');
      if (selected.includes('Procede de herencia')) extra.push('Escritura de adjudicación de herencia y justificantes fiscales');
      if (selected.includes('Separación o divorcio')) extra.push('Convenio regulador o documentación que acredite facultades de disposición');
      if (selected.includes('Existe usufructo')) extra.push('Documentación del usufructo y consentimiento de intervinientes');
      if (selected.includes('Hay inquilino vigente')) extra.push('Contrato de arrendamiento, anexos y estado de pagos');
      if (selected.includes('Vivienda protegida')) extra.push('Documentación de protección y limitaciones aplicables');
      if (selected.includes('Varios titulares')) extra.push('Identificación y consentimiento de todos los titulares');
      if (selected.includes('Venta por sociedad')) extra.push('Escrituras societarias, representación y titular real');
      const result = document.getElementById('document-checklist-result');
      if (result) { result.classList.remove('hidden'); result.innerHTML = `<strong class="text-navy">Checklist generado</strong><ul class="mt-2 space-y-1 list-disc pl-4">${[...base, ...extra].map(item => `<li>${item}</li>`).join('')}</ul><p class="mt-3 text-[10px] text-slate-400">Lista orientativa para revisión profesional según el expediente concreto.</p>`; }
    }

    function generateRentalAdCheck() {
      const text = cleanText(document.getElementById('ad-check-text')?.value || '').toLowerCase();
      const checks = [
        ['Precio visible', /\d/.test(text) && (text.includes('€') || text.includes('eur') || text.includes('precio'))],
        ['Ubicación aproximada', ['zona','municipio','barrio','provincia','ubicación'].some(word => text.includes(word))],
        ['Superficie o metros', text.includes('m²') || text.includes('m2') || text.includes('metros')],
        ['Tipo de operación', ['venta','alquiler','arrendamiento'].some(word => text.includes(word))],
        ['Certificado energético', text.includes('energ') || text.includes('certificado')],
        ['Condiciones principales', ['condiciones','fianza','honorarios','gastos','comisión'].some(word => text.includes(word))]
      ];
      const completed = checks.filter(item => item[1]).length;
      const result = document.getElementById('ad-check-result');
      if (!result) return;
      result.classList.remove('hidden');
      result.innerHTML = `<strong class="text-navy">Revisión preliminar: ${completed}/${checks.length} criterios detectados</strong><ul class="mt-3 space-y-1">${checks.map(([label,ok]) => `<li class="flex items-center gap-2"><span>${ok ? '✓' : '○'}</span><span>${label}</span></li>`).join('')}</ul>`;
    }

    function monthlyMortgagePayment(capital, years, annualRate) {
      const months = years * 12; if (!months || !capital) return 0;
      const monthly = annualRate / 100 / 12;
      if (!monthly) return capital / months;
      return capital * monthly * Math.pow(1 + monthly, months) / (Math.pow(1 + monthly, months) - 1);
    }

    function calculateMortgageScenarios() {
      const capital = Number(document.getElementById('mortgage-capital')?.value) || 0;
      const years = Number(document.getElementById('mortgage-years')?.value) || 0;
      const rate = Number(document.getElementById('mortgage-rate')?.value) || 0;
      const stress = Number(document.getElementById('mortgage-rate-stress')?.value) || 0;
      const normalPayment = monthlyMortgagePayment(capital, years, rate);
      const stressPayment = monthlyMortgagePayment(capital, years, stress);
      const result = document.getElementById('mortgage-result');
      if (result) result.innerHTML = `<span class="text-[10px] uppercase font-black text-blue">Comparación mensual</span><div class="grid grid-cols-2 gap-2 mt-2"><span class="p-3 rounded-xl bg-white text-xs"><b>Escenario ordinario</b><strong class="block text-lg text-navy mt-1">${formatCurrency(normalPayment)}</strong></span><span class="p-3 rounded-xl bg-white text-xs"><b>Escenario prudente</b><strong class="block text-lg text-navy mt-1">${formatCurrency(stressPayment)}</strong></span></div><p class="text-[11px] text-slate-600 mt-2">Diferencia estimada: ${formatCurrency(Math.max(0, stressPayment-normalPayment))} al mes.</p>`;
    }

    function calculateRentalProfitability() {
      const price = Number(document.getElementById('rent-price')?.value) || 0;
      const monthly = Number(document.getElementById('rent-monthly')?.value) || 0;
      const costs = Number(document.getElementById('rent-costs')?.value) || 0;
      const vacancy = Math.min(12, Math.max(0, Number(document.getElementById('rent-vacancy')?.value) || 0));
      const income = monthly * (12-vacancy);
      const net = income - costs;
      const profitability = price ? net / price * 100 : 0;
      const result = document.getElementById('rent-profitability-result');
      if (result) result.innerHTML = `<span class="text-[10px] uppercase font-black text-green">Rentabilidad neta orientativa</span><strong class="block text-2xl font-black text-navy mt-1">${profitability.toFixed(2)}%</strong><p class="text-[11px] text-slate-600 mt-2">Ingresos estimados: ${formatCurrency(income)} · Gastos: ${formatCurrency(costs)} · Neto anual: ${formatCurrency(net)}</p>`;
    }


    // --- INTERCEPTOR DE CLICS DE NAVEGACIÓN GLOBAL ---
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a');
      if (link) {
        let href = link.getAttribute('href') || '';
        
        // Excluir enlaces externos, anclas locales puras o modales
        if (href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:') || link.target === '_blank') {
          return;
        }

        // Quitar hash si está presente
        if (href.startsWith('#/')) {
          href = href.substring(1);
        }
        
        // Extraer la ruta limpia
        let cleanPath = href;
        if (cleanPath.startsWith(window.location.origin)) {
          cleanPath = cleanPath.substring(window.location.origin.length);
        }
        if (CAPTACION_BASE_PATH && CAPTACION_BASE_PATH !== '/' && cleanPath.startsWith(CAPTACION_BASE_PATH)) {
          cleanPath = cleanPath.substring(CAPTACION_BASE_PATH.length);
        }
        
        // Limpiar prefijos relativos ./ y barras múltiples
        cleanPath = cleanPath.replace(/^\.?\/+/, '');
        cleanPath = '/' + cleanPath.replace(/\/+$/, '');
        if (cleanPath === '/' || cleanPath === '/index.php' || !cleanPath) {
          cleanPath = '/inicio';
        }

        let targetRoute = cleanPath;
        if (!routes[targetRoute]) {
          const segments = targetRoute.split('/').filter(Boolean);
          for (let i = segments.length - 1; i >= 0; i--) {
            const testRoute = '/' + segments[i];
            if (routes[testRoute]) {
              targetRoute = testRoute;
              break;
            }
          }
        }
        
        // Interceptar si es una ruta controlada por la SPA
        if (routes[targetRoute] || routes[cleanPath]) {
          const finalRoute = routes[targetRoute] ? targetRoute : cleanPath;
          e.preventDefault();
          toggleMenu(true);
          closeMobileActionSheet();
          document.body.style.overflow = '';
          navigateTo(finalRoute);
        }
      }
    }, true);


    // ==========================================
    // 13. PASARELA IA DEL AGENTE: BYO-AI REAL
    // ==========================================
    const AI_PROVIDER_CONFIG = {
      openai: { label: 'OpenAI', model: 'gpt-4o-mini', icon: '◉' },
      anthropic: { label: 'Anthropic', model: 'claude-3-5-haiku-latest', icon: '◈' },
      google: { label: 'Google', model: 'gemini-2.0-flash', icon: '✦' },
      groq: { label: 'Groq', model: 'llama-3.1-8b-instant', icon: '▣' },
      openrouter: { label: 'OpenRouter', model: 'openai/gpt-4o-mini', icon: '◎' },
      compatible: { label: 'Endpoint compatible', model: 'modelo-personalizado', icon: '⌘' }
    };
    let aiConnectionState = null;

    function getAIClientConfig() {
      return window.CAPTACION_APP_AI || {};
    }

    async function captacionAIRequest(path = '', method = 'GET', payload = null) {
      const config = getAIClientConfig();
      const headers = { 'Content-Type': 'application/json' };
      if (config.nonce) headers['X-WP-Nonce'] = config.nonce;
      const response = await fetch(`${config.restBase || ''}${path}`, {
        method,
        headers,
        credentials: 'same-origin',
        body: payload ? JSON.stringify(payload) : null
      });

      let body = {};
      try { body = await response.json(); } catch (error) {}
      if (!response.ok) {
        const message = body?.message || body?.data?.provider_message || 'No se pudo completar la solicitud de IA.';
        const err = new Error(message);
        err.status = response.status;
        err.payload = body;
        throw err;
      }
      return body;
    }

    function hasConnectedAI() {
      return !!(aiConnectionState && aiConnectionState.connected && aiConnectionState.connection && aiConnectionState.connection.active);
    }

    async function loadAIConnection(force = false) {
      if (aiConnectionState && !force) return aiConnectionState;
      if (!getAIClientConfig().isLoggedIn) {
        aiConnectionState = { connected: false, connection: null, authRequired: true };
        return aiConnectionState;
      }
      aiConnectionState = await captacionAIRequest('config');
      return aiConnectionState;
    }

    function resetAIConnectionForm() {
      const form = document.querySelector('#ai-connection-modal form');
      if (form) form.reset();
      const secretInput = document.getElementById('ai-secret-input');
      if (secretInput) {
        secretInput.required = true;
        secretInput.placeholder = 'Se almacenará cifrada para tu usuario';
      }
      const saveBtn = document.getElementById('ai-save-connection-btn');
      if (saveBtn) saveBtn.textContent = 'Guardar conexión';
      syncAIProviderDefaults();
    }

    function openAIConnectionModal(provider = 'openai') {
      const modal = document.getElementById('ai-connection-modal');
      const select = document.getElementById('ai-provider-select');
      const connection = aiConnectionState?.connection || null;
      if (select) select.value = (connection?.provider && AI_PROVIDER_CONFIG[connection.provider]) ? connection.provider : provider;
      document.getElementById('ai-connection-alias').value = connection?.alias || '';
      document.getElementById('ai-use-profile').value = connection?.profile || 'general';
      document.getElementById('ai-model-name').value = connection?.model || '';
      document.getElementById('ai-backend-endpoint').value = connection?.provider === 'compatible' ? (connection?.endpoint || '') : '';
      const secretInput = document.getElementById('ai-secret-input');
      if (secretInput) {
        secretInput.value = '';
        secretInput.required = !connection;
        secretInput.placeholder = connection ? 'Déjalo vacío para mantener la credencial actual' : 'Se almacenará cifrada para tu usuario';
      }
      const saveBtn = document.getElementById('ai-save-connection-btn');
      if (saveBtn) saveBtn.textContent = connection ? 'Actualizar conexión' : 'Guardar conexión';
      syncAIProviderDefaults();
      modal?.classList.remove('hidden');
    }

    function closeAIConnectionModal() {
      const modal = document.getElementById('ai-connection-modal');
      modal?.classList.add('hidden');
      resetAIConnectionForm();
      const confirmation = document.getElementById('ai-security-confirmation');
      if (confirmation) confirmation.checked = false;
    }

    function syncAIProviderDefaults() {
      const provider = document.getElementById('ai-provider-select')?.value || 'openai';
      const config = AI_PROVIDER_CONFIG[provider] || AI_PROVIDER_CONFIG.openai;
      const alias = document.getElementById('ai-connection-alias');
      const model = document.getElementById('ai-model-name');
      const endpointWrap = document.getElementById('ai-endpoint-wrap');
      if (alias && !alias.value.trim()) alias.value = `${config.label} · mi agencia`;
      if (model && !model.value.trim()) model.value = config.model;
      if (endpointWrap) endpointWrap.classList.toggle('hidden', provider !== 'compatible');
    }

    async function saveAIConnection(event) {
      event.preventDefault();
      if (!getAIClientConfig().isLoggedIn) {
        showToast('Debes iniciar sesión en WordPress para guardar tu conexión IA.', 'info');
        return;
      }
      const provider = document.getElementById('ai-provider-select')?.value || 'openai';
      const alias = cleanText(document.getElementById('ai-connection-alias')?.value || '');
      const profile = cleanText(document.getElementById('ai-use-profile')?.value || 'general');
      const model = cleanText(document.getElementById('ai-model-name')?.value || '');
      const endpoint = cleanText(document.getElementById('ai-backend-endpoint')?.value || '');
      const secret = document.getElementById('ai-secret-input')?.value || '';
      const saveBtn = document.getElementById('ai-save-connection-btn');
      const hasExisting = !!aiConnectionState?.connection;
      if (!alias || (!secret && !hasExisting)) {
        showToast('Completa el alias y la credencial para guardar la conexión.', 'info');
        return;
      }
      if (provider === 'compatible' && !endpoint) {
        showToast('Indica un endpoint compatible con OpenAI para este proveedor.', 'info');
        return;
      }
      const original = saveBtn?.innerHTML || '';
      if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = 'Guardando...';
      }
      try {
        await captacionAIRequest('config', 'POST', {
          provider,
          alias,
          profile,
          model,
          endpoint,
          api_key: secret,
          active: true
        });
        await loadAIConnection(true);
        closeAIConnectionModal();
        renderAIConnections();
        showToast('Conexión IA guardada correctamente.', 'success');
      } catch (error) {
        showToast(error.message || 'No se pudo guardar la conexión IA.', 'info');
      } finally {
        if (saveBtn) {
          saveBtn.disabled = false;
          saveBtn.innerHTML = original;
        }
      }
    }

    async function removeAIConnection() {
      if (!getAIClientConfig().isLoggedIn) return;
      try {
        await captacionAIRequest('config', 'DELETE');
        aiConnectionState = { connected: false, connection: null };
        renderAIConnections();
        showToast('Configuración IA eliminada.', 'success');
      } catch (error) {
        showToast(error.message || 'No se pudo eliminar la configuración IA.', 'info');
      }
    }

    async function testAIConnection() {
      if (!getAIClientConfig().isLoggedIn) return;
      try {
        const result = await captacionAIRequest('test', 'POST', {});
        aiConnectionState = { connected: true, connection: result.connection || aiConnectionState?.connection || null };
        renderAIConnections();
        showToast(result.message || 'Conexión IA validada correctamente.', 'success');
      } catch (error) {
        showToast(error.payload?.data?.provider_message || error.message || 'La prueba de conexión falló.', 'info');
      }
    }

    async function setAIConnectionStatus(active) {
      if (!getAIClientConfig().isLoggedIn) return;
      try {
        const result = await captacionAIRequest('config/status', 'POST', { active: !!active });
        aiConnectionState = { connected: !!result.connection, connection: result.connection || null };
        renderAIConnections();
        showToast(active ? 'Conexión IA activada.' : 'Conexión IA desactivada.', 'success');
      } catch (error) {
        showToast(error.message || 'No se pudo actualizar el estado de la conexión IA.', 'info');
      }
    }

    async function renderAIConnections() {
      const container = document.getElementById('ai-connections-list');
      if (!container) return;
      if (!getAIClientConfig().isLoggedIn) {
        container.innerHTML = `<div class="p-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 text-xs text-slate-500 leading-relaxed">Debes iniciar sesión en WordPress para guardar y utilizar una conexión IA personal.</div>`;
        return;
      }
      container.innerHTML = `<div class="p-4 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-500">Cargando configuración IA...</div>`;
      try {
        const state = await loadAIConnection(true);
        const connection = state?.connection || null;
        if (!connection) {
          container.innerHTML = `<div class="p-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 text-xs text-slate-500 leading-relaxed">Todavía no has configurado un proveedor IA. Usa <strong>Conectar IA</strong> para activar funciones asistidas con tu propia cuenta.</div>`;
          return;
        }
        const provider = AI_PROVIDER_CONFIG[connection.provider] || AI_PROVIDER_CONFIG.compatible;
        const validatedAt = connection.last_validated_at ? new Date(connection.last_validated_at * 1000).toLocaleString('es-ES') : 'Pendiente';
        const statusLabel = connection.status === 'connected' ? 'Conectado' : (connection.status === 'error' ? 'Error' : (connection.active ? 'Configurado' : 'Desactivado'));
        const statusClass = connection.status === 'connected'
          ? 'bg-green-light text-green'
          : (connection.status === 'error' ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600');
        container.innerHTML = `
          <article class="ai-connection-card flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
              <span class="flex w-10 h-10 shrink-0 items-center justify-center rounded-xl bg-blue-light text-blue font-black">${provider.icon}</span>
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <strong class="text-sm text-navy">${escapeHTML(connection.alias || provider.label)}</strong>
                  <span class="px-2 py-1 rounded-full ${statusClass} text-[9px] font-black uppercase">${escapeHTML(statusLabel)}</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">${escapeHTML(connection.provider_label || provider.label)} · ${escapeHTML(connection.model || provider.model)} · Huella ${escapeHTML(connection.fingerprint || 'N/D')}</p>
                <p class="text-[10px] text-slate-400 mt-1">Perfil: ${escapeHTML(connection.profile || 'general')} · Última validación: ${escapeHTML(validatedAt)}</p>
                ${connection.last_error ? `<p class="text-[10px] text-red-600 mt-1">${escapeHTML(connection.last_error)}</p>` : ''}
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <button type="button" onclick="testAIConnection()" class="px-3 py-2 rounded-lg bg-blue text-white text-[10px] font-bold">Probar conexión</button>
              <button type="button" onclick="setAIConnectionStatus(${connection.active ? 'false' : 'true'})" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-[10px] font-bold">${connection.active ? 'Desactivar' : 'Activar'}</button>
              <button type="button" onclick="removeAIConnection()" class="px-3 py-2 rounded-lg border border-red-200 bg-red-50 text-red-600 text-[10px] font-bold">Eliminar</button>
            </div>
          </article>`;
      } catch (error) {
        container.innerHTML = `<div class="p-4 rounded-xl border border-red-200 bg-red-50 text-xs text-red-600 leading-relaxed">${escapeHTML(error.message || 'No se pudo cargar la configuración IA.')}</div>`;
      }
    }

    // ==========================================
    // 13. APARIENCIA GLOBAL: MODO CLARO / OSCURO
    // ==========================================
    function getCurrentTheme() {
      const storedTheme = localStorage.getItem('captacion_theme_v1') || localStorage.getItem('theme');
      if (storedTheme === 'dark' || storedTheme === 'light') return storedTheme;
      const isDark = document.documentElement.dataset.theme === 'dark' || document.documentElement.classList.contains('dark');
      return isDark ? 'dark' : 'light';
    }

    function syncThemeToggleButtons() {
      const isDark = getCurrentTheme() === 'dark';
      const desktopButton = document.getElementById('theme-toggle-desktop');
      const mobileButton = document.getElementById('theme-toggle-mobile');
      const desktopIcon = document.getElementById('theme-toggle-desktop-icon');
      const mobileIcon = document.getElementById('theme-toggle-mobile-icon');
      const nextTheme = isDark ? 'claro' : 'oscuro';
      [desktopButton, mobileButton].forEach(button => {
        if (!button) return;
        button.setAttribute('aria-pressed', String(isDark));
        button.setAttribute('title', `Cambiar a modo ${nextTheme}`);
        button.setAttribute('aria-label', `Cambiar a modo ${nextTheme}`);
      });
      if (desktopIcon) desktopIcon.textContent = isDark ? '🌙' : '☀';
      if (mobileIcon) mobileIcon.textContent = isDark ? '🌙' : '☀';
      const themeMeta = document.getElementById('theme-color-meta');
      if (themeMeta) themeMeta.setAttribute('content', isDark ? '#07111f' : '#eef3f8');
      setTimeout(() => {
        homeMap?.invalidateSize?.();
        marketplaceMap?.invalidateSize?.();
        needsMap?.invalidateSize?.();
      }, 80);
    }

    function applyTheme(theme, persist = true) {
      const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
      document.documentElement.dataset.theme = normalizedTheme;
      document.documentElement.classList.toggle('dark', normalizedTheme === 'dark');
      if (document.body) {
        document.body.classList.toggle('dark', normalizedTheme === 'dark');
        document.body.classList.toggle('dark-mode-active', normalizedTheme === 'dark');
      }
      if (persist) {
        try {
          localStorage.setItem('captacion_theme_v1', normalizedTheme);
          localStorage.setItem('theme', normalizedTheme);
        } catch (error) {}
      }
      syncThemeToggleButtons();
    }

    function toggleTheme() {
      const isDark = getCurrentTheme() === 'dark';
      const targetTheme = isDark ? 'light' : 'dark';
      applyTheme(targetTheme);
      if (typeof showToast === 'function') {
        showToast(`Modo ${targetTheme === 'dark' ? 'oscuro' : 'claro'} activado.`, 'info');
      }
    }

    window.toggleTheme = toggleTheme;
    window.applyTheme = applyTheme;
    window.getCurrentTheme = getCurrentTheme;



    // ==========================================
    // 14. DASHBOARD PRIVADO INTEGRADO DEL AGENTE
    // ==========================================
    const PRIVATE_DASHBOARD_STORAGE_KEY = 'captacion_agent_private_dashboard_v2';
    function privateDashboardStorageKey() {
      const id = Number(CAPTACION_API?.currentUserId || 0);
      const email = String(getDemoSession?.()?.email || CAPTACION_MAILCHIMP?.currentUser?.email || 'guest').toLowerCase().replace(/[^a-z0-9@._-]/g, '');
      return `${PRIVATE_DASHBOARD_STORAGE_KEY}_${id || email || 'guest'}`;
    }
    let privateDashboardPanel = 'overview';
    let lastPrivateDashboardPanel = '';
    let privateDashboardFocus = 'general';
    let executivePeriod = '30d';
    let privateMatchesMode = 'offers';

    function createPrivateDashboardSeed() {
      return {
        operations: [],
        favorites: [],
        tasks: [],
        notifications: [],
        activities: [],
        requestsReceived: [],
        requestsSent: [],
        clients: [],
        leads: []
      };
    }

    function getPrivateDashboardState() {
      try {
        const stored = JSON.parse(localStorage.getItem(privateDashboardStorageKey()));
        if (stored && Array.isArray(stored.operations) && Array.isArray(stored.tasks)) return normalizePrivateDashboardState(stored);
      } catch (error) {}
      const seed = normalizePrivateDashboardState(createPrivateDashboardSeed());
      persistPrivateDashboardState(seed);
      return seed;
    }

    function persistPrivateDashboardState(state) {
      try { localStorage.setItem(privateDashboardStorageKey(), JSON.stringify(normalizePrivateDashboardState(state))); } catch (error) {}
    }

    function inferDueTimestamp(task = {}) {
      if (Number(task.dueAt)) return Number(task.dueAt);
      const base = Date.now();
      const dueText = normalizeMatchText(task.due || '');
      if (dueText.includes('hoy')) return base + 3600000 * 6;
      if (dueText.includes('mañ') || dueText.includes('man')) return base + 86400000;
      if (dueText.includes('semana')) return base + 86400000 * 3;
      return base + 86400000 * 2;
    }

    function normalizePrivateDashboardState(state = {}) {
      state.tasks = Array.isArray(state.tasks) ? state.tasks.map(task => ({ ...task, dueAt: inferDueTimestamp(task) })) : [];
      state.notifications = Array.isArray(state.notifications) ? state.notifications.map(item => ({ ...item, dueAt: Number(item.dueAt) || Number(item.createdAt) || Date.now() })) : [];
      state.operations = Array.isArray(state.operations) ? state.operations : [];
      state.activities = Array.isArray(state.activities) ? state.activities : [];
      state.requestsReceived = Array.isArray(state.requestsReceived) ? state.requestsReceived : [];
      state.requestsSent = Array.isArray(state.requestsSent) ? state.requestsSent : [];
      state.favorites = Array.isArray(state.favorites) ? state.favorites : [];
      state.clients = Array.isArray(state.clients) ? state.clients : [];
      state.leads = Array.isArray(state.leads) ? state.leads : [];
      state.fiscalProfile = state.fiscalProfile && typeof state.fiscalProfile === 'object' ? state.fiscalProfile : {};
      return state;
    }

    function currentPrivateUserEmail() { return (getDemoSession?.()?.email || CAPTACION_MAILCHIMP?.currentUser?.email || '').toLowerCase(); }
    function currentPrivateUserId() { return Number(window.CAPTACION_API?.currentUserId || 0); }
    function isOwnedByCurrentUser(item = {}) {
      const currentUserId = currentPrivateUserId();
      const ownerId = Number(item.ownerUserId || item.owner_user_id || item.userId || item.user_id || 0);
      if (currentUserId && ownerId) return ownerId === currentUserId;
      const email = currentPrivateUserEmail();
      if (!email) return false;
      const itemEmail = String(item.userEmail || item.user_email || item.ownerEmail || '').toLowerCase();
      return Boolean(itemEmail) && itemEmail === email;
    }
    function getHiddenXmlFeedIds() {
      return new Set((window.CAPTACION_XML_BATCHES || []).filter(batch => ['paused','pending_deletion','deleted'].includes(batch.status)).map(batch => batch.import_batch_id));
    }
    function isFromHiddenXmlFeed(item = {}) {
      const batchId = item.importBatchId || item.import_batch_id || item.feed_id || '';
      return batchId && getHiddenXmlFeedIds().has(batchId);
    }
    function isMarketplaceVisibleProperty(property = {}) {
      return !isFromHiddenXmlFeed(property);
    }
    function privateProperties() { 
      const list = properties.filter(isOwnedByCurrentUser); 
      return list.length ? list : properties.slice(0, 10);
    }
    function visiblePrivateProperties() { return privateProperties().filter(isMarketplaceVisibleProperty); }
    function privateNeeds() { 
      const list = needs.filter(isOwnedByCurrentUser); 
      return list.length ? list : needs.slice(0, 10);
    }
    function privatePropertyById(id) { return (properties || []).find(item => String(item.id) === String(id)) || null; }
    function privateNeedById(id) { return (needs || []).find(item => String(item.id) === String(id)) || null; }
    function privateStatusClasses(status = '') {
      const normalized = String(status).toLowerCase();
      if (normalized.includes('complet') || normalized.includes('disponible') || normalized.includes('desbloque')) return 'bg-green-light text-green';
      if (normalized.includes('cancel') || normalized.includes('rechaz')) return 'bg-red-50 text-red-600';
      if (normalized.includes('pendiente') || normalized.includes('nda') || normalized.includes('pago')) return 'bg-amber-light text-amber';
      return 'bg-blue-light text-blue';
    }
    function privatePriorityClasses(priority = 'low') { return priority === 'high' ? 'private-priority-high' : priority === 'medium' ? 'private-priority-medium' : 'private-priority-low'; }
    function privatePriorityLabel(priority = 'low') { return priority === 'high' ? 'Alta' : priority === 'medium' ? 'Media' : 'Normal'; }
    function privateSafeDate(value) { return new Date(Number(value) || Date.now()).toLocaleDateString('es-ES', { day:'2-digit', month:'2-digit', year:'numeric' }); }

    function addPrivateNotification(entry = {}) {
      const state = getPrivateDashboardState();
      const dedupeKey = entry.dedupeKey || '';
      if (dedupeKey && (state.notifications || []).some(item => item.dedupeKey === dedupeKey)) return false;
      const notification = {
        id: `NOT-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
        category: entry.category || 'Sistema',
        title: entry.title || 'Aviso operativo',
        detail: entry.detail || '',
        createdAt: Date.now(),
        dueAt: Number(entry.dueAt) || Date.now(),
        read: false,
        target: entry.target || 'overview',
        propertyId: entry.propertyId || '',
        needId: entry.needId || '',
        dedupeKey
      };
      state.notifications.unshift(notification);
      persistWpRecord('notification', notification, { recordKey: notification.id, title: notification.title, status: notification.read ? 'read' : 'unread', relatedId: dedupeKey });
      persistPrivateDashboardState(state);
      return true;
    }

    function addPrivateTask(entry = {}) {
      const state = getPrivateDashboardState();
      const dedupeKey = entry.dedupeKey || '';
      if (dedupeKey && (state.tasks || []).some(item => item.dedupeKey === dedupeKey)) return false;
      state.tasks.unshift({
        id: `TASK-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
        title: entry.title || 'Seguimiento pendiente',
        detail: entry.detail || '',
        priority: entry.priority || 'medium',
        due: entry.due || 'Próximamente',
        dueAt: Number(entry.dueAt) || inferDueTimestamp({ due: entry.due || 'Próximamente' }),
        status: 'pending',
        target: entry.target || 'tasks',
        dedupeKey
      });
      persistPrivateDashboardState(state);
      return true;
    }

    function addPrivateActivity(icon, title, detail) {
      const state = getPrivateDashboardState();
      const activity = { id:`ACT-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`, icon, title, detail, createdAt:Date.now() };
      state.activities.unshift(activity);
      persistWpRecord('activity', activity, { recordKey: activity.id, title: activity.title, status: 'logged' });
      persistPrivateDashboardState(state);
    }

    function syncAlertsForProperty(property) {
      const matches = getCompatibleNeedsForProperty(property, 8);
      if (!matches.length) {
        persistWpRecord('smart_match', { id:`watch-property-${property.id}`, kind:'property_without_match', propertyId:property.id, matches:[], createdAt:Date.now() }, { recordKey:`watch-property-${property.id}`, title: property.title || property.reference || property.id, status:'watching', relatedId: property.id });
        sendNotificationEmail('no_match_watch', { reference: property.reference || property.title || property.id, message: 'Captacion publicada sin coincidencias inmediatas.' });
        return;
      }
      const top = matches[0];
      persistWpRecord('smart_match', { id:`match-property-${property.id}`, kind:'property_match', propertyId:property.id, topScore:top.score, matches, createdAt:Date.now() }, { recordKey:`match-property-${property.id}`, title: property.title || property.reference || property.id, status:'detected', relatedId: property.id });
      sendNotificationEmail('match_property', { reference: property.reference || property.title || property.id, message: `${matches.length} demanda${matches.length === 1 ? '' : 's'} compatible${matches.length === 1 ? '' : 's'} detectada${matches.length === 1 ? '' : 's'}. Match principal: ${top.score}%.` });
      addPrivateNotification({
        category: 'Oportunidades',
        title: 'Nueva captación con demanda compatible',
        detail: `${property.title} encaja con ${matches.length} demanda${matches.length === 1 ? '' : 's'} activa${matches.length === 1 ? '' : 's'}. Match principal: ${top.score}%.`,
        target: 'offers',
        dueAt: Date.now() + 3600000 * 6,
        dedupeKey: `prop-match-${property.id}`
      });
      addPrivateTask({
        title: 'Revisar nueva captación vinculable',
        detail: `Valora ${property.title} frente a ${matches.length} demanda${matches.length === 1 ? '' : 's'} compatible${matches.length === 1 ? '' : 's'}.`,
        priority: top.score >= 75 ? 'high' : 'medium',
        due: 'Hoy',
        dueAt: Date.now() + 3600000 * 8,
        target: 'offers',
        dedupeKey: `task-prop-match-${property.id}`
      });
      addPrivateActivity('✦', 'Captación enlazada automáticamente', 'La plataforma detectó nuevas demandas compatibles con una publicación reciente.');
    }

    function syncAlertsForNeed(need) {
      const matches = getCompatiblePropertiesForNeed(need, 8);
      if (!matches.length) {
        persistWpRecord('smart_match', { id:`watch-need-${need.id}`, kind:'need_without_match', needId:need.id, matches:[], createdAt:Date.now() }, { recordKey:`watch-need-${need.id}`, title: need.title || need.id, status:'watching', relatedId: need.id });
        sendNotificationEmail('no_match_watch', { reference: need.title || need.id, message: 'Demanda publicada sin coincidencias inmediatas.' });
        return;
      }
      const top = matches[0];
      persistWpRecord('smart_match', { id:`match-need-${need.id}`, kind:'need_match', needId:need.id, topScore:top.score, matches, createdAt:Date.now() }, { recordKey:`match-need-${need.id}`, title: need.title || need.id, status:'detected', relatedId: need.id });
      sendNotificationEmail('match_need', { reference: need.title || need.id, message: `${matches.length} captacion${matches.length === 1 ? '' : 'es'} compatible${matches.length === 1 ? '' : 's'} detectada${matches.length === 1 ? '' : 's'}. Match principal: ${top.score}%.` });
      addPrivateNotification({
        category: 'Demandas',
        title: 'Nueva búsqueda con oferta compatible',
        detail: `${need.title} tiene ${matches.length} captación${matches.length === 1 ? '' : 'es'} compatible${matches.length === 1 ? '' : 's'}. Match principal: ${top.score}%.`,
        target: 'demands',
        dueAt: Date.now() + 3600000 * 6,
        dedupeKey: `need-match-${need.id}`
      });
      addPrivateTask({
        title: 'Revisar demanda con oferta enlazada',
        detail: `La necesidad ${need.title} ya cuenta con producto compatible para revisar.`,
        priority: top.score >= 75 ? 'high' : 'medium',
        due: 'Hoy',
        dueAt: Date.now() + 3600000 * 8,
        target: 'demands',
        dedupeKey: `task-need-match-${need.id}`
      });
      addPrivateActivity('🔔', 'Demanda enlazada automáticamente', 'Se generó una alerta interna por coincidencia entre búsqueda y captación.');
    }

    function getAgendaEntries(state = getPrivateDashboardState()) {
      const tasks = (state.tasks || []).filter(item => item.status !== 'done').map(item => ({
        kind: 'task',
        title: item.title,
        detail: item.detail,
        timestamp: Number(item.dueAt) || inferDueTimestamp(item),
        target: item.target || 'tasks',
        badge: privatePriorityLabel(item.priority || 'low')
      }));
      const alerts = (state.notifications || []).map(item => ({
        kind: 'alert',
        title: item.title,
        detail: item.detail,
        timestamp: Number(item.dueAt) || Number(item.createdAt) || Date.now(),
        target: item.target || 'notifications',
        badge: item.category || 'Aviso'
      }));
      return [...tasks, ...alerts].sort((a, b) => a.timestamp - b.timestamp);
    }

    function renderPrivateAgendaCalendar(calendarId, eventsId, limit = 6) {
      const calendar = document.getElementById(calendarId);
      const events = document.getElementById(eventsId);
      if (!calendar || !events) return;
      const state = getPrivateDashboardState();
      const entries = getAgendaEntries(state);
      const base = new Date();
      const year = base.getFullYear();
      const month = base.getMonth();
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const startWeekday = (firstDay.getDay() + 6) % 7;
      const daysInMonth = lastDay.getDate();
      const todayKey = new Date().toISOString().slice(0, 10);
      const grouped = entries.reduce((acc, item) => {
        const key = new Date(item.timestamp).toISOString().slice(0, 10);
        (acc[key] = acc[key] || []).push(item);
        return acc;
      }, {});
      const labels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'].map(label => `<span class="text-[10px] font-black text-slate-400 text-center">${label}</span>`).join('');
      const cells = [];
      for (let i = 0; i < startWeekday; i++) cells.push('<div></div>');
      for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        const key = date.toISOString().slice(0, 10);
        const items = grouped[key] || [];
        const classes = ['private-calendar-day'];
        if (key === todayKey) classes.push('is-today');
        if (items.length) classes.push('is-active');
        const dots = items.slice(0, 3).map(item => `<span class="private-calendar-dot ${item.kind === 'task' ? 'task' : 'alert'}"></span>`).join('');
        cells.push(`<button type="button" onclick="focusAgendaDate('${key}')" class="${classes.join(' ')} text-left"><span class="text-[11px] font-bold text-navy">${day}</span><span class="flex flex-wrap gap-1 mt-auto">${dots}</span></button>`);
      }
      calendar.innerHTML = `<div><div class="flex items-center justify-between mb-3"><strong class="text-sm text-navy">${base.toLocaleDateString('es-ES', { month:'long', year:'numeric' })}</strong><span class="text-[10px] text-slate-400">Tareas y alertas</span></div><div class="private-calendar-grid mb-2">${labels}</div><div class="private-calendar-grid">${cells.join('')}</div></div>`;
      const nextEntries = entries.slice(0, limit);
      events.innerHTML = nextEntries.length ? nextEntries.map(item => `<article class="private-mini-card"><div class="flex items-start justify-between gap-3"><div><strong class="block text-xs text-navy">${escapeHTML(item.title)}</strong><span class="block text-[10px] text-slate-500 mt-1">${new Date(item.timestamp).toLocaleDateString('es-ES', { day:'2-digit', month:'2-digit' })} · ${escapeHTML(item.badge)}</span><p class="text-[11px] text-slate-500 mt-2">${escapeHTML(item.detail)}</p></div><button onclick="switchPrivateDashboardPanel('${item.target}')" class="text-[10px] font-bold text-blue shrink-0">Abrir</button></div></article>`).join('') : `<p class="text-xs text-slate-500">No hay elementos pendientes por fecha.</p>`;
      window.__captacionAgendaEntries = grouped;
    }

    function focusAgendaDate(dateKey) {
      const grouped = window.__captacionAgendaEntries || {};
      const list = grouped[dateKey] || [];
      const overview = document.getElementById('private-overview-calendar-events');
      const tasks = document.getElementById('private-tasks-calendar-events');
      const html = list.length ? list.map(item => `<article class="private-mini-card"><div class="flex items-start justify-between gap-3"><div><strong class="block text-xs text-navy">${escapeHTML(item.title)}</strong><span class="block text-[10px] text-slate-500 mt-1">${new Date(item.timestamp).toLocaleString('es-ES', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' })}</span><p class="text-[11px] text-slate-500 mt-2">${escapeHTML(item.detail)}</p></div><button onclick="switchPrivateDashboardPanel('${item.target}')" class="text-[10px] font-bold text-blue shrink-0">Abrir</button></div></article>`).join('') : `<p class="text-xs text-slate-500">No hay eventos para esta fecha.</p>`;
      if (overview) overview.innerHTML = html;
      if (tasks) tasks.innerHTML = html;
    }

    function exportPrivateAgendaCalendar() {
      const entries = getAgendaEntries().slice(0, 12);
      if (!entries.length) {
        showToast('No hay eventos pendientes para exportar.', 'info');
        return;
      }
      const lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Compra Captación//Agenda Demo//ES'];
      entries.forEach((item, index) => {
        const start = new Date(item.timestamp);
        const end = new Date(item.timestamp + 3600000);
        const toIcs = date => date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
        lines.push('BEGIN:VEVENT', `UID:AGENDA-${index}-${Date.now()}@captacion.app`, `DTSTAMP:${toIcs(new Date())}`, `DTSTART:${toIcs(start)}`, `DTEND:${toIcs(end)}`, `SUMMARY:${item.title}`, `DESCRIPTION:${item.detail}`, 'END:VEVENT');
      });
      lines.push('END:VCALENDAR');
      const blob = new Blob([lines.join('\r\n')], { type:'text/calendar;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `captacion-app-agenda-${Date.now()}.ics`;
      link.click();
      URL.revokeObjectURL(url);
      showToast('Agenda exportada al calendario.', 'success');
    }

    function triggerPageTransitionLoader() {
      if (typeof document === 'undefined') return;
      let loader = document.getElementById('captacion-page-loader');
      if (!loader) {
        loader = document.createElement('div');
        loader.id = 'captacion-page-loader';
        document.body.appendChild(loader);
      }
      loader.classList.remove('done');
      loader.classList.add('loading');
      setTimeout(() => {
        loader.classList.add('done');
        setTimeout(() => {
          loader.classList.remove('loading', 'done');
        }, 320);
      }, 260);
    }

    function renderPrivateCalendar() {
      const calContainer = document.getElementById('private-tasks-calendar');
      const eventsContainer = document.getElementById('private-tasks-calendar-events');
      if (!calContainer && !eventsContainer) return;
      const state = getPrivateDashboardState();
      const tasks = state.tasks || [];
      if (calContainer) {
        const today = new Date();
        const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        calContainer.innerHTML = `
          <div class="flex items-center justify-between mb-3">
            <strong class="text-xs font-black text-navy dark:text-white uppercase tracking-wider">${monthNames[today.getMonth()]} ${today.getFullYear()}</strong>
            <span class="text-[10px] font-bold text-blue bg-blue/10 px-2 py-0.5 rounded-full">Agenda sincronizada</span>
          </div>
          <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-slate-400 mb-1">
            <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
          </div>
          <div class="grid grid-cols-7 gap-1 text-center text-xs">
            ${Array.from({length: 31}, (_, i) => {
              const day = i + 1;
              const isToday = day === today.getDate();
              const hasTask = tasks.some(t => day === (new Date(t.dueAt || Date.now()).getDate()));
              return `<div class="p-1.5 rounded-lg ${isToday ? 'bg-blue text-white font-black shadow-xs' : hasTask ? 'bg-blue-light/50 dark:bg-slate-800 text-blue font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'}">${day}</div>`;
            }).join('')}
          </div>
        `;
      }
      if (eventsContainer) {
        eventsContainer.innerHTML = tasks.length ? tasks.slice(0, 5).map(task => `
          <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3">
            <div>
              <strong class="block text-xs text-navy dark:text-white">${escapeHTML(task.title)}</strong>
              <span class="text-[10px] text-slate-400">${escapeHTML(task.due || 'Hoy')} · Prioridad ${privatePriorityLabel(task.priority)}</span>
            </div>
            <button type="button" onclick="completePrivateTask('${task.id}')" class="px-2.5 py-1 rounded-lg text-[10px] font-bold ${task.status === 'done' ? 'bg-green-light text-green' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200'}">${task.status === 'done' ? '✓ Hecha' : 'Completar'}</button>
          </div>
        `).join('') : '<p class="text-xs text-slate-400">No hay eventos ni visitas programadas para hoy.</p>';
      }
    }

    function renderCommunicationSubscriptions() {
      if (typeof window.renderCommunicationModules === 'function') {
        try { window.renderCommunicationModules(); } catch(e) { console.warn(e); }
      }
    }
    function renderCommunicationThreads() {
      if (typeof window.renderCommunicationModules === 'function') {
        try { window.renderCommunicationModules(); } catch(e) { console.warn(e); }
      }
    }
    function renderCommunicationTrace() {
      if (typeof window.renderCommunicationTrace === 'function') {
        try { window.renderCommunicationTrace(); } catch(e) { console.warn(e); }
      } else if (typeof window.renderCommunicationModules === 'function') {
        try { window.renderCommunicationModules(); } catch(e) { console.warn(e); }
      }
    }

    function switchPrivateDashboardPanel(panel = 'overview', updateUrl = true) {
      const session = getDemoSession();
      if (!session) {
        showToast('Para acceder al panel privado debes identificarte o registrarte como profesional.', 'info');
        navigateTo('/');
        return;
      }
      if (panel === 'contact_modal') {
        openContactSupportModal();
        return;
      }
      try { triggerPageTransitionLoader(); } catch(e){}
      if (privateDashboardPanel !== panel && privateDashboardPanel !== 'overview') lastPrivateDashboardPanel = privateDashboardPanel;
      privateDashboardPanel = panel;
      const groupedPanel = ({data:'feeds', notifications:'requests'})[panel] || panel;
      const privateArea = document.getElementById('page-area-privada');
      if (privateArea) {
        privateArea.classList.toggle('executive-mode', panel === 'overview');
        privateArea.classList.toggle('credits-view-active', panel === 'credits');
      }
      
      // Conmutar visibilidad de paneles con estilo directo inline como garantía absoluta
      document.querySelectorAll('.private-dashboard-panel').forEach(item => {
        const isActive = item.id === `private-panel-${panel}` || (panel === 'credits' && item.id === 'cmc-credits-panel');
        item.classList.toggle('active', isActive);
        if (isActive) {
          item.style.setProperty('display', 'block', 'important');
        } else {
          item.style.setProperty('display', 'none', 'important');
        }
      });
      
      // Conmutar botones activos del sidebar
      document.querySelectorAll('[data-private-panel]').forEach(button => {
        button.classList.toggle('active', button.dataset.privatePanel === panel || button.dataset.privatePanel === groupedPanel);
      });
      
      const mobile = document.getElementById('private-dashboard-mobile-select');
      if (mobile && panel !== 'contact_modal') mobile.value = panel;

      // Deep Linking: actualizar URL del navegador
      const panelToSubPath = {
        'overview': '/area-privada/resumen',
        'academy': '/area-privada/academia',
        'offers': '/area-privada/mis-captaciones',
        'demands': '/area-privada/mis-demandas',
        'credits': '/area-privada/creditos',
        'requests': '/area-privada/solicitudes',
        'operations': '/area-privada/operaciones',
        'favorites': '/area-privada/favoritos',
        'tasks': '/area-privada/calendario',
        'subscriptions': '/area-privada/suscripciones',
        'communications': '/area-privada/mensajes',
        'traceability': '/area-privada/trazabilidad',
        'feeds': '/area-privada/importaciones',
        'referrals': '/area-privada/referidos',
        'ai': '/area-privada/ia-configuracion',
        'profile': '/area-privada/perfil'
      };
      if (updateUrl && panelToSubPath[panel]) {
        try {
          const canonicalBase = (typeof CAPTACION_BASE_PATH === 'string' && CAPTACION_BASE_PATH !== '/') ? CAPTACION_BASE_PATH.replace(/\/+$/, '') : '';
          const fullUrl = canonicalBase + panelToSubPath[panel];
          if (window.location.pathname !== fullUrl) {
            window.history.pushState(null, '', fullUrl);
          }
        } catch(e){}
      }
      
      // Renderizadores específicos de cada sección protegidos contra excepciones
      try {
        if (panel === 'overview') { renderDashboard(); try { updateExecutiveDashboard(); } catch(e){} }
        else if (panel === 'offers') { renderPrivateOffers(); }
        else if (panel === 'demands') { renderPrivateDemands(); }
        else if (panel === 'requests') { renderPrivateRequests(); }
        else if (panel === 'operations') { renderPrivateOperations(); }
        else if (panel === 'favorites') { renderPrivateFavorites(); }
        else if (panel === 'tasks') { renderPrivateTasks(); renderPrivateCalendar(); }
        else if (panel === 'subscriptions') { renderCommunicationSubscriptions(); }
        else if (panel === 'communications') { renderCommunicationThreads(); }
        else if (panel === 'traceability') { renderCommunicationTrace(); }
        else if (panel === 'feeds') { try{loadPrivateXmlUrl();}catch(e){} try{renderPrivateXmlFeeds();}catch(e){} try{loadXmlFeeds();}catch(e){} }
        else if (panel === 'data') { try{loadXmlFeeds();}catch(e){} }
        else if (panel === 'referrals') { loadPLGReferralData(); }
        else if (panel === 'ai') { renderAIConnections(); }
        else if (panel === 'credits') { loadAndRenderCreditsLedger(); }
        else if (panel === 'profile') { renderPrivateFiscalProfile(); }
        else { renderDashboard(); }
      } catch(err) {
        console.warn('Error rendering panel ' + panel + ':', err);
      }
    }
    window.switchPrivateDashboardPanel = switchPrivateDashboardPanel;

    function setCalculatorCommission(pct, triggerFlash = true) {
      const slider = document.getElementById('calc-commission-slider');
      const numericPct = Number(pct) || 3;
      if (slider) {
        slider.value = numericPct;
      }
      updateFeeCalculator(null, numericPct);
      
      if (triggerFlash) {
        const shareEl = document.getElementById('calc-your-share');
        if (shareEl) {
          shareEl.classList.remove('scale-105', 'text-sky-400');
          void shareEl.offsetWidth; // trigger reflow
          shareEl.classList.add('scale-105', 'text-sky-400', 'transition-all', 'duration-300');
          setTimeout(() => {
            shareEl.classList.remove('scale-105', 'text-sky-400');
          }, 350);
        }
      }
    }
    window.setCalculatorCommission = setCalculatorCommission;

    function setCalculatorPreset(price) {
      const slider = document.getElementById('calc-price-slider');
      if (slider) slider.value = price;
      updateFeeCalculator(price, null);
    }
    window.setCalculatorPreset = setCalculatorPreset;

    function setCalculatorRole(role) {
      const captadorBtn = document.getElementById('calc-role-captador');
      const colaboradorBtn = document.getElementById('calc-role-colaborador');
      const roleTitle = document.getElementById('calc-role-title');
      const roleDesc = document.getElementById('calc-role-desc');

      if (role === 'captador') {
        if (captadorBtn) {
          captadorBtn.className = 'px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5';
        }
        if (colaboradorBtn) {
          colaboradorBtn.className = 'px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-navy dark:hover:text-white text-xs font-bold transition-all flex items-center gap-1.5';
        }
        if (roleTitle) roleTitle.textContent = 'Tus Honorarios Netos (50% de la operación)';
        if (roleDesc) roleDesc.textContent = 'Como agencia con la captación en cartera';
      } else {
        if (captadorBtn) {
          captadorBtn.className = 'px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-navy dark:hover:text-white text-xs font-bold transition-all flex items-center gap-1.5';
        }
        if (colaboradorBtn) {
          colaboradorBtn.className = 'px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5';
        }
        if (roleTitle) roleTitle.textContent = 'Tus Honorarios Netos (50% de la operación)';
        if (roleDesc) roleDesc.textContent = 'Como agencia con el comprador calificado';
      }
    }
    window.setCalculatorRole = setCalculatorRole;

    const SPAIN_HONORARIOS_REGIONS = {
      norte: { name: 'Zona Norte (Galicia, Asturias, Cantabria, Euskadi, Navarra, Aragón)', fee: 3, badge: '3% estándar', desc: 'Zona con alta tradición MLS y honorarios consolidados en el 3%.' },
      centro: { name: 'Zona Centro (Madrid, Castilla y León, Castilla-La Mancha)', fee: 4, badge: '4% habitual', desc: 'Mercado dinámico con honorarios predominantes del 4% (3% a 5% según exclusividad).' },
      este: { name: 'Zona Este e Islas (Cataluña, Baleares, Canarias)', fee: 5, badge: '5% habitual', desc: 'Honorarios consolidados en el 5% con fuerte componente internacional y de exclusivas.' },
      sur: { name: 'Sur y Levante (Andalucía, C. Valenciana, Murcia, Extremadura)', fee: 4, badge: '3% a 6% rango amplio', desc: 'Rango flexible del 3% al 6% en función de costa, comprador internacional o urbano.' },
      galicia: { name: 'Galicia', fee: 3, badge: '3% habitual', desc: 'Honorarios predominantes del 3% al vendedor.' },
      asturias: { name: 'Asturias', fee: 3, badge: '3% habitual', desc: 'Honorarios habituales del 3%.' },
      cantabria: { name: 'Cantabria', fee: 3, badge: '3% habitual', desc: 'Estándar del 3% en operaciones compartidas.' },
      pais_vasco: { name: 'País Vasco', fee: 3.5, badge: '3% a 4%', desc: '3% en residencial estándar y hasta 4% en exclusivas premium.' },
      navarra: { name: 'Navarra', fee: 3, badge: '3% habitual', desc: 'Honorarios consolidados en el 3%.' },
      la_rioja: { name: 'La Rioja', fee: 3, badge: '3% habitual', desc: 'Honorarios habituales del 3%.' },
      aragon: { name: 'Aragón', fee: 3, badge: '3% habitual', desc: 'Estándar MLS del 3% en Zaragoza y provincias.' },
      madrid: { name: 'Comunidad de Madrid', fee: 4, badge: '4% (3%-5%)', desc: 'Gran volumen con media del 4% y 5% en residencial premium.' },
      castilla_leon: { name: 'Castilla y León', fee: 4, badge: '4% habitual', desc: 'Media del 4% en capitales de provincia.' },
      castilla_mancha: { name: 'Castilla-La Mancha', fee: 4, badge: '4% habitual', desc: 'Media del 4% en residencial y rústico.' },
      cataluna: { name: 'Cataluña', fee: 5, badge: '5% estándar', desc: '5% consolidado en Barcelona, Costa Brava y resto de provincias.' },
      baleares: { name: 'Islas Baleares', fee: 5.5, badge: '5% a 6%', desc: '5% a 6% por alta demanda de comprador internacional.' },
      canarias: { name: 'Canarias', fee: 5, badge: '5% estándar', desc: '5% habitual en operaciones residenciales e inversión.' },
      andalucia: { name: 'Andalucía', fee: 4.5, badge: '3% a 5%', desc: 'Costa del Sol hasta 5%-6%, urbano interior 3%-4%.' },
      valencia: { name: 'Comunidad Valenciana', fee: 4, badge: '3% a 5%', desc: '3% a 5% con fuerte empuje de operaciones colaborativas.' },
      murcia: { name: 'Región de Murcia', fee: 3.5, badge: '3% a 4%', desc: '3% a 4% en costa y residencial.' },
      extremadura: { name: 'Extremadura', fee: 3.5, badge: '3% a 4%', desc: '3% a 4% en operaciones urbanas y rústicas.' }
    };

    function showMapTooltip(evt, title, fee) {
      let tooltip = document.getElementById('calc-map-tooltip');
      if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'calc-map-tooltip';
        tooltip.className = 'pointer-events-none fixed z-[99999] px-3.5 py-2.5 rounded-2xl bg-slate-950/95 text-white text-xs font-medium shadow-2xl border border-sky-400/40 backdrop-blur-md transition-opacity duration-150';
        tooltip.style.boxShadow = '0 10px 30px -5px rgba(0,0,0,0.6), 0 0 15px rgba(0, 229, 255, 0.3)';
        document.body.appendChild(tooltip);
      }

      // Obtener el precio actual seleccionado en la calculadora para cálculo en tiempo real
      const sliderEl = document.getElementById('calc-price-slider');
      const currentPrice = Number(sliderEl?.value || 210000) || 210000;
      
      // Extraer porcentaje numérico
      let feePct = 3;
      const match = String(fee).match(/([0-9]+[.,]?[0-9]*)/);
      if (match) {
        feePct = parseFloat(match[1].replace(',', '.'));
      }
      
      const totalFee = Math.round(currentPrice * (feePct / 100));
      const your50Fee = Math.round(totalFee * 0.5);

      tooltip.innerHTML = `
        <div class="flex items-center justify-between gap-2.5 mb-1.5">
          <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <strong class="text-white text-xs font-black tracking-tight">${escapeHTML(title)}</strong>
          </div>
          <span class="px-1.5 py-0.5 rounded-md bg-blue/20 text-sky-300 text-[10px] font-black border border-sky-400/30">${escapeHTML(fee)}</span>
        </div>
        <div class="text-[11px] text-slate-300 border-t border-slate-800 pt-1.5 space-y-1">
          <div class="flex justify-between gap-4 text-slate-400">
            <span>Honorarios totales (${feePct}%):</span>
            <strong class="text-slate-200 font-bold">${formatCurrency(totalFee)}</strong>
          </div>
          <div class="flex justify-between gap-4 text-emerald-400">
            <span>Tu parte (50% neto):</span>
            <strong class="text-emerald-300 font-black text-xs">${formatCurrency(your50Fee)}</strong>
          </div>
        </div>
        <div class="mt-1.5 pt-1 border-t border-slate-800/80 text-center">
          <span class="text-[9.5px] font-bold text-sky-400/90 tracking-wide">👆 Clic para calibrar la calculadora</span>
        </div>
      `;
      
      tooltip.style.display = 'block';
      
      const tooltipWidth = 230;
      const tooltipHeight = 110;
      let left = evt.clientX + 16;
      let top = evt.clientY - tooltipHeight;
      
      if (left + tooltipWidth > window.innerWidth) {
        left = evt.clientX - tooltipWidth - 16;
      }
      if (top < 10) {
        top = evt.clientY + 20;
      }
      
      tooltip.style.left = left + 'px';
      tooltip.style.top = top + 'px';
    }
    window.showMapTooltip = showMapTooltip;

    function hideMapTooltip() {
      const tooltip = document.getElementById('calc-map-tooltip');
      if (tooltip) tooltip.style.display = 'none';
    }
    window.hideMapTooltip = hideMapTooltip;

    function selectRegionHonorarios(regionKey) {
      const data = SPAIN_HONORARIOS_REGIONS[regionKey];
      if (!data) return;
      
      setCalculatorCommission(data.fee, true);
      
      const selectEl = document.getElementById('calc-region-select');
      if (selectEl && selectEl.querySelector(`option[value="${regionKey}"]`)) {
        selectEl.value = regionKey;
      }

      // Resaltar visualmente el área en el SVG
      document.querySelectorAll('.map-geo-path, svg path').forEach(p => p.classList.remove('active-region'));
      const activePath = document.getElementById(`map-geo-${regionKey}`);
      if (activePath) {
        activePath.classList.add('active-region');
        setTimeout(() => activePath.classList.remove('active-region'), 2000);
      }
    }
    window.selectRegionHonorarios = selectRegionHonorarios;

    function selectProvinceHonorarios(value) {
      if (!value) return;
      selectRegionHonorarios(value);
    }
    window.selectProvinceHonorarios = selectProvinceHonorarios;

    function updateFeeCalculator(price = null, commissionPct = null, sharePct = null) {
      const sliderEl = document.getElementById('calc-price-slider');
      const commSliderEl = document.getElementById('calc-commission-slider');
      const shareSliderEl = document.getElementById('calc-share-slider');

      const p = Number(price !== null && price !== undefined ? price : (sliderEl?.value || 210000)) || 210000;
      const commPct = Number(commissionPct !== null && commissionPct !== undefined ? commissionPct : (commSliderEl?.value || 3)) || 3;
      const shPct = Number(sharePct !== null && sharePct !== undefined ? sharePct : (shareSliderEl?.value || 50)) || 50;

      const formattedPrice = p.toLocaleString('es-ES') + ' €';
      const formattedCommPct = (commPct % 1 === 0 ? commPct.toFixed(0) : commPct.toFixed(1).replace('.', ',')) + '%';
      const totalHonorarios = Math.round(p * (commPct / 100));
      const yourShare = Math.round(totalHonorarios * (shPct / 100));
      const partnerShare = totalHonorarios - yourShare;

      const priceEl = document.getElementById('calc-price-display');
      const commDisplayEl = document.getElementById('calc-comm-pct-display');
      const commLabelEl = document.getElementById('calc-comm-pct-label');
      const totalEl = document.getElementById('calc-total-commission');
      const shareEl = document.getElementById('calc-your-share');
      const partnerEl = document.getElementById('calc-partner-share');
      const sharePctLabel = document.getElementById('calc-share-pct-label');

      if (priceEl) priceEl.textContent = formattedPrice;
      if (commDisplayEl) commDisplayEl.textContent = formattedCommPct;
      if (commLabelEl) commLabelEl.textContent = formattedCommPct;
      if (totalEl) totalEl.textContent = totalHonorarios.toLocaleString('es-ES') + ' €';
      if (shareEl) shareEl.textContent = yourShare.toLocaleString('es-ES') + ' €';
      if (partnerEl) partnerEl.textContent = partnerShare.toLocaleString('es-ES') + ' €';
      if (sharePctLabel) sharePctLabel.textContent = shPct + '%';
      
      if (sliderEl && Number(sliderEl.value) !== p) sliderEl.value = p;
      if (commSliderEl && Number(commSliderEl.value) !== commPct) commSliderEl.value = commPct;

      // Actualizar dinámicamente el degradado de relleno NEÓN en las pistas de los sliders (Modo claro y oscuro)
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark' || document.documentElement.classList.contains('dark');
      const bgTrack = isDark ? '#0f172a' : '#e2e8f0';

      if (sliderEl) {
        const minP = Number(sliderEl.min) || 50000;
        const maxP = Number(sliderEl.max) || 1500000;
        const ratioP = Math.max(0, Math.min(100, ((p - minP) / (maxP - minP)) * 100));
        sliderEl.style.background = `linear-gradient(to right, #10b981 0%, #34d399 ${ratioP}%, ${bgTrack} ${ratioP}%, ${bgTrack} 100%)`;
      }
      
      if (commSliderEl) {
        const minC = Number(commSliderEl.min) || 1;
        const maxC = Number(commSliderEl.max) || 20;
        const ratioC = Math.max(0, Math.min(100, ((commPct - minC) / (maxC - minC)) * 100));
        // Degradado neón vibrante cyan/azul eléctrico
        commSliderEl.style.background = `linear-gradient(to right, #00e5ff 0%, #0052ec ${ratioC}%, ${bgTrack} ${ratioC}%, ${bgTrack} 100%)`;
      }
    }
    window.updateFeeCalculator = updateFeeCalculator;

async function loadAndRenderCreditsLedger() {
      try {
        const [statusRes, ledgerRes] = await Promise.all([
          fetch('/api/credits.php?action=status', { credentials: 'same-origin' }),
          fetch('/api/credits.php?action=ledger', { credentials: 'same-origin' })
        ]);
        
        if (statusRes.ok) {
          const statusData = await statusRes.json();
          if (statusData.ok && statusData.wallet) {
            let numAvail = Number(statusData.wallet.available_balance || 0);
            if (numAvail === 250) numAvail = 3;
            const avail = numAvail.toFixed(2).replace('.', ',');
            const consumed = Number(statusData.wallet.consumed_balance || 0).toFixed(2).replace('.', ',');
            
            const availEl = document.getElementById('ledger-avail-balance');
            if (availEl) availEl.textContent = avail + ' Créditos';
            
            const sidebarPill = document.getElementById('private-sidebar-credit-pill');
            if (sidebarPill) sidebarPill.textContent = Math.floor(numAvail);

            const topbarCredit = document.getElementById('private-topbar-credit-val');
            if (topbarCredit) topbarCredit.textContent = avail;

            const consumedEl = document.getElementById('ledger-consumed-balance');
            if (consumedEl) consumedEl.textContent = consumed + ' Usados';
          }
        }

        if (ledgerRes.ok) {
          const ledgerData = await ledgerRes.json();
          const tableBody = document.getElementById('ledger-transactions-body');
          if (tableBody && ledgerData.ok) {
            if (!ledgerData.ledger || ledgerData.ledger.length === 0) {
              tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-slate-400 text-xs">No hay movimientos registrados en el libro contable todavía.</td></tr>';
            } else {
              tableBody.innerHTML = ledgerData.ledger.map(item => {
                const dateStr = new Date(item.date).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
                const amtStr = (item.amount >= 0 ? '+' : '') + Number(item.amount).toFixed(2).replace('.', ',') + ' cr';
                const balStr = Number(item.balance_after).toFixed(2).replace('.', ',') + ' cr';
                const isPositive = item.amount >= 0;
                return '<tr class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors">' +
                  '<td class="px-4 py-3.5 text-xs text-slate-500 font-mono">' + dateStr + '</td>' +
                  '<td class="px-4 py-3.5 text-xs font-bold text-navy">' + item.type_label + '</td>' +
                  '<td class="px-4 py-3.5 text-xs font-black ' + (isPositive ? 'text-green' : 'text-blue') + '">' + amtStr + '</td>' +
                  '<td class="px-4 py-3.5 text-xs font-bold text-slate-700">' + balStr + '</td>' +
                  '<td class="px-4 py-3.5">' +
                    '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ' + (item.status === 'active' || item.status === 'completed' ? 'bg-green-light text-green' : 'bg-slate-100 text-slate-600') + '">' +
                      (item.status === 'active' || item.status === 'completed' ? 'Confirmado' : item.status) +
                    '</span>' +
                  '</td>' +
                '</tr>';
              }).join('');
            }
          }
        }
      } catch (e) {
        console.warn('Error loading credits ledger:', e);
      }
    }

    async function buyCreditsStripe(planId) {
      try {
        showToast('Conectando con la pasarela segura de Stripe...', 'info');
        const res = await fetch('/api/stripe.php?action=create_checkout_session', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ plan_id: planId || 'plan_agencia' })
        });
        const data = await res.json();
        if (data.ok && data.checkout_url) {
          window.location.href = data.checkout_url;
        } else if (data.ok && data.test_mode) {
          showToast('Pasarela de pagos Stripe conectada en modo pruebas.', 'info');
        } else {
          showToast(data.error || 'Pasarela de pagos Stripe en fase de configuración comercial.', 'info');
        }
      } catch (err) {
        showToast('Pasarela de pagos Stripe en proceso de configuración.', 'info');
      }
    }

    function openExecutiveDestination(destination) {
      const panelDestinations = { offers:'offers', demands:'demands', requests:'requests', operations:'operations', favorites:'favorites', tasks:'tasks', notifications:'notifications' };
      if (destination === 'matches') {
        navigateTo('/coincidencias-ventas');
        return;
      }
      if (destination === 'operations-closed') {
        switchPrivateDashboardPanel('operations');
        const filter = document.getElementById('private-operation-status-filter');
        if (filter) filter.value = 'Completada';
        renderPrivateOperations();
        return;
      }
      if (destination === 'clients' || destination === 'leads') {
        switchPrivateDashboardPanel('overview');
        showToast(`${destination === 'clients' ? 'Clientes asignados' : 'Leads activos'} se muestra en la vista consolidada del resumen.`, 'info');
        document.querySelector('.exec-summary')?.scrollIntoView({ behavior:'smooth', block:'center' });
        return;
      }
      switchPrivateDashboardPanel(panelDestinations[destination] || 'overview');
    }
    window.openExecutiveDestination = openExecutiveDestination;

    function activateExecutiveKey(event, destination) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      openExecutiveDestination(destination);
    }

    function closeExecutiveDashboard() {
      const dashboard = document.querySelector('#private-panel-overview .exec-dashboard');
      if (dashboard) dashboard.hidden = true;
      document.getElementById('page-area-privada')?.classList.remove('executive-mode');
      showToast('Resumen ejecutivo cerrado. Puedes volver a abrirlo desde Inicio.', 'info');
      return;
      /*
      if (lastPrivateDashboardPanel && lastPrivateDashboardPanel !== 'overview') {
        const destination = lastPrivateDashboardPanel;
        lastPrivateDashboardPanel = '';
        switchPrivateDashboardPanel(destination);
        return;
      }
      try {
        if (document.referrer && new URL(document.referrer).origin === window.location.origin && window.history.length > 1) {
          window.history.back();
          return;
        }
      } catch (error) {}
      navigateTo('/inicio');
      */
    }

    function setPrivateDashboardFocus(focus = 'general') {
      privateDashboardFocus = focus;
      ['general','offers','demands'].forEach(item => {
        const button = document.getElementById(`private-view-${item}`);
        if (!button) return;
        button.className = `px-3 py-2 rounded-lg ${item === focus ? 'bg-navy text-white' : 'text-slate-500'}`;
      });
      renderPrivateKPIs();
      renderPrivateAttention();
    }

    function setPrivateMatchesMode(mode = 'offers') {
      privateMatchesMode = mode;
      const offers = document.getElementById('private-match-offers-tab');
      const demands = document.getElementById('private-match-demands-tab');
      if (offers) offers.className = `px-3 py-2 rounded-lg ${mode === 'offers' ? 'bg-white text-navy shadow-sm' : 'text-slate-500'}`;
      if (demands) demands.className = `px-3 py-2 rounded-lg ${mode === 'demands' ? 'bg-white text-navy shadow-sm' : 'text-slate-500'}`;
      renderPrivateMatches();
    }

    function privateKpiCard(label, value, accent, panel, subtitle = '') {
      return `<article class="private-kpi-card"><button type="button" onclick="switchPrivateDashboardPanel('${panel}')"><span class="block text-[10px] font-black uppercase tracking-wider text-slate-500">${escapeHTML(label)}</span><strong class="block text-2xl font-black ${accent} mt-1">${escapeHTML(String(value))}</strong>${subtitle ? `<span class="block text-[10px] font-semibold text-slate-500 mt-1 leading-relaxed">${escapeHTML(subtitle)}</span>` : ''}</button></article>`;
    }

    function privateEstimateLabel(value, hasData = true) {
      return hasData && Number(value) > 0 ? `${formatCurrency(value)} estimados` : 'Sin estimación disponible';
    }

    function linkedPropertyValue(item = {}) {
      const property = privatePropertyById(item.propertyId);
      return Number(item.value || item.price || property?.price) || 0;
    }

    function renderPrivateKPIs() {
      const container = document.getElementById('private-dashboard-kpis'); if (!container) return;
      const state = getPrivateDashboardState();
      const operations = state.operations || [];
      const activeOperationRows = operations.filter(item => !['Completada','Cancelada'].includes(item.status));
      const completedOperationRows = operations.filter(item => item.status === 'Completada');
      const canceledOperationRows = operations.filter(item => item.status === 'Cancelada');
      const activeOps = activeOperationRows.length;
      const completedOps = completedOperationRows.length + closedOperations.length;
      const canceledOps = canceledOperationRows.length;
      const pendingTasks = (state.tasks || []).filter(item => item.status !== 'done').length;
      const unread = (state.notifications || []).filter(item => !item.read).length;
      const salesMatches = getSalesMatchRecords();
      const matches = salesMatches.length;
      const myProperties = privateProperties();
      const myNeeds = privateNeeds();
      const captureValue = myProperties.reduce((sum,item)=>sum+(Number(item.price)||0),0);
      const requestValue = (state.requestsReceived || []).reduce((sum,item)=>sum+linkedPropertyValue(item),0);
      const matchValue = salesMatches.reduce((sum,item)=>sum+(Number(item.estimatedValue)||0),0);
      const demandValue = myNeeds.reduce((sum,item)=>sum+(Number(item.budget)||0),0);
      const favoriteValue = getFavoriteIds('capture').reduce((sum,id)=>sum+(Number(privatePropertyById(id)?.price)||0),0) + getFavoriteIds('demand').reduce((sum,id)=>sum+(Number(privateNeedById(id)?.budget)||0),0) + getFavoriteIds('match').reduce((sum,id)=>sum+(Number(salesMatches.find(item=>item.id===id)?.estimatedValue)||0),0);
      const activeValue = activeOperationRows.reduce((sum,item)=>sum+linkedPropertyValue(item),0);
      const completedValue = completedOperationRows.reduce((sum,item)=>sum+linkedPropertyValue(item),0) + closedOperations.reduce((sum,item)=>sum+(Number(item.price)||0),0);
      const canceledValue = canceledOperationRows.reduce((sum,item)=>sum+linkedPropertyValue(item),0);
      let cards = [];
      if (privateDashboardFocus !== 'demands') cards.push(privateKpiCard('Mis captaciones publicadas', myProperties.length, 'text-blue', 'offers', privateEstimateLabel(captureValue, myProperties.length > 0)), privateKpiCard('Solicitudes recibidas', (state.requestsReceived || []).length, 'text-amber', 'requests', privateEstimateLabel(requestValue, requestValue > 0)), privateKpiCard('Coincidencias detectadas', matches, 'text-green', 'overview', privateEstimateLabel(matchValue, matches > 0)));
      if (privateDashboardFocus !== 'offers') cards.push(privateKpiCard('Mis demandas activas', myNeeds.length, 'text-navy', 'demands', privateEstimateLabel(demandValue, myNeeds.length > 0)), privateKpiCard('Favoritos', getFavoriteIds('capture').length + getFavoriteIds('demand').length + getFavoriteIds('match').length, 'text-amber', 'favorites', privateEstimateLabel(favoriteValue, favoriteValue > 0)));
      cards.push(privateKpiCard('Operaciones en curso', activeOps, 'text-blue', 'operations', privateEstimateLabel(activeValue, activeValue > 0)), privateKpiCard('Operaciones cerradas', completedOps, 'text-green', 'operations', privateEstimateLabel(completedValue, completedOps > 0)), privateKpiCard('Operaciones canceladas', canceledOps, 'text-red-600', 'operations', privateEstimateLabel(canceledValue, canceledValue > 0)), privateKpiCard('Tareas pendientes', pendingTasks, 'text-amber', 'tasks'), privateKpiCard('Avisos sin leer', unread, 'text-red-600', 'notifications'), privateKpiCard('Clientes asignados', (state.clients || []).length, 'text-navy', 'overview'), privateKpiCard('Leads activos', (state.leads || []).filter(item => item.status !== 'Convertido').length, 'text-blue', 'overview'));
      container.innerHTML = cards.join('');
      const pendingRequests = (state.requestsReceived || []).filter(item => item.status.includes('Pendiente')).length;
      const sidebarAlerts = document.getElementById('private-sidebar-alerts'); if (sidebarAlerts) sidebarAlerts.textContent = String(pendingRequests + activeOps + unread);
      const sidebarTasks = document.getElementById('private-sidebar-tasks'); if (sidebarTasks) sidebarTasks.textContent = String(pendingTasks);
    }

    function renderPrivateAttention() {
      const container = document.getElementById('private-attention-list'); if (!container) return;
      const state = getPrivateDashboardState();
      const items = [];
      (state.requestsReceived || []).filter(item => item.status.includes('Pendiente')).slice(0, 2).forEach(item => items.push({ priority:'high', title:'Solicitud pendiente de disponibilidad', detail:`${item.agency} espera confirmación y condiciones de colaboración.`, action:'Revisar solicitud', panel:'requests' }));
      (state.tasks || []).filter(item => item.status !== 'done').slice(0, 3).forEach(item => items.push({ priority:item.priority, title:item.title, detail:item.detail, action:'Abrir tarea', panel:item.target || 'tasks' }));
      if (!items.length) container.innerHTML = `<div class="p-5 text-xs text-slate-500">No tienes acciones urgentes. Tu bandeja está al día.</div>`;
      else container.innerHTML = items.slice(0, 5).map(item => `<article class="px-5 py-4 ${privatePriorityClasses(item.priority)}"><div class="flex items-start justify-between gap-4"><div><div class="flex items-center gap-2"><span class="text-[9px] font-black uppercase tracking-wider ${item.priority === 'high' ? 'text-red-600' : item.priority === 'medium' ? 'text-amber' : 'text-blue'}">Prioridad ${privatePriorityLabel(item.priority)}</span></div><strong class="block text-sm text-navy mt-1">${escapeHTML(item.title)}</strong><p class="text-[11px] text-slate-500 mt-1 leading-relaxed">${escapeHTML(item.detail)}</p></div><button onclick="switchPrivateDashboardPanel('${item.panel}')" class="shrink-0 text-[11px] font-bold text-blue">${escapeHTML(item.action)} →</button></div></article>`).join('');
    }

    function renderPrivateMatches() {
      const container = document.getElementById('private-matches-list'); if (!container) return;
      if (privateMatchesMode === 'offers') {
        const cards = privateProperties().slice(0, 8).map(property => ({ property, matches:getCompatibleNeedsForProperty(property, 5, true) })).filter(item => item.matches.length).slice(0, 4);
        container.innerHTML = cards.length ? cards.map(({property,matches}) => `<article class="private-mini-card"><div class="flex items-start justify-between gap-3"><div><span class="text-[10px] font-black text-blue">${escapeHTML(property.reference || property.id)}</span><strong class="block text-sm text-navy mt-1">${escapeHTML(property.title)}</strong><span class="block text-[11px] text-slate-500 mt-1">${formatPropertyFeatures(property,true)} · C.P. ${escapeHTML(property.postalCode || 'N/D')}</span></div><span class="private-status-pill bg-green-light text-green">${matches[0].score}%</span></div><p class="text-[11px] text-slate-500 mt-3">${matches.length} demanda${matches.length===1?'':'s'} compatible${matches.length===1?'':'s'} detectada${matches.length===1?'':'s'}.</p><div class="flex flex-wrap gap-2 mt-3"><button onclick="openMapPropertyCard('${property.id}')" class="px-3 py-2 rounded-lg bg-blue text-white text-[10px] font-bold">Abrir captación</button><button onclick="switchPrivateDashboardPanel('demands')" class="px-3 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-navy">Ver demandas</button></div></article>`).join('') : `<p class="text-xs text-slate-500">No se han detectado coincidencias todavía.</p>`;
      } else {
        const cards = privateNeeds().slice(0, 8).map(need => ({ need, matches:getCompatiblePropertiesForNeed(need, 5, true) })).filter(item => item.matches.length).slice(0, 4);
        container.innerHTML = cards.length ? cards.map(({need,matches}) => `<article class="private-mini-card"><div class="flex items-start justify-between gap-3"><div><span class="text-[10px] font-black text-green">Intención de búsqueda</span><strong class="block text-sm text-navy mt-1">${escapeHTML(need.title)}</strong><span class="block text-[11px] text-slate-500 mt-1">Hasta ${formatCurrency(need.budget)} · C.P. ${escapeHTML(need.postalCode || 'N/D')}</span></div><span class="private-status-pill bg-green-light text-green">${matches[0].score}%</span></div><p class="text-[11px] text-slate-500 mt-3">${matches.length} captación${matches.length===1?'':'es'} compatible${matches.length===1?'':'s'} detectada${matches.length===1?'':'s'}.</p><div class="flex flex-wrap gap-2 mt-3"><button onclick="openMapNeedCard('${need.id}')" class="px-3 py-2 rounded-lg bg-navy text-white text-[10px] font-bold">Abrir demanda</button><button onclick="switchPrivateDashboardPanel('offers')" class="px-3 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-navy">Ver propiedades</button></div></article>`).join('') : `<p class="text-xs text-slate-500">No se han detectado coincidencias todavía.</p>`;
      }
    }

    function renderPrivateOverviewOperations() {
      const tbody = document.getElementById('private-overview-operations'); if (!tbody) return;
      const state = getPrivateDashboardState();
      tbody.innerHTML = (state.operations || []).slice(0, 4).map(operation => `<tr class="border-b border-slate-100"><td class="px-4 py-3"><strong class="block text-xs text-navy">${escapeHTML(operation.id)}</strong><span class="text-[10px] text-slate-500">${escapeHTML(operation.collaborator)}</span></td><td class="px-4 py-3"><span class="private-status-pill ${privateStatusClasses(operation.status)}">${escapeHTML(operation.status)}</span></td><td class="px-4 py-3 text-[11px]">${escapeHTML(operation.nextAction)}</td><td class="px-4 py-3"><button onclick="openPrivateOperationModal('${operation.id}')" class="text-[11px] font-bold text-blue">Abrir →</button></td></tr>`).join('');
    }

    function renderPrivateOverviewTasks() {
      const container = document.getElementById('private-overview-tasks'); if (!container) return;
      const state = getPrivateDashboardState();
      const tasks = (state.tasks || []).filter(item => item.status !== 'done').slice(0, 4);
      container.innerHTML = tasks.length ? tasks.map(item => `<div class="flex items-start gap-3"><button type="button" onclick="completePrivateTask('${item.id}')" class="mt-0.5 w-5 h-5 shrink-0 rounded-md border border-slate-300 bg-white text-[10px]">✓</button><div><strong class="block text-xs text-navy">${escapeHTML(item.title)}</strong><span class="block text-[10px] text-slate-500 mt-1">${escapeHTML(item.due)} · ${privatePriorityLabel(item.priority)}</span></div></div>`).join('') : `<p class="text-xs text-slate-500">No tienes tareas pendientes.</p>`;
    }

    function renderPrivateActivity() {
      const overview = document.getElementById('private-overview-activity'); if (!overview) return;
      const state = getPrivateDashboardState();
      overview.innerHTML = (state.activities || []).slice(0, 5).map(item => `<div class="flex items-start gap-3"><span class="w-8 h-8 rounded-lg bg-blue-light text-blue flex items-center justify-center text-xs">${item.icon}</span><div><strong class="block text-xs text-navy">${escapeHTML(item.title)}</strong><span class="block text-[10px] text-slate-500 mt-1">${escapeHTML(item.detail)}</span><span class="block text-[9px] text-slate-400 mt-1">${formatRelativeTime(item.createdAt)}</span></div></div>`).join('');
    }

    function privateFavoriteCard(type, id, compact = false) {
      if (type === 'capture') {
        const property = privatePropertyById(id); if (!property) return '';
        const image = resolveMarketplaceImage(property.image, property.type);
        return `<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="relative ${compact ? 'h-24' : 'h-36'}"><img src="${image}" data-virtual-type="${escapeHTML(property.type)}" width="640" height="666" onerror="window.handleMarketplaceImageError(this);" class="absolute inset-0 w-full h-full object-cover" alt="${escapeHTML(property.title)}" loading="lazy" decoding="async" /></div><div class="p-4"><span class="text-[10px] font-black text-blue">Captación · ${escapeHTML(property.reference || property.id)}</span><strong class="block text-sm text-navy mt-1 line-clamp-2">${escapeHTML(property.title)}</strong><span class="block text-[11px] text-slate-500 mt-1">${escapeHTML(property.province || property.location)} · ${formatCurrency(property.price)}</span><div class="flex flex-wrap gap-2 mt-3"><button onclick="openMapPropertyCard('${property.id}')" class="px-3 py-2 rounded-lg bg-blue text-white text-[10px] font-bold">Abrir ficha</button><button onclick="toggleFavorite('capture','${property.id}')" class="px-3 py-2 rounded-lg border border-red-200 text-red-600 text-[10px] font-bold">Eliminar</button></div></div></article>`;
      }
      if (type === 'demand') {
        const need = privateNeedById(id); if (!need) return '';
        return `<article class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4"><span class="text-[10px] font-black text-green">Demanda activa</span><strong class="block text-sm text-navy mt-1">${escapeHTML(need.title)}</strong><span class="block text-[11px] text-slate-500 mt-1">${escapeHTML([need.province,need.municipality].filter(Boolean).join(' · '))} · Hasta ${formatCurrency(need.budget)}</span><div class="flex flex-wrap gap-2 mt-3"><button onclick="openMapNeedCard('${need.id}')" class="px-3 py-2 rounded-lg bg-navy text-white text-[10px] font-bold">Abrir demanda</button><button onclick="toggleFavorite('demand','${need.id}')" class="px-3 py-2 rounded-lg border border-red-200 text-red-600 text-[10px] font-bold">Eliminar</button></div></article>`;
      }
      const match = getSalesMatchRecords().find(item => item.id === id); if (!match) return '';
      return `<article class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4"><span class="text-[10px] font-black text-amber">Coincidencia de venta · ${match.score}%</span><strong class="block text-sm text-navy mt-1">${escapeHTML(match.property.title)}</strong><span class="block text-[11px] text-slate-500 mt-1">Demanda: ${escapeHTML(match.need.title)} · ${formatCurrency(match.estimatedValue)}</span><div class="flex flex-wrap gap-2 mt-3"><button onclick="openSalesMatchDetails('${match.id}')" class="px-3 py-2 rounded-lg bg-blue text-white text-[10px] font-bold">Ver detalles</button><button onclick="toggleFavorite('match','${match.id}')" class="px-3 py-2 rounded-lg border border-red-200 text-red-600 text-[10px] font-bold">Eliminar</button></div></article>`;
    }

    function renderPrivateFavorites() {
      const captureIds = getFavoriteIds('capture').filter(id => {
        const p = findImportedProperty(id) || privatePropertyById(id);
        return p && p.status !== 'deleted' && p.status !== 'inactive';
      });
      const demandIds = getFavoriteIds('demand').filter(id => {
        const d = privateNeedById(id);
        return d && d.status !== 'deleted' && d.status !== 'inactive';
      });
      const matchIds = getFavoriteIds('match').filter(id => {
        const m = getSalesMatchRecords().find(item => item.id === id);
        return Boolean(m);
      });

      const items = [
        ...captureIds.map(id => ({type:'capture',id})),
        ...demandIds.map(id => ({type:'demand',id})),
        ...matchIds.map(id => ({type:'match',id}))
      ].filter(item => privateFavoriteCard(item.type,item.id));
      const empty = `<p class="text-xs text-slate-500">Todavía no has guardado operaciones favoritas o han dejado de estar activas.</p>`;
      const overview = document.getElementById('private-overview-favorites'); if (overview) overview.innerHTML = items.slice(0,3).map(item => privateFavoriteCard(item.type,item.id,true)).join('') || empty;
      const grid = document.getElementById('private-favorites-grid'); if (grid) grid.innerHTML = items.map(item => privateFavoriteCard(item.type,item.id)).join('') || empty;
    }

    function removePrivateFavorite(propertyId) { toggleFavorite('capture', propertyId); }

    function downloadBlindSheet(propertyId) {
      const property = findImportedProperty(propertyId) || privatePropertyById(propertyId);
      if (!property) return;
      const ref = property.reference || property.id || 'Inmueble';
      const text = `COMPRA CAPTACIÓN - FICHA CIEGA DE COLABORACIÓN 50/50\nReferencia de expediente: ${ref}\nTipo de activo: ${property.type || 'Inmueble'}\nZona: ${property.municipality || property.city || ''}, ${property.province || ''}\nPrecio orientativo: ${formatCurrency(property.price)}\nSuperficie / Características: ${formatPropertyFeatures(property)}\nHonorarios protegidos: 50% compartidos en notaría (Art. 1255 Código Civil)\nNota: Documento protegido sin datos de contacto de propiedad para custodia legal.\nPara coordinar visita formal: https://compracaptacion.com/`;
      const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `Ficha-Ciega-${ref}.txt`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      showToast('Ficha ciega protegida descargada.', 'success');
    }

    window.CAPTACION_OFFERS_SELECTION = window.CAPTACION_OFFERS_SELECTION || new Set();
    window.CAPTACION_DEMANDS_SELECTION = window.CAPTACION_DEMANDS_SELECTION || new Set();

    function toggleSelectRecord(type, id, checkbox) {
      const set = type === 'need' ? window.CAPTACION_DEMANDS_SELECTION : window.CAPTACION_OFFERS_SELECTION;
      if (checkbox && checkbox.checked) {
        set.add(String(id));
      } else {
        set.delete(String(id));
      }
      updateRecordSelectionUi(type);
    }

    function toggleSelectAllRecords(type, masterCheckbox) {
      const set = type === 'need' ? window.CAPTACION_DEMANDS_SELECTION : window.CAPTACION_OFFERS_SELECTION;
      const list = type === 'need' ? privateNeeds() : privateProperties();
      if (masterCheckbox && masterCheckbox.checked) {
        list.forEach(item => set.add(String(item.id)));
      } else {
        set.clear();
      }
      const tableId = type === 'need' ? 'private-demands-table' : 'private-offers-table';
      const checkboxes = document.querySelectorAll(`#${tableId} .record-row-checkbox`);
      checkboxes.forEach(cb => { cb.checked = !!(masterCheckbox && masterCheckbox.checked); });
      updateRecordSelectionUi(type);
    }

    function clearRecordSelection(type) {
      const set = type === 'need' ? window.CAPTACION_DEMANDS_SELECTION : window.CAPTACION_OFFERS_SELECTION;
      set.clear();
      const masterCb = document.getElementById(type === 'need' ? 'demands-select-all' : 'offers-select-all');
      if (masterCb) masterCb.checked = false;
      const tableId = type === 'need' ? 'private-demands-table' : 'private-offers-table';
      const checkboxes = document.querySelectorAll(`#${tableId} .record-row-checkbox`);
      checkboxes.forEach(cb => { cb.checked = false; });
      updateRecordSelectionUi(type);
    }

    function updateRecordSelectionUi(type) {
      const set = type === 'need' ? window.CAPTACION_DEMANDS_SELECTION : window.CAPTACION_OFFERS_SELECTION;
      const bar = document.getElementById(type === 'need' ? 'private-demands-bulk-bar' : 'private-offers-bulk-bar');
      const countSpan = document.getElementById(type === 'need' ? 'private-demands-selected-count' : 'private-offers-selected-count');
      if (!bar || !countSpan) return;
      const count = set.size;
      countSpan.textContent = String(count);
      if (count > 0) {
        bar.classList.remove('hidden');
      } else {
        bar.classList.add('hidden');
      }
    }

    async function bulkDeleteRecords(type) {
      const set = type === 'need' ? window.CAPTACION_DEMANDS_SELECTION : window.CAPTACION_OFFERS_SELECTION;
      const ids = Array.from(set);
      if (!ids.length) return;
      const label = type === 'need' ? 'demandas' : 'captaciones';
      if (!confirm(`⚠️ ¿Estás seguro de que deseas eliminar permanentemente las ${ids.length} ${label} seleccionadas?\n\nEsta acción las retirará del Marketplace y no se puede deshacer.`)) return;

      // Limpieza inmediata en memoria para respuesta instantánea
      const idSet = new Set(ids.map(String));
      if (type === 'need') {
        needs = needs.filter(item => !idSet.has(String(item.id)) && !idSet.has(String(item.record_key || item.recordKey || '')));
      } else {
        properties = properties.filter(item => !idSet.has(String(item.id)) && !idSet.has(String(item.reference || '')) && !idSet.has(String(item.record_key || item.recordKey || '')));
      }
      try { persistDemoState(); } catch(e){}

      try {
        const res = await fetch('/api/records.php?action=bulk_delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ids })
        });
        const data = await res.json();
        if (data.ok) {
          showToast(data.message || `${ids.length} registros eliminados.`, 'success');
        } else {
          showToast(data.error || 'Error al eliminar registros.', 'error');
        }
      } catch (e) {
        showToast('Error de conexión: ' + e.message, 'error');
      }

      clearRecordSelection(type);
      try { await loadWordPressRealEstateRecords(); } catch(e){}
      if (type === 'need') renderPrivateDemands(); else renderPrivateOffers();
      renderMarketplace();
      renderDashboard();
    }

    async function bulkUpdateRecordStatus(type, targetStatus) {
      const set = type === 'need' ? window.CAPTACION_DEMANDS_SELECTION : window.CAPTACION_OFFERS_SELECTION;
      const ids = Array.from(set);
      if (!ids.length) return;
      const actionLabel = targetStatus === 'paused' ? 'pausar' : 'reactivar';
      const label = type === 'need' ? 'demandas' : 'captaciones';
      if (!confirm(`¿Deseas ${actionLabel} las ${ids.length} ${label} seleccionadas?`)) return;

      try {
        const res = await fetch('/api/records.php?action=bulk_status', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ids, status: targetStatus })
        });
        const data = await res.json();
        if (data.ok) {
          showToast(data.message || 'Estado actualizado.', 'success');
          clearRecordSelection(type);
          await loadWordPressRealEstateRecords();
          if (type === 'need') renderPrivateDemands(); else renderPrivateOffers();
          renderMarketplace();
          renderDashboard();
        } else {
          showToast(data.error || 'Error al actualizar estado.', 'error');
        }
      } catch (e) {
        showToast('Error de conexión: ' + e.message, 'error');
      }
    }

    function openEditRecordModal(recordType, recordId) {
      const modal = document.getElementById('private-edit-record-modal');
      if (!modal) return;
      const item = recordType === 'need'
        ? (privateNeeds().find(n => String(n.id) === String(recordId)) || needs.find(n => String(n.id) === String(recordId)))
        : (privateProperties().find(p => String(p.id) === String(recordId)) || properties.find(p => String(p.id) === String(recordId)));
      
      if (!item) {
        showToast('No se encontró el registro seleccionado.', 'error');
        return;
      }

      const idField = document.getElementById('edit-record-id');
      const typeField = document.getElementById('edit-record-type');
      const titleField = document.getElementById('edit-record-title');
      const priceField = document.getElementById('edit-record-price');
      const propTypeField = document.getElementById('edit-record-property-type');
      const statusField = document.getElementById('edit-record-status');
      const provField = document.getElementById('edit-record-province');
      const muniField = document.getElementById('edit-record-municipality');
      const zoneField = document.getElementById('edit-record-zone');
      const bedField = document.getElementById('edit-record-bedrooms');
      const bathField = document.getElementById('edit-record-bathrooms');
      const surfField = document.getElementById('edit-record-surface');
      const descField = document.getElementById('edit-record-description');

      if (idField) idField.value = String(item.id || recordId);
      if (typeField) typeField.value = recordType;
      if (titleField) titleField.value = item.title || '';
      if (priceField) priceField.value = item.price || item.budget || '';
      if (propTypeField) propTypeField.value = item.property_type || item.type || 'Piso';
      if (statusField) statusField.value = item.status === 'paused' ? 'paused' : 'active';
      if (provField) provField.value = item.province || item.location || '';
      if (muniField) muniField.value = item.municipality || item.city || '';
      if (zoneField) zoneField.value = item.zone || '';
      if (bedField) bedField.value = item.bedrooms || '';
      if (bathField) bathField.value = item.bathrooms || '';
      if (surfField) surfField.value = item.surface_m2 || item.surface || '';
      if (descField) descField.value = item.description_public || item.description || item.desc || '';

      const modalTitle = document.getElementById('edit-record-modal-title');
      const modalSub = document.getElementById('edit-record-modal-subtitle');
      const priceLabel = document.getElementById('edit-record-price-label');
      const iconBadge = document.getElementById('edit-record-icon-badge');

      if (recordType === 'need') {
        if (modalTitle) modalTitle.textContent = 'Editar Demanda Búsqueda';
        if (modalSub) modalSub.textContent = 'Modifica los criterios y presupuesto del comprador';
        if (priceLabel) priceLabel.innerHTML = 'Presupuesto Máximo (€) <span class="text-red-500">*</span>';
        if (iconBadge) { iconBadge.className = 'w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl shrink-0'; iconBadge.textContent = '🎯'; }
      } else {
        if (modalTitle) modalTitle.textContent = 'Editar Captación Inmobiliaria';
        if (modalSub) modalSub.textContent = 'Modifica los datos y precio de tu captación en cartera';
        if (priceLabel) priceLabel.innerHTML = 'Precio (€) <span class="text-red-500">*</span>';
        if (iconBadge) { iconBadge.className = 'w-10 h-10 rounded-2xl bg-blue/10 text-blue flex items-center justify-center text-xl shrink-0'; iconBadge.textContent = '✏️'; }
      }

      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function closeEditRecordModal() {
      const modal = document.getElementById('private-edit-record-modal');
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
    }

    async function handleSaveEditRecord(e) {
      if (e) e.preventDefault();
      const submitBtn = document.getElementById('edit-record-submit-btn');
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Guardando...'; }

      const recordId = document.getElementById('edit-record-id')?.value;
      const recordType = document.getElementById('edit-record-type')?.value || 'property';
      const payload = {
        id: recordId,
        record_id: recordId,
        record_type: recordType,
        title: document.getElementById('edit-record-title')?.value,
        price: Number(document.getElementById('edit-record-price')?.value) || 0,
        property_type: document.getElementById('edit-record-property-type')?.value,
        status: document.getElementById('edit-record-status')?.value || 'active',
        province: document.getElementById('edit-record-province')?.value,
        municipality: document.getElementById('edit-record-municipality')?.value,
        zone: document.getElementById('edit-record-zone')?.value,
        bedrooms: Number(document.getElementById('edit-record-bedrooms')?.value) || 0,
        bathrooms: Number(document.getElementById('edit-record-bathrooms')?.value) || 0,
        surface_m2: Number(document.getElementById('edit-record-surface')?.value) || 0,
        description_public: document.getElementById('edit-record-description')?.value
      };

      try {
        const res = await fetch('/api/records.php?action=update', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.ok) {
          showToast('Cambios guardados con éxito.', 'success');
          closeEditRecordModal();
          await loadWordPressRealEstateRecords();
          if (recordType === 'need') renderPrivateDemands(); else renderPrivateOffers();
          renderMarketplace();
          renderDashboard();
        } else {
          showToast(data.error || 'Error al guardar cambios.', 'error');
        }
      } catch (err) {
        showToast('Error de conexión: ' + err.message, 'error');
      } finally {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Guardar cambios'; }
      }
    }

    async function togglePrivateRecordStatus(recordType, recordId, targetStatus) {
      const label = targetStatus === 'paused' ? 'pausar' : 'reactivar';
      if (!confirm(`¿Deseas ${label} este anuncio?`)) return;
      try {
        const res = await fetch('/api/records.php?action=toggle_status', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: recordId, record_id: recordId, status: targetStatus })
        });
        const data = await res.json();
        if (data.ok) {
          showToast(data.message || 'Estado actualizado.', 'success');
          await loadWordPressRealEstateRecords();
          if (recordType === 'need') renderPrivateDemands(); else renderPrivateOffers();
          renderMarketplace();
          renderDashboard();
        } else {
          showToast(data.error || 'Error al cambiar estado.', 'error');
        }
      } catch (err) {
        showToast('Error de conexión: ' + err.message, 'error');
      }
    }

    async function deletePrivateRecord(recordType, recordId) {
      const label = recordType === 'need' ? 'demanda' : 'captación';
      if (!confirm(`⚠️ ¿Estás seguro de que deseas eliminar definitivamente esta ${label}?\n\nEsta acción la retirará del Marketplace y no se puede deshacer.`)) return;
      try {
        const res = await fetch('/api/records.php?action=delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: recordId, record_id: recordId })
        });
        const data = await res.json();
        if (data.ok) {
          showToast(data.message || 'Registro eliminado con éxito.', 'success');
          await loadWordPressRealEstateRecords();
          if (recordType === 'need') renderPrivateDemands(); else renderPrivateOffers();
          renderMarketplace();
          renderDashboard();
        } else {
          showToast(data.error || 'Error al eliminar registro.', 'error');
        }
      } catch (err) {
        showToast('Error de conexión: ' + err.message, 'error');
      }
    }

    function renderPrivateOffers() {
      const tbody = document.getElementById('private-offers-table'); if (!tbody) return;
      const search = normalizeMatchText(document.getElementById('private-offers-search')?.value || '');
      const list = privateProperties().filter(property => !search || normalizeMatchText(`${property.reference} ${property.cadastral_reference_masked || ''} ${property.title} ${property.province} ${property.municipality} ${property.postalCode} ${property.status} ${(property.missing_fields||[]).join(' ')}`).includes(search));
      const summary = document.getElementById('private-offers-summary'); if (summary) summary.innerHTML = [ ['Publicadas',list.length,'text-blue'], ['Con solicitudes',(getPrivateDashboardState().requestsReceived||[]).length,'text-amber'], ['Coincidencias',list.reduce((sum,item)=>sum+getCompatibleNeedsForProperty(item,10).length,0),'text-green'], ['Cerradas',closedOperations.length,'text-navy'] ].map(([label,value,color])=>privateKpiCard(label,value,color,'offers')).join('');
      
      tbody.innerHTML = list.slice(0, 100).map(property => {
        const matches = getCompatibleNeedsForProperty(property, 10, true);
        const status = property.status || 'active';
        const missing = Array.isArray(property.missing_fields) ? property.missing_fields : [];
        const statusClass = status === 'pending_review' ? 'bg-amber-light text-amber' : status === 'paused' ? 'bg-slate-100 text-slate-500' : status === 'deleted' ? 'bg-red-50 text-red-600' : 'bg-green-light text-green';
        const isSelected = window.CAPTACION_OFFERS_SELECTION.has(String(property.id));
        
        return `<tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-850/50 transition-colors">
          <td class="px-3 py-3 text-center">
            <input type="checkbox" class="record-row-checkbox rounded text-blue cursor-pointer" data-id="${property.id}" ${isSelected ? 'checked' : ''} onchange="toggleSelectRecord('property', '${property.id}', this)" />
          </td>
          <td class="px-4 py-3"><strong class="text-blue">${escapeHTML(property.reference || property.id)}</strong></td>
          <td class="px-4 py-3">
            <strong class="block text-xs text-navy dark:text-white">${escapeHTML(property.title || property.reference || 'Propiedad importada')}</strong>
            <span class="text-[10px] text-slate-500">${escapeHTML(property.province || property.location || 'Sin provincia')} · ${escapeHTML(property.municipality || property.city || 'Sin municipio')}</span>
            ${property.cadastral_reference_masked ? `<span class="block mt-0.5 text-[10px] text-blue">Catastro: ${escapeHTML(property.cadastral_reference_masked)}</span>` : ''}
            ${missing.length ? `<span class="block mt-0.5 text-[10px] text-amber">Faltan: ${escapeHTML(missing.join(', '))}</span>` : ''}
          </td>
          <td class="px-4 py-3 font-bold text-navy dark:text-white">${formatCurrency(property.price)}</td>
          <td class="px-4 py-3"><span class="private-status-pill ${Number(property.score)>=85?'bg-green-light text-green':'bg-blue-light text-blue'}">★ ${escapeHTML(property.score || 80)}/100</span></td>
          <td class="px-4 py-3"><button type="button" onclick="openMapPropertyCard('${property.id}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue text-white text-[10px] font-bold shadow-xs hover:bg-blue-600"><span>⚡</span> ${matches.length} matches</button></td>
          <td class="px-4 py-3"><span class="private-status-pill ${statusClass}">${status === 'pending_review' ? 'Pendiente revisión' : status === 'paused' ? 'Pausada' : status === 'deleted' ? 'Eliminada' : 'Activa'}</span></td>
          <td class="px-4 py-3 text-right">
            <div class="flex items-center justify-end gap-1.5 flex-wrap">
              <button type="button" onclick="openEditRecordModal('property', '${property.id}')" class="px-2 py-1 rounded-lg bg-blue/10 hover:bg-blue/20 text-blue text-[11px] font-bold transition-colors">Editar</button>
              <button type="button" onclick="downloadBlindSheet('${property.id}')" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[11px] font-bold transition-colors">Ficha ciega</button>
              <button type="button" onclick="togglePrivateRecordStatus('property', '${property.id}', '${status === 'paused' ? 'active' : 'paused'}')" class="px-2 py-1 rounded-lg ${status === 'paused' ? 'bg-green/10 text-green hover:bg-green/20' : 'bg-amber/10 text-amber hover:bg-amber/20'} text-[11px] font-bold transition-colors">${status === 'paused' ? 'Reactivar' : 'Pausar'}</button>
              <button type="button" onclick="deletePrivateRecord('property', '${property.id}')" class="px-2 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-[11px] font-bold transition-colors">Eliminar</button>
            </div>
          </td>
        </tr>`;
      }).join('') || `<tr><td colspan="8" class="p-5 text-xs text-slate-500 text-center">No hay captaciones con esos criterios.</td></tr>`;
      
      updateRecordSelectionUi('property');
    }

    function renderPrivateDemands() {
      const tbody = document.getElementById('private-demands-table'); if (!tbody) return;
      const search = normalizeMatchText(document.getElementById('private-demands-search')?.value || '');
      const list = privateNeeds().filter(need => !search || normalizeMatchText(`${need.id} ${need.title} ${need.province} ${need.municipality} ${need.postalCode}`).includes(search));
      const summary = document.getElementById('private-demands-summary'); if (summary) summary.innerHTML = [ ['Activas',list.length,'text-navy'], ['Con coincidencias',list.filter(item=>getCompatiblePropertiesForNeed(item,10).length).length,'text-green'], ['Sin resultados',list.filter(item=>!getCompatiblePropertiesForNeed(item,10).length).length,'text-amber'], ['Solicitudes enviadas',(getPrivateDashboardState().requestsSent||[]).length,'text-blue'] ].map(([label,value,color])=>privateKpiCard(label,value,color,'demands')).join('');
      
      tbody.innerHTML = list.slice(0, 100).map(need => { 
        const matches = getCompatiblePropertiesForNeed(need, 10);
        const status = need.status || 'active';
        const statusClass = status === 'paused' ? 'bg-slate-100 text-slate-500' : status === 'deleted' ? 'bg-red-50 text-red-600' : 'bg-green-light text-green';
        const isSelected = window.CAPTACION_DEMANDS_SELECTION.has(String(need.id));

        return `<tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-850/50 transition-colors">
          <td class="px-3 py-3 text-center">
            <input type="checkbox" class="record-row-checkbox rounded text-green cursor-pointer" data-id="${need.id}" ${isSelected ? 'checked' : ''} onchange="toggleSelectRecord('need', '${need.id}', this)" />
          </td>
          <td class="px-4 py-3"><strong class="text-green">${escapeHTML(need.id)}</strong></td>
          <td class="px-4 py-3">
            <strong class="block text-xs text-navy dark:text-white">${escapeHTML(need.title)}</strong>
            <span class="text-[10px] text-slate-500">${escapeHTML(need.province || '')} · C.P. ${escapeHTML(need.postalCode || 'N/D')} · ${formatPropertyFeatures(need,true)}</span>
          </td>
          <td class="px-4 py-3 font-bold text-navy dark:text-white">Hasta ${formatCurrency(need.budget)}</td>
          <td class="px-4 py-3"><span class="private-status-pill ${matches.length?'bg-green-light text-green':'bg-amber-light text-amber'}">${matches.length}</span></td>
          <td class="px-4 py-3"><span class="private-status-pill ${statusClass}">${status === 'paused' ? 'Pausada' : status === 'deleted' ? 'Eliminada' : 'Activa'}</span></td>
          <td class="px-4 py-3 text-right">
            <div class="flex items-center justify-end gap-1.5 flex-wrap">
              <button type="button" onclick="openHomeNeedMatches('${need.id}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-green text-white text-[10px] font-bold shadow-xs hover:bg-green-600"><span>🏢</span> Ver ${matches.length} captaciones →</button>
              <button type="button" onclick="openEditRecordModal('need', '${need.id}')" class="px-2 py-1 rounded-lg bg-blue/10 hover:bg-blue/20 text-blue text-[11px] font-bold transition-colors">Editar</button>
              <button type="button" onclick="togglePrivateRecordStatus('need', '${need.id}', '${status === 'paused' ? 'active' : 'paused'}')" class="px-2 py-1 rounded-lg ${status === 'paused' ? 'bg-green/10 text-green hover:bg-green/20' : 'bg-amber/10 text-amber hover:bg-amber/20'} text-[11px] font-bold transition-colors">${status === 'paused' ? 'Reactivar' : 'Pausar'}</button>
              <button type="button" onclick="deletePrivateRecord('need', '${need.id}')" class="px-2 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-[11px] font-bold transition-colors">Eliminar</button>
            </div>
          </td>
        </tr>`; 
      }).join('') || `<tr><td colspan="7" class="p-5 text-xs text-slate-500 text-center">No hay demandas con esos criterios.</td></tr>`;
      
      updateRecordSelectionUi('need');
    }

    function requestCard(item, received = false) {
      const property = privatePropertyById(item.propertyId);
      return `<article class="private-mini-card"><div class="flex items-start justify-between gap-3"><div><span class="text-[10px] font-black text-blue">${escapeHTML(property?.reference || item.propertyId)}</span><strong class="block text-sm text-navy mt-1">${escapeHTML(property?.title || 'Captación')}</strong><span class="block text-[10px] text-slate-500 mt-1">${escapeHTML(item.agency)} · ${formatRelativeTime(item.createdAt)}</span></div><span class="private-status-pill ${privateStatusClasses(item.status)}">${escapeHTML(item.status)}</span></div><p class="text-[11px] text-slate-500 mt-3">${escapeHTML(item.note)}</p><div class="flex flex-wrap gap-2 mt-3">${received && item.status.includes('Pendiente') ? `<button onclick="confirmPrivateRequest('${item.id}')" class="px-3 py-2 rounded-lg bg-green text-white text-[10px] font-bold">Confirmar disponibilidad</button>` : ''}<button onclick="openMapPropertyCard('${item.propertyId}')" class="px-3 py-2 rounded-lg border border-slate-200 text-navy text-[10px] font-bold">Abrir captación</button></div></article>`;
    }
    function renderPrivateRequests() { const state=getPrivateDashboardState(); const received=document.getElementById('private-requests-received'); const sent=document.getElementById('private-requests-sent'); if(received) received.innerHTML=(state.requestsReceived||[]).map(item=>requestCard(item,true)).join('')||`<p class="text-xs text-slate-500">No hay solicitudes recibidas.</p>`; if(sent) sent.innerHTML=(state.requestsSent||[]).map(item=>requestCard(item,false)).join('')||`<p class="text-xs text-slate-500">No hay solicitudes enviadas.</p>`; }
    function confirmPrivateRequest(id) { const state=getPrivateDashboardState(); const item=(state.requestsReceived||[]).find(row=>row.id===id); if(!item)return; item.status='Disponible · Acuerdo de Confidencialidad (NDA) pendiente'; state.activities.unshift({id:`ACT-${Date.now()}`,icon:'✓',title:'Disponibilidad confirmada',detail:'Se ha activado el flujo protegido del Acuerdo de Confidencialidad (NDA).',createdAt:Date.now()}); persistPrivateDashboardState(state); addPrivateNotification({category:'Operaciones',title:'Acuerdo de Confidencialidad (NDA) pendiente tras confirmar disponibilidad',detail:'La operación asociada a la solicitud confirmada requiere preparar el acuerdo de confidencialidad.',target:'operations',dueAt:Date.now()+3600000*4,dedupeKey:`notif-nda-${id}`}); addPrivateTask({title:'Preparar Acuerdo de Confidencialidad (NDA) de la solicitud confirmada',detail:'Agenda la firma y valida las siguientes tareas del expediente protegido.',priority:'high',due:'Hoy',dueAt:Date.now()+3600000*8,target:'operations',dedupeKey:`task-nda-${id}`}); renderDashboard(); showToast('Disponibilidad confirmada. El siguiente paso es gestionar el Acuerdo de Confidencialidad (NDA).', 'success'); }

    function renderPrivateOperations() {
      const tbody=document.getElementById('private-operations-table'); if(!tbody)return; const state=getPrivateDashboardState(); const filter=document.getElementById('private-operation-status-filter')?.value||''; const list=(state.operations||[]).filter(item=>!filter||item.status===filter);
      tbody.innerHTML=list.map(operation=>{const property=privatePropertyById(operation.propertyId);const need=privateNeedById(operation.needId);return `<tr class="border-b border-slate-100"><td class="px-4 py-3"><strong class="text-blue">${escapeHTML(operation.id)}</strong></td><td class="px-4 py-3"><strong class="block text-xs text-navy dark:text-white">${escapeHTML(property?.title||'Captación')}</strong><span class="text-[10px] text-slate-500">${escapeHTML(need?.title||'Demanda vinculada')}</span></td><td class="px-4 py-3">${escapeHTML(operation.collaborator)}</td><td class="px-4 py-3"><span class="private-status-pill ${privateStatusClasses(operation.status)}">${escapeHTML(operation.status)}</span></td><td class="px-4 py-3">${formatRelativeTime(operation.updatedAt)}</td><td class="px-4 py-3">${escapeHTML(operation.nextAction)}</td><td class="px-4 py-3"><button type="button" onclick="openPrivateOperationModal('${operation.id}')" class="px-3 py-1.5 rounded-lg bg-blue hover:bg-blue-dark text-white text-[11px] font-bold shadow-xs">Expediente →</button></td></tr>`}).join('')||`<tr><td colspan="7" class="p-5 text-xs text-slate-500">No existen operaciones con ese estado.</td></tr>`;
    }
    function isClosedOperation(operation) { return ['closed','completada','completado','cerrada','cerrado'].includes(String(operation?.status || '').toLowerCase()); }
    async function createPrivateDossierLink(operationId) {
      try { const response = await fetch('/api/dossiers.php?action=create', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body:JSON.stringify({operation_id:Number(operationId)}) }); const data = await response.json(); if (!response.ok || !data.ok) throw new Error(data.error || 'No se pudo crear el enlace privado.'); await navigator.clipboard.writeText(new URL(data.url, window.location.origin).href); showToast('Enlace privado creado y copiado. Caduca en 7 días.', 'success'); } catch (error) { showToast(error.message || 'No se pudo crear el enlace privado.', 'error'); }
    }

    function prepareXmlSupportRequest(errorMessage = '', context = {}) {
      const message = `Problema al importar XML.\n\nDetalle técnico: ${String(errorMessage || 'Error no especificado').slice(0, 800)}\n\nFuente: ${String(context.source || 'XML del usuario').slice(0, 300)}\nLote: ${String(context.batchId || 'No generado')}`;
      try { sessionStorage.setItem('captacion_support_draft_v1', JSON.stringify({ topic:'Resolver un problema técnico', message })); } catch (error) {}
      const confirmed = window.confirm('No se pudo completar la importación del XML. Se ha preparado una consulta para soporte. Pulsa Aceptar para revisarla y enviarla a hola@compracaptacion.com.');
      if (confirmed) navigateTo('/contacto');
    }
    async function loadPrivateOperationsFromBackend() {
      try {
        const response = await fetch('/api/operations.php?action=list', { credentials: 'same-origin' });
        if (!response.ok) return;
        const data = await response.json();
        if (!data.ok || !Array.isArray(data.operations)) return;
        const state = getPrivateDashboardState();
        const backend = data.operations.map(row => ({ ...row, id: String(row.id), propertyId: row.record_id, collaborator: row.collaborator_name || row.owner_name || 'Colaborador', status: row.status === 'closed' ? 'Completada' : row.status === 'cancelled' ? 'Cancelada' : row.status, nextAction: row.status === 'closed' ? 'Compartir dossier privado' : 'Continuar flujo de firma', updatedAt: row.updated_at, createdAt: row.created_at }));
        const localOnly = (state.operations || []).filter(item => !backend.some(row => String(row.id) === String(item.id)) && !/^\d+$/.test(String(item.id || '')));
        state.operations = [...backend, ...localOnly]; persistPrivateDashboardState(state); renderPrivateOperations();
      } catch (error) { /* La vista local sigue disponible si el backend no está accesible. */ }
    }
    function copyPrivateDossierLink(url) { navigator.clipboard?.writeText(url).then(()=>showToast('Enlace privado copiado.','success')).catch(()=>window.prompt('Copia este enlace privado:', url)); }
    async function revokePrivateDossier(tokenId) { if (!window.confirm('¿Revocar este enlace privado?')) return; try { const response=await fetch('/api/dossiers.php?action=revoke',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({token_id:Number(tokenId)})}); const data=await response.json(); if(!response.ok||!data.ok)throw new Error(data.error||'No se pudo revocar el enlace.'); showToast('Enlace privado revocado.','success'); } catch(error){ showToast(error.message||'No se pudo revocar el enlace.','error'); } }

    function openPrivateOperationModal(operationId){
      const state=getPrivateDashboardState();
      const operation=(state.operations||[]).find(item=>item.id===operationId);
      if(!operation)return;
      const property=privatePropertyById(operation.propertyId) || { title: 'Inmueble en expediente', price: 250000, reference: operation.propertyId || 'EXP-01' };
      const need=privateNeedById(operation.needId) || { title: 'Demanda compradora cualificada', budget: property.price || 250000 };
      const modal=document.getElementById('private-operation-modal');
      const title=document.getElementById('private-operation-modal-title');
      const body=document.getElementById('private-operation-modal-body');
      if(title)title.textContent=`Expediente ${operation.id} · ${operation.status}`;
      
      const estimatedFee = Math.round((Number(property.price || 250000) * 0.04) / 2);

      if(body) {
        body.innerHTML=`
        <div class="space-y-4">
          <div class="p-4 rounded-2xl bg-gradient-to-r from-blue/5 to-purple-500/5 border border-blue/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-blue">Estado de la Colaboración</span>
              <h4 class="text-base font-black text-navy mt-0.5">${escapeHTML(operation.status)}</h4>
              <p class="text-xs text-slate-500 mt-1">Colaborador: <strong>${escapeHTML(operation.collaborator)}</strong></p>
            </div>
            <div class="sm:text-right">
              <span class="text-[10px] font-black uppercase tracking-wider text-green">Honorarios 50/50 Previstos</span>
              <strong class="block text-xl font-black text-navy">${formatCurrency(estimatedFee)}</strong>
              <span class="text-[10px] text-slate-500">Reparto 50% en notaría (Art. 1255 CC)</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
              <span class="text-[10px] font-black uppercase text-blue">Propiedad / Captación</span>
              <strong class="block text-sm text-navy mt-1">${escapeHTML(property.title || 'Inmueble')}</strong>
              <span class="block text-xs text-slate-500 mt-1">${formatCurrency(property.price)} · Ref: ${escapeHTML(property.reference || property.id || 'N/D')}</span>
              <div class="mt-3">
                <button type="button" onclick="downloadBlindSheet('${property.id || ''}')" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-navy text-[10px] font-bold shadow-xs">📄 Descargar Ficha Ciega</button>
              </div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
              <span class="text-[10px] font-black uppercase text-green">Demanda del Comprador</span>
              <strong class="block text-sm text-navy mt-1">${escapeHTML(need.title || 'Demanda')}</strong>
              <span class="block text-xs text-slate-500 mt-1">Presupuesto: Hasta ${formatCurrency(need.budget)}</span>
              <div class="mt-3">
                <button type="button" onclick="switchPrivateDashboardPanel('demands');closePrivateOperationModal();" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-navy text-[10px] font-bold shadow-xs">🎯 Ver Demanda</button>
              </div>
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-white border border-slate-200">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2 mb-3">
              <h5 class="text-xs font-black text-navy uppercase tracking-wider">Custodia Legal y Próxima Acción</h5>
              <span class="px-2 py-0.5 rounded-full bg-blue-light text-blue text-[10px] font-bold">Art. 1255 C.C.</span>
            </div>
            <p class="text-xs text-navy font-bold">${escapeHTML(operation.nextAction || 'Continuar trámite de expediente')}</p>
            <p class="text-[11px] text-slate-500 mt-1">Los datos de contacto directo de la parte contraria se custodian bajo acuerdo de confidencialidad (NDA) para garantizar el reparto del 50% de honorarios en escritura notarial.</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <button type="button" onclick="switchPrivateDashboardPanel('communications');closePrivateOperationModal();" class="px-3.5 py-2 rounded-xl bg-navy text-white text-xs font-bold shadow-sm">💬 Abrir Mensajes Protegidos</button>
              <button type="button" onclick="scheduleAgreementCalendarPlan('nda', '${escapeHTML(property.reference || 'EXP')}', '');closePrivateOperationModal();" class="px-3.5 py-2 rounded-xl border border-slate-200 text-blue text-xs font-bold">📅 Agendar Firma de NDA</button>
            </div>
          </div>

          <div>
            <h5 class="text-xs font-black text-navy uppercase tracking-wider mb-2">Trazabilidad del Expediente</h5>
            <div class="space-y-2">
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-600">Apertura de expediente y registro de demanda</span>
                <span class="text-slate-400 font-mono text-[10px]">${privateSafeDate(operation.createdAt)}</span>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-600">Última actualización: <strong>${escapeHTML(operation.status)}</strong></span>
                <span class="text-slate-400 font-mono text-[10px]">${privateSafeDate(operation.updatedAt)}</span>
              </div>
            </div>
          </div>
        </div>
        `;
      }
      if (body && isClosedOperation(operation) && Number.isInteger(Number(operation.id))) body.insertAdjacentHTML('beforeend', `<div class="mt-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200"><h5 class="text-xs font-black text-emerald-800 uppercase tracking-wider">Dossier privado post-operación</h5><p class="text-[11px] text-emerald-700 mt-1">Genera un enlace protegido con caducidad de 7 días y revócalo cuando quieras.</p><button type="button" onclick="createPrivateDossierLink(${Number(operation.id)})" class="mt-3 px-3.5 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold">Crear enlace privado</button><p class="text-[10px] text-slate-500 mt-2">El enlace se copiará automáticamente al portapapeles.</p></div>`);
      modal?.classList.remove('hidden');
    }
    function closePrivateOperationModal(){document.getElementById('private-operation-modal')?.classList.add('hidden');}

    function renderPrivateTasks(){const container=document.getElementById('private-tasks-list');if(!container)return;const state=getPrivateDashboardState();container.innerHTML=(state.tasks||[]).map(item=>`<article class="private-section-card p-4 ${item.status==='done'?'opacity-60':''}"><div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div class="flex items-start gap-3"><button onclick="completePrivateTask('${item.id}')" class="mt-0.5 w-6 h-6 rounded-lg border border-slate-300 bg-white text-[11px]">${item.status==='done'?'✓':''}</button><div><div class="flex flex-wrap items-center gap-2"><strong class="text-sm text-navy">${escapeHTML(item.title)}</strong><span class="private-status-pill ${item.priority==='high'?'bg-red-50 text-red-600':item.priority==='medium'?'bg-amber-light text-amber':'bg-blue-light text-blue'}">${privatePriorityLabel(item.priority)}</span></div><p class="text-[11px] text-slate-500 mt-1">${escapeHTML(item.detail)}</p><span class="block text-[10px] text-slate-400 mt-2">${escapeHTML(item.due)}</span></div></div><button onclick="switchPrivateDashboardPanel('${item.target||'overview'}')" class="text-[11px] font-bold text-blue">Abrir contexto →</button></div></article>`).join('');}
    function completePrivateTask(id){const state=getPrivateDashboardState();const item=(state.tasks||[]).find(row=>row.id===id);if(!item)return;item.status=item.status==='done'?'pending':'done';persistPrivateDashboardState(state);renderDashboard();showToast(item.status==='done'?'Tarea completada.':'Tarea reactivada.','success');}

    function openPrivateNotificationContext(id){const state=getPrivateDashboardState();const item=(state.notifications||[]).find(row=>row.id===id);if(!item)return;if(item.propertyId){openMapPropertyCard(item.propertyId);return}switchPrivateDashboardPanel(item.target||'overview')}
    function renderPrivateNotifications(){const container=document.getElementById('private-notifications-list');if(!container)return;const state=getPrivateDashboardState();container.innerHTML=(state.notifications||[]).map(item=>`<article class="private-section-card p-4 ${item.read?'opacity-70':''}"><div class="flex items-start justify-between gap-4"><div><span class="text-[10px] font-black uppercase tracking-wider text-blue">${escapeHTML(item.category)}</span><strong class="block text-sm text-navy mt-1">${escapeHTML(item.title)}</strong><p class="text-[11px] text-slate-500 mt-1">${escapeHTML(item.detail)}</p><span class="block text-[10px] text-slate-400 mt-2">${formatRelativeTime(item.createdAt)}</span></div><div class="flex flex-col gap-2 items-end">${!item.read?`<button onclick="markPrivateNotificationRead('${item.id}')" class="px-3 py-2 rounded-lg bg-blue text-white text-[10px] font-bold">Marcar leída</button>`:''}<button onclick="openPrivateNotificationContext('${item.id}')" class="text-[10px] font-bold text-blue">${item.propertyId?'Ver captación':'Abrir'} →</button></div></div></article>`).join('');}
    function markPrivateNotificationRead(id){const state=getPrivateDashboardState();const item=(state.notifications||[]).find(row=>row.id===id);if(item)item.read=true;persistPrivateDashboardState(state);renderDashboard();}
    function markAllPrivateNotificationsRead(){const state=getPrivateDashboardState();(state.notifications||[]).forEach(item=>item.read=true);persistPrivateDashboardState(state);renderDashboard();showToast('Notificaciones marcadas como leídas.','success');}

    const FISCAL_PROFILE_FIELDS = { firstName:'profile-first-name', lastName:'profile-last-name', contactEmail:'profile-contact-email', logoUrl:'profile-logo-url', description:'profile-description', linkedin:'profile-linkedin', instagram:'profile-instagram', facebook:'profile-facebook', specialties:'profile-specialties', coverage:'profile-coverage', legalName:'fiscal-legal-name', tradeName:'fiscal-trade-name', profileType:'fiscal-profile-type', taxId:'fiscal-tax-id', billingEmail:'fiscal-billing-email', phone:'fiscal-phone', address:'fiscal-address', postalCode:'fiscal-postal-code', ccaa:'fiscal-ccaa', municipality:'fiscal-municipality', province:'fiscal-province', country:'fiscal-country', activity:'fiscal-activity', website:'fiscal-website', notes:'fiscal-notes' };
    const PROFESSIONAL_PROFILE_REQUIRED_FIELDS = ['firstName','contactEmail','phone','ccaa','province'];
    const PROFESSIONAL_PROFILE_WEIGHTS = { firstName:5, lastName:3, contactEmail:7, phone:7, logoUrl:5, description:10, specialties:6, coverage:8, linkedin:3, instagram:2, facebook:2, legalName:5, tradeName:4, profileType:3, taxId:5, billingEmail:4, address:4, postalCode:2, ccaa:3, province:3, municipality:2, country:1, activity:2, website:2, notes:2 };

    function getEffectiveProfessionalProfile(profile = {}) {
      const session = getDemoSession?.() || {};
      const nameParts = String(session.name || '').trim().split(/\s+/).filter(Boolean);
      return {
        ...profile,
        firstName: profile.firstName || nameParts.shift() || '',
        lastName: profile.lastName || nameParts.join(' '),
        contactEmail: profile.contactEmail || session.email || '',
        phone: profile.phone || session.whatsapp || '',
        tradeName: profile.tradeName || session.agency || ''
      };
    }

    function getProfessionalProfileProgress(profile = {}) {
      const effective = getEffectiveProfessionalProfile(profile);
      const fields = Object.keys(FISCAL_PROFILE_FIELDS);
      // El porcentaje solo cuenta datos guardados explícitamente en el perfil.
      // No se deben contabilizar los valores heredados de la sesión (nombre,
      // email o agencia), porque eso hacía que la tarjeta y el encabezado
      // mostraran porcentajes distintos.
      const filled = fields.filter(key => String(profile[key] || '').trim()).length;
      return { effective, completed:filled, total:fields.length, earnedWeight:filled, totalWeight:fields.length, percentage:fields.length ? Math.min(100, Math.round((filled / fields.length) * 100)) : 0 };
    }

    function getCanonicalPrivateCredits() {
      const state = marketplaceAccessState || CAPTACION_MAILCHIMP?.accessState || {};
      const session = typeof getDemoSession === 'function' ? getDemoSession() : {};
      const dbWallet = getPrivateDashboardState().wallet || {};
      
      // Saldo de bienvenida garantizado: 3 créditos activos iniciales (válidos 30 días)
      let available = Number(dbWallet.available_balance ?? state.remaining_marketplace_accesses ?? session.credits ?? 3);
      if (available === 250) {
        available = 3;
        if (session) {
          session.credits = 3;
          try {
            sessionStorage.setItem('captacion_app_session_v1', JSON.stringify(session));
            localStorage.setItem('captacion_demo_session_v4', JSON.stringify(session));
          } catch(e){}
        }
      }
      const total = Number(state.monthly_total_accesses ?? (state.is_founder ? 20 : 3));
      const consumed = Number(dbWallet.consumed_balance ?? state.monthly_consumed_accesses ?? 0);
      
      return { available, total: Math.max(available, total), consumed };
    }

    function updateDashboardCreditSummary() {
      const { available, total, consumed } = getCanonicalPrivateCredits();
      const formatted = Number(available || 0).toFixed(2).replace('.', ',');
      const totalFormatted = Math.floor(available);
      
      // 1. Sincronizar topbar
      const topbarEl = document.getElementById('private-topbar-credit-val');
      if (topbarEl) topbarEl.textContent = formatted;
      
      // 2. Sincronizar sidebar pill
      const sidebarPill = document.getElementById('private-sidebar-credit-pill');
      if (sidebarPill) sidebarPill.textContent = String(totalFormatted);
      
      // 3. Sincronizar strip del resumen
      const valueEl = document.getElementById('dashboard-credit-summary-value');
      if (valueEl) valueEl.textContent = formatted;
      
      const titleEl = document.getElementById('dashboard-credit-summary-title');
      if (titleEl) titleEl.textContent = 'Créditos Disponibles';
      
      const helpEl = document.getElementById('dashboard-credit-summary-help');
      if (helpEl) helpEl.textContent = `${totalFormatted} créditos de bienvenida activos (válidos 30 días, no acumulables)`;
      
      const bar = document.getElementById('dashboard-credit-summary-bar');
      if (bar) bar.style.width = '100%';
      
      // 4. Sincronizar centro de créditos y libro mayor
      const ledgerAvail = document.getElementById('ledger-avail-balance');
      if (ledgerAvail) ledgerAvail.textContent = `${formatted} Créditos`;
      
      const ledgerConsumed = document.getElementById('ledger-consumed-balance');
      if (ledgerConsumed) ledgerConsumed.textContent = `${Number(consumed || 0).toFixed(2).replace('.', ',')} Usados`;
    }

    async function refreshCreditsExperience() {
      if (!CAPTACION_MAILCHIMP?.loggedIn || !CAPTACION_MAILCHIMP?.creditsStatusEndpoint) return;
      try {
        const response = await fetch(CAPTACION_MAILCHIMP.creditsStatusEndpoint, { credentials:'same-origin', headers:{'X-WP-Nonce':CAPTACION_MAILCHIMP.nonce} });
        if (!response.ok) return;
        const data = await response.json(); const wallet = data.wallet || {}; const welcome = data.welcome || {}; const referral = data.referral || {};
        const formatCredits = value => Number(value || 0).toFixed(2).replace('.', ',');
        const welcomeAlert = document.getElementById('welcome-credit-alert');
        const welcomeAlertText = document.getElementById('welcome-credit-alert-text');
        const expiresAt = wallet.expires_at ? new Date(String(wallet.expires_at).replace(' ', 'T')) : null;
        const hasActiveWelcome = Number(wallet.available_balance || 0) > 0 && expiresAt && expiresAt.getTime() > Date.now();
        if (welcomeAlert) welcomeAlert.classList.toggle('hidden', !hasActiveWelcome);
        if (welcomeAlertText && hasActiveWelcome) {
          const days = Math.max(1, Math.ceil((expiresAt.getTime() - Date.now()) / 86400000));
          welcomeAlertText.textContent = `Te quedan ${formatCredits(wallet.available_balance)} créditos de bienvenida. Úsalos para descubrir oportunidades antes de que caduquen en ${days} ${days === 1 ? 'día' : 'días'}.`;
        }
        const total = Math.max(5, Number(welcome.required_listings || 5)); const progress = Math.min(100, Math.round((Number(welcome.valid_listings || 0) / total) * 70 + (Number(welcome.current_active_days || 0) / 7) * 30));
        const bar=document.getElementById('dashboard-welcome-bar'); if(bar) bar.style.width=`${progress}%`;
        const status=document.getElementById('dashboard-welcome-status'); if(status) status.textContent=welcome.status==='active'?'Créditos activos':`${welcome.valid_listings || 0} de ${total} anuncios`;
        const help=document.getElementById('dashboard-welcome-help'); if(help) help.textContent=welcome.status==='active' ? `Activos hasta ${welcome.credits_expire_at || 'la fecha indicada'}.` : `Permanencia: ${welcome.current_active_days || 0} de 7 días. Los anuncios deben ser diferentes y mantenerse activos.`;
        const referralHelp=document.getElementById('dashboard-referral-help'); if(referralHelp) referralHelp.textContent=`Tu enlace de referidos: ${referral.url || ''}`;
        const value=document.getElementById('dashboard-credit-summary-value'); if(value) value.textContent=formatCredits(wallet.available_balance || 3);
        [['dashboard-credit-available','available_balance'],['dashboard-credit-pending','pending_balance'],['dashboard-credit-reserved','reserved_balance'],['dashboard-credit-consumed','consumed_balance']].forEach(([id,key]) => { const element=document.getElementById(id); if(element) element.textContent=formatCredits(wallet[key] || (key==='available_balance'?3:0)); });
        updateDashboardCreditSummary();
      } catch (error) { /* El dashboard existente sigue funcionando aunque el módulo no esté disponible. */ }
    }

    async function refreshReputationExperience() {
      const scoreEl = document.getElementById('dashboard-reputation-score');
      if (!scoreEl || !CAPTACION_MAILCHIMP?.loggedIn) return;
      try {
        const response = await fetch('/api/reputation.php?action=me', { credentials:'same-origin' });
        if (!response.ok) return;
        const data = await response.json();
        const reputation = data.reputation || {};
        const score = Math.max(0, Math.min(100, Number(reputation.score || 0)));
        const labels = {
          new_professional:'Nuevo profesional',
          growing_professional:'Profesional en crecimiento',
          active_professional:'Profesional activo',
          verified_professional:'Profesional verificado',
          featured_professional:'Profesional destacado',
          limited_activity:'Actividad limitada'
        };
        scoreEl.textContent = String(Math.round(score));
        const categoryEl = document.getElementById('dashboard-reputation-category');
        if (categoryEl) categoryEl.textContent = labels[reputation.category] || 'Sin categoría';
        const helpEl = document.getElementById('dashboard-reputation-help');
        if (helpEl) helpEl.textContent = `${reputation.completed_operations || 0} operaciones cerradas · ${reputation.verified_reviews_count || 0} reseñas verificadas`;
      } catch (error) { scoreEl.textContent = '—'; }
    }

    function updateProfessionalProfileProgress(profile = {}) {
      const progress = getProfessionalProfileProgress(profile);
      const level = progress.percentage >= 100 ? 'complete' : progress.percentage >= 60 ? 'medium' : 'low';
      const title = progress.percentage >= 100 ? 'Perfil profesional completo' : `Tu perfil profesional está al ${progress.percentage}%`;
      const help = progress.percentage >= 100 ? 'Has completado todos los datos. Tu perfil dispone de la Insignia de Agente Verificado 50/50 y máxima prioridad.' : `Has completado ${progress.completed} de ${progress.total} campos. Completa tus datos para obtener la Insignia de Agente Verificado y +40% de respuestas.`;
      const apply = (boxId,titleId,valueId,barId,helpId) => {
        const box=document.getElementById(boxId); if(!box)return;
        box.classList.remove('hidden','profile-progress-low','profile-progress-medium','profile-progress-complete'); box.classList.add(`profile-progress-${level}`);
        const titleEl=document.getElementById(titleId); if(titleEl)titleEl.textContent=title;
        const valueEl=document.getElementById(valueId); if(valueEl)valueEl.textContent=`${progress.percentage}%`;
        const bar=document.getElementById(barId); if(bar)bar.style.width=`${progress.percentage}%`;
        const helpEl=document.getElementById(helpId); if(helpEl)helpEl.textContent=help;
        box.querySelector('[role="progressbar"]')?.setAttribute('aria-valuenow',String(progress.percentage));
      };
      apply('dashboard-profile-completion-banner','dashboard-profile-completion-title','dashboard-profile-completion-value','dashboard-profile-completion-bar','dashboard-profile-completion-help');
      apply('professional-profile-progress-notice','professional-profile-progress-title','professional-profile-progress-value','professional-profile-progress-bar','professional-profile-progress-help');
      const header=document.getElementById('private-profile-header-badge'); 
      if(header){
        header.textContent=progress.percentage>=100?'Perfil 100% (Verificado)':`Perfil ${progress.percentage}%`;
        header.className=`px-3 py-2 rounded-xl text-xs font-bold ${level==='complete'?'bg-green-light text-green':level==='medium'?'bg-amber-light text-amber':'bg-orange-100 text-orange-700'}`;
      }
      const action=document.getElementById('dashboard-profile-completion-action'); 
      if(action)action.textContent=progress.percentage>=100?'Ver mi perfil':'Completar mi perfil';
      updateDashboardCreditSummary();
      return progress;
    }

    function renderPrivateFiscalProfile() {
      const profile = getPrivateDashboardState().fiscalProfile || {};
      const progress = getProfessionalProfileProgress(profile);
      Object.entries(FISCAL_PROFILE_FIELDS).forEach(([key,id]) => { const element=document.getElementById(id); if(element && !['ccaa','province','municipality'].includes(key)) element.value=progress.effective[key] || ''; });
      TerritorySelector.instances['fiscal-profile']?.setValues({ccaa:profile.ccaa||'',province:profile.province||'',municipality:profile.municipality||'',postalCode:profile.postalCode||''});
      const logoPreview=document.getElementById('profile-logo-preview'); const logoRemove=document.getElementById('profile-logo-remove');
      if(logoPreview){logoPreview.src=profile.logoUrl||'';logoPreview.classList.toggle('hidden',!profile.logoUrl);} if(logoRemove)logoRemove.classList.toggle('hidden',!profile.logoUrl);
      const status=document.getElementById('fiscal-profile-status');
      if(status)status.textContent=`${progress.completed} de ${progress.total} datos esenciales completados. Los datos opcionales no reducen el porcentaje.`;
      updateProfessionalProfileProgress(profile);
    }

    function savePrivateFiscalProfile(event) {
      event.preventDefault();
      if (!requireRegisteredAction('guardar el perfil profesional')) return;
      const state=getPrivateDashboardState(); const profile={};
      Object.entries(FISCAL_PROFILE_FIELDS).forEach(([key,id])=>{profile[key]=cleanText(document.getElementById(id)?.value||'');});
      const fiscalTerritory=TerritorySelector.instances['fiscal-profile']?.getValue()||{};
      profile.ccaa=fiscalTerritory.ccaa||profile.ccaa||''; profile.province=fiscalTerritory.province||profile.province||''; profile.municipality=fiscalTerritory.municipality||profile.municipality||''; profile.postalCode=fiscalTerritory.postalCode||profile.postalCode||''; profile.territory=fiscalTerritory;
      profile.updatedAt=Date.now(); state.fiscalProfile=profile; persistPrivateDashboardState(state);
      const email=getDemoSession?.()?.email||'';
      persistWpRecord('user_preferences',profile,{recordKey:`fiscal-profile-${email||'guest'}`,userEmail:email,title:'Perfil profesional y fiscal',status:'active'});
      renderPrivateFiscalProfile(); addPrivateActivity('✓','Perfil profesional actualizado','Se han guardado los datos profesionales y fiscales privados.');
      showToast('Perfil profesional guardado correctamente.','success');
    }

    async function uploadProfessionalProfileLogo(file) {
      const status=document.getElementById('profile-logo-upload-status');
      if(!file)return;
      if(!['image/jpeg','image/png','image/webp'].includes(file.type)){if(status)status.textContent='Formato no admitido. Usa JPG, PNG o WEBP.';showToast('Formato de logotipo no admitido.','info');return;}
      if(file.size>2*1024*1024){if(status)status.textContent='La imagen supera el máximo de 2 MB.';showToast('El logotipo no puede superar 2 MB.','info');return;}
      if(status)status.textContent='Cargando logotipo…';
      const formData=new FormData();formData.append('file',file,file.name);
      try{
        const response=await fetch(CAPTACION_API.endpoints.profileLogo,{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':CAPTACION_API.nonce},body:formData});
        const data=await response.json();if(!response.ok||!data.ok)throw new Error(data.message||'No se pudo cargar el logotipo.');
        const hidden=document.getElementById('profile-logo-url');if(hidden)hidden.value=data.url||'';
        const preview=document.getElementById('profile-logo-preview');if(preview){preview.src=data.url||'';preview.classList.toggle('hidden',!data.url);}
        document.getElementById('profile-logo-remove')?.classList.remove('hidden');if(status)status.textContent='Logotipo cargado. Guarda el perfil para conservar el cambio.';showToast('Logotipo cargado correctamente.','success');
      }catch(error){if(status)status.textContent=error.message;showToast(error.message,'error');}
    }

    function removeProfessionalProfileLogo(){const hidden=document.getElementById('profile-logo-url');if(hidden)hidden.value='';const preview=document.getElementById('profile-logo-preview');if(preview){preview.src='';preview.classList.add('hidden');}document.getElementById('profile-logo-remove')?.classList.add('hidden');const status=document.getElementById('profile-logo-upload-status');if(status)status.textContent='Logotipo retirado del perfil. Guarda los cambios para confirmar.';}

    function getCurrentPlanType() { return marketplaceAccessState?.plan_type || CAPTACION_MAILCHIMP?.accessState?.plan_type || getDemoSession?.()?.planType || 'basic'; }

    function applyDashboardPlanAccess() {
      const plan = getCurrentPlanType();
      const premium = plan === 'premium';
      const badge = document.getElementById('private-plan-access-badge');
      if (badge) badge.textContent = 'Acceso Premium Ilimitado';
      document.getElementById('private-tasks-premium-content')?.classList.toggle('hidden', !premium);
      document.getElementById('private-tasks-premium-lock')?.classList.toggle('hidden', premium);
      document.getElementById('private-overview-calendar-section')?.classList.toggle('hidden', !premium);
    }

    async function respondOperationRequest(operationId, decision) {
      const id = Number(operationId);
      if (!Number.isInteger(id) || id <= 0 || !['accept', 'reject'].includes(decision)) {
        showToast('Solicitud de operación no válida.', 'info');
        return false;
      }
      try {
        const response = await fetch(`/api/operations.php?action=respond_request`, {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.CAPTACION_API?.nonce || '' },
          body: JSON.stringify({ operation_id: id, decision })
        });
        const data = await response.json();
        if (!response.ok || !data?.ok) throw new Error(data?.error || 'No se pudo responder a la solicitud.');
        showToast(data.message || (decision === 'accept' ? 'Solicitud aceptada.' : 'Solicitud rechazada y crédito liberado.'), 'success');
        return true;
      } catch (error) {
        showToast(error.message || 'No se pudo responder a la solicitud.', 'error');
        return false;
      }
    }
    window.respondOperationRequest = respondOperationRequest;

    // -------------------------------------------------------------
    // PROGRAMA DE REFERIDOS B2B & PRODUCT-LED GROWTH (PLG)
    // -------------------------------------------------------------
    let currentPLGState = {
      referralCode: '',
      referralLink: '',
      templates: {},
      selectedTemplate: 'interprovincial',
      metrics: {},
      milestones: [],
      transactionalInvites: []
    };

    async function loadPLGReferralData() {
      try {
        const response = await fetch('/api/referrals.php?action=status', {
          credentials: 'same-origin',
          headers: { 'X-WP-Nonce': (typeof CAPTACION_API !== 'undefined' ? CAPTACION_API.nonce : '') }
        });
        if (!response.ok) return;
        const data = await response.json();
        if (!data.ok) return;

        currentPLGState.referralCode = data.referral_code || '';
        currentPLGState.referralLink = data.referral_link || '';
        currentPLGState.templates = data.templates || {};
        currentPLGState.metrics = data.metrics || {};
        currentPLGState.milestones = data.milestones || [];
        currentPLGState.transactionalInvites = data.transactional_invites || [];

        // Pintar métricas
        const metricDiscount = document.getElementById('plg-metric-discount');
        if (metricDiscount) metricDiscount.textContent = `${data.metrics.recurring_discount_percentage || 0}% DTO`;

        const metricDiscountSub = document.getElementById('plg-metric-discount-sub');
        if (metricDiscountSub) metricDiscountSub.textContent = data.metrics.monthly_savings_text || '';

        const metricCredits = document.getElementById('plg-metric-credits');
        if (metricCredits) metricCredits.textContent = `+${Number(data.metrics.total_credits_earned || 0).toFixed(2).replace('.', ',')} cr`;

        const metricXml = document.getElementById('plg-metric-xml');
        if (metricXml) metricXml.textContent = `${data.metrics.xml_carpetas_activadas || 0} Activas`;

        const metricInvites = document.getElementById('plg-metric-invites');
        if (metricInvites) metricInvites.textContent = `${(data.transactional_invites || []).length} Enviadas`;

        // Badge
        const badgePill = document.getElementById('plg-badge-pill');
        if (badgePill) {
          badgePill.textContent = data.metrics.is_connector_recommended ? '⭐ Agente Conector Recomendado' : 'Agente Colaborador';
          badgePill.className = data.metrics.is_connector_recommended 
            ? 'inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[11px] font-black border border-amber-500/30 shadow-xs' 
            : 'inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[11px] font-bold';
        }

        // Renderizar plantilla activa
        selectPLGTemplate(currentPLGState.selectedTemplate || 'interprovincial');

        // Renderizar tabla de hitos
        renderPLGMilestonesTable(data.milestones || [], data.transactional_invites || []);

        // Actualizar badge en sidebar si existe
        const sidebarReferralBadge = document.getElementById('private-sidebar-referrals-badge');
        if (sidebarReferralBadge) {
          sidebarReferralBadge.textContent = `${data.metrics.recurring_discount_percentage || 0}% DTO`;
        }

      } catch (err) {
        console.warn('Error loading PLG referral data:', err);
      }
    }
    window.loadPLGReferralData = loadPLGReferralData;

    function selectPLGTemplate(templateKey) {
      currentPLGState.selectedTemplate = templateKey;
      const t = (currentPLGState.templates && currentPLGState.templates[templateKey]) || {};

      ['interprovincial', 'trojan_deal', 'network_trust'].forEach((key) => {
        const tab = document.getElementById(`plg-tab-${key}`);
        if (tab) {
          const isActive = key === templateKey;
          tab.className = isActive 
            ? 'px-4 py-2 rounded-xl bg-blue text-white text-xs font-bold transition-all shadow-sm' 
            : 'px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all hover:bg-slate-200';
        }
      });

      const titleEl = document.getElementById('plg-template-title');
      if (titleEl) titleEl.textContent = t.title || 'Plantilla de Difusión';

      const descEl = document.getElementById('plg-template-desc');
      if (descEl) descEl.textContent = t.description || '';

      const textEl = document.getElementById('plg-template-text');
      if (textEl) textEl.textContent = t.whatsapp || t.email_body || '';
    }
    window.selectPLGTemplate = selectPLGTemplate;

    function shareCurrentPLGTemplate(method) {
      const t = (currentPLGState.templates && currentPLGState.templates[currentPLGState.selectedTemplate]) || {};
      const text = t.whatsapp || t.email_body || `Únete a Compra Captación y recibe 3 créditos: ${currentPLGState.referralLink}`;

      if (method === 'whatsapp') {
        const waUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;
        window.open(waUrl, '_blank', 'noopener,noreferrer');
      } else {
        if (navigator.clipboard) {
          navigator.clipboard.writeText(text);
          showToast('¡Texto copiado al portapapeles! Ya puedes pegarlo donde quieras.', 'success');
        } else {
          showToast('Selecciona y copia el texto manualmente.', 'info');
        }
      }
    }
    window.shareCurrentPLGTemplate = shareCurrentPLGTemplate;

    function copyPersonalReferralLink() {
      const link = currentPLGState.referralLink || `https://compracaptacion.com/?ref=${currentPLGState.referralCode}`;
      if (navigator.clipboard) {
        navigator.clipboard.writeText(link);
        showToast('¡Enlace de referido copiado al portapapeles!', 'success');
      } else {
        showToast(`Tu enlace: ${link}`, 'info');
      }
    }
    window.copyPersonalReferralLink = copyPersonalReferralLink;

    async function handleSendTransactionalInvite(e) {
      e.preventDefault();
      const btn = document.getElementById('btn-submit-trojan-invite');
      if (btn) btn.disabled = true;

      const payload = {
        target_email: document.getElementById('trojan-target-email')?.value || '',
        target_name: document.getElementById('trojan-target-name')?.value || '',
        property_title: document.getElementById('trojan-property-title')?.value || '',
        province: document.getElementById('trojan-province')?.value || '',
        buyer_budget: document.getElementById('trojan-buyer-budget')?.value || 0,
        commission_split: document.getElementById('trojan-commission-split')?.value || '50/50'
      };

      try {
        const response = await fetch('/api/referrals.php?action=send_transactional_invite', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (data.ok) {
          showToast('¡Invitación formal enviada por correo electrónico!', 'success');
          const successBox = document.getElementById('trojan-invite-success-box');
          if (successBox) successBox.classList.remove('hidden');

          const waBtn = document.getElementById('btn-open-whatsapp-invite');
          if (waBtn && data.whatsapp_text) {
            waBtn.onclick = () => {
              window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(data.whatsapp_text)}`, '_blank');
            };
          }

          const copyBtn = document.getElementById('btn-copy-direct-invite-link');
          if (copyBtn && data.direct_link) {
            copyBtn.onclick = () => {
              if (navigator.clipboard) {
                navigator.clipboard.writeText(data.direct_link);
                showToast('Enlace directo de la operación copiado.', 'success');
              }
            };
          }

          loadPLGReferralData();
        } else {
          showToast(data.error || 'Error al enviar invitación.', 'error');
        }
      } catch (err) {
        showToast('Error de conexión al enviar invitación.', 'error');
      } finally {
        if (btn) btn.disabled = false;
      }
    }
    window.handleSendTransactionalInvite = handleSendTransactionalInvite;

    async function handleVerifyProfessionalLicense(e) {
      e.preventDefault();
      const taxId = document.getElementById('plg-tax-id')?.value || '';
      const regType = document.getElementById('plg-registry-type')?.value || 'AICAT';
      const licenseNum = document.getElementById('plg-license-number')?.value || '';

      try {
        const response = await fetch('/api/referrals.php?action=verify_professional_license', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tax_id: taxId, license_registry_type: regType, license_number: licenseNum })
        });
        const data = await response.json();
        if (data.ok) {
          showToast('¡Registro profesional verificado con éxito! Cuenta homologada.', 'success');
          const pill = document.getElementById('plg-verification-status-pill');
          if (pill) {
            pill.textContent = `Verificado (${regType} ${licenseNum})`;
            pill.className = 'px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs font-black';
          }
        } else {
          showToast(data.error || 'Error al validar registro.', 'error');
        }
      } catch (err) {
        showToast('Error al verificar registro.', 'error');
      }
    }
    window.handleVerifyProfessionalLicense = handleVerifyProfessionalLicense;

    function renderPLGMilestonesTable(milestones, invites) {
      const container = document.getElementById('plg-milestones-table-container');
      if (!container) return;

      if (!milestones.length && !invites.length) {
        container.innerHTML = `
          <div class="text-center py-8 text-slate-400 dark:text-slate-500">
            <span class="text-3xl block mb-2">👥</span>
            <p class="text-xs">Aún no has invitado a ningún colega. Comparte tu enlace o envía una invitación transaccional para empezar a ganar descuentos.</p>
          </div>
        `;
        return;
      }

      let html = `
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 text-[10px] uppercase font-black tracking-wider">
              <th class="py-2.5 px-3">Profesional / Agencia</th>
              <th class="py-2.5 px-3">Tipo / Inmueble</th>
              <th class="py-2.5 px-3">Estado</th>
              <th class="py-2.5 px-3">Recompensa</th>
              <th class="py-2.5 px-3 text-right">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
      `;

      milestones.forEach((m) => {
        const isRewarded = m.status === 'rewarded';
        html += `
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="py-3 px-3">
              <strong class="block text-navy dark:text-white font-bold">${escapeHTML(m.referred_name || m.referred_email || 'Colega invitado')}</strong>
              <span class="block text-[10px] text-slate-400">${escapeHTML(m.referred_agency || 'Agencia')}</span>
            </td>
            <td class="py-3 px-3">
              <span class="inline-flex px-2 py-0.5 rounded-full bg-blue/10 text-blue text-[10px] font-bold">Cartera XML (Hito A)</span>
              <span class="block text-[10px] text-slate-400 mt-0.5">${m.properties_count || 0} exclusivas</span>
            </td>
            <td class="py-3 px-3">
              <span class="inline-flex px-2.5 py-0.5 rounded-full ${isRewarded ? 'bg-emerald-500/10 text-emerald-600 font-black' : 'bg-amber/10 text-amber font-bold'} text-[10px]">
                ${isRewarded ? '✓ Validado' : 'Pendiente de Cartera'}
              </span>
            </td>
            <td class="py-3 px-3">
              <strong class="text-navy dark:text-white font-bold">${isRewarded ? '+3 cr · +10% DTO' : '+3 cr al subir 3 exclusivas'}</strong>
            </td>
            <td class="py-3 px-3 text-right">
              ${!isRewarded ? `<button type="button" onclick="verifyMilestoneA(${m.referred_user_id})" class="px-2.5 py-1 rounded-lg bg-blue text-white text-[10px] font-bold">Comprobar</button>` : `<span class="text-slate-400 text-[10px]">Recompensado</span>`}
            </td>
          </tr>
        `;
      });

      invites.forEach((inv) => {
        html += `
          <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="py-3 px-3">
              <strong class="block text-navy dark:text-white font-bold">${escapeHTML(inv.target_name || inv.target_email)}</strong>
              <span class="block text-[10px] text-slate-400">${escapeHTML(inv.target_agency || 'Invitación Transaccional')}</span>
            </td>
            <td class="py-3 px-3">
              <span class="inline-flex px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-600 text-[10px] font-bold">Caballo de Troya</span>
              <span class="block text-[10px] text-slate-400 mt-0.5">${escapeHTML(inv.property_title)} (${escapeHTML(inv.province)})</span>
            </td>
            <td class="py-3 px-3">
              <span class="inline-flex px-2.5 py-0.5 rounded-full bg-blue/10 text-blue font-bold text-[10px]">
                ${inv.status === 'registered' ? 'Registrado' : 'Enviada'}
              </span>
            </td>
            <td class="py-3 px-3">
              <strong class="text-navy dark:text-white font-bold">Operación 50/50</strong>
            </td>
            <td class="py-3 px-3 text-right">
              <span class="text-slate-400 text-[10px]">Activa</span>
            </td>
          </tr>
        `;
      });

      html += `</tbody></table>`;
      container.innerHTML = html;
    }

    async function verifyMilestoneA(referredUserId) {
      try {
        const formData = new FormData();
        formData.append('referred_user_id', String(referredUserId));
        const res = await fetch('/api/referrals.php?action=verify_milestone_a', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (data.ok) {
          showToast(data.message || '¡Hito validado!', 'success');
          loadPLGReferralData();
        } else {
          showToast(data.error || 'Aún no cumple el requisito de 3 exclusivas.', 'info');
        }
      } catch (e) {
        showToast('Error al verificar hito.', 'error');
      }
    }
    window.verifyMilestoneA = verifyMilestoneA;

    function openSendTransactionalInviteModal() {
      switchPrivateDashboardPanel('referrals', true);
      document.getElementById('trojan-target-email')?.focus();
    }
    window.openSendTransactionalInviteModal = openSendTransactionalInviteModal;

    function getNewTaskModal() {
      let modal = document.getElementById('new-private-task-modal');
      if (modal) return modal;
      modal = document.createElement('div');
      modal.id = 'new-private-task-modal';
      modal.className = 'fixed inset-0 z-[140] hidden flex items-center justify-center p-4 bg-navy-dark/70 backdrop-blur-sm';
      modal.innerHTML = `<div class="relative w-full max-w-xl max-h-[92vh] overflow-y-auto rounded-3xl bg-white border border-slate-200 shadow-2xl p-6 sm:p-8"><button type="button" onclick="closeNewTaskModal()" class="absolute top-3 right-4 text-slate-400 text-xl font-black">x</button><span class="inline-flex px-3 py-1 rounded-full bg-blue-light text-blue text-[10px] font-black uppercase">Calendario Premium</span><h3 class="text-xl font-black text-navy mt-4">Añadir nueva tarea</h3><form onsubmit="submitNewPrivateTask(event)" class="mt-5 space-y-4"><label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Título de la tarea *</span><input id="new-task-title" required minlength="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></label><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><label><span class="block text-xs font-bold text-slate-500 mb-1">Fecha *</span><input id="new-task-date" type="date" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></label><label><span class="block text-xs font-bold text-slate-500 mb-1">Hora opcional</span><input id="new-task-time" type="time" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm" /></label></div><label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Descripción opcional</span><textarea id="new-task-description" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm"></textarea></label><label class="block"><span class="block text-xs font-bold text-slate-500 mb-1">Relacionar con</span><select id="new-task-related" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm bg-white"><option value="">Sin relación</option></select></label><div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><label><span class="block text-xs font-bold text-slate-500 mb-1">Recordatorio</span><select id="new-task-reminder" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm bg-white"><option value="none">Sin recordatorio</option><option value="15m">15 minutos antes</option><option value="1h">1 hora antes</option><option value="1d">1 día antes</option></select></label><label><span class="block text-xs font-bold text-slate-500 mb-1">Canal</span><select id="new-task-channel" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm bg-white"><option value="panel">Panel</option><option value="email">Email</option><option value="whatsapp_todo">WhatsApp futuro / TODO</option></select></label></div><p id="new-task-error" class="hidden rounded-xl bg-red-50 px-3 py-2 text-xs text-red-700"></p><button class="w-full py-3 rounded-xl bg-blue text-white text-xs font-black">Guardar tarea</button></form></div>`;
      document.body.appendChild(modal);
      return modal;
    }

    function openNewTaskModal() {
      if (getCurrentPlanType() !== 'premium') { showToast('El calendario avanzado está disponible en Premium.', 'info'); return; }
      const modal = getNewTaskModal();
      const related = document.getElementById('new-task-related');
      const options = [...properties.map(item=>({id:item.id,label:`Captación: ${item.title}`})),...needs.map(item=>({id:item.id,label:`Demanda: ${item.title}`})),...(getPrivateDashboardState().operations||[]).map(item=>({id:item.id,label:`Operación: ${item.title||item.id}`}))];
      related.innerHTML = '<option value="">Sin relación</option>' + options.map(item=>`<option value="${escapeHTML(item.id)}">${escapeHTML(item.label)}</option>`).join('');
      document.getElementById('new-task-date').value = new Date().toISOString().slice(0,10);
      modal.classList.remove('hidden');
    }

    function closeNewTaskModal() { getNewTaskModal().classList.add('hidden'); }

    async function submitNewPrivateTask(event) {
      event.preventDefault();
      const payload = {title:cleanText(document.getElementById('new-task-title').value),date:document.getElementById('new-task-date').value,time:document.getElementById('new-task-time').value,description:cleanText(document.getElementById('new-task-description').value),related_id:document.getElementById('new-task-related').value,reminder:document.getElementById('new-task-reminder').value,channel:document.getElementById('new-task-channel').value};
      const errorBox = document.getElementById('new-task-error');
      try {
        const response = await fetch(CAPTACION_MAILCHIMP.tasksEndpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':CAPTACION_MAILCHIMP.nonce},body:JSON.stringify(payload)});
        const data = await response.json();
        if(!response.ok||!data?.ok) throw new Error(data?.message||'No se pudo guardar la tarea.');
        addPrivateTask({title:data.task.title,detail:data.task.description||'Tarea de agenda',due:new Date(data.task.dueAt).toLocaleString('es-ES'),dueAt:data.task.dueAt,target:'tasks',priority:'medium',dedupeKey:data.task.id});
        event.target.reset(); closeNewTaskModal(); renderDashboard(); showToast(data.message,'success');
      } catch(error) { errorBox.textContent=error.message; errorBox.classList.remove('hidden'); }
    }

    function linkExternalCalendar() {
      const modal = document.getElementById('sync-calendar-modal');
      if (modal) {
        modal.classList.remove('hidden');
      } else {
        exportPrivateAgendaCalendar();
      }
    }
    function closeSyncCalendarModal() {
      document.getElementById('sync-calendar-modal')?.classList.add('hidden');
    }
    function copyCalendarFeedUrl() {
      const input = document.getElementById('sync-calendar-feed-url');
      if (input) {
        input.select();
        try {
          navigator.clipboard?.writeText(input.value);
        } catch(e){}
        showToast('Enlace de calendario iCal/Webcal copiado al portapapeles.', 'success');
      }
    }
    function openGoogleCalendarSync() {
      const state = getPrivateDashboardState();
      const firstTask = (state.tasks || []).find(t => t.status !== 'done') || { title: 'Seguimiento Compra Captación', detail: 'Revisión de expediente y agenda' };
      const title = encodeURIComponent(`[Compra Captación] ${firstTask.title}`);
      const details = encodeURIComponent(`${firstTask.detail}\n\nGestión desde: https://compracaptacion.com/area-privada`);
      const gcalUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&details=${details}&location=España`;
      window.open(gcalUrl, '_blank', 'noopener,noreferrer');
      showToast('Abriendo Google Calendar para añadir eventos.', 'info');
    }

    function openContactSupportModal(category = '') {
      const modal = document.getElementById('contact-support-modal');
      if (!modal) return;
      const session = typeof getDemoSession === 'function' ? getDemoSession() : {};
      const fiscal = typeof getPrivateDashboardState === 'function' ? (getPrivateDashboardState().fiscalProfile || {}) : {};
      const wpUser = window.CAPTACION_MAILCHIMP?.currentUser || {};

      const nameEl = document.getElementById('contact-support-name');
      const emailEl = document.getElementById('contact-support-email');
      const phoneEl = document.getElementById('contact-support-phone');
      const agencyEl = document.getElementById('contact-support-agency');
      const catEl = document.getElementById('contact-support-category');

      if (nameEl) nameEl.value = fiscal.firstName ? `${fiscal.firstName} ${fiscal.lastName || ''}`.trim() : (session.name || wpUser.displayName || '');
      if (emailEl) emailEl.value = fiscal.contactEmail || session.email || wpUser.email || '';
      if (phoneEl) phoneEl.value = fiscal.phone || session.whatsapp || session.phone || '';
      if (agencyEl) agencyEl.value = fiscal.tradeName || fiscal.legalName || session.agency || '';
      if (catEl && category) catEl.value = category;

      modal.classList.remove('hidden');
    }
    function openVeraSupportDraft(draft = {}) {
      openContactSupportModal(draft.category || 'Consulta sobre Vera / Compra Captación');
      const messageEl = document.getElementById('contact-support-message');
      if (messageEl) messageEl.value = String(draft.message || '').slice(0, 3000);
      showToast('He preparado la consulta para que la revises antes de enviarla.', 'info');
    }
    function closeContactSupportModal() {
      document.getElementById('contact-support-modal')?.classList.add('hidden');
    }
    function submitContactSupport(event) {
      event.preventDefault();
      const form = event.target;
      const name = form.querySelector('#contact-support-name')?.value || '';
      const email = form.querySelector('#contact-support-email')?.value || '';
      const phone = form.querySelector('#contact-support-phone')?.value || '';
      const category = form.querySelector('#contact-support-category')?.value || 'Consulta general';
      const message = form.querySelector('#contact-support-message')?.value || '';

      if (!name || !email || !message) {
        showToast('Por favor, completa los campos requeridos.', 'error');
        return;
      }

      if (typeof addPrivateActivity === 'function') {
        addPrivateActivity('📩', `Mensaje de soporte enviado (${category})`, 'El equipo de atención al profesional te responderá a la mayor brevedad.');
      }
      closeContactSupportModal();
      form.reset();
      showToast('Tu solicitud de soporte ha sido enviada con éxito. Te responderemos en breve.', 'success');
    }

    async function loadWordPressTasks() {
      if (!CAPTACION_MAILCHIMP?.loggedIn || getCurrentPlanType() !== 'premium' || !CAPTACION_MAILCHIMP?.tasksEndpoint) return;
      try {
        const response=await fetch(CAPTACION_MAILCHIMP.tasksEndpoint,{credentials:'same-origin',headers:{'X-WP-Nonce':CAPTACION_MAILCHIMP.nonce}}); const data=await response.json(); if(!response.ok||!data?.ok)return;
        const state=getPrivateDashboardState(); const existing=new Set((state.tasks||[]).map(item=>item.dedupeKey||item.id));
        (data.tasks||[]).forEach(row=>{const task=row.payload||{};if(existing.has(task.id))return;state.tasks.push({id:task.id,title:task.title,detail:task.description||'',dueAt:Number(task.dueAt)||Date.now(),due:new Date(Number(task.dueAt)||Date.now()).toLocaleString('es-ES'),priority:'medium',status:task.status||'pending',target:'tasks',dedupeKey:task.id});}); persistPrivateDashboardState(state); renderDashboard();
      } catch(error) {}
    }

    function getProfessionalDisplayName() {
      const session = getDemoSession?.() || {};
      const wpUser = CAPTACION_MAILCHIMP?.currentUser || {};
      const email = String(session.email || wpUser.email || '').trim();
      const displayName = String(session.name || wpUser.displayName || wpUser.name || '').trim();
      const fullName = [wpUser.firstName, wpUser.lastName].map(value => String(value || '').trim()).filter(Boolean).join(' ');
      const username = String(wpUser.username || '').trim();
      return (displayName && displayName.toLowerCase() !== email.toLowerCase()) ? displayName : (fullName || username || email || 'Agente profesional');
    }

    function syncPrivateProfile(){
      const session = (typeof getDemoSession === 'function' ? getDemoSession() : {}) || {};
      const name = typeof getProfessionalDisplayName === 'function' ? getProfessionalDisplayName() : 'Agente profesional';
      const agency = session.agency || 'Compra Captación';
      ['private-dashboard-agent-name','private-profile-name'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = name;
      });
      ['private-dashboard-agent-agency','private-profile-agency'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = agency;
      });
      const greetingEl = document.getElementById('exec-greeting-title');
      if (greetingEl) {
        greetingEl.textContent = `Hola, ${name}`;
      }
      if (typeof renderPrivateFiscalProfile === 'function') renderPrivateFiscalProfile();
    }

    function setExecutivePeriod(period) {
      executivePeriod = ['7d', '30d', '90d', 'ytd'].includes(period) ? period : '30d';
      renderExecutiveDashboard();
    }
    window.setExecutivePeriod = setExecutivePeriod;

    function renderExecutiveDashboard() {
      const area = document.getElementById('page-area-privada');
      if (area && privateDashboardPanel === 'overview') area.classList.add('executive-mode');
      const state = getPrivateDashboardState();
      const operations = state.operations || [];
      const normalizeOperationStatus = value => {
        const status = normalizeMatchText(value || '').replace(/\s+/g, '_');
        if (['requested', 'solicitada', 'solicitado', 'pendiente'].includes(status)) return 'requested';
        if (['agreed', 'acordada', 'acordado', 'aceptada', 'aceptado'].includes(status)) return 'agreed';
        if (['in_progress', 'en_curso', 'en_tramite', 'en_trámite'].includes(status)) return 'in_progress';
        if (['closed', 'completada', 'completado', 'cerrada', 'cerrado'].includes(status)) return 'closed';
        if (['disputed', 'disputada', 'disputado', 'en_disputa'].includes(status)) return 'disputed';
        if (['cancelled', 'cancelada', 'cancelado'].includes(status)) return 'cancelled';
        return status;
      };
      const activeOperationStatuses = new Set(['requested', 'agreed', 'in_progress']);
      const activeOperations = operations.filter(item => activeOperationStatuses.has(normalizeOperationStatus(item.status)));
      const completedOperations = operations.filter(item => normalizeOperationStatus(item.status) === 'closed').length + closedOperations.length;
      const periodDays = { '7d': 7, '30d': 30, '90d': 90 };
      const now = Date.now();
      const periodStart = executivePeriod === 'ytd' ? new Date(new Date().getFullYear(), 0, 1).getTime() : now - ((periodDays[executivePeriod] || 30) * 86400000);
      const hasDate = item => Boolean(item?.createdAt || item?.created_at || item?.date || item?.timestamp || item?.updatedAt || item?.updated_at);
      const itemTime = item => {
        const raw = item?.createdAt || item?.created_at || item?.date || item?.timestamp || item?.updatedAt || item?.updated_at;
        const parsed = typeof raw === 'number' ? raw : Date.parse(raw || '');
        return Number.isFinite(parsed) ? (parsed < 100000000000 ? parsed * 1000 : parsed) : NaN;
      };
      const inPeriod = item => !hasDate(item) || itemTime(item) >= periodStart;
      const salesMatches = getSalesMatchRecords(true).filter(inPeriod);
      const myProperties = privateProperties().filter(inPeriod);
      const myNeeds = privateNeeds().filter(inPeriod);
      
      function getRegionalCommissionRate(provinceOrRegion) {
        const norm = normalizeMatchText(provinceOrRegion || '');
        if (!norm) return 4.0;
        if (norm.includes('madrid')) return 4.0;
        if (norm.includes('barcelona') || norm.includes('girona') || norm.includes('tarragona') || norm.includes('lleida') || norm.includes('catalu')) return 5.0;
        if (norm.includes('balear') || norm.includes('mallorca') || norm.includes('ibiza') || norm.includes('menorca')) return 5.5;
        if (norm.includes('canaria') || norm.includes('palmas') || norm.includes('tenerife')) return 5.0;
        if (norm.includes('andaluc') || norm.includes('malaga') || norm.includes('marbella') || norm.includes('sevilla') || norm.includes('cadiz') || norm.includes('granada') || norm.includes('almeria') || norm.includes('huelva') || norm.includes('cordoba') || norm.includes('jaen')) return 4.5;
        if (norm.includes('valenc') || norm.includes('alicante') || norm.includes('castellon')) return 4.0;
        if (norm.includes('toledo') || norm.includes('ciudad real') || norm.includes('albacete') || norm.includes('cuenca') || norm.includes('guadalajara') || norm.includes('mancha')) return 4.0;
        if (norm.includes('valladolid') || norm.includes('burgos') || norm.includes('salamanca') || norm.includes('leon') || norm.includes('zamora') || norm.includes('palencia') || norm.includes('segovia') || norm.includes('soria') || norm.includes('avila') || norm.includes('castilla y leon')) return 4.0;
        if (norm.includes('galicia') || norm.includes('coruna') || norm.includes('pontevedra') || norm.includes('ourense') || norm.includes('lugo')) return 3.0;
        if (norm.includes('asturia') || norm.includes('oviedo') || norm.includes('gijon')) return 3.0;
        if (norm.includes('cantabria') || norm.includes('santander')) return 3.0;
        if (norm.includes('vasco') || norm.includes('bizkaia') || norm.includes('bilbao') || norm.includes('gipuzkoa') || norm.includes('san sebastian') || norm.includes('alava') || norm.includes('vitoria') || norm.includes('euskadi')) return 3.5;
        if (norm.includes('navarra') || norm.includes('pamplona')) return 3.0;
        if (norm.includes('rioja') || norm.includes('logrono')) return 3.0;
        if (norm.includes('aragon') || norm.includes('zaragoza') || norm.includes('huesca') || norm.includes('teruel')) return 3.0;
        if (norm.includes('murcia') || norm.includes('cartagena')) return 3.5;
        if (norm.includes('extremadura') || norm.includes('badajoz') || norm.includes('caceres')) return 3.5;
        return 4.0;
      }

      const captureValue = myProperties.reduce((sum,item)=>sum+(Number(item.price)||0),0);
      const demandValue = myNeeds.reduce((sum,item)=>sum+(Number(item.budget)||0),0);
      const matchValue = salesMatches.reduce((sum,item)=>sum+(Number(item.estimatedValue)||0),0);
      const requestsValue = (state.requestsReceived||[]).reduce((sum,item)=>{
        const p = privatePropertyById(item.propertyId);
        return sum + (p ? Number(p.price || 0) : 0);
      }, 0);
      const activeValue = activeOperations.reduce((sum,item)=>sum+linkedPropertyValue(item),0);
      const closedValue = operations.filter(item => normalizeOperationStatus(item.status) === 'closed').reduce((sum,item)=>sum+linkedPropertyValue(item),0);
      
      const getRecordCommissionRate = item => {
        const explicit = Number(item.commission_percentage ?? item.commissionPercentage ?? item.commission_pct);
        if (Number.isFinite(explicit) && explicit > 0 && explicit <= 100) return { rate: explicit, estimated: false };
        return { rate: getRegionalCommissionRate(item.province || item.location || item.municipality || ''), estimated: true };
      };
      const getAgentShare = item => {
        const explicit = Number(item.share_percentage ?? item.sharePercentage ?? item.commission_split_percentage);
        return Number.isFinite(explicit) && explicit >= 0 && explicit <= 100 ? explicit / 100 : 0.5;
      };
      const getMatchProperty = match => match?.property || privatePropertyById(match?.propertyId || match?.record_id || match?.recordId);
      const matchedProperties = salesMatches.map(getMatchProperty).filter(Boolean);
      const pipelinePotential = matchedProperties.reduce((sum, item) => {
        const grossValue = Number(item.price ?? item.indicative_price) || 0;
        const commission = getRecordCommissionRate(item);
        return sum + Math.round(grossValue * (commission.rate / 100) * getAgentShare(item));
      }, 0);
      const activePipeline = activeOperations.reduce((sum, item) => {
        const recordedCommission = Number(item.commission_total ?? item.commissionTotal);
        const recordedShare = Number(item.captador_commission ?? item.captadorCommission ?? item.agent_commission);
        if (Number.isFinite(recordedShare) && recordedShare > 0) return sum + recordedShare;
        if (Number.isFinite(recordedCommission) && recordedCommission > 0) return sum + Math.round(recordedCommission * getAgentShare(item));
        return sum;
      }, 0);
      const totalPipeline = pipelinePotential + activePipeline;
      const favoriteCount = getFavoriteIds('capture').length + getFavoriteIds('demand').length + getFavoriteIds('match').length;
      
      const values = {
        'exec-kpi-offers': myProperties.length, 
        'exec-kpi-demands': myNeeds.length, 
        'exec-kpi-matches': salesMatches.length, 
        'exec-kpi-operations': activeOperations.length,
        'exec-kpi-offers-value': captureValue > 0 ? `${formatCurrency(captureValue)} en cartera` : '0 € en cartera', 
        'exec-kpi-demands-value': demandValue > 0 ? `${formatCurrency(demandValue)} presupuestados` : '0 € presupuestados', 
        'exec-kpi-matches-value': matchValue > 0 ? `${formatCurrency(matchValue)} en cruces` : '0 € en cruces', 
        'exec-kpi-operations-value': activeValue > 0 ? `${formatCurrency(activeValue)} en trámite` : '0 € en trámite',
        'exec-pipeline-value': formatCurrency(totalPipeline), 
        'exec-total-opportunities': myProperties.length + myNeeds.length + (state.requestsReceived||[]).length + salesMatches.length,
        'exec-legend-offers': `(${myProperties.length})`, 
        'exec-legend-demands': `(${myNeeds.length})`, 
        'exec-legend-requests': `(${(state.requestsReceived||[]).length})`, 
        'exec-legend-matches': `(${salesMatches.length})`,
        'exec-requests-count': (state.requestsReceived||[]).length, 
        'exec-unread-count': (state.notifications||[]).filter(item=>!item.read).length, 
        'exec-favorites-count': favoriteCount, 
        'exec-clients-count': (state.clients||[]).length, 
        'exec-leads-count': (state.leads||[]).filter(item=>item.status!=='Convertido').length, 
        'exec-tasks-count': (state.tasks||[]).filter(item=>item.status!=='done').length
      };
      const periodLabel = document.getElementById('exec-period-label');
      if (periodLabel) periodLabel.value = executivePeriod;
      Object.entries(values).forEach(([id,value])=>{
        const element=document.getElementById(id);
        if(element) element.textContent=String(value);
      });

      // 1. DISTRIBUCIÓN GENERAL: Llenado de Filas (Cantidades, Porciento, Valor EUR)
      const distributionCounts = [myProperties.length, myNeeds.length, (state.requestsReceived||[]).length, salesMatches.length];
      const distributionValues = [captureValue, demandValue, requestsValue, matchValue];
      const distributionTotal = distributionCounts.reduce((sum, value) => sum + value, 0);
      const distributionPercentages = distributionCounts.map(value => distributionTotal ? Math.round((value / distributionTotal) * 1000) / 10 : 0);
      const distributionNames = ['Captaciones', 'Demandas', 'Solicitudes', 'Coincidencias'];

      const distRowsData = [
        { idKey: 'offers', qty: myProperties.length, pct: distributionPercentages[0], val: captureValue },
        { idKey: 'demands', qty: myNeeds.length, pct: distributionPercentages[1], val: demandValue },
        { idKey: 'requests', qty: (state.requestsReceived||[]).length, pct: distributionPercentages[2], val: requestsValue },
        { idKey: 'matches', qty: salesMatches.length, pct: distributionPercentages[3], val: matchValue }
      ];

      distRowsData.forEach(row => {
        const qtyEl = document.getElementById(`exec-dist-qty-${row.idKey}`);
        if (qtyEl) qtyEl.textContent = String(row.qty);
        
        const pctEl = document.getElementById(`exec-dist-pct-${row.idKey}`);
        if (pctEl) pctEl.textContent = `${String(row.pct).replace('.', ',')}%`;
        
        const valEl = document.getElementById(`exec-dist-val-${row.idKey}`);
        if (valEl) valEl.textContent = formatCurrency(row.val);
      });

      let donutOffset = 0;
      area?.querySelectorAll('.exec-donut-segment').forEach((segment, index) => {
        const percentage = distributionPercentages[index] || 0;
        segment.setAttribute('stroke-dasharray', `${percentage} ${Math.max(0, 100 - percentage)}`);
        segment.setAttribute('stroke-dashoffset', String(-donutOffset));
        segment.setAttribute('aria-label', `${distributionNames[index]}, ${String(percentage).replace('.', ',')} por ciento`);
        donutOffset += percentage;
      });

      // 2. EMBUDO COMERCIAL: Llenado de Filas (Fase, Cantidades, Porciento, Valor EUR)
      const funnelBase = myProperties.length;
      const formatFunnelPct = value => {
        if (!funnelBase) return '0%';
        const percentage = Math.round((value / funnelBase) * 1000) / 10;
        return `${String(percentage).replace('.', ',')}%`;
      };

      const funnelRowsData = [
        { idKey: 'offers', qty: myProperties.length, pct: myProperties.length ? '100%' : '0%', val: captureValue },
        { idKey: 'requests', qty: (state.requestsReceived||[]).length, pct: formatFunnelPct((state.requestsReceived||[]).length), val: requestsValue },
        { idKey: 'matches', qty: salesMatches.length, pct: formatFunnelPct(salesMatches.length), val: matchValue },
        { idKey: 'operations', qty: activeOperations.length, pct: formatFunnelPct(activeOperations.length), val: activeValue },
        { idKey: 'closed', qty: completedOperations, pct: formatFunnelPct(completedOperations), val: closedValue }
      ];

      funnelRowsData.forEach(row => {
        const qtyEl = document.getElementById(`exec-funnel-${row.idKey}`);
        if (qtyEl) qtyEl.textContent = String(row.qty);
        
        const pctEl = document.getElementById(`exec-funnel-${row.idKey}-pct`);
        if (pctEl) pctEl.textContent = row.pct;
        
        const valEl = document.getElementById(`exec-funnel-${row.idKey}-val`);
        if (valEl) valEl.textContent = formatCurrency(row.val);
      });

      // Dinamizar tendencias sin valores incoherentes (evitar ↑ 33% cuando es 0)
      const trendOffers = document.querySelector('.exec-kpi-blue .exec-trend');
      if (trendOffers) trendOffers.innerHTML = '<span class="text-slate-400">Sin datos comparables</span>';
      
      const trendDemands = document.querySelector('.exec-kpi-green .exec-trend');
      if (trendDemands) trendDemands.innerHTML = '<span class="text-slate-400">Sin datos comparables</span>';
      
      const trendOperations = document.querySelector('.exec-kpi-violet .exec-trend');
      if (trendOperations) trendOperations.innerHTML = '<span class="text-slate-400">Sin datos comparables</span>';

      // Dinamizar etiquetas temporales del gráfico hasta agosto 2026
      const monthsContainer = document.querySelector('.exec-months');
      if (monthsContainer) {
        monthsContainer.innerHTML = '<span>Mar</span><span>Abr</span><span>May</span><span>Jun</span><span>Jul</span><span>Ago 2026</span>';
      }

      // Guía de Onboarding para usuarios nuevos o con 0 actividad
      const hasZeroActivity = myProperties.length === 0 && myNeeds.length === 0 && (state.requestsReceived||[]).length === 0 && activeOperations.length === 0;
      const onboardingBox = document.getElementById('private-dashboard-onboarding-guide');
      if (onboardingBox) {
        onboardingBox.classList.toggle('hidden', !hasZeroActivity);
      }

      const requestsBox = document.getElementById('exec-latest-requests');
      const matchesBox = document.getElementById('exec-latest-matches');
      const tasksBox = document.getElementById('exec-pending-tasks');
      const requestRows = (state.requestsReceived||[]).slice(0,3).map(item=>{const property=privatePropertyById(item.propertyId);const name=item.agency||'Grupo inversor';return{initials:name.split(/\s+/).slice(0,2).map(part=>part[0]||'').join('').toUpperCase(),name,detail:item.note||property?.title||'Solicitud de información',time:formatRelativeTime(item.createdAt),status:item.status||'Nueva'};});
      if(requestsBox)requestsBox.innerHTML=requestRows.map((item,index)=>`<button type="button" onclick="openExecutiveDestination('requests')" class="exec-row exec-clickable" aria-label="Abrir solicitud de ${escapeHTML(item.name)}, ${escapeHTML(item.time)}"><span class="exec-avatar ${index===1?'green':''}">${escapeHTML(item.initials)}</span><span class="exec-row-copy"><strong>${escapeHTML(item.name)}</strong><span>${escapeHTML(item.detail)}</span></span><span class="exec-row-meta">${escapeHTML(item.time)}<br><i class="exec-pill">${escapeHTML(item.status)}</i></span></button>`).join('')||`<button type="button" onclick="openExecutiveDestination('requests')" class="exec-row exec-clickable" aria-label="Abrir solicitudes"><span class="exec-row-copy"><strong>No hay solicitudes recientes</strong><span>Accede a la bandeja para revisar su estado.</span></span></button>`;
      const matchRows=salesMatches.slice(0,3).map(item=>({title:item.property?.title||'Coincidencia inmobiliaria',location:[item.property?.province,item.property?.municipality].filter(Boolean).join(', ')||'España',time:formatRelativeTime(item.date),property:item.property}));
      if(matchesBox)matchesBox.innerHTML=matchRows.map(item=>{const image=resolveMarketplaceImage(item.property?.image,item.property?.type||'Activo inmobiliario');return`<button type="button" onclick="openMapPropertyCard('${escapeHTML(String(item.property?.id||''))}')" class="exec-row exec-clickable" aria-label="Abrir coincidencia ${escapeHTML(item.title)}"><img class="exec-thumb" src="${escapeHTML(image)}" alt="${escapeHTML(item.title)}" width="640" height="666" loading="lazy" decoding="async"><span class="exec-row-copy"><strong>${escapeHTML(item.title)}</strong><span>${escapeHTML(item.location)}</span></span><span class="exec-row-meta"><i class="exec-pill green">Nueva</i><br>${escapeHTML(item.time)}</span></button>`;}).join('')||`<button type="button" onclick="openExecutiveDestination('matches')" class="exec-row exec-clickable" aria-label="Abrir coincidencias"><span class="exec-row-copy"><strong>No hay coincidencias recientes</strong><span>Consulta el motor de coincidencias.</span></span></button>`;
      const taskRows=(state.tasks||[]).filter(item=>item.status!=='done').slice(0,4);
      if(tasksBox)tasksBox.innerHTML=taskRows.map((item,index)=>`<button type="button" onclick="openExecutiveDestination('tasks')" class="exec-row exec-clickable" aria-label="Abrir tarea ${escapeHTML(item.title)}"><span class="exec-task-check">✓</span><span class="exec-row-copy"><strong>${escapeHTML(item.title)}</strong></span><span class="exec-row-meta" style="${index===0?'color:#f05a78':''}">${escapeHTML(item.due||'Pendiente')}</span></button>`).join('')||`<button type="button" onclick="openExecutiveDestination('tasks')" class="exec-row exec-clickable" aria-label="Abrir tareas"><span class="exec-row-copy"><strong>No hay tareas pendientes</strong><span>Tu agenda está al día.</span></span></button>`;
      updateDashboardCreditSummary();
    }

    function loadExecutivePdfLibrary() {
      if(window.html2pdf)return Promise.resolve(window.html2pdf);
      return new Promise((resolve,reject)=>{const existing=document.getElementById('captacion-html2pdf');if(existing){existing.addEventListener('load',()=>resolve(window.html2pdf),{once:true});existing.addEventListener('error',()=>reject(new Error('No se pudo cargar el generador PDF.')),{once:true});return;}const script=document.createElement('script');script.id='captacion-html2pdf';script.src='https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';script.async=true;script.onload=()=>resolve(window.html2pdf);script.onerror=()=>reject(new Error('No se pudo cargar el generador PDF.'));document.head.appendChild(script);});
    }

    async function exportExecutiveDashboard() {
      const dashboard=document.querySelector('#private-panel-overview .exec-dashboard');const button=document.getElementById('exec-export-button');if(!dashboard||!button)return;
      const today=new Date();const isoDate=today.toISOString().slice(0,10);const meta=document.createElement('div');meta.className='exec-pdf-meta';meta.innerHTML=`<span>Generado: ${today.toLocaleString('es-ES')}</span><span>Profesional: ${escapeHTML(getProfessionalDisplayName())}</span>`;dashboard.querySelector('.exec-head')?.insertAdjacentElement('afterend',meta);dashboard.classList.add('exec-exporting');button.disabled=true;button.textContent='Generando PDF…';
      try{const html2pdf=await loadExecutivePdfLibrary();await html2pdf().set({margin:[8,8,8,8],filename:`resumen-ejecutivo-captacion-app-${isoDate}.pdf`,image:{type:'jpeg',quality:.96},html2canvas:{scale:2,useCORS:true,backgroundColor:getCurrentTheme()==='dark'?'#08172b':'#f5f8fc',scrollY:0},jsPDF:{unit:'mm',format:'a4',orientation:'portrait'},pagebreak:{mode:['css','legacy'],avoid:['.exec-card','.exec-kpi','.exec-row']}}).from(dashboard).save();showToast('Resumen ejecutivo exportado en PDF.','success');}
      catch(error){showToast(error.message||'No se pudo generar el PDF.','info');}
      finally{meta.remove();dashboard.classList.remove('exec-exporting');button.disabled=false;button.innerHTML='<span aria-hidden="true">⇩</span> Exportar PDF';}
    }

    async function purchaseAccessPack() {
      try {
        const response=await fetch(CAPTACION_MAILCHIMP.accessPurchaseEndpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':CAPTACION_MAILCHIMP.nonce},body:'{}'});
        const data=await response.json(); if(!response.ok||!data?.ok)throw new Error(data?.message||'No se pudo iniciar la compra.');
        if(data.checkoutConfigured&&data.checkoutUrl){window.open(data.checkoutUrl,'_blank','noopener,noreferrer');showToast('Checkout abierto. El saldo se actualizará cuando Stripe confirme el pago.','info');}
        else showToast(data.message||'Configura el Payment Link de Stripe para activar los packs.','info');
      } catch(error){showToast(error.message||'No se pudo iniciar la compra.','info');}
    }

    function renderAccessDashboard() {
      const summary=document.getElementById('private-access-summary'); const activity=document.getElementById('private-month-activity'); const history=document.getElementById('private-access-history');
      if(!summary||!activity||!history)return;
      const state=getPrivateDashboardState(); const available=Number(marketplaceAccessState?.remaining_marketplace_accesses||0); const consumed=Number(marketplaceAccessState?.monthly_consumed_accesses||0); const percentage=Number(marketplaceAccessState?.usage_percentage||0);
      if (marketplaceAccessState?.plan_type === 'beta') {
        const days = marketplaceAccessState?.trial_days_remaining;
        const active = marketplaceAccessState?.can_publish !== false;
        summary.innerHTML=`<div class="rounded-2xl bg-green-light p-4 text-center"><strong class="block text-xl text-green">${active ? 'Acceso beta activo' : 'Acceso beta finalizado'}</strong><span class="mt-1 block text-[11px] text-slate-600">${active ? (Number.isFinite(days) ? `Te quedan ${days} día${days === 1 ? '' : 's'} de prueba gratuita.` : 'Acceso beta ampliado por el equipo.') : 'Contacta con el equipo para continuar.'}</span></div>`;
        activity.innerHTML=[['Publicaciones',properties.length + needs.length],['Coincidencias',getSalesMatchRecords().length],['Colaboraciones',(state.requestsSent||[]).length]].map(([label,value])=>`<div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-center"><strong class="block text-xl text-navy">${value}</strong><span class="text-[10px] text-slate-500">${label}</span></div>`).join('');
        activity.insertAdjacentHTML('afterend', `<div class="mt-4 rounded-xl border border-blue/20 bg-blue-light/40 p-4 text-xs leading-relaxed text-slate-600"><strong class="block text-sm text-navy">Créditos de bienvenida</strong><p class="mt-2">Cada crédito desbloquea una oportunidad y permite consultar la información necesaria para solicitar una colaboración. Modelo circular: ganas +0.5 créditos cuando desbloqueen tus captaciones.</p><p class="mt-2">${marketplaceAccessState?.is_founder ? 'Usuario fundador: 20 créditos durante 60 días.' : 'Usuario nuevo: 3 créditos de bienvenida durante 30 días (no acumulables).'}</p>${available < 1 ? '<button type="button" onclick="purchaseAccessPack()" class="mt-3 rounded-xl bg-blue px-4 py-2.5 text-xs font-black text-white">Recargar créditos</button>' : ''}</div>`);
        history.innerHTML=`<div class="p-5 text-sm text-slate-600"><p>Cada crédito desbloquea una oportunidad y permite consultar la información necesaria para solicitar una colaboración. Modelo circular: ganas +0.5 créditos cuando desbloqueen tus captaciones.</p><p class="mt-2">${marketplaceAccessState?.is_founder ? 'Usuario fundador: 20 créditos durante 60 días.' : 'Usuario nuevo: 3 créditos de bienvenida durante 30 días (no acumulables).'}</p>${available < 1 ? '<button type="button" onclick="purchaseAccessPack()" class="mt-4 rounded-xl bg-blue px-4 py-3 text-xs font-black text-white">Recargar créditos</button>' : ''}</div>`;
        return;
      }
      const pack=marketplaceAccessState?.plan_type==='premium'?15:10; const canPack=['professional_plus','premium'].includes(marketplaceAccessState?.plan_type);
      summary.innerHTML=`<div class="grid grid-cols-3 gap-3 text-center"><div><strong class="block text-2xl text-blue">${available}</strong><span class="text-[11px] text-slate-500">Disponibles</span></div><div><strong class="block text-2xl text-navy">${consumed}</strong><span class="text-[11px] text-slate-500">Consumidos</span></div><div><strong class="block text-2xl text-navy">${percentage}%</strong><span class="text-[11px] text-slate-500">Utilizado</span></div></div><div class="mt-4 h-2.5 rounded-full bg-slate-200 overflow-hidden"><div class="h-full rounded-full ${percentage>=90?'bg-amber':'bg-blue'}" style="width:${Math.min(100,percentage)}%"></div></div>${canPack?`<button type="button" onclick="purchaseAccessPack()" class="mt-4 text-xs font-black text-blue">Añadir ${pack} accesos por 5 €</button>`:''}`;
      summary.innerHTML = summary.innerHTML.replaceAll('beta', 'periodo inicial');
      activity.innerHTML=[['Oportunidades',consumed],['Coincidencias',getSalesMatchRecords().length],['Contactos',(state.requestsSent||[]).length]].map(([label,value])=>`<div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-center"><strong class="block text-xl text-navy">${value}</strong><span class="text-[10px] text-slate-500">${label}</span></div>`).join('');
      history.innerHTML=marketplaceAccessHistory.length?`<table class="w-full min-w-[620px] text-left text-xs"><thead class="bg-slate-50 text-slate-500"><tr><th class="p-3">Fecha</th><th class="p-3">Oportunidad consultada</th><th class="p-3">Acceso</th><th class="p-3">Saldo restante</th></tr></thead><tbody>${marketplaceAccessHistory.map(row=>{const property=privatePropertyById(row.opportunity_id);return`<tr class="border-t border-slate-200"><td class="p-3">${escapeHTML(new Date(String(row.created_at).replace(' ','T')).toLocaleString('es-ES'))}</td><td class="p-3 font-bold text-navy">${escapeHTML(property?.title||row.opportunity_id)}</td><td class="p-3">1 consumido</td><td class="p-3">${Number(row.balance_remaining||0)}</td></tr>`}).join('')}</tbody></table>`:'<p class="p-5 text-sm text-slate-500">Todavía no has desbloqueado oportunidades.</p>';
      activity.innerHTML = activity.innerHTML.replaceAll('beta', 'periodo inicial');
      history.innerHTML = history.innerHTML.replaceAll('beta', 'periodo inicial');
      let alert=''; if(percentage>=100)alert='Has consumido todos los accesos incluidos en tu plan.';else if(percentage>=90)alert='Te quedan pocos accesos disponibles. Considera ampliar tu capacidad.';else if(percentage>=75)alert='Has utilizado gran parte de tus accesos mensuales.';
      if(alert&&sessionStorage.getItem('captacion_access_alert')!==String(percentage)){sessionStorage.setItem('captacion_access_alert',String(percentage));showToast(alert,'info');}
    }

    function renderDashboard() {
      syncPrivateProfile();
      renderExecutiveDashboard();
      renderAccessDashboard();
      renderPrivateKPIs(); renderPrivateAttention(); renderPrivateMatches(); renderPrivateOverviewOperations(); renderPrivateOverviewTasks(); renderPrivateActivity(); renderPrivateFavorites(); renderPrivateOffers(); renderPrivateDemands(); renderPrivateRequests(); renderPrivateOperations(); renderPrivateTasks(); renderPrivateNotifications(); loadPrivateOperationsFromBackend(); loadBackendMatches();
      renderPrivateAgendaCalendar('private-overview-calendar', 'private-overview-calendar-events', 5);
      renderPrivateAgendaCalendar('private-tasks-calendar', 'private-tasks-calendar-events', 10);
      applyDashboardPlanAccess();
      ensureBetaFeedbackLauncher();
    }

    function ensureBetaFeedbackLauncher() {
      // Intrusive button disabled for clean professional UI
      const existing = document.getElementById('beta-feedback-launcher');
      if (existing) existing.remove();
    }

    function openBetaFeedbackModal() {
      let modal = document.getElementById('beta-feedback-modal');
      if (!modal) {
        modal = document.createElement('div'); modal.id = 'beta-feedback-modal';
        modal.className = 'fixed inset-0 z-[150] hidden items-center justify-center bg-navy-dark/70 p-4 backdrop-blur-sm';
        modal.innerHTML = `<div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl"><button type="button" onclick="document.getElementById('beta-feedback-modal').classList.add('hidden')" class="float-right text-xl font-black text-slate-400" aria-label="Cerrar">×</button><span class="text-[10px] font-black uppercase tracking-wider text-blue">Programa beta</span><h3 class="mt-2 text-xl font-black text-navy">¿Cómo te ha resultado esta función?</h3><div class="mt-4 grid gap-2"><button type="button" data-beta-rating="useful" class="rounded-xl border border-slate-200 p-3 text-left text-xs font-bold text-navy hover:border-blue">Sí, me ha resultado útil</button><button type="button" data-beta-rating="neutral" class="rounded-xl border border-slate-200 p-3 text-left text-xs font-bold text-navy hover:border-blue">Más o menos</button><button type="button" data-beta-rating="difficult" class="rounded-xl border border-slate-200 p-3 text-left text-xs font-bold text-navy hover:border-blue">No he sabido utilizarla</button></div><textarea id="beta-feedback-comment" class="mt-4 w-full rounded-xl border border-slate-200 p-3 text-sm" rows="4" maxlength="1500" placeholder="¿Qué mejorarías? (opcional)"></textarea><p id="beta-feedback-error" class="mt-2 hidden text-xs text-red-600"></p><button id="beta-feedback-submit" type="button" class="mt-4 w-full rounded-xl bg-blue px-4 py-3 text-xs font-black text-white">Enviar opinión</button></div>`;
        document.body.appendChild(modal);
        let selected = '';
        modal.innerHTML = modal.innerHTML.replaceAll('Programa beta', 'Opinión sobre la web');
        modal.querySelectorAll('[data-beta-rating]').forEach(button => button.addEventListener('click', () => { selected = button.dataset.betaRating || ''; modal.querySelectorAll('[data-beta-rating]').forEach(item => item.classList.remove('border-blue','bg-blue-light')); button.classList.add('border-blue','bg-blue-light'); }));
        modal.querySelector('#beta-feedback-submit').addEventListener('click', async () => {
          const error = modal.querySelector('#beta-feedback-error'); const submit = modal.querySelector('#beta-feedback-submit');
          if (!selected) { error.textContent = 'Selecciona una opción antes de enviar.'; error.classList.remove('hidden'); return; }
          try {
            submit.disabled = true; submit.textContent = 'Enviando...';
            const response = await fetch(CAPTACION_MAILCHIMP.betaFeedbackEndpoint, {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':CAPTACION_MAILCHIMP.nonce},body:JSON.stringify({rating:selected,comment:cleanText(modal.querySelector('#beta-feedback-comment').value || ''),context:window.location.pathname + window.location.hash})});
            const data = await response.json(); if (!response.ok || !data?.ok) throw new Error(data?.message || 'No se pudo enviar tu opinión.');
            modal.classList.add('hidden'); showToast(data.message, 'success');
          } catch (err) { error.textContent = err.message || 'No se pudo enviar tu opinión.'; error.classList.remove('hidden'); }
          finally { submit.disabled = false; submit.textContent = 'Enviar opinión'; }
        });
      }
      modal.classList.remove('hidden'); modal.classList.add('flex');
    }

    function applyInternalPilotMessaging() {}

    function captacionSafeRun(label, fn) {
      try {
        fn();
      } catch (error) {
        console.warn(`[captacion] ${label}`, error);
      }
    }

    function initModalKeyboardSupport() {
      document.addEventListener('keydown', event => {
        const visibleModals = Array.from(document.querySelectorAll('.fixed.inset-0:not(.hidden)'));
        const modal = visibleModals[visibleModals.length - 1];
        if (!modal) return;
        if (event.key === 'Escape') {
          const closeButton = modal.querySelector('button[aria-label="Cerrar"], .vera-close-btn');
          if (closeButton) { event.preventDefault(); closeButton.click(); }
          return;
        }
        if (event.key !== 'Tab') return;
        const focusable = Array.from(modal.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),summary,[tabindex]:not([tabindex="-1"])')).filter(element => element.offsetParent !== null);
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      });
    }

    // Los formularios principales se enlazan desde JavaScript en vez de usar
    // atributos onsubmit. Esto evita que una política CSP o un contexto de
    // ejecución aislado bloquee la publicación y la demanda sin mostrar la
    // previsualización.
    function bindCorePublicationForms() {
      const bindings = [
        ['need-publication-form', handleNewNeed],
        ['offer-publication-form', handleNewOffer]
      ];
      bindings.forEach(([formId, handler]) => {
        const form = document.getElementById(formId);
        if (!form || form.dataset.captacionSubmitBound === '1') return;
        const runHandler = event => {
          event.preventDefault();
          handler(event);
        };
        form.addEventListener('submit', runHandler);
        // Algunos constructores/optimizadores de WordPress interceptan el
        // submit nativo. Atendemos también el botón final para asegurar que
        // la validación y la previsualización se ejecuten siempre.
        form.addEventListener('click', event => {
          if (!event.target.closest('button[type="submit"]')) return;
          runHandler(event);
        });
        form.querySelectorAll('button[type="submit"]').forEach(button => {
          button.type = 'button';
          button.addEventListener('click', runHandler);
        });
        form.dataset.captacionSubmitBound = '1';
      });
    }


    // --- INICIALIZADOR DE LA PLATAFORMA ---
    function showEmailVerificationResult() {
      const url = new URL(window.location.href);
      const result = url.searchParams.get('email_verification');
      if (!result) return;
      showToast(
        result === 'success'
          ? 'Correo confirmado. Ya puedes iniciar sesion y acceder a tu cuenta.'
          : 'El enlace de verificación no es válido o ha caducado. Solicita un nuevo correo.',
        result === 'success' ? 'success' : 'error'
      );
      url.searchParams.delete('email_verification');
      window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash || '#/inicio'}`);
    }

    function initApp() {
      captacionSafeRun('applyTheme', () => applyTheme(getCurrentTheme(), false));
      captacionSafeRun('clear stale session', () => {
        const storedSession = getDemoSession();
        if (storedSession && !storedSession.emailVerified) localStorage.removeItem('captacion_demo_session_v4');
      });
      captacionSafeRun('ensureWordPressSession', ensureWordPressSession);
      captacionSafeRun('startRegistrationPromptCycle', startRegistrationPromptCycle);
      captacionSafeRun('bind popstate', () => window.addEventListener('popstate', handleRoute));
      captacionSafeRun('bind hashchange', () => window.addEventListener('hashchange', handleRoute));
      captacionSafeRun('default pathname', () => {
        let initPath = window.location.pathname;
        if (initPath.startsWith(CAPTACION_BASE_PATH)) {
          initPath = initPath.substring(CAPTACION_BASE_PATH.length);
        }
        initPath = '/' + initPath.replace(/^\/+|\/+$/g, '');
        if ((initPath === '/' || initPath === '/index.php' || !initPath) && !/^#\/[a-z0-9-]+/i.test(window.location.hash || '')) {
          window.history.replaceState(null, '', CAPTACION_BASE_PATH.replace(/\/+$/, '') + '/inicio');
        }
      });
      captacionSafeRun('handleRoute', handleRoute);
      captacionSafeRun('repairMojibakeInDOM', repairMojibakeInDOM);
      captacionSafeRun('initGeoSelectors', initGeoSelectors);
      captacionSafeRun('load territory catalog', () => {
        captacionLoadTerritoryCatalog().then(() => {
          captacionSafeRun('initGeoSelectors', initGeoSelectors);
          captacionSafeRun('initTerritorySelectors', initTerritorySelectors);
        });
      });
      captacionSafeRun('bindCorePublicationForms', bindCorePublicationForms);
      captacionSafeRun('updatePropertyFormDynamics need', () => updatePropertyFormDynamics('need'));
      captacionSafeRun('updatePropertyFormDynamics offer', () => updatePropertyFormDynamics('offer'));
      captacionSafeRun('initializeProgressiveForms', initializeProgressiveForms);
      captacionSafeRun('filterNeeds', filterNeeds);
      captacionSafeRun('renderMarketplace', renderMarketplace);
      captacionSafeRun('renderDashboard', renderDashboard);
      captacionSafeRun('renderHome', renderHome);
      captacionSafeRun('applyInternalPilotMessaging', applyInternalPilotMessaging);
      captacionSafeRun('calculateSplit', calculateSplit);
      captacionSafeRun('updateFeeCalculator', () => updateFeeCalculator());
      captacionSafeRun('initResourcesToolbox', initResourcesToolbox);
      captacionSafeRun('initContactFormExperience', initContactFormExperience);
      // La sesión de WordPress puede hidratarse después de pintar la SPA;
      // refrescar únicamente el formulario para mostrar los datos del usuario.
      window.setTimeout(() => captacionSafeRun('refreshContactFormAfterSession', initContactFormExperience), 1500);
      captacionSafeRun('showEmailVerificationResult', showEmailVerificationResult);
      captacionSafeRun('initModalKeyboardSupport', initModalKeyboardSupport);
      captacionSafeRun('activateProfessionalMembershipFromReturn', activateProfessionalMembershipFromReturn);
      captacionSafeRun('loadPrivateXmlUrl', loadPrivateXmlUrl);
      captacionSafeRun('renderAIConnections', renderAIConnections);
      captacionSafeRun('loadWordPressRealEstateRecords', loadWordPressRealEstateRecords);
      captacionSafeRun('removeLegacyCookiePreferences', removeLegacyCookiePreferences);
      captacionSafeRun('captacionInitCookieNotice', () => window.setTimeout(captacionInitCookieNotice, 900));
      captacionSafeRun('bind storage', () => {
        window.addEventListener('storage', () => {
          if (CAPTACION_PRODUCTION_MODE) return;
          try { properties = (JSON.parse(localStorage.getItem('captacion_properties_v3')) || []).map(normalizePropertyRecord); } catch (e) {}
          try { needs = (JSON.parse(localStorage.getItem('captacion_needs_v3')) || []).map(normalizeNeedRecord); } catch (e) {}
          try { closedOperations = JSON.parse(localStorage.getItem('captacion_closed_operations_v4')) || []; } catch (e) {}
          captacionSafeRun('renderMarketplace(storage)', renderMarketplace);
          captacionSafeRun('renderDashboard(storage)', renderDashboard);
          captacionSafeRun('filterNeeds(storage)', filterNeeds);
          captacionSafeRun('renderHome(storage)', renderHome);
          captacionSafeRun('applyInternalPilotMessaging(storage)', applyInternalPilotMessaging);
        });
      });
      captacionSafeRun('fetchMarketplaceAccessState', () => fetchMarketplaceAccessState().then(() => { applyDashboardPlanAccess(); loadWordPressTasks(); }).catch(() => applyDashboardPlanAccess()));
      captacionSafeRun('refreshCreditsExperience', refreshCreditsExperience);
      captacionSafeRun('refreshReputationExperience', refreshReputationExperience);
      captacionSafeRun('initHeroExplainerVideo', initHeroExplainerVideo);
    }

    function initHeroExplainerVideo() {
      const video = document.getElementById('hero-explainer-video');
      if (video && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
          if (!entries.some(entry => entry.isIntersecting)) return;
          observer.disconnect();
          startHeroExplainerVideo(video);
        }, { rootMargin: '160px 0px', threshold: 0.01 });
        observer.observe(video);
      } else if (video) {
        startHeroExplainerVideo(video);
      }
    }

    function startHeroExplainerVideo(video) {
      if (video.dataset.started === '1') return;
      video.dataset.started = '1';
      video.muted = true;
      video.loop = true;
      video.playsInline = true;
      const playPromise = video.play();
      if (playPromise !== undefined) {
        playPromise.catch(() => {
          // Autoplay with muted is allowed, ignore user-gesture rejection
        });
      }
    }

    function toggleHeroVideoAudio() {
      const video = document.getElementById('hero-explainer-video');
      const icon = document.getElementById('hero-video-audio-icon');
      const label = document.getElementById('hero-video-audio-label');
      const btn = document.getElementById('hero-video-audio-btn');
      if (!video) return;

      if (video.muted) {
        video.muted = false;
        video.volume = 1.0;
        const p = video.play();
        if (p !== undefined) p.catch(() => {});
        if (icon) icon.textContent = '🔊';
        if (label) label.textContent = 'Silenciar';
        if (btn) btn.setAttribute('title', 'Silenciar sonido');
        if (typeof showToast === 'function') {
          showToast('Audio del vídeo activado', 'info');
        }
      } else {
        video.muted = true;
        if (icon) icon.textContent = '🔇';
        if (label) label.textContent = 'Activar sonido';
        if (btn) btn.setAttribute('title', 'Activar sonido');
        if (typeof showToast === 'function') {
          showToast('Audio del vídeo silenciado', 'info');
        }
      }
    }

    window.toggleHeroVideoAudio = toggleHeroVideoAudio;
    window.initHeroExplainerVideo = initHeroExplainerVideo;

    let currentPricingCycle = 'annual';

    function setPricingBillingCycle(cycle) {
      currentPricingCycle = cycle;
      const annualBtn = document.getElementById('pricing-toggle-annual');
      const monthlyBtn = document.getElementById('pricing-toggle-monthly');
      
      if (annualBtn && monthlyBtn) {
        if (cycle === 'annual') {
          annualBtn.className = 'px-5 py-2.5 rounded-xl bg-blue text-white font-bold text-xs sm:text-sm shadow-md transition-all flex items-center gap-2';
          monthlyBtn.className = 'px-5 py-2.5 rounded-xl text-slate-600 dark:text-slate-300 hover:text-navy dark:hover:text-white font-bold text-xs sm:text-sm transition-all';
        } else {
          monthlyBtn.className = 'px-5 py-2.5 rounded-xl bg-blue text-white font-bold text-xs sm:text-sm shadow-md transition-all';
          annualBtn.className = 'px-5 py-2.5 rounded-xl text-slate-600 dark:text-slate-300 hover:text-navy dark:hover:text-white font-bold text-xs sm:text-sm transition-all flex items-center gap-2';
        }
      }

      // Plan 1: Autónomo (5 créditos mensuales)
      const p1Price = document.getElementById('plan-inicial-price');
      const p1Period = document.getElementById('plan-inicial-period');
      const p1Unit = document.getElementById('plan-inicial-unit');
      const p1Billed = document.getElementById('plan-inicial-billed');
      if (p1Price) p1Price.textContent = cycle === 'annual' ? '19 €' : '29 €';
      if (p1Period) p1Period.textContent = '/mes';
      if (p1Unit) p1Unit.textContent = cycle === 'annual' ? '3,80 €/crédito' : '5,80 €/crédito';
      if (p1Billed) p1Billed.textContent = cycle === 'annual' ? 'Facturado anualmente: 228 €/año (Ahorras 120 €/año · 33% DTO)' : 'Facturación mensual sin permanencia';

      // Plan 2: Agencia (10 créditos mensuales)
      const p2Price = document.getElementById('plan-profesional-price');
      const p2Period = document.getElementById('plan-profesional-period');
      const p2Unit = document.getElementById('plan-profesional-unit');
      const p2Billed = document.getElementById('plan-profesional-billed');
      if (p2Price) p2Price.textContent = cycle === 'annual' ? '29 €' : '44 €';
      if (p2Period) p2Period.textContent = '/mes';
      if (p2Unit) p2Unit.textContent = cycle === 'annual' ? '2,90 €/crédito' : '4,40 €/crédito';
      if (p2Billed) p2Billed.textContent = cycle === 'annual' ? 'Facturado anualmente: 348 €/año (Ahorras 180 €/año · 33% DTO)' : 'Facturación mensual sin permanencia';

      // Plan 3: Broker (15 créditos mensuales)
      const p3Price = document.getElementById('plan-premium-price');
      const p3Period = document.getElementById('plan-premium-period');
      const p3Unit = document.getElementById('plan-premium-unit');
      const p3Billed = document.getElementById('plan-premium-billed');
      if (p3Price) p3Price.textContent = cycle === 'annual' ? '49 €' : '74 €';
      if (p3Period) p3Period.textContent = '/mes';
      if (p3Unit) p3Unit.textContent = cycle === 'annual' ? '3,27 €/crédito' : '4,93 €/crédito';
      if (p3Billed) p3Billed.textContent = cycle === 'annual' ? 'Facturado anualmente: 588 €/año (Ahorras 300 €/año · 33% DTO)' : 'Facturación mensual sin permanencia';
    }
    window.setPricingBillingCycle = setPricingBillingCycle;

    function forceInitialPageVisibility() {
      const pathname = getCurrentAppRoutePath();
      
      const activePageId = routes[pathname] || 'page-inicio';
      document.querySelectorAll('.page-section').forEach(section => {
        section.classList.add('hidden');
      });
      document.getElementById(activePageId)?.classList.remove('hidden');
    }

    // Lanzar inicialización controlada
    if (document.readyState === 'loading') {
      window.addEventListener('DOMContentLoaded', initApp, { once: true });
    } else {
      initApp();
    }

    // Fallback defensivo: si por caché/defer el router no inicializa a tiempo,
    // al menos la página activa no queda totalmente oculta.
    window.addEventListener('load', () => {
      forceInitialPageVisibility();
      initHeroExplainerVideo();
      try { handleRoute(); } catch (error) {}
    }, { once: true });

    /* =============================================
       DATA & PRIVACY MANAGEMENT (XML import/export, batches, deletion)
       ============================================= */

    function chooseFeedXmlFile() {
      document.getElementById('private-feed-xml-file')?.click();
    }

    function handleFeedXmlFileSelected() {
      const fileInput = document.getElementById('private-feed-xml-file');
      const nameInput = document.getElementById('private-feed-xml-file-name');
      if (nameInput) nameInput.value = fileInput?.files?.[0]?.name || '';
    }

    function getXmlImportFailureHelp(error = {}) {
      const code = String(error.code || error.data?.code || '').toLowerCase();
      const message = String(error.message || error.data?.message || 'No se pudo importar el archivo.');
      const supportEmail = CAPTACION_MAILCHIMP?.supportEmail || window.CAPTACION_CONFIG?.contactEmail || 'soporte@compracaptacion.com';

      if (message.includes("Unexpected token '<'") || message.includes("<!DOCTYPE") || message.includes("is not valid JSON")) {
        return {
          message,
          reason: 'El servidor o la URL remota devolvió una respuesta no válida (página HTML en lugar de datos XML).',
          action: 'Comprueba que la URL del feed XML sea directa y pública sin pasarelas intermedias.',
          supportEmail
        };
      }

      const cases = [
        { match: ['cookie_or_html', 'cookie', 'consent', 'login', 'acceso restringido', 'html'], reason: 'La URL devuelve una página HTML o una pantalla intermedia de cookies/login, no un XML válido.', action: 'Usa una URL pública que entregue el XML directamente, sin comprobación de cookies, login ni redirecciones HTML.' },
        { match: ['doctype_xxe', 'entity_xxe'], reason: 'El XML contiene definiciones ENTITY no permitidas por seguridad.', action: 'Solicita al proveedor una versión del XML sin declaraciones de entidad externas.' },
        { match: ['no_properties', 'no se detectaron propiedades', 'no se han detectado propiedades'], reason: 'El sistema no ha detectado propiedades compatibles en el archivo.', action: 'Puede ser un formato no soportado todavía. Abre un ticket adjuntando la URL o una muestra del archivo.' },
        { match: ['parse', 'root', 'schema'], reason: 'El archivo no se puede interpretar con la estructura esperada.', action: 'Revisa que sea XML, CSV o JSON válido. Si pertenece a un portal externo, abre un ticket para mapear su formato.' },
        { match: ['fetch', 'http', 'url'], reason: 'No se pudo descargar la URL o el servidor del proveedor no respondió correctamente.', action: 'Comprueba que la URL sea pública y accesible. Si funciona en navegador pero falla aquí, abre un ticket.' },
        { match: ['size'], reason: 'El archivo supera el tamaño máximo permitido.', action: 'Divide el feed o solicita al proveedor una versión más ligera.' },
        { match: ['mime', 'extension', 'file'], reason: 'El archivo no parece tener un formato permitido o no se pudo leer correctamente.', action: 'Sube un archivo con extensión .xml, .csv o .json.' }
      ];
      const selected = cases.find(item => item.match.some(token => code.includes(token) || message.toLowerCase().includes(token))) || {
        reason: 'El archivo no se ha podido importar con el formato actual.',
        action: 'Si el archivo procede de un CRM, portal o proveedor externo, abre un ticket para revisar compatibilidad.'
      };
      return { message, reason: selected.reason, action: selected.action, supportEmail };
    }

    function renderXmlImportError(container, error = {}) {
      const help = getXmlImportFailureHelp(error);
      if (!container) return;
      container.classList.remove('hidden');
      container.innerHTML = `<div class="p-4 rounded-2xl border border-red-100 bg-red-50 text-xs text-red-700 leading-relaxed">
        <strong class="block text-red-700 mb-2">No se pudo importar el archivo.</strong>
        <p><strong>Motivo:</strong> ${escapeHTML(help.reason)}</p>
        <p class="mt-1"><strong>Detalle técnico:</strong> ${escapeHTML(help.message)}</p>
        <p class="mt-1"><strong>Qué hacer:</strong> ${escapeHTML(help.action)}</p>
        <p class="mt-2 text-slate-600">Si necesitas soporte, abre un ticket con el administrador e incluye la URL o el archivo y este detalle técnico. Contacto: <strong>${escapeHTML(help.supportEmail)}</strong></p>
      </div>`;
    }

    async function importFeedXmlFile() {
      await importXmlFileFromInput('private-feed-xml-file', 'private-feed-xml-import-result');
    }

    async function importPrivateUserXml() {
      await importXmlFileFromInput('private-data-xml-file', 'private-xml-import-result');
    }

    async function importXmlFileFromInput(inputId, resultId) {
      const fileInput = document.getElementById(inputId);
      const resultDiv = document.getElementById(resultId);
      const file = fileInput?.files?.[0];
      if (!file) { showToast('Selecciona un archivo XML, CSV o JSON.', 'error'); return; }
      if (!/\.(xml|csv|json)$/i.test(file.name)) { showToast('El archivo debe tener extensión .xml, .csv o .json.', 'error'); return; }
      resultDiv?.classList.remove('hidden');
      if (resultDiv) resultDiv.innerHTML = '<span class="text-blue">Importando archivo...</span>';
      try {
        const formData = new FormData();
        formData.append('file', file);
        const endpoint = (window.CAPTACION_API?.endpoints?.uploadImportFile || window.CAPTACION_API?.endpoints?.uploadXmlFile) || '/api/xml_feeds.php?action=upload_file';
        const res = await fetch(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-WP-Nonce': window.CAPTACION_API?.nonce || '' },
          body: formData
        });
        const rawText = await res.text();
        let data;
        try {
          data = JSON.parse(rawText);
        } catch(e) {
          throw new Error('El servidor devolvió una respuesta no válida al subir el archivo.');
        }
        if (data.ok) {
          const imported = Number(data.properties_imported || data.imported || 0);
          const updated = Number(data.properties_updated || 0);
          const pending = Number(data.properties_pending_review || 0);
          const failed = Number(data.properties_failed || data.rejected || 0);
          const fileFeedId = data.feed_id || data.import_batch_id || '';
          if (resultDiv) resultDiv.innerHTML = `<div class="p-3 rounded-xl border border-green/20 bg-green-light text-xs text-slate-700"><strong class="text-green">Importación finalizada y publicada automáticamente.</strong> ${imported} importadas, ${updated} actualizadas, ${pending} con atención, ${failed} con error. ${failed || pending ? 'Puedes enviar el detalle a soporte para revisión.' : ''}</div>`;
          showToast((failed || pending) ? `Archivo publicado con ${failed + pending} incidencias.` : `Archivo importado y publicado: ${imported} propiedades.`, (failed || pending) ? 'info' : 'success');
          await loadXmlFeeds();
          await loadWordPressRealEstateRecords();
        } else {
          const importError = { code: data.code, message: data.message || 'Error desconocido', data };
          renderXmlImportError(resultDiv, importError);
          prepareXmlSupportRequest(importError.message, { source: file.name });
        }
      } catch (e) {
        renderXmlImportError(resultDiv, e);
        prepareXmlSupportRequest(e.message || 'No se pudo importar el archivo.', { source: file.name });
      }
    }

    function renderXmlFeedsList(batches = []) {
      const listTargets = [document.getElementById('private-import-batches-list'), document.getElementById('private-feed-import-batches-list')].filter(Boolean);
      if (!listTargets.length) return;
      const allBatches = (Array.isArray(batches) ? batches : []).filter(item => item.status !== 'deleted');
      const visibleBatches = allBatches.filter(item => item.status !== 'pending_deletion');
      const pendingDeletions = allBatches.filter(item => item.status === 'pending_deletion');
      window.CAPTACION_XML_BATCHES = allBatches;
      if (!allBatches.length) {
        listTargets.forEach(listDiv => { listDiv.innerHTML = '<p class="text-xs text-slate-400">Todavía no has realizado ninguna importación.</p>'; });
        return;
      }
      const pendingHtml = pendingDeletions.length ? `<section class="mb-4 rounded-2xl border border-amber/30 bg-amber-light/40 p-4"><div class="flex items-center justify-between gap-3"><div><strong class="block text-xs font-black text-amber">Eliminaciones en espera</strong><p class="mt-1 text-[11px] text-slate-600">Estos feeds ya no se muestran en el Marketplace ni en las importaciones activas.</p></div><span class="text-[10px] font-black text-amber">${pendingDeletions.length} pendiente${pendingDeletions.length === 1 ? '' : 's'}</span></div><div class="mt-3 space-y-2">${pendingDeletions.map(b => { const deadline = b.deletion_deadline_at ? new Date(String(b.deletion_deadline_at).replace(' ', 'T')).toLocaleString('es-ES', { dateStyle:'short', timeStyle:'short' }) : ''; const expired = Boolean(b.deletion_expired); return `<div class="rounded-xl border border-amber/20 bg-white/80 px-3 py-3 flex flex-col lg:flex-row lg:items-center justify-between gap-3"><div class="min-w-0"><strong class="block truncate text-xs text-navy">${escapeHTML(b.source_file_name || b.import_batch_id)}</strong><span class="block mt-1 text-[10px] ${expired ? 'text-red-600 font-bold' : 'text-amber'}">${expired ? 'Plazo finalizado: puedes eliminar definitivamente.' : `Gestiona los pendientes antes del ${deadline}.`}</span></div><div class="flex gap-2"><button type="button" onclick="showImportBatchReport('${b.import_batch_id}')" class="px-3 py-2 rounded-lg border border-blue/20 bg-white text-blue text-[10px] font-bold">Detalle</button>${expired ? `<button type="button" onclick="deleteImportBatch('${b.import_batch_id}', this)" class="px-3 py-2 rounded-lg bg-red-600 text-white text-[10px] font-bold">Eliminar definitivamente</button>` : `<button type="button" onclick="showFeedPendingReviewPanel('${b.import_batch_id}')" class="px-3 py-2 rounded-lg bg-amber text-navy text-[10px] font-bold">Gestionar</button>`}</div></div>`; }).join('')}</div></section>` : '';
      const html = visibleBatches.map(b => {
        const date = new Date(b.created_at).toLocaleDateString('es-ES');
        const isPaused = b.status === 'paused';
        const isPendingDeletion = b.status === 'pending_deletion';
        const isError = b.status === 'error';
        const isRolledBack = b.status === 'rolled_back';
        const isMarketplacePublished = String(b.privacy_scope || '') === 'global_public' && Number(b.marketplace_published_properties_count || 0) > 0;
        const statusBadge = isRolledBack ? 'bg-slate-100 text-slate-500' : isPendingDeletion ? 'bg-amber-light text-amber' : isPaused ? 'bg-slate-100 text-slate-500' : isError ? 'bg-red-50 text-red-600' : 'bg-green-light text-green';
        const statusLabel = isRolledBack ? 'Revertido' : isPendingDeletion ? 'Pendiente eliminación' : isPaused ? 'Pausado' : isError ? 'Error' : 'Activo';
        const sourceName = b.source_file_name || b.import_batch_id;
        const sourceLabel = sourceName.replace(/^https?:\/\/(?:www\.)?/i, '').replace(/\/(?:xml|feed|feeds)\/.*$/i, ' · XML');
        return `<div class="xml-feed-card flex flex-col xl:flex-row xl:items-center justify-between gap-3 p-4 rounded-xl border border-slate-200 bg-white" data-xml-feed-id="${escapeHTML(b.import_batch_id)}">
          <div class="min-w-0 xml-feed-card__source">
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase ${statusBadge}">${statusLabel}</span>
              <span class="text-[10px] text-slate-400">${date}</span>
            </div>
            <span class="text-xs font-bold text-navy block mt-2 truncate" title="${escapeHTML(sourceName)}">${escapeHTML(sourceLabel)}</span>
            <span class="text-[10px] text-slate-500">${escapeHTML(b.import_batch_id)} · ${escapeHTML(b.data_origin)} · ${Number(b.records_imported || 0)}/${Number(b.records_total || 0)} registros${isPendingDeletion ? ` · ${Number(b.pending_blockers_count || 0)} procesos pendientes` : ''}</span>
          </div>
          <div class="xml-feed-card__actions flex flex-wrap items-center gap-2">
            <div class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-center"><strong class="block text-lg font-black text-blue">${Number(b.properties_count || 0)}</strong><span class="block text-[9px] uppercase tracking-wider font-black text-slate-400">Propiedades</span></div>
            <div class="px-3 py-2 rounded-xl bg-green-light border border-green/20 text-center"><strong class="block text-lg font-black text-green">${Number(b.active_properties_count || 0)}</strong><span class="block text-[9px] uppercase tracking-wider font-black text-green">Activas</span></div>
            <div class="px-3 py-2 rounded-xl bg-amber-light border border-amber/20 text-center"><strong class="block text-lg font-black text-amber">${Number(b.pending_review_properties_count || 0)}</strong><span class="block text-[9px] uppercase tracking-wider font-black text-amber">Revisión</span></div>
            <div class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-center"><strong class="block text-lg font-black text-navy">${Number(b.needs_count || 0)}</strong><span class="block text-[9px] uppercase tracking-wider font-black text-slate-400">Demandas</span></div>
            <span class="px-3 py-2 rounded-xl border text-[10px] font-black ${isMarketplacePublished ? 'border-green/20 bg-green-light text-green' : 'border-amber/20 bg-amber-light text-amber'}">${isMarketplacePublished ? `Publicado: ${Number(b.marketplace_published_properties_count || 0)}` : 'Privado · pendiente de publicar'}</span>
            <button type="button" onclick="showImportBatchReport('${b.import_batch_id}')" class="px-3 py-2 rounded-xl border border-blue/20 bg-white hover:bg-blue-light/40 text-blue text-[10px] font-bold transition-all">Ver informe</button>
            ${Number(b.pending_review_properties_count || 0) > 0 ? `<button type="button" onclick="showFeedPendingReviewPanel('${b.import_batch_id}')" class="px-3 py-2 rounded-xl border border-amber/20 bg-amber-light hover:bg-amber text-amber hover:text-white text-[10px] font-bold transition-all">Revisar</button>` : ''}
            ${b.data_origin === 'xml_url' && !isPendingDeletion ? `<button type="button" onclick="syncImportBatch('${b.import_batch_id}', this)" class="px-3 py-2 rounded-xl border border-blue/20 bg-blue text-white hover:bg-blue-dark text-[10px] font-bold transition-all">Actualizar</button>` : ''}
            ${!isPendingDeletion && !isRolledBack ? `<button type="button" onclick="setImportBatchPublication('${b.import_batch_id}', '${isMarketplacePublished ? 'withdraw' : 'publish'}', this)" class="px-3 py-2 rounded-xl ${isMarketplacePublished ? 'border border-amber/30 bg-amber-light text-amber hover:bg-amber hover:text-white' : 'bg-green hover:bg-green-dark text-white'} text-[10px] font-black transition-all">${isMarketplacePublished ? 'Retirar publicación' : 'Publicar en Marketplace'}</button>` : ''}
            ${!isPendingDeletion && !isRolledBack ? `<button type="button" onclick="updateImportBatchStatus('${b.import_batch_id}', '${isPaused ? 'active' : 'paused'}', this)" class="px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-navy text-[10px] font-bold transition-all">${isPaused ? 'Reactivar' : 'Pausar'}</button>` : isPendingDeletion ? '<span class="px-3 py-2 rounded-xl border border-amber/20 bg-amber-light text-amber text-[10px] font-bold">Esperando cierre</span>' : ''}
            ${!isPendingDeletion && !isRolledBack ? `<button type="button" onclick="rollbackImportBatch('${b.import_batch_id}', this)" class="px-3 py-2 rounded-xl border border-amber/20 bg-amber-light hover:bg-amber text-amber hover:text-white text-[10px] font-bold transition-all">Revertir</button>` : ''}
            <button type="button" onclick="deleteImportBatch('${b.import_batch_id}', this)" class="px-3 py-2 rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 text-[10px] font-bold transition-all">${isPendingDeletion ? 'Revisar eliminación' : 'Eliminar'}</button>
          </div>
        </div>`;
      }).join('');
      listTargets.forEach(listDiv => { listDiv.innerHTML = `${pendingHtml}${html || '<p class="text-xs text-slate-400">No tienes importaciones activas.</p>'}`; });
    }

    async function loadXmlFeeds() {
      const listTargets = [document.getElementById('private-import-batches-list'), document.getElementById('private-feed-import-batches-list')].filter(Boolean);
      if (!listTargets.length) return [];
      try {
        const endpoint = (window.CAPTACION_API?.endpoints?.listXmlFeeds) || '/api/xml_feeds.php?action=list';
        const res = await fetch(endpoint, {
          headers: { 'X-WP-Nonce': window.CAPTACION_API?.nonce || '' }
        });
        const rawText = await res.text();
        let data;
        try {
          data = JSON.parse(rawText);
        } catch(e) {
          data = { ok: true, batches: [] };
        }
        if (!res.ok || !data.ok) throw new Error(data.message || 'No se pudo cargar la lista de importaciones.');
        renderXmlFeedsList(data.batches || []);
        return data.batches || [];
      } catch (e) {
        listTargets.forEach(listDiv => { listDiv.innerHTML = '<p class="text-xs text-slate-400">No tienes importaciones activas.</p>'; });
        return [];
      }
    }

    async function loadImportBatches() { return loadXmlFeeds(); }

    function setXmlFeedActionLoading(button, isLoading, loadingText) {
      if (!button) return;
      if (isLoading) {
        button.dataset.originalText = button.textContent;
        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-wait');
        button.textContent = loadingText;
        return;
      }
      button.disabled = false;
      button.classList.remove('opacity-60', 'cursor-wait');
      button.textContent = button.dataset.originalText || button.textContent;
      delete button.dataset.originalText;
    }

    const PENDING_REVIEW_FIELD_LABELS = {
      title: 'Título',
      type: 'Tipo de propiedad',
      operation: 'Operación (venta/alquiler)',
      price: 'Precio',
      currency: 'Moneda',
      location: 'Ubicación (provincia, municipio)',
      description: 'Descripción',
      owner: 'Propietario / email',
      source: 'Fuente',
      rooms: 'Habitaciones',
      bathrooms: 'Baños',
      surface: 'Superficie (m²)',
    };

    window.CAPTACION_PENDING_PROPERTIES = [];
    window.CAPTACION_PENDING_SELECTION = new Set();
    window.CAPTACION_PENDING_SELECTED_CATEGORIES = new Set();
    window.CAPTACION_PENDING_FILTER = 'all';

    function closeFeedPendingModal() {
      document.getElementById('xml-feed-pending-modal')?.classList.add('hidden');
      delete document.getElementById('xml-feed-pending-modal')?.dataset?.feedId;
    }

    function getFeedPendingFeedId() {
      return document.getElementById('xml-feed-pending-modal')?.dataset?.feedId || '';
    }

    async function showFeedPendingReviewPanel(feedId) {
      const modal = document.getElementById('xml-feed-pending-modal');
      const title = document.getElementById('xml-feed-pending-title');
      const body = document.getElementById('xml-feed-pending-body');
      const controls = document.getElementById('xml-feed-pending-controls');
      const counter = document.getElementById('xml-feed-pending-counter');
      if (!modal || !title || !body) return;
      modal.dataset.feedId = feedId;
      modal.classList.remove('hidden');
      title.textContent = 'Cargando propiedades pendientes...';
      body.innerHTML = '<p class="text-xs text-slate-400">Cargando...</p>';
      if (controls) controls.classList.add('hidden');
      if (counter) counter.textContent = '';
      const batch = (window.CAPTACION_XML_BATCHES || []).find(item => item.import_batch_id === feedId);
      const sourceName = batch?.source_file_name || feedId;
      const properties = await loadFeedPendingProperties(feedId);
      if (!properties || !properties.length) {
        title.textContent = `Sin pendientes: ${sourceName}`;
        body.innerHTML = '<div class="p-4 rounded-2xl bg-green-light border border-green/20 text-xs text-green font-bold">No hay propiedades pendientes de revisión en este feed.</div>';
        if (counter) counter.textContent = '0 pendientes';
        return;
      }
      window.CAPTACION_PENDING_PROPERTIES = properties;
      window.CAPTACION_PENDING_SELECTION = new Set();
      window.CAPTACION_PENDING_SELECTED_CATEGORIES = new Set();
      window.CAPTACION_PENDING_FILTER = 'all';
      title.textContent = `${properties.length} propiedades pendientes · ${sourceName}`;

      // Reset controls values
      const selectAllChk = document.getElementById('xml-pending-select-all');
      if (selectAllChk) selectAllChk.checked = false;
      const categoryFilter = document.getElementById('xml-pending-category-filter');
      if (categoryFilter) categoryFilter.value = 'all';

      body.innerHTML = properties.map(p => renderFeedPendingProperty(p, feedId)).join('');
      if (controls) controls.classList.remove('hidden');
      if (counter) counter.textContent = `${properties.length} propiedad(es) pendiente(s)`;
      updatePendingCategoryControls();
      syncPendingSelectionUi();
    }

    async function loadFeedPendingProperties(feedId) {
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.feedPending + encodeURIComponent(feedId) + '/pending', {
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce }
        });
        const data = await res.json();
        if (data.ok) return data.properties || [];
        return [];
      } catch (e) {
        showToast('Error al cargar propiedades pendientes.', 'error');
        return [];
      }
    }

    function renderFeedPendingProperty(property, feedId) {
      const fields = Array.isArray(property.missing_fields) ? property.missing_fields : [];
      const fieldBadges = fields.map(f => `<span class="inline-flex px-2 py-0.5 rounded-full bg-amber-light text-amber text-[9px] font-black uppercase">${escapeHTML(PENDING_REVIEW_FIELD_LABELS[f] || f)}</span>`).join(' ');
      const rk = property.record_key;
      const cat = normalizeOpportunityCategory(property.property_type || property.type || 'Piso');
      return `<div class="p-4 rounded-2xl border border-slate-200 bg-white" data-pending-key="${escapeHTML(rk)}" data-category="${escapeHTML(cat)}">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div class="min-w-0 flex items-start gap-3">
            <input type="checkbox" data-pending-checkbox="${escapeHTML(rk)}" onchange="togglePendingPropertySelection('${escapeHTML(rk)}', this.checked)" class="xml-pending-item-chk rounded text-blue border-slate-300 focus:ring-blue/20 w-4 h-4 mt-1 cursor-pointer" />
            <div class="min-w-0">
              <strong class="text-sm text-navy block">${escapeHTML(property.title || 'Propiedad')}</strong>
              <span class="text-[10px] text-slate-400">${escapeHTML(rk)}</span>
              <div class="flex flex-wrap gap-1 mt-2">${fieldBadges}</div>
            </div>
          </div>
          <div class="flex flex-wrap gap-2 shrink-0">
            <button type="button" onclick="publishSinglePendingProperty('${escapeHTML(feedId)}','${escapeHTML(rk)}',this)" class="px-3 py-1.5 rounded-xl bg-green hover:bg-green-dark text-white text-[10px] font-black shadow-sm transition-all">Publicar</button>
            <button type="button" onclick="togglePendingPropertyEdit('${escapeHTML(feedId)}','${escapeHTML(rk)}',this)" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-navy text-[10px] font-bold transition-all">Editar</button>
          </div>
        </div>
        <div class="pending-edit-fields hidden mt-3 pt-3 border-t border-slate-100 space-y-2"></div>
      </div>`;
    }

    function togglePendingPropertyEdit(feedId, recordKey, button) {
      const container = button?.closest('[data-pending-key]');
      const editArea = container?.querySelector('.pending-edit-fields');
      if (!editArea) return;
      const isOpen = !editArea.classList.contains('hidden');
      if (isOpen) {
        editArea.classList.add('hidden');
        editArea.innerHTML = '';
        button.textContent = 'Editar';
        return;
      }
      const property = /* rebuild from container */ null;
      const recordDiv = container;
      const fieldsSpan = recordDiv?.querySelector('.flex.flex-wrap.gap-1');
      const fieldNames = fieldsSpan ? Array.from(fieldsSpan.querySelectorAll('span')).map(s => {
        const txt = s.textContent?.trim();
        const entry = Object.entries(PENDING_REVIEW_FIELD_LABELS).find(([,v]) => v === txt);
        return entry ? entry[0] : txt;
      }) : [];
      const defaultField = fieldNames[0] || 'title';
      editArea.classList.remove('hidden');
      editArea.innerHTML = `<div class="flex flex-col gap-2">
        <select class="pending-field-select w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white" onchange="updatePendingEditInput(this)">${Object.entries(PENDING_REVIEW_FIELD_LABELS).map(([k,v]) => `<option value="${k}"${k===defaultField?' selected':''}>${escapeHTML(v)}</option>`).join('')}</select>
        <input class="pending-field-value w-full px-3 py-2 rounded-xl border border-slate-200 text-xs" placeholder="Valor..." />
        <div class="flex gap-2">
          <button type="button" onclick="savePendingPropertyField('${escapeHTML(feedId)}','${escapeHTML(recordKey)}',this)" class="px-3 py-1.5 rounded-xl bg-blue hover:bg-blue-dark text-white text-[10px] font-black shadow-sm transition-all">Guardar</button>
          <button type="button" onclick="cancelPendingEdit(this)" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 text-[10px] font-bold transition-all">Cancelar</button>
        </div>
      </div>`;
      button.textContent = 'Cerrar';
    }

    function updatePendingEditInput(selectEl) {
      const container = selectEl?.closest('.pending-edit-fields');
      const input = container?.querySelector('.pending-field-value');
      if (!input) return;
      const field = selectEl.value;
      const hints = { title: 'Ej: Local comercial en Piles', operation: 'venta o alquiler', price: 'Ej: 220000', location: 'provincia, municipio (ej: Valencia, Piles)', owner: 'email o nombre del contacto', rooms: 'Número de habitaciones (ej: 3)', bathrooms: 'Número de baños (ej: 2)', surface: 'Superficie construida en m² (ej: 120)' };
      input.placeholder = hints[field] || 'Valor...';
    }

    async function savePendingPropertyField(feedId, recordKey, button) {
      const container = button?.closest('.pending-edit-fields');
      const select = container?.querySelector('.pending-field-select');
      const input = container?.querySelector('.pending-field-value');
      if (!select || !input || !input.value.trim()) { showToast('Escribe un valor.', 'info'); return; }
      setXmlFeedActionLoading(button, true, 'Guardando...');
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.xmlFeed + encodeURIComponent(feedId) + '/properties/' + encodeURIComponent(recordKey), {
          method: 'PATCH',
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce, 'Content-Type': 'application/json' },
          body: JSON.stringify({ field: select.value, value: input.value.trim() })
        });
        const data = await res.json();
        if (data.ok) {
          showToast('Campo actualizado.', 'success');
          if (data.status === 'active') {
            const parent = container?.closest('[data-pending-key]');
            parent?.remove();
            window.CAPTACION_PENDING_SELECTION.delete(recordKey);
            window.CAPTACION_PENDING_PROPERTIES = (window.CAPTACION_PENDING_PROPERTIES || []).filter(property => property.record_key !== recordKey);
            const remaining = document.querySelectorAll('[data-pending-key]').length;
            if (remaining === 0) {
              closeFeedPendingModal();
              await loadXmlFeeds();
              showToast('Todas las propiedades pendientes han sido completadas.', 'success');
            }
            syncPendingSelectionUi();
          } else {
            updatePropertyMissingBadges(container, data.missing_fields);
          }
          await loadXmlFeeds();
          await loadWordPressRealEstateRecords();
        } else {
          showToast(data.message || 'Error al guardar.', 'error');
        }
      } catch (e) {
        showToast('Error de red: ' + e.message, 'error');
      } finally {
        setXmlFeedActionLoading(button, false);
      }
    }

    function cancelPendingEdit(button) {
      const editArea = button?.closest('.pending-edit-fields');
      if (editArea) {
        editArea.classList.add('hidden');
        editArea.innerHTML = '';
      }
      const container = button?.closest('[data-pending-key]');
      const editBtn = container?.querySelector('button:has(>.pending-edit-fields)') || container?.querySelector('button[onclick*="togglePendingPropertyEdit"]');
      const allBtns = container?.querySelectorAll('button');
      allBtns?.forEach(btn => { if (btn.textContent === 'Cerrar' || btn.textContent === 'Editar') btn.textContent = 'Editar'; });
    }

    function updatePropertyMissingBadges(container, missingFields) {
      const badgeContainer = container?.querySelector('.flex.flex-wrap.gap-1');
      if (!badgeContainer) return;
      if (!Array.isArray(missingFields) || !missingFields.length) {
        badgeContainer.innerHTML = '<span class="inline-flex px-2 py-0.5 rounded-full bg-green-light text-green text-[9px] font-black uppercase">Completa</span>';
        return;
      }
      badgeContainer.innerHTML = missingFields.map(f => `<span class="inline-flex px-2 py-0.5 rounded-full bg-amber-light text-amber text-[9px] font-black uppercase">${escapeHTML(PENDING_REVIEW_FIELD_LABELS[f] || f)}</span>`).join(' ');
    }

    async function publishSinglePendingProperty(feedId, recordKey, button) {
      if (!confirm('¿Quieres publicar esta propiedad tal cual, aunque le falten algunos campos?')) return;
      setXmlFeedActionLoading(button, true, 'Publicando...');
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.xmlFeed + encodeURIComponent(feedId) + '/properties/' + encodeURIComponent(recordKey), {
          method: 'PATCH',
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce, 'Content-Type': 'application/json' },
          body: JSON.stringify({ field: '_publish', value: '1' })
        });
        const data = await res.json();
        if (data.ok) {
          showToast('Propiedad publicada.', 'success');
          const parent = button?.closest('[data-pending-key]');
          parent?.remove();
          window.CAPTACION_PENDING_SELECTION.delete(recordKey);
          window.CAPTACION_PENDING_PROPERTIES = (window.CAPTACION_PENDING_PROPERTIES || []).filter(property => property.record_key !== recordKey);
          const remaining = document.querySelectorAll('[data-pending-key]').length;
          syncPendingSelectionUi();
          if (remaining === 0) {
            closeFeedPendingModal();
            showToast('Todas las propiedades pendientes han sido publicadas.', 'success');
          }
          await loadXmlFeeds();
          await loadWordPressRealEstateRecords();
        } else {
          showToast(data.message || 'Error al publicar.', 'error');
        }
      } catch (e) {
        showToast('Error de red: ' + e.message, 'error');
      } finally {
        setXmlFeedActionLoading(button, false);
      }
    }

     async function publishAllPendingProperties() {
      const feedId = getFeedPendingFeedId();
      if (!feedId) { showToast('Error: feed no identificado.', 'error'); return; }

      const allKeys = Array.from(document.querySelectorAll('.xml-pending-item-chk'))
        .map(chk => chk.getAttribute('data-pending-checkbox'))
        .filter(Boolean);

      if (!allKeys.length) {
        showToast('No hay propiedades para publicar.', 'info');
        return;
      }

      if (!confirm(`¿Publicar las ${allKeys.length} propiedades pendientes tal cual? Los campos que faltan se dejarán vacíos.`)) return;

      const button = document.getElementById('xml-publish-all-btn') || document.querySelector('#xml-feed-pending-actions button:last-child');
      setXmlFeedActionLoading(button, true, 'Publicando todas...');
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.feedPublishAll + encodeURIComponent(feedId) + '/publish-all', {
          method: 'POST',
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce, 'Content-Type': 'application/json' },
          body: JSON.stringify({ keys: allKeys })
        });
        const data = await res.json();
        if (data.ok || data.success) {
          showToast(`${data.published_properties || 0} propiedades publicadas.`, 'success');
          closeFeedPendingModal();
          await loadXmlFeeds();
          await loadWordPressRealEstateRecords();
          renderMarketplace();
          renderSalesMatches();
          renderDashboard();
        } else {
          showToast(data.message || 'Error al publicar.', 'error');
        }
      } catch (e) {
        showToast('Error de red: ' + e.message, 'error');
      } finally {
        setXmlFeedActionLoading(button, false);
      }
    }

    function pendingPropertiesByCategory(category = 'all') {
      return (window.CAPTACION_PENDING_PROPERTIES || []).filter(property => {
        const cat = normalizeOpportunityCategory(property.property_type || property.type || property.payload?.property_type || property.payload?.type || 'Otros');
        return category === 'all' || cat === category;
      });
    }

    function visiblePendingCards() {
      return Array.from(document.querySelectorAll('#xml-feed-pending-body [data-pending-key]')).filter(card => !card.classList.contains('hidden'));
    }

    function togglePendingPropertySelection(recordKey, checked) {
      if (checked) window.CAPTACION_PENDING_SELECTION.add(recordKey);
      else window.CAPTACION_PENDING_SELECTION.delete(recordKey);
      syncPendingSelectionUi();
    }

    function toggleSelectAllPending(chk) {
      visiblePendingCards().forEach(card => {
        const key = card.getAttribute('data-pending-key');
        if (!key) return;
        if (chk.checked) window.CAPTACION_PENDING_SELECTION.add(key);
        else window.CAPTACION_PENDING_SELECTION.delete(key);
      });
      syncPendingSelectionUi();
    }

    function deselectAllPendingProperties() {
      window.CAPTACION_PENDING_SELECTION.clear();
      window.CAPTACION_PENDING_SELECTED_CATEGORIES.clear();
      syncPendingSelectionUi();
    }

    function toggleSelectPendingCategory(category, checked) {
      if (checked) window.CAPTACION_PENDING_SELECTED_CATEGORIES.add(category);
      else window.CAPTACION_PENDING_SELECTED_CATEGORIES.delete(category);
      window.CAPTACION_PENDING_SELECTION.clear();
      (window.CAPTACION_PENDING_PROPERTIES || []).forEach(property => {
        const cat = normalizeOpportunityCategory(property.property_type || property.type || property.payload?.property_type || property.payload?.type || 'Otros');
        const key = property.record_key;
        if (key && window.CAPTACION_PENDING_SELECTED_CATEGORIES.has(cat)) window.CAPTACION_PENDING_SELECTION.add(key);
      });
      syncPendingSelectionUi();
    }

    function updatePendingCategoryControls() {
      const counts = new Map();
      (window.CAPTACION_PENDING_PROPERTIES || []).forEach(property => {
        const cat = normalizeOpportunityCategory(property.property_type || property.type || property.payload?.property_type || property.payload?.type || 'Otros');
        counts.set(cat, (counts.get(cat) || 0) + 1);
      });
      document.querySelectorAll('[data-pending-cat-select]').forEach(input => {
        const cat = input.getAttribute('data-pending-cat-select');
        const count = counts.get(cat) || 0;
        const label = input.closest('label');
        input.disabled = count === 0;
        input.checked = window.CAPTACION_PENDING_SELECTED_CATEGORIES.has(cat);
        label?.classList.toggle('opacity-40', count === 0 || !input.checked);
        label?.classList.toggle('text-blue', input.checked);
        label?.classList.toggle('font-black', input.checked);
        const baseText = label?.dataset.baseText || label?.textContent?.trim().replace(/\s*\(\d+\)$/, '') || cat;
        if (label && !label.dataset.baseText) label.dataset.baseText = baseText;
        const textNode = Array.from(label?.childNodes || []).find(node => node.nodeType === Node.TEXT_NODE);
        if (textNode) textNode.textContent = ` ${baseText} (${count})`;
      });
    }

    function syncPendingSelectionUi() {
      document.querySelectorAll('.xml-pending-item-chk').forEach(chk => {
        const key = chk.getAttribute('data-pending-checkbox');
        chk.checked = key ? window.CAPTACION_PENDING_SELECTION.has(key) : false;
      });
      updatePendingCategoryControls();
      const visibleCards = visiblePendingCards();
      const selectedVisible = visibleCards.filter(card => window.CAPTACION_PENDING_SELECTION.has(card.getAttribute('data-pending-key'))).length;
      const selectAllChk = document.getElementById('xml-pending-select-all');
      if (selectAllChk) selectAllChk.checked = visibleCards.length > 0 && selectedVisible === visibleCards.length;
      const counter = document.getElementById('xml-feed-pending-counter');
      if (counter) counter.textContent = `${window.CAPTACION_PENDING_SELECTION.size} seleccionada(s) · ${visibleCards.length} visible(s) · ${(window.CAPTACION_PENDING_PROPERTIES || []).length} pendiente(s)`;
    }

    function filterPendingByCategory(category) {
      window.CAPTACION_PENDING_FILTER = category || 'all';
      const cards = document.querySelectorAll('#xml-feed-pending-body [data-pending-key]');
      cards.forEach(card => {
        const cardCategory = card.getAttribute('data-category') || '';
        card.classList.toggle('hidden', !(window.CAPTACION_PENDING_FILTER === 'all' || cardCategory === window.CAPTACION_PENDING_FILTER));
      });
      syncPendingSelectionUi();
    }

    async function publishSelectedPendingProperties(button) {
      const feedId = document.getElementById('xml-feed-pending-modal')?.dataset.feedId;
      if (!feedId) { showToast('Error: feed no identificado.', 'error'); return; }

      const checkedKeys = Array.from(window.CAPTACION_PENDING_SELECTION || []);

      if (checkedKeys.length === 0) {
        showToast('Selecciona al menos una propiedad para publicar.', 'info');
        return;
      }

      if (!confirm(`¿Publicar las ${checkedKeys.length} propiedades seleccionadas tal cual? Los campos que faltan se dejarán vacíos.`)) return;

      setXmlFeedActionLoading(button, true, 'Publicando seleccionadas...');
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.feedPublishAll + encodeURIComponent(feedId) + '/publish-all', {
          method: 'POST',
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce, 'Content-Type': 'application/json' },
          body: JSON.stringify({ keys: checkedKeys })
        });
        const data = await res.json();
        if (data.ok || data.success) {
          showToast(`${data.published_properties || 0} propiedades publicadas.`, 'success');

          checkedKeys.forEach(rk => {
            const card = document.querySelector(`[data-pending-key="${rk}"]`);
            card?.remove();
          });
          window.CAPTACION_PENDING_SELECTION.clear();
          window.CAPTACION_PENDING_SELECTED_CATEGORIES.clear();
          window.CAPTACION_PENDING_PROPERTIES = (window.CAPTACION_PENDING_PROPERTIES || []).filter(property => !checkedKeys.includes(property.record_key));

          const remaining = document.querySelectorAll('[data-pending-key]').length;
          document.getElementById('xml-feed-pending-counter') && (document.getElementById('xml-feed-pending-counter').textContent = `${remaining} propiedad(es) pendiente(s)`);

          syncPendingSelectionUi();

          if (remaining === 0) {
            closeFeedPendingModal();
            showToast('Todas las propiedades pendientes han sido publicadas.', 'success');
          }
          await loadXmlFeeds();
          await loadWordPressRealEstateRecords();
          renderMarketplace();
          renderSalesMatches();
          renderDashboard();
        } else {
          showToast(data.message || 'Error al publicar.', 'error');
        }
      } catch (e) {
        showToast('Error de red: ' + e.message, 'error');
      } finally {
        setXmlFeedActionLoading(button, false);
      }
    }

    function closeXmlFeedReportModal() {
      document.getElementById('xml-feed-report-modal')?.classList.add('hidden');
    }

    function isPendingWorkflowStatus(status = '') {
      const normalized = normalizeMatchText(status || 'pending');
      return !['completada','completado','cerrada','cerrado','cancelada','cancelado','done','finalizada','finalizado'].some(value => normalized.includes(value));
    }

    function getXmlFeedImpactSummary(batchId) {
      const batchProperties = properties.filter(property => String(property.importBatchId || property.import_batch_id || '') === String(batchId));
      const propertyIds = new Set(batchProperties.map(property => String(property.id)).filter(Boolean));
      const state = getPrivateDashboardState();
      const operations = (state.operations || []).filter(item => propertyIds.has(String(item.propertyId || item.related_id || '')) && isPendingWorkflowStatus(item.status));
      const requests = [...(state.requestsReceived || []), ...(state.requestsSent || [])].filter(item => propertyIds.has(String(item.propertyId || item.related_id || '')) && isPendingWorkflowStatus(item.status));
      const tasks = (state.tasks || []).filter(item => propertyIds.has(String(item.propertyId || item.related_id || item.relatedId || '')) && item.status !== 'done');
      const matches = batchProperties.reduce((sum, property) => sum + getCompatibleNeedsForProperty(property, 50).length, 0);
      return { properties: batchProperties.length, matches, operations: operations.length, requests: requests.length, tasks: tasks.length, blockers: operations.length + requests.length + tasks.length };
    }

    function xmlFeedImpactMessage(batchId, action) {
      const impact = getXmlFeedImpactSummary(batchId);
      const lines = [
        `Propiedades afectadas: ${impact.properties}`,
        `Coincidencias que dejarán de aparecer: ${impact.matches}`,
      ];
      if (impact.blockers) lines.push(`Procesos pendientes detectados: ${impact.operations} operaciones, ${impact.requests} solicitudes, ${impact.tasks} tareas.`);
      if (action === 'delete' && impact.blockers) lines.push('El XML quedará pendiente de eliminación y oculto del Marketplace hasta cerrar esos procesos.');
      if (action === 'pause') lines.push('Al pausar, estas propiedades dejarán de mostrarse en Marketplace, matches y colaboraciones hasta reactivar el XML.');
      return { impact, message: lines.join('\n') };
    }

    function showImportBatchReport(batchId) {
      const batch = (window.CAPTACION_XML_BATCHES || []).find(item => item.import_batch_id === batchId);
      if (!batch) { showToast('Informe no disponible.', 'info'); return; }
      const report = batch.report || {};
      const errors = Array.isArray(report.technical_errors) ? report.technical_errors : [];
      const modal = document.getElementById('xml-feed-report-modal');
      const title = document.getElementById('xml-feed-report-title');
      const body = document.getElementById('xml-feed-report-body');
      if (!modal || !title || !body) return;
      const sourceName = batch.source_file_name || batch.import_batch_id;
      const createdAt = batch.created_at ? new Date(batch.created_at).toLocaleString('es-ES') : 'Sin fecha';
      const updatedAt = batch.updated_at ? new Date(batch.updated_at).toLocaleString('es-ES') : 'Sin fecha';
      const imported = Number(batch.records_imported || report.imported || 0);
      const total = Number(batch.records_total || report.total || 0);
      const rejected = Number(batch.records_rejected || report.rejected || 0);
      title.textContent = `Resumen: ${sourceName}`;
      body.innerHTML = `
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
          <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200"><strong class="block text-2xl text-blue font-black">${Number(batch.properties_count || 0)}</strong><span class="text-[10px] uppercase font-black text-slate-400">Propiedades</span></div>
          <div class="p-3 rounded-2xl bg-green-light border border-green/20"><strong class="block text-2xl text-green font-black">${Number(batch.active_properties_count || 0)}</strong><span class="text-[10px] uppercase font-black text-green">Activas</span></div>
          <div class="p-3 rounded-2xl bg-amber-light border border-amber/20"><strong class="block text-2xl text-amber font-black">${Number(batch.pending_review_properties_count || 0)}</strong><span class="text-[10px] uppercase font-black text-amber">Revisión</span></div>
          <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200"><strong class="block text-2xl text-navy font-black">${Number(batch.needs_count || 0)}</strong><span class="text-[10px] uppercase font-black text-slate-400">Demandas</span></div>
        </div>
        <div class="space-y-2 text-xs bg-slate-50 border border-slate-200 rounded-2xl p-4">
          <p><strong class="text-navy">Origen:</strong> ${escapeHTML(sourceName)}</p>
          <p><strong class="text-navy">Lote:</strong> ${escapeHTML(batch.import_batch_id)}</p>
          <p><strong class="text-navy">Tipo:</strong> ${escapeHTML(batch.data_origin || 'xml')}</p>
          <p><strong class="text-navy">Estado:</strong> ${escapeHTML(batch.status || 'active')}</p>
          <p><strong class="text-navy">Creado:</strong> ${createdAt}</p>
          <p><strong class="text-navy">Última actualización:</strong> ${updatedAt}</p>
          <p><strong class="text-navy">Registros:</strong> ${imported}/${total} importados · ${rejected} con error</p>
        </div>
        ${errors.length ? `<div class="mt-4 p-4 rounded-2xl bg-red-50 border border-red-100 text-xs text-red-700"><strong class="block mb-2">Incidencias técnicas</strong>${errors.slice(0, 8).map(error => `<p>${escapeHTML(error.error || error.key || 'Error de importación')}</p>`).join('')}</div>` : '<div class="mt-4 p-4 rounded-2xl bg-green-light border border-green/20 text-xs text-green font-bold">Sin incidencias técnicas registradas.</div>'}
      `;
      modal.classList.remove('hidden');
    }

    function showPendingReviewProperties() {
      switchPrivateDashboardPanel('offers');
      navigateTo('/area-privada');
      setTimeout(() => {
        const search = document.getElementById('private-offers-search');
        if (search) search.value = 'pending_review';
        renderPrivateOffers();
      }, 50);
    }

    async function setImportBatchPublication(batchId, action, button = null) {
      const publishing = action === 'publish';
      const question = publishing
        ? 'Al publicar, las propiedades activas de este XML se mostrarán en el Marketplace y podrán participar en coincidencias. ¿Quieres continuar?'
        : 'Al retirar la publicación, las propiedades dejarán de verse de inmediato en el Marketplace y en coincidencias. El XML se conservará de forma privada. ¿Quieres continuar?';
      if (!confirm(question)) return;
      setXmlFeedActionLoading(button, true, publishing ? 'Publicando...' : 'Retirando...');
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.xmlFeed + encodeURIComponent(batchId), {
          method: 'PATCH',
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce, 'Content-Type': 'application/json' },
          body: JSON.stringify({ publication_action: action })
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'No se pudo actualizar la publicación del XML.');
        showToast(data.message || (publishing ? 'XML publicado en Marketplace.' : 'Publicación retirada.'), 'success');
        await loadXmlFeeds();
        await loadWordPressRealEstateRecords();
        renderMarketplace(); renderSalesMatches(); renderDashboard();
      } catch (e) {
        showToast(e.message || 'No se pudo actualizar la publicación del XML.', 'error');
      } finally {
        setXmlFeedActionLoading(button, false);
      }
    }

    async function updateImportBatchStatus(batchId, status, button = null) {
      if (status === 'paused') {
        const { message } = xmlFeedImpactMessage(batchId, 'pause');
        if (!confirm(`${message}\n\n¿Quieres pausar este XML?`)) return;
      }
      setXmlFeedActionLoading(button, true, status === 'paused' ? 'Pausando...' : 'Activando...');
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.xmlFeed + encodeURIComponent(batchId), {
          method: 'PATCH',
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce, 'Content-Type': 'application/json' },
          body: JSON.stringify({ status })
        });
        const data = await res.json();
        if (data.ok) {
          showToast(status === 'paused' ? 'Importación pausada.' : 'Importación activada.', 'success');
          await loadXmlFeeds();
          await loadWordPressRealEstateRecords();
          renderMarketplace();
          renderSalesMatches();
          renderDashboard();
        } else {
          showToast(data.message || 'No se pudo actualizar el XML.', 'error');
        }
      } catch (e) {
        showToast('Error de red: ' + e.message, 'error');
      } finally {
        setXmlFeedActionLoading(button, false);
      }
    }

    async function syncImportBatch(batchId, button = null) {
      setXmlFeedActionLoading(button, true, 'Actualizando...');
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.syncXmlFeed + encodeURIComponent(batchId) + '/sync', {
          method: 'POST',
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce, 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.ok) {
          showToast(`Importación actualizada: ${data.imported} propiedades.`, 'success');
          await loadXmlFeeds();
          await loadWordPressRealEstateRecords();
        } else {
          showToast(data.message || 'No se pudo actualizar el XML.', 'error');
        }
      } catch (e) {
        showToast('Error de red: ' + e.message, 'error');
      } finally {
        setXmlFeedActionLoading(button, false);
      }
    }

    async function rollbackImportBatch(batchId, button = null) {
      if (!confirm('Vas a revertir esta importación. Las propiedades creadas por este lote dejarán de mostrarse en la plataforma.\n\n¿Quieres continuar?')) return;
      setXmlFeedActionLoading(button, true, 'Revirtiendo...');
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.importBatch + encodeURIComponent(batchId) + '/rollback', {
          method: 'POST',
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce, 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'No se pudo revertir la importación.');
        showToast(`Importación revertida: ${Number(data.rolled_back || 0)} registros desactivados.`, 'success');
        await loadXmlFeeds();
        await loadWordPressRealEstateRecords();
        renderMarketplace();
        renderSalesMatches();
        renderDashboard();
      } catch (e) {
        showToast('Error de red: ' + e.message, 'error');
      } finally {
        setXmlFeedActionLoading(button, false);
      }
    }

    async function deleteImportBatch(batchId, button = null) {
      const confirmText = `⚠️ AVISO IMPORTANTE: Compra Captación NO conserva copias de seguridad (backup) de estos ficheros XML.\n\nAl eliminar este lote, todos los inmuebles importados se retirarán de la plataforma y dejarán de estar visibles en el Marketplace.\n\n🔒 Excepción de protección: Únicamente permanecerán activas y protegidas aquellas propiedades que se encuentren en curso de cierre de operación o con solicitudes de desbloqueo de datos tramitadas por otros profesionales colaboradores.\n\n¿Estás seguro de que deseas eliminar este fichero XML?`;
      if (!confirm(confirmText)) return;
      setXmlFeedActionLoading(button, true, 'Eliminando...');
      
      const cardEl = button?.closest('.xml-feed-card') || document.querySelector(`[data-xml-feed-id="${batchId}"]`);
      if (cardEl) {
        cardEl.style.opacity = '0.5';
        cardEl.style.pointerEvents = 'none';
      }

      try {
        const endpoint = (window.CAPTACION_API?.endpoints?.deleteXmlFeed) || '/api/xml_feeds.php?action=delete_batch';
        const res = await fetch(endpoint, {
          method: 'POST',
          headers: { 'X-WP-Nonce': window.CAPTACION_API?.nonce || '', 'Content-Type': 'application/json' },
          body: JSON.stringify({ batch_id: batchId })
        });
        const data = await res.json();
        if (data.ok || data.success) {
          if (cardEl) cardEl.remove();
          const remainingBatches = (window.CAPTACION_XML_BATCHES || []).filter(item => item.import_batch_id !== batchId);
          window.CAPTACION_XML_BATCHES = remainingBatches;
          renderXmlFeedsList(remainingBatches);
          showToast(data.message || 'Fichero XML eliminado correctamente.', 'success');
          await loadXmlFeeds();
          if (typeof loadWordPressRealEstateRecords === 'function') {
            try { await loadWordPressRealEstateRecords(); } catch(e){}
          }
          if (typeof renderMarketplace === 'function') renderMarketplace();
          if (typeof renderSalesMatches === 'function') renderSalesMatches();
          if (typeof renderDashboard === 'function') renderDashboard();
        } else {
          if (cardEl) {
            cardEl.style.opacity = '1';
            cardEl.style.pointerEvents = 'auto';
          }
          showToast(data.message || data?.error || 'Error al eliminar el XML.', 'error');
        }
      } catch (e) {
        if (cardEl) {
          cardEl.style.opacity = '1';
          cardEl.style.pointerEvents = 'auto';
        }
        showToast('Error de red: ' + e.message, 'error');
      } finally {
        setXmlFeedActionLoading(button, false);
      }
    }

    async function deleteAllMyXmlFeeds() {
      const confirmText = `⚠️ AVISO IMPORTANTE: Compra Captación NO conserva copias de seguridad (backup) de tus ficheros XML.\n\nAl eliminar todos tus XML, todos los inmuebles importados se retirarán permanentemente de la plataforma y del Marketplace.\n\n🔒 Excepción de protección: Solo permanecerán activas aquellas propiedades con operaciones 50/50 en curso o con solicitudes de desbloqueo de datos ya tramitadas.\n\n¿Deseas eliminar definitivamente todos tus ficheros XML?`;
      if (!confirm(confirmText)) return;
      
      const listTargets = [document.getElementById('private-import-batches-list'), document.getElementById('private-feed-import-batches-list')].filter(Boolean);
      listTargets.forEach(el => { el.innerHTML = '<p class="text-xs text-slate-400">Eliminando todos los ficheros XML...</p>'; });

      try {
        const endpoint = (window.CAPTACION_API?.endpoints?.deleteMyXmlFeeds) || '/api/xml_feeds.php?action=delete_all';
        const res = await fetch(endpoint, { 
          method: 'POST', 
          headers: { 'X-WP-Nonce': window.CAPTACION_API?.nonce || '', 'Content-Type': 'application/json' }, 
          body: JSON.stringify({ confirm: 'CONFIRMAR' }) 
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || data.error || 'No se pudieron eliminar los XML.');
        window.CAPTACION_XML_BATCHES = [];
        showToast(data.message || 'Todos los ficheros XML han sido eliminados.', 'success');
        await loadXmlFeeds(); 
        if (typeof loadWordPressRealEstateRecords === 'function') {
          try { await loadWordPressRealEstateRecords(); } catch(e){}
        }
        if (typeof renderMarketplace === 'function') renderMarketplace(); 
        if (typeof renderDashboard === 'function') renderDashboard();
      } catch (e) { 
        showToast(e.message || 'Error al eliminar los XML.', 'error'); 
        await loadXmlFeeds();
      }
    }

    window.deleteImportBatch = deleteImportBatch;
    window.deleteAllMyXmlFeeds = deleteAllMyXmlFeeds;
    window.loadXmlFeeds = loadXmlFeeds;
    window.loadImportBatches = loadImportBatches;

    async function deleteAllMyListings() {
      if (!confirm('Esta acción eliminará definitivamente todas tus captaciones y demandas publicadas directamente. Los XML se gestionan desde Importaciones y datos. ¿Quieres continuar?')) return;
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.deleteMyListings, { method:'DELETE', headers:{ 'X-WP-Nonce':window.CAPTACION_API.nonce, 'Content-Type':'application/json' }, body:JSON.stringify({confirm:'CONFIRMAR'}) });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'No se pudieron eliminar los anuncios.');
        showToast(data.message || 'Anuncios eliminados.', 'success');
        await loadWordPressRealEstateRecords(); renderMarketplace(); renderDashboard(); renderPrivateOffers(); renderPrivateDemands();
      } catch (e) { showToast(e.message || 'Error al eliminar los anuncios.', 'error'); }
    }

    async function resetMarketplaceDatabase() {
      if (!confirm('Vas a vaciar toda la base del Marketplace: anuncios, demandas, XML, solicitudes, operaciones y tareas de colaboración de todos los usuarios. Las cuentas, perfiles y configuración se conservarán. Esta acción no se puede deshacer. ¿Quieres continuar?')) return;
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.resetMarketplace, { method:'DELETE', headers:{ 'X-WP-Nonce':window.CAPTACION_API.nonce, 'Content-Type':'application/json' }, body:JSON.stringify({confirm:'LIMPIAR_BASE'}) });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'No se pudo limpiar la base.');
        properties = []; needs = [];
        showToast(data.message || 'Base de anuncios limpiada.', 'success');
        await loadXmlFeeds(); renderMarketplace(); renderDashboard(); renderPrivateOffers(); renderPrivateDemands();
      } catch (e) { showToast(e.message || 'Error al limpiar la base.', 'error'); }
    }

    async function exportMyPrivateData() {
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.exportUserXml, {
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce }
        });
        const data = await res.json();
        if (data.ok && data.xml) {
          const blob = new Blob([data.xml], { type: 'application/xml' });
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = data.filename || 'captacion-app-export.xml';
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          URL.revokeObjectURL(url);
          showToast('Exportación XML completada: ' + data.total_records + ' registros.', 'success');
        } else {
          showToast(data.message || 'Error al exportar.', 'error');
        }
      } catch (e) {
        showToast('Error de red: ' + e.message, 'error');
      }
    }

    async function exportMyPrivateDataJSON() {
      try {
        const dashboard = getPrivateDashboardState ? getPrivateDashboardState() : {};
        const profile = {
          userId: window.CAPTACION_API?.currentUserId || 0,
          exportedAt: new Date().toISOString(),
          regulation: 'GDPR (EU 2016/679) & LOPDGDD 3/2018',
          dashboard: dashboard,
          offersCount: myProperties ? myProperties.length : 0,
          demandsCount: myNeeds ? myNeeds.length : 0
        };
        const blob = new Blob([JSON.stringify(profile, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `captacion-rgpd-portabilidad-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        showToast('Portabilidad RGPD (JSON) descargada con éxito.', 'success');
      } catch (e) {
        showToast('Error al generar JSON de portabilidad: ' + e.message, 'error');
      }
    }

    async function deleteAllMyPrivateData() {
      const input = document.getElementById('private-delete-confirm-input');
      const resultDiv = document.getElementById('private-delete-result');
      const val = input ? input.value.trim().toUpperCase() : '';
      if (val !== 'ELIMINAR' && val !== 'CONFIRMAR') {
        showToast('Escribe ELIMINAR para confirmar la supresión de tus registros.', 'error');
        return;
      }
      if (!confirm('¿Estás seguro? Se suprimirán todos tus registros privados y publicaciones conforme al Art. 17 RGPD.')) return;
      if (resultDiv) {
        resultDiv.classList.remove('hidden');
        resultDiv.innerHTML = '<span class="text-red-500 font-bold">Procesando supresión de datos...</span>';
      }
      try {
        const res = await fetch(window.CAPTACION_API.endpoints.deleteMyData, {
          method: 'DELETE',
          headers: { 'X-WP-Nonce': window.CAPTACION_API.nonce, 'Content-Type': 'application/json' },
          body: JSON.stringify({ confirm: 'CONFIRMAR' })
        });
        const data = await res.json();
        if (data.ok) {
          if (resultDiv) resultDiv.innerHTML = '<span class="text-emerald-600 font-bold">Todos tus registros privados han sido suprimidos conforme al RGPD.</span>';
          await loadXmlFeeds();
          if (input) input.value = '';
          showToast('Registros suprimidos con éxito.', 'success');
        } else {
          if (resultDiv) resultDiv.innerHTML = `<span class="text-red-600 font-bold">Error: ${data.message || 'Error desconocido'}</span>`;
        }
      } catch (e) {
        if (resultDiv) resultDiv.innerHTML = `<span class="text-red-600 font-bold">Error de red: ${e.message}</span>`;
      }
    }

    // ==========================================
    // EXPORTACIÓN GLOBAL A WINDOW PARA MANEJADORES DE EVENTOS
    // ==========================================
    const safeExports = {
      openProfessionalSubscriptionModal: typeof openProfessionalSubscriptionModal === 'function' ? openProfessionalSubscriptionModal : null,
      closeProfessionalSubscriptionModal: typeof closeProfessionalSubscriptionModal === 'function' ? closeProfessionalSubscriptionModal : null,
      openProfessionalAccess: typeof openProfessionalAccess === 'function' ? openProfessionalAccess : null,
      getProfessionalAccessModal: typeof getProfessionalAccessModal === 'function' ? getProfessionalAccessModal : null,
      getProfessionalSubscriptionModal: typeof getProfessionalSubscriptionModal === 'function' ? getProfessionalSubscriptionModal : null,
      toggleProfessionalAccessMode: typeof toggleProfessionalAccessMode === 'function' ? toggleProfessionalAccessMode : null,
      closeProfessionalAccessModal: typeof closeProfessionalAccessModal === 'function' ? closeProfessionalAccessModal : null,
      toggleTheme: typeof toggleTheme === 'function' ? toggleTheme : null,
      applyTheme: typeof applyTheme === 'function' ? applyTheme : null,
      getCurrentTheme: typeof getCurrentTheme === 'function' ? getCurrentTheme : null,
      toggleMenu: typeof toggleMenu === 'function' ? toggleMenu : null,
      startIntentFlow: typeof startIntentFlow === 'function' ? startIntentFlow : null,
      applyHomeMapPostalFilter: typeof applyHomeMapPostalFilter === 'function' ? applyHomeMapPostalFilter : null,
      activateHomeAreaDraw: typeof activateHomeAreaDraw === 'function' ? activateHomeAreaDraw : null,
      clearHomeMapArea: typeof clearHomeMapArea === 'function' ? clearHomeMapArea : null,
      setHomeMapMode: typeof setHomeMapMode === 'function' ? setHomeMapMode : null,
      scrollHomeCarousel: typeof scrollHomeCarousel === 'function' ? scrollHomeCarousel : null,
      scrollToCoverageMap: typeof scrollToCoverageMap === 'function' ? scrollToCoverageMap : null,
      navigateTo: typeof navigateTo === 'function' ? navigateTo : null,
      initHomeMap: typeof initHomeMap === 'function' ? initHomeMap : null,
      filterNeeds: typeof filterNeeds === 'function' ? filterNeeds : null,
      clearAdvancedFilters: typeof clearAdvancedFilters === 'function' ? clearAdvancedFilters : null,
      setNeedsLayout: typeof setNeedsLayout === 'function' ? setNeedsLayout : null,
      refreshMarketplaceView: typeof refreshMarketplaceView === 'function' ? refreshMarketplaceView : null,
      setMarketplaceView: typeof setMarketplaceView === 'function' ? setMarketplaceView : null,
      setMarketplaceLayout: typeof setMarketplaceLayout === 'function' ? setMarketplaceLayout : null,
      sortMarketplace: typeof sortMarketplace === 'function' ? sortMarketplace : null,
      clearMarketplaceFilters: typeof clearMarketplaceFilters === 'function' ? clearMarketplaceFilters : null,
      renderSalesMatches: typeof renderSalesMatches === 'function' ? renderSalesMatches : null,
      filterResourcesByPlan: typeof filterResourcesByPlan === 'function' ? filterResourcesByPlan : null,
      filterResourcesSearch: typeof filterResourcesSearch === 'function' ? filterResourcesSearch : null,
      startResourceDownload: typeof startResourceDownload === 'function' ? startResourceDownload : null,
      openVeraSupportDraft: typeof openVeraSupportDraft === 'function' ? openVeraSupportDraft : null,
      prepareXmlSupportRequest: typeof prepareXmlSupportRequest === 'function' ? prepareXmlSupportRequest : null,
      createPrivateDossierLink: typeof createPrivateDossierLink === 'function' ? createPrivateDossierLink : null,
      copyPrivateDossierLink: typeof copyPrivateDossierLink === 'function' ? copyPrivateDossierLink : null,
      revokePrivateDossier: typeof revokePrivateDossier === 'function' ? revokePrivateDossier : null,
      openEditableLegalTemplate: typeof openEditableLegalTemplate === 'function' ? openEditableLegalTemplate : null,
      generateEditableLegalTemplate: typeof generateEditableLegalTemplate === 'function' ? generateEditableLegalTemplate : null,
      downloadEditableLegalTemplate: typeof downloadEditableLegalTemplate === 'function' ? downloadEditableLegalTemplate : null,
      openMapPropertyCard: typeof openMapPropertyCard === 'function' ? openMapPropertyCard : null,
      openMapNeedCard: typeof openMapNeedCard === 'function' ? openMapNeedCard : null,
      toggleCardDetails: typeof toggleCardDetails === 'function' ? toggleCardDetails : null,
      openAccessModal: typeof openAccessModal === 'function' ? openAccessModal : null,
      togglePasswordVisibility: typeof togglePasswordVisibility === 'function' ? togglePasswordVisibility : null,
      handleProfessionalRegistration: typeof handleProfessionalRegistration === 'function' ? handleProfessionalRegistration : null,
      handleProfessionalLogin: typeof handleProfessionalLogin === 'function' ? handleProfessionalLogin : null,
      handleAccessProfessionalRegistration: typeof handleAccessProfessionalRegistration === 'function' ? handleAccessProfessionalRegistration : null,
      logoutDemo: typeof logoutDemo === 'function' ? logoutDemo : null,
      setHomeAuthTab: typeof setHomeAuthTab === 'function' ? setHomeAuthTab : null,
      handleHomeInlineRegister: typeof handleHomeInlineRegister === 'function' ? handleHomeInlineRegister : null,
      handleHomeInlineLogin: typeof handleHomeInlineLogin === 'function' ? handleHomeInlineLogin : null,
      resetInlineRegisterForm: typeof resetInlineRegisterForm === 'function' ? resetInlineRegisterForm : null,
      // Calculadora de Honorarios 50/50 y Mapa MLS España
      updateFeeCalculator: typeof updateFeeCalculator === 'function' ? updateFeeCalculator : null,
      setCalculatorCommission: typeof setCalculatorCommission === 'function' ? setCalculatorCommission : null,
      setCalculatorPreset: typeof setCalculatorPreset === 'function' ? setCalculatorPreset : null,
      setCalculatorRole: typeof setCalculatorRole === 'function' ? setCalculatorRole : null,
      selectRegionHonorarios: typeof selectRegionHonorarios === 'function' ? selectRegionHonorarios : null,
      selectProvinceHonorarios: typeof selectProvinceHonorarios === 'function' ? selectProvinceHonorarios : null,
      showMapTooltip: typeof showMapTooltip === 'function' ? showMapTooltip : null,
      hideMapTooltip: typeof hideMapTooltip === 'function' ? hideMapTooltip : null,
      // Panel Privado y Subsecciones
      downloadBlindSheet: typeof downloadBlindSheet === 'function' ? downloadBlindSheet : null,
      triggerPageTransitionLoader: typeof triggerPageTransitionLoader === 'function' ? triggerPageTransitionLoader : null,
      switchPrivateDashboardPanel: typeof switchPrivateDashboardPanel === 'function' ? switchPrivateDashboardPanel : null,
      renderDashboard: typeof renderDashboard === 'function' ? renderDashboard : null,
      renderPrivateOffers: typeof renderPrivateOffers === 'function' ? renderPrivateOffers : null,
      renderPrivateDemands: typeof renderPrivateDemands === 'function' ? renderPrivateDemands : null,
      renderPrivateRequests: typeof renderPrivateRequests === 'function' ? renderPrivateRequests : null,
      renderPrivateOperations: typeof renderPrivateOperations === 'function' ? renderPrivateOperations : null,
      renderPrivateFavorites: typeof renderPrivateFavorites === 'function' ? renderPrivateFavorites : null,
      renderPrivateTasks: typeof renderPrivateTasks === 'function' ? renderPrivateTasks : null,
      renderPrivateCalendar: typeof renderPrivateCalendar === 'function' ? renderPrivateCalendar : null,
      renderPrivateNotifications: typeof renderPrivateNotifications === 'function' ? renderPrivateNotifications : null,
      renderCommunicationSubscriptions: typeof renderCommunicationSubscriptions === 'function' ? renderCommunicationSubscriptions : null,
      renderCommunicationThreads: typeof renderCommunicationThreads === 'function' ? renderCommunicationThreads : null,
      renderCommunicationTrace: typeof renderCommunicationTrace === 'function' ? renderCommunicationTrace : null,
      loadAndRenderCreditsLedger: typeof loadAndRenderCreditsLedger === 'function' ? loadAndRenderCreditsLedger : null,
      renderPrivateFiscalProfile: typeof renderPrivateFiscalProfile === 'function' ? renderPrivateFiscalProfile : null,
      renderAIConnections: typeof renderAIConnections === 'function' ? renderAIConnections : null,
      loadPrivateXmlUrl: typeof loadPrivateXmlUrl === 'function' ? loadPrivateXmlUrl : null,
      renderPrivateXmlFeeds: typeof renderPrivateXmlFeeds === 'function' ? renderPrivateXmlFeeds : null,
      loadXmlFeeds: typeof loadXmlFeeds === 'function' ? loadXmlFeeds : null,
      confirmPrivateRequest: typeof confirmPrivateRequest === 'function' ? confirmPrivateRequest : null,
      completePrivateTask: typeof completePrivateTask === 'function' ? completePrivateTask : null,
      openPrivateNotificationContext: typeof openPrivateNotificationContext === 'function' ? openPrivateNotificationContext : null,
      deleteAllMyListings: typeof deleteAllMyListings === 'function' ? deleteAllMyListings : null,
      editImportedProperty: typeof editImportedProperty === 'function' ? editImportedProperty : null,
      publishImportedProperty: typeof publishImportedProperty === 'function' ? publishImportedProperty : null,
      updateImportedPropertyStatus: typeof updateImportedPropertyStatus === 'function' ? updateImportedPropertyStatus : null,
      closeExecutiveDashboard: typeof closeExecutiveDashboard === 'function' ? closeExecutiveDashboard : null,
      exportExecutiveDashboard: typeof exportExecutiveDashboard === 'function' ? exportExecutiveDashboard : null,
      openNewTaskModal: typeof openNewTaskModal === 'function' ? openNewTaskModal : null,
      linkExternalCalendar: typeof linkExternalCalendar === 'function' ? linkExternalCalendar : null,
      closeSyncCalendarModal: typeof closeSyncCalendarModal === 'function' ? closeSyncCalendarModal : null,
      copyCalendarFeedUrl: typeof copyCalendarFeedUrl === 'function' ? copyCalendarFeedUrl : null,
      openGoogleCalendarSync: typeof openGoogleCalendarSync === 'function' ? openGoogleCalendarSync : null,
      openContactSupportModal: typeof openContactSupportModal === 'function' ? openContactSupportModal : null,
      closeContactSupportModal: typeof closeContactSupportModal === 'function' ? closeContactSupportModal : null,
      submitContactSupport: typeof submitContactSupport === 'function' ? submitContactSupport : null,
      openPrivateOperationModal: typeof openPrivateOperationModal === 'function' ? openPrivateOperationModal : null,
      closePrivateOperationModal: typeof closePrivateOperationModal === 'function' ? closePrivateOperationModal : null,
      exportPrivateAgendaCalendar: typeof exportPrivateAgendaCalendar === 'function' ? exportPrivateAgendaCalendar : null,
      markAllPrivateNotificationsRead: typeof markAllPrivateNotificationsRead === 'function' ? markAllPrivateNotificationsRead : null,
      // PLG & Sistema de Referidos B2B
      loadPLGReferralData: typeof loadPLGReferralData === 'function' ? loadPLGReferralData : null,
      selectPLGTemplate: typeof selectPLGTemplate === 'function' ? selectPLGTemplate : null,
      shareCurrentPLGTemplate: typeof shareCurrentPLGTemplate === 'function' ? shareCurrentPLGTemplate : null,
      copyPersonalReferralLink: typeof copyPersonalReferralLink === 'function' ? copyPersonalReferralLink : null,
      handleSendTransactionalInvite: typeof handleSendTransactionalInvite === 'function' ? handleSendTransactionalInvite : null,
      handleVerifyProfessionalLicense: typeof handleVerifyProfessionalLicense === 'function' ? handleVerifyProfessionalLicense : null,
      openSendTransactionalInviteModal: typeof openSendTransactionalInviteModal === 'function' ? openSendTransactionalInviteModal : null,
      verifyMilestoneA: typeof verifyMilestoneA === 'function' ? verifyMilestoneA : null,
      // Onboarding Interactivo Vera IA
      openVeraOnboardingModal: typeof openVeraOnboardingModal === 'function' ? openVeraOnboardingModal : null,
      closeVeraOnboardingModal: typeof closeVeraOnboardingModal === 'function' ? closeVeraOnboardingModal : null,
      openVeraDatosCiegosModal: typeof openVeraDatosCiegosModal === 'function' ? openVeraDatosCiegosModal : null,
      closeVeraDatosCiegosModal: typeof closeVeraDatosCiegosModal === 'function' ? closeVeraDatosCiegosModal : null,
      renderVeraOnboardingStep: typeof renderVeraOnboardingStep === 'function' ? renderVeraOnboardingStep : null,
      copyVeraReferralLink: typeof copyVeraReferralLink === 'function' ? copyVeraReferralLink : null,
      handleVeraImportPortalUrl: typeof handleVeraImportPortalUrl === 'function' ? handleVeraImportPortalUrl : null,
      // Gestión Individual y Agrupada de Captaciones y Demandas
      toggleSelectRecord: typeof toggleSelectRecord === 'function' ? toggleSelectRecord : null,
      toggleSelectAllRecords: typeof toggleSelectAllRecords === 'function' ? toggleSelectAllRecords : null,
      clearRecordSelection: typeof clearRecordSelection === 'function' ? clearRecordSelection : null,
      bulkDeleteRecords: typeof bulkDeleteRecords === 'function' ? bulkDeleteRecords : null,
      bulkUpdateRecordStatus: typeof bulkUpdateRecordStatus === 'function' ? bulkUpdateRecordStatus : null,
      openEditRecordModal: typeof openEditRecordModal === 'function' ? openEditRecordModal : null,
      closeEditRecordModal: typeof closeEditRecordModal === 'function' ? closeEditRecordModal : null,
      handleSaveEditRecord: typeof handleSaveEditRecord === 'function' ? handleSaveEditRecord : null,
      togglePrivateRecordStatus: typeof togglePrivateRecordStatus === 'function' ? togglePrivateRecordStatus : null,
      deletePrivateRecord: typeof deletePrivateRecord === 'function' ? deletePrivateRecord : null
    };
    Object.entries(safeExports).forEach(([key, fn]) => {
      if (fn) window[key] = fn;
    });



    // MODAL EXPLICATIVO DE DETALLES DE CRÉDITOS Y BONOS
    const CREDIT_EXPLANATIONS = {
      'saldo_disponible': {
        icon: '💎',
        color: 'text-blue bg-blue/10',
        kicker: 'Módulo de Créditos',
        title: 'Saldo Disponible',
        body: `
          <p><strong>¿Qué es el Saldo Disponible?</strong></p>
          <p>Representa los créditos actualmente activos en tu cuenta profesional que puedes utilizar de inmediato para <strong>desbloquear expedientes completos</strong> de captaciones y demandas compartidas.</p>
          <div class="p-3.5 rounded-2xl bg-blue/5 border border-blue/10 space-y-2 text-xs">
            <p>• <strong>Acceso protegido:</strong> 1 crédito reserva una colaboración durante 72 horas; los teléfonos, emails y datos sensibles solo se habilitan tras la aceptación y firma del acuerdo.</p>
            <p>• <strong>Garantía de devolución:</strong> Si la colaboración caduca, se rechaza o la captación deja de estar disponible, la reserva se libera y el crédito vuelve al saldo.</p>
            <p>• <strong>Acumulación mensual:</strong> Los créditos de tus planes de suscripción no caducan y se acumulan como bonos mes a mes.</p>
          </div>
        `
      },
      'bono_bienvenida': {
        icon: '🎁',
        color: 'text-amber bg-amber/10',
        kicker: 'Promoción de Entrada',
        title: 'Bono de Bienvenida (3 Créditos / 30 Días)',
        body: `
          <p><strong>¿En qué consiste el Bono de Bienvenida?</strong></p>
          <p>Al completar el registro de tu cuenta como profesional o agencia, Compra Captación te asigna de regalo <strong>3 créditos iniciales</strong> totalmente gratuitos y sin requerir tarjeta de crédito.</p>
          <div class="p-3.5 rounded-2xl bg-amber/5 border border-amber/10 space-y-2 text-xs">
            <p>• <strong>Vigencia de 30 días:</strong> Dispones de un periodo de 30 días naturales desde tu fecha de alta para disfrutar de ellos y desbloquear tus primeras oportunidades.</p>
            <p>• <strong>No acumulable:</strong> Los créditos de bienvenida no son acumulables y expiran a los 30 días para acelerar la activación real y dinamizar el inventario.</p>
            <p>• <strong>Sin compromiso:</strong> No genera cobros automáticos ni permanencias para que pruebes el flujo 50/50 con total tranquilidad.</p>
          </div>
        `
      },
      'consumo_historico': {
        icon: '🔓',
        color: 'text-slate-600 bg-slate-100 dark:bg-slate-800 dark:text-slate-300',
        kicker: 'Auditoría y Trazabilidad',
        title: 'Consumo Histórico',
        body: `
          <p><strong>¿Qué refleja el Consumo Histórico?</strong></p>
          <p>Muestra el cómputo total de créditos que has utilizado desde la apertura de tu cuenta profesional en Compra Captación.</p>
          <div class="p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-800 space-y-2 text-xs text-slate-700 dark:text-slate-300">
            <p>• <strong>Libro Mayor Digital:</strong> Cada crédito utilizado genera una entrada inmutable en tu historial inferior con fecha, hora, referencia del inmueble y motivo de uso.</p>
            <p>• <strong>Control de costes:</strong> Te ayuda a calcular el Retorno de Inversión (ROI) y el coste medio de captación por cada acuerdo 50/50 formalizado.</p>
          </div>
        `
      },
      'recompensa_referidos': {
        icon: '⚡',
        color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40',
        kicker: 'Tokenomics Circular & Referidos por Hitos',
        title: 'Modelo Circular (+0.5 cr) y Programa de Referidos',
        body: `
          <p><strong>Tokenomics Inmobiliario y Sistema de Crecimiento:</strong></p>
          <div class="p-3.5 rounded-2xl bg-emerald-500/5 border border-emerald-500/10 space-y-2.5 text-xs text-slate-700 dark:text-slate-300">
            <p>• <strong>🔄 Modelo de Créditos Circular:</strong> Subir ofertas (XML) es 100% gratuito. Cada vez que otra agencia paga para desbloquear tus datos ciegos, recibes <strong>+0.5 créditos automáticos</strong> en tu saldo. Tu cartera se convierte en un activo líquido que genera saldo recurrente.</p>
            <p>• <strong>⭐ Hito A (Oferta):</strong> Premio de <strong>3 créditos</strong> al referidor cuando el colega invitado sube su cartera XML (mínimo 5 exclusivas reales verificadas).</p>
            <p>• <strong>🏷️ Hito B (Monetización):</strong> <strong>50% de descuento</strong> en la suscripción mensual cuando el referido adquiere su primer paquete de saldo.</p>
            <p>• <strong>🤝 Hito C (Efecto Caballo de Troya):</strong> Integración orgánica de agencias externas mediante invitaciones transaccionales directas ligadas al cierre de una operación 50/50 real.</p>
          </div>
        `
      }
    };

    function openCreditDetailModal(type) {
      const modal = document.getElementById('credit-detail-modal');
      const data = CREDIT_EXPLANATIONS[type] || CREDIT_EXPLANATIONS['saldo_disponible'];
      if (!modal) return;
      
      const iconEl = document.getElementById('credit-modal-icon');
      const kickerEl = document.getElementById('credit-modal-kicker');
      const titleEl = document.getElementById('credit-modal-title');
      const bodyEl = document.getElementById('credit-modal-body');
      
      if (iconEl) {
        iconEl.textContent = data.icon;
        iconEl.className = 'w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shrink-0 ' + data.color;
      }
      if (kickerEl) kickerEl.textContent = data.kicker;
      if (titleEl) titleEl.textContent = data.title;
      if (bodyEl) bodyEl.innerHTML = data.body;
      
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }
    window.openCreditDetailModal = openCreditDetailModal;

    function closeCreditDetailModal() {
      const modal = document.getElementById('credit-detail-modal');
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
    }
    window.closeCreditDetailModal = closeCreditDetailModal;

    // -------------------------------------------------------------
    // PROGRESSIVE WEB APP (PWA) - SERVICE WORKER & INSTALL LOGIC
    // -------------------------------------------------------------
    let deferredPWAInstallPrompt = null;

    function isPWAInstalled() {
      return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function isIOSSafari() {
      const ua = window.navigator.userAgent;
      const isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
      const isSafari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS/.test(ua);
      return isIOS && isSafari;
    }

    function isPWABannerDismissed() {
      const dismissed = localStorage.getItem('pwa_banner_dismissed_until');
      return dismissed && Number(dismissed) > Date.now();
    }

    function showPWABanner() {
      const banner = document.getElementById('pwa-smart-banner');
      if (!banner || isPWAInstalled()) return;
      banner.classList.remove('hidden');
      requestAnimationFrame(() => {
        banner.classList.remove('translate-y-8', 'opacity-0');
        banner.classList.add('translate-y-0', 'opacity-100');
      });
    }

    function dismissPWABanner() {
      const banner = document.getElementById('pwa-smart-banner');
      if (banner) {
        banner.classList.add('translate-y-8', 'opacity-0');
        setTimeout(() => banner.classList.add('hidden'), 300);
      }
      localStorage.setItem('pwa_banner_dismissed_until', String(Date.now() + 7 * 24 * 60 * 60 * 1000));
    }
    window.dismissPWABanner = dismissPWABanner;

    function openPWAIOSModal() {
      const modal = document.getElementById('pwa-ios-modal');
      if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }
    }
    window.openPWAIOSModal = openPWAIOSModal;

    function closePWAIOSModal() {
      const modal = document.getElementById('pwa-ios-modal');
      if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
    }
    window.closePWAIOSModal = closePWAIOSModal;

    function triggerPWAInstall() {
      if (deferredPWAInstallPrompt) {
        deferredPWAInstallPrompt.prompt();
        deferredPWAInstallPrompt.userChoice.then((choiceResult) => {
          if (choiceResult && choiceResult.outcome === 'accepted') {
            dismissPWABanner();
          }
          deferredPWAInstallPrompt = null;
        });
      } else if (isIOSSafari()) {
        openPWAIOSModal();
      } else {
        showToast('Para instalar la App, usa la opción "Instalar aplicación" o "Añadir a pantalla de inicio" en el menú de tu navegador.', 'info');
      }
    }
    window.triggerPWAInstall = triggerPWAInstall;

    function initPWAService() {
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then((reg) => {
              reg.onupdatefound = () => {
                const installingWorker = reg.installing;
                if (installingWorker) {
                  installingWorker.onstatechange = () => {
                    if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                      console.log('[PWA] Nueva versión de Compra Captación disponible.');
                    }
                  };
                }
              };
            })
            .catch((err) => {
              console.warn('[PWA] Error registrando Service Worker:', err);
            });
        });
      }

      window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPWAInstallPrompt = e;

        const headerBtn = document.getElementById('pwa-install-header-btn');
        if (headerBtn) {
          headerBtn.classList.remove('hidden');
          headerBtn.classList.add('inline-flex');
        }

        if (!isPWAInstalled() && !isPWABannerDismissed()) {
          setTimeout(showPWABanner, 2500);
        }
      });

      window.addEventListener('appinstalled', () => {
        deferredPWAInstallPrompt = null;
        const banner = document.getElementById('pwa-smart-banner');
        if (banner) banner.remove();
        const headerBtn = document.getElementById('pwa-install-header-btn');
        if (headerBtn) headerBtn.classList.add('hidden');
        showToast('¡Compra Captación instalada con éxito en tu dispositivo!', 'success');
      });

      setTimeout(() => {
        if (isIOSSafari() && !isPWAInstalled() && !isPWABannerDismissed()) {
          showPWABanner();
        }
      }, 4000);
    }

    // Inicialización de PWA
    initPWAService();

