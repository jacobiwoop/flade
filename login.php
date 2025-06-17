<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Chat App</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
      <?php require_once("./head.php") ?>
</head>

<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center">
    <?php
   // require_once 'config/config.php';

    if (isLoggedIn()) {
        redirect('dashboard.php');
    }
    ?>
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <i class="fas fa-comments text-4xl text-blue-500 mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800">Floade</h1>
            <p class="text-gray-600 mt-2">Connectez-vous pour commencer à discuter</p>
        </div>

        <!-- Messages d'erreur/succès -->
        <div id="messages" class="mb-4">
            <?php if ($error = getError()): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success = getSuccess()): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulaire de connexion -->
        <form action="auth/login.php" method="POST" class="space-y-6">
            <div>
                <label for="login" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user mr-2"></i>Pseudo ou Email
                </label>
                <input type="text" id="login" name="login" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Entrez votre pseudo ou email">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>Mot de passe
                </label>
                <input type="password" id="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Entrez votre mot de passe">
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">
                Pas encore de compte ?
                <a href="register.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    Créer un compte
                </a>
            </p>
        </div>
    </div>

    <script>
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
