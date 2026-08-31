<?php
/**
 * Compra Captación - Capa de Base de Datos y Modelos
 * Compatible con SQLite3 y MySQL/PDO
 */

if (!defined('CAPTACION_ROOT')) {
    define('CAPTACION_ROOT', dirname(__DIR__));
}

class CaptacionDB {
    private static ?PDO $instance = null;
    private static string $dbFile = CAPTACION_ROOT . '/data/compracaptacion.sqlite';

    public static function get(): PDO {
        if (self::$instance === null) {
            $dbDir = dirname(self::$dbFile);
            if (!is_dir($dbDir)) {
                @mkdir($dbDir, 0755, true);
            }

            if (getenv('DB_HOST') && getenv('DB_NAME') && getenv('DB_USER')) {
                $dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
                self::$instance = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS') ?: '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                self::$instance = new PDO("sqlite:" . self::$dbFile, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$instance->exec("PRAGMA journal_mode = WAL;");
                self::$instance->exec("PRAGMA synchronous = NORMAL;");
                self::$instance->exec("PRAGMA foreign_keys = ON;");
            }

            self::initSchema();
        }
        return self::$instance;
    }

    private static function initSchema(): void {
        $db = self::$instance;

        // 1. Usuarios
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL DEFAULT '',
            agency_name TEXT NOT NULL DEFAULT '',
            cif_nif TEXT NOT NULL DEFAULT '',
            tax_id TEXT NOT NULL DEFAULT '',
            license_registry_type TEXT NOT NULL DEFAULT '',
            phone TEXT NOT NULL DEFAULT '',
            license_number TEXT NOT NULL DEFAULT '',
            province TEXT NOT NULL DEFAULT '',
            municipality TEXT NOT NULL DEFAULT '',
            role TEXT NOT NULL DEFAULT 'professional',
            verification_status TEXT NOT NULL DEFAULT 'approved',
            email_verified INTEGER NOT NULL DEFAULT 1,
            verification_token TEXT DEFAULT '',
            commercial_consent INTEGER NOT NULL DEFAULT 1,
            avatar_url TEXT DEFAULT '',
            onboarding_completed INTEGER NOT NULL DEFAULT 0,
            password_reset_token TEXT DEFAULT '',
            password_reset_expires_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        // Migraciones preventivas de columnas en SQLite / MySQL
        try { $db->exec("ALTER TABLE users ADD COLUMN tax_id TEXT DEFAULT ''"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN license_registry_type TEXT DEFAULT ''"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN onboarding_completed INTEGER DEFAULT 0"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN password_reset_token TEXT DEFAULT ''"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN password_reset_expires_at DATETIME NULL"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN staff_category TEXT DEFAULT ''"); } catch (Throwable $e) {}
        try { $db->exec("DELETE FROM records WHERE is_demo = 1"); } catch (Throwable $e) {}

        // 2. Registros (Propiedades y Demandas)
        $db->exec("CREATE TABLE IF NOT EXISTS records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            record_type TEXT NOT NULL,
            record_key TEXT UNIQUE NOT NULL,
            user_id INTEGER NOT NULL,
            user_email TEXT NOT NULL DEFAULT '',
            title TEXT NOT NULL,
            property_type TEXT NOT NULL DEFAULT 'piso',
            operation_type TEXT NOT NULL DEFAULT 'colaboracion_50_50',
            price REAL NOT NULL DEFAULT 0,
            commission_percentage REAL NOT NULL DEFAULT 50.0,
            commission_amount REAL NOT NULL DEFAULT 0,
            province TEXT NOT NULL DEFAULT '',
            municipality TEXT NOT NULL DEFAULT '',
            zone TEXT NOT NULL DEFAULT '',
            address_public TEXT NOT NULL DEFAULT '',
            address_private TEXT NOT NULL DEFAULT '',
            bedrooms INTEGER NOT NULL DEFAULT 0,
            bathrooms INTEGER NOT NULL DEFAULT 0,
            surface_m2 REAL NOT NULL DEFAULT 0,
            is_exclusive INTEGER NOT NULL DEFAULT 0,
            description_public TEXT NOT NULL DEFAULT '',
            description_private TEXT NOT NULL DEFAULT '',
            images_json TEXT NOT NULL DEFAULT '[]',
            documents_json TEXT NOT NULL DEFAULT '[]',
            features_json TEXT NOT NULL DEFAULT '[]',
            status TEXT NOT NULL DEFAULT 'active',
            privacy_scope TEXT NOT NULL DEFAULT 'global_public',
            data_origin TEXT NOT NULL DEFAULT 'manual',
            is_demo INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE INDEX IF NOT EXISTS idx_records_search ON records (record_type, status, province, municipality, property_type, price)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_records_user ON records (user_id)");

        // Diagnósticos profesionales aislados: nunca se publican ni entran en matching.
        $db->exec("CREATE TABLE IF NOT EXISTS captation_diagnoses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'draft',
            record_type TEXT NOT NULL DEFAULT 'property',
            payload_json TEXT NOT NULL DEFAULT '{}',
            completeness_score INTEGER NOT NULL DEFAULT 0,
            version INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_captation_diagnoses_user ON captation_diagnoses (user_id, status)");

        // 3. Monederos
        $db->exec("CREATE TABLE IF NOT EXISTS wallets (
            user_id INTEGER PRIMARY KEY,
            available_balance REAL NOT NULL DEFAULT 10.0,
            pending_balance REAL NOT NULL DEFAULT 0.0,
            reserved_balance REAL NOT NULL DEFAULT 0.0,
            consumed_balance REAL NOT NULL DEFAULT 0.0,
            total_granted REAL NOT NULL DEFAULT 0.0,
            expires_at DATETIME NULL,
            cumulative INTEGER NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Migraciones de monederos
        try { $db->exec("ALTER TABLE wallets ADD COLUMN total_granted REAL DEFAULT 0.0"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE wallets ADD COLUMN expires_at DATETIME NULL"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE wallets ADD COLUMN cumulative INTEGER DEFAULT 0"); } catch (Throwable $e) {}

        // 4. Ledger
        $db->exec("CREATE TABLE IF NOT EXISTS ledger (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            movement_type TEXT NOT NULL,
            credit_source TEXT NOT NULL DEFAULT 'purchase',
            amount REAL NOT NULL,
            balance_after REAL NOT NULL,
            related_entity_type TEXT DEFAULT '',
            related_entity_id TEXT DEFAULT '',
            metadata TEXT DEFAULT '{}',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // 5. Desbloqueos
        $db->exec("CREATE TABLE IF NOT EXISTS access_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            record_id INTEGER NOT NULL,
            consumed_credit REAL NOT NULL DEFAULT 1.0,
            referral_bonus_paid REAL NOT NULL DEFAULT 0.5,
            unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_access_logs_user_record ON access_logs (user_id, record_id)");

        // 6. Operaciones / Colaboraciones
        $db->exec("CREATE TABLE IF NOT EXISTS operations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            operation_code TEXT UNIQUE NOT NULL,
            captador_user_id INTEGER NOT NULL,
            colaborador_user_id INTEGER NOT NULL,
            record_id INTEGER NOT NULL,
            deal_amount REAL NOT NULL DEFAULT 0.0,
            commission_total REAL NOT NULL DEFAULT 0.0,
            captador_commission REAL NOT NULL DEFAULT 0.0,
            colaborador_commission REAL NOT NULL DEFAULT 0.0,
            status TEXT NOT NULL DEFAULT 'in_progress',
            contract_signed INTEGER NOT NULL DEFAULT 0,
            contract_signed_at DATETIME NULL,
            captador_signed INTEGER NOT NULL DEFAULT 0,
            colaborador_signed INTEGER NOT NULL DEFAULT 0,
            contract_hash TEXT NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (captador_user_id) REFERENCES users(id),
            FOREIGN KEY (colaborador_user_id) REFERENCES users(id),
            FOREIGN KEY (record_id) REFERENCES records(id)
        )");
        try { $db->exec("ALTER TABLE operations ADD COLUMN captador_signed INTEGER NOT NULL DEFAULT 0"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE operations ADD COLUMN colaborador_signed INTEGER NOT NULL DEFAULT 0"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE operations ADD COLUMN contract_hash TEXT NOT NULL DEFAULT ''"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE operations ADD COLUMN closed_at DATETIME NULL"); } catch (Throwable $e) {}

        // 7. Feeds XML
        $db->exec("CREATE TABLE IF NOT EXISTS import_batches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            import_batch_id TEXT UNIQUE NOT NULL,
            user_id INTEGER NOT NULL,
            data_origin TEXT DEFAULT 'xml_url',
            source_file_name TEXT DEFAULT '',
            source_url TEXT DEFAULT '',
            records_total INTEGER DEFAULT 0,
            records_imported INTEGER DEFAULT 0,
            records_updated INTEGER DEFAULT 0,
            properties_count INTEGER DEFAULT 0,
            active_properties_count INTEGER DEFAULT 0,
            pending_review_properties_count INTEGER DEFAULT 0,
            needs_count INTEGER DEFAULT 0,
            marketplace_published_properties_count INTEGER DEFAULT 0,
            privacy_scope TEXT DEFAULT 'global_public',
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 8. Soporte y Tickets
        $db->exec("CREATE TABLE IF NOT EXISTS support_tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_code TEXT UNIQUE NOT NULL,
            user_id INTEGER NOT NULL,
            user_email TEXT NOT NULL,
            user_name TEXT NOT NULL DEFAULT '',
            agency_name TEXT NOT NULL DEFAULT '',
            subject TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'general',
            priority TEXT NOT NULL DEFAULT 'medium',
            status TEXT NOT NULL DEFAULT 'open',
            resolution_notes TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        // 9. Pagos
        $db->exec("CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            stripe_session_id TEXT UNIQUE,
            stripe_payment_intent TEXT,
            amount REAL NOT NULL,
            currency TEXT NOT NULL DEFAULT 'EUR',
            status TEXT NOT NULL DEFAULT 'pending',
            credits_amount REAL NOT NULL DEFAULT 0,
            metadata_json TEXT DEFAULT '{}',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        try { $db->exec("ALTER TABLE ledger ADD COLUMN status TEXT NOT NULL DEFAULT 'completed'"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE payments ADD COLUMN credits_amount REAL NOT NULL DEFAULT 0"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE payments ADD COLUMN metadata_json TEXT DEFAULT '{}'"); } catch (Throwable $e) {}
        $db->exec("CREATE TABLE IF NOT EXISTS stripe_idempotency_keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            idempotency_key TEXT NOT NULL,
            request_hash TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, idempotency_key),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS credit_reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reservation_key TEXT UNIQUE NOT NULL,
            user_id INTEGER NOT NULL,
            record_id INTEGER NOT NULL,
            operation_id INTEGER NULL,
            credits REAL NOT NULL DEFAULT 1.0,
            status TEXT NOT NULL DEFAULT 'reserved',
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE,
            FOREIGN KEY (operation_id) REFERENCES operations(id) ON DELETE SET NULL
        )");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_credit_reservations_user_status ON credit_reservations (user_id, status)");

        // 10. Reputación profesional calculada en backend
        $db->exec("CREATE TABLE IF NOT EXISTS professional_reputation (
            user_id INTEGER PRIMARY KEY,
            score INTEGER NOT NULL DEFAULT 0,
            category TEXT NOT NULL DEFAULT 'new_professional',
            profile_complete INTEGER NOT NULL DEFAULT 0,
            completed_operations INTEGER NOT NULL DEFAULT 0,
            accepted_requests INTEGER NOT NULL DEFAULT 0,
            response_rate REAL NOT NULL DEFAULT 0,
            publication_completeness REAL NOT NULL DEFAULT 0,
            verified_reviews_count INTEGER NOT NULL DEFAULT 0,
            verified_reviews_average REAL NOT NULL DEFAULT 0,
            relevant_matches INTEGER NOT NULL DEFAULT 0,
            incidents_count INTEGER NOT NULL DEFAULT 0,
            last_activity_at DATETIME NULL,
            verification_badge INTEGER NOT NULL DEFAULT 0,
            review_status TEXT NOT NULL DEFAULT 'normal',
            calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS professional_reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reviewer_user_id INTEGER NOT NULL,
            subject_user_id INTEGER NOT NULL,
            operation_id INTEGER NOT NULL,
            score INTEGER NOT NULL,
            comment TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(reviewer_user_id, subject_user_id, operation_id),
            FOREIGN KEY (reviewer_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (operation_id) REFERENCES operations(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS record_matches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            record_id INTEGER NOT NULL,
            matched_record_id INTEGER NOT NULL,
            score INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'detected',
            idempotency_key TEXT NOT NULL UNIQUE,
            notified_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(record_id, matched_record_id),
            FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE,
            FOREIGN KEY (matched_record_id) REFERENCES records(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_record_matches_record ON record_matches (record_id, score DESC)");
        $db->exec("CREATE TABLE IF NOT EXISTS dossier_access_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            token_hash TEXT NOT NULL UNIQUE,
            operation_id INTEGER NOT NULL,
            record_id INTEGER NOT NULL,
            created_by INTEGER NOT NULL,
            expires_at DATETIME NOT NULL,
            revoked_at DATETIME NULL,
            last_accessed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (operation_id) REFERENCES operations(id) ON DELETE CASCADE,
            FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        )");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_dossier_tokens_operation ON dossier_access_tokens (operation_id, revoked_at, expires_at)");

        // Ventana de conservación temporal cuando existe un trámite activo.
        // El anuncio deja de ser público inmediatamente y solo permanece en
        // el panel privado hasta deletion_deadline_at.
        try { $db->exec("ALTER TABLE records ADD COLUMN deletion_requested_at DATETIME NULL"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE records ADD COLUMN deletion_deadline_at DATETIME NULL"); } catch (Throwable $e) {}
        // Compatibilidad con bases de datos creadas antes del esquema XML/Marketplace.
        // CREATE TABLE IF NOT EXISTS no añade columnas a tablas ya existentes.
        try { $db->exec("ALTER TABLE records ADD COLUMN privacy_scope TEXT NOT NULL DEFAULT 'global_public'"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE records ADD COLUMN data_origin TEXT NOT NULL DEFAULT 'manual'"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE records ADD COLUMN deleted_at DATETIME NULL"); } catch (Throwable $e) {}

        self::seedInitialData();
    }

    public static function seedInitialData(): void {
        $db = self::$instance;
        // 1. El Master Admin solo se inicializa cuando existe configuración segura.
        $masterAdminEmail = (string)(getenv('CAPTACION_MASTER_ADMIN_EMAIL') ?: '');
        $masterAdminPassword = (string)(getenv('CAPTACION_MASTER_ADMIN_PASSWORD') ?: '');

        if ($masterAdminEmail !== '' && $masterAdminPassword !== '') {
            $masterAdminPass = password_hash($masterAdminPassword, PASSWORD_BCRYPT);

            // Desactivar cualquier otro master_admin para garantizar singleton absoluto
            try {
                $db->prepare("UPDATE users SET staff_category = 'staff_gerente', role = 'staff' WHERE staff_category = 'master_admin' AND email != ?")
                   ->execute([$masterAdminEmail]);
            } catch (Throwable $e) {}

            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$masterAdminEmail]);
            $existingMaster = $stmt->fetch();

            if ($existingMaster) {
                $masterId = (int)$existingMaster['id'];
                $db->prepare("UPDATE users SET password_hash = ?, role = 'admin', staff_category = 'master_admin', verification_status = 'approved', email_verified = 1 WHERE id = ?")
                   ->execute([$masterAdminPass, $masterId]);
                $db->prepare("INSERT OR REPLACE INTO wallets (user_id, available_balance) VALUES (?, 999.0)")->execute([$masterId]);
            } else {
                $db->prepare("INSERT INTO users (email, password_hash, full_name, agency_name, cif_nif, phone, role, staff_category, verification_status, email_verified) VALUES (?, ?, 'Master Admin', 'Compra Captación Central HQ', 'B88992211', '+34 600 000 000', 'admin', 'master_admin', 'approved', 1)")
                   ->execute([$masterAdminEmail, $masterAdminPass]);
                $masterId = (int)$db->lastInsertId();
                $db->prepare("INSERT OR REPLACE INTO wallets (user_id, available_balance) VALUES (?, 999.0)")->execute([$masterId]);
            }
        }

        // 2. Usuario Premium opcional, solo con credenciales proporcionadas por entorno.
        $premiumEmail = (string)(getenv('CAPTACION_SEED_PREMIUM_EMAIL') ?: '');
        $premiumPassword = (string)(getenv('CAPTACION_SEED_PREMIUM_PASSWORD') ?: '');
        if ($premiumEmail !== '' && $premiumPassword !== '') {
            $premiumPass = password_hash($premiumPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$premiumEmail]);
            $existingPremium = $stmt->fetch();

        if ($existingPremium) {
            $premId = (int)$existingPremium['id'];
            $db->prepare("UPDATE users SET password_hash = ?, role = 'agency', verification_status = 'approved', email_verified = 1 WHERE id = ?")
               ->execute([$premiumPass, $premId]);
            $db->prepare("INSERT OR REPLACE INTO wallets (user_id, available_balance, total_granted, expires_at, cumulative) VALUES (?, 3.0, 3.0, datetime('now', '+30 days'), 0)")->execute([$premId]);
        } else {
            $db->prepare("INSERT INTO users (email, password_hash, full_name, agency_name, cif_nif, phone, role, verification_status, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', 1)")
               ->execute([$premiumEmail, $premiumPass, 'Moreiras JC', 'Moreiras Real Estate', 'B87654321', '+34 600 000 000', 'agency']);
            $premId = (int)$db->lastInsertId();
            $db->prepare("INSERT OR REPLACE INTO wallets (user_id, available_balance, total_granted, expires_at, cumulative) VALUES (?, 3.0, 3.0, datetime('now', '+30 days'), 0)")->execute([$premId]);
        }
        }

        // 3. Directorio de Agencias y Profesionales Registrados de la Plataforma
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $seedUsers = [
            ['antonio.garcia@levantehomes.com', 'Antonio García', 'Levante Real Estate', 'B46554433', '+34 633 445 566', 'Valencia', 'Valencia', 'agency', 25.0],
            ['elena.ruiz@costasol-realty.com', 'Elena Ruiz', 'Costa del Sol Luxury Partners', 'B29887766', '+34 644 556 677', 'Málaga', 'Marbella', 'agency', 60.0],
            ['javier.sanchez@andaluciahomes.es', 'Javier Sánchez', 'Andalucía Capital Inmuebles', 'B41223344', '+34 655 667 788', 'Sevilla', 'Sevilla', 'agency', 20.0],
            ['sofia.navarro@norteinmobiliaria.com', 'Sofía Navarro', 'Norte Inmobiliaria & Gestión', 'B48991122', '+34 666 778 899', 'Bizkaia', 'Bilbao', 'agency', 15.0],
            ['marcos.delgado@agenteindependiente.com', 'Marcos Delgado', 'Delgado Property Advisory', '47889900Z', '+34 677 889 900', 'Alicante', 'Alicante', 'professional', 12.0]
        ];

        foreach ($seedUsers as $u) {
            $stmt->execute([$u[0]]);
            $found = $stmt->fetch();
            if (!$found) {
                $seedPassword = (string)(getenv('CAPTACION_SEED_USER_PASSWORD') ?: '');
                if ($seedPassword === '') {
                    continue;
                }
                $pwdHash = password_hash($seedPassword, PASSWORD_BCRYPT);
                $db->prepare("INSERT INTO users (email, password_hash, full_name, agency_name, cif_nif, phone, province, municipality, role, verification_status, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', 1)")
                   ->execute([$u[0], $pwdHash, $u[1], $u[2], $u[3], $u[4], $u[5], $u[6], $u[7]]);
                $uid = (int)$db->lastInsertId();
                $db->prepare("INSERT OR REPLACE INTO wallets (user_id, available_balance, total_granted) VALUES (?, ?, ?)")
                   ->execute([$uid, (float)$u[8], (float)$u[8]]);
            }
        }

        // 4. Cartera Inmobiliaria Activa (Captaciones y Demandas 50/50)
        $recordsCount = (int)$db->query("SELECT COUNT(*) FROM records")->fetchColumn();
        if ($recordsCount === 0) {
            $seedRecords = [
                // Captaciones
                ['property', 'Piso señorial en Barrio Salamanca', 'piso', 850000, 50.0, 'Madrid', 'Madrid', 'Salamanca', 3, 2, 145, 1, 'Excelente vivienda exterior en finca clásica con techos altos y balcones.', 'carlos.villar@inmomadrid.es'],
                ['property', 'Ático dúplex con terraza en Eixample', 'atico', 690000, 50.0, 'Barcelona', 'Barcelona', 'Eixample', 3, 2, 128, 1, 'Terraza de 45m2 orientada al sur, recién reformado con altas calidades.', 'laura.mendez@bcnproperties.cat'],
                ['property', 'Villa contemporánea en Nueva Andalucía', 'chalet', 2150000, 50.0, 'Málaga', 'Marbella', 'Nueva Andalucía', 5, 4, 380, 1, 'Piscina infinity, vistas al golf y máxima privacidad para clientes VIP.', 'elena.ruiz@costasol-realty.com'],
                ['property', 'Piso luminoso junto a Ciudad de las Artes', 'piso', 340000, 50.0, 'Valencia', 'Valencia', 'Quatre Carreres', 3, 2, 110, 0, 'Ubicación inmejorable, garaje incluido y listo para entrar a vivir.', 'antonio.garcia@levantehomes.com'],
                ['property', 'Casa unifamiliar con jardín en Getxo', 'chalet', 920000, 50.0, 'Bizkaia', 'Getxo', 'Neguri', 4, 3, 290, 1, 'Zona residencial premium, cerca de la playa y excelentes comunicaciones.', 'sofia.navarro@norteinmobiliaria.com'],
                ['property', 'Chalet adosado en Los Remedios', 'chalet', 485000, 50.0, 'Sevilla', 'Sevilla', 'Los Remedios', 4, 3, 210, 0, 'Patio privado, garaje doble y calidades de primera.', 'javier.sanchez@andaluciahomes.es'],
                
                // Demandas
                ['need', 'Demanda: Inversor busca piso para reformar en Chamberí', 'piso', 450000, 50.0, 'Madrid', 'Madrid', 'Chamberí', 2, 1, 90, 0, 'Comprador con fondos propios disponibles inmediatamente. Sin financiación.', 'carlos.villar@inmomadrid.es'],
                ['need', 'Demanda: Familia internacional busca ático en Sarrià / Gràcia', 'atico', 750000, 50.0, 'Barcelona', 'Barcelona', 'Sarrià-Sant Gervasi', 3, 2, 120, 1, 'Requisito indispensable terraza mínima de 25m2 y ascensor.', 'laura.mendez@bcnproperties.cat'],
                ['need', 'Demanda: Cliente suizo busca villa en Marbella este', 'chalet', 1800000, 50.0, 'Málaga', 'Marbella', 'Elviria', 4, 3, 300, 1, 'Vistas al mar y seguridad 24h. Cierre rápido garantizado.', 'elena.ruiz@costasol-realty.com'],
                ['need', 'Demanda: Pareja busca piso exterior en Ruzafa o Gran Vía', 'piso', 310000, 50.0, 'Valencia', 'Valencia', 'L\'Eixample', 2, 1, 85, 0, 'Presupuesto preaprobado para firma en menos de 45 días.', 'antonio.garcia@levantehomes.com']
            ];

            foreach ($seedRecords as $r) {
                $userStmt = $db->prepare("SELECT id, email FROM users WHERE email = ?");
                $userStmt->execute([$r[13]]);
                $author = $userStmt->fetch();
                $authorId = $author ? (int)$author['id'] : $adminId;
                $authorEmail = $author ? $author['email'] : $adminEmail;

                $rKey = 'rec_' . substr(md5($r[1] . mt_rand()), 0, 16);
                $commAmt = (float)$r[3] * 0.03 * ((float)$r[4] / 100);

                $db->prepare("INSERT INTO records (
                    record_type, record_key, user_id, user_email, title, property_type, operation_type,
                    price, commission_percentage, commission_amount, province, municipality, zone,
                    bedrooms, bathrooms, surface_m2, is_exclusive, description_public, status, privacy_scope
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 'colaboracion_50_50',
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, 'active', 'global_public'
                )")->execute([
                    $r[0], $rKey, $authorId, $authorEmail, $r[1], $r[2],
                    (float)$r[3], (float)$r[4], $commAmt, $r[5], $r[6], $r[7],
                    (int)$r[8], (int)$r[9], (float)$r[10], (int)$r[11], $r[12]
                ]);
            }
        }

        // 5. Batches XML: solo gestionados por el usuario, sin re-seed automático

        // 6. Tickets de Soporte
        $ticketCount = (int)$db->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn();
        if ($ticketCount === 0) {
            $db->prepare("INSERT INTO support_tickets (ticket_code, user_id, user_email, user_name, agency_name, subject, category, priority, status) VALUES 
                ('TCK-2026-001', 2, 'carlos.villar@inmomadrid.es', 'Carlos Villar', 'InmoMadrid Prime', 'Validación de acuerdo 50/50 en Piso Barrio Salamanca', 'collaboration', 'high', 'open'),
                ('TCK-2026-002', 3, 'laura.mendez@bcnproperties.cat', 'Laura Méndez', 'BCN Exclusive Living', 'Consulta sobre feed XML pasarela Kyero v3', 'xml_feed', 'medium', 'open'),
                ('TCK-2026-003', 4, 'elena.ruiz@costasol-realty.com', 'Elena Ruiz', 'Costa del Sol Luxury Partners', 'Ampliación de saldo de créditos profesionales', 'finance', 'low', 'resolved')
            ")->execute();
        }
    }
}
