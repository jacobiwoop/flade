#!/bin/bash

# Démarrer Apache en arrière-plan
apache2-foreground &

# Démarrer le serveur WebSocket (par exemple Ratchet)
php websocket/server.php &

# Garder le container vivant en attendant les processus
wait -n

# Si un des processus meurt, on stoppe le container
exit $?
