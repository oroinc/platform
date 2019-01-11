define(function(require) {
    'use strict';

    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    require('oroui/js/content-processor/dropdown-button');
    var config = require('module').config();
    var containerSelector = '.navigation.navbar-extra .title-buttons-container';

    function hideButtons() {
        $(containerSelector).hide();
    }

    function showButtons() {
        var $container = $(containerSelector);
        if ($container.dropdownButtonProcessor('isGrouped')) {
            $container.closest('.row').addClass('row__nowrap');
        }
        $container.show();
    }

    function updatePageHeader() {
        var $container = $(containerSelector);
        var options = _.extend({
            moreLabel: __('oro.ui.page_header.button.more'),
            minItemQuantity: 1,
            moreButtonAttrs: {
                'class': 'btn-icon dropdown-toggle--no-caret'
            }
        }, config.dropdownButtonProcessorOptions || {});
        var label = $container.find('.btn').slice(0, 2).text().replace(/\s{2,}/g, ' ');
        if (label.length > 35) {
            options.minItemQuantity = 0;
        }
        options.stickyButton = {
            enabled: Boolean($container.closest('form').length)
        };
        $container.dropdownButtonProcessor(options);

        showButtons();
    }

    /**
     * Initializes mobile layout for page-header
     *
     * @export oroui/js/mobile/page-header
     * @name oro.mobile.pageHeader
     */
    return {
        init: function() {
            hideButtons();
            mediator.on('page:afterChange', updatePageHeader);
        }
    };
});
