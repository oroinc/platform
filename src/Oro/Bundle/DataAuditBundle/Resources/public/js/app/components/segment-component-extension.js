define([
    'jquery',
    'underscore'
], function($, _) {
    'use strict';

    return {
        load: function(segment) {
            segment.defaults = $.extend(true, segment.defaults, {
                defaults: {
                    auditFieldsLoader: {
                        loadingMaskParent: '',
                        router: null,
                        routingParams: {},
                        fieldsData: [],
                        loadEvent: 'auditFieldsLoaded'
                    }
                }
            });

            var originalConfigureFilters = segment.configureFilters;
            segment.configureFilters = function() {
                var $criteria = $(this.options.filters.criteriaList);

                var $dataAuditCondition = $criteria.find('[data-criteria=condition-data-audit]');
                if (!_.isEmpty($dataAuditCondition)) {
                    this.on('auditFieldsLoaded', function(className, data) {
                        $dataAuditCondition.toggleClass('disabled', !data[className]);
                    });
                    $.extend(true, $dataAuditCondition.data('options'), {
                        fieldChoice: this.options.fieldChoiceOptions,
                        filters: this.options.auditFilters,
                        hierarchy: this.options.metadata.hierarchy
                    });
                }

                originalConfigureFilters.apply(this, arguments);
            };

            var originalInitFieldsLoader = segment.initFieldsLoader;
            segment.initFieldsLoader = function() {
                this.$auditFieldsLoader = originalInitFieldsLoader.call(this, this.options.auditFieldsLoader);

                return originalInitFieldsLoader.apply(this, arguments);
            };

            var originalInitEntityChangeEvents = segment.initEntityChangeEvents;
            segment.initEntityChangeEvents = function() {
                this.trigger(
                this.options.auditFieldsLoader.loadEvent,
                this.$auditFieldsLoader.val(),
                this.$auditFieldsLoader.fieldsLoader('getFieldsData'));

                return originalInitEntityChangeEvents.apply(this, arguments);
            };

            var originalOnEntityChangeConfirm = segment._onEntityChangeConfirm;
            segment._onEntityChangeConfirm = function(e, additionalOptions) {
                this.$auditFieldsLoader.val(e.val).trigger('change', additionalOptions);

                return originalOnEntityChangeConfirm.apply(this, arguments);
            };
        }
    };
});
