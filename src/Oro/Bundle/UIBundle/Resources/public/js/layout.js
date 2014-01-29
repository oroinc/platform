/*global define*/
/*jshint browser: true*/
define(function (require) {
    'use strict';

    var $ = require('jquery');

    var __ = require('oro/translator');
    var scrollspy = require('oro/scrollspy');
    var _bootstrapTooltip = require('bootstrap-tooltip');
    var _jqueryUI = require('jquery-ui');
    var _jqueryUITimepicker = require('jquery-ui-timepicker');

    var documentTitle = '';

    var layout = {};

    layout.init = function (container) {
        container = $(container || document.body);
        this.styleForm(container);

        scrollspy.init(container);

        container.find('[data-toggle="tooltip"]').tooltip();

        var handlePopoverMouseout = function (e, popover) {
            var popoverHandler = $(e.relatedTarget).closest('.popover');
            if (!popoverHandler.length) {
                popover.data('popover-timer',
                    setTimeout(function () {
                        popover.popover('hide');
                        popover.data('popover-active', false);
                    }, 500));
            } else {
                popoverHandler.one('mouseout', function (evt) {
                    handlePopoverMouseout(evt, popover);
                });
            }
        };
        $('form label [data-toggle="popover"]')
            .popover({
                animation: true,
                delay: { show: 0, hide: 0 },
                html: true,
                trigger: 'manual'
            })
            .mouseover(function () {
                var popoverEl = $(this);
                clearTimeout(popoverEl.data('popover-timer'));
                if (!popoverEl.data('popover-active')) {
                    popoverEl.data('popover-active', true);
                    $(this).popover('show');
                }
            })
            .mouseout(function (e) {
                var popover = $(this);
                setTimeout(function () {
                    handlePopoverMouseout(e, popover);
                }, 500);
            });

        setTimeout(function () {
            scrollspy.top();
        }, 500);
    };

    layout.setDocumentTitle = function (title) {
        documentTitle = title;
    };

    layout.initDocumentTitle = function () {
        if (documentTitle) {
            document.title = documentTitle;
        }
    };

    layout.hideProgressBar = function () {
        var $bar = $('#progressbar');
        if ($bar.is(':visible')) {
            $bar.hide();
            $('#page').show();
        }
    };

    layout.styleForm = function (container) {
        if ($.isPlainObject($.uniform)) {
            var elements = $(container).find('input:file, select:not(.select2)');
            elements.uniform();
            elements.trigger('uniformInit');
        }
    };

    return layout;
});
