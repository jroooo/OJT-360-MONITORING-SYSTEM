// ============================================================
// OJT-360 GEMINI AI CHATBOT
// ============================================================

document.addEventListener("DOMContentLoaded", () => {

    const aiLauncher = document.getElementById("aiLauncher");
    const aiPanel = document.getElementById("aiPanel");
    const aiClose = document.getElementById("aiClose");
    const aiBody = document.getElementById("aiBody");
    const aiForm = document.getElementById("aiForm");
    const aiInput = document.getElementById("aiInput");

    if (!aiLauncher || !aiPanel || !aiBody || !aiForm || !aiInput) {
        console.warn("OJT-360 AI chatbot elements were not found.");
        return;
    }

    // Store conversation for the current chatbot session
    let aiConversation = [];

    // --------------------------------------------------------
    // OPEN CHATBOT
    // --------------------------------------------------------
    function openAssistant() {
        aiPanel.classList.add("open");
        aiLauncher.setAttribute("aria-expanded", "true");

        setTimeout(() => {
            aiInput.focus();
        }, 200);
    }

    // --------------------------------------------------------
    // CLOSE CHATBOT
    // --------------------------------------------------------
    function closeAssistant() {
        aiPanel.classList.remove("open");
        aiLauncher.setAttribute("aria-expanded", "false");
    }

    // --------------------------------------------------------
    // ADD MESSAGE TO CHAT
    // --------------------------------------------------------
    function addAssistantMessage(text, isUser = false) {

        const row = document.createElement("div");
        row.className = isUser
            ? "ai-row user"
            : "ai-row";

        const bubble = document.createElement("div");
        bubble.className = "ai-bubble";

        // Convert line breaks to HTML
        bubble.innerHTML = formatMessage(text);

        row.appendChild(bubble);
        aiBody.appendChild(row);

        scrollChatToBottom();

        return row;
    }

    // --------------------------------------------------------
    // FORMAT GEMINI RESPONSE
    // --------------------------------------------------------
    function formatMessage(text) {

        if (!text) {
            return "";
        }

        // Basic HTML escaping
        text = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

        // Bold markdown
        text = text.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");

        // Bullet points
        text = text.replace(/^\s*[-•]\s+(.*)$/gm, "• $1");

        // Line breaks
        text = text.replace(/\n/g, "<br>");

        return text;
    }

    // --------------------------------------------------------
    // SCROLL CHAT TO BOTTOM
    // --------------------------------------------------------
    function scrollChatToBottom() {

        aiBody.scrollTop = aiBody.scrollHeight;
    }

    // --------------------------------------------------------
    // TYPING / THINKING INDICATOR
    // --------------------------------------------------------
    function showTypingIndicator() {

        const row = document.createElement("div");
        row.className = "ai-row";
        row.id = "aiTypingIndicator";

        const bubble = document.createElement("div");
        bubble.className = "ai-bubble";

        bubble.innerHTML = `
            <span>Thinking</span>
            <span class="ai-dots">...</span>
        `;

        row.appendChild(bubble);
        aiBody.appendChild(row);

        scrollChatToBottom();

        return row;
    }

    // --------------------------------------------------------
    // ASK GEMINI THROUGH chatbot.php
    // --------------------------------------------------------
    async function askGemini(question) {
        try {

            const response = await fetch("chatbot.php", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    csrf_token: CHATBOT_CSRF_TOKEN,
                    message: question,
                    conversation: aiConversation.slice(-10)
                })
            });

            let data;

            try {
                data = await response.json();
            } catch (jsonError) {
                throw new Error("Invalid response from chatbot.php.");
            }

            if (!response.ok || data.success !== true || typeof data.message !== "string") {

                throw new Error(
                    data.error || "Gemini API returned an error."
                );
            }

            const answer = data.message;

            aiConversation.push(
                {role: "user", content: question},
                {role: "assistant", content: answer}
            );
            if (aiConversation.length > 10) {
                aiConversation = aiConversation.slice(-10);
            }

            return answer;

        } catch (error) {

            console.error("Gemini chatbot error:", error);

            throw error;
        }
    }

    // --------------------------------------------------------
    // SEND MESSAGE
    // --------------------------------------------------------
    async function sendAIMessage(question) {

        question = question.trim();

        if (!question) {
            return;
        }

        // Prevent sending while another request is processing
        if (aiForm.dataset.loading === "true") {
            return;
        }

        aiForm.dataset.loading = "true";

        // Show user's message
        addAssistantMessage(question, true);

        // Clear input
        aiInput.value = "";

        // Show typing indicator
        const typingIndicator = showTypingIndicator();

        try {

            const answer = await askGemini(question);

            // Remove typing indicator
            if (typingIndicator) {
                typingIndicator.remove();
            }

            // Show Gemini response
            addAssistantMessage(answer, false);

        } catch (error) {

            // Remove typing indicator
            if (typingIndicator) {
                typingIndicator.remove();
            }

            addAssistantMessage(
                error instanceof Error
                    ? error.message
                    : "I'm having trouble connecting to Gemini. Please try again.",
                false
            );

        } finally {

            aiForm.dataset.loading = "false";

            aiInput.focus();
        }
    }

    // --------------------------------------------------------
    // OPEN BUTTON
    // --------------------------------------------------------
    aiLauncher.addEventListener("click", () => {

        if (aiPanel.classList.contains("open")) {
            closeAssistant();
        } else {
            openAssistant();
        }

    });

    // --------------------------------------------------------
    // CLOSE BUTTON
    // --------------------------------------------------------
    if (aiClose) {

        aiClose.addEventListener("click", () => {
            closeAssistant();
        });

    }

    // --------------------------------------------------------
    // SEND FORM
    // --------------------------------------------------------
    aiForm.addEventListener("submit", async (event) => {

        event.preventDefault();

        const question = aiInput.value.trim();

        if (!question) {
            return;
        }

        await sendAIMessage(question);

    });

    // --------------------------------------------------------
    // ENTER KEY
    // --------------------------------------------------------
    aiInput.addEventListener("keydown", (event) => {

        if (event.key === "Enter" && !event.shiftKey) {

            event.preventDefault();

            aiForm.dispatchEvent(new Event("submit"));

        }

    });

    // --------------------------------------------------------
    // SUGGESTION BUTTONS
    // --------------------------------------------------------
    document.querySelectorAll(".ai-suggestion").forEach(button => {

        button.addEventListener("click", async () => {

            const question =
                button.dataset.question ||
                button.textContent.trim();

            if (!question) {
                return;
            }

            await sendAIMessage(question);

        });

    });

});