<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Chat App</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
      <?php require_once("./head.php") ?>
</head>

<body class="bg-gradient-to-br from-purple-500 to-pink-600 min-h-screen flex items-center justify-center">
    <?php
    require_once 'config/config.php';

    if (isLoggedIn()) {
        redirect('dashboard.php');
    }
    ?>
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <i class="fas fa-user-plus text-4xl text-purple-500 mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800">Créer un compte</h1>
            <p class="text-gray-600 mt-2">Rejoignez notre communauté de Floade</p>
        </div>

        <!-- Messages d'erreur/succès -->
        <div id="messages" class="mb-4">
            <?php if ($error = getError()): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulaire d'inscription -->
        <form action="auth/register.php" method="POST" class="space-y-6" id="registerForm">
            <div>
                <label for="pseudo" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user mr-2"></i>Pseudo
                </label>
                <input type="text" id="pseudo" name="pseudo" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Choisissez un pseudo">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-2"></i>Email
                </label>
                <input type="email" id="email" name="email" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="votre.email@exemple.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>Mot de passe
                </label>
                <input type="password" id="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Au moins 6 caractères">
                <div class="mt-1 text-xs text-gray-500">
                    Le mot de passe doit contenir au moins 6 caractères
                </div>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>Confirmer le mot de passe
                </label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Répétez votre mot de passe">
                <div id="password-match-message" class="mt-1 text-xs"></div>
            </div>

            <button type="submit"
                class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition duration-200">
                <i class="fas fa-user-plus mr-2"></i>Créer mon compte
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">
                Déjà un compte ?
                <a href="login.php" class="text-purple-600 hover:text-purple-800 font-medium">
                    Se connecter
                </a>
            </p>
        </div>
    </div>

    <script>
        // Validation en temps réel
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const matchMessage = document.getElementById('password-match-message');

        function checkPasswordMatch() {
            if (confirmPassword.value === '') {
                matchMessage.textContent = '';
                return;
            }

            if (password.value === confirmPassword.value) {
                matchMessage.textContent = '✓ Les mots de passe correspondent';
                matchMessage.className = 'mt-1 text-xs text-green-600';
            } else {
                matchMessage.textContent = '✗ Les mots de passe ne correspondent pas';
                matchMessage.className = 'mt-1 text-xs text-red-600';
            }
        }

        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);

        // Validation du formulaire
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
            }

            if (password.value.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères');
            }
        });

        // Auto-masquer les messages après 5 secondes
        setTimeout(() => {
            const messages = document.getElementById('messages');
            if (messages.children.length > 0) {
                messages.style.transition = 'opacity 0.5s';
                messages.style.opacity = '0';
                setTimeout(() => {
                    messages.innerHTML = '';
                    messages.style.opacity = '1';
                }, 500);
            }
        }, 5000);
    </script>
</body>

</html>
