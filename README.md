# Clicks Service

A lightweight Laravel-based microservice for handling third-party click data with webhook reception, aggregated reporting, and export functionality to Finance microservice.

## Features

- **Webhook Reception**: Receive raw clicks from third-party services via webhook
- **High Volume Support**: Handle ~100-200k clicks per day with 1k RPS in peak
- **Aggregated Reports**: Generate reports with sorting and filtering capabilities
- **Finance Export**: Export click data to Finance microservice for analytics
- **Data Validation**: HMAC SHA256 signature verification for webhook security
- **Bulk Processing**: Support for bulk click ingestion
- **Docker Support**: Complete Docker containerization

## Technology Stack

- **Language**: PHP 7.4+
- **Framework**: Laravel 8.x
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Testing**: PHPUnit
- **Containerization**: Docker & Docker Compose
- **Code Standards**: PSR-12

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd clicks-service
   ```

2. **Set up environment**
   ```bash
   cp env.example .env
   # Edit .env file with your configuration
   ```

3. **Start the application**
   ```bash
   docker-compose up -d
   ```

4. **Install dependencies and run migrations**
   ```bash
   docker-compose exec app composer install
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   ```

5. **Run tests**
   ```bash
   docker-compose exec app php artisan test
   ```

The service will be available at `http://localhost:8080`

## API Endpoints

### Webhook Endpoints

#### Single Click Webhook
```http
POST /api/webhook/click
Content-Type: application/json

{
  "click_id": "abc123",
  "offer_id": 12345,
  "source": "asd_network_1",
  "timestamp": "2025-06-11T14:00:00Z",
  "signature": "hex_hmac_sha256"
}
```

#### Bulk Clicks Webhook
```http
POST /api/webhook/clicks/bulk
Content-Type: application/json

{
  "clicks": [
    {
      "click_id": "abc123",
      "offer_id": 12345,
      "source": "asd_network_1",
      "timestamp": "2025-06-11T14:00:00Z",
      "signature": "hex_hmac_sha256"
    },
    {
      "click_id": "def456",
      "offer_id": 12346,
      "source": "asd_network_2",
      "timestamp": "2025-06-11T15:00:00Z",
      "signature": "hex_hmac_sha256"
    }
  ]
}
```

### Report Endpoints

#### Aggregated Report
```http
GET /api/reports/aggregated?start_date=2024-01-15&end_date=2024-01-15&sort_by=click_count&sort_direction=desc&page=1&per_page=50
```

**Query Parameters:**
- `start_date` (required): Start date in YYYY-MM-DD format
- `end_date` (required): End date in YYYY-MM-DD format
- `offer_id` (optional): Filter by offer ID
- `source` (optional): Filter by source
- `sort_by` (optional): Sort field (click_count, offer_id, source, first_click, last_click)
- `sort_direction` (optional): Sort direction (asc, desc)
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (max 1000)

#### Statistics Summary
```http
GET /api/reports/statistics?start_date=2024-01-15&end_date=2024-01-15
```

### Export Endpoints

#### Export to Finance Service
```http
POST /api/export/forward
Content-Type: application/json

{
  "date": "2024-01-15"
}
```

#### Export Status
```http
GET /api/export/status?date=2024-01-15
```

### System Endpoints

#### Health Check
```http
GET /api/health
```

#### API Documentation
```http
GET /api/docs
```

## Configuration

### Environment Variables

```env
# Application
APP_NAME="Clicks Service"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=clicks_service
DB_USERNAME=clicks_user
DB_PASSWORD=password

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Clicks Service Configuration
WEBHOOK_SECRET_KEY=your-secret-key-here
FINANCE_SERVICE_URL=http://finance-service:8080
FINANCE_SERVICE_API_KEY=your-finance-api-key
```

### Webhook Security

The service uses HMAC SHA256 signature verification for webhook security:

1. **Generate Signature**: Create HMAC SHA256 hash of the payload (excluding signature field)
2. **Secret Key**: Use the configured `WEBHOOK_SECRET_KEY`
3. **Verification**: The service verifies incoming signatures against expected values

Example signature generation:
```php
$payload = json_encode($data, JSON_UNESCAPED_SLASHES);
$signature = hash_hmac('sha256', $payload, $secretKey);
```

## Database Schema

### Clicks Table

```sql
CREATE TABLE clicks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    click_id VARCHAR(255) UNIQUE NOT NULL,
    offer_id BIGINT UNSIGNED NOT NULL,
    source VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP NOT NULL,
    signature VARCHAR(255) NOT NULL,
    received_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_offer_timestamp (offer_id, timestamp),
    INDEX idx_source_timestamp (source, timestamp),
    INDEX idx_timestamp (timestamp),
    INDEX idx_received_at (received_at)
);
```

## Finance Microservice Integration

### Data Format

The service exports click data to the Finance microservice in the following format:

