/*global define*/
/*jslint nomen: true*/
define(['jquery', 'jquery-ui'],   function ($) {
    'use strict';

    function setValue($elem, value) {
        if ($elem.data('select2')) {
            $elem.select2('val', value);
        } else {
            $elem.val(value).trigger('change');
        }
    }

    $.widget('oroui.itemsManagerEditor', {
        options: {
            namePattern: /^([\w\W]*)$/,
            mapping: {
                /* attrName: elemName */
            },
            addButton: '.add-button',
            saveButton: '.save-button',
            cancelButton: '.cancel-button',
            collection: null
        },

        _create: function () {
            // turn off global validation on submit form
            this.element.attr('data-validation-ignore', '');
            this.errors = $({});
            this.form = this.element.parents('form');
            this.form.on('submit', $.proxy(this._hideErrors, this));

            this.reset();
            this._on({
                change: this._onElementChange,
                click: this._onClick
            });

            this.options.collection.on('action:edit', $.proxy(this._onEditModel, this));
            this.options.collection.on('remove', $.proxy(this._onRemoveModel, this));
        },

        reset: function (model) {
            var self = this;
            var elementsMap, attrs;
            this._hideErrors();
            this.validated = false;
            this.model = model;
            if (model) {
                elementsMap = this._elementsMap();
                attrs = model.toJSON();
                $.each(attrs, function (name, value) {
                    if (elementsMap[name]) {
                        self.options.setPropertyEditor(name, value, elementsMap[name], setValue);
                    }
                });
            } else {
                this._elements().each(function () {
                    setValue($(this), '');
                });
            }
            this._updateActions();
        },

        _onSaveItem: function (e) {
            var attrs, model;
            e.preventDefault();
            if (!this._validate()) {
                return;
            }

            attrs = this._collectAttrs();
            if (this.model) {
                this.model.set(attrs);
            } else {
                model = new (this.options.collection.model)(attrs);
                this.options.collection.add(model);
            }

            this.reset();
        },

        _onEditModel: function (model) {
            this.reset(model);
        },

        _onRemoveModel: function (model) {
            if (this.model === model) {
                this.reset();
            }
        },

        _onCancel: function (e) {
            e.preventDefault();
            this.reset();
        },

        _validate: function (elem) {
            var validator = this._getValidator(),
                result = true;
            if (validator) {
                this.element.removeAttr('data-validation-ignore');
                if (elem) {
                    result = validator.element(elem);
                } else {
                    $.each(this._elements(), function () {
                        result = validator.element(this) && result;
                    });
                    this.validated = true;
                }
                this.errors = validator.toShow;
                this.element.attr('data-validation-ignore', '');
            }
            return result;
        },

        _hideErrors: function () {
            var validator = this._getValidator();
            if (validator) {
                this._elements().each(function () {
                    validator.settings.unhighlight(this);
                });
                this.errors.hide();
            }
        },

        _getValidator: function () {
            var validator;
            if (this.form.validate) {
                validator = this.form.validate();
            }
            return validator;
        },

        _elements: function () {
            return this.element.find("input, select, textarea")
                .not(":submit, :reset, :image, [disabled]");
        },

        _onElementChange: function (e) {
            if (this.validated) {
                this._validate(e.target);
            }
        },

        _onClick: function (e) {
            var $target = $(e.target);
            if ($target.is(this.options.addButton) || $target.is(this.options.saveButton)) {
                this._onSaveItem(e);
            } else if ($target.is(this.options.cancelButton)) {
                this._onCancel(e);
            }
        },

        _collectAttrs: function () {
            var self = this;
            var arrts = {};

            var t = this._elementsMap();
            debugger;
            $.each(t, function (name, $elem) {
                arrts[name] = self.options.readPropertyEditor(name, $elem);
            });

            return arrts;
        },

        _elementsMap: function () {
            var elementsMap = {},
                $container = this.element,
                pattern = this.options.namePattern;

            // collect elements using map
            $.each(this.options.mapping, function (attrName, elemName) {
                var $elem = $container.find('[name="' + elemName + '"]');
                if ($elem.length) {
                    elementsMap[attrName] = $elem;
                }
            });

            // collect elements using name pattern
            $.each(this._elements(), function () {
                var name = this.name && (this.name.match(pattern) || [])[1];
                if (name && !elementsMap[name]) {
                    elementsMap[name] = $(this);
                }
            });
            return elementsMap;
        },

        _updateActions: function () {
            this.element.find(this.options.addButton)[this.model ? 'hide' : 'show']();
            this.element.find(this.options.saveButton)[this.model ? 'show' : 'hide']();
        }
    });

    return $;
});
