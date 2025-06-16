# Application de Chat en Temps Réel

## Description
Application de chat en temps réel développée en PHP avec WebSocket pour la communication instantanée.

## Fonctionnalités
- ✅ Authentification des utilisateurs (inscription/connexion)
- ✅ Création et gestion de conversations
- ✅ Messagerie en temps réel via WebSocket
- ✅ Interface responsive avec Tailwind CSS
- ✅ Indicateur de frappe
- ✅ Messages non lus
- ✅ Fallback API REST si WebSocket indisponible

## Installation

### 1. Prérequis
- PHP 7.4+
- MySQL 5.7+
- Composer
- Serveur web (Apache/Nginx)

### 2. Configuration de la base de données
\`\`\`bash
# Créer la base de données
mysql -u root -p < sql.db
\`\`\`

### 3. Configuration PHP
\`\`\`bash
# Modifier config/database.php avec vos paramètres MySQL
# Modifier config/config.php avec votre BASE_URL
\`\`\`

### 4. Installation des dépendances WebSocket
\`\`\`bash
cd websocket
composer install
\`\`\`

### 5. Démarrage

#### Serveur Web
\`\`\`bash
# Démarrer Apache/Nginx et pointer vers le dossier du projet
\`\`\`

#### Serveur WebSocket
\`\`\`bash
# Terminal séparé
chmod +x start_websocket.sh
./start_websocket.sh

# Ou manuellement :
cd websocket
php server.php
\`\`\`

## Utilisation

1. **Inscription** : Créer un compte sur `/register.php`
2. **Connexion** : Se connecter sur `/login.php`
3. **Dashboard** : Voir la liste des conversations sur `/dashboard.php`
4. **Chat** : Cliquer sur une conversation pour discuter en temps réel

## Structure du Projet

\`\`\`
v1/
├── auth/                   # Authentification
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── User.php
├── api/                    # API REST
│   ├── create_conversation.php
│   ├── send_message.php
│   └── get_messages.php
├── classes/                # Classes PHP
│   └── Chat.php
├── config/                 # Configuration
│   ├── config.php
│   └── database.php
├── websocket/              # Serveur WebSocket
│   ├── server.php
│   └── composer.json
├── dashboard.php           # Liste des conversations
├── conversation.php        # Interface de chat
├── login.php              # Page de connexion
├── register.php           # Page d'inscription
├── index.php              # Page d'accueil
└── sql.db                 # Structure de la base de données
\`\`\`

## Technologies Utilisées

- **Backend** : PHP 7.4+
- **Base de données** : MySQL
- **WebSocket** : Ratchet/ReactPHP
- **Frontend** : HTML5, CSS3, JavaScript ES6
- **Styling** : Tailwind CSS
- **Icons** : Font Awesome

## Sécurité

- Mots de passe hashés avec `password_hash()`
- Protection CSRF via sessions
- Validation et sanitisation des données
- Requêtes préparées pour éviter les injections SQL
- Vérification des permissions d'accès aux conversations

## Dépannage

### WebSocket ne fonctionne pas
- Vérifier que le port 8080 est ouvert
- Vérifier les logs du serveur WebSocket
- Le fallback API REST prendra le relais automatiquement

### Problèmes de base de données
- Vérifier les paramètres dans `config/database.php`
- S'assurer que la base de données est créée et les tables importées

### Erreurs PHP
- Vérifier les logs d'erreur PHP
- S'assurer que toutes les extensions PHP nécessaires sont installées

## Développement

Pour contribuer au projet :
1. Fork le repository
2. Créer une branche feature
3. Commiter les changements
4. Créer une Pull Request

## Licence

MIT License - Voir le fichier LICENSE pour plus de détails.
