define(function(require) {
    'use strict';

    var LanguagesSelectView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    LanguagesSelectView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectSelector: 'select'
        },

        /**
         * @property {jQuery.Element}
         */
        $select: null,

        /**
         * @inheritDoc
         */
        constructor: function LanguagesSelectView() {
            LanguagesSelectView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);

            this.$select = this.$el.find(this.options.selectSelector);
            this.$select
                .on('change' + this.eventNamespace(), _.bind(this.onChange, this));
        },

        /**
         * Handle change select
         */
        onChange: function() {
            var options = this.$select.find('option:selected');
            var selected = options.map(function(index, option) {
                var $option = $(option);

                return {
                    id: $option.val(),
                    label: $option.text()
                };
            });

            mediator.trigger('supported_languages:changed', selected);
        },

        /**
         * {@inheritDoc}
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$select.off('change' + this.eventNamespace());

            LanguagesSelectView.__super__.dispose.call(this);
        }
    });

    return LanguagesSelectView;
});
