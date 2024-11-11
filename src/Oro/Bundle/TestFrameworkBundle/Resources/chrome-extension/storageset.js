/* global chrome */
async function initializeSelectors() {
    try {
        const response = await fetch(`${window.location.origin}/media/behat_tests_elements.json`);
        const selectors = await response.json();

        const categorizedSelectors = categorizeSelectors(selectors);
        await chrome.storage.local.set(categorizedSelectors);
    } catch (error) {
        await chrome.storage.local.set({
            elements: [],
            elementsWithBadSelector: []
        });
    }
}

function categorizeSelectors(selectors) {
    const categorized = {
        elements: [],
        elementsWithBadSelector: []
    };

    selectors.forEach(selector => {
        try {
            const result = document.evaluate(
                selector.xpath,
                document,
                null,
                XPathResult.ANY_TYPE,
                null
            );

            if (result.iterateNext()) {
                categorized.elements.push(selector);
            }
        } catch (error) {
            categorized.elementsWithBadSelector.push(selector);
            console.error(`Invalid selector ${selector.name}:`, error);
        }
    });

    return categorized;
}

initializeSelectors();
