define(function(require) {
    'use strict';

    var AbstractValidationMessageHandlerView;
    var $ = require('jquery');
    var _ = require('underscore');
    var Popper = require('popper');
    var BaseView = require('oroui/js/app/views/base/view');
    var VALIDATOR_ERROR_CLASS = 'validation-failed';

    function getScrollParent(element) {
        if (!element) {
            return document.body;
        }

        switch (element.nodeName) {
            case 'HTML':
            case 'BODY':
                return element.ownerDocument.body;
            case '#document':
                return element.body;
        }

        var needles = ['auto', 'scroll', 'overlay'];

        if (needles.indexOf($(element).css('overflow-x')) > -1 || needles.indexOf($(element).css('overflow-y')) > -1) {
            return element;
        }

        return getScrollParent(element.parentNode);
    }

    AbstractValidationMessageHandlerView = BaseView.extend({
        autoRender: true,

        label: null,

        scrollParent: null,

        popper: null,

        active: false,

        template: require('tpl!oroform/templates/floating-error-message.html'),

        /**
         * @inheritDoc
         */
        constructor: function AbstractValidationMessageHandlerView() {
            AbstractValidationMessageHandlerView.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            this.label = options.label;
            this.labelContainer = this.label.parent();
            this.scrollParent = options.scrollParent;
            this.active = this.isActive();

            AbstractValidationMessageHandlerView.__super__.initialize.call(this, options);
        },

        getPopperReferenceElement: function() {
            throw new Error('Method `getPopperReferenceElement` has to be overridden in descendant');
        },

        /**
         * Checks if a control has opened dropdown in position when an error label has to be placed above
         *
         * @returns {boolean}
         */
        isActive: function() {
            throw new Error('Method `isActive` has to be overridden in descendant');
        },

        render: function() {
            if (this.popper) {
                this.popper.destroy();
                this.popper = null;
            }

            var message = this.label.text();

            if (message.length) {
                var messageEl = $(this.template({content: message}));

                this.labelContainer.append(messageEl);

                messageEl.css({'max-width': Math.ceil(this.label.width())});

                var popperReference = this.getPopperReferenceElement();

                this.scrollParent = getScrollParent(popperReference[0]);

                this.popper = new Popper(popperReference, messageEl, {
                    placement: _.isRTL() ? 'top-end' : 'top-start',
                    positionFixed: true,
                    removeOnDestroy: true,
                    modifiers: {
                        flip: {enabled: false},
                        arrow: {
                            element: '.arrow',
                            fn: this.arrowModifier.bind(this)
                        },

                        preventOverflow: {
                            boundariesElement: 'window'
                        },

                        hide: {
                            enabled: true,
                            fn: this.hideModifier.bind(this)
                        }
                    }
                });
            }
        },

        arrowModifier: function(data, options) {
            Popper.Defaults.modifiers.arrow.fn(data, options);

            if (data.placement.split('-')[1] === 'end') {
                data.offsets.arrow.left = data.offsets.reference.right - data.offsets.popper.left;
            } else {
                data.offsets.arrow.left = data.offsets.reference.left - data.offsets.popper.left;
            }

            return data;
        },

        hideModifier: function(data, options) {
            var scrollRect = this.scrollParent.getBoundingClientRect();

            if (!this.active || data.offsets.reference.top < scrollRect.top ||
                data.offsets.reference.top > scrollRect.bottom || data.offsets.left > scrollRect.right ||
                data.offsets.left > scrollRect.left
            ) {
                data.hide = true;
                data.attributes['x-out-of-boundaries'] = '';
            } else {
                Popper.Defaults.modifiers.hide.fn(data, options);
            }

            return data;
        },

        update: function() {
            if (this.active) {
                var $lastLabel = this.label.nextAll('.' + VALIDATOR_ERROR_CLASS).last();

                if ($lastLabel.length) {
                    $lastLabel.after(this.label);
                }

                this.label.css('visibility', 'hidden');
            } else {
                this.label.css('visibility', '');
            }

            if (!this.disposed && this.popper) {
                this.popper.scheduleUpdate();
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.label.css('visibility', '');

            if (this.popper) {
                this.popper.destroy();
                this.popper = null;
            }

            AbstractValidationMessageHandlerView.__super__.dispose.call(this);
        }
    }, {
        test: function(element) {
            throw new Error('Method `test` has to be overridden in descendant');
        }
    });

    return AbstractValidationMessageHandlerView;
});
