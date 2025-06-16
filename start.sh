#!/bin/bash

# Enregistrer le token ngrok s'il est défini
# if [ -n "$NGROK_AUTHTOKEN" ]; then
ngrok config add-authtoken 2c8bOYFGPf7xyfMFMLnMFFq1LCN_2Yoc8Q5eD6JSpqLwihRE2
# fi

# Lancer le serveur websocket en arrière-plan
php websocket/server.php &

# Lancer ngrok TCP sur le port 8081 en arrière-plan
 ngrok http --url=seahorse-eager-joey.ngrok-free.app 8080 --log=stdout &

# Démarrer Apache en premier plan (obligatoire pour Docker)
apache2-foreground
