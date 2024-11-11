class PopupManager {
    constructor() {
        this.loadingIndicator = document.getElementById('loadingIndicator');
        this.elementList = document.getElementById('elementList');
        this.searchBar = document.getElementById('searchBar');

        this.initialize();
    }

    initialize() {
        document.addEventListener('DOMContentLoaded', () => {
            this.showLoading();
            this.initializeElementList();
            this.initializeSearch();
            chrome.runtime.connect({name: "popup"});
        });
    }

    showLoading() {
        this.loadingIndicator.style.display = 'block';
        this.elementList.style.display = 'none';
    }

    hideLoading() {
        this.loadingIndicator.style.display = 'none';
        this.elementList.style.display = 'block';
    }

    async initializeElementList() {
        try {
            const [tab] = await chrome.tabs.query({active: true, currentWindow: true});

            await chrome.scripting.executeScript({
                target: {tabId: tab.id},
                files: ['storageset.js']
            });

            this.setupStorageListener();
            this.setupFallbackTimer();
        } catch (error) {
            console.error('Failed to initialize element list:', error);
            this.showError('Failed to initialize. Please refresh the page and try again.');
        }
    }

    showError(message) {
        if (this.elementList) {
            this.elementList.innerHTML = `<div class="error-message">${message}</div>`;
            this.hideLoading();
        }
    }

    setupStorageListener() {
        chrome.storage.onChanged.addListener((changes, areaName) => {
            if (areaName === 'local' &&
                (changes.elements ||
                    changes.elementsWithBadSelector)) {
                this.hideLoading();
                this.updateElementList();
            }
        });
    }

    setupFallbackTimer() {
        setTimeout(() => {
            this.updateElementList();
            this.hideLoading();
        }, 1000);
    }

    createListItem(element, color) {
        const listItem = document.createElement('li');
        const selectorType = element.css ? 'css' : 'xpath';
        const selectorValue = element.css || element.xpath;

        listItem.innerHTML = `
            <b class="selectable">${element.name}</b>
            <span>(${selectorType}: ${selectorValue})</span>
        `;

        if (color) {
            listItem.style.color = color;
        }

        return listItem;
    }

    async attachClickHandler(listItem, element, action) {
        listItem.addEventListener('click', async () => {
            try {
                const [tab] = await chrome.tabs.query({active: true, currentWindow: true});
                await chrome.tabs.sendMessage(tab.id, {
                    action: action,
                    element: element
                });
            } catch (error) {
                console.error('Failed to send message:', error);
            }
        });
    }

    updateElementList() {
        chrome.storage.local.get(
            ['elements', 'elementsWithBadSelector'],
            (data) => {
                this.elementList.innerHTML = '';

                if (!data.elements && !data.elementsWithBadSelector) {
                    this.showError('No elements found.');
                    return;
                }

                // Handle found elements
                if (data.elements?.length) {
                    data.elements.forEach(element => {
                        const listItem = this.createListItem(element, 'green');
                        this.attachClickHandler(listItem, element, 'highlight');
                        this.elementList.appendChild(listItem);
                    });
                }

                // Handle elements with bad selectors
                if (data.elementsWithBadSelector?.length) {
                    data.elementsWithBadSelector.forEach(element => {
                        const listItem = this.createListItem(element, 'red');
                        this.attachClickHandler(listItem, element, 'removeHighlight');
                        this.elementList.appendChild(listItem);
                    });
                }
            }
        );
    }

    initializeSearch() {
        this.searchBar.addEventListener('input', () => {
            const searchText = this.searchBar.value.toLowerCase();
            const items = this.elementList.getElementsByTagName('li');

            Array.from(items).forEach(item => {
                const text = item.textContent || item.innerText;
                item.style.display = text.toLowerCase().includes(searchText) ? '' : 'none';
            });
        });
    }
}

new PopupManager();
