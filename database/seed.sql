-- ============================================================================
-- MEGAENSAMBLER — Datos semilla (demo)
-- Contraseña para todos los usuarios demo: Mega2026!
-- ============================================================================

INSERT INTO roles (slug, nombre, color_hex, icono) VALUES
  ('superadmin','Super Administrador','#d91e2c','fa-crown'),
  ('gerente','Gerente General','#7c6cf6','fa-chart-line'),
  ('jefe_obra','Jefe de Obra','#06b6d4','fa-helmet-safety'),
  ('cliente','Cliente','#22a35a','fa-house-user'),
  ('proveedor','Proveedor','#f59e0b','fa-truck-field'),
  ('contador','Contador','#2563eb','fa-file-invoice-dollar')
ON CONFLICT (slug) DO NOTHING;

-- Password hash corresponde a: Mega2026!
INSERT INTO users (nombre, email, password_hash, role_id, telefono, estado) VALUES
  ('Admin MegaEnsambler','admin@megaensambler.com','$2y$10$OCik4k8B9O7o0PgkFf/UVOszqhSHGUapYAVU4YyrO2AhYl0DAoXeO',(SELECT id FROM roles WHERE slug='superadmin'),'+51 999 888 777','activo'),
  ('Renzo Vidal','gerente@megaensambler.com','$2y$10$OCik4k8B9O7o0PgkFf/UVOszqhSHGUapYAVU4YyrO2AhYl0DAoXeO',(SELECT id FROM roles WHERE slug='gerente'),'+51 999 111 222','activo'),
  ('Carla Injante','jefeobra@megaensambler.com','$2y$10$OCik4k8B9O7o0PgkFf/UVOszqhSHGUapYAVU4YyrO2AhYl0DAoXeO',(SELECT id FROM roles WHERE slug='jefe_obra'),'+51 999 333 444','activo'),
  ('Diego Farfán','cliente@megaensambler.com','$2y$10$OCik4k8B9O7o0PgkFf/UVOszqhSHGUapYAVU4YyrO2AhYl0DAoXeO',(SELECT id FROM roles WHERE slug='cliente'),'+51 999 555 666','activo'),
  ('Cementos Perú S.A.','proveedor@megaensambler.com','$2y$10$OCik4k8B9O7o0PgkFf/UVOszqhSHGUapYAVU4YyrO2AhYl0DAoXeO',(SELECT id FROM roles WHERE slug='proveedor'),'+51 999 777 888','activo'),
  ('Lucía Mendoza','contador@megaensambler.com','$2y$10$OCik4k8B9O7o0PgkFf/UVOszqhSHGUapYAVU4YyrO2AhYl0DAoXeO',(SELECT id FROM roles WHERE slug='contador'),'+51 999 222 333','activo')
ON CONFLICT (email) DO NOTHING;

INSERT INTO obras (nombre, ubicacion, lat, lng, cliente_user_id, jefe_obra_user_id, monto_contratado, monto_ejecutado, costo_real, avance_pct, estado, riesgo_ia, fecha_inicio, fecha_fin_estimada)
VALUES
  ('Residencial Los Pinos','Surco, Lima',-12.1450,-77.0011,
    (SELECT id FROM users WHERE email='cliente@megaensambler.com'),
    (SELECT id FROM users WHERE email='jefeobra@megaensambler.com'),
    2450000,1592500,1242150,65,'en_tiempo','bajo','2025-11-01','2026-06-15'),
  ('Torre Corporativa Andes','Arequipa',-16.4090,-71.5375,
    NULL,
    (SELECT id FROM users WHERE email='jefeobra@megaensambler.com'),
    5800000,4756000,3899920,82,'en_tiempo','medio','2025-08-10','2026-09-30'),
  ('Condominio Terra Vista','Trujillo',-8.1116,-79.0288,
    NULL, NULL,
    3100000,1240000,1054000,40,'retrasada','alto','2025-12-01','2026-10-01'),
  ('Centro Logístico Norte','Chiclayo',-6.7714,-79.8409,
    NULL, NULL,
    4200000,3990000,2992500,95,'por_finalizar','bajo','2025-05-01','2026-08-01')
ON CONFLICT DO NOTHING;

INSERT INTO partidas (obra_id, nombre, avance_pct, orden) VALUES
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Cimentación',100,1),
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Estructura',78,2),
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Instalaciones',55,3),
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Muros y tabiquería',40,4),
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Acabados',15,5);

INSERT INTO materiales_pedidos (obra_id, material, cantidad, estado, eta) VALUES
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Cemento tipo I','120 bolsas','camino','Hoy, 4:00pm'),
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Fierro 1/2"','2.4 ton','entregado',NULL),
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Ladrillo King Kong','8,000 und','pendiente','Mañana');

INSERT INTO valorizaciones (obra_id, contratista, numero, monto, porcentaje_avance, estado) VALUES
  ((SELECT id FROM obras WHERE nombre='Torre Corporativa Andes'),'Acero Fuerte E.I.R.L.','N°07',185400,82,'pendiente'),
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Instalaciones RM S.A.C.','N°04',62300,65,'pendiente'),
  ((SELECT id FROM obras WHERE nombre='Residencial Los Pinos'),'Instalaciones RM S.A.C.','N°03',45800,50,'pagada');

INSERT INTO medallas (slug, nombre, icono_emoji, descripcion) VALUES
  ('cimiento-perfecto','Cimiento Perfecto','🏗️','Cimentación entregada sin observaciones'),
  ('cero-retrasos','0 retrasos en 3 meses','⏱️','Cumplimiento perfecto de cronograma'),
  ('cien-reportes','100 reportes fotográficos','📸','Documentación constante de obra'),
  ('precision-bim','Precisión BIM 99%','🎯','Desviaciones de replanteo mínimas')
ON CONFLICT (slug) DO NOTHING;

INSERT INTO user_medallas (user_id, medalla_id) VALUES
  ((SELECT id FROM users WHERE email='jefeobra@megaensambler.com'),(SELECT id FROM medallas WHERE slug='cimiento-perfecto')),
  ((SELECT id FROM users WHERE email='jefeobra@megaensambler.com'),(SELECT id FROM medallas WHERE slug='cero-retrasos')),
  ((SELECT id FROM users WHERE email='jefeobra@megaensambler.com'),(SELECT id FROM medallas WHERE slug='cien-reportes'))
ON CONFLICT DO NOTHING;

INSERT INTO integraciones (slug, nombre, activo, descripcion) VALUES
  ('whatsapp','WhatsApp Business API', FALSE, 'Bot automático conectado a la base de datos de cada obra'),
  ('sunat','SUNAT — Facturación electrónica', FALSE, 'Emisión de comprobantes electrónicos'),
  ('google_maps','Google Maps API', FALSE, 'Geolocalización de obras y rutas óptimas'),
  ('gemini_ai','Google AI Studio (Gemini)', TRUE, 'Motor de inteligencia predictiva y asistente de obra'),
  ('google_oauth','Google Login (OAuth)', TRUE, 'Inicio de sesión con cuenta de Gmail'),
  ('gmail_smtp','Gmail SMTP', TRUE, 'Envío de códigos de verificación y notificaciones por correo')
ON CONFLICT (slug) DO NOTHING;

INSERT INTO site_settings (key_name, value) VALUES
  ('hero_title','El sistema que usan las constructoras que facturan 50 millones al año.'),
  ('brand_color','#d91e2c'),
  ('empresa_nombre','Grupo MegaEnsambler S.A.C.')
ON CONFLICT (key_name) DO NOTHING;
