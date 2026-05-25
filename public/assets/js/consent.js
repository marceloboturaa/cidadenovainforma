(function () {
    const STORAGE_KEY = 'cni_cookie_consent';
    const configUrl = window.CNI_CONSENT_CONFIG_URL || '/api/consent/config';
    let config = null;
    let loadedScripts = new Set();

    document.addEventListener('DOMContentLoaded', init);

    async function init() {
        try {
            const response = await fetch(configUrl, { credentials: 'same-origin' });
            config = await response.json();
        } catch (error) {
            return;
        }

        bindPreferenceButtons();
        const stored = getStoredConsent();
        if (stored && stored.policyVersion === config.settings.policy_version) {
            loadAllowedScripts(stored.preferences || {});
            showPreferenceButton();
            return;
        }

        renderBanner(false);
    }

    function bindPreferenceButtons() {
        document.querySelectorAll('[data-cookie-preferences]').forEach((button) => {
            button.hidden = false;
            button.addEventListener('click', () => renderBanner(true));
        });
    }

    function showPreferenceButton() {
        document.querySelectorAll('[data-cookie-preferences]').forEach((button) => {
            button.hidden = false;
        });
    }

    function renderBanner(openPreferences) {
        removeBanner();
        const settings = config.settings || {};
        const banner = document.createElement('section');
        banner.className = 'cookie-consent';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-modal', 'true');
        banner.setAttribute('aria-label', settings.banner_title || 'Consentimento de cookies');
        banner.style.setProperty('--cookie-primary', settings.primary_color || '#b91c1c');
        banner.style.setProperty('--cookie-secondary', settings.secondary_color || '#111827');
        banner.style.setProperty('--cookie-bg', settings.background_color || '#ffffff');
        banner.style.setProperty('--cookie-text', settings.text_color || '#111827');

        banner.innerHTML = `
            <h2>${escapeHtml(settings.banner_title || 'Controle de privacidade')}</h2>
            <p>${escapeHtml(settings.banner_text || '')}</p>
            <div class="cookie-consent-preferences" ${openPreferences ? '' : 'hidden'}>
                ${categoryFields()}
            </div>
            <div class="cookie-consent-actions">
                <button type="button" data-consent-reject>${escapeHtml(settings.reject_label || 'Rejeitar tudo')}</button>
                <button type="button" data-consent-customize>${escapeHtml(settings.customize_label || 'Personalizar')}</button>
                <button type="button" data-consent-accept>${escapeHtml(settings.accept_label || 'Aceitar tudo')}</button>
                <button type="button" data-consent-save ${openPreferences ? '' : 'hidden'}>${escapeHtml(settings.save_label || 'Salvar preferências')}</button>
            </div>
            <a href="${escapeAttribute(config.policyUrl || '/politica-de-cookies')}">Política de Cookies</a>
        `;

        document.body.appendChild(banner);
        bindBanner(banner);
    }

    function bindBanner(banner) {
        const preferences = banner.querySelector('.cookie-consent-preferences');
        const customize = banner.querySelector('[data-consent-customize]');
        const save = banner.querySelector('[data-consent-save]');

        banner.querySelector('[data-consent-accept]').addEventListener('click', () => {
            const prefs = {};
            (config.categories || []).forEach((category) => prefs[category.slug] = true);
            saveConsent(prefs, 'accept_all');
        });

        banner.querySelector('[data-consent-reject]').addEventListener('click', () => {
            const prefs = {};
            (config.categories || []).forEach((category) => prefs[category.slug] = !!category.required);
            saveConsent(prefs, 'reject_all');
        });

        customize.addEventListener('click', () => {
            preferences.hidden = false;
            save.hidden = false;
            customize.hidden = true;
        });

        save.addEventListener('click', () => {
            const prefs = {};
            banner.querySelectorAll('[data-consent-category]').forEach((input) => {
                prefs[input.name] = input.checked || input.disabled;
            });
            saveConsent(prefs, 'preferences');
        });
    }

    function categoryFields() {
        const stored = getStoredConsent();
        const prefs = stored ? stored.preferences || {} : {};
        return (config.categories || []).map((category) => {
            const checked = category.required || prefs[category.slug] === true;
            return `
                <label class="cookie-consent-category">
                    <span>
                        <strong>${escapeHtml(category.name)}</strong>
                        <small>${escapeHtml(category.description || '')}</small>
                    </span>
                    <input data-consent-category type="checkbox" name="${escapeAttribute(category.slug)}" ${checked ? 'checked' : ''} ${category.required ? 'disabled' : ''}>
                </label>
            `;
        }).join('');
    }

    async function saveConsent(preferences, source) {
        const payload = {
            preferences,
            source
        };
        const local = {
            policyVersion: config.settings.policy_version,
            preferences,
            savedAt: new Date().toISOString()
        };

        localStorage.setItem(STORAGE_KEY, JSON.stringify(local));
        removeBanner();
        showPreferenceButton();
        loadAllowedScripts(preferences);

        try {
            await fetch(config.apiUrl || '/api/consent', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
        } catch (error) {
            // Local consent remains valid; the server will receive the next update.
        }
    }

    function loadAllowedScripts(preferences) {
        (config.scripts || []).forEach((script) => {
            if (!preferences[script.category]) {
                return;
            }
            const key = `${script.category}:${script.name}:${script.src || script.code || ''}`;
            if (loadedScripts.has(key)) {
                return;
            }
            loadedScripts.add(key);
            injectScript(script);
        });
    }

    function injectScript(script) {
        const element = document.createElement('script');
        element.async = true;

        if (script.type === 'src' && script.src) {
            element.src = script.src;
        } else if (script.code) {
            element.text = script.code;
        } else {
            return;
        }

        (script.position === 'head' ? document.head : document.body).appendChild(element);
    }

    function getStoredConsent() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
        } catch (error) {
            return null;
        }
    }

    function removeBanner() {
        document.querySelectorAll('.cookie-consent').forEach((banner) => banner.remove());
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }
})();
