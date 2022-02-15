define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    require('oroui/js/content-processor/dropdown-button');
    const config = require('module-config').default(module.id);
    const containerSelector = '.navigation.navbar-extra .title-buttons-container';

    function updatePageHeader() {
        const $container = $(containerSelector);
        const options = _.extend({
            moreLabel: __('oro.ui.page_header.button.more'),
            minItemQuantity: 1,
            moreButtonAttrs: {
                'class': 'btn-icon dropdown-toggle--no-caret'
            }
        }, config.dropdownButtonProcessorOptions || {});
        const label = $container.find('.btn').slice(0, 2).text().replace(/\s{2,}/g, ' ');
        if (label.length > 35) {
            options.minItemQuantity = 0;
        }
        options.stickyOptions = {
            enabled: Boolean($container.closest('form').length),
            relativeTo: 'body'
        };
        $container.dropdownButtonProcessor(options);
        $container.addClass('buttons-grouped');

        if ($container.dropdownButtonProcessor('isGrouped')) {
            if (!$container.closest('.row').find('.dashboard-selector-container').length) {
                $container.closest('.row').addClass('row__nowrap');
            }
        }
    }

    /**
     * Initializes mobile layout for page-header
     *
     * @export oroui/js/mobile/page-header
     * @name oro.mobile.pageHeader
     */
    return {
        init: function() {
            mediator.on('page:afterChange', updatePageHeader);
        }
    };
});
