define(function(require) {
    'use strict';

    var SideMenuOverlayView;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('tpl!oroui/templates/side-menu-overlay.html');

    var ESCAPE_KEY_CODE = 27;

    SideMenuOverlayView = BaseView.extend({
        /**
         * @inheritDoc
         */
        className: 'side-menu-overlay',

        /**
         * @inheritDoc
         */
        template: template,

        /**
         * @inheritDoc
         */
        events: {
            'click [data-role="overlay-close"]': 'close',
            'click [data-role="clear-search"]': 'clearSearch',
            'keyup [data-role="search"]': 'onSearch'
        },

        /**
         * @inheritDoc
         */
        listen: {
            'page:beforeChange mediator': 'onBeforeChange',
            'leave-focus': 'setFocus'
        },

        isOpen: false,

        searchContent: null,

        timeout: 100,

        /**
         * @inheritDoc
         */
        constructor: function SideMenuOverlayView() {
            this.onSearch = _.debounce(this.onSearch, this.timeout);
            SideMenuOverlayView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            SideMenuOverlayView.__super__.render.call(this);
        },

        /**
         * @inheritDoc
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
         * @inheritDoc
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

            var $menuItem = $('<li/>', {
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

            var $title = this.$('[data-role="overlay-title"]');
            var $last = this.searchContent.filter(':visible').last();

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
            this.isOpen = true;
            this.$el.addClass('open');
            this.clearSearch();
            this.setFocus();
            this.setTitleWidth();
        },

        /**
         *  Action for close
         */
        close: function() {
            this.isOpen = false;
            this.$el.removeClass('open');
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

            var value = $(event.target).val();

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

        clearSearchContent: function() {
            $.each(this.searchContent, function() {
                var $this = $(this);
                var $title = $this.find('.title');

                $title.html($this.data('original-text'));
                $this.show();
            });
        },

        /**
         * @param {String} value
         */
        search: function(value) {
            var regex = tools.safeRegExp(value, 'ig');
            var highlight = '<span class="highlight">$&</span>';
            var testValue = function(string) {
                return regex.test(string);
            };

            this.searchContent.hide();

            $.each(this.searchContent, function() {
                var $this = $(this);
                var $title = $this.find('.title');

                if (testValue($this.text().trim())) {
                    $title.html(
                        $this.data('original-text').replace(regex, highlight)
                    );

                    $this.show();

                    var groups = $this.data('related-groups');
                    if (groups) {
                        groups = groups.split(';');

                        $.each(groups, function(index, group) {
                            $this.prevAll('[data-index="'+ group +'"]').show();
                        });
                    }
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
         * @inheritDoc
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
