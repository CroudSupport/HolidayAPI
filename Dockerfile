##### WEBAPP BUILD #####
FROM croudtech/webapp:2.0 AS webapp
ARG ssh_key

RUN mkdir /root/.ssh
RUN touch /root/.ssh/config
RUN touch /root/.ssh/id_rsa

RUN echo ${ssh_key} | base64 --decode >> /root/.ssh/id_rsa

RUN chown root:root -R /root/.ssh
RUN chmod 600 /root/.ssh/id_rsa

RUN echo "    IdentityFile /root/.ssh/id_rsa" >> /root/.ssh/config
RUN echo "    StrictHostKeyChecking no" >> /root/.ssh/config

RUN mkdir -p /var/www/app_tmp

WORKDIR /var/www/app_tmp

COPY ./src/composer.json ./
COPY ./src/composer.lock ./

RUN composer install  --no-scripts --no-autoloader --no-dev

WORKDIR /var/www/holidays

ENV WEBAPP_ROOT /var/www/holidays
ENV DOC_ROOT /var/www/holidays/public
ENV CONFIG_DIR /var/www/holidays/server_config
ENV NGINX_HOSTNAME holidays
ENV APP_LOG=errorlog

ENV CRON_TABS_LOCATION /usr/share/docker_build/crontabs
COPY ./docker_build /usr/share/docker_build
COPY ./src ./

RUN rsync -ah /var/www/app_tmp/* ${WEBAPP_ROOT}/
RUN rm -fr /var/www/app_tmp

RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data ./
