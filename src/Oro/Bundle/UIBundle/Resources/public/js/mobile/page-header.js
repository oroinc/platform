define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    require('oroui/js/content-processor/dropdown-button');
    const config = require('module-config').default(module.id);
    const containerSelector = '.navigation.navbar-extra .title-buttons-container';

    function renovateDropdownActions($subContainer) {
        const $container = $(containerSelector);

        if (!$container.data('orouiDropdownButtonProcessor')) {
            return;
        }

        const $stickyElement = $container.dropdownButtonProcessor('instance').$stickyElement;

        if (!$.contains(document.body, $stickyElement[0])) {
            return;
        }

        const $dropdown = $(`[aria-labelledby="${$stickyElement.attr('id')}"]`);
        const $newActions = $subContainer.find('[data-action-name]');
        let $oldActions = $dropdown.find('[data-action-name]');

        // Replace all old actions by new ones in the dropdown
        $newActions.each((i, newAction) => {
            const $oldAction = $oldActions.filter(`[data-action-name="${$(newAction).data('action-name')}"]`);
            const $newItem = $container.dropdownButtonProcessor('prepareDropdownButtons', $(newAction));

            if ($oldAction.length) {
                const $oldItem = $oldAction.parent();

                // Remove elements from the sets
                $oldActions = $oldActions.not($oldAction);

                $newItem.insertAfter($oldItem);
                $oldItem.remove();
            } else {
                $dropdown.append($newItem);
            }
        });
        // Go through the rest of old actions and remove them if they are disposed
        $oldActions.each((i, el) => {
            if ($(el).data('disposed')) {
                $(el).parent().remove();
            }
        });
    }

    function updatePageHeader() {
        const $container = $(containerSelector);
        const options = _.extend({
            moreLabel: __('oro.ui.page_header.button.more'),
            minItemQuantity: 1,
            moreButtonAttrs: {
                'class': 'btn-icon dropdown-toggle--no-caret',
                'data-fullscreenable': true
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
            mediator.on('widget:contentLoad', $widget => {
                if ($.contains($(containerSelector)[0], $widget[0])) {
                    renovateDropdownActions($widget);
                }
            });
        }
    };
});
