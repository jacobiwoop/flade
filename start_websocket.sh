#!/bin/bash
# Script pour démarrer le serveur WebSocket

echo "Démarrage du serveur WebSocket..."
echo "Port: 8080"
echo "Pour arrêter: Ctrl+C"
echo "=========================="

cd websocket
php server.php

curl -X POST http://flad.x10.mx/api/upload.php ^
  -F "file=@./icons-192.png" ^
  -F "type=profile"
