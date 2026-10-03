.PHONY: up stop down logs install build test

# Start the development environment (editor, site preview and Vite dev server).
up:
	docker compose up -d

# Stop the development environment, keeping the containers and the test site.
stop:
	docker compose stop

# Stop and remove the development environment containers, resetting the test site.
down:
	docker compose down

# Follow the logs of the development environment.
logs:
	docker compose logs -f

# Install the PHP and frontend dependencies into the repository.
install:
	docker compose run --rm composer install
	docker compose run --rm node install

# Compile the assets into public/build/.
build:
	docker compose run --rm node run build

# Run the tests affected by the changes since the last run, replaying the rest
# from the test impact analysis cache.
test:
	docker compose run --rm --entrypoint php composer vendor/bin/pest --tia --parallel
