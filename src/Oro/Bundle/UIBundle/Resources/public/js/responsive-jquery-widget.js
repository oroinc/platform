/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'jquery-ui'], function ($, _) {
    'use strict';

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

            hasSingleCellModifier: 'responsive-single-cell',
            hasSingleBlockModifier: 'responsive-single-block',
            hasNoBlocksModifier: 'responsive-no-blocks',

            sizes: [{
                modifierClassName: 'responsive-small',
                width: {
                    from: 0,
                    to: 1024
                }
            }, {
                modifierClassName: 'responsive-medium',
                width: {
                    from: 1025,
                    to: 1340
                }
            }, {
                modifierClassName: 'responsive-big',
                width: {
                    from: 1341,
                    to: null // to infinity
                }
            }]
        },

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

            $sections.each(function(index, element) {
                context._updateSection($(element));
            });
        },

        /**
         * Update section modificators
         *
         * @param {jQuery} $section
         * @protected
         */
        _updateSection: function($section) {
            var context = this;
            var options = this.options;
            var $cells = this._getCellsFromSection($section);
            var sectionWidth = $section.outerWidth();
            var size = this._getSize(sectionWidth);
            var classNames = [size.modifierClassName];
            var hasNoBlocks = true;

            if($cells.length === 1) {
                classNames.push(options.hasSingleCellModifier);
            }

            this._updateClasses($section, classNames);

            $cells.each(function(index, cell) {
                var $cell = $(cell);
                context._updateCell($cell);
                if(!$cell.hasClass(options.hasNoBlocksModifier)) {
                    hasNoBlocks = false;
                }
            });

            if(hasNoBlocks) {
                $section.addClass(options.hasNoBlocksModifier);
            }
        },

        /**
         * Update cell modifiers
         *
         * @param {jQuery} $cell
         * @protected
         */
        _updateCell: function($cell) {
            var options = this.options;
            var $blocks = this._getBlocksFromCell($cell);
            var classNames = [];

            if($blocks.length === 1) {
                classNames.push(options.hasSingleBlockModifier);
            } else if ($blocks.length === 0) {
                classNames.push(options.hasNoBlocksModifier);
            }

            this._updateClasses($cell, classNames);
        },

        /**
         * Get sections by class name from element
         *
         * @returns {jQuery}
         * @protected
         */
        _getSections: function() {
            var $parent;

            if(this.element.hasClass(this.options.sectionClassName)) {
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
         * @returns {object}
         * @potected
         */
        _getSize: function(sectionWidth) {
            return _.find(this.options.sizes, function(value) {
                return (sectionWidth >= value.width.from &&
                    (sectionWidth <= value.width.to || value.width.to === null));
            });
        },

        /**
         * Remove all added classes then add new
         *
         * @param {jQuery} $target
         * @param {string[]} classNames
         * @protected
         */
        _updateClasses: function($target, classNames) {
            this._clearClasses($target);
            this._addClasses($target, classNames);
        },

        /**
         * Remove all added classes
         *
         * @param {jQuery} $target
         * @protected
         */
        _clearClasses: function($target) {
            var classNames = $target.data(this.options.addedClassesDataName);

            _.forEach(classNames, function(className) {
                $target.removeClass(className);
            }, this);

            $target.data(this.options.addedClassesDataName, null);
        },

        /**
         * Add classes from set to element
         *
         * @param {jQuery} $target
         * @param {string[]} classNames
         * @protected
         */
        _addClasses: function($target, classNames) {
            _.forEach(classNames, function(className) {
                $target.addClass(className);
            }, this);

            $target.data(this.options.addedClassesDataName, classNames);
        }
    });

    return $;
});
