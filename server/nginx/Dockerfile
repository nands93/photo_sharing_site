FROM nginx:1.27.5-alpine-slim

RUN rm /etc/nginx/conf.d/default.conf

RUN apt update && apt install -y openssl

RUN mkdir -p /etc/nginx/ssl

EXPOSE 443

CMD ["nginx", "-g", "daemon off;"]