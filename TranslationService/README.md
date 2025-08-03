# Translation Management Service

A high-performance Laravel 11 API for managing translations across multiple locales with advanced search, filtering, and export capabilities.

## 🎯 Features

- **Multi-locale Support**: Store translations for multiple languages (`en`, `fr`, `es`, `de`, `it`, `pt`, `ru`, `ja`, `ko`, `zh`)
- **Tagging System**: Organize translations by context (`mobile`, `desktop`, `web`, `admin`, etc.)
- **Full CRUD Operations**: Create, read, update, delete translations with validation
- **Advanced Search**: Search by keys, content, tags, locale, and namespace
- **High-Performance Export**: JSON export for frontend apps with <500ms response time
- **Token Authentication**: Secure API with Laravel Sanctum
- **Caching**: Redis-based caching for optimal performance
- **Docker Support**: Complete containerized development environment
- **Comprehensive Testing**: >95% test coverage with performance tests

## 🚀 Performance Targets

- **Export Endpoint**: <500ms for 100k+ records
- **All Other Endpoints**: <200ms response time
- **Caching**: Redis-based caching for frequently accessed data
- **Database Optimization**: Proper indexing and query optimization

## 📋 Requirements

- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+
- Composer
- Docker (optional)

## 🛠️ Installation

### Using Docker (Recommended)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd TranslationService
   ```

2. **Start the Docker containers**
   ```bash
   docker-compose up -d
   ```

3. **Install dependencies**
   ```bash
   docker-compose exec app composer install
   ```

4. **Set up environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

5. **Generate application key**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

6. **Run migrations**
   ```bash
   docker-compose exec app php artisan migrate
   ```

7. **Generate test data (optional)**
   ```bash
   docker-compose exec app php artisan translations:generate 100000
   ```

### Manual Installation

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Set up environment**
   ```bash
   cp .env.example .env
   # Configure database and Redis settings
   ```

3. **Generate application key**
   ```bash
   php artisan key:generate
   ```

4. **Run migrations**
   ```bash
   php artisan migrate
   ```

5. **Start the server**
   ```bash
   php artisan serve
   ```

## 📚 API Documentation

### Authentication

All protected endpoints require a Bearer token obtained through authentication.

#### Register User
```http
POST /api/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

### Translation Endpoints

#### List Translations
```http
GET /api/translations?locale=en&namespace=general&search=welcome&tags=mobile,web&per_page=15
Authorization: Bearer {token}
```

#### Create Translation
```http
POST /api/translations
Authorization: Bearer {token}
Content-Type: application/json

{
    "key": "welcome.message",
    "locale": "en",
    "content": "Welcome to our application!",
    "namespace": "general",
    "is_active": true,
    "tags": ["mobile", "web"],
    "metadata": {
        "author": "John Doe",
        "version": "1.0"
    }
}
```

#### Update Translation
```http
PUT /api/translations/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "Updated welcome message",
    "is_active": false
}
```

#### Delete Translation
```http
DELETE /api/translations/{id}
Authorization: Bearer {token}
```

#### Export Translations
```http
GET /api/translations/export?locale=en&namespace=general&tags[]=mobile&tags[]=web
Authorization: Bearer {token}
```

Response:
```json
{
    "locale": "en",
    "namespace": "general",
    "translations": {
        "welcome.message": "Welcome to our application!",
        "button.save": "Save",
        "button.cancel": "Cancel"
    },
    "count": 3,
    "exported_at": "2024-01-15T10:30:00.000000Z"
}
```

### Tag Endpoints

#### List Tags
```http
GET /api/tags
Authorization: Bearer {token}
```

## 🧪 Testing

### Run All Tests
```bash
php artisan test
```

### Run Performance Tests
```bash
php artisan test --filter=PerformanceTest
```

### Generate Test Data
```bash
# Generate 100,000 translations for performance testing
php artisan translations:generate 100000

# Generate with custom batch size
php artisan translations:generate 50000 --batch=500
```

## 🏗️ Architecture

### Database Schema

- **translations**: Core translation data with optimized indexes
- **translation_tags**: Tag definitions
- **translation_tag_pivot**: Many-to-many relationship table

### Key Design Decisions

1. **Caching Strategy**: Redis-based caching for export endpoints with 1-hour TTL
2. **Database Optimization**: Composite indexes on frequently queried columns
3. **Batch Processing**: Efficient data generation with configurable batch sizes
4. **API Design**: RESTful endpoints with consistent JSON responses
5. **Security**: Token-based authentication with Laravel Sanctum

### Performance Optimizations

- **Indexing**: Strategic database indexes for fast queries
- **Caching**: Redis caching for expensive operations
- **Pagination**: Efficient pagination for large datasets
- **Query Optimization**: Optimized Eloquent queries with eager loading
- **Batch Operations**: Efficient bulk operations for data generation

## 🔧 Configuration

### Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=translation_service
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache
CACHE_DRIVER=redis

# Translation Service Settings
TRANSLATION_CACHE_TTL=3600
TRANSLATION_EXPORT_LIMIT=100000
TRANSLATION_PERFORMANCE_THRESHOLD=500
```

## 🐳 Docker Configuration

The project includes a complete Docker setup with:

- **PHP 8.2-FPM**: Application container
- **Nginx**: Web server
- **MySQL 8.0**: Database
- **Redis**: Caching and session storage

### Docker Commands

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Execute commands in app container
docker-compose exec app php artisan migrate

# Stop all services
docker-compose down
```

## 📊 Monitoring & Performance

### Performance Metrics

- Export endpoint response time: <500ms
- List/search endpoint response time: <200ms
- Database query optimization
- Cache hit rates

### Monitoring

- Application logs in `storage/logs/`
- Database query logs
- Performance test results

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🆘 Support

For support and questions:

1. Check the API documentation above
2. Review the test files for usage examples
3. Run performance tests to verify your setup
4. Check the logs for error details

---

**Note**: This is a demonstration project for senior-level Laravel development. It showcases clean architecture, performance optimization, comprehensive testing, and production-ready practices.
