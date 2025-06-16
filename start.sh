#!/bin/bash

# Ajouter le token ngrok si la variable est définie (version ngrok 2.x)
if [ -n "$NGROK_AUTHTOKEN" ]; then
    ngrok config add-authtoken "$NGROK_AUTHTOKEN"
fi

# Lancer le serveur websocket (écoute sur le port 8081) en arrière-plan
php websocket/server.php &

# Lancer ngrok en tunnel TCP vers le port 8081 en arrière-plan
ngrok http --url=seahorse-eager-joey.ngrok-free.app  8081 --log=stdout &

# Démarrer Apache en premier plan
apache2-foreground
