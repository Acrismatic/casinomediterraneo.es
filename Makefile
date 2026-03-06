COMPOSE_DEV=docker compose -f compose.yml -f compose.dev.yml

setup:
	cp -n .env.example .env || true
	grep -q '^HOST_UID=' .env || echo "HOST_UID=$$(id -u)" >> .env
	grep -q '^HOST_GID=' .env || echo "HOST_GID=$$(id -g)" >> .env
	mkdir -p src/wp-content/uploads

up:
	$(COMPOSE_DEV) up -d --build

down:
	$(COMPOSE_DEV) down

logs:
	$(COMPOSE_DEV) logs -f

wp:
	$(COMPOSE_DEV) run --rm wordpress wp $(filter-out $@,$(MAKECMDGOALS))

shell:
	$(COMPOSE_DEV) exec wordpress sh

%:
	@:
