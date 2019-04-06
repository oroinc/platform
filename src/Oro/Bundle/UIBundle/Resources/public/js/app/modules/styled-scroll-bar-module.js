define(function(require) {
    'use strict';

    var $ = require('jquery');
    var scrollBarName = 'styled-scrollbar';

    require('styled-scroll-bar');

    $(document)
        .on('initLayout content:changed', function(e) {
            $(e.target).find('[data-' + scrollBarName + ']').each(function() {
                var data = $(this).data(scrollBarName);

                $(this).styledScrollBar(typeof data === 'object' ? data : {});
            });
        })
        .on('disposeLayout content:remove', function(e) {
            $(e.target).find('[data-' + scrollBarName + ']').each(function() {
                if ($(this).data('oro.styledScrollBar')) {
                    $(this).styledScrollBar('dispose');
                }
            });
        });
});
