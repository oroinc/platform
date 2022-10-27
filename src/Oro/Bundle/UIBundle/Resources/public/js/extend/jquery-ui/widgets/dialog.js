import $ from 'jquery';
import 'jquery-ui/widgets/dialog';

$.widget('ui.dialog', $.ui.dialog, {
    /**
     * Replace method because some browsers return string 'auto' if property z-index not specified.
     * */
    _moveToTop: function() {
        const zIndex = this.uiDialog.css('z-index');
        const numberRegexp = /^\d+$/;
        if (typeof zIndex === 'string' && !numberRegexp.test(zIndex)) {
            this.uiDialog.css('z-index', 910);
        }
        this._super();
    },

    _title: function(title) {
        title.html(
            $('<span/>', {'class': 'ui-dialog-title__inner'}).text(this.options.title)
        );
    }
});
