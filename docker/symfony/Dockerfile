FROM php:8

RUN apt-get update && apt-get upgrade
RUN apt-get install -y wget unzip\
    && wget https://get.symfony.com/cli/installer -O - | bash\
    && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

RUN echo "$(curl -sS https://composer.github.io/installer.sig) -" > composer-setup.php.sig \
    && curl -sS https://getcomposer.org/installer | tee composer-setup.php | sha384sum -c composer-setup.php.sig \
    && php composer-setup.php && rm composer-setup.php* \
    && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer


WORKDIR /app
