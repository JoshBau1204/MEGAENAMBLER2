-- ============================================================================
-- MEGAENSAMBLER — Esquema PostgreSQL
-- Grupo MegaEnsambler S.A.C. · BIM Coordination
-- ============================================================================

CREATE TABLE IF NOT EXISTS roles (
  id SERIAL PRIMARY KEY,
  slug VARCHAR(30) UNIQUE NOT NULL,
  nombre VARCHAR(60) NOT NULL,
  color_hex VARCHAR(9) NOT NULL DEFAULT '#7c6cf6',
  icono VARCHAR(40) NOT NULL DEFAULT 'fa-user'
);

CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(160) UNIQUE NOT NULL,
  password_hash VARCHAR(255),
  google_id VARCHAR(60) UNIQUE,
  avatar_url TEXT,
  telefono VARCHAR(30),
  role_id INTEGER NOT NULL REFERENCES roles(id),
  estado VARCHAR(20) NOT NULL DEFAULT 'activo', -- activo | inactivo
  two_factor_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  last_login_at TIMESTAMPTZ
);

CREATE TABLE IF NOT EXISTS two_factor_codes (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  code VARCHAR(6) NOT NULL,
  expires_at TIMESTAMPTZ NOT NULL,
  used BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS password_resets (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token VARCHAR(80) UNIQUE NOT NULL,
  expires_at TIMESTAMPTZ NOT NULL,
  used BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS oauth_states (
  id SERIAL PRIMARY KEY,
  state_token VARCHAR(80) UNIQUE NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS obras (
  id SERIAL PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  ubicacion VARCHAR(150),
  lat NUMERIC(10,6),
  lng NUMERIC(10,6),
  cliente_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  jefe_obra_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  imagen_url TEXT,
  monto_contratado NUMERIC(14,2) NOT NULL DEFAULT 0,
  monto_ejecutado NUMERIC(14,2) NOT NULL DEFAULT 0,
  costo_real NUMERIC(14,2) NOT NULL DEFAULT 0,
  avance_pct NUMERIC(5,2) NOT NULL DEFAULT 0,
  estado VARCHAR(30) NOT NULL DEFAULT 'en_tiempo', -- en_tiempo | retrasada | por_finalizar | completada
  riesgo_ia VARCHAR(20) NOT NULL DEFAULT 'bajo',   -- bajo | medio | alto
  riesgo_ia_analisis TEXT,
  riesgo_ia_actualizado_at TIMESTAMPTZ,
  fecha_inicio DATE,
  fecha_fin_estimada DATE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS partidas (
  id SERIAL PRIMARY KEY,
  obra_id INTEGER NOT NULL REFERENCES obras(id) ON DELETE CASCADE,
  nombre VARCHAR(120) NOT NULL,
  avance_pct NUMERIC(5,2) NOT NULL DEFAULT 0,
  orden INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS reportes_avance (
  id SERIAL PRIMARY KEY,
  obra_id INTEGER NOT NULL REFERENCES obras(id) ON DELETE CASCADE,
  partida_id INTEGER REFERENCES partidas(id) ON DELETE SET NULL,
  user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  porcentaje NUMERIC(5,2) NOT NULL,
  comentario TEXT,
  foto_url TEXT,
  origen VARCHAR(20) NOT NULL DEFAULT 'manual', -- manual | voz | qr
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS materiales_pedidos (
  id SERIAL PRIMARY KEY,
  obra_id INTEGER NOT NULL REFERENCES obras(id) ON DELETE CASCADE,
  proveedor_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  material VARCHAR(150) NOT NULL,
  cantidad VARCHAR(60) NOT NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'pendiente', -- pendiente | preparacion | camino | entregado
  eta VARCHAR(60),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS valorizaciones (
  id SERIAL PRIMARY KEY,
  obra_id INTEGER NOT NULL REFERENCES obras(id) ON DELETE CASCADE,
  contratista VARCHAR(150) NOT NULL,
  numero VARCHAR(30) NOT NULL,
  monto NUMERIC(14,2) NOT NULL,
  porcentaje_avance NUMERIC(5,2),
  estado VARCHAR(30) NOT NULL DEFAULT 'pendiente', -- pendiente | aprobada | rechazada | pagada
  aprobado_por INTEGER REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS comprobantes (
  id SERIAL PRIMARY KEY,
  obra_id INTEGER REFERENCES obras(id) ON DELETE SET NULL,
  tipo VARCHAR(20) NOT NULL DEFAULT 'factura', -- factura | boleta | nota_credito
  serie_numero VARCHAR(30) NOT NULL,
  cliente_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  proveedor_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  monto NUMERIC(14,2) NOT NULL,
  estado_sunat VARCHAR(20) NOT NULL DEFAULT 'enviado', -- enviado | aceptado | rechazado
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS medallas (
  id SERIAL PRIMARY KEY,
  slug VARCHAR(60) UNIQUE NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  icono_emoji VARCHAR(10) NOT NULL DEFAULT '🏅',
  descripcion VARCHAR(200)
);

CREATE TABLE IF NOT EXISTS user_medallas (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  medalla_id INTEGER NOT NULL REFERENCES medallas(id) ON DELETE CASCADE,
  obtenida_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE(user_id, medalla_id)
);

CREATE TABLE IF NOT EXISTS auditoria (
  id SERIAL PRIMARY KEY,
  user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  accion VARCHAR(200) NOT NULL,
  modulo VARCHAR(60) NOT NULL,
  ip VARCHAR(50),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS integraciones (
  id SERIAL PRIMARY KEY,
  slug VARCHAR(60) UNIQUE NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  activo BOOLEAN NOT NULL DEFAULT FALSE,
  descripcion VARCHAR(200)
);

CREATE TABLE IF NOT EXISTS site_settings (
  key_name VARCHAR(80) PRIMARY KEY,
  value TEXT
);

CREATE TABLE IF NOT EXISTS chat_mensajes (
  id SERIAL PRIMARY KEY,
  obra_id INTEGER NOT NULL REFERENCES obras(id) ON DELETE CASCADE,
  user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  remitente VARCHAR(20) NOT NULL, -- cliente | bot
  mensaje TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS notificaciones (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  tipo VARCHAR(40) NOT NULL DEFAULT 'info',
  icono VARCHAR(40) NOT NULL DEFAULT 'fa-bell',
  titulo VARCHAR(160) NOT NULL,
  mensaje VARCHAR(300),
  link VARCHAR(200),
  leida BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_notif_user ON notificaciones(user_id, leida, created_at DESC);

CREATE TABLE IF NOT EXISTS leads (
  id SERIAL PRIMARY KEY,
  email VARCHAR(160) NOT NULL,
  origen VARCHAR(40) NOT NULL DEFAULT 'landing',
  atendido BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_reportes_obra ON reportes_avance(obra_id);
CREATE INDEX IF NOT EXISTS idx_materiales_obra ON materiales_pedidos(obra_id);
CREATE INDEX IF NOT EXISTS idx_valorizaciones_obra ON valorizaciones(obra_id);
CREATE INDEX IF NOT EXISTS idx_auditoria_created ON auditoria(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_chat_obra ON chat_mensajes(obra_id);
