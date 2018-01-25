define(['jquery', 'oroui/js/mediator', 'orotranslation/js/translator', '../content-processor/dropdown-button'
], function($, mediator, __) {
    'use strict';

    var containerSelector = '.navigation.navbar-extra .title-buttons-container';

    function hideButtons() {
        $(containerSelector).find('.btn').parent().hide();
    }

    function updatePageHeader() {
        var $container = $(containerSelector);
        var options = {
            moreLabel: __('oro.ui.page_header.button.more'),
            minItemQuantity: 1
        };
        var label = $container.find('.btn').slice(0, 2).text().replace(/\s{2,}/g, ' ');
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
        init: function() {
            hideButtons();
            mediator.on('page:afterChange', updatePageHeader);
        }
    };
});
