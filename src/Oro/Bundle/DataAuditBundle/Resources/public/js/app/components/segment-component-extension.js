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

            var originalInitFieldsLoader = segment.initFieldsLoader;
            segment.initFieldsLoader = function() {
                var auditFieldsLoaderOptions = this.options.auditFieldsLoader;
                this.$auditFieldsLoader = originalInitFieldsLoader.call(this, auditFieldsLoaderOptions);

                return originalInitFieldsLoader.apply(this, arguments);
            };

            var originalConfigureFilters = segment.configureFilters;
            segment.configureFilters = function() {
                var $condition = this.conditionBuilderComponent.view.getCriteriaOrigin('condition-data-audit');
                if ($condition.length) {
                    var toggleCondition = function(entityClassName, data) {
                        $condition.toggle(entityClassName in data);
                    };
                    toggleCondition(this.$entityChoice.val(), this.$auditFieldsLoader.fieldsLoader('getFieldsData'));
                    this.on(this.options.auditFieldsLoader.loadEvent, toggleCondition);
                }
                originalConfigureFilters.apply(this, arguments);
            };

            var originalInitEntityChangeEvents = segment.initEntityChangeEvents;
            segment.initEntityChangeEvents = function() {
                var loadHandler = function() {
                    this.trigger(
                        this.options.auditFieldsLoader.loadEvent,
                        this.$auditFieldsLoader.val(),
                        this.$auditFieldsLoader.fieldsLoader('getFieldsData')
                    );
                }.bind(this);

                loadHandler();
                this.$auditFieldsLoader.on('fieldsloaderupdate', loadHandler);

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
