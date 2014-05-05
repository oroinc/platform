/*global define*/
define(['underscore', 'oroentity/js/field-choice', 'oroui/js/mediator', 'orotranslation/js/translator'],
    function (_, $, mediator, __) {
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
                optionsTemplate: '<%= field %>(<%= group %>,<%= name %>,<%= type %>)',
                fieldChoiceOptions: {
                    select2: {
                        placeholder: __('oro.entity.form.choose_entity_field')
                    },
                    util: {},
                    exclude: [],
                    fields: []
                },
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
                this.options = _.extend({}, this.options, options);

                _.each(this.options.events, function (event) {
                    mediator.on(event, function (collection) {
                        self.updateFields(collection);

                        self.updateFilters(id);
                    });
                });
            },

            /**
             * @param {String} id
             */
            updateFilters: function (id) {
                var self = this;
                var childSelector = _.template(self.options.childTemplate, {id: id});
                var items = $('#' + id).find(childSelector);

                _.each(items, function (item) {
                    self.options.fieldChoiceOptions.exclude = $(item).data('type-filter');
                    $(item).fieldChoice(self.options.fieldChoiceOptions);
                });
            },

            /**
             * @param {Array} collection
             */
            updateFields: function (collection) {
                var self = this;
                var fieldsList = $(self.options.fieldsLoaderSelector).fieldsLoader('getFieldsData');

                self.options.fieldChoiceOptions.fields = [];
                _.each(collection.models, function (model) {
                    var name = model.get('name');
                    var options = model.get('func');
                    self._addFieldByPath(fieldsList, self.options.fieldChoiceOptions.fields, name, options);
                });
            },

            /**
             * @param {Array} fields
             * @param {Array} root
             * @param {String} name
             * @param {Array} options
             */
            _addFieldByPath: function (fields, root, name, options) {
                var self = this;
                var chain = name.split('+');
                var fieldName = _.last(_.first(chain).split('::'));
                var lastFieldName = _.last(_.last(chain).split('::'));
                var hasOptions = fieldName == lastFieldName;

                var field = _.findWhere(fields, {name: fieldName});
                if (field) {
                    var rootField = _.findWhere(root, {name: fieldName});
                    if (!rootField) {
                        rootField = _.clone(field);
                        rootField.related_entity_fields = [];

                        if (options && hasOptions) {
                            var optionedName = _.template(
                                self.options.optionsTemplate,
                                {
                                    field: rootField.name,
                                    group: options.name,
                                    name: options.group_name,
                                    type: options.group_type
                                }
                            );

                            rootField.name = optionedName;
                        }

                        root.push(rootField);
                    }

                    if (chain.length > 1) {
                        var childName = _.rest(chain).join('+');
                        self._addFieldByPath(
                            field.related_entity_fields,
                            rootField.related_entity_fields,
                            childName,
                            options
                        );
                    }
                }
            }
        };
    });
