#!/bin/bash
# backend/start_websocket.sh

echo "Démarrage du serveur WebSocket Floade Chat..."

# Vérifier si PHP est installé
if ! command -v php &> /dev/null; then
    echo "Erreur: PHP n'est pas installé ou n'est pas dans le PATH"
    exit 1
fi

# Vérifier si Composer est installé
if ! command -v composer &> /dev/null; then
    echo "Erreur: Composer n'est pas installé ou n'est pas dans le PATH"
    exit 1
fi

# Installer les dépendances si nécessaire
if [ ! -d "vendor" ]; then
    echo "Installation des dépendances Composer..."
    composer install
fi

# Démarrer le serveur WebSocket
echo "Serveur WebSocket démarré sur le port 8080"
echo "Appuyez sur Ctrl+C pour arrêter le serveur"

php websocket/server.php
