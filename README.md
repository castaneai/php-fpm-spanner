php-fpm + cloud spanner boilerplate
=======================================

```bash
export GOOGLE_APPLICATION_CREDENTIALS=/path/to/key.json
cp php/.env.example php/.env
vi php/.env
docker-compose up -d
docker-compose run php composer install
curl http://localhost:8080
```