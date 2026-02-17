# Yandex Map Parser

## Running the Application

### With Docker

1. Copy `.env.example` to `.env` and configure:
   ```bash
   cp .env.example .env
   # Edit .env: DB_*, REDIS_HOST, etc.
   ```

2. Start all containers:
   ```bash
   docker compose up -d
   ```

3. Ensure the `node` container is running (Vite dev server for hot reload):
   ```bash
   docker compose up -d node
   ```

4. Generate app key and run migrations (first time):
   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   ```

5. Open the app: **http://localhost:8078**

| Service     | URL                    |
|------------|------------------------|
| Application| http://localhost:8078  |
| phpMyAdmin | http://localhost:8058  |
| Parser API | http://localhost:3000  |

### Without Docker (local)

1. Install PHP 8.4+, Node 20+, Composer, MySQL, Redis
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Configure `.env` (database, redis)
4. Run migrations:
   ```bash
   php artisan migrate
   ```
5. Start services (in separate terminals):
   ```bash
   php artisan serve
   npm run dev
   ```

## Helpful Commands

### Docker
| Command | Description |
|---------|-------------|
| `docker compose up -d` | Start all services |
| `docker compose down` | Stop all services |
| `docker compose ps` | List running containers |
| `docker compose logs -f app` | View app container logs |
| `docker compose exec app bash` | Shell into app container |

### Laravel Artisan
| Command | Description |
|---------|-------------|
| `php artisan migrate` | Run migrations |
| `php artisan migrate:fresh --seed` | Fresh migration with seed |
| `php artisan cache:clear` | Clear application cache |
| `php artisan config:clear` | Clear config cache |
| `php artisan route:list` | List all routes |
| `php artisan queue:work` | Run queue worker |
| `php artisan tinker` | Open REPL |

### Composer & NPM
| Command | Description |
|---------|-------------|
| `composer install` | Install PHP dependencies |
| `composer dump-autoload` | Regenerate autoload files |
| `npm run dev` | Start Vite dev server |
| `npm run build` | Build assets for production |
| `npm run lint` | Run ESLint |
| `npm run format` | Format code with Prettier |

## License

The Laravel + Vue starter kit is open-sourced software licensed under the MIT license.
