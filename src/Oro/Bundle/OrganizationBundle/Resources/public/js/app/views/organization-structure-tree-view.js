define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view'
], function($, _, BaseView) {
    'use strict';

    var OrganizationStructureTreeView = BaseView.extend({
        dataInputSelector: null,

        initialize: function(options) {
            if (!_.has(options, 'dataInputSelector')) {
                throw new Error('Required option "dataInputSelector" not found.');
            }

            this.dataInputSelector = options.dataInputSelector;

            this.$el.on('change', 'input.bu:checkbox', function () {
                var org_id = $(this).data('organization');
                $('input.org-id-' + org_id + ':checkbox').prop('checked', true);
            });
            this.$el.on('change', 'input.org:checkbox:not(:checked)', function () {
                $('input.bu:checkbox[data-organization="' + $(this).val() + '"]').prop('checked', false);
            });

            this.$el.closest('form')
                .on('submit' + this.eventNamespace(), _.bind(this._onSubmit, this));

            this.$el.closest('form')
                .find('button[type=submit]')
                .on('click' + this.eventNamespace(), _.bind(this._preSubmit, this));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.closest('form').off(this.eventNamespace());
            this.$el.closest('form').find('button[type=submit]').off(this.eventNamespace());
            OrganizationStructureTreeView.__super__.dispose.apply(this, arguments);
        },

        /**
         * Make sending of the form way faster when having way too much checkboxes
         * (tested with 10 000)
         */
        _preSubmit: function() {
            this.$('.accordion-toggle:not(.collapse)').click();
        },

        _onSubmit: function(e) {
            this.$(this.dataInputSelector).val(JSON.stringify({
                organizations: _.map(this.$('input[data-name="organization"]:checked'), _.property('value')),
                businessUnits: _.map(this.$('input[data-name="businessUnit"]:checked'), _.property('value'))
            }));
        }
    });

    return OrganizationStructureTreeView;
});
