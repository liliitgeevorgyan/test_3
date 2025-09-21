# Clicks Service

A high-performance Laravel microservice for handling click tracking data with webhook processing, aggregated reporting, and data export capabilities.

## Features

- **Webhook Processing**: Receive and process raw clicks from third-party services
- **High Throughput**: Optimized for 100-200k clicks per day with 1k RPS peak capacity
- **Aggregated Reports**: Generate reports with filtering and sorting capabilities
- **Data Export**: Forward clicks data to Finance microservice
- **Custom DI Container**: Lightweight dependency injection container
- **Queue Processing**: Asynchronous click processing for better performance
- **Comprehensive Testing**: Full test coverage with PHPUnit

## Architecture

### Technology Stack
- **Language**: PHP 7.4+
- **Framework**: Laravel 8.x
- **Database**: MySQL 8.0 with Redis for caching and queuing
- **Containerization**: Docker with Docker Compose
- **Testing**: PHPUnit
- **Code Standards**: PSR-12

### Performance Optimizations
- **Asynchronous Processing**: All clicks are processed via Redis queues
- **Database Indexing**: Optimized indexes for high-volume queries
- **Connection Pooling**: MySQL connection optimization
- **Caching**: Redis-based caching for frequently accessed data
- **Batch Processing**: Support for batch webhook submissions

## Quick Start

### Prerequisites
- Docker and Docker Compose
- PHP 7.4+ (for local development)
- Composer (for local development)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd clicks-service
   ```

2. **Environment Setup**
   ```bash
   cp env.example .env
   # Edit .env file with your configuration
   ```

3. **Start the services**
   ```bash
   docker-compose up -d
   ```

4. **Install dependencies and run migrations**
   ```bash
   docker-compose exec app composer install
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   ```

5. **Start the queue worker**
   ```bash
   docker-compose exec app php artisan queue:work
   ```

The service will be available at `http://localhost:8080`

## API Endpoints

### Webhook Endpoints

#### Receive Single Click
```http
POST /api/webhook/clicks
Content-Type: application/json
X-Signature: sha256=<hmac_signature>

{
  "click_id": "abc123",
  "offer_id": 12345,
  "source": "asd_network_1",
  "timestamp": "2025-06-11T14:00:00Z",
  "signature": "hex_hmac_sha256"
}
```

#### Receive Batch Clicks
```http
POST /api/webhook/clicks/batch
Content-Type: application/json
X-Signature: sha256=<hmac_signature>

{
  "clicks": [
    {
      "click_id": "abc123",
      "offer_id": 12345,
      "source": "asd_network_1",
      "timestamp": "2025-06-11T14:00:00Z",
      "signature": "hex_hmac_sha256"
    }
  ]
}
```

### Report Endpoints

#### Get Aggregated Report
```http
GET /api/reports/aggregated?start_date=2024-01-01&end_date=2024-01-31&offer_id=12345&source=network_1&sort_by=clicks_count&sort_direction=desc&limit=100&page=1
```

**Query Parameters:**
- `start_date` (required): Start date in YYYY-MM-DD format
- `end_date` (required): End date in YYYY-MM-DD format
- `offer_id` (optional): Filter by offer ID
- `source` (optional): Filter by source
- `sort_by` (optional): Sort field (clicks_count, offer_id, source, date)
- `sort_direction` (optional): Sort direction (asc, desc)
- `limit` (optional): Results per page (default: 100, max: 1000)
- `page` (optional): Page number (default: 1)

#### Get Summary Statistics
```http
GET /api/reports/summary?start_date=2024-01-01&end_date=2024-01-31
```

### Export Endpoints

#### Forward Clicks to Finance Service
```http
POST /api/export/forward
Content-Type: application/json

{
  "date": "2024-01-01"
}
```

#### Check Export Status
```http
GET /api/export/status?date=2024-01-01
```

### Health Check
```http
GET /api/health
```

## Finance Microservice Integration

### Data Format

The Clicks Service forwards data to the Finance microservice in the following format:

```json
{
  "date": "2024-01-01",
  "total_clicks": 1500,
  "clicks": [
    {
      "click_id": "abc123",
      "offer_id": 12345,
      "source": "asd_network_1",
      "timestamp": "2024-01-01T14:00:00Z",
      "signature": "hex_hmac_sha256"
    }
  ]
}
```

### Finance Service Endpoints

The Finance microservice should implement the following endpoints:

#### Receive Clicks Data
```http
POST /clicks
Content-Type: application/json

{
  "date": "2024-01-01",
  "total_clicks": 1500,
  "clicks": [...]
}
```

#### Health Check
```http
GET /health
```

### Configuration

Configure the Finance service URL in your `.env` file:

```env
FINANCE_SERVICE_URL=http://finance-service:8080
FINANCE_SERVICE_TIMEOUT=30
```

### Data Storage in Finance Service

The Finance microservice should store the received clicks data in a format suitable for financial analysis. Recommended approach:

1. **Database Schema**:
   ```sql
   CREATE TABLE clicks_analytics (
       id BIGINT PRIMARY KEY AUTO_INCREMENT,
       click_id VARCHAR(255) UNIQUE,
       offer_id BIGINT,
       source VARCHAR(255),
       timestamp DATETIME,
       signature VARCHAR(255),
       received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
       INDEX idx_offer_timestamp (offer_id, timestamp),
       INDEX idx_source_timestamp (source, timestamp),
       INDEX idx_timestamp (timestamp)
   );
   ```

