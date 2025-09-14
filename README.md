# Bank Loan Backend — Docker + Render

This repo is configured to run with PHP 8.2 + Apache and uses the official `mongodb/mongodb` PHP library (requires the `ext-mongodb` PHP extension installed in the image).

## What’s included
- `Dockerfile` — installs `ext-mongodb`, Composer, copies source, runs `composer install`.
- `docker-entrypoint.sh` — waits for DB, runs `migrations.php`, sets Apache to use `$PORT`, starts server.
- `docker-compose.yml` — local dev example with a Mongo service.

## Build & run locally (without Compose)
```bash
# build
docker build -t bank-loan-backend:dev .

# run (example using Atlas URI)
docker run -e MONGODB_URI="mongodb+srv://<user>:<pass>@cluster0.xxxx.mongodb.net/dbname" \
  -e PORT=8080 -p 8080:8080 bank-loan-backend:dev
