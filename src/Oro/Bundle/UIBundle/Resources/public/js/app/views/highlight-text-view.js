define(function(require) {
    'use strict';

    var HighlightTextView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var Popover = require('bootstrap-popover');
    var FuzzySearch = require('oroui/js/fuzzy-search');
    var persistentStorage = require('oroui/js/persistent-storage');
    var highlightSwitcherTemplate = require('tpl!oroui/templates/highlight-switcher.html');

    HighlightTextView = BaseView.extend({
        /**
         * @inheritDoc
         */
        optionNames: BaseView.prototype.optionNames.concat([
            'text', 'toggleSelectors', 'viewGroup', 'notFoundClass',
            'elementHighlightClass', 'foundClass', 'fuzzySearch',
            'highlightClass', 'highlightSelectors', 'highlightStateStorageKey',
            'highlightSwitcherContainer', 'highlightSwitcherElement',
            'highlightSwitcherTemplate', 'showNotFoundItems'
        ]),

        events: {
            'click [data-role="highlight-switcher"]': 'changeHighlightSwitcherState'
        },

        /**
         * @property {Function}
         */
        highlightSwitcherTemplate: highlightSwitcherTemplate,

        /**
         * @property {String}
         */
        highlightSwitcherElement: '[data-role="highlight-switcher"]',

        /**
         * @property {String}
         */
        highlightSwitcherContainer: null,

        /**
         * @property {String}
         */
        highlightStateStorageKey: null,

        /**
         * @property {String}
         */
        text: '',

        /**
         * @property {RegExp|null}
         */
        findText: null,

        /**
         * @property {Boolean}
         */
        fuzzySearch: false,

        /**
         * @property {Boolean}
         */
        showNotFoundItems: false,

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
        groupedElementSelector: 'div[data-name="field__value"]',

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
        constructor: function HighlightTextView() {
            HighlightTextView.__super__.constructor.apply(this, arguments);
        },

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

            this.renderHighlightSwitcher();
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
         * @param {Boolean|null} fuzzySearch
         */
        update: function(text, fuzzySearch) {
            if (fuzzySearch !== undefined) {
                this.fuzzySearch = fuzzySearch;
            }
            this.text = text;
            var regexp = this.text;

            if (this.fuzzySearch) {
                regexp = this.text.toLowerCase().replace(/\s/g, '').split('');
                regexp = '[' + this._escape(_.uniq(regexp).join('')) + ']';
            } else {
                regexp = this._escape(regexp);
            }
            this.findText = this.text.length ? new RegExp(regexp, 'gi') : null;

            this.render();
            this.toggleHighlightSwitcher();
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
            _.each(this.findElements(_.keys(this.toggleSelectors)), this.toggleElement, this);
            if (this.isElementHighlighted(this.$el)) {
                _.each(this.findElements(_.keys(this.toggleSelectors)), this.toggleElement, this);
            }

            _.each(this.$(this.groupedElementSelector), this.showGroupContainingHighlighted, this);
        },

        /**
         * Show whole subform if it contains at least one highlighted element.
         *
         * @param {Element} groupEl
         */
        showGroupContainingHighlighted: function(groupEl) {
            var $groupEl = $(groupEl);
            if (this.isElementHighlighted($groupEl)) {
                $groupEl.find(this.findNotFoundClass).removeClass(this.notFoundClass);
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
            if (this.isElementHighlighted($parent) && !this.showNotFoundItems) {
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
         * Check is applicable switcher state
         *
         * @return {boolean}
         */
        isApplicableSwitcher: function() {
            var foundHighlight = this.$el.find(this.findHighlightClass);
            var foundSiblings = this.$el.find(this.findFoundClass).siblings().not(this.findHighlightClass);
            return foundHighlight.length && foundSiblings.length;
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

                if (!this._isFieldChoice($el)) {
                    this.setElementContent($el, $content);
                }
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
            var content;
            var popover = $el.data(Popover.DATA_KEY);

            if (popover !== void 0) {
                content = $('<div/>').html(popover.getContent());
            } else if (this._isField($el) && !this._isFieldChoice($el)) {
                content = $('<div/>').html($el.val());
            } else {
                content = $el;
            }

            return content;
        },

        /**
         * Set processed content to element
         *
         * @param {jQuery} $el
         * @param {jQuery} $content
         */
        setElementContent: function($el, $content) {
            var popover = $el.data(Popover.DATA_KEY);

            if (popover !== void 0) {
                popover.updateContent($content.html());
                $el.toggleClass(this.elementHighlightClass, this.isElementContentHighlighted($content, false));
            } else if (this._isFieldChoice($el)) {
                $el.parent().toggleClass(this.elementHighlightClass, this.isElementContentHighlighted($content, false));
            } else if (this._isField($el)) {
                $el.toggleClass(this.elementHighlightClass, this.isElementContentHighlighted($content, false));
            } else {
                $el.get(0).normalize();
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
                    if (!this.fuzzySearch || FuzzySearch.isMatched(_.trim(text), this.text)) {
                        text = text.replace(this.findText, this.replaceBy);
                        $children.replaceWith(text);
                    }
                } else {
                    if (!$children.is(this.highlightSelectors.join(', '))) {
                        this.highlightElement({
                            $el: $children
                        });
                    }
                }
            }, this);
        },

        /**
         * Render highlight switcher interface for changing visibility of notFoundItems
         */
        renderHighlightSwitcher: function() {
            if (this.highlightSwitcherContainer) {
                this.$el.find(this.highlightSwitcherContainer).append(this.highlightSwitcherTemplate());
                this.checkHighlightSwitcherState();
            }
        },

        /**
         * Toggle visibility of highlight switcher view
         *
         * @param {boolean} state
         */
        toggleHighlightSwitcher: function(state) {
            state = state ? state : this.isApplicableSwitcher();
            this.$el.find(this.highlightSwitcherElement).toggleClass('hide', !state);
        },

        /**
         * Check highlight switcher state and get value from localStorage
         */
        checkHighlightSwitcherState: function() {
            var switcherState = persistentStorage.getItem(this.highlightStateStorageKey);
            if (this.highlightStateStorageKey && switcherState) {
                this.showNotFoundItems = switcherState === 'true';
            }
            this.toggleHighlightSwitcherItems(!this.showNotFoundItems);
        },

        /**
         * Set highlight switcher state to localStorage
         *
         * @param {boolean} state
         */
        setHighlightSwitcherState: function(state) {
            if (this.highlightStateStorageKey) {
                persistentStorage.setItem(this.highlightStateStorageKey, state || !this.showNotFoundItems);
            }
        },

        /**
         * Change highlight switcher state
         *
         * @param {boolean} state
         */
        changeHighlightSwitcherState: function(state) {
            state = _.isBoolean(state) ? state : this.showNotFoundItems;
            this.setHighlightSwitcherState();
            this.toggleHighlightSwitcherItems(state);
            this.showNotFoundItems = !state;
            this.update(this.text);
        },

        /**
         * Toggle visibility of highlight switcher items
         *
         * @param {boolean} state
         */
        toggleHighlightSwitcherItems: function(state) {
            this.$el.find(this.highlightSwitcherElement).toggleClass('highlighted-only', !state);
        },

        /**
         * Check if given element is field
         *
         * @param {jQuery} $element
         */
        _isFieldChoice: function($element) {
            var $child;
            var isFieldChoice = this._isField($element) && $element.is('select');
            if (!isFieldChoice) {
                $child = $element.children('select');
                if ($child.length) {
                    return true;
                }
            }

            return isFieldChoice;
        },

        /**
         * Check if given element is field
         *
         * @param {jQuery} $element
         */
        _isField: function($element) {
            var elementName = $element.data('name');
            var fieldName = 'field__value';

            return elementName === fieldName;
        },

        /**
         * Escaping special characters for regexp expression
         *
         * @param str
         * @private
         */
        _escape: function(str) {
            return str.replace(/[-[\]{}()*+?.,\\^$|#]/g, '\\$&');
        }
    });

    return HighlightTextView;
});
