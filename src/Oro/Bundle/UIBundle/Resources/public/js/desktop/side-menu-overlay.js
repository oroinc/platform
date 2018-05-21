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
            'page:beforeChange mediator': 'onBeforeChange'
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
            $(document).on('keyup.side-menu-overlay-close' + this.eventNamespace(), function(event) {
                if (event.keyCode === ESCAPE_KEY_CODE) {
                    this.close();
                }
            }.bind(this));

            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            SideMenuOverlayView.__super__.undelegateEvents.call(this);
            $(document).off(this.eventNamespace());
            return this;
        },

        /**
         * @param $menu
         * @returns {SideMenuOverlayView}
         */
        updateContent: function($menu) {
            this.searchContent = $menu.children();

            var $menuItem = $('<li/>', {
                'class': $menu.children().last().attr('class')
            }).append(this.$('[data-role="overlay-design-helper"]'));

            $menu.append($menuItem);

            this.$('[data-role="overlay-content"]').html($menu);

            this.toggleNoResult();
            return this;
        },

        /**
         * @param title
         */
        setTitle: function(title) {
            this.$('[data-role="overlay-title"]').text(title);

            return this;
        },

        /**
         * Action fot open
         */
        open: function() {
            this.isOpen = true;
            this.$el.addClass('open');
            this.clearSearch();
            this.$('[data-role="search"]').trigger('focus');
        },

        /**
         *  Action for close
         */
        close: function() {
            this.isOpen = false;
            this.$el.removeClass('open');
            this.$('[data-role="search"]').trigger('blur');
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
            var value = $(event.target).val();

            event.stopPropagation();

            this.toggleClearButton(value.length);
            this.search(value);

            if (event.keyCode === ESCAPE_KEY_CODE) {
                value ? this.clearSearch() : this.close();
            }
        },

        clearSearch: function() {
            this.$('[data-role="search"]').val('').trigger('keyup');
            this.toggleClearButton(false);
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

                $title.html(
                    $this.data('original-text').replace(regex, highlight)
                );

                if (testValue($this.text().trim())) {
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

            this.toggleNoResult();
        },

        /**
         * @param {Boolean} hasValue
         */
        toggleClearButton: function(hasValue ) {
            this.$('[data-role="clear-search"]').toggleClass('hide', !hasValue);
            this.$('[data-role="search-icon"]').toggleClass('hide', hasValue);
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
            $(document).off('.side-menu-overlay-close');

            SideMenuOverlayView.__super__.dispose.call(this);
        }
    });

    return SideMenuOverlayView;
});
