import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/rotikita-page-loader.js', import.meta.url), 'utf8');

class FakeElement {
    constructor() { this.attributes = new Set(); }
    closest(selector) { return selector === 'a[href]' ? this : null; }
    hasAttribute(name) { return this.attributes.has(name); }
}

class FakeForm extends FakeElement {
    constructor(options = {}) {
        super();
        this.action = options.action ?? 'https://rotikita.test/submit';
        this.target = options.target ?? '';
        this.attributes = new Set(options.attributes ?? []);
    }
}

function createHarness() {
    const documentEvents = new Map();
    const windowEvents = new Map();
    const timers = new Map();
    const microtasks = [];
    const session = new Map();
    const classes = new Set();
    let nextTimerId = 1;
    const loader = { attributes: { 'aria-hidden': 'true' }, setAttribute(name, value) { this.attributes[name] = value; } };
    const document = {
        readyState: 'loading',
        documentElement: { classList: { add: (name) => classes.add(name), remove: (name) => classes.delete(name) } },
        querySelector: () => loader,
        addEventListener: (name, callback) => documentEvents.set(name, callback),
    };
    const storage = {
        getItem: (key) => session.get(key) ?? null,
        setItem: (key, value) => session.set(key, value),
        removeItem: (key) => session.delete(key),
    };
    const window = {
        location: { href: 'https://rotikita.test/home', origin: 'https://rotikita.test', pathname: '/home', search: '', hash: '' },
        sessionStorage: storage,
        setTimeout: (callback) => { const id = nextTimerId++; timers.set(id, callback); return id; },
        clearTimeout: (id) => timers.delete(id),
        addEventListener: (name, callback) => windowEvents.set(name, callback),
    };
    const context = {
        document, window, Element: FakeElement, HTMLFormElement: FakeForm, URL, Date, Number, String,
        performance: { getEntriesByType: () => [{ type: 'navigate' }] },
        queueMicrotask: (callback) => microtasks.push(callback),
    };
    vm.runInNewContext(source, context);

    return {
        classes, documentEvents, loader, session, timers, windowEvents,
        click(link, options = {}) {
            const event = {
                target: link,
                button: options.button ?? 0,
                defaultPrevented: options.defaultPrevented ?? false,
                metaKey: options.metaKey ?? false,
                ctrlKey: options.ctrlKey ?? false,
                shiftKey: options.shiftKey ?? false,
                altKey: options.altKey ?? false,
            };
            documentEvents.get('click')(event);
            return event;
        },
        submit(form, options = {}) {
            const event = { target: form, defaultPrevented: options.defaultPrevented ?? false };
            documentEvents.get('submit')(event);
            return event;
        },
        flushMicrotasks() { while (microtasks.length) microtasks.shift()(); },
        link(href, options = {}) {
            const link = new FakeElement();
            link.href = href;
            link.target = options.target ?? '';
            if (options.download) link.attributes.add('download');
            return link;
        },
    };
}

test('loader activates for eligible internal navigation links only', () => {
    const harness = createHarness();
    harness.click(harness.link('https://rotikita.test/artikel'));
    assert.equal(harness.loader.attributes['aria-hidden'], 'false');
    assert.equal(harness.session.get('rotikita:page-navigation') !== undefined, true);

    for (const [link, options] of [
        [harness.link('https://example.com/page'), {}],
        [harness.link('https://rotikita.test/home#contact'), {}],
        [harness.link('https://rotikita.test/file.pdf', { download: true }), {}],
        [harness.link('https://rotikita.test/login', { target: '_blank' }), {}],
        [harness.link('https://rotikita.test/login'), { ctrlKey: true }],
        [harness.link('https://rotikita.test/login'), { button: 1 }],
        [harness.link('https://rotikita.test/login'), { defaultPrevented: true }],
    ]) {
        const next = createHarness();
        next.click(link, options);
        assert.equal(next.loader.attributes['aria-hidden'], 'true');
        assert.equal(next.session.has('rotikita:page-navigation'), false);
    }
});

test('only successful normal same-origin form submissions activate the loader', () => {
    const harness = createHarness();
    harness.submit(new FakeForm());
    assert.equal(harness.loader.attributes['aria-hidden'], 'false');

    for (const [form, options] of [
        [new FakeForm({ action: 'https://example.com/submit' }), {}],
        [new FakeForm({ attributes: ['data-ajax'] }), {}],
        [new FakeForm({ attributes: ['data-remote'] }), {}],
        [new FakeForm({ attributes: ['wire:submit'] }), {}],
        [new FakeForm(), { defaultPrevented: true }],
        [new FakeForm({ target: '_blank' }), {}],
    ]) {
        const next = createHarness();
        next.submit(form, options);
        assert.equal(next.loader.attributes['aria-hidden'], 'true');
    }
});

test('loader clears when a later JavaScript handler cancels navigation', () => {
    const harness = createHarness();
    const event = harness.click(harness.link('https://rotikita.test/artikel'));
    event.defaultPrevented = true;
    harness.flushMicrotasks();
    assert.equal(harness.loader.attributes['aria-hidden'], 'true');
    assert.equal(harness.session.has('rotikita:page-navigation'), false);
});

test('loader hides after page load and retains an accessible status for reduced motion', () => {
    const harness = createHarness();
    harness.click(harness.link('https://rotikita.test/artikel'));
    harness.windowEvents.get('load')();
    for (const callback of harness.timers.values()) callback();
    assert.equal(harness.loader.attributes['aria-hidden'], 'true');
    assert.equal(harness.classes.has('rks-navigation-pending'), false);

    const markup = readFileSync(new URL('../../resources/views/partials/page-loader.blade.php', import.meta.url), 'utf8');
    const styles = readFileSync(new URL('../../public/css/page-transitions.css', import.meta.url), 'utf8');
    assert.match(markup, /aria-live="polite"/);
    assert.match(markup, /Menyiapkan halaman/);
    assert.match(styles, /prefers-reduced-motion: reduce/);
});
