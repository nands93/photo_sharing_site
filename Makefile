NAME=camagru

.PHONY: up down re clean fclean

up:
	@printf "🚀 Launching ${NAME}...\n"
	@docker compose up -d --build

down:
	@printf "🛑 Stopping ${NAME}...\n"
	@docker compose down || true

re:
	@printf "♻️ Rebuilding ${NAME}...\n"
	@$(MAKE) down
	@$(MAKE) up

clean: down
	@printf "🧹 Cleaning up docker system...\n"
	@docker system prune -a -f

fclean:
	@printf "☢️  Nuking all docker configurations...\n"
	@docker ps -qa | xargs -r docker stop
	@docker system prune --all --force
	@docker network prune --force
	@docker compose -f docker-compose.yml down --remove-orphans || true
	