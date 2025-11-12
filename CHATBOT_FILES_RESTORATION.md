# Chatbot Files Restoration Guide

## Files That Need to Be Created

The following chatbot-related files were deleted and need to be restored:

### 1. chatbot.php (Backend API)
- Location: Root directory
- Purpose: Handles all chatbot interactions with AI integration
- Status: NEEDS RESTORATION

### 2. includes/chatbot.html (UI Component)
- Location: includes/ directory
- Purpose: Chatbot interface HTML/CSS
- Status: NEEDS RESTORATION

### 3. js/chatbot.js (Frontend Logic)
- Location: js/ directory  
- Purpose: Chatbot frontend controller with dynamic API path detection
- Status: NEEDS RESTORATION

### 4. Additional Test Files
- chatbot_test.php
- chatbot_old.php
- chatbot_analytics.php
- test_chatbot_backend.php
- test_enhanced_chatbot.php
- test_chatbot_connection.html
- test_quick_replies.html
- test_with_tips.html
- test_tts.html
- test_voice_input.html
- voice_text_choice_demo.html

### 5. Documentation Files
- CHATBOT_FIX.md
- CHATBOT_README.md

## Restoration Instructions

To restore these files, use the backup or recreate them based on the specifications below.

## Database Requirements

Chatbot requires these database tables:
- `chatbot_logs` - Store conversation history
- `chatbot_feedback` - Store user feedback
- `chatbot_sessions` - Track user sessions

## Integration Points

The chatbot is integrated into these pages:
- index.php
- dashboard.php
- challenges.php
- lipa_kidogo.php
- direct_purchase.php
- admin.php
- join_challenge.php
- core/login.php
- core/register.php

Integration code: `<?php include 'includes/chatbot.html'; ?>`

## Key Features

1. **Multi-language Support** (English & Swahili)
2. **Intent Detection** (12 intent types)
3. **Sentiment Analysis**
4. **Database Integration** (materials, challenges queries)
5. **Voice Input Support**
6. **Emoji Picker**
7. **Quick Reply Suggestions**
8. **Session Persistence**
9. **Dynamic Path Detection** for subdirectories

## Configuration

- API Endpoint: chatbot.php
- Base URL: http://localhost/mjengo-new
- Gemini AI Key: Configured in config.php

## Contact

For assistance: chrisbetuelmlay@oweru.com | +255 714 859 934
