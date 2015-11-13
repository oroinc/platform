define([
    'underscore',
    'jquery',
    'oroui/js/app/components/base/component',
    'routing',
    'oroui/js/mediator'
], function(_, $, BaseComponent, routing, mediator) {
    'use strict';

    return BaseComponent.extend({

        MODE_CREATE: 'create',
        MODE_VIEW: 'view',
        MODE_EDIT: 'edit',

        requiredOptions: [
            'modeSelector',
            'newEntitySelector',
            'existingEntitySelector',
            'existingEntityInputSelector',
            '_sourceElement'
        ],

        $el: null,
        $mode: null,
        $newEntity: null,
        $existingEntity: null,
        $existingEntityInput: null,
        $dialog: null,
        editable: false,

        /**
         * @param options
         */
        initialize: function(options) {
            this.editable = (options.editable === true);
            if (this.editable) {
                this.requiredOptions.push('editRoute');
            }
            var missingProperties = _.filter(this.requiredOptions, _.negate(_.bind(options.hasOwnProperty, options)));
            if (missingProperties.length) {
                throw new Error(
                    'Following properties are required but weren\'t passed: ' +
                    missingProperties.join(', ') +
                    '.'
                );
            }

            this.$el = options._sourceElement;
            this.$mode = this.$el.find(options.modeSelector);
            this.$newEntity = this.$el.find(options.newEntitySelector);
            this.newEntityFormName = options.newEntityFormName;
            this.$existingEntity = this.$el.find(options.existingEntitySelector);
            this.$existingEntityInput = $(options.existingEntityInputSelector);
            this.$dialog = this.$el.closest('.ui-dialog');
            this.$dialog.css('top', 0);
            this.editRoute = options.editRoute;

            this.$existingEntity.on('change', _.bind(this._onEntityChange, this));
            this.$existingEntity.on('change', _.bind(this._retrieveEntityData, this));
            this.$existingEntity.on('change', _.bind(this._cleanEntityData, this));
            this.$mode.on('change', _.bind(this._updateNewEntityVisibility, this));

            this._onEntityChange({val: this.$existingEntityInput.val()});
        },

        /**
         * Processes change in selected entity field.
         *
         * @param e
         * @private
         */
        _onEntityChange: function(e) {
            var mode = this.MODE_CREATE;
            if (e.val) {
                mode = this.editable ? this.MODE_EDIT : this.MODE_VIEW;
            }
            this._setMode(mode);
        },

        /**
         * Updates entity form visibility according to mode.
         *
         * @private
         */
        _updateNewEntityVisibility: function() {
            if (this._isInMode(this.MODE_CREATE) || this._isInMode(this.MODE_EDIT)) {
                this.$newEntity.show();
            } else {
                this.$newEntity.hide();
            }
        },

        /**
         * @param mode
         * @private
         */
        _setMode: function(mode) {
            if (this.$mode.val() === mode) {
                return;
            }

            this.$mode.val(mode).change();
        },

        /**
         * Checks if is in provided mode.
         *
         * @param mode
         * @returns Boolean
         * @private
         */
        _isInMode: function(mode) {
            return this.$mode.val() === mode;
        },

        /**
         * Retrieves entity data using set route. Provided id is used as query parameter for request.
         *
         * @param e
         * @private
         */
        _retrieveEntityData: function(e) {
            if (!this._isInMode(this.MODE_EDIT) || !e.val) {
                return;
            }

            var route = routing.generate(this.editRoute, {id: e.val});

            this._setLoading(true);
            $.get(route)
                .done(_.bind(this._setNewEntityForm, this))
                .fail(_.bind(this._handleDataRequestError, this))
                .always(_.bind(function() {
                    this._setLoading(false);
                }, this));
        },

        /**
         * Cleans entity data when selecting default choice.
         *
         * @param e
         * @private
         */
        _cleanEntityData: function(e) {
            if (!this._isInMode(this.MODE_CREATE) || e.val || !this.editable) {
                return;
            }

            var self = this;
            this.$newEntity.find('input[type=text], input[data-default-value], textarea').each(function() {
                var $el = $(this);
                var newVal = self._getCleanValue($el);
                $el.val(newVal);
                if ($el.is('textarea')) {
                    $el.text(newVal);
                }
            });
        },

        /**
         * @param {jQuery} $el
         * @returns {String}
         */
        _getCleanValue: function($el) {
            if (typeof $el.data('default-value') !== 'undefined') {
                return $el.data('default-value');
            }

            if ($el.is('textarea')) {
                return '';
            }

            if ($el.is('input[type=hidden]')) {
                return $el.val();
            }

            return null;
        },

        /**
         * @param data HTML of edit form.
         * @private
         */
        _setNewEntityForm: function(data) {
            var $data = $(data);
            /*
             * For each input element in added form, create new name to match naming scheme.
             */
            $data.find(':input').each(_.bind(function(index, element) {
                var $element = $(element);
                var inputName = $element.attr('name');
                inputName = inputName.substr(inputName.indexOf('['));

                var $modifiedField = this.$newEntity.find('[name$="' + inputName + '"]');

                if ($element.is(':checkbox') || $element.is(':radio')) {
                    $modifiedField = this.$newEntity.find(
                        '[name$="' + inputName + '"][value="' + $element.val() + '"]'
                    );
                    $modifiedField.prop('checked', $element.is(':checked')).change();
                } else {
                    $modifiedField.val($element.val()).change();
                }
            }, this));
        },

        /**
         * @param jqXHR
         * @param textStatus
         * @param error
         * @private
         */
        _handleDataRequestError: function(jqXHR, textStatus, error) {
            throw new Error(textStatus); // Throw error
        },

        /**
         * @param enabled
         * @private
         */
        _setLoading: function(enabled) {
            if (enabled) {
                mediator.execute('showLoading');
            } else {
                mediator.execute('hideLoading');
            }
        }
    });
});
