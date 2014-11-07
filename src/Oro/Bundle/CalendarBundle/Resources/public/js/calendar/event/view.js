/*jslint nomen:true*/
/*global define*/
define(['underscore', 'backbone', 'orotranslation/js/translator', 'routing', 'oro/dialog-widget', 'oroui/js/loading-mask',
    'orocalendar/js/form-validation', 'oroui/js/delete-confirmation'
], function (_, Backbone, __, routing, DialogWidget, LoadingMask, FormValidation, DeleteConfirmation) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  orocalendar/js/calendar/event/view
     * @class   orocalendar.calendar.event.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Object} */
        options: {
            widgetRoute: null,
            widgetOptions: null
        },

        /** @property {Object} */
        selectors: {
            loadingMaskContent: '.loading-content'
        },

        initialize: function (options) {
            this.options = _.defaults(_.pick(options || {}, _.keys(this.options)), this.options);
            this.template = _.template($(options.formTemplateSelector).html());

            this.listenTo(this.model, 'sync', this.onModelSave);
            this.listenTo(this.model, 'destroy', this.onModelDelete);
        },

        remove: function () {
            this.trigger('remove');
            this._hideMask();
            Backbone.View.prototype.remove.apply(this, arguments);
        },

        onModelSave: function () {
            this.trigger('addEvent', this.model);
            this.eventDialog.remove();
            this.remove();
        },

        onModelDelete: function () {
            this.eventDialog.remove();
            this.remove();
        },

        render: function () {
            var widgetOptions = this.options.widgetOptions || {},
                eventForm = this.options.widgetRoute ? $('<div></div>') : this.getEventForm(),
                onDelete = _.bind(function (e) {
                    var el = $(e.target),
                        confirm = new DeleteConfirmation({
                            content: el.data('message')
                        });
                    e.preventDefault();
                    confirm.on('ok', _.bind(this.deleteModel, this));
                    confirm.open();
                }, this);

            this.eventDialog = new DialogWidget(_.defaults(_.omit(widgetOptions, ['dialogOptions']), {
                el: eventForm,
                title: this.model.isNew() ? __('Add New Event') : __('Edit Event'),
                stateEnabled: false,
                incrementalPosition: false,
                loadingMaskEnabled: false,
                dialogOptions: _.defaults(widgetOptions.dialogOptions || {}, {
                    modal: true,
                    resizable: false,
                    width: 475,
                    autoResize: true,
                    close: _.bind(this.remove, this)
                }),
                submitHandler: _.bind(function () {
                    this.saveModel();
                }, this)
            }));
            this.eventDialog.render();

            // subscribe to 'delete event' event
            this.eventDialog.getAction('delete', 'adopted', function (deleteAction) {
                deleteAction.on('click', onDelete);
            });

            // init loading mask control
            this.loadingMask = new LoadingMask();
            this.eventDialog.$el.closest('.ui-dialog').append(this.loadingMask.render().$el);

            if (this.options.widgetRoute) {
                this.showLoadingMask();
                try {
                    $.ajax({
                        url: routing.generate(this.options.widgetRoute, {id: this.model.originalId}),
                        data: '_widgetContainer=dialog&_wid=' + this._getUniqueIdentifier(),
                        success: _.bind(function (data) {
                            this._hideMask();
                            this.eventDialog.$el.html(data);
                        }, this),
                        error: _.bind(function (jqXHR) {
                            this._hideMask();
                            this.eventDialog.$el.html(
                                '<div class="alert alert-error">' + __('oro.ui.widget_loading_filed') + '</div>'
                            );
                        }, this)
                    });
                } catch (err) {
                    this.showError(err);
                }
            } else {
                eventForm.find('[name]').uniform('update');
            }

            return this;
        },

        saveModel: function () {
            this.showSavingMask();
            try {
                this.model.save(this.getEventFormData(), {
                    wait: true,
                    error: _.bind(this._handleResponseError, this)
                });
            } catch (err) {
                this.showError(err);
            }
        },

        deleteModel: function () {
            this.showDeletingMask();
            try {
                this.model.destroy({
                    wait: true,
                    error: _.bind(this._handleResponseError, this)
                });
            } catch (err) {
                this.showError(err);
            }
        },

        showLoadingMask: function () {
            this._showMask(__('Loading...'));
        },

        showSavingMask: function () {
            this._showMask(__('Saving...'));
        },

        showDeletingMask: function () {
            this._showMask(__('Deleting...'));
        },

        _showMask: function (message) {
            if (this.loadingMask) {
                this.loadingMask.$el
                    .find(this.selectors.loadingMaskContent)
                    .text(message);
                this.loadingMask.show();
            }
        },

        _hideMask: function () {
            if (this.loadingMask) {
                this.loadingMask.hide();
            }
        },

        _handleResponseError: function (model, response) {
            this.showError(response.responseJSON || {});
        },

        showError: function (err) {
            this._hideMask();
            if (this.eventDialog) {
                FormValidation.handleErrors(this.eventDialog.$el.parent(), err);
            }
        },

        fillForm: function (form, modelData) {
            var self = this;
            form = $(form);

            self.buildForm(form, modelData);

            var inputs = form.find('[name]');
            var fieldNameRegex = /\[(\w+)\]/g;

            _.each(inputs, function (input) {
                input = $(input);
                var name = input.attr('name'),
                    matches = [],
                    match;

                while ((match = fieldNameRegex.exec(name)) !== null) {
                    matches.push(match[1]);
                }

                if (matches.length) {
                    var value = self.getValueByPath(modelData, matches);
                    if (input.is(':checkbox')) {
                        input.prop('checked', value);
                    } else {
                        input.val(value);
                    }
                    input.change();
                }
            });

            return form;
        },

        buildForm: function (form, modelData) {
            var self = this;
            form = $(form);
            _.each(modelData, function (value, key) {
                if (typeof value === 'object') {
                    var container = form.find('.' + key + '-collection');
                    if (container) {
                        var prototype = container.data('prototype');
                        if (prototype) {
                            _.each(value, function (collectionValue, collectionKey) {
                                container.append(prototype.replace(/__name__/g, collectionKey));
                            });
                        }
                    }

                    self.buildForm(form, value);
                }
            });
        },

        getEventForm: function () {
            var modelData = this.model.toJSON();
            return this.fillForm(this.template(modelData), modelData);
        },

        getEventFormData: function () {
            var fieldNameRegex = /\[(\w+)\]/g,
                data = {},
                formData = this.eventDialog.form.serializeArray();
            formData = formData.concat(this.eventDialog.form.find('input[type=checkbox]:not(:checked)')
                .map(function () {
                    return {"name": this.name, "value": false};
                }).get());
            _.each(formData, function (dataItem) {
                var matches = [], match;
                while ((match = fieldNameRegex.exec(dataItem.name)) !== null) {
                    matches.push(match[1]);
                }

                if (matches.length) {
                    this.setValueByPath(data, dataItem.value, matches);
                }
            }, this);

            return data;
        },

        setValueByPath: function (obj, value, path) {
            var parent = obj, i;

            for (i = 0; i < path.length - 1; i++) {
                if (parent[path[i]] === undefined) {
                    parent[path[i]] = {};
                }
                parent = parent[path[i]];
            }

            parent[path[path.length - 1]] = value;
        },

        getValueByPath: function (obj, path) {
            var current = obj, i;

            for (i = 0; i < path.length; i++) {
                if (current[path[i]] == undefined) {
                    return undefined;
                }
                current = current[path[i]];
            }

            return current;
        },

        _getUniqueIdentifier: function () {
            /*jslint bitwise:true */
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0,
                    v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
    });
});
