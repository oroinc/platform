/*global define*/
/*jslint nomen: true*/
define([
    'jquery',
    'underscore',
], function ($, _) {

    return {
        load: function (Segment) {
            Segment.defaults = $.extend(true, Segment.defaults, {
                defaults: {
                    auditFieldsLoader: {
                        loadingMaskParent: '',
                        router: null,
                        routingParams: {},
                        fieldsData: [],
                        loadEvent: 'auditFieldsLoaded'
                    },
                }
            });

            var originalConfigureFilters = Segment.configureFilters;
            Segment.configureFilters = function () {
                var $criteria = $(this.options.filters.criteriaList);

                var $dataAuditCondition = $criteria.find('[data-criteria=condition-data-audit]');
                if (!_.isEmpty($dataAuditCondition)) {
                    this.on('auditFieldsLoaded', function (className, data) {
                        $dataAuditCondition.toggleClass('disabled', !data[className]);
                    });
                    $.extend(true, $dataAuditCondition.data('options'), {
                        fieldChoice: this.options.fieldChoiceOptions,
                        filters: this.options.metadata.filters,
                        hierarchy: this.options.metadata.hierarchy
                    });
                }

                originalConfigureFilters.apply(this, arguments);
            };

            var originalInitFieldsLoader = Segment.initFieldsLoader;
            Segment.initFieldsLoader = function () {
                this.$auditFieldsLoader = originalInitFieldsLoader.call(this, this.options.auditFieldsLoader);

                return originalInitFieldsLoader.apply(this, arguments);
            };

            var originalInitEntityChangeEvents = Segment.initEntityChangeEvents;
            Segment.initEntityChangeEvents = function () {
                this.trigger(
                this.options.auditFieldsLoader.loadEvent,
                this.$auditFieldsLoader.val(),
                this.$auditFieldsLoader.fieldsLoader('getFieldsData'));

                return originalInitEntityChangeEvents.apply(this, arguments);
            };

            var original_onEntityChangeConfirm = Segment._onEntityChangeConfirm;
            Segment._onEntityChangeConfirm = function (e, additionalOptions) {
                this.$auditFieldsLoader.val(e.val).trigger('change', additionalOptions);

                return original_onEntityChangeConfirm.apply(this, arguments);
            };
        }
    };
});
