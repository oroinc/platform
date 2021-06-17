import $ from 'jquery';
import 'styled-scroll-bar';

const scrollBarName = 'styled-scrollbar';

$(document)
    .on('initLayout content:changed', function(e) {
        $(e.target).find('[data-' + scrollBarName + ']').each(function() {
            const data = $(this).data(scrollBarName);

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
