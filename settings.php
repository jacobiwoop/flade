<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Floade</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
      <?php require_once("./head.php") ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        .settings-card {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .setting-item {
            transition: all 0.2s ease;
        }

        .setting-item:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 24px;
            background: #374151;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-switch.active {
            background: #3b82f6;
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .toggle-switch.active::after {
            transform: translateX(26px);
        }

        .danger-zone {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>

<body class="min-h-screen text-white">
    <?php
    require_once 'config/config.php';

    if (!isLoggedIn()) {
        redirect('login.php');
    }
    ?>

    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="flex items-center mb-8">
                <a href="dashboard.php" class="text-blue-400 hover:text-blue-300 mr-4">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-3xl font-bold">Paramètres</h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Menu de navigation -->
                <div class="lg:col-span-1">
                    <div class="settings-card rounded-xl p-6 sticky top-8">
                        <nav class="space-y-2">
                            <a href="#general" class="nav-item flex items-center px-4 py-3 rounded-lg text-blue-400 bg-blue-500 bg-opacity-20">
                                <i class="fas fa-cog mr-3"></i>
                                Général
                            </a>
                            <a href="#notifications" class="nav-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">
                                <i class="fas fa-bell mr-3"></i>
                                Notifications
                            </a>
                            <a href="#privacy" class="nav-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">
                                <i class="fas fa-shield-alt mr-3"></i>
                                Confidentialité
                            </a>
                            <a href="#appearance" class="nav-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">
                                <i class="fas fa-palette mr-3"></i>
                                Apparence
                            </a>
                            <a href="#account" class="nav-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">
                                <i class="fas fa-user-cog mr-3"></i>
                                Compte
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Contenu des paramètres -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Paramètres généraux -->
                    <div id="general" class="settings-card rounded-xl p-6">
                        <h2 class="text-xl font-semibold mb-6">
                            <i class="fas fa-cog mr-2"></i>Paramètres généraux
                        </h2>

                        <div class="space-y-4">
                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Langue</h3>
                                    <p class="text-sm text-gray-400">Choisissez votre langue préférée</p>
                                </div>
                                <select class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
                                    <option value="fr">Français</option>
                                    <option value="en">English</option>
                                    <option value="es">Español</option>
                                </select>
                            </div>

                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Démarrage automatique</h3>
                                    <p class="text-sm text-gray-400">Lancer Floade au démarrage du système</p>
                                </div>
                                <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                            </div>

                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Minimiser dans la barre des tâches</h3>
                                    <p class="text-sm text-gray-400">Réduire l'application au lieu de la fermer</p>
                                </div>
                                <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div id="notifications" class="settings-card rounded-xl p-6">
                        <h2 class="text-xl font-semibold mb-6">
                            <i class="fas fa-bell mr-2"></i>Notifications
                        </h2>

                        <div class="space-y-4">
                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Notifications de bureau</h3>
                                    <p class="text-sm text-gray-400">Afficher les notifications sur le bureau</p>
                                </div>
                                <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                            </div>

                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Sons de notification</h3>
                                    <p class="text-sm text-gray-400">Jouer un son lors de la réception de messages</p>
                                </div>
                                <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                            </div>

                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Notifications par email</h3>
                                    <p class="text-sm text-gray-400">Recevoir un résumé par email</p>
                                </div>
                                <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                            </div>

                            <div class="setting-item p-4 rounded-lg">
                                <h3 class="font-medium mb-2">Ne pas déranger</h3>
                                <p class="text-sm text-gray-400 mb-3">Définir des heures où vous ne voulez pas être dérangé</p>
                                <div class="flex items-center space-x-4">
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">De</label>
                                        <input type="time" value="22:00" class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">À</label>
                                        <input type="time" value="08:00" class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Confidentialité -->
                    <div id="privacy" class="settings-card rounded-xl p-6">
                        <h2 class="text-xl font-semibold mb-6">
                            <i class="fas fa-shield-alt mr-2"></i>Confidentialité
                        </h2>

                        <div class="space-y-4">
                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Statut en ligne</h3>
                                    <p class="text-sm text-gray-400">Afficher votre statut en ligne aux autres utilisateurs</p>
                                </div>
                                <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                            </div>

                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Dernière connexion</h3>
                                    <p class="text-sm text-gray-400">Afficher votre dernière heure de connexion</p>
                                </div>
                                <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                            </div>

                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Indicateur de frappe</h3>
                                    <p class="text-sm text-gray-400">Montrer quand vous êtes en train d'écrire</p>
                                </div>
                                <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                            </div>

                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Accusés de lecture</h3>
                                    <p class="text-sm text-gray-400">Confirmer la lecture de vos messages</p>
                                </div>
                                <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Apparence -->
                    <div id="appearance" class="settings-card rounded-xl p-6">
                        <h2 class="text-xl font-semibold mb-6">
                            <i class="fas fa-palette mr-2"></i>Apparence
                        </h2>

                        <div class="space-y-4">
                            <div class="setting-item p-4 rounded-lg">
                                <h3 class="font-medium mb-2">Thème</h3>
                                <p class="text-sm text-gray-400 mb-3">Choisissez l'apparence de l'application</p>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="theme-option p-3 border-2 border-blue-500 rounded-lg cursor-pointer">
                                        <div class="w-full h-8 bg-gradient-to-r from-gray-800 to-gray-900 rounded mb-2"></div>
                                        <p class="text-xs text-center">Sombre</p>
                                    </div>
                                    <div class="theme-option p-3 border-2 border-gray-600 rounded-lg cursor-pointer">
                                        <div class="w-full h-8 bg-gradient-to-r from-gray-100 to-gray-200 rounded mb-2"></div>
                                        <p class="text-xs text-center">Clair</p>
                                    </div>
                                    <div class="theme-option p-3 border-2 border-gray-600 rounded-lg cursor-pointer">
                                        <div class="w-full h-8 bg-gradient-to-r from-gray-800 via-gray-100 to-gray-800 rounded mb-2"></div>
                                        <p class="text-xs text-center">Auto</p>
                                    </div>
                                </div>
                            </div>

                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Taille de police</h3>
                                    <p class="text-sm text-gray-400">Ajuster la taille du texte</p>
                                </div>
                                <select class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
                                    <option value="small">Petite</option>
                                    <option value="medium" selected>Moyenne</option>
                                    <option value="large">Grande</option>
                                </select>
                            </div>

                            <div class="setting-item flex items-center justify-between p-4 rounded-lg">
                                <div>
                                    <h3 class="font-medium">Animations</h3>
                                    <p class="text-sm text-gray-400">Activer les animations de l'interface</p>
                                </div>
                                <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Compte -->
                    <div id="account" class="settings-card rounded-xl p-6">
                        <h2 class="text-xl font-semibold mb-6">
                            <i class="fas fa-user-cog mr-2"></i>Paramètres du compte
                        </h2>

                        <div class="space-y-4">
                            <div class="setting-item p-4 rounded-lg">
                                <h3 class="font-medium mb-2">Informations du compte</h3>
                                <div class="space-y-2 text-sm">
                                    <p><span class="text-gray-400">Pseudo:</span> <?php echo htmlspecialchars($_SESSION['user_pseudo']); ?></p>
                                    <p><span class="text-gray-400">Email:</span> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                                    <p><span class="text-gray-400">Membre depuis:</span> Janvier 2024</p>
                                </div>
                                <a href="profile.php" class="inline-block mt-3 text-blue-400 hover:text-blue-300">
                                    Modifier le profil <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>

                            <div class="setting-item p-4 rounded-lg">
                                <h3 class="font-medium mb-2">Sauvegarde des données</h3>
                                <p class="text-sm text-gray-400 mb-3">Télécharger une copie de vos données</p>
                                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-download mr-2"></i>Télécharger mes données
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Zone dangereuse -->
                    <div class="danger-zone rounded-xl p-6">
                        <h2 class="text-xl font-semibold mb-6 text-red-400">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Zone dangereuse
                        </h2>

                        <div class="space-y-4">
                            <div class="setting-item p-4 rounded-lg border border-red-500 border-opacity-30">
                                <h3 class="font-medium text-red-400 mb-2">Supprimer le compte</h3>
                                <p class="text-sm text-gray-400 mb-3">
                                    Cette action est irréversible. Toutes vos données seront définitivement supprimées.
                                </p>
                                <button onclick="confirmDeleteAccount()"
                                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                    <i class="fas fa-trash mr-2"></i>Supprimer mon compte
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSwitch(element) {
            element.classList.toggle('active');
        }

        function confirmDeleteAccount() {
            if (confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) {
                if (confirm('Dernière confirmation : toutes vos données seront perdues. Continuer ?')) {
                    // Ici vous pouvez ajouter la logique de suppression
                    alert('Fonctionnalité de suppression non implémentée dans cette démo');
                }
            }
        }

        // Navigation smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    // Mettre à jour la navigation active
                    document.querySelectorAll('.nav-item').forEach(item => {
                        item.classList.remove('text-blue-400', 'bg-blue-500', 'bg-opacity-20');
                        item.classList.add('text-gray-300');
                    });

                    this.classList.remove('text-gray-300');
                    this.classList.add('text-blue-400', 'bg-blue-500', 'bg-opacity-20');
                }
            });
        });

        // Gestion des thèmes
        document.querySelectorAll('.theme-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.theme-option').forEach(opt => {
                    opt.classList.remove('border-blue-500');
                    opt.classList.add('border-gray-600');
                });

                this.classList.remove('border-gray-600');
                this.classList.add('border-blue-500');
            });
        });
    </script>
</body>

</html>
