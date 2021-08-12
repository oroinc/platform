define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!orodatagrid/templates/inline-editing/error-holder.html');
    require('jquery-ui/position');

    /* The same like `getBoundingClientRect` but takes in account all child nodes, i.e. calculates real occupied rect
     *  for case when a child goes beyond its parent
     */
    function getTreeRect(el) {
        if (!el || !_.isFunction(el.getBoundingClientRect)) {
            return null;
        }
        const rect = _.pick(el.getBoundingClientRect(), ['top', 'right', 'bottom', 'left', 'width', 'height']);
        _.forEach(el.childNodes, function(child) {
            const childRect = getTreeRect(child);
            if (childRect) {
                rect.top = Math.min(rect.top, childRect.top);
                rect.left = Math.min(rect.left, childRect.left);
                rect.right = Math.max(rect.right, childRect.right);
                rect.bottom = Math.max(rect.bottom, childRect.bottom);
            }
        });
        rect.width = rect.right - rect.left;
        rect.height = rect.bottom - rect.top;
        return rect;
    }

    const InlineEditorErrorHolderView = BaseView.extend({
        keepElement: true,

        DEFAULT_POSITION: 'top',

        POSITION_CLASSES: {
            bottom: 'error-message-below',
            right: 'error-message-right',
            left: 'error-message-left'
        },

        DROPDOWNS: [
            '.select2-container.select2-drop-below',
            '.timepicker-dialog-is-below',
            '.ui-datepicker-dialog-is-below'
        ],
        DROPUPS: [
            '.select2-container.select2-drop-above',
            '.timepicker-dialog-is-above',
            '.ui-datepicker-dialog-is-above'
        ],

        offsetDefault: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 0
        },

        events: {
            'click': 'updatePosition',
            'mouseenter': 'updatePosition',
            'updatePosition': 'updatePosition',
            'datepicker:dialogShow': 'updatePosition',
            'datepicker:dialogReposition': 'updatePosition',
            'datepicker:dialogHide': 'updatePosition',
            'showTimepicker': 'updatePosition',
            'hideTimepicker': 'updatePosition',
            'select2-open': 'updatePosition',
            'select2-close': 'updatePosition'
        },

        /**
         * @type {jQuery}
         */
        within: null,

        /**
         * @inheritdoc
         */
        constructor: function InlineEditorErrorHolderView(options) {
            InlineEditorErrorHolderView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!options.within) {
                throw new Error('Option "within" is required');
            }
            this._errors = {};
            _.extend(this, _.pick(options, ['within']));
            InlineEditorErrorHolderView.__super__.initialize.call(this, options);
        },

        render: function() {
            const $form = this.$('form');
            this.$('.error-holder').remove();
            this.$el.toggleClass('has-error', !_.isEmpty(this._errors));
            if (!_.isEmpty(this._errors)) {
                if ($form.length && $form.data('validator')) {
                    $form.data('validator').showErrors(this._errors);
                } else {
                    this.$el.append(template({messages: this._errors}));
                }
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$('.error-holder').remove();
            this.$el.removeClass('has-error');
            delete this.within;
            InlineEditorErrorHolderView.__super__.dispose.call(this);
        },

        getWithinRect: function() {
            const rect = _.pick(this.within[0].getBoundingClientRect(),
                ['top', 'right', 'bottom', 'left', 'width', 'height']);
            const headerHeight = this.within.find('thead:first').outerHeight();
            const scroll = $.position.scrollbarWidth();

            // subtract header height
            rect.top += headerHeight;
            rect.height -= headerHeight;

            if (this.within.find('.grid-container').height() > rect.height) {
                // subtract vertical scroll width
                rect.right -= scroll;
                rect.width -= scroll;
            }

            if (this.within.find('.grid-container').width() > rect.width) {
                // subtract horizontal scroll height
                rect.bottom -= scroll;
                rect.height -= scroll;
            }

            return rect;
        },

        updatePosition: function() {
            const errorMessageElements = this.getErrorMessageElements();
            if (errorMessageElements.length === 0) {
                return;
            }
            const withinRect = this.getWithinRect();
            const positionSequence = _.keys(this.POSITION_CLASSES);
            const initialPosition = this.getPositionByClass();
            // check if current position is default and correct
            if (initialPosition === this.DEFAULT_POSITION) {
                if (this.checkPosition(initialPosition, withinRect, errorMessageElements[0])) {
                    return;
                }
            } else {
                // if initial position is'n default it needs to try default position as well
                positionSequence.unshift(this.DEFAULT_POSITION);
            }
            const initialOpacity = errorMessageElements.css('opacity');
            errorMessageElements.css('opacity', 0);
            for (let i = 0; i < positionSequence.length; i++) {
                const position = positionSequence[i];
                this.setPositionClass(position);
                if (this.checkPosition(position, withinRect, errorMessageElements[0])) {
                    break;
                }
            }
            errorMessageElements.css('opacity', initialOpacity);
        },

        checkPosition: function(position, withinRect, errorMessageElement) {
            const methodName = 'check' + _.capitalize(position);
            if (_.isFunction(this[methodName])) {
                return this[methodName](withinRect, errorMessageElement);
            } else {
                return false;
            }
        },

        checkTop: function(withinRect, errorMessageElement) {
            if (this.$(this.DROPUPS.join(', ')).length > 0) { // don't show error above if dropup is present
                return false;
            }
            const messageRect = getTreeRect(errorMessageElement);
            return messageRect && messageRect.top >= withinRect.top && messageRect.right <= withinRect.right;
        },

        checkBottom: function(withinRect, errorMessageElement) {
            if (this.$(this.DROPDOWNS.join(', ')).length > 0) { // don't show error below if dropdown is present
                return false;
            }
            const messageRect = getTreeRect(errorMessageElement);
            return messageRect && messageRect.bottom <= withinRect.bottom && messageRect.right <= withinRect.right;
        },

        checkRight: function(withinRect, errorMessageElement) {
            const messageRect = getTreeRect(errorMessageElement);
            return messageRect && messageRect.right <= withinRect.right;
        },

        checkLeft: function(withinRect, errorMessageElement) {
            const messageRect = getTreeRect(errorMessageElement);
            return messageRect && messageRect.left >= withinRect.left;
        },

        getErrorMessageElements: function() {
            return this.$('.validation-failed:visible');
        },

        setErrorMessages: function(errors) {
            this._errors = errors;
            this.render();
        },

        parseValidatorErrors: function(errorList) {
            const errors = {};
            _.each(errorList, function(errorItem) {
                errors[errorItem.element.getAttribute('name')] = errorItem.message;
            }, this);
            this._errors = errors;
        },

        setPositionClass: function(position) {
            const classesToRemove = _.values(_.omit(this.POSITION_CLASSES, position));
            this.$el.removeClass(classesToRemove.join(' '));
            if (position !== this.DEFAULT_POSITION) {
                this.$el.addClass(_.result(this.POSITION_CLASSES, position));
            }
        },

        getPositionByClass: function() {
            const position = _.findKey(this.POSITION_CLASSES, function(value) {
                return this.$el.hasClass(value);
            }, this);
            return position || this.DEFAULT_POSITION;
        }
    });

    return InlineEditorErrorHolderView;
});
