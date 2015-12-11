define(function() {
    'use strict';
    return {
        applyTo: function(select2el, marginsData) {
            var choices = select2el.find('.select2-search-choice');
            var widthPieces = Math.ceil(Math.pow(choices.length, 0.6));
            var widthes = choices.map(function(i, item) {return item.clientWidth;});
            widthes.sort();
            var percentile90 = widthes[Math.floor(widthes.length * 0.9)];
            select2el.find('.select2-choices').width(
                (percentile90 + marginsData.SELECTED_ITEMS_H_MARGIN_BETWEEN) * widthPieces +
                marginsData.SELECTED_ITEMS_H_INCREMENT
            );
        },
    };
});
