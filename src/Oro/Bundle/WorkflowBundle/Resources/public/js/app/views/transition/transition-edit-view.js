define(function(require) {
    'use strict';

    var TransitionEditView;
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');
    var DialogWidget = require('oro/dialog-widget');
    var helper = require('oroworkflow/js/tools/workflow-helper');
    var AttributeFormOptionEditView = require('../attribute/attribute-form-option-edit-view');
    var AttributeFormOptionListView = require('../attribute/attribute-form-option-list-view');
    require('jquery.validate');

    TransitionEditView = BaseView.extend({
        attributes: {
            'class': 'widget-content'
        },

        events: {
            'change [name=label]': 'updateExampleView',
            'change [name=button_label]': 'updateExampleView',
            'change [name=button_title]': 'updateExampleView',
            'change [name$="[transition_prototype_icon]"]': 'updateExampleView',
            'change [name=button_color]': 'updateExampleView',
            'change [name=display_type]': 'updateDestinationPageView'
        },

        options: {
            template: null,
            workflow: null,
            step_from: null,
            button_example_template: '<button type="button" class="btn <%- button_color %>" ' +
                'title="<%- button_title %>">' +
                '<% if (transition_prototype_icon) { %><i class="<%- transition_prototype_icon %>"/> <% } %>' +
                '<%- button_label %></button>',
            allowed_button_styles: [
                {
                    label: __('Gray button'),
                    name: ''
                },
                {
                    label: __('Navy blue button'),
                    name: 'btn-primary'
                },
                {
                    label: __('Blue button'),
                    name: 'btn-info'
                },
                {
                    label: __('Green button'),
                    name: 'btn-success'
                },
                {
                    label: __('Yellow button'),
                    name: 'btn-warning'
                },
                {
                    label: __('Red button'),
                    name: 'btn-danger'
                },
                {
                    label: __('Black button'),
                    name: 'btn-inverse'
                },
                {
                    label: __('Link'),
                    name: 'btn-link'
                }
            ]
        },

        requiredOptions: ['workflow', 'entityFieldsProvider'],

        listen: {
            'destroy model': 'remove'
        },

        /**
         * @inheritDoc
         */
        constructor: function TransitionEditView() {
            TransitionEditView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            options = options || {};
            var requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }
            this.options = _.defaults(options, this.options);

            var template = this.options.template || $('#transition-form-template').html();
            this.template = _.template(template);
            this.button_example_template = _.template(this.options.button_example_template);
            this.widget = null;
        },

        initDestinationPageView: function(form) {
            var availableDestinations = this.options.workflow.getAvailableDestinations();
            var entityRoutes = _.keys(this.options.entityFieldsProvider.getEntityRoutes());
            entityRoutes.push('');

            var enabledRedirects = _.intersection(availableDestinations, entityRoutes);

            var currentRedirects = $(form).find('[name=destination_page] option');
            _.each(currentRedirects, function(item) {
                $(item).toggle(_.indexOf(enabledRedirects, $(item).val()) >= 0);
            });
        },

        updateDestinationPageView: function() {
            var $displayType = $(this.widget.form).find('[name=display_type]');
            var $pageRedirectRow = $(this.widget.form).find('.destination-page-controls');

            $pageRedirectRow.toggle($displayType.val() === 'page');
        },

        updateExampleView: function() {
            var formData = helper.getFormData(this.widget.form);
            formData.transition_prototype_icon = formData.transition_prototype_icon || this._getFrontendOption('icon');
            formData.button_label = formData.button_label || formData.label;
            formData.button_title = formData.button_title || formData.button_label;

            if (formData.transition_prototype_icon || formData.button_label || formData.button_title) {
                this.$exampleBtnContainer.html(
                    this.button_example_template(formData)
                );
            }
        },

        onTransitionAdd: function() {
            var formData = helper.getFormData(this.widget.form);
            var modelUpdateData = _.pick(formData,
                'label', 'button_label', 'button_title', 'step_to', 'display_type', 'destination_page', 'message');
            if (!this.model.get('name')) {
                modelUpdateData.name = helper.getNameByString(formData.label, 'transition_');
            }
            var frontendOptions = this.model.get('frontend_options');
            modelUpdateData.frontend_options = _.extend({}, frontendOptions, {
                'icon': formData.transition_prototype_icon,
                'class': formData.button_color
            });
            modelUpdateData._is_clone = false;
            var attributeFields = {};
            _.each(this.subview('attributes-list-view').getCollection(), function(item) {
                this.options.workflow.ensureAttributeByPropertyPath(item.property_path);
                var attribute = this.options.workflow.getAttributeByPropertyPath(item.property_path);
                var attributeName = attribute.get('name');
                var formOptionsData = _.pick(item, 'options');
                if (item.required) {
                    formOptionsData.options = _.extend({}, formOptionsData.options, {required: true});
                } else if (formOptionsData.options) {
                    delete formOptionsData.options.required;
                    delete formOptionsData.options.constraints;
                }

                if (item.label && item.isLabelUpdated) {
                    formOptionsData.label = item.label;
                    if (formOptionsData.options) {
                        delete formOptionsData.options.label;
                    }
                }

                attributeFields[attributeName] = formOptionsData;
            }, this);

            modelUpdateData.form_options = {
                attribute_fields: attributeFields
            };

            this.model.set(modelUpdateData);

            var stepFrom = formData.step_from ? formData.step_from : this.options.step_from;
            this.trigger('transitionAdd', this.model, stepFrom);
            this.widget.remove();
        },

        _getFrontendOption: function(key) {
            var result = '';
            var formOptions = this.model.get('frontend_options');
            if (formOptions && formOptions.hasOwnProperty(key)) {
                result = formOptions[key];
            }
            return result;
        },

        renderAddAttributeForm: function(el) {
            var attributesFormView = new AttributeFormOptionEditView({
                autoRender: true,
                el: el.find('.transition-attributes-form-container'),
                workflow: this.options.workflow,
                entityFieldsProvider: this.options.entityFieldsProvider,
                entity: this.options.entity,
                filterPreset: 'workflow'
            });

            this.listenTo(attributesFormView, 'formOptionAdd', this.addFormOption);
            this.subview('attributes-form-view', attributesFormView);
            return attributesFormView;
        },

        addFormOption: function(data) {
            var attributeName;
            var attribute = this.options.workflow.getAttributeByPropertyPath(data.property_path);
            if (attribute) {
                attributeName = attribute.get('name');
            } else {
                attributeName = this.options.workflow.generateAttributeName(data.property_path);
            }
            if (data.label) {
                data.isLabelUpdated = true;
            }
            data.attribute_name = attributeName;
            data.is_entity_attribute = true;

            this.subview('attributes-list-view').addItem(data);
        },

        renderAttributesList: function(el) {
            var attributesList = new AttributeFormOptionListView({
                autoRender: true,
                el: el.find('.transition-attributes-list-container'),
                items: this.getFormOptions(),
                fieldsChoiceView: this.subview('attributes-form-view').getFieldChoiceView(),
                workflow: this.options.workflow,
                entityFieldsProvider: this.options.entityFieldsProvider
            });

            this.listenTo(attributesList, 'editFormOption', this.editFormOption);
            this.subview('attributes-list-view', attributesList);
        },

        editFormOption: function(data) {
            this.subview('attributes-form-view').editRow(data);
        },

        getFormOptions: function() {
            var results = [];
            var entityPropertyPathPrefix = this.options.workflow.get('entity_attribute') + '.';
            _.each(this.model.get('form_options').attribute_fields, function(formOption, attributeName) {
                formOption = formOption || {};
                var attribute = this.options.workflow.getAttributeByName(attributeName);
                var propertyPath = attribute.get('property_path');
                var isEntityAttribute = Boolean(propertyPath);
                if (isEntityAttribute && propertyPath.indexOf(entityPropertyPathPrefix) === 0) {
                    propertyPath = propertyPath.substr(entityPropertyPathPrefix.length);
                }
                var options = formOption.hasOwnProperty('options') ? formOption.options : {};
                var isRequired = options.hasOwnProperty('required') ? options.required : false;

                var label = formOption.hasOwnProperty('label') ? formOption.label : null;
                if (!label && options.hasOwnProperty('label')) {
                    label = options.label;
                }
                var result = {
                    itemId: _.uniqueId(),
                    is_entity_attribute: isEntityAttribute,
                    attribute_name: attributeName,
                    property_path: propertyPath || attributeName,
                    required: isRequired,
                    label: label,
                    isLabelUpdated: false,
                    translateLinks: attribute.attributes.translateLinks[this.model.get('name')]
                };
                if ('options' in formOption) {
                    result.options = _.clone(formOption.options);
                }
                results.push(result);
            }, this);

            return results;
        },

        onCancel: function() {
            if (this.model.get('_is_clone')) {
                this.model.destroy();
            } else {
                this.remove();
            }
        },

        remove: function() {
            this.removeSubview('attributes-form-view');
            this.removeSubview('attributes-list-view');
            TransitionEditView.__super__.remove.call(this);
        },

        renderWidget: function() {
            if (!this.widget) {
                var title = this.model.get('name') ? __('Edit transition') : __('Add new transition');
                if (this.model.get('_is_clone')) {
                    title = __('Clone transition');
                }
                this.widget = new DialogWidget({
                    title: title,
                    el: this.$el,
                    stateEnabled: false,
                    incrementalPosition: false,
                    dialogOptions: {
                        close: _.bind(this.onCancel, this),
                        width: 800,
                        modal: true
                    }
                });
                this.widget.render();
            } else {
                this.widget._adoptWidgetActions();
            }

            // Disable widget submit handler and set our own instead
            this.widget.form.off('submit');
            this.widget.form.validate({
                submitHandler: _.bind(this.onTransitionAdd, this),
                ignore: '[type="hidden"]',
                highlight: function(element) {
                    var tabContent = $(element).closest('.tab-pane');
                    if (tabContent.is(':hidden')) {
                        tabContent
                            .closest('.oro-tabs')
                            .find('[href="#' + tabContent.prop('id') + '"]')
                            .click();
                    }
                }
            });
        },

        render: function() {
            this._deferredRender();
            var data = this.model.toJSON();
            var steps = this.options.workflow.get('steps').models;
            data.stepFrom = this.options.step_from;
            if (!data.step_to) {
                data.step_to = this.options.step_to ? this.options.step_to.get('name') : undefined;
            }
            data.allowedButtonStyles = _.sortBy(this.options.allowed_button_styles, 'label');
            data.buttonIcon = this._getFrontendOption('icon');
            data.buttonStyle = this._getFrontendOption('class');
            data.allowedStepsFrom = steps;
            data.allowedStepsTo = steps.slice(1);

            var form = $(this.template(data));

            this.initDestinationPageView(form);
            var attributesFormView = this.renderAddAttributeForm(form);
            $.when(attributesFormView.getDeferredRenderPromise()).then(function() {
                this.renderAttributesList(form);
                this.$el.append(form);

                this.renderWidget();

                this.$exampleContainer = this.$('.transition-example-container');
                this.$exampleBtnContainer = this.$exampleContainer.find('.transition-btn-example');
                this.updateExampleView();
                this.updateDestinationPageView();
                this._resolveDeferredRender();
            }.bind(this));

            return this;
        }
    });

    return TransitionEditView;
});
