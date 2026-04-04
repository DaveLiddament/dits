## Docker

This project uses Docker for all PHP operations.

Xdebug is enabled by default. To disable it, set `XDEBUG_MODE=off` in `docker-compose.yml` and restart the container.

## Running Commands

This project uses a Makefile to wrap Docker commands. Always prefer `make` targets over direct Docker commands.

Run `make help` to see all available targets.

Common commands:
- `make up` — Start the Docker container (use this for day-to-day work)
- `make start` — Full rebuild and start (only needed after Dockerfile changes)
- `make app/shell` — Open a shell in the container
- `make app/ci` — Run all CI checks
- `make app/test` — Run tests (`make app/test c="--filter=MyTest"` for specific tests)
- `make app/composer c="require some/package"` — Run composer commands

Only fall back to `docker compose exec app <command>` if there is no suitable Make target.
