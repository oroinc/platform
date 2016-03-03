define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view'
], function($, _, BaseView) {
    'use strict';

    var OrganizationStructureTreeView = BaseView.extend({
        dataInputSelector: null,

        initialize: function() {
            this.$el.on('change', 'input.bu:checkbox', function () {
                var org_id = $(this).data('organization');
                $('input.org-id-' + org_id + ':checkbox').prop('checked', true);
            });
            this.$el.on('change', 'input.org:checkbox:not(:checked)', function () {
                $('input.bu:checkbox[data-organization="' + $(this).val() + '"]').prop('checked', false);
            });

            this.$el.closest('form').on('submit' + this.eventNamespace(), _.bind(this._onSubmit, this));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.closest('form').off(this.eventNamespace());
            OrganizationStructureTreeView.__super__.dispose.apply(this, arguments);
        },

        _onSubmit: function() {
//            var values = _.map(this.$('input:checked'), _.property('value'));
//            this.$(this.dataInputSelector).val(JSON.stringify(values));
        }
    });

    return OrganizationStructureTreeView;
});
