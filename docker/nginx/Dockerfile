FROM nginx:alpine

# Copy default nginx configuration. Note the volume mount in docker-compose.yaml takes presedence over this. This line is kept in case
# you want to ship a production version of this container. 
COPY nginx.conf /etc/nginx/conf.d/default.conf