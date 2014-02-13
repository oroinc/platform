/*global define*/
/*jslint nomen: true*/
define(['jquery', 'jquery-ui' ],   function ($) {
    'use strict';

    function getLabel($elem) {
        var label;
        if ($elem.data('select2')) {
            label = $elem.data('select2').selection.html();
        } else if ($elem.is('select')) {
            label = $elem.find('option:selected').map(function () {
                return $(this).text();
            }).get().join(', ');
        } else {
            label = $elem.val();
        }
        return label;
    }

    function setValue($elem, value) {
        if ($elem.data('select2')) {
            $elem.select2('val', value);
        } else {
            $elem.val(value);
        }
    }

    $.widget('oroui.editForm', {

        options: {
            namePattern: /^([\w\W]*)$/
        },

        _create: function () {
            this.reset();
            this.element
                .on('click', '.add-button, .save-button', $.proxy(this._onSaveItem, this));
            this.element
                .on('click', '.cancel-button', $.proxy(this._onCancel, this));
            this._on({
                change: this._onElementChange
            });
        },

        reset: function () {
            this.validated = false;
            this.itemId = null;
            this._elements().each(function () {
                setValue($(this), '');
            });
        },

        setItem: function (item) {
            var elementsMap;

            this.reset();
            if (item.id) {
                this.itemId = item.id;
            }

            elementsMap = this._elementsMap();

            $.each(item, function (name, value) {
                if (elementsMap[name]) {
                    setValue(elementsMap[name], value.value);
                }
            });
        },

        _onSaveItem: function (e) {
            var item;
            e.preventDefault();
            if (!this._validate()) {
                return;
            }

            item = this._getItem();
            this._trigger('save', e, item);

            this.reset();
        },

        _onCancel: function (e) {
            e.preventDefault();
        },

        _validate: function (elem) {
            var validator, result = true,
                form = this.element.parents('form');
            if (form.validate) {
                validator = form.validate();
                if (elem) {
                    result = validator.element(elem);
                } else {
                    $.each(this._elements(), function () {
                        result = result && validator.element(this);
                    });
                    this.validated = true;
                }
            }
            return result;
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

        _getItem: function () {
            var item = {};

            if (this.itemId) {
                item.id = this.itemId;
            }

            $.each(this._elementsMap(), function (name, $elem) {
                item[name] = {
                    value: $elem.val(),
                    label: getLabel($elem)
                };
            });

            return item;
        },

        _elementsMap: function () {
            var elementsMap = {},
                pattern = this.options.namePattern;

            $.each(this._elements(), function () {
                var name = this.name && (this.name.match(pattern) || [])[1];
                if (name) {
                    elementsMap[name] = $(this);
                }
            });

            return elementsMap;
        }
    });

    return $;
});
