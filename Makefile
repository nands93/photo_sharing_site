NAME=camagru

.PHONY: up down re clean fclean

up:
	@printf "🚀 Launching ${NAME}...\n"
	@docker compose up -d --build

down:
	@printf "🛑 Stopping ${NAME}...\n"
	@docker compose down -v || true

re:
	@printf "♻️ Rebuilding ${NAME}...\n"
	@$(MAKE) down
	@$(MAKE) up

clean: down
	@printf "🧹 Cleaning up docker system...\n"
	@docker system prune -a -f

fclean:
	@printf "☢️  Nuking all docker configurations...\n"
	@docker stop $$(docker ps -qa) || true
	@docker system prune --all --volumes --force
	@$(MAKE) down --remove-orphans