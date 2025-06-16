#!/bin/bash
# Script pour démarrer le serveur WebSocket

echo "Démarrage du serveur WebSocket..."
echo "Port: 8080"
echo "Pour arrêter: Ctrl+C"
echo "=========================="

cd websocket
php server.php
