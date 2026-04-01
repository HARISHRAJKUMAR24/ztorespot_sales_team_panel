// js/user-tracker.js
// User Activity Tracker for Sales Team Panel

class UserActivityTracker {
    constructor() {
        this.userId = null;
        this.userUid = null;
        this.userName = null;
        this.isTabActive = true;
        this.isPageVisible = true;
        this.lastActivity = Date.now();
        this.heartbeatInterval = null;
        this.HEARTBEAT_INTERVAL = 30000; // 30 seconds
        this.IDLE_TIMEOUT = 60000; // 60 seconds idle timeout
        this.idleTimer = null;

        this.init();
    }

    init() {
        // Get user data from meta tags
        this.getUserData();

        if (!this.userId) {
            console.log('No user data found, skipping tracker');
            return;
        }

        // Setup event listeners
        this.setupEventListeners();

        // Start heartbeat
        this.startHeartbeat();

        // Initial activity update
        this.updateActivity('page_load');

        console.log('User Activity Tracker initialized for user:', this.userName);
    }

    getUserData() {
        // Get user data from meta tags
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        const userUidMeta = document.querySelector('meta[name="user-uid"]');
        const userNameMeta = document.querySelector('meta[name="user-name"]');

        if (userIdMeta) this.userId = userIdMeta.getAttribute('content');
        if (userUidMeta) this.userUid = userUidMeta.getAttribute('content');
        if (userNameMeta) this.userName = userNameMeta.getAttribute('content');

        // Fallback to session storage
        if (!this.userId) {
            this.userId = sessionStorage.getItem('user_id');
            this.userUid = sessionStorage.getItem('user_uid');
            this.userName = sessionStorage.getItem('user_name');
        }
    }

    setupEventListeners() {
        // Tab visibility change
        document.addEventListener('visibilitychange', () => {
            this.isPageVisible = !document.hidden;
            this.updateActivity('visibility_change');

            if (this.isPageVisible) {
                this.resetIdleTimer();
                this.sendOnlineStatus(true);
            } else {
                this.sendOnlineStatus(false);
            }
        });

        // Window focus/blur
        window.addEventListener('focus', () => {
            this.isTabActive = true;
            this.updateActivity('window_focus');
            this.resetIdleTimer();
            this.sendOnlineStatus(true);
        });

        window.addEventListener('blur', () => {
            this.isTabActive = false;
            this.updateActivity('window_blur');
            this.sendOnlineStatus(false);
        });

        // User activity events
        const activityEvents = ['mousemove', 'mousedown', 'keypress', 'scroll', 'click', 'touchstart'];
        activityEvents.forEach(event => {
            document.addEventListener(event, () => {
                this.resetIdleTimer();
                this.updateActivity('user_interaction');
            });
        });

        // Before unload
        window.addEventListener('beforeunload', () => {
            this.sendOfflineStatus();
        });

        // Page unload
        window.addEventListener('unload', () => {
            this.sendOfflineStatus();
        });
    }

    resetIdleTimer() {
        if (this.idleTimer) clearTimeout(this.idleTimer);

        this.idleTimer = setTimeout(() => {
            console.log('User idle for too long, marking as offline');
            this.sendOnlineStatus(false);
        }, this.IDLE_TIMEOUT);
    }

    updateActivity(action) {
        this.lastActivity = Date.now();
        this.sendActivityUpdate(action);
    }

    sendActivityUpdate(action) {
        if (!this.userId) return;

        const data = {
            user_id: this.userId,
            user_uid: this.userUid,
            user_name: this.userName,
            action: action,
            current_page: window.location.pathname,
            is_online: this.isPageVisible && this.isTabActive,
            last_activity: new Date().toISOString()
        };

        // Send to admin panel's AJAX endpoint
        const adminUrl = 'http://localhost/ztorespotcrm_admin/ajax/update-user-activity.php';

        fetch(adminUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        }).catch(error => console.error('Activity update failed:', error));
    }

    sendOnlineStatus(isOnline) {
        if (!this.userId) return;

        const data = {
            user_id: this.userId,
            user_uid: this.userUid,
            user_name: this.userName,
            is_online: isOnline,
            current_page: window.location.pathname
        };

        const adminUrl = 'http://localhost/ztorespotcrm_admin/ajax/update-user-status.php';

        fetch(adminUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        }).catch(error => console.error('Status update failed:', error));
    }

    sendOfflineStatus() {
        this.sendOnlineStatus(false);
    }

    startHeartbeat() {
        this.heartbeatInterval = setInterval(() => {
            if (this.isPageVisible && this.isTabActive) {
                this.updateActivity('heartbeat');
            }
        }, this.HEARTBEAT_INTERVAL);
    }

    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }
    }
}

// Initialize tracker when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.userTracker = new UserActivityTracker();
});