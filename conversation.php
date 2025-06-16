<?php
require_once 'config/config.php';
require_once 'classes/Chat.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conversation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($conversation_id <= 0) {
    redirect('dashboard.php');
}

$chat = new Chat();

// Vérifier que l'utilisateur fait partie de la conversation
if (!$chat->isUserInConversation($_SESSION['user_id'], $conversation_id)) {
    redirect('dashboard.php');
}

// Récupérer les informations de la conversation
$conversation_info = $chat->getConversationInfo($conversation_id);
$messages = $chat->getConversationMessages($conversation_id, $_SESSION['user_id'], 15);

// Marquer la conversation comme lue
$chat->markConversationAsRead($conversation_id, $_SESSION['user_id']);

// Récupérer les informations de l'autre utilisateur pour les conversations directes
$other_user = $chat->getOtherUserInConversation($conversation_id, $_SESSION['user_id']);

// Déterminer le nom d'affichage et l'avatar
$display_name = $conversation_info['name'];
$other_user_photo = null;

if (!$display_name && $other_user) {
    // Pour les conversations directes, utiliser le nom de l'autre participant
    $display_name = $other_user['pseudo'];
    $other_user_photo = $other_user['profile_photo'];
} elseif (!$display_name) {
    $display_name = 'Conversation';
}