```json
{
  "date": "2024-01-15",
  "clicks": [
    {
      "click_id": "abc123",
      "offer_id": 12345,
      "source": "asd_network_1",
      "timestamp": "2024-01-15T14:00:00Z",
      "received_at": "2024-01-15T14:00:01Z",
      "created_at": "2024-01-15T14:00:01Z",
      "updated_at": "2024-01-15T14:00:01Z"
    }
  ],
  "total_count": 1,
  "exported_at": "2024-01-15T14:00:01Z",
  "source": "clicks-service"
}
```

### Finance Service Requirements

The Finance microservice should implement the following endpoint:

```http
POST /api/clicks/import
Authorization: Bearer {api_key}
Content-Type: application/json

{
  "date": "2024-01-15",
  "clicks": [...],
  "total_count": 100,
  "exported_at": "2024-01-15T14:00:01Z",
  "source": "clicks-service"
}
```

**Expected Response:**
```json
{
  "success": true,
  "imported_count": 100,
  "message": "Clicks imported successfully"
}
```

### Data Storage in Finance Service

The Finance microservice should store the received click data for analytics purposes. Recommended storage approach:

1. **Database Table**: Create a `clicks_analytics` table with similar structure
2. **Data Processing**: Process clicks for revenue attribution, conversion tracking
3. **Aggregation**: Create aggregated tables for reporting (daily, hourly summaries)
4. **Retention**: Implement data retention policies based on business requirements

Example Finance service table structure:
```sql
CREATE TABLE clicks_analytics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    click_id VARCHAR(255) NOT NULL,
    offer_id BIGINT UNSIGNED NOT NULL,
    source VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP NOT NULL,
    received_at TIMESTAMP NOT NULL,
    imported_at TIMESTAMP NOT NULL,
    revenue DECIMAL(10,2) NULL,
    conversion_status ENUM('pending', 'converted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_offer_timestamp (offer_id, timestamp),
    INDEX idx_source_timestamp (source, timestamp),
    INDEX idx_imported_at (imported_at)
);
```

## Performance Considerations

### High Volume Handling

- **Database Indexing**: Optimized indexes for common query patterns
- **Bulk Operations**: Support for bulk click ingestion
- **Connection Pooling**: Efficient database connection management
- **Caching**: Redis caching for frequently accessed data

### Scalability

- **Horizontal Scaling**: Stateless design allows multiple instances
- **Load Balancing**: Can be deployed behind load balancers
- **Database Sharding**: Can be extended to support database sharding
- **Queue Processing**: Background job processing for heavy operations

## Monitoring and Logging

### Health Checks

- **Health Endpoint**: `/api/health` for service monitoring
- **Database Connectivity**: Automatic database health checks
- **External Service Status**: Finance service availability monitoring

### Logging

- **Structured Logging**: JSON-formatted logs for easy parsing
- **Error Tracking**: Comprehensive error logging with stack traces
- **Performance Metrics**: Request timing and database query logging
- **Security Events**: Webhook signature verification failures

## Testing

### Running Tests

```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test suite
docker-compose exec app php artisan test --testsuite=Feature
docker-compose exec app php artisan test --testsuite=Unit

# Run with coverage
docker-compose exec app php artisan test --coverage
```

### Test Coverage

- **Unit Tests**: Service layer and model testing
- **Feature Tests**: API endpoint testing
- **Integration Tests**: Database and external service integration
- **Mock Testing**: External service mocking for isolated testing

## Deployment

### Docker Deployment

```bash
# Production deployment
docker-compose -f docker-compose.prod.yml up -d

# Scale services
docker-compose up -d --scale app=3
```

### Environment-Specific Configuration

- **Development**: Local development with hot reloading
- **Staging**: Production-like environment for testing
- **Production**: Optimized for performance and security

## Security

### Webhook Security

- **HMAC Verification**: All webhooks must include valid signatures
- **Rate Limiting**: Protection against abuse and DoS attacks
- **Input Validation**: Comprehensive data validation and sanitization

### Data Protection

- **Encryption**: Sensitive data encryption at rest and in transit
- **Access Control**: API key-based authentication for external services
- **Audit Logging**: Complete audit trail for all operations

## Troubleshooting

### Common Issues

1. **Webhook Signature Failures**
   - Verify `WEBHOOK_SECRET_KEY` configuration
   - Check signature generation algorithm
   - Ensure payload format matches expected structure

2. **Database Connection Issues**
   - Verify database credentials and connectivity
   - Check database server status
   - Review connection pool settings

3. **Finance Service Integration**
   - Verify `FINANCE_SERVICE_URL` and `FINANCE_SERVICE_API_KEY`
   - Check network connectivity to Finance service
   - Review Finance service logs for errors

### Logs Location

- **Application Logs**: `storage/logs/laravel.log`
- **Docker Logs**: `docker-compose logs app`
- **Database Logs**: `docker-compose logs db`

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## License

This project is licensed under the MIT License.
# test_3
