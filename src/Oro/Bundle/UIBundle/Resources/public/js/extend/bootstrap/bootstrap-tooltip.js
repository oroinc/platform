define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    require('bootstrap-tooltip');

    const Tooltip = $.fn.tooltip.Constructor;
    const original = _.pick(Tooltip.prototype, 'show', 'hide', '_getContainer');

    const DATA_ATTRIBUTE_PATTERN = /^data-[\w-]*$/i;
    Tooltip.Default.whiteList['*'].push(DATA_ATTRIBUTE_PATTERN);
    _.extend(Tooltip.Default.whiteList, {
        mark: [],
        table: [],
        caption: [],
        colgroup: [],
        col: ['span'],
        thead: [],
        tbody: [],
        tfoot: [],
        tr: [],
        th: ['colspan', 'rowspan', 'scope'],
        td: ['colspan', 'rowspan'],
        dl: [],
        dd: [],
        dt: [],
        q: [],
        blockquote: [],
        figure: [],
        picture: [],
        source: ['srcset', 'type']
    });

    Tooltip.prototype.show = function(...args) {
        const result = original.show.apply(this, args);
        const hideHandler = this.hide.bind(this, null);
        const dialogEvents = _.map(['dialogresize', 'dialogdrag', 'dialogreposition'], function(item) {
            return item + '.oro-bs-tooltip';
        });
        $(this.element).closest('.ui-dialog').on(dialogEvents.join(' '), hideHandler);
        $(this.element).parents().on('scroll.oro-bs-tooltip', hideHandler);
        $(window).on('resize.oro-bs-tooltip', hideHandler);

        if ('class' in this.config) {
            $(this.getTipElement()).addClass(_.result(this.config, 'class'));
        }

        return result;
    };

    Tooltip.prototype.hide = function(...args) {
        if ($(this.getTipElement()).hasClass('show')) {
            $(this.element).parents().add(window).off('.oro-bs-tooltip');
        }

        return original.hide.apply(this, args);
    };

    Tooltip.prototype._getContainer = function(...args) {
        let modal;
        if (
            this.config.container === false &&
            (modal = $(this.element).closest('.modal').get(0))
        ) {
            return modal;
        }

        return original._getContainer.apply(this, args);
    };

    const delegateAction = function(method, action) {
        return function(args) {
            if (this.element === null) {
                // disposed
                return;
            }
            // Tooltip/Popover delegates initialization to element -- propagate action them first
            if (this.config.selector) {
                $(this.element).find(this.config.selector).each(function() {
                    if ($(this).data(Tooltip.DATA_KEY)) {
                        $(this).popover(action);
                    }
                });
            }
            // clear timeout if it exists
            if (this.timeout) {
                clearTimeout(this.timeout);
                delete this.timeout;
            }
            return method.apply(this, args);
        };
    };

    Tooltip.prototype.hide = delegateAction(Tooltip.prototype.hide, 'hide');
    Tooltip.prototype.dispose = delegateAction(Tooltip.prototype.dispose, 'dispose');

    $(document)
        .on('initLayout', function(e) {
            $(e.target).find('[data-toggle="tooltip"]').tooltip();
        })
        .on('disposeLayout', function(e) {
            $(e.target).find('[data-toggle="tooltip"]').each(function() {
                const $el = $(this);

                if ($el.data(Tooltip.DATA_KEY)) {
                    $el.tooltip('dispose');
                }
            });
        });
});
