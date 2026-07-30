import BaseView from 'oroui/js/app/views/base/view';

/**
 * Makes a "Back"/"Cancel" link navigate to the previous page in the browser history
 * when it is safe (the target belongs to the application), otherwise the link works
 * as usual and leads to the server-rendered fallback URL from the "href" attribute.
 */
const GoBackView = BaseView.extend({
    events: {
        click: 'onClick'
    },

    constructor: function GoBackView(...args) {
        GoBackView.__super__.constructor.apply(this, args);
    },

    onClick(event) {
        // a modified click opens the link in a new tab - keep the default behavior
        if (event.ctrlKey || event.metaKey || event.shiftKey) {
            return;
        }

        const entry = this.findPreviousPageEntry();
        if (!entry) {
            return; // the click proceeds to the fallback URL from the "href" attribute
        }

        event.preventDefault();
        this.goToEntry(entry);
    },

    /**
     * Finds the nearest safe history entry that leads to a different page, skipping
     * same-page entries: in-page anchor navigation (e.g. form tabs/scrollspy, which only
     * change the URL hash) and full-page redirects that land back on the same page
     * (e.g. a workflow transition redirecting to its "originalUrl", which Chaplin reloads
     * with a cache-busting "_rand" query parameter).
     *
     * @returns {{key: string}|{delta: number}|null}
     */
    findPreviousPageEntry() {
        // Navigation API entries are limited to the same origin and expose the whole
        // in-tab history, so same-page entries can be looked ahead and skipped
        if (window.navigation !== undefined) {
            const entries = window.navigation.entries();
            const currentIndex = window.navigation.currentEntry.index;
            for (let i = currentIndex - 1; i >= 0; i--) {
                const url = entries[i].url;
                if (url && new URL(url).pathname !== window.location.pathname) {
                    return {key: entries[i].key};
                }
            }
            return null;
        }

        // no Navigation API: an in-app pushState navigation already happened in this tab
        // (the backoffice SPA), or the page was opened from a same-origin page (the storefront);
        // same-page entries cannot be looked ahead and skipped here
        if (window.history.state !== null || document.referrer.startsWith(`${window.location.origin}/`)) {
            return {delta: -1};
        }

        return null;
    },

    goToEntry(entry) {
        if (entry.key) {
            window.navigation.traverseTo(entry.key);
        } else {
            window.history.go(entry.delta);
        }
    }
});

export default GoBackView;
