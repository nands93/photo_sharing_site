FROM nginx:1.27-alpine

RUN rm /etc/nginx/conf.d/default.conf

RUN apk update && apk add --no-cache openssl

RUN mkdir -p /etc/nginx/ssl

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]