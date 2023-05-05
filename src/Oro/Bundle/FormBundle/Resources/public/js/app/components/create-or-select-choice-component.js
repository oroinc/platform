define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const routing = require('routing');
    const tinyMCE = require('tinymce/tinymce');

    const CreateOrSelectChoiceComponent = BaseComponent.extend({

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
        disabledEditForm: false,

        /**
         * @inheritdoc
         */
        constructor: function CreateOrSelectChoiceComponent(options) {
            CreateOrSelectChoiceComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param options
         */
        initialize: function(options) {
            this.editable = (options.editable === true);
            this.disabledEditForm = (options.disabled_edit_form === true);
            if (this.editable) {
                this.requiredOptions.push('editRoute');
            }
            const missingProperties = _.filter(this.requiredOptions, _.negate(options.hasOwnProperty.bind(options)));
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

            this.$existingEntity.on('change', this._onEntityChange.bind(this));
            this.$existingEntity.on('change', this._retrieveEntityData.bind(this));
            this.$existingEntity.on('change', this._cleanEntityData.bind(this));
            this.$mode.on('change', this._updateNewEntityVisibility.bind(this));

            this._onEntityChange({val: this.$existingEntityInput.val()});
        },

        /**
         * Processes change in selected entity field.
         *
         * @param e
         * @private
         */
        _onEntityChange: function(e) {
            let mode = this.MODE_CREATE;
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

            const route = routing.generate(this.editRoute, {id: e.val});

            this._setLoading(true);
            $.get(route)
                .done(this._setNewEntityForm.bind(this))
                .fail(this._handleDataRequestError.bind(this))
                .always(() => {
                    this._setLoading(false);
                });
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

            const self = this;
            this.$newEntity.find('input[type=text], input[data-default-value], textarea').each(function() {
                const $el = $(this);
                const newVal = self._getCleanValue($el);
                $el.val(newVal);
                if ($el.is('textarea')) {
                    $el.text(newVal).change();
                } else {
                    $el.change();
                }
            });

            if (this.disabledEditForm) {
                this.$newEntity.find(':input').each(function() {
                    const $input = $(this);
                    const editor = tinyMCE.get($input.attr('id'));

                    if (editor && !$input.data('saved-disabled')) {
                        editor.mode.set('design');
                        $(editor.editorContainer).removeClass('disabled');
                        $(editor.editorContainer).children('.disabled-overlay').remove();
                    }

                    $input.prop('disabled', $input.data('saved-disabled'));
                });
            }
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
            const $data = $(data);
            /*
             * For each input element in added form, create new name to match naming scheme.
             */
            $data.find(':input').each((index, element) => {
                const $element = $(element);
                let inputName = $element.attr('name');
                inputName = inputName.substr(inputName.indexOf('['));

                let $modifiedField = this.$newEntity.find('[name$="' + inputName + '"]');

                if ($element.is(':checkbox') || $element.is(':radio')) {
                    $modifiedField = this.$newEntity.find(
                        '[name$="' + inputName + '"][value="' + $element.val() + '"]'
                    );
                    $modifiedField.prop('checked', $element.is(':checked')).change();
                } else {
                    const editor = tinyMCE.get($modifiedField.attr('id'));
                    if (editor) {
                        editor.setContent($element.val());
                    } else {
                        $modifiedField.val($element.val()).change();
                    }
                }
            });

            if (this.disabledEditForm) {
                this.$newEntity.find(':input').each(function() {
                    const $input = $(this);
                    const editor = tinyMCE.get($input.attr('id'));

                    $input.data('saved-disabled', $input.prop('disabled'));
                    $input.prop('disabled', true);

                    if (editor && !$input.data('saved-disabled')) {
                        editor.mode.set('readonly');
                        $(editor.editorContainer).addClass('disabled');
                        $(editor.editorContainer).children('.disabled-overlay').remove();
                        $(editor.editorContainer).append('<div class="disabled-overlay"></div>');
                    }
                });
            }
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
                this._showLoadingMask();
            } else {
                this._hideLoadingMask();
            }
        },

        /**
         * @private
         */
        _showLoadingMask: function() {
            this._ensureLoadingMaskLoaded();

            if (!this.loadingMask.isShown()) {
                this.loadingMask.show();
            }
        },

        /**
         * @private
         */
        _hideLoadingMask: function() {
            this._ensureLoadingMaskLoaded();

            if (this.loadingMask.isShown()) {
                this.loadingMask.hide();
            }
        },

        /**
         * @private
         */
        _ensureLoadingMaskLoaded: function() {
            if (!this.loadingMask) {
                this.loadingMask = new LoadingMaskView({container: this.$newEntity});
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.loadingMask) {
                this.loadingMask.dispose();
                delete this.loadingMask;
            }

            this.$existingEntity.off('change', this._onEntityChange.bind(this));
            this.$existingEntity.off('change', this._retrieveEntityData.bind(this));
            this.$existingEntity.off('change', this._cleanEntityData.bind(this));
            this.$mode.off('change', this._updateNewEntityVisibility.bind(this));

            CreateOrSelectChoiceComponent.__super__.dispose.call(this);
        }
    });

    return CreateOrSelectChoiceComponent;
});
