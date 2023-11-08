FROM fedora:28

RUN dnf install -y composer php php-sodium unzip
RUN useradd -u 1000 -m -r -s /sbin/nologin user

WORKDIR /website
RUN chown user: /website

USER user
# Optimization on the courtesy of Iacovos Constantinou - https://medium.com/@softius/faster-docker-builds-with-composer-install-b4d2b15d0fff
# Add composer.json and/or composer.lock first
COPY --chown=user:user composer.???? /website
RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts

# Then add the actual sources
COPY --chown=user:user . /website
RUN composer dump-autoload --optimize

# And let's make sure the right command gets printed in case no one reads my comments in the first place
CMD cd /website && echo -e "Please, run the container with\n$ docker run --network host <IMAGE_NAME>\nto be able to connect from your machine's browser." && composer start

