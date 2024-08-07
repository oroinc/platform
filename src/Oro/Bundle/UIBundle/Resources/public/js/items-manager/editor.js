define(function(require) {
    'use strict';

    const $ = require('jquery');
    require('jquery-ui/widget');

    function setValue($elem, value) {
        $elem.inputWidget('val', value);
        $elem.trigger('change');
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

            this._onEditModel = this._onEditModel.bind(this);
            this._onRemoveModel = this._onRemoveModel.bind(this);

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

            this.options.collection.on('action:edit', this._onEditModel);
            this.options.collection.on('remove', this._onRemoveModel);
        },

        reset: function(model) {
            let elementsMap;
            let attrs;
            const self = this;
            this._hideErrors();
            this.validated = false;
            this.model = model;
            if (model) {
                elementsMap = this._elementsMap();
                attrs = model.toJSON();
                $.each(attrs, function(name, value) {
                    const $elem = elementsMap[name];
                    if ($elem) {
                        value = self.options.setter($elem, name, value, attrs);
                        setValue($elem, value);
                    }
                });
            } else {
                this._elements().each(function() {
                    setValue($(this), '');
                });
                this.element.trigger('after-reset');
            }
            this._updateActions();
            this.changed = false;
        },

        _onSaveItem: function(e) {
            e.preventDefault();

            this.element.trigger('before-save');

            if (!this._validate()) {
                return;
            }

            const attrs = this._collectAttrs();
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
            const validator = this._getValidator();
            let result = true;
            if (validator) {
                this.element.attr('data-validation-ignore', null);
                if (elem) {
                    result = validator.element(elem);
                } else {
                    $.each(this._elements(), function() {
                        result = validator.element(this) && result;
                    });
                    this.validated = true;
                }
                this.errors = validator.errors();
                this.element.attr('data-validation-ignore', '');
            }
            return result;
        },

        _hideErrors: function() {
            const validator = this._getValidator();

            if (validator) {
                validator.resetElements(this._elements());
                this.errors.hide();
            }
        },

        _getValidator: function() {
            let validator;
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
            const $target = $(e.target);
            if ($target.is(this.options.addButton) || $target.is(this.options.saveButton)) {
                this._onSaveItem(e);
            } else if ($target.is(this.options.cancelButton)) {
                this._onCancel(e);
            }
        },

        _collectAttrs: function() {
            const arrts = {};
            const self = this;

            $.each(this._elementsMap(), function(name, $elem) {
                arrts[name] = self.options.getter($elem, name, $elem.val());
            });

            return arrts;
        },

        _elementsMap: function() {
            const elementsMap = {};
            const $container = this.element;
            const pattern = this.options.namePattern;

            // collect elements using map
            $.each(this.options.mapping, function(attrName, elemName) {
                const $elem = $container.find('[name="' + elemName + '"]');
                if ($elem.length) {
                    elementsMap[attrName] = $elem;
                }
            });

            const mapped = $.map(elementsMap, function($elem) {
                return $elem[0];
            });

            // collect elements using name pattern
            $.each(this._elements().not(mapped), function() {
                const name = this.name && (this.name.match(pattern) || [])[1];
                if (name && !elementsMap[name]) {
                    elementsMap[name] = $(this);
                }
            });
            return elementsMap;
        },

        _updateActions: function() {
            this.element.find(this.options.addButton)[this.model ? 'hide' : 'show']();
            this.element.find(this.options.saveButton)[this.model ? 'show' : 'hide']();
        },

        _destroy: function() {
            this.options.collection.off('action:edit', this._onEditModel);
            this.options.collection.off('remove', this._onRemoveModel);

            this._off(this.form);
            this._off(this.element);

            this._super();
        }
    });

    return $;
});
