// frontend/js/auth.js
const API_BASE = "../backend/api"

// Vérifier si l'utilisateur est déjà connecté
if (localStorage.getItem("user_id")) {
  window.location.href = "dashboard.html"
}

// Gestion du formulaire de connexion
const loginForm = document.getElementById("loginForm")
if (loginForm) {
  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault()

    const formData = new FormData(loginForm)
    const data = {
      login: formData.get("login"),
      password: formData.get("password"),
    }

    try {
      showMessage("Connexion en cours...", "info")

      const response = await fetch(`${API_BASE}/auth/login.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })

      const result = await response.json()

      if (result.success) {
        // Sauvegarder les informations utilisateur
        localStorage.setItem("user_id", result.user.id)
        localStorage.setItem("user_pseudo", result.user.pseudo)
        localStorage.setItem("user_email", result.user.email)

        showMessage("Connexion réussie ! Redirection...", "success")

        setTimeout(() => {
          window.location.href = "dashboard.html"
        }, 1000)
      } else {
        showMessage(result.message, "error")
      }
    } catch (error) {
      console.error("Erreur:", error)
      showMessage("Erreur de connexion au serveur", "error")
    }
  })
}

// Gestion du formulaire d'inscription
const registerForm = document.getElementById("registerForm")
if (registerForm) {
  registerForm.addEventListener("submit", async (e) => {
    e.preventDefault()

    const formData = new FormData(registerForm)
    const data = {
      pseudo: formData.get("pseudo"),
      email: formData.get("email"),
      password: formData.get("password"),
      confirm_password: formData.get("confirm_password"),
    }

    // Validation côté client
    if (data.password !== data.confirm_password) {
      showMessage("Les mots de passe ne correspondent pas", "error")
      return
    }

    if (data.password.length < 6) {
      showMessage("Le mot de passe doit contenir au moins 6 caractères", "error")
      return
    }

    try {
      showMessage("Inscription en cours...", "info")

      const response = await fetch(`${API_BASE}/auth/register.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })

      const result = await response.json()

      if (result.success) {
        showMessage("Inscription réussie ! Vous pouvez maintenant vous connecter.", "success")

        setTimeout(() => {
          window.location.href = "login.html"
        }, 2000)
      } else {
        showMessage(result.message, "error")
      }
    } catch (error) {
      console.error("Erreur:", error)
      showMessage("Erreur de connexion au serveur", "error")
    }
  })
}

// Fonction pour afficher les messages
function showMessage(text, type) {
  const messageDiv = document.getElementById("message")
  if (messageDiv) {
    messageDiv.textContent = text
    messageDiv.className = `message ${type}`
    messageDiv.style.display = "block"

    if (type === "success" || type === "info") {
      setTimeout(() => {
        messageDiv.style.display = "none"
      }, 5000)
    }
  }
}
