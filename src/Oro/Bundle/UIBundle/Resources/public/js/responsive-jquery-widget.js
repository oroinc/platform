define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var scrollHelper = require('oroui/js/tools/scroll-helper');
    var tools = require('oroui/js/tools');

    var SCROLLBAR_WIDTH = scrollHelper.scrollbarWidth() || 17;
    var DESKTOP_SERVICE_AREA = 80 + 32 + 24 * 2 + SCROLLBAR_WIDTH; // menu, sidebar, content paddings, scollbar widths;
    var MOBILE_SERVICE_AREA = 16 * 2; // content paddings;
    var SERVICE_AREA = tools.isMobile() ? MOBILE_SERVICE_AREA : DESKTOP_SERVICE_AREA;

    var SCREEN_SMALL = 1366 - SERVICE_AREA; // HD ~16:9 1366x768
    var SCREEN_MEDIUM = 1440 - SERVICE_AREA; // WXGA+ 16:10 1440x900
    var SCREEN_LARGE = 1680 - SERVICE_AREA; // WSXGA+ 16:10 1680x1050

    require('jquery-ui');

    /**
     * Widget makes layout responive
     *
     * How does it work:
     *  Widget finds all sections by options.sectionClassName
     *  According to width of found section it get one of options.sizes and add its modifierClassName to
     *  section element
     *  Checks that section has only one cell and adds options.hasSingleCellModifier
     *  For each cell in each section checks that cell has only one block and adds options.hasSingleBlockModifier
     *
     * Widget just adds css classes, so you can define you style for each case
     *
     * $(document).responsive(); // make all options.sectionClassName children responsive
     *
     */
    $.widget('oroui.responsive', {
        options: {
            sectionClassName: 'responsive-section',
            cellClassName: 'responsive-cell',
            blockClassName: 'responsive-block',

            // key to store added classes via jquery.data
            addedClassesDataName: 'responsive-classes',

            sectionNoBlocksModifier: 'responsive-section-no-blocks',
            cellNoBlocksModifier: 'responsive-cell-no-blocks',

            sizes: [{
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
            }]
        },

        widgetEventPrefix: 'responsive-',

        /**
         *
         * @protected
         */
        _init: function() {
            this.$sections = this._getSections();
            this._update();
        },

        /**
         * Update sections' modificators
         *
         * @protected
         */
        _update: function() {
            var context = this;
            var $sections = this.$sections;
            var isChanged = false;

            $sections.each(function() {
                if (context._updateSection($(this))) {
                    isChanged = true;
                }
            });

            if (isChanged) {
                this._trigger('reflow');
            }
        },

        /**
         * Update section modificators
         *
         * @param {jQuery} $section
         * @return {boolean} true if section was updated
         * @protected
         */
        _updateSection: function($section) {
            var context = this;
            var options = this.options;
            var $cells = this._getCellsFromSection($section);
            var sectionWidth = $section.outerWidth();
            var size = this._getSize(sectionWidth);
            var classNames = [size.modifierClassName];
            var hasBlocks = false;
            var isChanged = false;

            $cells.each(function() {
                var $cell = $(this);
                if (context._updateCell($cell)) {
                    isChanged = true;
                }
                hasBlocks = hasBlocks || !$cell.hasClass(options.cellNoBlocksModifier);
            });

            if (!hasBlocks) {
                classNames.push(options.sectionNoBlocksModifier);
            }

            if (this._updateClasses($section, classNames)) {
                isChanged = true;
            }

            return isChanged;
        },

        /**
         * Update cell modifiers
         *
         * @param {jQuery} $cell
         * @return {boolean} true if cell was updated
         * @protected
         */
        _updateCell: function($cell) {
            var isChanged;
            var options = this.options;
            var $blocks = this._getBlocksFromCell($cell);
            var classNames = [];

            if ($blocks.length === 0) {
                classNames.push(options.cellNoBlocksModifier);
            }

            isChanged = this._updateClasses($cell, classNames);

            return isChanged;
        },

        /**
         * Get sections by class name from element
         *
         * @returns {jQuery}
         * @protected
         */
        _getSections: function() {
            var $parent;

            if (this.element.hasClass(this.options.sectionClassName)) {
                $parent = this.element.parent();
            } else {
                $parent = this.element;
            }

            return $parent.find('.' + this.options.sectionClassName);
        },

        /**
         * Get cells by class name from section element
         *
         * @param {jQuery} $section
         * @returns {jQuery}
         * @protected
         */
        _getCellsFromSection: function($section) {
            var $firstCell = $section.find('.' + this.options.cellClassName + ':first');
            var $siblingsCells = $firstCell.siblings();
            return $siblingsCells.add($firstCell);
        },

        /**
         * Get blocks by class name from cell element
         *
         * @param {jQuery} $cell
         * @returns {jQuery}
         * @protected
         */
        _getBlocksFromCell: function($cell) {
            var $firstBlock = $cell.find('.' + this.options.blockClassName + ':first');
            var $siblingsBlocks = $firstBlock.siblings();
            return $siblingsBlocks.add($firstBlock);
        },

        /**
         * Get size object from options.sizes by width
         *
         * @param {number} sectionWidth
         * @returns {Object}
         * @potected
         */
        _getSize: function(sectionWidth) {
            var size = _.find(this.options.sizes, function(value) {
                return (sectionWidth >= value.width.from &&
                    (sectionWidth <= value.width.to || value.width.to === null));
            });
            return size;
        },

        /**
         * Updates classes for the target if they are different from already added
         *
         * @param {jQuery} $target
         * @param {string[]} classNames
         * @return {boolean} if classes are changed
         * @protected
         */
        _updateClasses: function($target, classNames) {
            var addedClasses = $target.data(this.options.addedClassesDataName) || [];
            var isChanged = !_.isEqual(addedClasses, classNames);

            if (isChanged) {
                $target.removeClass(addedClasses.join(' '));
                $target.addClass(classNames.join(' '));
                $target.data(this.options.addedClassesDataName, classNames);
            }

            return isChanged;
        }
    });

    return $;
});
