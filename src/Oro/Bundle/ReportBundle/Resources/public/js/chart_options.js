/*global define*/
define(['underscore', 'oroentity/js/field-choice', 'oroui/js/mediator'],
    function (_, $, mediator) {
        'use strict';

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
                fieldChoiceOptions: {
                    select2: {},
                    util: {},
                    fieldsLoaderSelector: '#oro_report_form_entity',
                    exclude: {}
                },
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
                this.options = _.extend({}, this.options, options);

                _.each(this.options.events, function(event) {
                    mediator.on(event, function (collection) {
                        var allowedFields = self._getAllowedFields(collection);
                        var fieldsList = $(self.options.fieldChoiceOptions.fieldsLoaderSelector)
                            .fieldsLoader('getFieldsData');

                        self.updateItemsFields(id, fieldsList, allowedFields);
                    });
                });
            },

            /**
             *
             * @param {String} id
             * @param {Array} fieldsList
             * @param {Array} allowedFieldValues
             */
            updateItemsFields: function(id, fieldsList, allowedFieldValues) {
                var self = this;
                var selector = '#' + id;
                var childSelector = _.template(self.options.childTemplate, {id: id});

                var items = $(selector).find(childSelector);
                _.each(items, function (item) {
                    var itemFilters = $(item).data('filter');
                    var extraFilters = [];

                    _.each(fieldsList, function(field){
                        var fieldValue = _.pick(field, 'name');
                        if (!_.findWhere(allowedFieldValues, fieldValue)) {
                            extraFilters.push(fieldValue);
                        }
                    });

                    self.options.fieldChoiceOptions.exclude = _.union(itemFilters, extraFilters);
                    $(item).fieldChoice(self.options.fieldChoiceOptions);
                });
            },

            /**
             * @param collection
             * @returns {Array}
             * @private
             */
            _getAllowedFields: function(collection) {
                var allowedFieldValues =  [];
                _.each(collection.models, function(model){
                    allowedFieldValues.push({name: model.get('name')})
                });

                return allowedFieldValues;
            }
        };
    });
