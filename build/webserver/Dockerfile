From php:7.0-apache
RUN a2enmod rewrite
RUN service apache2 restart
RUN docker-php-ext-install pdo pdo_mysql
RUN chown -R www-data:www-data /var/www/html
RUN cd /var/www/html
RUN apt-get update
RUN apt-get install -y cron
RUN apt-get install -y nano
RUN apt-get install -y git
RUN git clone https://github.com/foraern/ethos-panel.git .
RUN cp -f config/config.docker.json /var/www/html/config/config.json
RUN (crontab -l 2>/dev/null; echo "*/10 * * * * cd /var/www/html/ && ./cron 2>/dev/null") | crontab -
