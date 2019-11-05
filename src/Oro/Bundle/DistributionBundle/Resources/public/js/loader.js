// eslint-disable-next-line no-unused-vars
function Loader(element) {
    'use strict';

    let clickedElement = null;
    const loadingElement = element;

    return {
        setClickedElement: function(element) {
            clickedElement = element;
        },
        displayLoadingProgress: function() {
            if (clickedElement) {
                clickedElement.hide();
                loadingElement.insertAfter(clickedElement);
            }
            loadingElement.show();
        },
        displayOriginalElement: function() {
            if (clickedElement) {
                clickedElement.show();
            }
            loadingElement.hide();
        }
    };
}
