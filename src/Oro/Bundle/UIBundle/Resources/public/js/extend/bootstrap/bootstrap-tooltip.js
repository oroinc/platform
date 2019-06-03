define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    require('jquery-ui');
    require('bootstrap-tooltip');

    var Tooltip = $.fn.tooltip.Constructor;
    var original = _.pick(Tooltip.prototype, 'show', 'hide', '_getContainer');

    var DATA_ATTRIBUTE_PATTERN = /^data-[\w-]*$/i;
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
        figure: []
    });

    Tooltip.prototype.show = function() {
        var result = original.show.apply(this, arguments);
        var hideHandler = this.hide.bind(this, null);
        var dialogEvents = _.map(['dialogresize', 'dialogdrag', 'dialogreposition'], function(item) {
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

    Tooltip.prototype.hide = function() {
        if ($(this.getTipElement()).hasClass('show')) {
            $(this.element).parents().add(window).off('.oro-bs-tooltip');
        }

        return original.hide.apply(this, arguments);
    };

    Tooltip.prototype._getContainer = function() {
        var modal;
        if (
            this.config.container === false &&
            (modal = $(this.element).closest('.modal').get(0))
        ) {
            return modal;
        }

        return original._getContainer.apply(this, arguments);
    };

    var delegateAction = function(method, action) {
        return function() {
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
            return method.apply(this, arguments);
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
                var $el = $(this);

                if ($el.data(Tooltip.DATA_KEY)) {
                    $el.tooltip('dispose');
                }
            });
        });
});
