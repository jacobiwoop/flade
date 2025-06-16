<?php
require_once 'config/config.php';
require_once 'auth/User.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = new User();
$userInfo = $user->getUserById($_SESSION['user_id']);

// Fonction pour obtenir l'URL de la photo de profil
function getProfilePhotoUrl($photo)
{
    if ($photo && file_exists('uploads/profiles/' . $photo)) {
        return 'uploads/profiles/' . $photo;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPseudo = sanitize($_POST['pseudo']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validation du pseudo
    if (empty($newPseudo)) {
        $errors[] = 'Le pseudo est requis';
    } elseif (strlen($newPseudo) < 3) {
        $errors[] = 'Le pseudo doit contenir au moins 3 caractères';
    }

    // Validation du mot de passe si fourni
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $errors[] = 'Le mot de passe actuel est requis pour changer le mot de passe';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'Le nouveau mot de passe doit contenir au moins 6 caractères';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'Les nouveaux mots de passe ne correspondent pas';
        }
    }

    if (empty($errors)) {
        // Mettre à jour le profil
        $result = $user->updateProfile($_SESSION['user_id'], $newPseudo, $currentPassword, $newPassword);

        if ($result['success']) {
            $_SESSION['user_pseudo'] = $newPseudo;
            setSuccess('Profil mis à jour avec succès');
            redirect('profile.php');
        } else {
            setError($result['message']);
        }
    } else {
        setError(implode('<br>', $errors));
    }
}

