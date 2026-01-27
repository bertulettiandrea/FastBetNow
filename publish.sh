#!/bin/bash


# Controllo argomenti
if [ "$#" -ne 2 ]; then
  echo "Uso: $0 <nome_link> <cartella_riferimento>"
  exit 1
fi

LINK_NAME="$1"
TARGET_DIR="$2"
WWW_ROOT="/var/www/html"

# Crea il link simbolico
ln -s "/workspaces/codespaces-blank/$TARGET_DIR" "$WWW_ROOT/$LINK_NAME"