#!/bin/sh
set -e

# Na primeira inicialização, semeia o volume de uploads com as imagens da imagem Docker.
# Usa -n (no-clobber) para nunca sobrescrever uploads feitos pelo usuário.
if [ -d /var/www/html/uploads-seed ] && [ -d /var/www/html/public/uploads ]; then
  cp -rn /var/www/html/uploads-seed/. /var/www/html/public/uploads/ 2>/dev/null || true
  chown -R www-data:www-data /var/www/html/public/uploads/ 2>/dev/null || true
fi

exec "$@"