$userPhoto = getProfilePhotoUrl($userInfo['profile_photo']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Floade</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
      <?php require_once("./head.php") ?>
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        .avatar-colors {
            background: linear-gradient(135deg, #8b5cf6, #3b82f6);
        }

        .profile-card {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .input-field {
            background: rgba(55, 65, 81, 0.8);
            border: 1px solid rgba(75, 85, 99, 0.5);
            transition: all 0.3s ease;
        }

        .input-field:focus {
            background: rgba(55, 65, 81, 1);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }

        .photo-upload-area {
            border: 2px dashed rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .photo-upload-area:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .photo-upload-area.dragover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }

        .profile-photo {
            width: 8rem;
            height: 8rem;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(59, 130, 246, 0.3);
        }

        .upload-progress {
            display: none;
            width: 100%;
            height: 4px;
            background: rgba(59, 130, 246, 0.2);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .upload-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>

<body class="min-h-screen text-white">
    <div class="min-h-screen py-8">
        <div class="max-w-2xl mx-auto px-4">
            <!-- Header -->
            <div class="flex items-center mb-8">
                <a href="dashboard.php" class="text-blue-400 hover:text-blue-300 mr-4">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-3xl font-bold">Mon Profil</h1>
            </div>

            <!-- Messages -->
            <?php if ($error = getError()): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success = getSuccess()): ?>
                <div class="bg-green-500 bg-opacity-20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="profile-card rounded-xl p-8">
                <!-- Photo de profil -->
                <div class="text-center mb-8">
                    <div class="relative inline-block">
                        <div id="profilePhotoContainer" class="w-32 h-32 mx-auto mb-4 relative">
                            <?php if ($userPhoto): ?>
                                <img id="profilePhoto" src="<?php echo htmlspecialchars($userPhoto); ?>"
                                    alt="Photo de profil" class="profile-photo">
                            <?php else: ?>
                                <div id="profilePhoto" class="w-32 h-32 avatar-colors rounded-full flex items-center justify-center text-white font-bold text-4xl">
                                    <?php echo strtoupper(substr($_SESSION['user_pseudo'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                            <button id="changePhotoBtn" class="absolute bottom-0 right-0 w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white hover:bg-blue-700 transition-colors">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Zone de téléchargement de photo -->
                    <div id="photoUploadArea" class="photo-upload-area rounded-lg p-6 mt-4 hidden">
                        <i class="fas fa-cloud-upload-alt text-3xl text-blue-400 mb-2"></i>
                        <p class="text-gray-400">Glissez-déposez une image ou cliquez pour sélectionner</p>
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF ou WebP (max. 2MB)</p>
                        <div class="upload-progress">
                            <div class="upload-progress-bar"></div>
                        </div>
                    </div>
                    <input type="file" id="photoInput" class="hidden" accept="image/*">
                </div>

                <!-- Formulaire de profil -->
                <form method="POST" class="space-y-6">
                    <!-- Informations de base -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-300 text-sm font-medium mb-2">
                                <i class="fas fa-user mr-2"></i>Pseudo
                            </label>
                            <input type="text" name="pseudo"
                                value="<?php echo htmlspecialchars($userInfo['pseudo']); ?>"
                                class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                required>
                        </div>

                        <div>
                            <label class="block text-gray-300 text-sm font-medium mb-2">
                                <i class="fas fa-envelope mr-2"></i>Email
                            </label>
                            <input type="email"
                                value="<?php echo htmlspecialchars($userInfo['email']); ?>"
                                class="input-field w-full px-4 py-3 rounded-lg text-gray-400 bg-gray-700 cursor-not-allowed"
                                disabled>
                            <p class="text-xs text-gray-500 mt-1">L'email ne peut pas être modifié</p>
                        </div>
                    </div>

                    <!-- Changement de mot de passe -->
                    <div class="border-t border-gray-700 pt-6">
                        <h3 class="text-lg font-semibold mb-4">
                            <i class="fas fa-lock mr-2"></i>Changer le mot de passe
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-300 text-sm font-medium mb-2">
                                    Mot de passe actuel
                                </label>
                                <input type="password" name="current_password"
                                    class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                    placeholder="Laissez vide si vous ne voulez pas changer">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">
                                        Nouveau mot de passe
                                    </label>
                                    <input type="password" name="new_password" id="newPassword"
                                        class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                        placeholder="Au moins 6 caractères">
                                </div>

                                <div>
                                    <label class="block text-gray-300 text-sm font-medium mb-2">
                                        Confirmer le nouveau mot de passe
                                    </label>
                                    <input type="password" name="confirm_password" id="confirmPassword"
                                        class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                                        placeholder="Répétez le nouveau mot de passe">
                                </div>
                            </div>
                            <div id="passwordMatchMessage" class="text-sm"></div>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="border-t border-gray-700 pt-6">
                        <h3 class="text-lg font-semibold mb-4">
                            <i class="fas fa-chart-bar mr-2"></i>Statistiques
                        </h3>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center p-4 bg-gray-700 bg-opacity-50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-400">12</div>
                                <div class="text-xs text-gray-400">Conversations</div>
                            </div>
                            <div class="text-center p-4 bg-gray-700 bg-opacity-50 rounded-lg">
                                <div class="text-2xl font-bold text-green-400">248</div>
                                <div class="text-xs text-gray-400">Messages envoyés</div>
                            </div>
                            <div class="text-center p-4 bg-gray-700 bg-opacity-50 rounded-lg">
                                <div class="text-2xl font-bold text-purple-400">15</div>
                                <div class="text-xs text-gray-400">Jours actifs</div>
                            </div>
                            <div class="text-center p-4 bg-gray-700 bg-opacity-50 rounded-lg">
                                <div class="text-2xl font-bold text-yellow-400">
                                    <?php echo date('d/m/Y', strtotime($userInfo['created_at'])); ?>
                                </div>
                                <div class="text-xs text-gray-400">Membre depuis</div>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-6">
                        <button type="submit" class="btn-primary flex-1 py-3 px-6 rounded-lg text-white font-semibold">
                            <i class="fas fa-save mr-2"></i>Sauvegarder les modifications
                        </button>
                        <a href="dashboard.php"
                            class="flex-1 py-3 px-6 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors text-center">
                            <i class="fas fa-times mr-2"></i>Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let isUploading = false;

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            initializePhotoUpload();
            initializePasswordValidation();
        });

        function initializePhotoUpload() {
            const changePhotoBtn = document.getElementById('changePhotoBtn');
            const photoInput = document.getElementById('photoInput');
            const photoUploadArea = document.getElementById('photoUploadArea');
            const profilePhoto = document.getElementById('profilePhoto');

            // Clic sur le bouton de changement de photo
            changePhotoBtn.addEventListener('click', function() {
                photoUploadArea.classList.toggle('hidden');
            });

            // Clic sur la zone d'upload
            photoUploadArea.addEventListener('click', function() {
                if (!isUploading) {
                    photoInput.click();
                }
            });

            // Sélection de fichier
            photoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    handleFileUpload(file);
                }
            });

            // Drag & Drop
            photoUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                photoUploadArea.classList.add('dragover');
            });

            photoUploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                photoUploadArea.classList.remove('dragover');
            });

            photoUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                photoUploadArea.classList.remove('dragover');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileUpload(files[0]);
                }
            });
        }

        function handleFileUpload(file) {
            // Validation du fichier
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showError('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
                return;
            }

            if (file.size > 2 * 1024 * 1024) { // 2MB
                showError('Le fichier est trop volumineux (maximum 2MB)');
                return;
            }

            // Prévisualisation
            const reader = new FileReader();
            reader.onload = function(e) {
                updateProfilePhoto(e.target.result);
            };
            reader.readAsDataURL(file);

            // Upload
            uploadProfilePhoto(file);
        }

        function uploadProfilePhoto(file) {
            if (isUploading) return;

            isUploading = true;
            const formData = new FormData();
            formData.append('profile_photo', file);

            const progressBar = document.querySelector('.upload-progress-bar');
            const uploadProgress = document.querySelector('.upload-progress');

            uploadProgress.style.display = 'block';
            progressBar.style.width = '0%';

            // Simulation de progression (remplacer par vraie progression si supportée)
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 200);

            fetch('api/upload_profile_photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    clearInterval(progressInterval);
                    progressBar.style.width = '100%';

                    setTimeout(() => {
                        uploadProgress.style.display = 'none';
                        progressBar.style.width = '0%';
                    }, 500);

                    if (data.success) {
                        showSuccess('Photo de profil mise à jour avec succès');
                        // Mettre à jour l'affichage
                        updateProfilePhoto(data.photo_url);
                        // Masquer la zone d'upload
                        document.getElementById('photoUploadArea').classList.add('hidden');
                    } else {
                        showError('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    clearInterval(progressInterval);
                    uploadProgress.style.display = 'none';
                    progressBar.style.width = '0%';
                    showError('Erreur lors de l\'upload de la photo');
                    console.error('Erreur upload:', error);
                })
                .finally(() => {
                    isUploading = false;
                });
        }

        function updateProfilePhoto(photoUrl) {
            const profilePhoto = document.getElementById('profilePhoto');

            if (profilePhoto.tagName === 'IMG') {
                profilePhoto.src = photoUrl;
            } else {
                // Remplacer le div par une image
                const img = document.createElement('img');
                img.id = 'profilePhoto';
                img.src = photoUrl;
                img.alt = 'Photo de profil';
                img.className = 'profile-photo';
                profilePhoto.parentNode.replaceChild(img, profilePhoto);
            }
        }

        function initializePasswordValidation() {
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            const matchMessage = document.getElementById('passwordMatchMessage');

            function checkPasswordMatch() {
                if (confirmPassword.value === '' || newPassword.value === '') {
                    matchMessage.textContent = '';
                    return;
                }

                if (newPassword.value === confirmPassword.value) {
                    matchMessage.textContent = '✓ Les mots de passe correspondent';
                    matchMessage.className = 'text-sm text-green-400';
                } else {
                    matchMessage.textContent = '✗ Les mots de passe ne correspondent pas';
                    matchMessage.className = 'text-sm text-red-400';
                }
            }

            newPassword.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            errorDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(errorDiv);
            setTimeout(() => errorDiv.remove(), 5000);
        }

        function showSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            successDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-check mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(successDiv);
            setTimeout(() => successDiv.remove(), 3000);
        }

        // Auto-masquer les messages après 5 secondes
        setTimeout(() => {
            const messages = document.querySelectorAll('.bg-red-500, .bg-green-500');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>

</html>
