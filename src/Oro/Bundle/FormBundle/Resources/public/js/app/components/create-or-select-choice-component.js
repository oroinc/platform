define([
    'underscore',
    'oroui/js/app/components/base/component',
    'tinymce/jquery.tinymce.min'
], function (_, BaseComponent) {
    'use strict';

    var CreateOrSelectChoiceComponent =  BaseComponent.extend({

        MODE_CREATE: 'create',
        MODE_VIEW: 'view',

        requiredOptions: [
            'modeSelector',
            'newEntitySelector',
            'existingEntitySelector',
            'existingEntityInputSelector',
            '_sourceElement',
        ],

        $el: null,
        $mode: null,
        $newEntity: null,
        $existingEntity: null,
        $dialog: null,

        updateDialogPositionCb: null,

        initialize: function(options) {
            var missingProperties = _.filter(this.requiredOptions, _.negate(_.bind(options.hasOwnProperty, options)));
            if (missingProperties.length) {
                throw new Error('Following properties are required but weren\'t passed: ' + missingProperties.join(', ') + '.');
            }

            this.updateDialogPositionCb = _.bind(this._updateDialogPosition, this);

            this.$el = options._sourceElement;
            this.$mode = this.$el.find(options.modeSelector);
            this.$newEntity = this.$el.find(options.newEntitySelector);
            this.$existingEntity = this.$el.find(options.existingEntitySelector);
            this.$dialog = this.$el.closest('.ui-dialog');

            this.$existingEntity.on('change', _.bind(this._onEntityChange, this));
            this.$mode.on('change', _.bind(this._updateNewEntityVisibility, this));
            this.$mode.on('change', this.updateDialogPositionCb);

            this._onEntityChange({val: $(options.existingEntityInputSelector).val()});

            var self = this;
            tinymce.EditorManager.once('AddEditor', function (e) {
                e.editor.once('init', self.updateDialogPositionCb);
            });
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }

            tinymce.EditorManager.off('init', this.updateDialogPositionCb);

            CreateOrSelectChoiceComponent.__super__.dispose.apply(this, arguments);
        },

        _onEntityChange: function(e) {
            var mode = e.val ? this.MODE_VIEW : this.MODE_CREATE;
            this._setMode(mode);
        },

        _updateDialogPosition: function() {
            var maxHeight = $(window).height();
            var dialogHeight = this.$dialog.height();
            var dialogBottom = dialogHeight + this.$dialog.position.top;

            if (dialogBottom <= maxHeight) {
                return;
            }

            var top = Math.max(0, (maxHeight - dialogHeight) / 2);
            this.$dialog.css('top', top + 'px');
        },

        _updateNewEntityVisibility: function() {
            if (this._isInCreateMode()) {
                this.$newEntity.show();
            } else {
                this.$newEntity.hide();
            }
        },

        _setMode: function (mode) {
            if (this.$mode.val() === mode) {
                return;
            }

            this.$mode.val(mode).change();
        },

        _isInCreateMode: function() {
            return this.$mode.val() === this.MODE_CREATE;
        }
    });

    return CreateOrSelectChoiceComponent;
});
