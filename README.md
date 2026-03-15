cd backend

# Dependencias principales
composer require doctrine orm doctrine/doctrine-bundle
composer require doctrine/doctrine-migrations-bundle
composer require nelmio/cors-bundle
composer require symfony/validator

# Dependencias de desarrollo
composer require --dev doctrine/doctrine-fixtures-bundle
composer require --dev symfony/maker-bundle
```

### 3. Copiar Archivos del Proyecto

Copiar los siguientes archivos a tu proyecto Symfony:
```
backend/
├── src/
│   ├── Entity/
│   │   ├── Book.php           
│   │   └── Review.php         
│   ├── Controller/
│   │   ├── BookController.php    
│   │   └── ReviewController.php  
│  

# Copiar archivo de configuración
cp .env .env.local

# Editar .env.local y configurar la base de datos
# Para MySQL:
DATABASE_URL="mysql://root:@127.0.0.1:3306/libros_db?serverVersion=8.0"

Configuracion de CORS

nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['*']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api':
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE', 'OPTIONS']
            max_age: 3600


# Crear base de datos
php bin/console doctrine:database:create

# Generar migración
php bin/console make:migration

# Ejecutar migraciones
php bin/console doctrine:migrations:migrate

run server 
php -S localhost:8000 -t public