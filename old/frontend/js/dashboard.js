// frontend/js/dashboard.js
const API_BASE = "../backend/api"
let currentConversationId = null
let lastMessageId = 0
let websocket = null

// Vérifier l'authentification
if (!localStorage.getItem("user_id")) {
  window.location.href = "login.html"
}

// Initialisation
document.addEventListener("DOMContentLoaded", () => {
  initializeDashboard()
  setupEventListeners()
  connectWebSocket()
})

function initializeDashboard() {
  // Afficher les informations utilisateur
  const userName = document.getElementById("userName")
  if (userName) {
    userName.textContent = localStorage.getItem("user_pseudo") || "Utilisateur"
  }

  // Charger les conversations
  loadConversations()
}

function setupEventListeners() {
  // Bouton de déconnexion
  const logoutBtn = document.getElementById("logoutBtn")
  if (logoutBtn) {
    logoutBtn.addEventListener("click", logout)
  }

  // Bouton nouvelle conversation
  const newConversationBtn = document.getElementById("newConversationBtn")
  const newConversationModal = document.getElementById("newConversationModal")
  if (newConversationBtn && newConversationModal) {
    newConversationBtn.addEventListener("click", () => {
      newConversationModal.classList.add("show")
    })
  }

  // Fermer les modals
  const modalCloses = document.querySelectorAll(".modal-close")
  modalCloses.forEach((close) => {
    close.addEventListener("click", (e) => {
      const modal = e.target.closest(".modal")
      if (modal) {
        modal.classList.remove("show")
      }
    })
  })

  // Formulaire nouvelle conversation
  const newConversationForm = document.getElementById("newConversationForm")
  if (newConversationForm) {
    newConversationForm.addEventListener("submit", createConversation)
  }

  // Envoi de message
  const sendBtn = document.getElementById("sendBtn")
  const messageText = document.getElementById("messageText")
  if (sendBtn) {
    sendBtn.addEventListener("click", sendMessage)
  }
  if (messageText) {
    messageText.addEventListener("keypress", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault()
        sendMessage()
      }
    })
  }
}

async function loadConversations() {
  try {
    const response = await fetch(`${API_BASE}/chat/get_conversations.php`, {
      credentials: "include",
    })

    const result = await response.json()

    if (result.success) {
      displayConversations(result.conversations)
    } else {
      console.error("Erreur lors du chargement des conversations:", result.message)
    }
  } catch (error) {
    console.error("Erreur:", error)
  }
}

function displayConversations(conversations) {
  const conversationsList = document.getElementById("conversationsList")
  if (!conversationsList) return

  conversationsList.innerHTML = ""

  conversations.forEach((conversation) => {
    const conversationElement = createConversationElement(conversation)
    conversationsList.appendChild(conversationElement)
  })
}

function createConversationElement(conversation) {
  const div = document.createElement("div")
  div.className = "conversation-item"
  div.dataset.conversationId = conversation.id

  const avatarSrc = conversation.other_user_photo
    ? `../uploads/profiles/${conversation.other_user_photo}`
    : "../icons/icon-72x72.png"

  div.innerHTML = `
        <div class="conversation-avatar">
            <img src="${avatarSrc}" alt="Avatar">
        </div>
        <div class="conversation-info">
            <div class="conversation-name">${conversation.display_name}</div>
            <div class="conversation-last-message">${conversation.last_message}</div>
        </div>
        <div class="conversation-meta">
            <div class="conversation-time">${formatTime(conversation.last_message_time)}</div>
            ${conversation.unread_count > 0 ? `<div class="conversation-unread">${conversation.unread_count}</div>` : ""}
        </div>
    `

  div.addEventListener("click", () => {
    selectConversation(conversation.id, conversation.display_name)
  })

  return div
}

function selectConversation(conversationId, conversationName) {
  // Marquer comme active
  const conversationItems = document.querySelectorAll(".conversation-item")
  conversationItems.forEach((item) => {
    item.classList.remove("active")
    if (item.dataset.conversationId == conversationId) {
      item.classList.add("active")
    }
  })

  // Mettre à jour l'interface
  currentConversationId = conversationId
  const chatTitle = document.getElementById("chatTitle")
  const messageInput = document.getElementById("messageInput")

  if (chatTitle) {
    chatTitle.textContent = conversationName
  }

  if (messageInput) {
    messageInput.style.display = "flex"
  }

  // Charger les messages
  loadMessages(conversationId)
}

async function loadMessages(conversationId) {
  try {
    const response = await fetch(
      `${API_BASE}/chat/get_messages.php?conversation_id=${conversationId}&last_message_id=0&limit=50`,
      {
        credentials: "include",
      },
    )

    const result = await response.json()

    if (result.success) {
      displayMessages(result.messages)
      if (result.messages.length > 0) {
        lastMessageId = Math.max(...result.messages.map((m) => m.id))
      }
    } else {
      console.error("Erreur lors du chargement des messages:", result.message)
    }
  } catch (error) {
    console.error("Erreur:", error)
  }
}

