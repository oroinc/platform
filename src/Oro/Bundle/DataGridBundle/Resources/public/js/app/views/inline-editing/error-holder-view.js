define(function(require) {
    'use strict';

    var InlineEditorErrorHolderView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('tpl!orodatagrid/templates/inline-editing/error-holder.html');

    /* The same like `getBoundingClientRect` but takes in account all child nodes, i.e. calculates real occupied rect
     *  for case when a child goes beyond its parent
     */
    function getTreeRect(el) {
        if (!el || !_.isFunction(el.getBoundingClientRect)) {
            return null;
        }
        var rect = _.pick(el.getBoundingClientRect(), ['top', 'right', 'bottom',  'left', 'width', 'height']);
        _.forEach(el.childNodes, function(child) {
            var childRect = getTreeRect(child);
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

    require('jquery-ui');

    InlineEditorErrorHolderView = BaseView.extend({
        keepElement: true,

        POSITION_TOP: '',
        POSITION_RIGHT: 'error-message-right',
        POSITION_BOTTOM: 'error-message-below',
        POSITION_LEFT: 'error-message-left',
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
            click: 'updatePosition',
            mouseenter: 'updatePosition',
            updatePosition: 'updatePosition',
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

        initialize: function(options) {
            if (!options.within) {
                throw new Error('Option "within" is required');
            }
            this._errors = {};
            _.extend(this, _.pick(options, ['within']));
            InlineEditorErrorHolderView.__super__.initialize.call(this, options);
        },

        render: function() {
            var $form = this.$('form');
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
            var rect = _.pick(this.within[0].getBoundingClientRect(),
                ['top', 'right', 'bottom',  'left', 'width', 'height']);
            var headerHeight = this.within.find('thead:first').outerHeight();
            var scroll = $.position.scrollbarWidth();

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
            var errorMessageElements = this.getErrorMessageElements();
            if (errorMessageElements.length === 0) {
                return;
            }
            var withinRect = this.getWithinRect();
            var classes = [this.POSITION_TOP, this.POSITION_RIGHT, this.POSITION_BOTTOM, this.POSITION_LEFT].join(' ');
            var initialOpacity = errorMessageElements.css('opacity');
            errorMessageElements.css('opacity', 0);
            this.$el.removeClass(classes);
            $.Deferred().resolve(false).promise()
                .then(_.bind(this.tryTop, this, withinRect))
                .then(_.bind(this.tryBottom, this, withinRect))
                .then(_.bind(this.tryRight, this, withinRect))
                .then(_.bind(this.tryLeft, this, withinRect))
                .then(function() {
                    errorMessageElements.css('opacity', initialOpacity);
                });
        },

        tryTop: function(withinRect, done) {
            if (done || this.$(this.DROPUPS.join(', ')).length) {
                return done;
            }
            this.$el.addClass(this.POSITION_TOP);
            var messageRect = getTreeRect(this.getErrorMessageElements()[0]);
            if (messageRect && messageRect.top >= withinRect.top && messageRect.right <= withinRect.right) {
                done = true;
            } else {
                this.$el.removeClass(this.POSITION_TOP);
            }

            return done;
        },

        tryBottom: function(withinRect, done) {
            if (done || this.$(this.DROPDOWNS.join(', ')).length) {
                return done;
            }
            this.$el.addClass(this.POSITION_BOTTOM);
            var messageRect = getTreeRect(this.getErrorMessageElements()[0]);
            if (messageRect && messageRect.bottom <= withinRect.bottom && messageRect.right <= withinRect.right) {
                done = true;
            } else {
                this.$el.removeClass(this.POSITION_BOTTOM);
            }

            return done;
        },

        tryRight: function(withinRect, done) {
            if (done) {
                return done;
            }

            this.$el.addClass(this.POSITION_RIGHT);
            var messageRect = getTreeRect(this.getErrorMessageElements()[0]);
            if (messageRect && messageRect.right <= withinRect.right) {
                done = true;
            } else {
                this.$el.removeClass(this.POSITION_RIGHT);
            }

            return done;
        },

        tryLeft: function(withinRect, done) {
            if (done) {
                return done;
            }

            this.$el.addClass(this.POSITION_LEFT);
            var messageRect = getTreeRect(this.getErrorMessageElements()[0]);
            if (messageRect && messageRect.left >= withinRect.left) {
                done = true;
            } else {
                this.$el.removeClass(this.POSITION_LEFT);
            }

            return done;
        },

        getErrorMessageElements: function() {
            return this.$('.validation-failed:visible');
        },

        setErrorMessages: function(errors) {
            this._errors = errors;
            this.render();
        },

        adoptErrorMessage: function() {
            var errors = {};
            _.each(this.getErrorMessageElements(), function(label) {
                var name = label.getAttribute('for');
                var $input = this.$('#' + name);
                // if 'for' attribute reference to an input's ID, map it into its name
                if ($input.length) {
                    name = $input[0].getAttribute('name');
                }
                errors[name] = $(label).text();
            }, this);
            this._errors = errors;
        }
    });

    return InlineEditorErrorHolderView;
});
