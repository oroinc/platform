define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    require('jquery-ui');
    require('bootstrap');

    var Tooltip = $.fn.tooltip.Constructor;

    Tooltip.prototype.show = _.wrap(Tooltip.prototype.show, function(func) {
        var result = func.apply(this, _.rest(arguments));
        var hideHandler = _.bind(this.hide, this);
        var dialogEvents = _.map(['dialogresize', 'dialogdrag', 'dialogreposition'], function(item) {
            return item + '.oro-bs-tooltip';
        });
        this.$element.closest('.ui-dialog').on(dialogEvents.join(' '), hideHandler);
        this.$element.parents().on('scroll.oro-bs-tooltip', hideHandler);
        $(window).on('resize.oro-bs-tooltip', hideHandler);
        return result;
    });

    Tooltip.prototype.hide = _.wrap(Tooltip.prototype.hide, function(func) {
        if (this.tip().hasClass('in')) {
            this.$element.parents().add(window).off('.oro-bs-tooltip');
        }
        return func.apply(this, _.rest(arguments));
    });

    Tooltip.prototype.applyPlacement = function(offset, placement) {
        var $tip = this.tip();
        var arrowDimension = ['left', 'right'].indexOf(placement) !== -1 ? 'width' : 'height';
        var arrowSize;
        var position;

        $tip.addClass(placement).addClass('in');
        arrowSize = this.arrow()[0].getBoundingClientRect()[arrowDimension] - 1;

        switch (placement) {
            case 'bottom':
                position = {
                    my: 'center top+' + arrowSize,
                    at: 'center bottom'
                };
                break;
            case 'top':
                position = {
                    my: 'center bottom-' + arrowSize,
                    at: 'center top'
                };
                break;
            case 'left':
                position = {
                    my: 'right-' + arrowSize + ' center',
                    at: 'left center'
                };
                break;
            case 'right':
                position = {
                    my: 'left+' + arrowSize + ' center',
                    at: 'right center'
                };
                break;
        }

        _.extend(position, {
            of: this.$element,
            collision: 'flipfit',
            using: _.bind(this.correctPlacement, this, placement, arrowSize)
        });

        $tip.position(position);
    };

    Tooltip.prototype.correctPlacement = function(placement, arrowSize, props, feedback) {
        var delta;
        var dimension;
        var position;
        var targetPlacement = {bottom: 'top', top: 'bottom', left: 'right', right: 'left'}[placement];
        var actualTargetPlacement = feedback[['left', 'right'].indexOf(placement) !== -1 ? 'horizontal' : 'vertical'];

        // if tooltip was flipped, add correction for arrow placement
        if (actualTargetPlacement !== targetPlacement) {
            this.tip().removeClass(placement).addClass(targetPlacement);
            switch (actualTargetPlacement) {
                case 'bottom':
                    props.top += arrowSize * 2;
                    break;
                case 'top':
                    props.top -= arrowSize * 2;
                    break;
                case 'left':
                    props.left -= arrowSize * 2;
                    break;
                case 'right':
                    props.left += arrowSize * 2;
                    break;
            }
        }

        // add correction for arrow shifting
        if (placement === 'bottom' || placement === 'top') {
            delta = (feedback.target.left + feedback.target.width / 2) -
                (feedback.element.left + feedback.element.width / 2);
            dimension = feedback.element.width;
            position = 'left';
        } else {
            delta = (feedback.target.top + feedback.target.height / 2) -
                (feedback.element.top + feedback.element.height / 2);
            dimension = feedback.element.height;
            position = 'top';
        }

        this.replaceArrow(delta, dimension, position);
        this.tip().css(props);
    };

    Tooltip.prototype.replaceArrow = function(delta, dimension, position) {
        this.arrow()
            .css(position, delta ? (50 + delta / dimension * 100 + '%') : '');
    };

    var delegateAction = function(method, action) {
        return function() {
            var type = this.type;
            // Tooltip/Popover delegates initialization to element -- propagate action them first
            if (this.options.selector) {
                this.$element.find(this.options.selector).each(function() {
                    if ($(this).data(type)) {
                        $(this).popover(action);
                    }
                });
            }
            // clear timeout if it exists
            if (this.timeout) {
                clearTimeout(this.timeout);
                delete this.timeout;
            }
            return method.apply(this, arguments);
        };
    };

    Tooltip.prototype.hide = delegateAction(Tooltip.prototype.hide, 'hide');
    Tooltip.prototype.destroy = delegateAction(Tooltip.prototype.destroy, 'destroy');
});
