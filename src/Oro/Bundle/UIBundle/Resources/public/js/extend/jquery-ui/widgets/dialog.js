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
        const {title: titleText, closeOnDialogTitle} = this.options;

        title.append(
            this._renderBadge(),
            $('<span/>', {'class': 'ui-dialog-title__inner'}).text(titleText)
        );

        if (closeOnDialogTitle) {
            title
                .attr({role: 'button', tabindex: '0'})
                .on('click', e => {
                    e.preventDefault();
                    this.close(e);
                });
        }
    },

    _renderBadge() {
        const {dialogTitleIcon, dialogTitleBadge} = this.options;

        if (dialogTitleIcon) {
            let $icon = $('<span />', {'class': dialogTitleIcon, 'aria-hidden': 'true'});

            if (dialogTitleBadge) {
                $icon.wrap($('<span />', {'class': 'dialog-badge', 'aria-hidden': 'true'}));
                $icon = $icon.parent();
            }
            return $icon;
        }

        return $();
    }
});
