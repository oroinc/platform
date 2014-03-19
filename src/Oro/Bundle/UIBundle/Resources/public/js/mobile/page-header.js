/*global define*/
define(['jquery', 'oroui/js/mediator', 'orotranslation/js/translator', '../content-processor/dropdown-button'
    ], function ($, mediator, __) {
    'use strict';

    function updatePageHeader() {
        var $header = $('.navigation.navbar-extra'),
            $container = $header.find('.title-buttons-container'),
            options = {
                moreLabel: __('More'),
                minItemQuantity: 1
            },
            label = $container.find('.btn').slice(0,2).text().replace(/\s{2,}/g, ' ');
        if (label.length > 35) {
            options.minItemQuantity = 0;
        }
        $container.dropdownButtonProcessor(options);
    }

    /**
     * Initializes mobile layout for page-header
     *
     * @export oroui/js/mobile/page-header
     * @name oro.mobile.pageHeader
     */
    return {
        init: function () {
            updatePageHeader();
            mediator.on('hash_navigation_request:complete', updatePageHeader);
        }
    };
});
