<?php
require_once 'config/config.php';
require_once 'classes/Chat.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_pseudo = $_SESSION['user_pseudo'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Floade</title>

    <!-- Meta tags PWA -->
    <meta name="theme-color" content="#1a202c">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #1a202c;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .dashboard-container {
            background: #2d3748;
            min-height: 100vh;
        }

        .header {
            background: #2d3748;
            border-bottom: 1px solid #4a5568;
            padding: 1rem;
        }

        .conversations-list {
            background: #1a202c;
            overflow-y: auto;
            height: calc(100vh - 80px);
        }

        .conversations-list::-webkit-scrollbar {
            width: 4px;
        }

        .conversations-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .conversations-list::-webkit-scrollbar-thumb {
            background: #4a5568;
            border-radius: 2px;
        }

        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #2d3748;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .conversation-item:hover {
            background: #2d3748;
        }

        .conversation-item.active {
            background: #3182ce;
        }

        .avatar {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .avatar-blue {
            background: linear-gradient(135deg, #3182ce, #2c5aa0);
        }

        .avatar-green {
            background: linear-gradient(135deg, #38a169, #2f855a);
        }

        .avatar-purple {
            background: linear-gradient(135deg, #805ad5, #6b46c1);
        }

        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
        }

        .fab-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #3182ce;
            color: white;
            border: none;
            border-radius: 50%;
            width: 3.5rem;
            height: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.2s;
            z-index: 1000;
        }

        .fab-button:hover {
            background: #2c5aa0;
            transform: scale(1.1);
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-content {
            background: #2d3748;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .user-item {
            padding: 0.75rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0.5rem;
        }

        .user-item:hover {
            background: #4a5568;
        }

        .user-item.selected {
            background: #3182ce;
        }

        .search-input {
            background: #4a5568;
            border: 1px solid #718096;
            border-radius: 0.5rem;
            padding: 0.75rem;
            color: #e2e8f0;
            width: 100%;
            margin-bottom: 1rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #3182ce;
        }

        .search-input::placeholder {
            color: #a0aec0;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #3182ce;
            color: white;
        }

        .btn-primary:hover {
            background: #2c5aa0;
        }

        .btn-secondary {
            background: #4a5568;
            color: #e2e8f0;
        }

        .btn-secondary:hover {
            background: #718096;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading-spinner {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #3182ce;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .conversations-loader {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            flex-direction: column;
            gap: 1rem;
        }

        .skeleton-item {
            background: #2d3748;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .skeleton-avatar {
            width: 3rem;
            height: 3rem;
            background: #4a5568;
            border-radius: 50%;
        }

        .skeleton-text {
            height: 1rem;
            background: #4a5568;
            border-radius: 0.25rem;
        }

        .skeleton-text.short {
            width: 60%;
        }

        .skeleton-text.long {
            width: 80%;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="header">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="avatar avatar-blue text-white">
                        <?php echo strtoupper(substr($user_pseudo, 0, 2)); ?>
                    </div>
                    <div>
                        <h1 class="text-white font-semibold text-lg">
                            Bonjour, <?php echo htmlspecialchars($user_pseudo); ?>
                        </h1>
                        <p class="text-gray-400 text-sm">Vos conversations</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <button onclick="window.location.href='profile.php'"
                        class="text-gray-400 hover:text-white transition-colors p-2">
                        <i class="fas fa-user"></i>
                    </button>
                    <button onclick="window.location.href='settings.php'"
                        class="text-gray-400 hover:text-white transition-colors p-2">
                        <i class="fas fa-cog"></i>
                    </button>
                    <button onclick="window.location.href='auth/logout.php'"
                        class="text-gray-400 hover:text-red-400 transition-colors p-2">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Liste des conversations -->
        <div id="conversationsList" class="conversations-list">
            <!-- Loader initial -->
            <div id="conversationsLoader" class="conversations-loader">
                <div class="loading-spinner"></div>
                <p class="text-gray-400">Chargement de vos conversations...</p>
            </div>

            <!-- Skeleton loader -->
            <div id="skeletonLoader" class="hidden p-4">
                <div class="skeleton-item">
                    <div class="flex items-center space-x-3">
                        <div class="skeleton-avatar"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton-text short"></div>
                            <div class="skeleton-text long"></div>
                        </div>
                    </div>
                </div>
                <div class="skeleton-item">
                    <div class="flex items-center space-x-3">
                        <div class="skeleton-avatar"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton-text short"></div>
                            <div class="skeleton-text long"></div>
                        </div>
                    </div>
                </div>
                <div class="skeleton-item">
                    <div class="flex items-center space-x-3">
                        <div class="skeleton-avatar"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton-text short"></div>
                            <div class="skeleton-text long"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu des conversations (sera rempli dynamiquement) -->
            <div id="conversationsContent"></div>
        </div>

        <!-- Bouton flottant pour nouvelle conversation -->
        <button class="fab-button" onclick="openNewConversationModal()">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <!-- Modal nouvelle conversation -->
    <div id="newConversationModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-white text-xl font-semibold">Nouvelle conversation</h2>
                <button onclick="closeNewConversationModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <input type="text"
                id="userSearch"
                class="search-input"
                placeholder="Rechercher un utilisateur..."
                oninput="searchUsers()">

            <div id="usersList" class="max-h-64 overflow-y-auto mb-4">
                <!-- Les utilisateurs seront chargés ici -->
            </div>

            <div class="flex justify-end space-x-3">
                <button class="btn btn-secondary" onclick="closeNewConversationModal()">
                    Annuler
                </button>
                <button id="createConversationBtn" class="btn btn-primary" onclick="createDirectConversation()" disabled>
                    Créer
                </button>
            </div>
        </div>
    </div>

    <script>
        let selectedUserId = null;
        let conversations = [];

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Afficher le skeleton loader pendant un court moment pour l'effet visuel
            setTimeout(() => {
                document.getElementById('conversationsLoader').style.display = 'none';
                document.getElementById('skeletonLoader').classList.remove('hidden');

                // Puis charger les vraies conversations
                setTimeout(loadConversations, 800);
            }, 500);
        });

        // Charger les conversations
        async function loadConversations() {
            try {
                const response = await fetch('api/get_conversations.php');
                const data = await response.json();

                // Masquer les loaders
                document.getElementById('skeletonLoader').classList.add('hidden');

                if (data.success) {
                    conversations = data.conversations;
                    displayConversations(conversations);
                } else {
                    showError('Erreur lors du chargement des conversations: ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                document.getElementById('skeletonLoader').classList.add('hidden');
                showError('Erreur de connexion');
            }
        }

        // Afficher les conversations
        function displayConversations(conversations) {
            const container = document.getElementById('conversationsContent');

            if (conversations.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3 class="text-lg font-semibold mb-2">Aucune conversation</h3>
                        <p class="mb-4">Commencez une nouvelle conversation en cliquant sur le bouton +</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = conversations.map(conv => {
                const avatarColors = ['avatar-blue', 'avatar-green', 'avatar-purple'];
                const avatarColor = avatarColors[conv.id % avatarColors.length];

                return `
                    <div class="conversation-item" onclick="openConversation(${conv.id})">
                        ${conv.unread_count > 0 ? `<div class="unread-badge">${conv.unread_count}</div>` : ''}
                        
                        <div class="flex items-center space-x-3">
                            <div class="avatar ${avatarColor} text-white">
                                ${conv.other_user_photo ? 
                                    `<img src="${conv.other_user_photo}" alt="Photo de profil">` :
                                    conv.display_name.substring(0, 2).toUpperCase()
                                }
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-white font-medium truncate">
                                        ${conv.display_name}
                                    </h3>
                                    <span class="text-gray-400 text-xs">
                                        ${formatTime(conv.last_message_time)}
                                    </span>
                                </div>
                                
                                <p class="text-gray-400 text-sm truncate">
                                    ${conv.last_message}
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Ouvrir une conversation
        function openConversation(conversationId) {
            window.location.href = `conversation.php?id=${conversationId}`;
        }

        // Ouvrir la modal nouvelle conversation
        function openNewConversationModal() {
            document.getElementById('newConversationModal').style.display = 'flex';
            loadUsers();
        }

        // Fermer la modal
        function closeNewConversationModal() {
            document.getElementById('newConversationModal').style.display = 'none';
            selectedUserId = null;
            document.getElementById('userSearch').value = '';
            document.getElementById('createConversationBtn').disabled = true;
        }

        // Charger les utilisateurs
        async function loadUsers() {
            try {
                const response = await fetch('api/get_users.php');
                const data = await response.json();

                if (data.success) {
                    displayUsers(data.users);
                }
            } catch (error) {
                console.error('Erreur chargement utilisateurs:', error);
            }
        }

        // Afficher les utilisateurs
        function displayUsers(users) {
            const container = document.getElementById('usersList');

            if (users.length === 0) {
                container.innerHTML = '<p class="text-gray-400 text-center py-4">Aucun utilisateur trouvé</p>';
                return;
            }

            container.innerHTML = users.map(user => `
                <div class="user-item" onclick="selectUser(${user.id}, '${user.pseudo}')">
                    <div class="flex items-center space-x-3">
                        <div class="avatar avatar-blue text-white">
                            ${user.profile_photo ? 
                                `<img src="uploads/profiles/${user.profile_photo}" alt="Photo de profil">` :
                                user.pseudo.substring(0, 2).toUpperCase()
                            }
                        </div>
                        <div>
                            <h4 class="text-white font-medium">${user.pseudo}</h4>
                            <p class="text-gray-400 text-sm">
                                ${user.is_online ? 'En ligne' : 'Hors ligne'}
                            </p>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Sélectionner un utilisateur
        function selectUser(userId, pseudo) {
            selectedUserId = userId;

            // Mettre à jour l'affichage
            document.querySelectorAll('.user-item').forEach(item => {
                item.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            document.getElementById('createConversationBtn').disabled = false;
        }

        // Rechercher des utilisateurs
        let searchTimeout;

        function searchUsers() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(async () => {
                const query = document.getElementById('userSearch').value;

                try {
                    const response = await fetch(`api/get_users.php?search=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.success) {
                        displayUsers(data.users);
                    }
                } catch (error) {
                    console.error('Erreur recherche:', error);
                }
            }, 300);
        }

        // Créer une conversation directe
        async function createDirectConversation() {
            if (!selectedUserId) return;

            try {
                const response = await fetch('api/create_direct_conversation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: selectedUserId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    closeNewConversationModal();
                    window.location.href = `conversation.php?id=${data.conversation_id}`;
                } else {
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                console.error('Erreur création conversation:', error);
                alert('Erreur lors de la création de la conversation');
            }
        }

        // Utilitaires
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) { // Moins d'une minute
                return 'À l\'instant';
            } else if (diff < 3600000) { // Moins d'une heure
                return Math.floor(diff / 60000) + 'm';
            } else if (diff < 86400000) { // Moins d'un jour
                return Math.floor(diff / 3600000) + 'h';
            } else if (diff < 604800000) { // Moins d'une semaine
                return Math.floor(diff / 86400000) + 'j';
            } else {
                return date.toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit'
                });
            }
        }

        function showError(message) {
            const container = document.getElementById('conversationsContent');
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle text-red-400"></i>
                    <h3 class="text-lg font-semibold mb-2 text-red-400">Erreur</h3>
                    <p class="mb-4">${message}</p>
                    <button class="btn btn-primary" onclick="loadConversations()">
                        Réessayer
                    </button>
                </div>
            `;
        }

        // Actualiser les conversations périodiquement
        setInterval(loadConversations, 30000); // Toutes les 30 secondes
    </script>
</body>

</html>