/* global chrome */
const STYLES = `
.page-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.1);
    z-index: 9999999;
}

.element-highlight {
    position: fixed;
    pointer-events: none;
    z-index: 10000;
    border: 2px solid #FF4444;
    background-color: rgba(255, 68, 68, 0.2);
    border-radius: 2px;
}

.warning-message {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border: 2px solid #FF4444;
    background-color: rgb(255, 68, 68);
    color: white;
    padding: 20px;
    font-size: 18px;
    z-index: 10000;
}`;

class ElementHighlighter {
    constructor() {
        this.highlightedElements = [];
        this.container = this.initializeContainer();
        this.initializeStyles();
        this.initializeEventListeners();
    }

    initializeContainer() {
        const container = document.createElement('div');
        document.body.appendChild(container);
        container.addEventListener('click', () => this.removeHighlights());
        return container;
    }

    initializeStyles() {
        const styleElement = document.createElement('style');
        styleElement.textContent = STYLES;
        document.head.appendChild(styleElement);
    }

    initializeEventListeners() {
        const handleViewportChange = this.debounce(() => this.removeHighlights(), 100);
        window.addEventListener('scroll', handleViewportChange, {passive: true});
        window.addEventListener('resize', handleViewportChange, {passive: true});

        chrome.runtime.onMessage.addListener(this.handleMessage.bind(this));
    }

    handleMessage(request, sender, sendResponse) {
        if (request.action === 'highlight') {
            this.highlightElements(request.element);
        } else if (request.action === 'removeHighlight') {
            this.removeHighlights();
        }
    }

    highlightElements(selectorData) {
        const elements = this.findElementsByXPath(selectorData.xpath);

        if (elements.length === 0) return;

        this.removeHighlights();
        this.highlightedElements = elements;

        let visibleElementFound = false;
        elements.forEach((element, index) => {
            if (!this.isElementVisible(element)) return;

            visibleElementFound = true;
            this.createHighlightOverlay(element);
        });

        if (!visibleElementFound) {
            this.showWarning('Element is not visible or out of the viewport!');
        }

        this.container.className = 'page-overlay';
    }

    findElementsByXPath(xpath) {
        const result = document.evaluate(
            xpath,
            document,
            null,
            XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
            null
        );
        return Array.from({length: result.snapshotLength}, (_, i) => result.snapshotItem(i));
    }

    createHighlightOverlay(element) {
        const rect = element.getBoundingClientRect();
        const overlay = document.createElement('div');
        overlay.className = 'element-highlight';
        overlay.style.left = `${rect.left}px`;
        overlay.style.top = `${rect.top}px`;
        overlay.style.width = `${rect.width}px`;
        overlay.style.height = `${rect.height}px`;
        this.container.appendChild(overlay);
    }

    isElementVisible(element) {
        if (!element?.offsetParent) return false;

        const style = window.getComputedStyle(element);
        if (style.display === 'none' ||
            style.visibility === 'hidden' ||
            style.opacity === '0') {
            return false;
        }

        const rect = element.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return false;

        const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        return (
            rect.top < viewportHeight &&
            rect.bottom > 0 &&
            rect.left < viewportWidth &&
            rect.right > 0
        );
    }

    showWarning(message) {
        const warning = document.createElement('div');
        warning.className = 'warning-message';
        warning.textContent = message;
        this.container.appendChild(warning);
    }

    removeHighlights() {
        this.container.className = '';
        this.container.innerHTML = '';
        this.highlightedElements = [];
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

new ElementHighlighter();
