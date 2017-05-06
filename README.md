# Instalacion

```bash
docker run --rm --interactive --tty \
    --volume $PWD:/app \
    composer install  # or update
```

# Ejecucion

```bash
docker run -it --rm --name wordpressodb -v "$PWD":/app -w /app php:7.0-cli php app.php
```

```bash
docker-compose run -p 8081:8081 bot
```
