#!/bin/bash
set -e

if [ -z "$BUILDER_UID" ] || [ -z "$BUILDER_GID" ]; then
    echo "BUILDER_UID and BUILDER_GID env. variables should be set"
    exit 1
fi

if [[ "$MEMORY_LIMIT" != "" ]]; then
    echo "memory_limit = $MEMORY_LIMIT" >> /usr/local/etc/php/conf.d/zzzz_runtime.ini
fi

mkdir -p /home/builder
groupadd -g $BUILDER_GID builder || groupmod -n builder $(getent group $BUILDER_GID | awk -F ':' '{print $1}')
useradd -u $BUILDER_UID -g $BUILDER_GID builder || usermod -l builder $(getent passwd $BUILDER_UID | awk -F ':' '{print $1}')
if [[ "$PRESTISSIMO_ENABLED" = "1" ]]; then
    cp -rn /root/.composer /home/builder/
fi
chown -R builder:builder /home/builder

# We need this to enable XDebug with same IDE mappings
chown builder:builder /app
ln -s /app /home/builder/build

if [[ "$XDEBUG_ENABLED" = "1" ]]; then
    docker-php-ext-enable xdebug

    if [[ "$XDEBUG_PORT" = "" ]]; then
        XDEBUG_PORT=9003
    fi
    echo "xdebug.remote_port = $XDEBUG_PORT" >> /usr/local/etc/php/conf.d/zzzz_runtime.ini
fi

if [[ "$OPCACHE_ENABLED" = "0" ]]; then
    rm -f /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
fi

cd /app
exec gosu ${USER} "$@"
