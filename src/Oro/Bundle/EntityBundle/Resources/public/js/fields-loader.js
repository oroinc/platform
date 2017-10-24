define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var EntityFieldsUtil = require('oroentity/js/entity-fields-util');

    require('jquery-ui');

    /**
     * Widget responsible for loading fields of selected entity
     */
    $.widget('oroentity.fieldsLoader', {
        options: {
            router: null,
            routingParams: {},
            afterRevertCallback: null,
            // supports 'oroui/js/modal' confirmation dialog
            confirm: null,
            requireConfirm: function() { return true; }
        },

        _create: function() {
            this.setFieldsData(this.element.data('fields') || []);

            this._on({
                change: this._onChange
            });
        },

        _onChange: function(e, extraArgs) {
            _.extend(e, extraArgs);
            var oldVal;
            var confirm = this.options.confirm;
            if (confirm && this.options.requireConfirm()) {
                // @todo support also other kind of inputs than select2
                oldVal = (e.removed && e.removed.id) || null;
                this._confirm(confirm, e.val, oldVal);
            } else {
                this.loadFields();
            }
        },

        loadFields: function() {
            var routeName = this.options.router;
            var routeParams = this.options.routingParams;

            var additionalRequestParams = this.element.data('select2_query_additional_params');
            if (additionalRequestParams) {
                routeParams = $.extend({}, routeParams, additionalRequestParams);
            }

            $.ajax({
                url: routing.generate(routeName, routeParams),
                success: $.proxy(this._onLoaded, this),
                beforeSend: $.proxy(this._trigger, this, 'start'),
                complete: $.proxy(this._trigger, this, 'complete')
            });
        },

        getEntityName: function() {
            return this.element.val();
        },

        setFieldsData: function(data) {
            var fields = EntityFieldsUtil.convertData(data);
            this.element.data('fields', fields);
            this._trigger('update', null, [fields]);
        },

        getFieldsData: function() {
            return this.element.data('fields');
        },

        _confirm: function(confirm, newVal, oldVal) {
            if (!oldVal) {
                return;
            }
            var $el = this.element;
            var load = $.proxy(this.loadFields, this);
            var revert = $.proxy(function() {
                    var $entityChoice = $el.data('relatedChoice');
                    if ($entityChoice && $entityChoice.val() !== oldVal) {
                        $entityChoice.val(oldVal).change();
                    }
                    $el.val(oldVal).change();
                    if ($.isFunction(this.options.afterRevertCallback)) {
                        this.options.afterRevertCallback.call(this, $el);
                    }
                }, this);
            confirm.on('ok', load);
            confirm.on('cancel', revert);
            confirm.once('hidden', function() {
                confirm.off('ok', load);
                confirm.off('cancel', revert);
            });
            confirm.open();
        },

        _onLoaded: function(data) {
            this.setFieldsData(data);
        }
    });
});