function displayMessages(messages) {
  const messagesContainer = document.getElementById("messagesContainer")
  if (!messagesContainer) return

  messagesContainer.innerHTML = ""

  messages.forEach((message) => {
    const messageElement = createMessageElement(message)
    messagesContainer.appendChild(messageElement)
  })

  // Scroll vers le bas
  messagesContainer.scrollTop = messagesContainer.scrollHeight
}

function createMessageElement(message) {
  const div = document.createElement("div")
  const isOwn = message.sender_id == localStorage.getItem("user_id")
  div.className = `message ${isOwn ? "own" : ""}`

  const avatarSrc = message.sender_photo ? `../uploads/profiles/${message.sender_photo}` : "../icons/icon-72x72.png"

  div.innerHTML = `
        <div class="message-avatar">
            <img src="${avatarSrc}" alt="Avatar">
        </div>
        <div class="message-content">
            ${!isOwn ? `<div class="message-sender">${message.sender_pseudo}</div>` : ""}
            ${message.content ? `<div class="message-text">${message.content}</div>` : ""}
            ${message.image_path ? `<div class="message-image"><img src="../${message.image_path}" alt="Image"></div>` : ""}
            ${message.voice_path ? `<div class="message-voice"><audio controls><source src="../${message.voice_path}" type="audio/wav"></audio></div>` : ""}
            <div class="message-time">${formatTime(message.created_at)}</div>
        </div>
    `

  return div
}

async function sendMessage() {
  const messageText = document.getElementById("messageText")
  if (!messageText || !currentConversationId) return

  const content = messageText.value.trim()
  if (!content) return

  try {
    const response = await fetch(`${API_BASE}/chat/send_message.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "include",
      body: JSON.stringify({
        conversation_id: currentConversationId,
        content: content,
      }),
    })

    const result = await response.json()

    if (result.success) {
      messageText.value = ""
      // Le message sera ajouté via WebSocket
    } else {
      console.error("Erreur lors de l'envoi du message:", result.message)
    }
  } catch (error) {
    console.error("Erreur:", error)
  }
}

async function createConversation(e) {
  e.preventDefault()

  const formData = new FormData(e.target)
  const data = {
    name: formData.get("conversationName"),
    participants: formData.get("participants"),
  }

  try {
    const response = await fetch(`${API_BASE}/chat/create_conversation.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "include",
      body: JSON.stringify(data),
    })

    const result = await response.json()

    if (result.success) {
      // Fermer le modal
      const modal = document.getElementById("newConversationModal")
      if (modal) {
        modal.classList.remove("show")
      }

      // Recharger les conversations
      loadConversations()

      // Réinitialiser le formulaire
      e.target.reset()
    } else {
      alert("Erreur: " + result.message)
    }
  } catch (error) {
    console.error("Erreur:", error)
    alert("Erreur de connexion au serveur")
  }
}

function connectWebSocket() {
  try {
    websocket = new WebSocket("ws://localhost:8080")

    websocket.onopen = () => {
      console.log("WebSocket connecté")
      // S'authentifier
      websocket.send(
        JSON.stringify({
          type: "auth",
          user_id: localStorage.getItem("user_id"),
        }),
      )
    }

    websocket.onmessage = (event) => {
      const data = JSON.parse(event.data)
      handleWebSocketMessage(data)
    }

    websocket.onclose = () => {
      console.log("WebSocket déconnecté")
      // Tentative de reconnexion après 3 secondes
      setTimeout(connectWebSocket, 3000)
    }

    websocket.onerror = (error) => {
      console.error("Erreur WebSocket:", error)
    }
  } catch (error) {
    console.error("Erreur de connexion WebSocket:", error)
  }
}

function handleWebSocketMessage(data) {
  switch (data.type) {
    case "new_message":
      if (data.conversation_id == currentConversationId) {
        const messageElement = createMessageElement(data.message)
        const messagesContainer = document.getElementById("messagesContainer")
        if (messagesContainer) {
          messagesContainer.appendChild(messageElement)
          messagesContainer.scrollTop = messagesContainer.scrollHeight
        }
      }
      // Mettre à jour la liste des conversations
      loadConversations()
      break
  }
}

async function logout() {
  try {
    await fetch(`${API_BASE}/auth/logout.php`, {
      method: "POST",
      credentials: "include",
    })
  } catch (error) {
    console.error("Erreur lors de la déconnexion:", error)
  }

  // Nettoyer le localStorage
  localStorage.clear()

  // Fermer WebSocket
  if (websocket) {
    websocket.close()
  }

  // Rediriger vers la page de connexion
  window.location.href = "login.html"
}

function formatTime(timestamp) {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date

  if (diff < 60000) {
    // Moins d'une minute
    return "À l'instant"
  } else if (diff < 3600000) {
    // Moins d'une heure
    return Math.floor(diff / 60000) + "min"
  } else if (diff < 86400000) {
    // Moins d'un jour
    return Math.floor(diff / 3600000) + "h"
  } else {
    return date.toLocaleDateString()
  }
}
