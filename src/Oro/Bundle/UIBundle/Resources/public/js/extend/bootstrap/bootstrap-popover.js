define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const layout = require('oroui/js/layout');

    require('bootstrap-popover');
    require('./bootstrap-tooltip');

    const Tooltip = $.fn.tooltip.Constructor;
    const Popover = $.fn.popover.Constructor;

    _.extend(Popover.prototype, _.pick(Tooltip.prototype, 'show', 'hide', 'dispose'));

    Popover.prototype.getContent = function() {
        return $('<div/>').append(this._getContent()).html();
    };

    Popover.prototype.applyPlacement = function(offset, placement) {
        const isOpen = this.isOpen();

        _.extend(this.config, {offset: offset, placement: placement});
        this.update();
        this.hide();

        if (isOpen) {
            this.show();
        }
    };

    Popover.prototype.getTipElement = function() {
        this.tip = this.tip || $(this.config.template)[0];

        const addClass = $(this.element).data('class');
        if (addClass) {
            $(this.tip).addClass(addClass);
        }

        return this.tip;
    };

    Popover.prototype.updateContent = function(content) {
        this.element.setAttribute('data-content', content);
        this.config.content = content;
        if (this.isOpen()) {
            this.show();
        }
    };

    Popover.prototype.isOpen = function() {
        return $(this.getTipElement()).is(':visible');
    };

    $(document)
        .on('initLayout', function(e) {
            layout.initPopover($(e.target));
        })
        .on('disposeLayout', function(e) {
            $(e.target).find('[data-toggle="popover"]').each(function() {
                const $el = $(this);

                if ($el.data(Popover.DATA_KEY)) {
                    $el.popover('dispose');
                }
            });
        });

    return Popover;
});
