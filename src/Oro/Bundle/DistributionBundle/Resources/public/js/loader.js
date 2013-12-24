function Loader(element) {
    var clickedElement = null;
    var loadingElement = element;

    return {
        setClickedElement: function (element) {
            clickedElement = element;
        },
        displayLoadingProgress: function () {
            if (clickedElement) {
                clickedElement.hide();
                loadingElement.insertAfter(clickedElement);
            }
            loadingElement.show();
        },
        displayOriginalElement: function () {
            clickedElement && clickedElement.show();
            loadingElement.hide();
        }

    }
}