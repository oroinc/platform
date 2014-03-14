/*global define*/
define(['jquery', 'oroui/js/mediator', 'orotranslation/js/translator', '../content-processor/dropdown-button'
    ], function ($, mediator, __) {
    'use strict';

    function updatePageHeader() {
        var $header = $('.navigation.navbar-extra');
        $header.find('.title-buttons-container').dropdownButtonProcessor({
            moreLabel: __('More')
        });
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
