{{-- RolePermissionManager Automatic Toast / Alert System --}}
<div id="acl-toast-container" aria-live="polite" style="position: fixed; top: 20px; right: 20px; z-index: 999999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;"></div>

<style>
.acl-toast-card {
    pointer-events: auto;
    position: relative;
    overflow: hidden;
    min-width: 300px;
    max-width: 440px;
    background: #181824;
    color: #f3f4f6;
    border: 1px solid rgba(239, 68, 68, 0.35);
    border-left: 5px solid #ef4444;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
    padding: 14px 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    transform: translateX(120%);
    opacity: 0;
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.35s ease;
}
.acl-toast-card.acl-toast-show {
    transform: translateX(0);
    opacity: 1;
}
.acl-toast-card.acl-toast-hide {
    transform: translateX(120%);
    opacity: 0;
}
.acl-toast-icon {
    font-size: 20px;
    line-height: 1.2;
    flex-shrink: 0;
}
.acl-toast-body {
    flex: 1;
}
.acl-toast-title {
    font-size: 14px;
    font-weight: 700;
    color: #ef4444;
    margin-bottom: 4px;
}
.acl-toast-message {
    font-size: 13px;
    line-height: 1.4;
    color: #e5e7eb;
    word-break: break-word;
}
.acl-toast-close {
    background: transparent;
    border: none;
    color: #9ca3af;
    font-size: 16px;
    cursor: pointer;
    padding: 0 4px;
    line-height: 1;
    transition: color 0.2s;
}
.acl-toast-close:hover {
    color: #ffffff;
}
.acl-toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: #ef4444;
    width: 100%;
    animation: aclToastProgress linear forwards;
}
@keyframes aclToastProgress {
    from { width: 100%; }
    to { width: 0%; }
}
</style>

<script>
(function() {
    if (window.__aclAlertsInitialized) return;
    window.__aclAlertsInitialized = true;

    const defaultTitle = @json(__('acl::resources.toast_title'));

    window.showAclToast = function(message, title, duration = 5000) {
        // Dispatch custom event for custom listeners (SweetAlert2, Toastr, etc.)
        try {
            window.dispatchEvent(new CustomEvent('acl:denied', {
                detail: { message: message, title: title || defaultTitle }
            }));
            window.dispatchEvent(new CustomEvent('acl:unauthorized', {
                detail: { message: message, title: title || defaultTitle }
            }));
        } catch(e) {}

        if (window.ACL_DISABLE_DEFAULT_TOAST === true) {
            return;
        }

        const container = document.getElementById('acl-toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'acl-toast-card';
        toast.innerHTML = `
            <div class="acl-toast-icon">🔒</div>
            <div class="acl-toast-body">
                <div class="acl-toast-title">${title || defaultTitle}</div>
                <div class="acl-toast-message">${message || 'Accesso non consentito.'}</div>
            </div>
            <button type="button" class="acl-toast-close" aria-label="Close">&times;</button>
            <div class="acl-toast-progress" style="animation-duration: ${duration}ms;"></div>
        `;

        container.appendChild(toast);

        // Slide in
        requestAnimationFrame(() => {
            toast.classList.add('acl-toast-show');
        });

        const closeToast = () => {
            toast.classList.remove('acl-toast-show');
            toast.classList.add('acl-toast-hide');
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 400);
        };

        const closeBtn = toast.querySelector('.acl-toast-close');
        if (closeBtn) closeBtn.addEventListener('click', closeToast);

        const timer = setTimeout(closeToast, duration);
        toast.addEventListener('mouseenter', () => clearTimeout(timer));
    };

    function extractMessageFromResponse(data, status) {
        if (!data) return status === 403 ? 'Accesso non consentito (403 Forbidden).' : null;
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch(e) { return data; }
        }
        return data.message || data.error || (status === 403 ? 'Accesso non consentito (403 Forbidden).' : null);
    }

    // 1. Axios interceptor
    if (window.axios && window.axios.interceptors && window.axios.interceptors.response) {
        window.axios.interceptors.response.use(
            response => response,
            error => {
                if (error && error.response && (error.response.status === 403 || error.response.status === 401)) {
                    const msg = extractMessageFromResponse(error.response.data, error.response.status);
                    if (msg) window.showAclToast(msg);
                }
                return Promise.reject(error);
            }
        );
    }

    // 2. Native fetch interceptor
    if (window.fetch) {
        const originalFetch = window.fetch;
        window.fetch = async function(...args) {
            try {
                const response = await originalFetch.apply(this, args);
                if (response && (response.status === 403 || response.status === 401)) {
                    try {
                        const clone = response.clone();
                        clone.json().then(data => {
                            const msg = extractMessageFromResponse(data, response.status);
                            if (msg) window.showAclToast(msg);
                        }).catch(() => {
                            window.showAclToast('Accesso non consentito (403 Forbidden).');
                        });
                    } catch(e) {}
                }
                return response;
            } catch(err) {
                throw err;
            }
        };
    }

    // 3. XMLHttpRequest interceptor (jQuery $.ajax / native XHR)
    if (window.XMLHttpRequest) {
        const origOpen = XMLHttpRequest.prototype.open;
        const origSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function() {
            return origOpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function() {
            this.addEventListener('load', function() {
                if (this.status === 403 || this.status === 401) {
                    let msg = null;
                    try {
                        const json = JSON.parse(this.responseText);
                        msg = extractMessageFromResponse(json, this.status);
                    } catch(e) {
                        msg = 'Accesso non consentito (403 Forbidden).';
                    }
                    if (msg) window.showAclToast(msg);
                }
            });
            return origSend.apply(this, arguments);
        };
    }
})();
</script>
