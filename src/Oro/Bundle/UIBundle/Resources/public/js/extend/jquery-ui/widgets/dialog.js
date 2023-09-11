import $ from 'jquery';
import {macros} from 'underscore';
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

        if (!titleText) {
            title.hide();
        }

        title.append(
            this._renderIcon(),
            $('<span/>', {'class': 'ui-dialog-title__inner'}).text(titleText)
        );

        if (closeOnDialogTitle && titleText) {
            title
                .attr({role: 'button', tabindex: '0'})
                .on('click', e => {
                    e.preventDefault();
                    this.close(e);
                });
        }
    },

    _renderIcon() {
        const {dialogTitleIcon} = this.options;

        if (dialogTitleIcon) {
            return $(`<span class="dialog-icon" aria-hidden="true">
                ${macros('orofrontend::renderIcon')({id: dialogTitleIcon})}
            </span>`);
        }

        return $();
    }
});
