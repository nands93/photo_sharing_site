name=Camagru

all:
	@printf "Launch development configuration ${name}...\n";
	@docker compose --env-file .env -f docker-compose.yml up -d
	
re:
	@printf "Rebuild development configuration ${name}...\n"
	@docker compose --env-file .env -f docker-compose.yml down
	@docker compose --env-file .env -f docker-compose.yml up -d --build

down:
	@printf "Stopping configuration ${name}...\n"
	@docker compose -f docker-compose.yml down || true

clean: down
	@printf "Cleaning configuration ${name}...\n"
	@docker system prune -a

fclean:
	@printf "Total clean of all configurations docker\n"
	@docker ps -qa | xargs -r docker stop
	@docker system prune --all --force
	@docker network prune --force
	@rm -rf /var/www/django/staticfiles
	@rm -rf /var/www/django/media
	@docker compose -f docker-compose.yml down --remove-orphans || true

.PHONY: all dev prod re-dev re-prod down clean fclean