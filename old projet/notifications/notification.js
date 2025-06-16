// notifications/notification.js
class NotificationManager {
  constructor() {
    this.audio = null
    this.permission = "default"
    this.soundEnabled = true
    this.desktopEnabled = true
    this.currentPage = this.detectCurrentPage()

    this.init()
  }

  detectCurrentPage() {
    const path = window.location.pathname
    if (path.includes("conversation.php")) return "conversation"
    if (path.includes("dashboard.php")) return "dashboard"
    return "other"
  }

  async init() {
    // Demander la permission pour les notifications
    if ("Notification" in window) {
      this.permission = await Notification.requestPermission()
    }

    // Charger le son de notification
    this.loadNotificationSound()

    // Récupérer les préférences utilisateur
    this.loadUserPreferences()
  }

  loadNotificationSound() {
    try {
      this.audio = new Audio("notifications/message.mp3")
      this.audio.preload = "auto"
      this.audio.volume = 0.7
    } catch (error) {
      console.error("Erreur lors du chargement du son de notification:", error)
    }
  }

  loadUserPreferences() {
    // Charger depuis localStorage
    this.soundEnabled = localStorage.getItem("notification_sound") !== "false"
    this.desktopEnabled = localStorage.getItem("notification_desktop") !== "false"
  }

  saveUserPreferences() {
    localStorage.setItem("notification_sound", this.soundEnabled)
    localStorage.setItem("notification_desktop", this.desktopEnabled)
  }

  playSound() {
    if (this.soundEnabled && this.audio) {
      try {
        this.audio.currentTime = 0
        this.audio.play().catch((error) => {
          console.error("Erreur lors de la lecture du son:", error)
        })
      } catch (error) {
        console.error("Erreur lors de la lecture du son:", error)
      }
    }
  }

  vibrate() {
    if (navigator.vibrate) {
      navigator.vibrate([200, 100, 200])
    }
  }

  showDesktopNotification(title, message, icon = null) {
    if (this.desktopEnabled && this.permission === "granted" && "Notification" in window) {
      try {
        const notification = new Notification(title, {
          body: message,
          icon: icon || "/icons/icon-128x128.png",
          badge: "/icons/icon-72x72.png",
          tag: "floade-message",
          requireInteraction: false,
          silent: !this.soundEnabled,
        })

        // Auto-fermer après 5 secondes
        setTimeout(() => {
          notification.close()
        }, 5000)

        // Gérer le clic sur la notification
        notification.onclick = () => {
          window.focus()
          notification.close()
        }

        return notification
      } catch (error) {
        console.error("Erreur lors de l'affichage de la notification:", error)
      }
    }
    return null
  }

  notifyNewMessage(senderName, messageContent, senderAvatar = null, isFromCurrentConversation = false) {
    const truncatedMessage = messageContent.length > 50 ? messageContent.substring(0, 50) + "..." : messageContent

    // Gestion intelligente selon la page et le contexte
    if (this.currentPage === "conversation") {
      if (isFromCurrentConversation) {
        // Message de la conversation actuelle : juste vibration si page visible
        if (!document.hidden) {
          this.vibrate()
          return
        } else {
          // Page cachée : son + notification desktop
          this.playSound()
          this.showDesktopNotification(`${senderName}`, truncatedMessage, senderAvatar)
        }
      } else {
        // Message d'une autre conversation : toujours son + notification
        this.playSound()
        if (document.hidden) {
          this.showDesktopNotification(`Nouveau message de ${senderName}`, truncatedMessage, senderAvatar)
        }
      }
    } else {
      // Autres pages : toujours son + notification si page cachée
      this.playSound()
      if (document.hidden) {
        this.showDesktopNotification(`Nouveau message de ${senderName}`, truncatedMessage, senderAvatar)
      }
    }
  }

  setSoundEnabled(enabled) {
    this.soundEnabled = enabled
    this.saveUserPreferences()
  }

  setDesktopEnabled(enabled) {
    this.desktopEnabled = enabled
    this.saveUserPreferences()
  }

  // Test des notifications
  test() {
    this.playSound()
    this.showDesktopNotification("Test de notification", "Ceci est un test de notification Floade", null)
  }
}

// Instance globale
window.notificationManager = new NotificationManager()
