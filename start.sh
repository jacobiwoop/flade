#!/bin/bash

# Lancer le serveur WebSocket Ratchet sur le port 8081 en arrière-plan
php websocket/server.php &

# Lancer Apache en premier plan (obligatoire pour Docker)
apache2-foreground
