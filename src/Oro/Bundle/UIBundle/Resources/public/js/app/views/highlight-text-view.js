define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const Popover = require('bootstrap-popover');
    const FuzzySearch = require('oroui/js/fuzzy-search');
    const persistentStorage = require('oroui/js/persistent-storage');
    const highlightSwitcherTemplate = require('tpl-loader!oroui/templates/highlight-switcher.html');
    const inputWidgetManager = require('oroui/js/input-widget-manager');

    const HighlightTextView = BaseView.extend({
        /**
         * @inheritdoc
         */
        optionNames: BaseView.prototype.optionNames.concat([
            'text', 'toggleSelectors', 'viewGroup', 'notFoundClass',
            'elementHighlightClass', 'foundClass', 'fuzzySearch',
            'highlightClass', 'highlightSelectors', 'highlightStateStorageKey',
            'highlightSwitcherContainer', 'highlightSwitcherElement',
            'highlightSwitcherTemplate', 'showNotFoundItems'
        ]),

        events: {
            'click [data-role="highlight-switcher"]': 'changeHighlightSwitcherState',
            'change .select2-offscreen': 'onSelectChange',
            'select2-init .select2-offscreen[data-name]': 'onSelectChange',
            'change .input-widget-select select': 'onSelectChange'
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
        alwaysDisplaySelector: '.validation-error',

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
         * @inheritdoc
         */
        constructor: function HighlightTextView(options) {
            HighlightTextView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.findHighlightClass = '.' + this.highlightClass;
            this.findElementHighlightClass = '.' + this.elementHighlightClass;
            this.findNotFoundClass = '.' + this.notFoundClass;
            this.findFoundClass = '.' + this.foundClass;
            this.replaceBy = '<mark class="' + this.highlightClass + '">$1</mark>';
            this.combinedHighlightSelectors = this.highlightSelectors.join(', ');

            HighlightTextView.__super__.initialize.call(this, options);

            this.renderHighlightSwitcher();
            this.update(this.text);

            mediator.on(this.viewGroup + ':highlight-text:update', this.update, this);
        },

        /**
         * @inheritdoc
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
            let regexp = this.text;

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
            const $groupEl = $(groupEl);
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
            const elements = [];
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
            const $el = element.$el;
            if (!$el.is(':visible')) {
                return;
            }

            if (this.isElementHighlighted($el) || $el.find(this.alwaysDisplaySelector).length > 0) {
                $el.addClass(this.foundClass);
                return;
            }

            const $parent = $el.closest(this.toggleSelectors[element.selector]);
            if (this.isElementHighlighted($parent) && !this.showNotFoundItems) {
                $el.addClass(this.notFoundClass);
            }
        },

        /**
         * Check visible highlighted elements exists in given element
         *
         * @param {jQuery} $el
         * @return {boolean}
         */
        isElementHighlighted: function($el) {
            let $highlighted = $el.find(this.findElementHighlightClass);
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
            let $highlighted = $el.find(this.findHighlightClass);
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
            const foundHighlight = this.$el.find(this.findHighlightClass);
            const foundSiblings = this.$el.find(this.findFoundClass).siblings().not(this.findHighlightClass);
            return foundHighlight.length && foundSiblings.length;
        },

        /**
         * Remove highlight from all elements
         */
        clear: function() {
            this.unhighlightElementContent(this.$el);

            _.each(this.$el.find(this.findElementHighlightClass), function(element) {
                const $el = $(element);
                const popover = $el.data(Popover.DATA_KEY);

                $el.removeClass(this.elementHighlightClass);

                if (popover !== void 0) {
                    const $content = $('<div/>').html(popover.getContent());
                    this.unhighlightElementContent($content);
                    popover.updateContent($content.html());
                }
            }, this);

            this.$el.find(this.findNotFoundClass).removeClass(this.notFoundClass);
            this.$el.find(this.findFoundClass).removeClass(this.foundClass);
        },

        /**
         * Highlight text in given element
         *
         * @param {Object} element
         */
        highlightElement: function(element) {
            let result = false;
            let $content;
            const $el = element.$el;
            let $highlightTarget = $el;
            let popover;

            if ($el.attr('data-toggle') === 'popover' && (popover = $el.data(Popover.DATA_KEY)) !== void 0) {
                $content = $('<div/>').html(popover.getContent());
                result = this.highlightElementContent($content);
                popover.updateContent($content.html());
            } else if (this._isField($el) && !this._isFieldChoice($el)) {
                result = this.textContainsSearchTerm($el.val());
            } else if (this._isSelect2($el)) {
                $highlightTarget = $el.parent();

                if (this._isSelect2Multi($el)) {
                    $content = $el.siblings('.select2-container').find('.select2-choices');
                } else {
                    $content = $el.siblings('.select2-container').find('.select2-choice:not(.select2-default)')
                        .find('.select2-chosen');
                }

                result = this.highlightElementContent($content) || this.select2ContainsSearchText($el);
            } else if (this._isFieldChoice($el) && !this._isMultiselect($el)) {
                result = this.highlightElementContent($('option:selected', $el));

                $highlightTarget = $el.parent();

                if (inputWidgetManager.hasWidget($el)) {
                    $el.inputWidget('refresh');
                }
            } else {
                result = this.highlightElementContent($el);

                if (!this._isField($el)) {
                    $el[0].normalize();
                }
            }

            $highlightTarget.toggleClass(this.elementHighlightClass, result);

            return result;
        },

        /**
         * Highlight text in given content
         *
         * @param {jQuery} $content
         */
        highlightElementContent: function($content) {
            let result = false;

            _.each($content.contents(), function(children) {
                const $children = $(children);
                if (children.nodeType === Node.TEXT_NODE) {
                    let text = children.textContent;
                    if (this.textContainsSearchTerm(text)) {
                        result = true;
                        text = _.escape(text.replace(this.findText, '[mark]$&[/mark]'))
                            .replace(/\[mark\](.*?)\[\/mark\]/gi, this.replaceBy);

                        $children.replaceWith(text);
                    }
                } else {
                    if (
                        children.nodeType === Node.ELEMENT_NODE &&
                        !$children.is(this.combinedHighlightSelectors)
                    ) {
                        result = this.highlightElement({
                            $el: $children
                        }) || result;
                    }
                }
            }, this);

            return result;
        },

        /**
         * Unhighlight text in given content
         *
         * @param {jQuery} $content
         */
        unhighlightElementContent: function($content) {
            $content.find(this.findHighlightClass).each(function(index, el) {
                const $el = $(el);
                const parent = el.parentNode;

                $el.contents().unwrap();

                if (parent) {
                    parent.normalize();
                }
            });
        },

        /**
         * Checks if string is matched search term
         *
         * @param {string} text
         * @return {boolean}
         */
        textContainsSearchTerm: function(text) {
            if (!this.findText || this.fuzzySearch && !FuzzySearch.isMatched(_.trim(text), this.text)) {
                return false;
            }

            return text.match(this.findText) !== null;
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
            const switcherState = persistentStorage.getItem(this.highlightStateStorageKey);
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
         * Check if given element is selectable field
         *
         * @param {jQuery} $element
         */
        _isFieldChoice: function($element) {
            let $child;
            const isFieldChoice = this._isField($element) && $element.is('select');
            if (!isFieldChoice) {
                $child = $element.find('select');
                if ($child.length) {
                    return true;
                }
            }

            return isFieldChoice;
        },

        /**
         * Check if given element is multiselect field
         *
         * @param {jQuery} $element
         */
        _isMultiselect: function($element) {
            return this._isField($element) && $element.is('select') && $element[0].hasAttribute('multiple');
        },

        /**
         * Check if given element is field
         *
         * @param {jQuery} $element
         */
        _isField: function($element) {
            const elementName = $element[0].getAttribute('data-name');
            const fieldName = 'field__value';

            return elementName === fieldName;
        },

        /**
         * Check if given element is Select2
         *
         * @param {jQuery} $element
         */
        _isSelect2: function($element) {
            return $element.is('.select2-offscreen[data-name]');
        },

        /**
         * Check if given element is Select2 multiselect
         *
         * @param {jQuery} $element
         */
        _isSelect2Multi: function($element) {
            return this._isSelect2($element) && $element[0].hasAttribute('multiple');
        },

        onSelectChange: function(e) {
            this.highlightElement({$el: $(e.currentTarget)});
        },

        /**
         * Checks if Select2 data or options contain search text
         *
         * @param {jQuery} $el
         * @return {boolean}
         */
        select2ContainsSearchText: function($el) {
            let result = false;

            if (this.findText) {
                if ($el.is('select')) {
                    $el.children('option').each(function(i, option) {
                        result = this.findText.test($(option).text());

                        return !result;
                    }.bind(this));
                } else {
                    const initializeOptions = _.result($el.data('inputWidget'), 'initializeOptions');

                    if (_.isArray(initializeOptions.data)) {
                        result = _.some(initializeOptions.data, function(item) {
                            const text = _.isString(item) ? item : item.text;

                            return this.findText.test(text);
                        }, this);
                    }
                }
            }

            return result;
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
