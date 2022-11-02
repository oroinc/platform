define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const AddressCollectionView = BaseView.extend({
        events: {
            'click [data-name="field__types"] input': 'onClicked'
        },

        /**
         * @inheritdoc
         */
        constructor: function AddressCollectionView(options) {
            AddressCollectionView.__super__.constructor.call(this, options);
        },

        onClicked: function(e) {
            const currentTarget = e.currentTarget;

            if (!currentTarget.checked) {
                return;
            }

            this.$('[name$="[types][]"][value="' + currentTarget.value + '"]').prop('checked', false);

            currentTarget.checked = true;
        }
    });

    return AddressCollectionView;
});
