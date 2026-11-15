/*
 * Lean Global admin: instant form validation in the browser.
 *
 * Reads the HTML attributes Filament renders from each field's rules (required, minlength, maxlength,
 * min, max, step, type=email) plus data attributes from PHP:
 *   data-lg-pattern / data-lg-pattern-message   → TextInput::livePattern($regex, $message)
 *   data-lg-match="password"                   → must equal the sibling field "…password"
 *   data-lg-wait-for="password_confirmation"   → only checked once that sibling has a value
 *
 * A field is checked when the user leaves it, then on every keystroke. Submitting checks every field
 * and stops the request if one is wrong. The server ALWAYS validates again on submit (full *Rules,
 * incl. unique names and image checks), so this script is only for speed and never for security.
 */
(() => {
    if (window.__lgLiveValidation) return;
    window.__lgLiveValidation = true;

    const FIELD = 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=file]):not([type=search]), textarea';
    // Widgets with their own hidden inputs: left to the server.
    const SKIP = '.choices, .fi-fo-rich-editor, .fi-fo-markdown-editor, .fi-fo-date-time-picker, .fi-fo-tags-input, .fi-fo-key-value, .filepond--root';
    const INVALID = 'lg-invalid';
    const touched = new WeakSet();

    const wrapperOf = (el) => el.closest('.fi-fo-field-wrp');
    const isTracked = (el) => el instanceof Element && el.matches(FIELD) && wrapperOf(el) && !el.closest(SKIP);
    const formFields = (root) => [...root.querySelectorAll(FIELD)].filter(isTracked);
    const sibling = (el, name) => (el.closest('form') || document).querySelector(`[id$=".${CSS.escape(name)}"], [id="${CSS.escape(name)}"]`);

    const labelOf = (el) => {
        const text = wrapperOf(el)?.querySelector('.fi-fo-field-wrp-label span')?.textContent || '';
        return text.replace(/\*/g, '').replace(/\s+/g, ' ').trim() || 'This field';
    };

    /** @returns {string} error message, or '' when valid */
    const check = (el) => {
        if (el.disabled || el.readOnly) return '';

        const value = el.value;
        const text = value.trim();
        const name = labelOf(el);
        const wait = el.dataset.lgWaitFor;

        if (wait && sibling(el, wait)?.value === '') return '';
        if (el.validity?.badInput) return `${name} must be a number.`;
        // data-lg-required: Filament omits `required` on fields inside tabs/collapsibles (see AdminUi)
        if ((el.required || el.dataset.lgRequired === 'true') && text === '') return `${name} is required.`;
        if (text === '') return '';

        const length = [...value].length;
        const minLength = Number(el.getAttribute('minlength')) || 0;
        const maxLength = Number(el.getAttribute('maxlength')) || 0;
        if (minLength && length < minLength) return `${name} must be at least ${minLength} characters.`;
        if (maxLength && length > maxLength) return `${name} must not be longer than ${maxLength} characters.`;

        if (el.type === 'number') {
            const n = Number(value);
            const min = el.getAttribute('min');
            const max = el.getAttribute('max');
            const step = el.getAttribute('step');
            if (Number.isNaN(n)) return `${name} must be a number.`;
            if (step && step !== 'any' && Number.isInteger(Number(step)) && !Number.isInteger(n)) return `${name} must be a whole number.`;
            if (min !== null && max !== null && (n < Number(min) || n > Number(max))) return `${name} must be between ${min} and ${max}.`;
            if (min !== null && n < Number(min)) return `${name} must be at least ${min}.`;
            if (max !== null && n > Number(max)) return `${name} must not be greater than ${max}.`;
        }

        if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(text)) return `${name} must be a valid email address.`;

        if (el.dataset.lgPattern) {
            let ok = true;
            try { ok = new RegExp(el.dataset.lgPattern).test(value); } catch { ok = true; }
            if (!ok) return el.dataset.lgPatternMessage || `${name} has an invalid format.`;
        }

        const match = el.dataset.lgMatch;
        if (match) {
            const other = sibling(el, match);
            if (other && other.value !== value) return 'The passwords do not match.';
        }

        return '';
    };

    const render = (el, message) => {
        const wrapper = wrapperOf(el);
        if (!wrapper) return;
        const box = el.closest('.fi-input-wrp') || el;
        let p = wrapper.querySelector('p.lg-live-error');

        if (message) {
            if (!p) {
                p = document.createElement('p');
                p.className = 'lg-live-error fi-fo-field-wrp-error-message text-sm text-danger-600 dark:text-danger-400';
                p.id = `${el.id || 'field'}-live-error`;
                box.insertAdjacentElement('afterend', p);
            }
            p.textContent = message;
            box.classList.add(INVALID);
            el.setAttribute('aria-invalid', 'true');
            el.setAttribute('aria-describedby', p.id);
        } else {
            p?.remove();
            box.classList.remove(INVALID);
            el.removeAttribute('aria-invalid');
        }
        // The user changed the value, so an older server message for this field is out of date.
        wrapper.querySelectorAll('[data-validation-error]:not(.lg-live-error)').forEach((e) => { e.hidden = true; });
        return !message;
    };

    const validate = (el) => render(el, check(el));

    /** "120 / 300" next to the label of limited text fields. */
    const counter = (el) => {
        const max = Number(el.getAttribute('maxlength')) || 0;
        if (!max || max < 50 || el.type === 'password' || el.type === 'number') return;
        const row = wrapperOf(el)?.querySelector('.fi-fo-field-wrp-label')?.parentElement;
        if (!row) return;
        let c = row.querySelector('.lg-counter');
        if (!c) {
            c = document.createElement('span');
            c.className = 'lg-counter';
            c.setAttribute('aria-hidden', 'true');
            const hint = row.querySelector('.fi-fo-field-wrp-hint');
            hint ? row.insertBefore(c, hint) : row.appendChild(c);
        }
        const length = [...el.value].length;
        c.textContent = `${length} / ${max}`;
        c.classList.toggle('lg-counter-near', length >= max * 0.9);
    };

    /** Re-applies counters + messages after Livewire re-renders the form (it removes nodes it didn't render). */
    const scan = () => {
        document.querySelectorAll('form').forEach((form) => {
            if (!form.querySelector('.fi-fo-field-wrp')) return;
            form.noValidate = true; // our messages replace the browser's bubbles
            formFields(form).forEach((el) => {
                counter(el);
                if (touched.has(el)) validate(el);
            });
        });
    };

    const revalidateLinked = (el) => {
        const form = el.closest('form');
        if (!form) return;
        form.querySelectorAll('[data-lg-match], [data-lg-wait-for]').forEach((other) => {
            if (other !== el && touched.has(other)) validate(other);
        });
    };

    document.addEventListener('focusout', (e) => {
        if (!isTracked(e.target)) return;
        touched.add(e.target);
        validate(e.target);
        revalidateLinked(e.target);
    }, true);

    document.addEventListener('input', (e) => {
        if (!isTracked(e.target)) return;
        counter(e.target);
        if (touched.has(e.target)) validate(e.target);
        revalidateLinked(e.target);
    }, true);

    // Runs before Livewire's wire:submit listener (capture phase) and stops it when a field is wrong.
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.querySelector('.fi-fo-field-wrp')) return;

        const invalid = formFields(form).filter((el) => {
            touched.add(el);
            return !validate(el);
        });
        if (!invalid.length) return;

        e.preventDefault();
        e.stopImmediatePropagation();
        // Prefer a visible field; otherwise open the tab / collapsed section / repeater item that hides it
        // (Filament containers listen for a bubbling "expand" event), then focus once Alpine has shown it.
        const first = invalid.find((el) => el.offsetParent !== null) || invalid[0];
        if (first.offsetParent === null) first.dispatchEvent(new CustomEvent('expand', { bubbles: true }));
        requestAnimationFrame(() => {
            first.scrollIntoView({ block: 'center', behavior: 'smooth' });
            first.focus({ preventScroll: true });
        });
    }, true);

    const hookLivewire = () => window.Livewire?.hook?.('commit', ({ succeed }) => succeed(() => requestAnimationFrame(scan)));
    if (window.Livewire?.hook) hookLivewire();
    else document.addEventListener('livewire:init', hookLivewire, { once: true });

    document.addEventListener('livewire:navigated', scan);
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', scan) : scan();
})();