// Fonction pour obtenir l'URL de la photo de profil
function getProfilePhotoUrl($photo)
{
    if ($photo && file_exists('uploads/profiles/' . $photo)) {
        return 'uploads/profiles/' . $photo;
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversation - Floade</title>

    <!-- Meta tags PWA -->
    <meta name="theme-color" content="#1a202c">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php require_once("./head.php") ?>
    <style>
        body {
            background: #1a202c;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .chat-container {
            background: #2d3748;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: #2d3748;
            border-bottom: 1px solid #4a5568;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background: #1a202c;
        }

        .chat-messages::-webkit-scrollbar {
            width: 4px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #4a5568;
            border-radius: 2px;
        }

        .message {
            margin-bottom: 1rem;
            display: flex;
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message.received {
            justify-content: flex-start;
        }

        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 1.25rem;
            position: relative;
            word-wrap: break-word;
        }

        .message.sent .message-bubble {
            background: #3182ce;
            color: white;
            border-bottom-right-radius: 0.5rem;
            overflow: hidden;
        }

        .message.received .message-bubble {
            background: #4a5568;
            color: #e2e8f0;
            border-bottom-left-radius: 0.5rem;
            overflow: hidden;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .message.sent .message-time {
            justify-content: flex-end;
        }

        .message.received .message-time {
            justify-content: flex-start;
        }

        .chat-input-container {
            background: #2d3748;
            padding: 1rem;
            border-top: 1px solid #4a5568;
        }

        .chat-input-wrapper {
            background: #4a5568;
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            padding: 0.5rem;
        }

        .chat-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #e2e8f0;
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
        }

        .chat-input::placeholder {
            color: #a0aec0;
        }

        .send-button {
            background: #3182ce;
            color: white;
            border: none;
            border-radius: 50%;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .send-button:hover {
            background: #2c5aa0;
            transform: scale(1.05);
        }

        .send-button:disabled {
            background: #4a5568;
            cursor: not-allowed;
            transform: none;
        }

        .avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
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

        .online-dot {
            width: 0.75rem;
            height: 0.75rem;
            background: #48bb78;
            border: 2px solid #2d3748;
            border-radius: 50%;
            position: absolute;
            bottom: 0;
            right: 0;
        }

        .typing-indicator {
            display: none;
            align-items: center;
            gap: 0.5rem;
            color: #a0aec0;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .typing-indicator.show {
            display: flex;
        }

        .typing-dots {
            display: flex;
            gap: 0.25rem;
        }

        .typing-dot {
            width: 0.5rem;
            height: 0.5rem;
            background: #a0aec0;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes typing {

            0%,
            80%,
            100% {
                transform: scale(0.8);
                opacity: 0.5;
            }

            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .connection-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .status-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
        }

        .status-dot.connected {
            background: #48bb78;
        }

        .status-dot.disconnected {
            background: #f56565;
        }

        .status-dot.connecting {
            background: #ed8936;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-button {
            background: transparent;
            border: none;
            color: #a0aec0;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .header-button:hover {
            background: #4a5568;
            color: #e2e8f0;
        }

        .back-button {
            background: transparent;
            border: none;
            color: #a0aec0;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
            margin-right: 1rem;
        }

        .back-button:hover {
            background: #4a5568;
            color: #e2e8f0;
        }

        .attach-button {
            background: transparent;
            border: none;
            color: #a0aec0;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .attach-button:hover {
            background: #4a5568;
            color: #e2e8f0;
        }

        .message-status {
            font-size: 0.75rem;
            margin-left: 0.25rem;
        }

        .message-status.sent {
            color: #a0aec0;
        }

        .message-status.delivered {
            color: #68d391;
        }

        .message-status.read {
            color: #3182ce;
        }

        .message-reply {
            background: rgba(59, 130, 246, 0.15);
            border-left: 4px solid #3182ce;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .message-reply::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #3182ce, transparent);
            border-radius: 0.75rem 0.75rem 0 0;
        }

        .message-reply .reply-author {
            color: #3182ce;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .message-reply .reply-author::before {
            content: '↳';
            font-size: 1rem;
            opacity: 0.7;
        }

        .message-reply .reply-content {
            color: #e2e8f0;
            line-height: 1.4;
            font-style: italic;
            background: rgba(0, 0, 0, 0.2);
            padding: 0.5rem;
            border-radius: 0.5rem;
            border-left: 2px solid rgba(59, 130, 246, 0.3);
        }

        .message-reply .reply-image-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #60a5fa;
            font-weight: 500;
        }

        .message-reply .reply-image-indicator i {
            font-size: 1rem;
        }

        .message-image {
            max-width: 100%;
            width: auto;
            height: auto;
            max-height: 300px;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: transform 0.2s;
            display: block;
        }

        .message-image:hover {
            transform: scale(1.02);
        }

        .message-actions {
            position: absolute;
            top: -2rem;
            right: 0;
            background: #2d3748;
            border-radius: 0.5rem;
            padding: 0.25rem;
            display: none;
            gap: 0.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .message:hover .message-actions {
            display: flex;
        }

        .message-action-btn {
            background: transparent;
            border: none;
            color: #a0aec0;
            padding: 0.25rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .message-action-btn:hover {
            background: #4a5568;
            color: #e2e8f0;
        }

        .reply-preview {
            background: rgba(59, 130, 246, 0.15);
            border-left: 4px solid #3182ce;
            padding: 1rem;
            margin: 0 1rem 0.5rem 1rem;
            border-radius: 0.75rem;
            display: none;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .reply-preview::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #3182ce, transparent);
            border-radius: 0.75rem 0.75rem 0 0;
        }

        .reply-preview .reply-to-author {
            color: #3182ce;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .reply-preview .reply-to-author::before {
            content: '↳ Réponse à';
            font-size: 0.75rem;
            opacity: 0.7;
            text-transform: none;
            font-weight: 400;
        }

        .reply-preview .reply-to-content {
            color: #e2e8f0;
            font-size: 0.875rem;
            line-height: 1.4;
            font-style: italic;
            background: rgba(0, 0, 0, 0.2);
            padding: 0.5rem;
            border-radius: 0.5rem;
            border-left: 2px solid rgba(59, 130, 246, 0.3);
        }

        .reply-close-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(239, 68, 68, 0.2);
            border: none;
            color: #f87171;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.25rem;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            transition: all 0.2s;
        }

        .reply-close-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            transform: scale(1.1);
        }

        .message.deleted .message-bubble {
            background: rgba(156, 163, 175, 0.3) !important;
            font-style: italic;
            color: #9ca3af !important;
        }

        .image-upload-area {
            display: none;
            background: #4a5568;
            border: 2px dashed #718096;
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            margin: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .image-upload-area:hover {
            border-color: #3182ce;
            background: rgba(59, 130, 246, 0.1);
        }

        .image-upload-area.dragover {
            border-color: #3182ce;
            background: rgba(59, 130, 246, 0.2);
        }

        .image-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 0.5rem;
        }

        .image-modal-close {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 3rem;
            height: 3rem;
            font-size: 1.25rem;
            cursor: pointer;
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .new-message-indicator {
            position: relative;
        }

        .vibrate {
            animation: vibrate 0.3s ease-in-out;
        }

        @keyframes vibrate {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-2px);
            }

            75% {
                transform: translateX(2px);
            }
        }

        /* Styles pour les messages vocaux */
        .voice-message {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 1rem;
            max-width: 250px;
        }

        .voice-play-button {
            background: #3182ce;
            color: white;
            border: none;
            border-radius: 50%;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .voice-play-button:hover {
            background: #2c5aa0;
            transform: scale(1.05);
        }

        .voice-waveform {
            flex: 1;
            height: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            position: relative;
            overflow: hidden;
        }

        .voice-progress {
            height: 100%;
            background: #3182ce;
            border-radius: 1rem;
            transition: width 0.1s;
        }

        .voice-duration {
            font-size: 0.75rem;
            color: #a0aec0;
        }

        /* Styles pour les appels */
        .call-controls {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1rem;
            background: rgba(0, 0, 0, 0.8);
            padding: 1rem;
            border-radius: 2rem;
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .call-button {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.25rem;
        }

        .call-button.voice {
            background: #10b981;
            color: white;
        }

        .call-button.muted {
            background: #ef4444;
        }

        .call-button.end {
            background: #ef4444;
            color: white;
        }

        .call-button:hover {
            transform: scale(1.1);
        }

        /* Styles pour l'enregistrement vocal */
        .voice-recording {
            display: none;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #ef4444;
            border-radius: 1rem;
            margin: 1rem;
            color: white;
        }

        .voice-recording.active {
            display: flex;
        }

        .recording-indicator {
            width: 1rem;
            height: 1rem;
            background: white;
            border-radius: 50%;
            animation: pulse 1s infinite;
        }

        .recording-timer {
            font-weight: 600;
            font-family: monospace;
        }

        .recording-controls {
            display: flex;
            gap: 0.5rem;
        }

        .recording-button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .recording-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Styles pour la prévisualisation d'image */
        #imagePreviewModal {
            backdrop-filter: blur(10px);
        }

        #imagePreviewModal .bg-gray-800 {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        #imagePreviewModal img {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Animation pour l'indicateur d'envoi */
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        /* Styles pour la barre de progression */
        .progress-bar {
            transition: width 0.3s ease;
        }

        /* Message temporaire d'envoi */
        #tempImageMessage {
            opacity: 0.8;
        }

        #tempImageMessage .message-bubble {
            border: 1px dashed rgba(59, 130, 246, 0.3);
        }

        /* Amélioration de l'apparence des boutons */
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        button:not(:disabled):hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Animation d'apparition pour la modal */
        #imagePreviewModal {
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Modal d'options */
        .options-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .options-modal.show {
            display: flex;
        }

        .options-content {
            background: #2d3748;
            border-radius: 1rem;
            padding: 1.5rem;
            max-width: 300px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .options-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .options-header h3 {
            color: #e2e8f0;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .options-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .option-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #4a5568;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            color: #e2e8f0;
            text-align: left;
            width: 100%;
        }

        .option-item:hover {
            background: #5a6578;
            transform: translateY(-1px);
        }

        .option-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .option-icon.voice {
            background: #10b981;
            color: white;
        }

        .option-text {
            flex: 1;
        }

        .option-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .option-description {
            font-size: 0.875rem;
            color: #a0aec0;
        }

        .close-options {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: transparent;
            border: none;
            color: #a0aec0;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .close-options:hover {
            background: #4a5568;
            color: #e2e8f0;
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <!-- Header -->
        <div class="chat-header">
            <div class="flex items-center">
                <button class="back-button" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-arrow-left"></i>
                </button>

                <div class="relative mr-3">
                    <div class="avatar avatar-blue text-white">
                        <?php
                        $otherUserPhotoUrl = getProfilePhotoUrl($other_user_photo);
                        if ($otherUserPhotoUrl): ?>
                            <img src="<?php echo htmlspecialchars($otherUserPhotoUrl); ?>" alt="Photo de profil">
                        <?php else: ?>
                            <?php echo strtoupper(substr($display_name, 0, 2)); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($other_user && $other_user['is_online']): ?>
                        <div class="online-dot"></div>
                    <?php endif; ?>
                </div>

                <div>
                    <h2 class="text-white font-semibold text-lg">
                        <?php echo htmlspecialchars($display_name); ?>
                    </h2>
                    <?php if ($other_user): ?>
                        <p class="text-sm <?php echo $other_user['is_online'] ? 'text-green-400' : 'text-gray-400'; ?>">
                            <?php echo $other_user['is_online'] ? 'En ligne' : 'Hors ligne'; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="header-actions">
                <div id="connectionStatus" class="connection-status">
                    <div class="status-dot connecting"></div>
                    <span class="text-gray-400">Connexion...</span>
                </div>

                <div id="newMessageIndicator" class="hidden">
                    <button class="header-button text-blue-400 pulse-animation" onclick="showNewMessagesList()">
                        <i class="fas fa-bell"></i>
                        <span id="newMessageCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
                    </button>
                </div>

                <button class="header-button" onclick="toggleOptionsModal()">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div id="messagesContainer" class="chat-messages">
            <!-- Loader pour charger plus de messages -->
            <div id="loadMoreLoader" class="hidden text-center py-4">
                <div class="inline-flex items-center space-x-2 text-gray-400">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-400"></div>
                    <span class="text-sm">Chargement des messages...</span>
                </div>
            </div>

            <!-- Indicateur de fin de messages -->
            <div id="noMoreMessages" class="hidden text-center py-4">
                <p class="text-gray-500 text-sm">
                    <i class="fas fa-check-circle mr-2"></i>
                    Début de la conversation
                </p>
            </div>

            <!-- Messages existants -->
            <?php if (empty($messages)): ?>
                <div class="text-center text-gray-400 py-8">
                    <i class="fas fa-comments text-4xl mb-4 opacity-50"></i>
                    <p class="text-lg">Aucun message dans cette conversation.</p>
                    <p class="text-sm">Soyez le premier à envoyer un message !</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <!-- Code des messages existant reste identique -->
                    <?php if ($message['is_deleted']): ?>
                        <div class="message deleted <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                            <div class="message-bubble">
                                <div><i class="fas fa-trash mr-2"></i>Ce message a été supprimé</div>
                                <div class="message-time">
                                    <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="message <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>" data-message-id="<?php echo $message['id']; ?>">
                            <!-- Contenu du message reste identique -->
                            <div class="message-bubble" style="position: relative;">
                                <!-- Actions de message -->
                                <?php if ($message['sender_id'] == $_SESSION['user_id']): ?>
                                    <div class="message-actions">
                                        <button class="message-action-btn" onclick="replyToMessage(<?php echo $message['id']; ?>, '<?php echo addslashes($message['sender_pseudo']); ?>', '<?php echo addslashes($message['content']); ?>')">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                        <button class="message-action-btn" onclick="deleteMessage(<?php echo $message['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="message-actions">
                                        <button class="message-action-btn" onclick="replyToMessage(<?php echo $message['id']; ?>, '<?php echo addslashes($message['sender_pseudo']); ?>', '<?php echo addslashes($message['content']); ?>')">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <!-- Message de réponse -->
                                <?php if ($message['reply_to_message_id']): ?>
                                    <div class="message-reply">
                                        <div class="reply-author"><?php echo htmlspecialchars($message['reply_user_pseudo'] ?: 'Utilisateur supprimé'); ?></div>
                                        <div class="reply-content">
                                            <?php if ($message['reply_image_path']): ?>
                                                <div class="reply-image-indicator">
                                                    <i class="fas fa-image"></i>
                                                    <span>Image partagée</span>
                                                </div>
                                            <?php elseif ($message['reply_voice_path']): ?>
                                                <div class="reply-image-indicator">
                                                    <i class="fas fa-microphone"></i>
                                                    <span>Message vocal</span>
                                                </div>
                                            <?php else: ?>
                                                <?php
                                                $replyContent = $message['reply_content'] ?: 'Message supprimé';
                                                echo htmlspecialchars(strlen($replyContent) > 150 ? substr($replyContent, 0, 150) . '...' : $replyContent);
                                                ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Message vocal -->
                                <?php if ($message['voice_path']): ?>
                                    <div class="voice-message mb-2">
                                        <button onclick="playVoiceMessage('<?php echo htmlspecialchars($message['voice_path']); ?>', this)"
                                            class="voice-play-button">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <div class="voice-waveform">
                                            <div class="voice-progress" style="width: 0%"></div>
                                        </div>
                                        <div class="voice-duration">
                                            <?php echo isset($message['voice_duration']) ? gmdate("i:s", $message['voice_duration']) : '0:00'; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Contenu du message -->
                                <?php if ($message['image_path']): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($message['image_path']); ?>"
                                            alt="Image"
                                            class="message-image"
                                            onclick="openImageModal('<?php echo htmlspecialchars($message['image_path']); ?>')">
                                    </div>
                                <?php endif; ?>

                                <?php if ($message['content']): ?>
                                    <div><?php echo nl2br(htmlspecialchars($message['content'])); ?></div>
                                <?php endif; ?>

                                <div class="message-time">
                                    <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                    <?php if ($message['sender_id'] == $_SESSION['user_id']): ?>
                                        <i class="fas fa-check message-status read"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Indicateur de frappe -->
            <div id="typingIndicator" class="typing-indicator">
                <span id="typingUser">Marie</span>
                <span>est en train d'écrire</span>
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        </div>

        <!-- Prévisualisation de réponse -->
        <div id="replyPreview" class="reply-preview">
            <button class="reply-close-btn" onclick="cancelReply()">
                <i class="fas fa-times"></i>
            </button>
            <div class="reply-to-author" id="replyToAuthor"></div>
            <div class="reply-to-content" id="replyToContent"></div>
        </div>

        <!-- Zone d'upload d'image -->
        <div id="imageUploadArea" class="image-upload-area">
            <i class="fas fa-cloud-upload-alt text-3xl text-blue-400 mb-2"></i>
            <p class="text-gray-400">Glissez-déposez une image ou cliquez pour sélectionner</p>
            <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF ou WebP (max. 5MB)</p>
        </div>

        <!-- Zone de saisie -->
        <div class="chat-input-container">
            <!-- Zone d'enregistrement vocal -->
            <div id="voiceRecording" class="voice-recording">
                <div class="recording-indicator"></div>
                <span class="recording-timer" id="recordingTimer">00:00</span>
                <div class="recording-controls">
                    <button class="recording-button" onclick="stopRecording()" title="Arrêter">
                        <i class="fas fa-stop"></i>
                    </button>
                    <button class="recording-button" onclick="cancelRecording()" title="Annuler">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <form id="messageForm" class="chat-input-wrapper">
                <button type="button" class="attach-button" onclick="toggleImageUpload()">
                    <i class="fas fa-image"></i>
                </button>

                <button type="button" class="attach-button" onclick="startVoiceRecording()" id="voiceButton">
                    <i class="fas fa-microphone"></i>
                </button>

                <input type="text"
                    id="messageInput"
                    class="chat-input"
                    placeholder="Tapez votre message..."
                    maxlength="1000">

                <button type="submit" id="sendButton" class="send-button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>

            <input type="file" id="imageInput" accept="image/*" style="display: none;">
        </div>
    </div>

    <!-- Modal d'affichage d'image -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <button class="image-modal-close" onclick="closeImageModal()">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="/placeholder.svg" alt="Image">
    </div>

    <!-- Modal d'options -->
    <div id="optionsModal" class="options-modal" onclick="closeOptionsModal(event)">
        <div class="options-content" onclick="event.stopPropagation()">
            <button class="close-options" onclick="closeOptionsModal()">
                <i class="fas fa-times"></i>
            </button>

            <div class="options-header">
                <h3>Options</h3>
            </div>

            <div class="options-list">
                <button class="option-item" onclick="startVoiceCall()">
                    <div class="option-icon voice">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="option-text">
                        <div class="option-title">Appel vocal</div>
                        <div class="option-description">Démarrer un appel vocal</div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Charger les notifications -->
    <script src="./notifications/notification.js"></script>

    <script>
        // Exposer l'ID utilisateur pour les notifications push
        window.USER_ID = <?php echo $_SESSION['user_id']; ?>;
    </script>

    <script>
        // Configuration
        const WEBSOCKET_URL = 'wss://https://1e42-41-85-163-114.ngrok-free.app';
        const USER_ID = <?php echo $_SESSION['user_id']; ?>;
        const CONVERSATION_ID = <?php echo $conversation_id; ?>;
        const USER_PSEUDO = '<?php echo addslashes($_SESSION['user_pseudo']); ?>';

        // Variables globales
        let websocket = null;
        let isConnected = false;
        let isAuthenticated = false;
        let typingTimeout = null;
        let reconnectTimeout = null;
        let pingInterval = null;
        let reconnectAttempts = 0;
        let maxReconnectAttempts = 5;
        let lastMessageId = 0;

        // Variables pour les appels
        let currentCall = null;
        let localStream = null;

        // Éléments DOM
        const messagesContainer = document.getElementById('messagesContainer');
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const connectionStatus = document.getElementById('connectionStatus');
        const typingIndicator = document.getElementById('typingIndicator');

        // Initialiser la dernière ID de message
        const messages = document.querySelectorAll('.message');
        if (messages.length > 0) {
            <?php if (!empty($messages)): ?>
                lastMessageId = <?php echo end($messages)['id']; ?>;
            <?php endif; ?>
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            connectWebSocket();
            setupEventListeners();
            scrollToBottom();

            // Polling de secours si WebSocket ne fonctionne pas
            setInterval(pollForNewMessages, 5000);
        });

        // Connexion WebSocket avec retry automatique
        function connectWebSocket() {
            try {
                console.log('Tentative de connexion WebSocket...');
                websocket = new WebSocket(WEBSOCKET_URL);

                websocket.onopen = function() {
                    console.log('WebSocket connecté');
                    isConnected = true;
                    reconnectAttempts = 0;
                    updateConnectionStatus('connected', 'Connecté');

                    // Démarrer le ping pour maintenir la connexion
                    startPing();

                    // Authentification immédiate
                    authenticate();
                };

                websocket.onmessage = function(event) {
                    try {
                        const data = JSON.parse(event.data);
                        handleWebSocketMessage(data);
                    } catch (e) {
                        console.error('Erreur parsing message WebSocket:', e);
                    }
                };

                websocket.onclose = function(event) {
                    console.log('WebSocket déconnecté', event.code, event.reason);
                    isConnected = false;
                    isAuthenticated = false;
                    updateConnectionStatus('disconnected', 'Déconnecté');

                    // Arrêter le ping
                    if (pingInterval) {
                        clearInterval(pingInterval);
                        pingInterval = null;
                    }

                    // Tentative de reconnexion
                    if (reconnectAttempts < maxReconnectAttempts) {
                        const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 10000);
                        reconnectTimeout = setTimeout(() => {
                            reconnectAttempts++;
                            console.log(`Tentative de reconnexion ${reconnectAttempts}/${maxReconnectAttempts}`);
                            updateConnectionStatus('connecting', `Reconnexion... (${reconnectAttempts}/${maxReconnectAttempts})`);
                            connectWebSocket();
                        }, delay);
                    } else {
                        updateConnectionStatus('disconnected', 'Connexion échouée');
                    }
                };

                websocket.onerror = function(error) {
                    console.error('Erreur WebSocket:', error);
                    isConnected = false;
                    isAuthenticated = false;
                    updateConnectionStatus('disconnected', 'Erreur de connexion');
                };

            } catch (e) {
                console.error('Erreur création WebSocket:', e);
                updateConnectionStatus('disconnected', 'Erreur de connexion');
            }
        }

        // Authentification
        function authenticate() {
            if (!isConnected || isAuthenticated) return;

            console.log('Envoi authentification...');
            const authMessage = {
                type: 'auth',
                user_id: USER_ID,
                conversation_id: CONVERSATION_ID
            };

            try {
                websocket.send(JSON.stringify(authMessage));
            } catch (e) {
                console.error('Erreur envoi authentification:', e);
            }
        }

        // Ping pour maintenir la connexion
        function startPing() {
            if (pingInterval) {
                clearInterval(pingInterval);
            }

            pingInterval = setInterval(() => {
                if (isConnected && websocket.readyState === WebSocket.OPEN) {
                    try {
                        websocket.send(JSON.stringify({
                            type: 'ping'
                        }));
                    } catch (e) {
                        console.error('Erreur ping:', e);
                    }
                }
            }, 30000); // Ping toutes les 30 secondes
        }

        // Variables pour la gestion des notifications
        let currentConversationId = CONVERSATION_ID;
        let newMessagesFromOtherConversations = 0;

        // Gérer les messages WebSocket
        function handleWebSocketMessage(data) {
            console.log('Message WebSocket reçu:', data);

            switch (data.type) {
                case 'connection_established':
                    console.log('Connexion établie');
                    break;

                case 'success':
                    console.log('Succès:', data.message);
                    if (data.message === 'Authentification réussie') {
                        isAuthenticated = true;
                        updateConnectionStatus('connected', 'Connecté');
                    }
                    break;

                case 'error':
                    console.error('Erreur WebSocket:', data.message);
                    if (data.message.includes('non authentifié')) {
                        isAuthenticated = false;
                        setTimeout(authenticate, 1000);
                    }
                    break;

                case 'new_message':
                    if (data.message) {
                        handleNewMessage(data.message);
                    }
                    break;

                case 'new_message_other_conversation':
                    handleNewMessageFromOtherConversation(data);
                    break;

                case 'typing':
                    handleTypingIndicator(data);
                    break;

                case 'call_invite':
                    handleCallInvite(data);
                    break;

                case 'call_accepted':
                    handleCallAccepted(data);
                    break;

                case 'call_rejected':
                    handleCallRejected(data);
                    break;

                case 'call_ended':
                    handleCallEnded(data);
                    break;

                case 'user_joined':
                    console.log('Utilisateur rejoint:', data.user_id);
                    break;

                case 'user_left':
                    console.log('Utilisateur parti:', data.user_id);
                    break;

                case 'pong':
                    // Réponse au ping
                    break;

                default:
                    console.log('Type de message non géré:', data.type);
            }
        }

        function handleNewMessage(message) {
            addMessageToUI(message);

            // Si c'est un message de quelqu'un d'autre dans cette conversation
            if (message.sender_id != USER_ID) {
                // Juste une vibration, pas de son
                if (navigator.vibrate) {
                    navigator.vibrate(200);
                }

                // Effet visuel de vibration
                document.body.classList.add('vibrate');
                setTimeout(() => {
                    document.body.classList.remove('vibrate');
                }, 300);
            }
        }

        function handleNewMessageFromOtherConversation(data) {
            newMessagesFromOtherConversations++;

            // Afficher l'indicateur de nouveaux messages
            const indicator = document.getElementById('newMessageIndicator');
            const countElement = document.getElementById('newMessageCount');

            if (indicator && countElement) {
                indicator.classList.remove('hidden');
                countElement.textContent = newMessagesFromOtherConversations;
            }

            // Jouer le son de notification
            if (window.notificationManager) {
                window.notificationManager.notifyNewMessage(
                    data.sender_name || 'Utilisateur',
                    data.message_content || 'Nouveau message'
                );
            }
        }

        function showNewMessagesList() {
            // Rediriger vers le dashboard pour voir les nouveaux messages
            window.location.href = 'dashboard.php';
        }

        // Réinitialiser le compteur quand on quitte la page
        window.addEventListener('beforeunload', function() {
            newMessagesFromOtherConversations = 0;
        });

        // Configuration des événements
        function setupEventListeners() {
            // Soumission du formulaire
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });

            // Indicateur de frappe
            let typingTimer;
            let isTyping = false;

            messageInput.addEventListener('input', function() {
                if (isConnected && isAuthenticated) {
                    if (!isTyping) {
                        isTyping = true;
                        sendTypingStatus(true);
                    }

                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(() => {
                        isTyping = false;
                        sendTypingStatus(false);
                    }, 1000);
                }
            });

            // Envoyer avec Entrée
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Focus automatique sur le champ de saisie
            messageInput.focus();

            // Configurer la pagination par scroll
            setupScrollPagination();

            // Initialiser les fonctionnalités
            setupImageUpload();
        }

        // Envoyer le statut de frappe
        function sendTypingStatus(typing) {
            if (isConnected && isAuthenticated && websocket.readyState === WebSocket.OPEN) {
                try {
                    websocket.send(JSON.stringify({
                        type: 'typing',
                        is_typing: typing
                    }));
                } catch (e) {
                    console.error('Erreur envoi statut frappe:', e);
                }
            }
        }

        // Variables pour éviter les envois doubles
        let isSending = false;
        let lastSentMessage = '';
        let lastSentTime = 0;

        // Modifier la fonction sendMessage
        function sendMessage() {
            // Éviter les envois doubles
            if (isSending) {
                console.log('Envoi en cours, ignoré');
                return;
            }

            const content = messageInput.value.trim();

            if (!content) return;

            // Vérifier si c'est un message dupliqué
            const now = Date.now();
            if (content === lastSentMessage && (now - lastSentTime) < 2000) {
                console.log('Message dupliqué détecté, ignoré');
                return;
            }

            if (content.length > 1000) {
                alert('Le message est trop long (maximum 1000 caractères)');
                return;
            }

            isSending = true;
            lastSentMessage = content;
            lastSentTime = now;
            sendButton.disabled = true;

            if (isConnected && isAuthenticated && websocket.readyState === WebSocket.OPEN) {
                try {
                    const messageData = {
                        type: 'message',
                        content: content,
                        reply_to_message_id: replyToMessageId
                    };
                    websocket.send(JSON.stringify(messageData));
                    messageInput.value = '';
                    cancelReply();
                } catch (e) {
                    console.error('Erreur envoi WebSocket:', e);
                    sendMessageViaAPI(content, replyToMessageId);
                }
            } else {
                sendMessageViaAPI(content, replyToMessageId);
            }

            // Réactiver après 1 seconde
            setTimeout(() => {
                isSending = false;
                sendButton.disabled = false;
            }, 1000);
        }

        // Envoyer via API REST (fallback)
        function sendMessageViaAPI(content, replyId = null, imagePath = null, voicePath = null) {
            const requestData = {
                conversation_id: CONVERSATION_ID,
                content: content,
                reply_to_message_id: replyId,
                image_path: imagePath,
                voice_path: voicePath
            };

            console.log('Données API:', requestData);

            fetch('api/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Réponse API:', data);
                    if (data.success) {
                        if (data.message) {
                            console.log('Message reçu:', data.message);
                            // Supprimer le message temporaire s'il existe
                            const tempMessage = document.getElementById('tempImageMessage');
                            if (tempMessage) {
                                tempMessage.remove();
                            }
                            addMessageToUI(data.message);
                        }
                        messageInput.value = '';
                        cancelReply();
                    } else {
                        alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur envoi message API:', error);
                    alert('Erreur lors de l\'envoi du message');
                })
                .finally(() => {
                    sendButton.disabled = false;
                    isSending = false;
                });
        }

        // Ajouter un message à l'interface
        function addMessageToUI(message) {
            if (message.is_deleted) {
                return; // Ne pas afficher les messages supprimés
            }

            // Vérifier si le message existe déjà
            const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
            if (existingMessage) {
                console.log('Message déjà affiché, ignoré');
                return;
            }

            const isOwnMessage = message.sender_id == USER_ID;

            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isOwnMessage ? 'sent' : 'received'}`;
            messageDiv.setAttribute('data-message-id', message.id);

            const messageBubble = document.createElement('div');
            messageBubble.className = 'message-bubble';
            messageBubble.style.position = 'relative';

            // Actions de message
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'message-actions';

            if (isOwnMessage) {
                actionsDiv.innerHTML = `
                    <button class="message-action-btn" onclick="replyToMessage(${message.id}, '${escapeHtml(message.sender_pseudo)}', '${escapeHtml(message.content || message.voice_path ? 'Message vocal' : 'Image')}')">
                        <i class="fas fa-reply"></i>
                    </button>
                    <button class="message-action-btn" onclick="deleteMessage(${message.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            } else {
                actionsDiv.innerHTML = `
                    <button class="message-action-btn" onclick="replyToMessage(${message.id}, '${escapeHtml(message.sender_pseudo)}', '${escapeHtml(message.content || message.voice_path ? 'Message vocal' : 'Image')}')">
                        <i class="fas fa-reply"></i>
                    </button>
                `;
            }

            messageBubble.appendChild(actionsDiv);

            // Message de réponse
            if (message.reply_to_message_id) {
                const replyDiv = document.createElement('div');
                replyDiv.className = 'message-reply';

                const replyAuthor = document.createElement('div');
                replyAuthor.className = 'reply-author';
                replyAuthor.textContent = message.reply_user_pseudo || 'Utilisateur supprimé';

                const replyContent = document.createElement('div');
                replyContent.className = 'reply-content';

                if (message.reply_image_path) {
                    replyContent.innerHTML = `
                        <div class="reply-image-indicator">
                            <i class="fas fa-image"></i>
                            <span>Image partagée</span>
                        </div>
                    `;
                } else if (message.reply_voice_path) {
                    replyContent.innerHTML = `
                        <div class="reply-image-indicator">
                            <i class="fas fa-microphone"></i>
                            <span>Message vocal</span>
                        </div>
                    `;
                } else {
                    const content = message.reply_content || 'Message supprimé';
                    replyContent.textContent = content.length > 150 ? content.substring(0, 150) + '...' : content;
                }

                replyDiv.appendChild(replyAuthor);
                replyDiv.appendChild(replyContent);
                messageBubble.appendChild(replyDiv);
            }

            // Message vocal
            if (message.voice_path) {
                const voiceDiv = document.createElement('div');
                voiceDiv.className = 'voice-message mb-2';
                voiceDiv.innerHTML = `
                    <button onclick="playVoiceMessage('${escapeHtml(message.voice_path)}', this)" 
                            class="voice-play-button">
                        <i class="fas fa-play"></i>
                    </button>
                    <div class="voice-waveform">
                        <div class="voice-progress" style="width: 0%"></div>
                    </div>
                    <div class="voice-duration">
                        ${formatVoiceDuration(message.voice_duration || 0)}
                    </div>
                `;
                messageBubble.appendChild(voiceDiv);
            }

            // Image
            if (message.image_path) {
                const imageDiv = document.createElement('div');
                imageDiv.className = 'mb-2';
                imageDiv.innerHTML = `
                    <img src="${escapeHtml(message.image_path)}" 
                         alt="Image" 
                         class="message-image"
                         onclick="openImageModal('${escapeHtml(message.image_path)}')">
                `;
                messageBubble.appendChild(imageDiv);
            }

            // Contenu texte
            if (message.content) {
                const contentDiv = document.createElement('div');
                contentDiv.innerHTML = escapeHtml(message.content).replace(/\n/g, '<br>');
                messageBubble.appendChild(contentDiv);
            }

            // Heure
            const messageTime = document.createElement('div');
            messageTime.className = 'message-time';
            messageTime.innerHTML = `
                ${formatTime(message.created_at)}
                ${isOwnMessage ? '<i class="fas fa-check message-status read"></i>' : ''}
            `;
            messageBubble.appendChild(messageTime);

            messageDiv.appendChild(messageBubble);

            // Insérer avant l'indicateur de frappe
            messagesContainer.insertBefore(messageDiv, typingIndicator);

            if (message.id) {
                lastMessageId = Math.max(lastMessageId, message.id);
            }
            scrollToBottom();
        }

        // Polling pour nouveaux messages (fallback)
        function pollForNewMessages() {
            if (isConnected && isAuthenticated) return; // Ne pas faire de polling si WebSocket fonctionne

            fetch(`api/get_messages.php?conversation_id=${CONVERSATION_ID}&last_message_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages && data.messages.length > 0) {
                        data.messages.forEach(message => {
                            addMessageToUI(message);
                            // Notification pour les nouveaux messages via polling
                            if (window.notificationManager && message.sender_id != USER_ID) {
                                window.notificationManager.notifyNewMessage(
                                    message.sender_pseudo || 'Utilisateur',
                                    message.content
                                );
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Erreur polling messages:', error);
                });
        }

        // Gérer l'indicateur de frappe
        function handleTypingIndicator(data) {
            const typingUser = document.getElementById('typingUser');

            if (data.is_typing && data.user_id != USER_ID) {
                typingUser.textContent = `Utilisateur ${data.user_id}`;
                typingIndicator.classList.add('show');

                // Masquer après 3 secondes
                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(() => {
                    typingIndicator.classList.remove('show');
                }, 3000);
            } else if (data.user_id != USER_ID) {
                typingIndicator.classList.remove('show');
            }
        }

        // Mettre à jour le statut de connexion
        function updateConnectionStatus(status, statusText = null) {
            const statusDot = connectionStatus.querySelector('.status-dot');
            const statusTextElement = connectionStatus.querySelector('span');

            statusDot.className = `status-dot ${status}`;
            statusTextElement.textContent = statusText || status;
        }

        // Utilitaires
        function scrollToBottom() {
            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 100);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatVoiceDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        // Nettoyage à la fermeture de la page
        window.addEventListener('beforeunload', function() {
            if (websocket) {
                websocket.close();
            }
            if (pingInterval) {
                clearInterval(pingInterval);
            }
        });

        // Gestion de la visibilité de la page pour optimiser les ressources
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page cachée - réduire l'activité
                if (pingInterval) {
                    clearInterval(pingInterval);
                    pingInterval = null;
                }
            } else {
                // Page visible - reprendre l'activité normale
                if (isConnected && !pingInterval) {
                    startPing();
                }
            }
        });

        // Variables pour les réponses et images
        let replyToMessageId = null;

        // Fonctions pour gérer les réponses
        function replyToMessage(messageId, authorName, content) {
            console.log('Réponse à:', messageId, authorName, content);
            replyToMessageId = messageId;

            document.getElementById('replyToAuthor').textContent = authorName;
            document.getElementById('replyToContent').textContent = content.length > 100 ? content.substring(0, 100) + '...' : content;
            document.getElementById('replyPreview').style.display = 'block';

            messageInput.focus();
        }

        function cancelReply() {
            replyToMessageId = null;
            document.getElementById('replyPreview').style.display = 'none';
        }

        // Fonctions pour gérer les images
        function toggleImageUpload() {
            const uploadArea = document.getElementById('imageUploadArea');
            if (!uploadArea) {
                console.error('Zone d\'upload non trouvée');
                return;
            }

            const isVisible = uploadArea.style.display === 'block';
            uploadArea.style.display = isVisible ? 'none' : 'block';

            console.log('Toggle image upload:', !isVisible);
        }

        function setupImageUpload() {
            const uploadArea = document.getElementById('imageUploadArea');
            const imageInput = document.getElementById('imageInput');

            if (!uploadArea || !imageInput) {
                console.error('Éléments d\'upload d\'image non trouvés');
                return;
            }

            // Supprimer les anciens event listeners
            uploadArea.replaceWith(uploadArea.cloneNode(true));
            const newUploadArea = document.getElementById('imageUploadArea');

            imageInput.replaceWith(imageInput.cloneNode(true));
            const newImageInput = document.getElementById('imageInput');

            // Ajouter les nouveaux event listeners
            newUploadArea.addEventListener('click', () => {
                newImageInput.click();
            });

            newUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                newUploadArea.classList.add('dragover');
            });

            newUploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                newUploadArea.classList.remove('dragover');
            });

            newUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                newUploadArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleImageUpload(files[0]);
                }
            });

            newImageInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleImageUpload(e.target.files[0]);
                }
            });

            console.log('Setup image upload terminé');
        }

        function handleImageUpload(file) {
            // Validation
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('Le fichier est trop volumineux (maximum 5MB)');
                return;
            }

            // Créer une prévisualisation
            const reader = new FileReader();
            reader.onload = function(e) {
                showImagePreview(e.target.result, file);
            };
            reader.readAsDataURL(file);

            // Fermer la zone d'upload
            document.getElementById('imageUploadArea').style.display = 'none';
        }

        function showImagePreview(imageSrc, file) {
            // Créer la modal de prévisualisation
            const previewModal = document.createElement('div');
            previewModal.id = 'imagePreviewModal';
            previewModal.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center';

            previewModal.innerHTML = `
                <div class="bg-gray-800 rounded-xl p-6 max-w-lg w-full mx-4">
                    <div class="text-center mb-4">
                        <h3 class="text-xl font-semibold text-white mb-2">Envoyer cette image ?</h3>
                        <div class="bg-gray-700 rounded-lg p-4 mb-4">
                            <img src="${imageSrc}" alt="Prévisualisation" class="max-w-full max-h-64 mx-auto rounded-lg">
                        </div>
                        <div class="text-sm text-gray-400 mb-4">
                            <p><i class="fas fa-file-image mr-2"></i>${file.name}</p>
                            <p><i class="fas fa-weight mr-2"></i>${formatFileSize(file.size)}</p>
                        </div>
                        
                        <!-- Zone de texte optionnelle -->
                        <div class="mb-4">
                            <input type="text" 
                                   id="imageCaption" 
                                   placeholder="Ajouter une légende (optionnel)..." 
                                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div class="flex justify-center space-x-4">
                        <button onclick="cancelImagePreview()" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition-colors">
                            <i class="fas fa-times mr-2"></i>Annuler
                        </button>
                        
                        <button onclick="confirmImageSend()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>Envoyer
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(previewModal);

            // Focus sur le champ de légende
            document.getElementById('imageCaption').focus();

            // Permettre l'envoi avec Entrée
            document.getElementById('imageCaption').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    confirmImageSend();
                }
            });

            // Stocker le fichier pour l'envoi
            previewModal.imageFile = file;
        }

        function cancelImagePreview() {
            const modal = document.getElementById('imagePreviewModal');
            if (modal) {
                modal.remove();
            }
        }

        function confirmImageSend() {
            const modal = document.getElementById('imagePreviewModal');
            const caption = document.getElementById('imageCaption').value.trim();
            const file = modal.imageFile;

            if (!file) {
                alert('Erreur: fichier non trouvé');
                return;
            }

            // Fermer la modal
            modal.remove();

            // Afficher l'indicateur d'envoi
            showImageSendingIndicator(file, caption);

            // Uploader le fichier
            uploadImageFile(file, caption);
        }

        function showImageSendingIndicator(file, caption) {
            // Créer un message temporaire d'envoi en cours
            const tempMessageDiv = document.createElement('div');
            tempMessageDiv.className = 'message sent';
            tempMessageDiv.id = 'tempImageMessage';

            const messageBubble = document.createElement('div');
            messageBubble.className = 'message-bubble';
            messageBubble.style.position = 'relative';

            messageBubble.innerHTML = `
                <div class="mb-2">
                    <div class="bg-gray-600 rounded-lg p-4 max-w-xs">
                        <div class="flex items-center space-x-3">
                            <div class="animate-spin">
                                <i class="fas fa-spinner text-blue-400"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm text-gray-300 mb-1">Envoi en cours...</div>
                                <div class="text-xs text-gray-400">${file.name}</div>
                                <div class="w-full bg-gray-700 rounded-full h-2 mt-2">
                                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                         id="uploadProgress" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ${caption ? `<div class="text-sm mb-2">${escapeHtml(caption)}</div>` : ''}
                <div class="message-time">
                    ${formatTime(new Date())}
                    <i class="fas fa-clock message-status" style="color: #a0aec0;"></i>
                </div>
            `;

            tempMessageDiv.appendChild(messageBubble);

            // Insérer avant l'indicateur de frappe
            messagesContainer.insertBefore(tempMessageDiv, typingIndicator);
            scrollToBottom();
        }

        function uploadImageFile(file, caption) {
            const formData = new FormData();
            formData.append('image', file);

            // Créer une requête avec suivi de progression
            const xhr = new XMLHttpRequest();

            // Suivi de la progression
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    const progressBar = document.getElementById('uploadProgress');
                    if (progressBar) {
                        progressBar.style.width = percentComplete + '%';
                    }
                }
            });

            xhr.onload = function() {
                // Supprimer le message temporaire
                const tempMessage = document.getElementById('tempImageMessage');
                if (tempMessage) {
                    tempMessage.remove();
                }

                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Envoyer le message avec l'image
                            sendMessageWithUploadedImage(response.image_path, caption);
                        } else {
                            alert('Erreur: ' + response.message);
                        }
                    } catch (e) {
                        console.error('Erreur parsing réponse:', e);
                        alert('Erreur lors de l\'upload de l\'image');
                    }
                } else {
                    alert('Erreur lors de l\'upload de l\'image');
                }
            };

            xhr.onerror = function() {
                // Supprimer le message temporaire
                const tempMessage = document.getElementById('tempImageMessage');
                if (tempMessage) {
                    tempMessage.remove();
                }
                alert('Erreur lors de l\'upload de l\'image');
            };

            xhr.open('POST', 'api/upload_message_image.php');
            xhr.send(formData);
        }

        function sendMessageWithUploadedImage(imagePath, caption) {
            if (isConnected && isAuthenticated && websocket.readyState === WebSocket.OPEN) {
                try {
                    websocket.send(JSON.stringify({
                        type: 'message',
                        content: caption || '',
                        reply_to_message_id: replyToMessageId,
                        image_path: imagePath
                    }));

                    if (caption) {
                        messageInput.value = '';
                    }
                    cancelReply();
                } catch (e) {
                    console.error('Erreur envoi WebSocket:', e);
                    sendMessageViaAPI(caption || '', replyToMessageId, imagePath);
                }
            } else {
                sendMessageViaAPI(caption || '', replyToMessageId, imagePath);
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Fonction pour supprimer un message
        function deleteMessage(messageId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
                return;
            }

            fetch('api/delete_message.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message_id: messageId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour l'affichage du message
                        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                        if (messageElement) {
                            messageElement.classList.add('deleted');
                            const bubble = messageElement.querySelector('.message-bubble');
                            bubble.innerHTML = '<div><i class="fas fa-trash mr-2"></i>Ce message a été supprimé</div>';
                        }
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur suppression message:', error);
                    alert('Erreur lors de la suppression du message');
                });
        }

        // Fonctions pour la modal d'image
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').style.display = 'flex';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Fonctions pour la modal d'options
        function toggleOptionsModal() {
            const modal = document.getElementById('optionsModal');
            if (modal.classList.contains('show')) {
                closeOptionsModal();
            } else {
                modal.classList.add('show');
            }
        }

        function closeOptionsModal(event) {
            const modal = document.getElementById('optionsModal');
            modal.classList.remove('show');
        }

        // Fonctions pour les appels (vocal seulement)
        async function startVoiceCall() {
            closeOptionsModal(); // Fermer la modal d'options

            try {
                localStream = await navigator.mediaDevices.getUserMedia({
                    audio: true
                });
                initializeCall('voice');
            } catch (error) {
                console.error('Erreur lors de l\'accès au microphone:', error);
                alert('Impossible d\'accéder au microphone');
            }
        }

        function initializeCall(type) {
            currentCall = {
                type: type,
                startTime: Date.now(),
                status: 'calling'
            };

            // Afficher l'interface d'appel
            showCallInterface(type);

            // Envoyer l'invitation d'appel via WebSocket
            if (isConnected && isAuthenticated) {
                websocket.send(JSON.stringify({
                    type: 'call_invite',
                    call_type: type,
                    conversation_id: CONVERSATION_ID
                }));
            }
        }

        function showCallInterface(type) {
            // Créer l'interface d'appel (vocal seulement)
            const callInterface = document.createElement('div');
            callInterface.id = 'callInterface';
            callInterface.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 flex flex-col items-center justify-center';

            callInterface.innerHTML = `
                <div class="text-center text-white mb-8">
                    <h2 class="text-2xl font-bold mb-2">Appel vocal</h2>
                    <p class="text-gray-300">En cours d'appel...</p>
                </div>
                
                <div class="call-controls">
                    <button class="call-button voice" onclick="toggleMute()" id="muteButton">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button class="call-button end" onclick="endCall()">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(callInterface);
        }

        function toggleMute() {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                if (audioTrack) {
                    audioTrack.enabled = !audioTrack.enabled;
                    const button = document.getElementById('muteButton');
                    const icon = button.querySelector('i');
                    if (audioTrack.enabled) {
                        icon.className = 'fas fa-microphone';
                        button.classList.remove('muted');
                    } else {
                        icon.className = 'fas fa-microphone-slash';
                        button.classList.add('muted');
                    }
                }
            }
        }

        function endCall() {
            // Fermer les streams
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }

            // Supprimer l'interface d'appel
            const callInterface = document.getElementById('callInterface');
            if (callInterface) {
                callInterface.remove();
            }

            // Notifier la fin d'appel
            if (isConnected && isAuthenticated) {
                websocket.send(JSON.stringify({
                    type: 'call_end',
                    conversation_id: CONVERSATION_ID
                }));
            }

            currentCall = null;
        }

        // Gestion des appels entrants
        function handleCallInvite(data) {
            if (data.caller_id === USER_ID) return; // Ignorer ses propres appels

            // Afficher une notification d'appel entrant
            showIncomingCallNotification(data);
        }

        function showIncomingCallNotification(data) {
            const notification = document.createElement('div');
            notification.id = 'incomingCallNotification';
            notification.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-600 text-white p-4 rounded-lg shadow-lg z-50';

            notification.innerHTML = `
                <div class="text-center">
                    <h3 class="font-bold mb-2">Appel entrant</h3>
                    <p class="mb-4">Appel vocal de ${data.caller_name || 'Utilisateur'}</p>
                    <div class="flex space-x-2">
                        <button onclick="acceptCall(${data.caller_id})" class="bg-green-700 hover:bg-green-800 px-4 py-2 rounded">
                            <i class="fas fa-phone mr-2"></i>Accepter
                        </button>
                        <button onclick="rejectCall(${data.caller_id})" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded">
                            <i class="fas fa-phone-slash mr-2"></i>Refuser
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto-reject après 30 secondes
            setTimeout(() => {
                if (document.getElementById('incomingCallNotification')) {
                    rejectCall(data.caller_id);
                }
            }, 30000);
        }

        function acceptCall(callerId) {
            const notification = document.getElementById('incomingCallNotification');
            if (notification) {
                notification.remove();
            }

            // Envoyer l'acceptation
            if (isConnected && isAuthenticated) {
                websocket.send(JSON.stringify({
                    type: 'call_accept',
                    caller_id: callerId,
                    conversation_id: CONVERSATION_ID
                }));
            }

            // Démarrer l'appel
            startVoiceCall();
        }

        function rejectCall(callerId) {
            const notification = document.getElementById('incomingCallNotification');
            if (notification) {
                notification.remove();
            }

            // Envoyer le rejet
            if (isConnected && isAuthenticated) {
                websocket.send(JSON.stringify({
                    type: 'call_reject',
                    caller_id: callerId,
                    conversation_id: CONVERSATION_ID
                }));
            }
        }

        function handleCallAccepted(data) {
            console.log('Appel accepté par:', data.accepter_id);
            // Ici vous pouvez ajouter la logique pour établir la connexion audio
        }

        function handleCallRejected(data) {
            console.log('Appel rejeté par:', data.rejecter_id);
            alert('Appel refusé');
            endCall();
        }

        function handleCallEnded(data) {
            console.log('Appel terminé par:', data.user_id);
            endCall();
        }

        // Variables pour la pagination
        let isLoadingMoreMessages = false;
        let hasMoreMessages = true;
        let oldestMessageId = <?php echo !empty($messages) ? $messages[0]['id'] : 0; ?>;
        let totalMessagesLoaded = <?php echo count($messages); ?>;

        // Configuration de la pagination par scroll
        function setupScrollPagination() {
            messagesContainer.addEventListener('scroll', function() {
                // Détecter le scroll vers le haut
                if (messagesContainer.scrollTop === 0 && hasMoreMessages && !isLoadingMoreMessages) {
                    loadMoreMessages();
                }
            });
        }

        // Charger plus de messages
        async function loadMoreMessages() {
            if (isLoadingMoreMessages || !hasMoreMessages) return;

            isLoadingMoreMessages = true;

            // Afficher le loader
            document.getElementById('loadMoreLoader').classList.remove('hidden');

            try {
                const response = await fetch(`api/get_older_messages.php?conversation_id=${CONVERSATION_ID}&oldest_message_id=${oldestMessageId}&limit=15`);
                const data = await response.json();

                if (data.success && data.messages && data.messages.length > 0) {
                    // Sauvegarder la position de scroll actuelle
                    const scrollHeight = messagesContainer.scrollHeight;

                    // Ajouter les messages au début
                    data.messages.reverse().forEach(message => {
                        addOlderMessageToUI(message);
                    });

                    // Mettre à jour les variables
                    oldestMessageId = data.messages[data.messages.length - 1].id;
                    totalMessagesLoaded += data.messages.length;

                    // Restaurer la position de scroll
                    const newScrollHeight = messagesContainer.scrollHeight;
                    messagesContainer.scrollTop = newScrollHeight - scrollHeight;

                    // Vérifier s'il y a encore des messages
                    if (data.messages.length < 15) {
                        hasMoreMessages = false;
                        document.getElementById('noMoreMessages').classList.remove('hidden');
                    }
                } else {
                    hasMoreMessages = false;
                    document.getElementById('noMoreMessages').classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erreur lors du chargement des messages:', error);
            } finally {
                isLoadingMoreMessages = false;
                document.getElementById('loadMoreLoader').classList.add('hidden');
            }
        }

        // Ajouter un message plus ancien à l'interface
        function addOlderMessageToUI(message) {
            if (message.is_deleted) {
                return;
            }

            // Vérifier si le message existe déjà
            const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
            if (existingMessage) {
                return;
            }

            const isOwnMessage = message.sender_id == USER_ID;
            const messageDiv = createMessageElement(message, isOwnMessage);

            // Insérer au début (après les loaders)
            const loadMoreLoader = document.getElementById('loadMoreLoader');
            const noMoreMessages = document.getElementById('noMoreMessages');

            if (!noMoreMessages.classList.contains('hidden')) {
                messagesContainer.insertBefore(messageDiv, noMoreMessages.nextSibling);
            } else {
                messagesContainer.insertBefore(messageDiv, loadMoreLoader.nextSibling);
            }
        }

        // Fonction utilitaire pour créer un élément message
        function createMessageElement(message, isOwnMessage) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isOwnMessage ? 'sent' : 'received'}`;
            messageDiv.setAttribute('data-message-id', message.id);

            const messageBubble = document.createElement('div');
            messageBubble.className = 'message-bubble';
            messageBubble.style.position = 'relative';

            // Actions de message
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'message-actions';

            if (isOwnMessage) {
                actionsDiv.innerHTML = `
                    <button class="message-action-btn" onclick="replyToMessage(${message.id}, '${escapeHtml(message.sender_pseudo)}', '${escapeHtml(message.content || message.voice_path ? 'Message vocal' : 'Image')}')">
                        <i class="fas fa-reply"></i>
                    </button>
                    <button class="message-action-btn" onclick="deleteMessage(${message.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            } else {
                actionsDiv.innerHTML = `
                    <button class="message-action-btn" onclick="replyToMessage(${message.id}, '${escapeHtml(message.sender_pseudo)}', '${escapeHtml(message.content || message.voice_path ? 'Message vocal' : 'Image')}')">
                        <i class="fas fa-reply"></i>
                    </button>
                `;
            }

            messageBubble.appendChild(actionsDiv);

            // Message de réponse
            if (message.reply_to_message_id) {
                const replyDiv = document.createElement('div');
                replyDiv.className = 'message-reply';

                const replyAuthor = document.createElement('div');
                replyAuthor.className = 'reply-author';
                replyAuthor.textContent = message.reply_user_pseudo || 'Utilisateur supprimé';

                const replyContent = document.createElement('div');
                replyContent.className = 'reply-content';

                if (message.reply_image_path) {
                    replyContent.innerHTML = `
                        <div class="reply-image-indicator">
                            <i class="fas fa-image"></i>
                            <span>Image partagée</span>
                        </div>
                    `;
                } else if (message.reply_voice_path) {
                    replyContent.innerHTML = `
                        <div class="reply-image-indicator">
                            <i class="fas fa-microphone"></i>
                            <span>Message vocal</span>
                        </div>
                    `;
                } else {
                    const content = message.reply_content || 'Message supprimé';
                    replyContent.textContent = content.length > 150 ? content.substring(0, 150) + '...' : content;
                }

                replyDiv.appendChild(replyAuthor);
                replyDiv.appendChild(replyContent);
                messageBubble.appendChild(replyDiv);
            }

            // Message vocal
            if (message.voice_path) {
                const voiceDiv = document.createElement('div');
                voiceDiv.className = 'voice-message mb-2';
                voiceDiv.innerHTML = `
                    <button onclick="playVoiceMessage('${escapeHtml(message.voice_path)}', this)" 
                            class="voice-play-button">
                        <i class="fas fa-play"></i>
                    </button>
                    <div class="voice-waveform">
                        <div class="voice-progress" style="width: 0%"></div>
                    </div>
                    <div class="voice-duration">
                        ${formatVoiceDuration(message.voice_duration || 0)}
                    </div>
                `;
                messageBubble.appendChild(voiceDiv);
            }

            // Image
            if (message.image_path) {
                const imageDiv = document.createElement('div');
                imageDiv.className = 'mb-2';
                imageDiv.innerHTML = `
                    <img src="${escapeHtml(message.image_path)}" 
                         alt="Image" 
                         class="message-image"
                         onclick="openImageModal('${escapeHtml(message.image_path)}')">
                `;
                messageBubble.appendChild(imageDiv);
            }

            // Contenu texte
            if (message.content) {
                const contentDiv = document.createElement('div');
                contentDiv.innerHTML = escapeHtml(message.content).replace(/\n/g, '<br>');
                messageBubble.appendChild(contentDiv);
            }

            // Heure
            const messageTime = document.createElement('div');
            messageTime.className = 'message-time';
            messageTime.innerHTML = `
                ${formatTime(message.created_at)}
                ${isOwnMessage ? '<i class="fas fa-check message-status read"></i>' : ''}
            `;
            messageBubble.appendChild(messageTime);

            messageDiv.appendChild(messageBubble);
            return messageDiv;
        }

        // Variables pour l'enregistrement vocal
        let mediaRecorder = null;
        let audioChunks = [];
        let recordingStartTime = 0;
        let recordingTimer = null;

        // Fonctions pour l'enregistrement vocal
        async function startVoiceRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: true
                });

                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = (event) => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = async () => {
                    const audioBlob = new Blob(audioChunks, {
                        type: 'audio/wav'
                    });
                    await uploadVoiceMessage(audioBlob);
                    stream.getTracks().forEach(track => track.stop());
                };

                mediaRecorder.start();
                recordingStartTime = Date.now();

                // Afficher l'interface d'enregistrement
                document.getElementById('voiceRecording').classList.add('active');
                document.getElementById('voiceButton').style.display = 'none';

                // Démarrer le timer
                recordingTimer = setInterval(updateRecordingTimer, 1000);

            } catch (error) {
                console.error('Erreur lors de l\'accès au microphone:', error);
                alert('Impossible d\'accéder au microphone');
            }
        }

        function updateRecordingTimer() {
            const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const seconds = (elapsed % 60).toString().padStart(2, '0');
            document.getElementById('recordingTimer').textContent = `${minutes}:${seconds}`;
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
            cleanupRecording();
        }

        function cancelRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
            audioChunks = [];
            cleanupRecording();
        }

        function cleanupRecording() {
            clearInterval(recordingTimer);
            document.getElementById('voiceRecording').classList.remove('active');
            document.getElementById('voiceButton').style.display = 'block';
            document.getElementById('recordingTimer').textContent = '00:00';
        }

        async function uploadVoiceMessage(audioBlob) {
            try {
                const formData = new FormData();
                formData.append('voice', audioBlob, 'voice_message.wav');

                const response = await fetch('api/upload_voice_message.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Envoyer le message vocal
                    sendMessageViaAPI('', replyToMessageId, null, result.voice_path);
                } else {
                    alert('Erreur lors de l\'upload du message vocal: ' + result.message);
                }
            } catch (error) {
                console.error('Erreur upload message vocal:', error);
                alert('Erreur lors de l\'upload du message vocal');
            }
        }

        function playVoiceMessage(voicePath, button) {
            const audio = new Audio(voicePath);
            const icon = button.querySelector('i');
            const waveform = button.parentElement.querySelector('.voice-progress');

            // Arrêter tous les autres audios en cours
            document.querySelectorAll('audio').forEach(a => {
                if (!a.paused) {
                    a.pause();
                    a.currentTime = 0;
                }
            });

            // Réinitialiser tous les boutons play
            document.querySelectorAll('.voice-play-button i').forEach(i => {
                i.className = 'fas fa-play';
            });

            // Réinitialiser toutes les barres de progression
            document.querySelectorAll('.voice-progress').forEach(p => {
                p.style.width = '0%';
            });

            if (audio.paused) {
                audio.play();
                icon.className = 'fas fa-pause';

                // Mettre à jour la barre de progression
                audio.ontimeupdate = () => {
                    const progress = (audio.currentTime / audio.duration) * 100;
                    waveform.style.width = progress + '%';
                };

                audio.onended = () => {
                    icon.className = 'fas fa-play';
                    waveform.style.width = '0%';
                };
            } else {
                audio.pause();
                icon.className = 'fas fa-play';
                waveform.style.width = '0%';
            }
        }
    </script>
</body>

</html>