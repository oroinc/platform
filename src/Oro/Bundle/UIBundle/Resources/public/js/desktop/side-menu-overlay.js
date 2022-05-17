define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!oroui/templates/side-menu-overlay.html');

    const ESCAPE_KEY_CODE = 27;

    const SideMenuOverlayView = BaseView.extend({
        /**
         * @inheritdoc
         */
        className: 'side-menu-overlay',

        /**
         * @inheritdoc
         */
        template: template,

        /**
         * @inheritdoc
         */
        events: {
            'click [data-role="overlay-close"]': 'close',
            'click [data-role="clear-search"]': 'clearSearch',
            'keyup [data-role="search"]': 'onSearch'
        },

        /**
         * @inheritdoc
         */
        listen: {
            'page:beforeChange mediator': 'onBeforeChange',
            'leave-focus': 'setFocus'
        },

        isOpen: false,

        searchContent: null,

        timeout: 100,

        /**
         * @inheritdoc
         */
        constructor: function SideMenuOverlayView(options) {
            this.onSearch = _.debounce(this.onSearch, this.timeout);
            SideMenuOverlayView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            SideMenuOverlayView.__super__.render.call(this);
        },

        /**
         * @inheritdoc
         */
        delegateEvents: function(events) {
            SideMenuOverlayView.__super__.delegateEvents.call(this, events);
            $(document).on('keyup' + this.eventNamespace(), function(event) {
                if (!this.isOpen) {
                    return;
                }

                if (event.keyCode === ESCAPE_KEY_CODE && $(event.target).data('role') !== 'search') {
                    this.close();
                }
            }.bind(this));

            $(window).on('resize' + this.eventNamespace(), _.debounce(function() {
                if (this.isOpen) {
                    this.setTitleWidth();
                }
            }.bind(this), this.timeout));

            return this;
        },

        /**
         * @inheritdoc
         */
        undelegateEvents: function() {
            SideMenuOverlayView.__super__.undelegateEvents.call(this);
            $(document).off(this.eventNamespace());
            $(window).off(this.eventNamespace());
            return this;
        },

        /**
         * @param $menu
         * @returns {SideMenuOverlayView}
         */
        updateContent: function($menu) {
            this.searchContent = $menu.children().filter(':not(.divider)');

            const $menuItem = $('<li/>', {
                'class': 'menu-item ui-helper'
            }).append(this.$('[data-role="overlay-design-helper"]'));

            $menu.append($menuItem);

            this.$('[data-role="overlay-content"]').html($menu);

            this.toggleNoResult();
            return this;
        },

        setFocus: function() {
            if (this.isOpen) {
                this.$('[data-role="search"]').focus();
            }
        },

        /**
         * @param {String} title
         */
        setTitle: function(title) {
            this.$('[data-role="overlay-title"]').text(title).attr('title', title);

            return this;
        },

        /**
         * @param {Boolean|Undefined} [undoComputedWidth]
         */
        setTitleWidth: function(undoComputedWidth) {
            if (!this.searchContent) {
                return;
            }

            const $title = this.$('[data-role="overlay-title"]');
            const $last = this.searchContent.filter(':visible').last();

            if (undoComputedWidth || $last.length === 0 || $last.position().left === 0) {
                $title.css('width', '');
            } else {
                $title.width(
                    _.isRTL()
                        ? $title.position().left + $title.width() - $last.position().left
                        : $last.position().left + $last.width() - ($title.position().left / 2)
                );
            }
        },

        /**
         * Action fot open
         */
        open: function() {
            if (!this.isOpen) {
                this.isOpen = true;
                this.$el.addClass('open');
                this.trigger('open');
            }

            this.clearSearch();
            this.setFocus();
            this.setTitleWidth();
        },

        /**
         *  Action for close
         */
        close: function() {
            if (this.isOpen) {
                this.isOpen = false;
                this.$el.removeClass('open');
                this.trigger('close');
            }

            this.$('[data-role="search"]').trigger('blur');
            this.setTitleWidth(true);
        },

        /**
         *  Action on before page change
         */
        onBeforeChange: function() {
            if (this.isOpen) {
                this.close();
            }
        },

        /**
         * Action on search items
         * @param event
         */
        onSearch: function(event) {
            if (this.disposed) {
                return;
            }

            const value = $(event.target).val();

            if (event.keyCode === ESCAPE_KEY_CODE) {
                if (value.length !== 0) {
                    this.clearSearch();
                    event.stopPropagation();
                } else {
                    this.close();
                }
            }

            this.toggleClearButton(value.length);

            if (value.length) {
                this.search(value);
            } else {
                this.clearSearchContent();
            }

            this.toggleNoResult();
            this.setTitleWidth();
        },

        clearSearch: function() {
            this.$('[data-role="search"]').val('').trigger('keyup');
            this.toggleClearButton(false);
        },

        updateSearchContent: function($element, modifyContentFn) {
            const $title = $element.find('.title');
            const $icon = $title.find('.menu-icon').clone();
            let content = $element.data('original-text');

            if (typeof modifyContentFn === 'function') {
                content = modifyContentFn(content);
            }

            if ($icon.length) {
                content = $icon[0].outerHTML + content;
            }

            $title.html(content);

            return this;
        },

        clearSearchContent: function() {
            $.each(this.searchContent, (i, el) => {
                const $el = $(el);

                this.updateSearchContent($el);
                $el.show();
            });
        },

        /**
         * @param {String} value
         */
        search: function(value) {
            value = value.trim();

            const regex = tools.safeRegExp(value, 'ig');
            const testValue = string => regex.test(string);

            this.searchContent.hide();
            this.searchContent.each((i, el) => {
                const $el = $(el);

                if (testValue($el.text().trim())) {
                    const highlightContent = content => {
                        const highlight = '<span class="highlight">$&</span>';

                        return content.replace(regex, highlight);
                    };

                    this.updateSearchContent($el, highlightContent);
                    $el.show();

                    // Show all headings for a group which include current element
                    let groups = $el.data('related-groups');
                    if (groups) {
                        groups = groups.split(';');

                        $.each(groups, function(index, group) {
                            $el.prevAll('[data-index="' + group + '"]').show();
                        });
                    }
                }
            });
            // Visible items with heading
            this.searchContent.filter((i, el) => {
                return $(el).is(':visible') && $(el).find('.unclickable').length > 0;
            }).each((i, el) => {
                const $groupEls = $(el).nextAll(`[data-related-groups*="${$(el).data('index')};"]`);
                const $allHidden = $groupEls.filter((i, el) => $(el).is(':hidden'));

                // Show all related elements for group if all of them are hidden
                if ($groupEls.length === $allHidden.length) {
                    $groupEls.each((i, groupEl) => {
                        // Eliminate outdated highlight
                        this.updateSearchContent($(groupEl));
                        $(groupEl).show();
                    });
                }
            });
        },

        /**
         * @param {Boolean} hasValue
         */
        toggleClearButton: function(hasValue) {
            if (hasValue) {
                this.$('[data-role="clear-search"]').removeClass('hide');
                this.$('[data-role="search-icon"]').addClass('hide');
            } else {
                this.$('[data-role="clear-search"]').addClass('hide');
                this.$('[data-role="search-icon"]').removeClass('hide');
            }
        },

        /**
         *  Show or hide no results block
         */
        toggleNoResult: function() {
            this.$('[data-role="no-result"]')
                .toggleClass('hide', this.searchContent && this.searchContent.is(':visible'));
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.isOpen;

            SideMenuOverlayView.__super__.dispose.call(this);
        }
    });

    return SideMenuOverlayView;
});
