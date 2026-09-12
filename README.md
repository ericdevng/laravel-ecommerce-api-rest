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

**Service Layer** - logica en services y los controladores delegan
**Form Request** - validación en app/Http/Request/
**API Resources** - serialización en app/Http/Resources/
**Transacciones y locks** - DB::transaction + lockForUpdate que previene race conditions y duplicado de ordenes
**Caché** - Productos y categorias con TTL de 60 segundos. Invalidación automatica tras la compra
**Manejo de errores** - Excepciones globales con JSON consistente



