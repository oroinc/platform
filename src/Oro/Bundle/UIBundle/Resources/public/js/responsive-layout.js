define(function(require) {
    'use strict';

    /**
     * Module makes layout responsive
     *
     * How does it work:
     *  According to width of its element it finds appropriate item in SIZES and adds its modifierClassName
     *  to section element
     *  Checks if section has only one cell and adds particular class
     *  For each cell in the section checks that cell has only one block and adds special class
     *
     * Module just adds css classes, so you can define you style for each case
     */

    const $ = require('jquery');
    const _ = require('underscore');
    const scrollHelper = require('oroui/js/tools/scroll-helper');
    const tools = require('oroui/js/tools');
    const mediator = require('oroui/js/mediator');

    const SCROLLBAR_WIDTH = scrollHelper.scrollbarWidth() || 17;
    const DESKTOP_SERVICE_AREA = 80 + 32 + 24 * 2 + SCROLLBAR_WIDTH; // menu, sidebar, content paddings, scollbar widths;
    const MOBILE_SERVICE_AREA = 16 * 2; // content paddings;
    const SERVICE_AREA = tools.isMobile() ? MOBILE_SERVICE_AREA : DESKTOP_SERVICE_AREA;

    const SCREEN_SMALL = 1366 - SERVICE_AREA; // HD ~16:9 1366x768
    const SCREEN_MEDIUM = 1440 - SERVICE_AREA; // WXGA+ 16:10 1440x900
    const SCREEN_LARGE = 1680 - SERVICE_AREA; // WSXGA+ 16:10 1680x1050

    const ADDED_CLASSES_DATA_KEY = 'responsive-classes';

    const CSS_VAR_NAMES = {
        MINIMAL_WIDTH_THRESHOLD: '--responsive-section-container-minimal-width-threshold'
    };

    const SELECTORS = {
        SECTION: '.responsive-section',
        CELL: '.responsive-cell',
        BLOCK: '.responsive-block'
    };

    const MODIFIERS = {
        SECTION_NO_BLOCKS: 'responsive-section-no-blocks',
        CELL_NO_BLOCKS: 'responsive-cell-no-blocks'
    };

    const SIZES = [
        {
            modifierClassName: 'responsive-small',
            width: {
                from: 0,
                to: SCREEN_SMALL - 1
            }
        }, {
            modifierClassName: 'responsive-medium',
            width: {
                from: SCREEN_SMALL,
                to: SCREEN_MEDIUM - 1
            }
        }, {
            modifierClassName: 'responsive-big',
            width: {
                from: SCREEN_MEDIUM,
                to: SCREEN_LARGE - 1
            }
        }, {
            modifierClassName: '',
            width: {
                from: SCREEN_LARGE,
                to: null // to infinity
            }
        }
    ];

    /**
     * Update section modificators
     *
     * @param {jQuery} $section
     * @return {boolean} true if section was updated
     */
    function updateSection($section) {
        const $cells = $section.find(SELECTORS.CELL + ':first').siblings().addBack();
        const sectionWidth = $section.outerWidth();
        const classNames = [getSizeClass(sectionWidth)];
        let hasBlocks = false;
        let isChanged = false;

        $cells.each(function(index, cell) {
            const $cell = $(cell);

            if (updateCell($cell)) {
                isChanged = true;
            }

            hasBlocks = hasBlocks || !$cell.hasClass(MODIFIERS.CELL_NO_BLOCKS);
        });

        if (!hasBlocks) {
            classNames.push(MODIFIERS.SECTION_NO_BLOCKS);
        }

        if (updateClasses($section, classNames)) {
            isChanged = true;
        }

        return isChanged;
    }

    /**
     * Update cell modifiers
     *
     * @param {jQuery} $cell
     * @return {boolean} true if cell was updated
     */
    function updateCell($cell) {
        const $blocks = $cell.find(SELECTORS.BLOCK + ':first').siblings().addBack();
        const classNames = [];

        if ($blocks.length === 0) {
            classNames.push(MODIFIERS.CELL_NO_BLOCKS);
        }

        const isChanged = updateClasses($cell, classNames);

        return isChanged;
    }


    /**
     * Get size object from options.sizes by width
     *
     * @param {number} sectionWidth
     * @returns {Object}
     */
    function getSizeClass(sectionWidth) {
        const size = _.find(SIZES, function(value) {
            return (sectionWidth >= value.width.from &&
                (sectionWidth <= value.width.to || value.width.to === null));
        });

        return size ? size.modifierClassName : '';
    }

    /**
     * Updates classes for the target if they are different from already added
     *
     * @param {jQuery} $target
     * @param {string[]} classNames
     * @return {boolean} if classes are changed
     */
    function updateClasses($target, classNames) {
        const addedClasses = $target.data(ADDED_CLASSES_DATA_KEY) || [];
        const isChanged = !_.isEqual(addedClasses, classNames);

        if (isChanged) {
            $target.removeClass(addedClasses.join(' '));
            $target.addClass(classNames.join(' '));
            $target.data(ADDED_CLASSES_DATA_KEY, classNames);
        }

        return isChanged;
    }

    /**
     * Toggles correspondent CSS class if container reached minimal width threshold
     *
     * @param {jQuery} $content
     */
    function checkMinimalWidthThreshold($content) {
        const $scrollableContainerInside = $content.find(SELECTORS.SECTION).first().closest('.container-fluid');
        const minimalWidthThreshold = Number(getComputedStyle(document.body)
            .getPropertyValue(CSS_VAR_NAMES.MINIMAL_WIDTH_THRESHOLD).trim());
        const isMinimalWidthThreshold = minimalWidthThreshold > $scrollableContainerInside.parent().width();
        $scrollableContainerInside.toggleClass('responsive-section-container-minimal', isMinimalWidthThreshold);
    }

    return {
        /**
         * Finds responsive sections in received context (or in document if context wasn't determined)
         * and update them and its blocks with appropriate classes
         *
         * @param {HTMLElement} [context]
         */
        update: function(context) {
            let isChanged = false;

            $(context || window.document).find(SELECTORS.SECTION).each(function() {
                isChanged = updateSection($(this)) || isChanged;
            });

            checkMinimalWidthThreshold($(context || window.document));

            if (isChanged) {
                mediator.trigger('layout:reposition', context);
            }
        }
    };
});
