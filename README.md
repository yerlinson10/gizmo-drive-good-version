# Gizmo Drive

Drive de archivos sencillo y extensible, construido con Laravel, Inertia y React.

Permite crear carpetas, subir archivos, navegar por el contenido y compartir carpetas/archivos con otros usuarios registrados.

## Stack

| Capa | Tecnología |
| --- | --- |
| Backend | Laravel 13, PHP 8.5 |
| Frontend | React 19, Inertia 3, TypeScript, Tailwind 4, shadcn/ui |
| Base de datos | PostgreSQL 18 |
| Cache / colas | Valkey (compatible con Redis) |
| Auth | Laravel Fortify |
| Permisos | Spatie Permission |
| Entorno local | Laravel Sail (Docker) |

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (o Docker Engine + Docker Compose)
- Git

No necesitas PHP, Composer ni Node instalados en el host si usas Sail para todo el flujo.

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/Hytech-Gizmo/gizmo-drive-good-version.git
cd gizmo-drive-good-version
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

El `.env.example` ya viene preparado para Sail con PostgreSQL y Valkey. Los valores por defecto son:

| Variable | Valor |
| --- | --- |
| `APP_URL` | `http://localhost` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `pgsql` |
| `DB_DATABASE` | `laravel` |
| `DB_USERNAME` | `sail` |
| `DB_PASSWORD` | `password` |
| `CACHE_STORE` | `redis` |
| `QUEUE_CONNECTION` | `redis` |
| `REDIS_HOST` | `redis` |

### 3. Instalar dependencias PHP (primera vez)

Si aún no tienes `vendor/`, instala Composer en el host o usa un contenedor temporal:

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php85-composer:latest \
  composer install --ignore-platform-reqs
```

### 4. Levantar Sail

```bash
./vendor/bin/sail up -d
```

Esto inicia:

- **App** → `http://localhost` (puerto 80)
- **Vite** → `http://localhost:5173`
- **PostgreSQL** → puerto 5432
- **Valkey** → puerto 6379
- **Mailpit** → SMTP `1025`, UI `http://localhost:8025`

### 5. Preparar la aplicación

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

> **Nota:** Ejecuta `npm install` dentro de Sail para evitar problemas de permisos entre Windows/WSL y Linux.

## Desarrollo

### Modo desarrollo (recomendado)

En una terminal, deja Sail corriendo:

```bash
./vendor/bin/sail up
```

En otra terminal, inicia el stack de desarrollo de Laravel (servidor, cola, logs y Vite):

```bash
./vendor/bin/sail composer dev
```

O solo el frontend:

```bash
./vendor/bin/sail npm run dev
```

### Alias opcional para Sail

Agrega esto a tu `~/.bashrc` o `~/.zshrc`:

```bash
alias sail='./vendor/bin/sail'
```

Luego puedes usar `sail up`, `sail artisan migrate`, etc.

## Acceso

| URL | Descripción |
| --- | --- |
| http://localhost | Aplicación |
| http://localhost/drive | Drive (requiere login) |
| http://localhost/login | Inicio de sesión |
| http://localhost:8025 | Mailpit (correos de prueba) |

### Usuarios de prueba

Tras ejecutar `migrate --seed`:

| Email | Password | Uso |
| --- | --- | --- |
| `test@example.com` | `password` | Usuario principal |
| `colleague@example.com` | `password` | Probar compartir archivos/carpetas |

## Comandos útiles

```bash
# Contenedores
./vendor/bin/sail up -d          # Levantar en background
./vendor/bin/sail down           # Detener contenedores
./vendor/bin/sail ps             # Ver estado

# Laravel
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan tinker
./vendor/bin/sail artisan queue:work

# Frontend
./vendor/bin/sail npm run dev
./vendor/bin/sail npm run build

# Tests y calidad
./vendor/bin/sail artisan test
./vendor/bin/sail artisan test --filter=DriveTest
./vendor/bin/sail composer test
```

## Estructura relevante

```
app/
├── Http/Controllers/Drive/   # Capa HTTP (delgada)
├── Services/Drive/             # Lógica de negocio
│   ├── DriveService.php        # CRUD, browse, share
│   └── DriveAccess.php         # Autorización de acceso
├── Models/                     # Folder, DriveFile, Share
└── Policies/                   # Policies por recurso

database/migrations/
├── *_create_folders_table.php
├── *_create_files_table.php
└── *_create_shares_table.php

resources/js/pages/drive/       # UI Inertia/React
```

## Permisos (Spatie)

| Rol | Permisos |
| --- | --- |
| `user` | `drive.manage`, `drive.share` |
| `admin` | Todos los permisos |

Los usuarios registrados reciben automáticamente el rol `user`.

## Solución de problemas

### Docker no puede descargar imágenes en WSL

Si ves errores con `docker-credential-desktop.exe`, revisa `~/.docker/config.json` y elimina `credsStore` si estorba en WSL.

### Error de permisos con `node_modules`

Reinstala dependencias dentro de Sail:

```bash
rm -rf node_modules
./vendor/bin/sail npm install
```

### Puerto 80 ocupado

Cambia el puerto en `.env`:

```env
APP_PORT=8080
```

Luego actualiza `APP_URL=http://localhost:8080` y reinicia Sail.

### Resetear base de datos

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

## Licencia

MIT
