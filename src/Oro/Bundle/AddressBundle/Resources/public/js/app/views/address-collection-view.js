define(function(require) {
    'use strict';

    var AddressCollectionView;
    var BaseView = require('oroui/js/app/views/base/view');

    AddressCollectionView = BaseView.extend({
        events: {
            'click [data-name="field__types"] input': 'onClicked'
        },

        onClicked: function(e) {
            var currentTarget = e.currentTarget;

            if (!currentTarget.checked) {
                return;
            }

            this.$('[name$="[types][]"][value="' + currentTarget.value + '"]').prop('checked', false);

            currentTarget.checked = true;
        }
    });

    return AddressCollectionView;
});
