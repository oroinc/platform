/*global define*/
/*jslint nomen: true*/
define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var Util = require('oroentity/js/entity-fields-util');
    require('oroentity/js/fields-loader');
    require('jquery.select2');


    /**
     * @export ororeport/js/chart_options
     * @class  ororeport.ChartOptions
     */
    return {

        /**
         * @property {Object}
         */
        options: {
            childTemplate: '[id^=<%= id %>_]',
            optionsTemplate: '<%= field %>(<%= group %>,<%= name %>,<%= type %>)',
            fieldsLoaderSelector: '#oro_report_form_entity',
            events: [
                'items-manager:table:add',
                'items-manager:table:change',
                'items-manager:table:remove',
                'items-manager:table:reset'
            ]
        },

        /**
         * @param {String} id
         * @param {Array} options
         */
        initialize: function (id, options) {
            var self = this;
            var initializer = _.once(_.bind(this.initSelect2, this, id));

            this.options = _.extend({}, this.options, options);
            this.items = [];

            _.each(this.options.events, function (event) {
                mediator.on(event, function (collection) {
                    var entity = $(self.options.fieldsLoaderSelector).fieldsLoader('getEntityName');
                    var data = $(self.options.fieldsLoaderSelector).fieldsLoader('getFieldsData');
                    self.util = new Util(entity, data);
                    self.items = collection.toJSON();
                    initializer();
                });
            });
        },

        initSelect2: function (id) {
            var childSelector = _.template(this.options.childTemplate, {id: id});
            var self = this;
            $('#' + id).find(childSelector).each(function () {
                var exclude = $(this).data('type-filter');
                $(this).select2({
                    collapsibleResults: true,
                    placeholder: __('oro.entity.form.choose_entity_field'),
                    data: function () {
                        return self.data(exclude);
                    },
                    initSelection: function (element, callback) {
                        var value = element.val().split('(');
                        var node = _.last(self.util.pathToEntityChain(value[0]));
                        callback({
                            id: value.join('('),
                            text: node.field.label
                        });
                    }
                });
            });
        },

        data: function (exclude) {
            var data, util, optionsTemplate;
            util = this.util;
            data = {
                more: false,
                results: []
            };

            if (!util) {
                return data;
            }

            optionsTemplate = _.template(this.options.optionsTemplate);

            $.each(this.items, function () {
                var options = this.func;
                var chain = util.pathToEntityChain(this.name).slice(1);
                var field = chain[chain.length - 1].field;
                var items = data.results;
                if (!Util.filterFields([field], exclude).length) {
                    return;
                }
                $.each(chain, function () {
                    var item, id;
                    if (this.entity) {
                        item = _.findWhere(items, {path: this.path});
                        if (!item) {
                            item = {
                                text: this.field.label,
                                path: this.path,
                                children: []
                            };
                            items.push(item);
                        }
                        items = item.children;
                    } else {
                        if (options) {
                            id = optionsTemplate({
                                field: this.path,
                                group: options.name,
                                name: options.group_name,
                                type: options.group_type
                            });
                        } else {
                            id = this.path;
                        }
                        items.push({
                            text: this.field.label,
                            id: id
                        });
                    }
                });
            });

            return data;
        }
    }
});
