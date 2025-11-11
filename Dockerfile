FROM nginx:alpine
COPY nginx.conf /etc/nginx/nginx.conf
COPY jla-alerts /usr/share/nginx/html/jla-alerts
COPY jla-dashboard /usr/share/nginx/html/jla-dashboard
COPY jla-progress /usr/share/nginx/html/jla-progress
COPY jla-splash /usr/share/nginx/html/jla-splash
COPY jla-standings /usr/share/nginx/html/jla-standings
COPY favicon.ico /usr/share/nginx/html/favicon.ico
