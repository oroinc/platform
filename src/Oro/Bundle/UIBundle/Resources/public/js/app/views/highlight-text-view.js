define(function(require) {
    'use strict';

    var HighlightTextView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var FuzzySearch = require('oroui/js/fuzzy-search');

    HighlightTextView = BaseView.extend({
        /**
         * @inheritDoc
         */
        optionNames: BaseView.prototype.optionNames.concat([
            'text',
            'fuzzySearch',
            'viewGroup',
            'highlightClass', 'elementHighlightClass', 'notFoundClass', 'foundClass',
            'highlightSelectors', 'toggleSelectors'
        ]),

        /**
         * @property {String}
         */
        text: '',

        /**
         * @property {RegExp|null}
         */
        findText: null,

        /**
         * @property {String}
         */
        fuzzySearch: false,

        /**
         * @property {String}
         */
        highlightClass: 'highlight-text',

        /**
         * @property {String}
         */
        elementHighlightClass: 'highlight-element',

        /**
         * @property {String}
         */
        notFoundClass: 'highlight-not-found',

        /**
         * @property {String}
         */
        foundClass: 'highlight-found',

        /**
         * @property {String}
         */
        replaceBy: '',

        /**
         * @property {Array}
         */
        highlightSelectors: [],

        /**
         * @property {Array}
         */
        toggleSelectors: {},

        /**
         * @property {String}
         */
        viewGroup: '',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.findHighlightClass = '.' + this.highlightClass;
            this.findElementHighlightClass = '.' + this.elementHighlightClass;
            this.findNotFoundClass = '.' + this.notFoundClass;
            this.findFoundClass = '.' + this.foundClass;
            this.replaceBy = '<span class="' + this.highlightClass + '">$&</span>';

            HighlightTextView.__super__.initialize.apply(this, arguments);

            this.update(this.text);

            mediator.on(this.viewGroup + ':highlight-text:update', this.update, this);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this.clear();
            this.highlightElements();
            this.toggleElements();
        },

        /**
         * Refresh highlight using new text
         *
         * @param {String} text
         */
        update: function(text, options) {
            if (options) {
                _.extend(this, options);
            }
            this.text = text;
            var regexp = this.text;
            if (this.fuzzySearch) {
                regexp = this.text.toLowerCase().replace(/\s/g, '').split('');
                regexp = '[' + _.uniq(regexp).join('') + ']';
            }
            this.findText = this.text.length ? new RegExp(regexp, 'gi') : null;

            this.render();
        },

        /**
         * Highlight text in all found elements
         */
        highlightElements: function() {
            _.each(this.findElements(this.highlightSelectors), this.highlightElement, this);
        },

        /**
         * Toggle found/not-found class for all elements based on found highlighted elements
         */
        toggleElements: function() {
            if (this.isElementHighlighted(this.$el)) {
                _.each(this.findElements(_.keys(this.toggleSelectors)), this.toggleElement, this);
            }
        },

        /**
         * Return found elements with matched selector
         *
         * @param {Array} selectors
         * @return {Array}
         */
        findElements: function(selectors) {
            var elements = [];
            _.each(selectors, function(selector) {
                this.$(selector).each(function() {
                    elements.push({
                        selector: selector,
                        $el: $(this)
                    });
                });
            }, this);

            return elements;
        },

        /**
         * Toggle found/not-found class for given element based on found highlighted elements
         *
         * @param {Object} element
         */
        toggleElement: function(element) {
            var $el = element.$el;
            if (!$el.is(':visible')) {
                return;
            }

            if (this.isElementHighlighted($el)) {
                $el.addClass(this.foundClass);
                return;
            }

            var $parent = $el.closest(this.toggleSelectors[element.selector]);
            if (this.isElementHighlighted($parent)) {
                $el.addClass(this.notFoundClass);
            }
        },

        /**
         * Highlight text in given element
         *
         * @param {Object} element
         */
        highlightElement: function(element) {
            var $el = element.$el;

            var $content = this.getElementContent($el);
            if (this.findText) {
                this.highlightElementContent($content);
            }
            this.setElementContent($el, $content);
        },

        /**
         * Check visible highlighted elements exists in given element
         *
         * @param {jQuery} $el
         * @return {boolean}
         */
        isElementHighlighted: function($el) {
            var $highlighted = $el.find(this.findElementHighlightClass);
            if ($el.hasClass(this.elementHighlightClass)) {
                $highlighted = $highlighted.add($el);
            }
            return $highlighted.filter(':visible').length > 0;
        },

        /**
         * Check highlighted text exists in given element
         *
         * @param {jQuery} $el
         * @param {Boolean|null} filterVisible
         * @return {boolean}
         */
        isElementContentHighlighted: function($el, filterVisible) {
            var $highlighted = $el.find(this.findHighlightClass);
            if (filterVisible !== false) {
                $highlighted = $highlighted.filter(':visible');
            }
            return $highlighted.length > 0;
        },

        /**
         * Remove highlight from all elements
         */
        clear: function() {
            _.each(this.$el.find(this.findElementHighlightClass), function(element) {
                var $el = $(element);
                var $content = this.getElementContent($el);

                $el.removeClass(this.elementHighlightClass);
                $content.find(this.findHighlightClass).each(function() {
                    var $el = $(this);
                    $el.replaceWith($el.html());
                });

                this.setElementContent($el, $content);
            }, this);

            this.$el.find(this.findNotFoundClass).removeClass(this.notFoundClass);
            this.$el.find(this.findFoundClass).removeClass(this.foundClass);
        },

        /**
         * Return element content, based on element type
         *
         * @param {jQuery} $el
         * @return {jQuery}
         */
        getElementContent: function($el) {
            var isPopover = $el.data('popover');
            var content = !isPopover ? $el.html() : $el.data('popover').getContent();

            return $('<div/>').html(content);
        },

        /**
         * Set processed content to element
         *
         * @param {jQuery} $el
         * @param {jQuery} $content
         */
        setElementContent: function($el, $content) {
            var isPopover = $el.data('popover');
            if (isPopover) {
                $el.data('popover').updateContent($content.html());
                $el.toggleClass(this.elementHighlightClass, this.isElementContentHighlighted($content, false));
            } else {
                $el.html($content.html());
                $el.toggleClass(this.elementHighlightClass, this.isElementContentHighlighted($el));
            }
        },

        /**
         * Highlight text in given content
         *
         * @param {jQuery} $content
         */
        highlightElementContent: function($content) {
            _.each($content.contents(), function(children) {
                var $children = $(children);
                if (children.nodeName === '#text') {
                    var text = $children.text();
                    if (!this.fuzzySearch || FuzzySearch.isMatched(text, this.text)) {
                        text = text.replace(this.findText, this.replaceBy);
                        $children.replaceWith(text);
                    }
                } else {
                    this.highlightElementContent($children);
                }
            }, this);
        }
    });

    return HighlightTextView;
});
