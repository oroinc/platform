define([
    'underscore',
    'jquery',
    'oroui/js/app/components/base/component'
], function(_, $, BaseComponent) {
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
        $dialog: null,
        editable: false,

        /**
         * @param options
         */
        initialize: function(options) {
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
            this.$existingEntity = this.$el.find(options.existingEntitySelector);
            this.$dialog = this.$el.closest('.ui-dialog');
            this.$dialog.css('top', 0);
            this.editable = options.editable == true;

            this.$existingEntity.on('change', _.bind(this._onEntityChange, this));
            this.$mode.on('change', _.bind(this._updateNewEntityVisibility, this));

            this._onEntityChange({val: $(options.existingEntityInputSelector).val()});
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
        }
    });
});
