define([
    'jquery',
    'jquery-ui'
], function($) {
    'use strict';

    function setValue($elem, value) {
        $elem.inputWidget('val', value).trigger('change');
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
            collection: null,
            setter: function($el, name, value) {
                return value;
            },
            getter: function($el, name, value) {
                return value;
            },
            changed: false
        },

        /**
         * Is this component changed by user
         *
         * @returns {boolean}
         */
        hasChanges: function() {
            return this.changed;
        },

        _create: function() {
            // turn off global validation on submit form
            this.element.attr('data-validation-ignore', '');
            this.errors = $({});
            this.form = this.element.parents('form');
            this._on(this.form, {
                submit: '_hideErrors'
            });
            if (typeof this.options.namePattern === 'string') {
                this.options.namePattern = new RegExp(this.options.namePattern);
            }

            this.reset();
            this._on({
                change: this._onElementChange,
                click: this._onClick
            });

            this.options.collection.on('action:edit', $.proxy(this._onEditModel, this));
            this.options.collection.on('remove', $.proxy(this._onRemoveModel, this));
        },

        reset: function(model) {
            var elementsMap;
            var attrs;
            var self = this;
            this._hideErrors();
            this.validated = false;
            this.model = model;
            if (model) {
                elementsMap = this._elementsMap();
                attrs = model.toJSON();
                $.each(attrs, function(name, value) {
                    var $elem = elementsMap[name];
                    if ($elem) {
                        value = self.options.setter($elem, name, value);
                        setValue($elem, value);
                    }
                });
            } else {
                this._elements().each(function() {
                    setValue($(this), '');
                });
            }
            this._updateActions();
            this.changed = false;
        },

        _onSaveItem: function(e) {
            var attrs;
            e.preventDefault();
            if (!this._validate()) {
                return;
            }

            attrs = this._collectAttrs();
            if (this.model) {
                this.model.set(attrs);
            } else {
                this.options.collection.set(attrs, {add: true, remove: false});
            }

            this.reset();
        },

        _onEditModel: function(model) {
            this.reset(model);
        },

        _onRemoveModel: function(model) {
            if (this.model === model) {
                this.reset();
            }
        },

        _onCancel: function(e) {
            e.preventDefault();
            this.reset();
        },

        _validate: function(elem) {
            var validator = this._getValidator();
            var result = true;
            if (validator) {
                this.element.removeAttr('data-validation-ignore');
                if (elem) {
                    result = validator.element(elem);
                } else {
                    $.each(this._elements(), function() {
                        result = validator.element(this) && result;
                    });
                    this.validated = true;
                }
                this.errors = validator.toShow;
                this.element.attr('data-validation-ignore', '');
            }
            return result;
        },

        _hideErrors: function() {
            var validator = this._getValidator();
            if (validator) {
                this._elements().each(function() {
                    validator.settings.unhighlight(this);
                });
                this.errors.hide();
            }
        },

        _getValidator: function() {
            var validator;
            if (this.form.data('validator')) {
                validator = this.form.validate();
            }
            return validator;
        },

        _elements: function() {
            return this.element.find('input, select, textarea')
                .not(':submit, :reset, :image');
        },

        _onElementChange: function(e) {
            this.changed = true;
            if (this.validated) {
                this._validate(e.target);
            }
        },

        _onClick: function(e) {
            var $target = $(e.target);
            if ($target.is(this.options.addButton) || $target.is(this.options.saveButton)) {
                this._onSaveItem(e);
            } else if ($target.is(this.options.cancelButton)) {
                this._onCancel(e);
            }
        },

        _collectAttrs: function() {
            var arrts = {};
            var self = this;

            $.each(this._elementsMap(), function(name, $elem) {
                arrts[name] = self.options.getter($elem, name, $elem.val());
            });

            return arrts;
        },

        _elementsMap: function() {
            var mapped;
            var elementsMap = {};
            var $container = this.element;
            var pattern = this.options.namePattern;

            // collect elements using map
            $.each(this.options.mapping, function(attrName, elemName) {
                var $elem = $container.find('[name="' + elemName + '"]');
                if ($elem.length) {
                    elementsMap[attrName] = $elem;
                }
            });

            mapped = $.map(elementsMap, function($elem) {
                return $elem[0];
            });

            // collect elements using name pattern
            $.each(this._elements().not(mapped), function() {
                var name = this.name && (this.name.match(pattern) || [])[1];
                if (name && !elementsMap[name]) {
                    elementsMap[name] = $(this);
                }
            });
            return elementsMap;
        },

        _updateActions: function() {
            this.element.find(this.options.addButton)[this.model ? 'hide' : 'show']();
            this.element.find(this.options.saveButton)[this.model ? 'show' : 'hide']();
        }
    });

    return $;
});
