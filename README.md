php-fpm + cloud spanner boilerplate
=======================================

```bash
export GOOGLE_APPLICATION_CREDENTIALS=/path/to/key.json
cp php/.env.example php/.env
vi php/.env
docker-compose up -d
docker-compose run php composer install
curl http://localhost:8080

# with XDebug

docker-compose exec php vi /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# Add two lines
xdebug.remote_enable=1
xdebug.remote_host=host.docker.internal

curl http://localhost:8080?XDEBUG_SESSION_START=1
```
