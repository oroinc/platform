define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui/widgets/sortable');

    const EnumValuesView = BaseView.extend({
        /**
         * @type {boolean}
         */
        multiple: false,

        events: {
            'click a.add-list-item': 'reindexValues',
            'click [name$="[is_default]"]': 'onIsDefaultClick',
            'click [data-name="clear-default"]': 'clearDefault',
            'content:remove': function() {
                // execute right after content removed
                _.defer(this.updateClearDefault.bind(this));
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function EnumValuesView(options) {
            EnumValuesView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['multiple']));
            EnumValuesView.__super__.initialize.call(this, options);
        },

        render: function() {
            this.appendClearDefault();
            this.updateClearDefault();
            this.initSortable();
            this.reindexValues();
            return this;
        },

        reindexValues: function() {
            let index = 1;
            this.$('[name$="[priority]"]').each(function() {
                $(this).val(index++);
            });
        },

        initSortable: function() {
            this.$('[data-name="field__enum-options"]').sortable({
                handle: '[data-name="sortable-handle"]',
                tolerance: 'pointer',
                delay: 100,
                containment: 'parent',
                stop: this.reindexValues.bind(this)
            });
        },

        onIsDefaultClick: function(e) {
            if (!this.multiple) {
                this.$('[name$="[is_default]"]').each(function() {
                    const el = this;
                    if (el.checked && el !== e.currentTarget) {
                        el.checked = false;
                    }
                });
            }
            this.updateClearDefault();
        },

        clearDefault: function() {
            this.$('[name$="[is_default]"]').each(function() {
                const el = this;
                el.checked = false;
            });
            this.updateClearDefault();
        },

        appendClearDefault: function() {
            this.$el.append('<a data-name="clear-default" class="enum-value-collection__clear-default" href="#">' +
                __('oro.entity_extend.enum_value_collection.clear_default') + '</a>');
        },

        updateClearDefault: function() {
            const hasDefault = this.$('[name$="[is_default]"]:checked').length > 0;
            this.$('[data-name="clear-default"]').toggleClass('disabled', !hasDefault);
        }
    });

    return EnumValuesView;
});
