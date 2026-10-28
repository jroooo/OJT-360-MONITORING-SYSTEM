// =====================================================
// OJT360 NOTIFICATION SYSTEM
// notification.js
//
// Uses the EXISTING Supabase client from
// dashboard(employee).php
//
// IMPORTANT:
// - Does NOT create or modify Supabase tables
// - Does NOT modify RLS
// - Does NOT modify Realtime settings
// - Uses the existing "message" table and its "messages" text column
// =====================================================

(function () {

    "use strict";

    // =================================================
    // CURRENT EMPLOYEE
    // =================================================

    const NOTIFICATION_EMPLOYEE_ID =
        typeof CURRENT_EMPLOYEE_ID !== "undefined"
            ? String(CURRENT_EMPLOYEE_ID)
            : "";


    // =================================================
    // VARIABLES
    // =================================================

    let notifications = [];

    let companyNotifications = [];

    const seenCompanyMessageIds = new Set();

    const toastedCompanyMessageIds = new Set();

    let initialCompanyNotificationsLoaded = false;

    let companyNotificationPoll = null;

    let notificationRealtimeChannel = null;


    // =================================================
    // HTML ELEMENTS
    // =================================================

    const notificationBtn =
        document.getElementById("notificationBtn");

    const notificationDropdown =
        document.getElementById("notificationDropdown");

    const notificationList =
        document.getElementById("notificationList");

    const notificationBadge =
        document.getElementById("notificationBadge");

    const markAllReadBtn =
        document.getElementById("markAllReadBtn");


    // =================================================
    // CHECK SUPABASE
    // =================================================

    function supabaseIsReady() {

        return (
            typeof supabaseReady !== "undefined" &&
            supabaseReady === true &&
            typeof supabaseClient !== "undefined" &&
            supabaseClient !== null &&
            NOTIFICATION_EMPLOYEE_ID !== ""
        );

    }


    // =================================================
    // ESCAPE HTML
    // =================================================

    function escapeHtml(value) {

        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");

    }


    // =================================================
    // GET SENDER NAME
    // =================================================

    function getSenderName(senderType) {

        const type =
            String(senderType || "").toLowerCase();

        switch (type) {

            case "company":
                return "Company";

            case "student":
                return "Student";

            case "admin":
                return "Administrator";

            case "employee":
                return "Employee";

            default:
                return "OJT360";

        }

    }


    // =================================================
    // FORMAT NOTIFICATION TIME
    // =================================================

    function formatNotificationTime(dateValue) {

        if (!dateValue) {
            return "";
        }


        const date =
            new Date(dateValue);


        if (isNaN(date.getTime())) {
            return "";
        }


        const now =
            new Date();


        const seconds =
            Math.floor(
                (now.getTime() - date.getTime()) / 1000
            );


        if (seconds < 60) {
            return "Just now";
        }


        const minutes =
            Math.floor(seconds / 60);


        if (minutes < 60) {

            return (
                minutes +
                (minutes === 1
                    ? " min ago"
                    : " mins ago")
            );

        }


        const hours =
            Math.floor(minutes / 60);


        if (hours < 24) {

            return (
                hours +
                (hours === 1
                    ? " hr ago"
                    : " hrs ago")
            );

        }


        const days =
            Math.floor(hours / 24);


        if (days < 7) {

            return (
                days +
                (days === 1
                    ? " day ago"
                    : " days ago")
            );

        }


        return date.toLocaleDateString();

    }


    // =================================================
    // UPDATE NOTIFICATION BADGE
    // =================================================

    function updateNotificationBadge() {

        if (!notificationBadge) {
            return;
        }


        const unreadCount =
            [...notifications, ...companyNotifications].filter(
                notification =>
                    !notification.is_read
            ).length;


        if (unreadCount > 0) {

            notificationBadge.textContent =
                unreadCount > 99
                    ? "99+"
                    : String(unreadCount);

            notificationBadge.hidden = false;

        } else {

            notificationBadge.textContent =
                "0";

            notificationBadge.hidden = true;

        }

    }


    // =================================================
    // RENDER NOTIFICATIONS
    // =================================================

    function renderNotifications() {

        if (!notificationList) {
            return;
        }


        updateNotificationBadge();
        if (markAllReadBtn) {
            markAllReadBtn.disabled = ![...notifications, ...companyNotifications].some(notification => !notification.is_read);
        }


        // ---------------------------------------------
        // NO NOTIFICATIONS
        // ---------------------------------------------

        const allNotifications = [...notifications, ...companyNotifications]
            .sort((left, right) => new Date(right.created_at) - new Date(left.created_at))
            .slice(0, 30);

        if (allNotifications.length === 0) {

            notificationList.innerHTML = `
                <div class="notification-empty">
                    No notifications
                </div>
            `;

            return;

        }


        // ---------------------------------------------
        // NOTIFICATION LIST
        // ---------------------------------------------

        notificationList.innerHTML =
            allNotifications.map(notification => {

                const unreadClass =
                    notification.is_read
                        ? ""
                        : "unread";

                const companyName = notification.company_name || getSenderName(notification.sender_type);

                return `
                    <div
                        class="notification-item ${unreadClass}"
                        data-notification-id="${escapeHtml(notification.id)}"
                    >

                        <div class="notification-icon">
                            ✉
                        </div>

                        <div class="notification-content">

                            <div class="notification-title">
                                You have a message from ${escapeHtml(companyName)}
                            </div>

                            <div class="notification-time">
                                ${escapeHtml(
                                    formatNotificationTime(
                                        notification.created_at
                                    )
                                )}
                            </div>

                        </div>

                    </div>
                `;

            }).join("");


        // ---------------------------------------------
        // CLICK NOTIFICATION
        // ---------------------------------------------

        notificationList
            .querySelectorAll(".notification-item")
            .forEach(item => {

                item.addEventListener(
                    "click",
                    function () {

                        const id =
                            this.dataset.notificationId;

                        const companyNotification = companyNotifications.find(
                            notification => String(notification.id) === String(id)
                        );

                        if (companyNotification) {
                            window.location.href = "employee_messages.php?company_id=" +
                                encodeURIComponent(companyNotification.company_id);
                            return;
                        }

                        markNotificationAsRead(id);

                    }
                );

            });

    }

    async function loadCompanyNotifications() {
        try {
            const response = await fetch(
                window.location.pathname + "?message_api=notifications",
                { credentials: "same-origin", cache: "no-store" }
            );
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Unable to load company message notifications.");
            }

            const freshNotifications = data.notifications || [];

            if (initialCompanyNotificationsLoaded) {
                freshNotifications.forEach(notification => {
                    if (seenCompanyMessageIds.has(String(notification.id))) {
                        return;
                    }

                    window.dispatchEvent(new CustomEvent("ojt360:company-reply", {
                        detail: {
                            id: notification.id,
                            companyId: notification.company_id,
                            companyName: notification.company_name,
                            message: notification.messages
                        }
                    }));

                    if ("Notification" in window && Notification.permission === "granted") {
                        try {
                            new Notification("New message from " + notification.company_name, {
                                body: "You have a new message."
                            });
                        } catch (error) {
                            console.warn("Browser notification failed:", error);
                        }
                    }
                });
            }

            freshNotifications.forEach(notification => {
                seenCompanyMessageIds.add(String(notification.id));
            });
            companyNotifications = freshNotifications;
            initialCompanyNotificationsLoaded = true;
            renderNotifications();
        } catch (error) {
            console.error("Company message notification polling failed:", error);
        }
    }

    function startCompanyMessageNotifications() {
        if (companyNotificationPoll) {
            return;
        }

        loadCompanyNotifications();
        companyNotificationPoll = window.setInterval(loadCompanyNotifications, 5000);
    }

    window.addEventListener("ojt360:company-reply", event => {
        const messageId = String(event.detail?.id || "");
        if (messageId && toastedCompanyMessageIds.has(messageId)) {
            return;
        }
        if (messageId) {
            toastedCompanyMessageIds.add(messageId);
            if (toastedCompanyMessageIds.size > 1000) {
                toastedCompanyMessageIds.delete(toastedCompanyMessageIds.values().next().value);
            }
        }

        const companyName = event.detail?.companyName || "a company";
        const toast = document.getElementById("toast");
        if (toast) {
            toast.textContent = "You have a message from " + companyName + ".";
            toast.classList.add("show");
            window.setTimeout(() => toast.classList.remove("show"), 3000);
        }
    });


    // =================================================
    // LOAD NOTIFICATIONS
    // =================================================

    async function loadNotifications() {

        if (!supabaseIsReady()) {

            console.warn(
                "OJT360 Notifications: Supabase is not ready."
            );

            return;

        }


        try {

            const {
                data,
                error
            } = await supabaseClient

                .from("message")

                .select(`
                    id,
                    sender_type,
                    sender_id,
                    receiver_type,
                    receiver_id,
                    messages,
                    is_read,
                    created_at
                `)

                .eq(
                    "receiver_type",
                    "employee"
                )

                .eq(
                    "receiver_id",
                    NOTIFICATION_EMPLOYEE_ID
                )

                .order(
                    "created_at",
                    {
                        ascending: false
                    }
                )

                .limit(30);


            if (error) {

                console.error(
                    "OJT360 notification loading error:",
                    error
                );

                if (notificationList) {
                    notificationList.innerHTML = `
                        <p class="notification-empty">
                            Unable to load notifications. Please try again later.
                        </p>
                    `;
                }

                return;

            }


            notifications =
                data || [];


            renderNotifications();


        } catch (error) {

            console.error(
                "OJT360 notification error:",
                error
            );

            if (notificationList) {
                notificationList.innerHTML = `
                    <p class="notification-empty">
                        Unable to load notifications. Please try again later.
                    </p>
                `;
            }

        }

    }


    // =================================================
    // MARK ONE NOTIFICATION AS READ
    // =================================================

    async function markNotificationAsRead(id) {

        if (!supabaseIsReady()) {
            return;
        }


        try {

            const {
                error
            } = await supabaseClient

                .from("message")

                .update({
                    is_read: true
                })

                .eq(
                    "id",
                    id
                )
                .eq(
                    "receiver_type",
                    "employee"
                )
                .eq(
                    "receiver_id",
                    NOTIFICATION_EMPLOYEE_ID
                );


            if (error) {

                console.error(
                    "Mark notification as read error:",
                    error
                );

                return;

            }


            notifications =
                notifications.map(
                    notification => {

                        if (
                            String(notification.id) ===
                            String(id)
                        ) {

                            return {
                                ...notification,
                                is_read: true
                            };

                        }


                        return notification;

                    }
                );


            renderNotifications();


        } catch (error) {

            console.error(
                "Notification read error:",
                error
            );

        }

    }


    // =================================================
    // MARK ALL NOTIFICATIONS AS READ
    // =================================================

    async function markAllNotificationsAsRead() {

        fetch(window.location.pathname + "?message_api=mark_all_read", {
            method: "POST",
            credentials: "same-origin"
        }).then(async response => {
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || "Unable to mark company messages as read.");
            }
            companyNotifications = companyNotifications.map(notification => ({
                ...notification,
                is_read: true
            }));
            renderNotifications();
        }).catch(error => {
            console.error("Mark company messages as read failed:", error);
        });

        if (!supabaseIsReady()) {
            return;
        }


        try {

            const {
                error
            } = await supabaseClient

                .from("message")

                .update({
                    is_read: true
                })

                .eq(
                    "receiver_type",
                    "employee"
                )

                .eq(
                    "receiver_id",
                    NOTIFICATION_EMPLOYEE_ID
                )

                .eq(
                    "is_read",
                    false
                );


            if (error) {

                console.error(
                    "Mark all notifications error:",
                    error
                );

                return;

            }


            notifications =
                notifications.map(
                    notification => ({
                        ...notification,
                        is_read: true
                    })
                );


            renderNotifications();


        } catch (error) {

            console.error(
                "Mark all notifications error:",
                error
            );

        }

    }


    // =================================================
    // MARK ALL READ BUTTON
    // =================================================

    if (markAllReadBtn) {

        markAllReadBtn.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                markAllNotificationsAsRead();

            }
        );

    }


    // =================================================
    // OPEN / CLOSE NOTIFICATION DROPDOWN
    // =================================================

    if (notificationBtn) {

        notificationBtn.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();


                if (!notificationDropdown) {
                    return;
                }


                const isOpen =
                    notificationDropdown.classList.toggle(
                        "show"
                    );


                notificationBtn.setAttribute(
                    "aria-expanded",
                    String(isOpen)
                );

            }
        );

    }


    // =================================================
    // CLOSE DROPDOWN WHEN CLICKING OUTSIDE
    // =================================================

    document.addEventListener(
        "click",
        function (event) {

            if (
                !notificationDropdown ||
                !notificationBtn
            ) {
                return;
            }


            if (
                !notificationDropdown.contains(
                    event.target
                ) &&
                !notificationBtn.contains(
                    event.target
                )
            ) {

                notificationDropdown.classList.remove(
                    "show"
                );


                notificationBtn.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        }
    );


    // =================================================
    // REALTIME NOTIFICATIONS
    //
    // Uses the EXISTING Realtime configuration.
    // Nothing is changed in Supabase.
    // =================================================

    function subscribeToNotifications() {

        if (!supabaseIsReady()) {
            return;
        }


        // Remove previous channel if one exists

        if (notificationRealtimeChannel) {

            supabaseClient.removeChannel(
                notificationRealtimeChannel
            );

            notificationRealtimeChannel =
                null;

        }


        notificationRealtimeChannel =
            supabaseClient

                .channel(
                    "employee-notifications-" +
                    NOTIFICATION_EMPLOYEE_ID
                )

                .on(
                    "postgres_changes",
                    {
                        event: "INSERT",
                        schema: "public",
                        table: "message"
                    },
                    function (payload) {

                        const row =
                            payload.new;


                        if (!row) {
                            return;
                        }


                        // ---------------------------------
                        // ONLY EMPLOYEE RECEIVERS
                        // ---------------------------------

                        if (
                            String(
                                row.receiver_type
                            ).toLowerCase() !==
                            "employee"
                        ) {

                            return;

                        }


                        // ---------------------------------
                        // ONLY CURRENT EMPLOYEE
                        // ---------------------------------

                        if (
                            String(
                                row.receiver_id
                            ) !==
                            NOTIFICATION_EMPLOYEE_ID
                        ) {

                            return;

                        }

                        if (
                            notifications.some(
                                notification =>
                                    String(notification.id) === String(row.id)
                            )
                        ) {
                            return;
                        }

                        // ---------------------------------
                        // ADD NEW NOTIFICATION
                        // ---------------------------------

                        notifications.unshift(
                            row
                        );


                        // Keep only latest 30

                        notifications =
                            notifications.slice(
                                0,
                                30
                            );


                        // Update UI

                        renderNotifications();


                        // ---------------------------------
                        // OPTIONAL BROWSER NOTIFICATION
                        // ---------------------------------

                        if (
                            "Notification" in window &&
                            Notification.permission ===
                                "granted"
                        ) {

                            try {

                                new Notification(
                                    "OJT360 - New Message",
                                    {
                                        body:
                                            row.messages ||
                                            "You received a new message."
                                    }
                                );

                            } catch (error) {

                                console.warn(
                                    "Browser notification failed:",
                                    error
                                );

                            }

                        }

                    }
                )

                .subscribe(
                    function (status) {

                        console.log(
                            "Notification realtime status:",
                            status
                        );

                    }
                );

    }


    // =================================================
    // INITIALIZE NOTIFICATIONS
    // =================================================

    function initializeNotifications() {

        if (!notificationBtn) {

            console.warn(
                "OJT360 Notifications: " +
                "#notificationBtn was not found."
            );

            return;

        }


        if (!notificationDropdown) {

            console.warn(
                "OJT360 Notifications: " +
                "#notificationDropdown was not found."
            );

        }

        if (notificationDropdown) {
            notificationDropdown.hidden = false;
        }


        if (!notificationList) {

            console.warn(
                "OJT360 Notifications: " +
                "#notificationList was not found."
            );

        }


        if (!NOTIFICATION_EMPLOYEE_ID) {

            console.warn(
                "OJT360 Notifications: " +
                "CURRENT_EMPLOYEE_ID is missing."
            );

            return;

        }


        // Load existing notifications

        loadNotifications();


        // Listen for new messages

        subscribeToNotifications();


    }


    // =================================================
    // START
    // =================================================

    function startNotificationSystem() {

        if (
            typeof supabaseReady !== "undefined" &&
            supabaseReady === true
        ) {

            initializeNotifications();

            return;

        }


        // Wait for custom event if dashboard
        // dispatches one

        window.addEventListener(
            "supabase-ready",
            function () {

                initializeNotifications();

            },
            {
                once: true
            }
        );


        // Fallback check

        let attempts = 0;

        const waitForSupabase =
            setInterval(
                function () {

                    attempts++;


                    if (
                        typeof supabaseReady !==
                            "undefined" &&
                        supabaseReady === true
                    ) {

                        clearInterval(
                            waitForSupabase
                        );


                        initializeNotifications();

                    }


                    if (attempts >= 20) {

                        clearInterval(
                            waitForSupabase
                        );

                        if (!supabaseIsReady() && notificationList) {
                            notificationList.innerHTML = `
                                <p class="notification-empty">
                                    Notifications are unavailable. Check the messaging connection and try again.
                                </p>
                            `;
                        }

                    }

                },
                500
            );

    }


    // Start the notification system

    startCompanyMessageNotifications();
    startNotificationSystem();


})();