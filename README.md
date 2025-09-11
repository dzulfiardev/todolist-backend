# TodoList Backend API

A Laravel 11 REST API for managing todo lists with comprehensive CRUD operations, search functionality, and bulk operations.

## Features

- ✅ Complete CRUD operations for TodoList items
- 🔍 Search functionality by task name
- 📦 Bulk delete operations
- 📊 Excel export with advanced filtering
- 🏷️ Priority levels (low, medium, high, critical, best_effort)
- 📊 Status tracking (pending, in_progress, completed)
- 🎯 Task types (task, bug, feature)
- 👥 Developer assignment
- ⏰ Due date management
- 📝 Time tracking
- 🧪 Comprehensive unit testing

## Requirements

- PHP >= 8.2
- Composer
- SQLite (default) or MySQL/PostgreSQL
- Git

## Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/dzulfiardev/todolist-backend.git
cd todolist-backend
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

Copy the environment file and configure it:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 4. Database Setup

The application is configured to use SQLite by default. The database file is already included at `database/database.sqlite`.

If you prefer to use MySQL/PostgreSQL, update your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Clear Cache (if needed)

```bash
php artisan optimize:clear
```

## Running the Application

### Development Server

Start the Laravel development server:

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run specific test files
php artisan test tests/Unit/TodoListStoreTest.php
php artisan test tests/Unit/TodoListDestroyTest.php

# Run tests with coverage (if xdebug is installed)
php artisan test --coverage
```

## API Endpoints

### Base URL
```
http://localhost:8000/api
```

### TodoList Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/todo-lists` | Get all todo lists |
| GET | `/api/todo-lists?search=keyword` | Search todo lists by task name |
| POST | `/api/todo-lists` | Create new todo list |
| GET | `/api/todo-lists/{id}` | Get specific todo list |
| PUT | `/api/todo-lists/{id}` | Update todo list |
| DELETE | `/api/todo-lists/{id}` | Delete single todo list |
| DELETE | `/api/todo-lists` | Bulk delete todo lists |

### Reports Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/reports/todo-lists/export` | Export todo lists to Excel with filtering |
| GET | `/api/reports/todo-lists/preview` | Preview export data (JSON response) |

#### Export Filters

Both export and preview endpoints support the following query parameters:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `title` | string | Filter by task title (partial match) | `title=App` |
| `assigne` | string | Filter by assignees (comma-separated) | `assigne=john,zul` |
| `start` | date | Start date for due_date range | `start=2025-09-01` |
| `end` | date | End date for due_date range | `end=2025-12-31` |
| `min` | numeric | Minimum time tracked | `min=0` |
| `max` | numeric | Maximum time tracked | `max=10` |
| `status` | string | Filter by status (comma-separated) | `status=pending,in_progress` |
| `priority` | string | Filter by priority (comma-separated) | `priority=high,medium` |

### Request Examples

#### Create TodoList
```bash
curl -X POST http://localhost:8000/api/todo-lists \
  -H "Content-Type: application/json" \
  -d '{
    "task": "Complete project documentation",
    "developer": "John Doe",
    "priority": "high",
    "status": "pending",
    "type": "task",
    "due_date": "2025-12-31",
    "description": "Write comprehensive API documentation",
    "time_tracked": "2.5"
  }'
```

#### Search TodoLists
```bash
curl "http://localhost:8000/api/todo-lists?search=documentation"
```

#### Export to Excel (with filters)
```bash
# Export all todos
curl "http://localhost:8000/api/reports/todo-lists/export" --output todolist_export.xlsx

# Export with filters
curl "http://localhost:8000/api/reports/todo-lists/export?title=App&status=pending,in_progress&start=2025-09-01&end=2025-12-31" --output filtered_export.xlsx

# Export by assignees and priority
curl "http://localhost:8000/api/reports/todo-lists/export?assigne=john,alice&priority=high,critical" --output assignee_export.xlsx

# Export by time range
curl "http://localhost:8000/api/reports/todo-lists/export?min=0&max=8" --output time_filtered_export.xlsx
```

#### Preview Export Data
```bash
# Preview data before export
curl "http://localhost:8000/api/reports/todo-lists/preview?title=App&status=pending" -H "Accept: application/json"
```

#### Bulk Delete
```bash
curl -X DELETE http://localhost:8000/api/todo-lists \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [1, 2, 3]
  }'
```

#### Bulk Delete
```bash
curl -X DELETE http://localhost:8000/api/todo-lists \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [1, 2, 3]
  }'
```

### Excel Export Features

The Excel export includes:
- **Formatted Headers**: Bold headers with background color
- **Data Columns**: Title, Assignee, Due Date, Time Tracked, Status, Priority
- **Summary Row**: Total time tracked across all filtered todos
- **Auto-sizing**: Columns automatically adjust to content width
- **Borders**: Clean table formatting with borders
- **Date Formatting**: Consistent YYYY-MM-DD date format
- **File Naming**: Timestamped filenames (e.g., `todolist_report_2025_09_11_14_30_45.xlsx`)

### Response Format

All API responses follow this format:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data here
  }
}
```

### Validation Rules

#### Create/Update TodoList

| Field | Type | Rules | Options |
|-------|------|-------|---------|
| task | string | required, max:255 | - |
| developer | string | nullable, max:255 | - |
| priority | string | required | low, medium, high, critical, best_effort |
| status | string | required | pending, in_progress, completed |
| type | string | required | task, bug, feature |
| due_date | date | nullable, after_or_equal:today | YYYY-MM-DD format |
| description | string | nullable | - |
| time_tracked | numeric | nullable, min:0 | Hours (decimal) |

## Project Structure

```
app/
├── Helpers/
│   └── TodoListHelper.php          # Utility functions for data formatting
├── Http/Controllers/Api/
│   └── TodoListController.php      # Main API controller
└── Models/
    ├── TodoLists.php               # TodoList model
    └── User.php                    # User model

database/
├── migrations/                     # Database schema migrations
├── factories/                      # Model factories for testing
└── seeders/                        # Database seeders

tests/
├── Unit/
│   ├── TodoListStoreTest.php       # Unit tests for create operations
│   └── TodoListDestroyTest.php     # Unit tests for delete operations
└── Feature/                        # Feature tests
```

## Development

### Code Style

The project uses Laravel Pint for code formatting:

```bash
./vendor/bin/pint
```

### Database Migrations

Create new migration:

```bash
php artisan make:migration create_your_table_name
```

Run migrations:

```bash
php artisan migrate
```

Rollback migrations:

```bash
php artisan migrate:rollback
```

### Creating Tests

Create unit test:

```bash
php artisan make:test YourTestName --unit
```

Create feature test:

```bash
php artisan make:test YourTestName
```

## Troubleshooting

### Common Issues

1. **Routes not found**: Ensure API routes are loaded in `bootstrap/app.php`
2. **Database connection**: Check `.env` configuration and database file permissions
3. **Permission errors**: Ensure `storage/` and `bootstrap/cache/` directories are writable

### Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# List all routes
php artisan route:list

# Check application status
php artisan about

# View logs
tail -f storage/logs/laravel.log
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