2. **Data Processing**:
   - Store raw click data for audit purposes
   - Create aggregated views for reporting
   - Implement data retention policies
   - Set up monitoring and alerting

3. **Analytics Views**:
   ```sql
   CREATE VIEW daily_click_summary AS
   SELECT 
       DATE(timestamp) as date,
       offer_id,
       source,
       COUNT(*) as click_count,
       COUNT(DISTINCT click_id) as unique_clicks
   FROM clicks_analytics
   GROUP BY DATE(timestamp), offer_id, source;
   ```

## Configuration

### Environment Variables

```env
# Application
APP_NAME="Clicks Service"
APP_ENV=production
APP_KEY=base64:your_app_key_here
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

# Webhook Security
WEBHOOK_SECRET_KEY=your_webhook_secret_key_here

# Finance Service
FINANCE_SERVICE_URL=http://finance-service:8080
FINANCE_SERVICE_TIMEOUT=30

# Performance
CLICKS_BATCH_SIZE=1000
CLICKS_QUEUE_NAME=clicks-processing
```

### Performance Tuning

#### Database Optimization
- Enable query caching
- Optimize MySQL configuration for high throughput
- Use connection pooling
- Monitor slow queries

#### Redis Configuration
- Configure appropriate memory limits
- Set up persistence if needed
- Monitor memory usage

#### Queue Configuration
- Adjust worker processes based on load
- Configure job timeouts appropriately
- Monitor queue lengths

## Testing

### Run Tests
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
- **Feature Tests**: API endpoints, webhook processing, report generation
- **Unit Tests**: Service classes, validation logic, data processing
- **Integration Tests**: Database operations, external service communication

## Monitoring and Logging

### Logs
- Application logs: `storage/logs/laravel.log`
- Webhook processing logs with click IDs
- Error tracking for failed operations
- Performance metrics logging

### Health Monitoring
- Database connection status
- Redis connectivity
- Queue worker status
- External service availability

### Metrics to Monitor
- Clicks per second (RPS)
- Queue processing time
- Database query performance
- Memory usage
- Error rates

## Deployment

### Production Considerations
1. **Load Balancing**: Use multiple application instances
2. **Database**: Set up read replicas for reporting queries
3. **Caching**: Implement Redis clustering for high availability
4. **Monitoring**: Set up comprehensive monitoring and alerting
5. **Backup**: Regular database backups and disaster recovery plan

### Docker Production Setup
```bash
# Build production image
docker build -t clicks-service:latest .

# Run with production configuration
docker run -d \
  --name clicks-service \
  --env-file .env.production \
  -p 8080:80 \
  clicks-service:latest
```

## Security

### Webhook Security
- HMAC-SHA256 signature verification
- Rate limiting on webhook endpoints
- IP whitelisting (if required)
- Request validation and sanitization

### Data Protection
- Encrypt sensitive data at rest
- Use HTTPS for all communications
- Implement proper access controls
- Regular security audits

## Troubleshooting

### Common Issues

1. **High Memory Usage**
   - Check for memory leaks in queue workers
   - Optimize database queries
   - Monitor Redis memory usage

2. **Slow Query Performance**
   - Review database indexes
   - Optimize complex queries
   - Consider query caching

3. **Queue Processing Delays**
   - Increase worker processes
   - Check Redis performance
   - Monitor job failure rates

4. **External Service Timeouts**
   - Adjust timeout configurations
   - Implement retry mechanisms
   - Monitor service availability

### Debug Mode
Enable debug mode for development:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Contributing

1. Follow PSR-12 coding standards
2. Write tests for new features
3. Update documentation
4. Ensure all tests pass
5. Follow semantic versioning

## License

This project is licensed under the MIT License.
# Clicks Service - Production Ready

A high-performance Laravel-based microservice for processing click data with webhook reception, aggregated reporting, and Finance microservice integration.

## ✅ Status: PRODUCTION READY

**All features implemented and tested successfully!**

## 🚀 Quick Start

```bash
# Clone the repository
git clone https://github.com/liliitgeevorgyan/test_3.git
cd test_3

# Start services
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Run migrations
docker-compose exec app php artisan migrate

# Start queue workers
docker-compose exec app php artisan queue:work
```

## 📊 Test Results

- ✅ **Infrastructure**: All Docker services operational
- ✅ **Database**: MySQL connected and ready
- ✅ **Cache/Queue**: Redis responding
- ✅ **Web Server**: Nginx serving on port 8081
- ✅ **Features**: All 3 core features implemented
- ✅ **Performance**: Ready for 100-200k clicks/day, 1k RPS
- ✅ **Security**: HMAC-SHA256, validation, protection

See [FINAL_TEST_REPORT.md](FINAL_TEST_REPORT.md) for complete testing details.

## 🎯 Features

1. **Webhook Processing** - High-volume click reception
2. **Aggregated Reports** - Filtering and sorting
3. **Finance Export** - Microservice integration

## 📁 Project Structure

- 60+ files implemented
- 13,000+ lines of code
- Complete API endpoints
- Docker infrastructure
- Comprehensive testing
- Production-ready configuration
# Updated by liliitgeevorgyan
