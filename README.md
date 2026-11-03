# Laravel Ecommerce API

REST API para ecommerce con autenticación JWT, test y seeders.

## Requisitos
- PHP 8.2+
- PostgreSQL
- Composer

## Instalación (bash)
- git clone https://github.com/ericdevng/laravel-ecommerce-api.git
- cd laravel-ecommerce-api
- composer setup

## Base de datos (bash)
- php artisan migrate
- php artisan db:seed

## Usuario admin por defecto:
- Email: `admin@example.com`
- Password: `password`

## Servidor (bash)
- composer dev

# Test
- composer test

## Endpoints

### Autenticación

- POST /api/register
- POST /api/login
- POST /api/logout

### Categorias

- GET /api/categories
- GET /api/categories/{id}

### Productos

- GET /api/products
- GET /api/products/{id}

**Filtros**

- search - Buscar por nombre y descripción
- category - filtrar por ID de categoria
- min_price / max_price - rango de precios
- sort_by - price o name
- sort_dir - asc / desc

### Carrito

- GET /api/cart
- POST /api/cart/items
- PATCH /api/cart/items/{item}
- DELETE /api/cart/items/{item}

### Ordenes

- POST /api/orders
- GET /api/orders
- GET /api/orders/{id}

### Arquitectura y componentes

- **Service Layer** - logica en services y los controladores delegan
- **Form Request** - validación en app/Http/Request/
- **API Resources** - serialización en app/Http/Resources/
- **Transacciones y locks** - DB::transaction + lockForUpdate que previene race conditions y duplicado de ordenes
- **Caché** - Productos y categorias con TTL de 60 segundos. Invalidación automatica tras la compra
- **Manejo de errores** - Excepciones globales con JSON consistente

## Docker

La imagen se construye en varias etapas (dependencias PHP, assets con Vite y PHP 8.3 + Apache) y usa PostgreSQL como base de datos.

### 1. Preparar variables de entorno

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Asegúrate de definir `DB_PASSWORD` en `.env`. `docker-compose.yml` usa ese valor para PostgreSQL y para la aplicación.

### 2. Levantar la aplicación

```bash
docker compose up -d db
docker compose --profile tools run --rm migrate
docker compose up -d app queue
```

La API queda disponible en `http://localhost:8080` y el health check en `http://localhost:8080/up`.

Para cargar los seeders:

```bash
docker compose run --rm app php artisan db:seed --force
```

### 3. Comandos útiles

```bash
docker compose logs -f app
docker compose exec app php artisan tinker
docker compose down
docker compose down -v   # elimina también la base de datos y el storage
```

### 4. Imagen publicada por GitHub Actions

El workflow `.github/workflows/ci.yml` ejecuta los tests, construye la imagen, hace un smoke test y la publica en GitHub Container Registry al hacer push a `main` o al crear un tag `v*.*.*`:

```text
ghcr.io/<owner>/<repo>:main
ghcr.io/<owner>/<repo>:latest
ghcr.io/<owner>/<repo>:v1.0.0
```

Para descargarla:

```bash
docker pull ghcr.io/<owner>/<repo>:main
```



