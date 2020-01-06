define(function(require) {
    'use strict';

    var TabsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var config = require('module').config();

    config = _.extend({
        useDropdown: true,
        dropdownText: _.__('oro.ui.tab_view_more')
    }, config);

    TabsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            useDropdown: config.useDropdown,
            elements: {
                tabsContainer: ['el', 'ul:first'],
                tabs: ['tabsContainer', 'li.tab, li.nav-item:not("[data-dropdown]")'],
                dropdown: ['tabsContainer', 'li[data-dropdown]'],
                dropdownMenu: ['dropdown', 'ul.dropdown-menu'],
                dropdownToggle: ['dropdown', 'a.dropdown-toggle'],
                dropdownToggleLabel: ['dropdownToggle', '[data-dropdown-label]'],
                visibleTabs: ['tabsContainer', '>li.tab, >li.nav-item:not("[data-dropdown]")'],
                hiddenTabs: ['dropdownMenu', '>li:not("[data-helper-element]")']
            },
            tabClass: 'nav-item',
            tabLinkClass: 'nav-link',
            dropdownItemClass: '',
            dropdownItemLinkClass: 'dropdown-item',
            dropdownTemplate: require('tpl!oroui/templates/dropdown-control.html'),
            dropdownText: config.dropdownText
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {Object}
         * @private
         */
        $elements: null,

        /**
         * @property {Number}
         */
        dropdownContainerWidth: 0,

        /**
         * @inheritDoc
         */
        constructor: function TabsComponent() {
            TabsComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            TabsComponent.__super__.initialize.apply(this, arguments);

            this.options = $.extend(true, {}, this.options, options || {});
            this.$el = options._sourceElement;

            if (this.options.useDropdown) {
                this.$el
                    .find(this.options.elements.tabsContainer[1])
                    .append(this.options.dropdownTemplate({
                        label: this.options.dropdownText
                    }));
            }

            this.initElements();

            if (this.options.useDropdown) {
                this.dropdownInit();
            }
        },

        initElements: function() {
            this.$elements = {
                el: this.$el
            };
            _.each(this.options.elements, function(element, name) {
                if (_.isArray(element)) {
                    this.$elements[name] = this.$elements[element[0]].find(element[1]);
                } else {
                    this.$elements[name] = $(element);
                }
            }.bind(this));
        },

        /**
         * @param {String} name
         * @returns {jQuery}
         */
        getElement: function(name) {
            return this.$elements[name];
        },

        /**
         * @param {String} name
         * @returns {Boolean}
         */
        hasElement: function(name) {
            return this.$elements && name in this.$elements;
        },

        dropdownInit: function() {
            this.getElement('dropdownToggleLabel').data(
                'dropdownDefaultLabel',
                this.getElement('dropdownToggleLabel').html()
            );
            this.dropdownInitTabs();

            this.dropdownUpdate();
            this.getElement('tabsContainer').addClass('responsive-tabs');
            this.toggleDropdown();

            mediator.on('layout:reposition', _.debounce(this.dropdownUpdate.bind(this), 50));
        },

        dropdownInitTabs: function() {
            this.saveDropdownOuterWidth();
            this.getElement('tabs').each(function(index, tab) {
                $(tab).on('shown.bs.tab', function(e) {
                    // fix bug, 'active' class doesn't removed from dropdown tabs
                    this.getElement('tabsContainer').find('a').not(e.target).removeClass('active');
                    this.dropdownUpdateLabel();
                    this.getElement('dropdown').trigger('tohide.bs.dropdown');
                }.bind(this));
            }.bind(this));

            $(document).on('shown.bs.collapse', this.updateStateOfHiddenTabs.bind(this));
        },

        updateStateOfHiddenTabs: function() {
            // Once update width of tabs if they were hide
            if (this.hasElement('tabs') && this.getElement('tabs').data('dropdownOuterWidth') <= 0) {
                this.saveDropdownOuterWidth();
                this.dropdownUpdate();
            }
        },

        saveDropdownOuterWidth: function() {
            this.getElement('tabs').each(function(index, tab) {
                var $tab = $(tab);

                $tab.data('dropdownOuterWidth', $tab.find(':first-child').outerWidth(true));
            });
        },

        dropdownUpdate: function() {
            if (this.disposed) {
                return;
            }
            var self = this;
            var $tabsContainer = this.getElement('tabsContainer');
            var dropdownContainerWidth = $tabsContainer.width();
            if (!$tabsContainer.is(':visible') || this.dropdownContainerWidth === dropdownContainerWidth) {
                return;
            }
            this.dropdownContainerWidth = dropdownContainerWidth;

            var visibleWidth = this.dropdownVisibleWidth();
            var dropdownWidth = this.getElement('dropdown').outerWidth(true);
            var updated = false;

            if (
                dropdownContainerWidth < visibleWidth ||
                (this.getElement('hiddenTabs').length > 0 && dropdownContainerWidth < visibleWidth + dropdownWidth)
            ) {
                dropdownContainerWidth -= dropdownWidth;

                $.each(this.getElement('visibleTabs').get().reverse(), function() {
                    var $tab = $(this);
                    visibleWidth -= $tab.data('dropdownOuterWidth');
                    $tab.prependTo(self.getElement('dropdownMenu'));

                    self.turnToDropdownItem($tab);

                    updated = true;
                    if (dropdownContainerWidth >= visibleWidth) {
                        return false;
                    }
                });
            } else {
                var showAll = false;
                if (dropdownContainerWidth >= visibleWidth + this.dropdownHiddenWidth()) {
                    showAll = true;
                } else {
                    dropdownContainerWidth -= dropdownWidth;
                }

                this.getElement('hiddenTabs').each(function(i) {
                    var $tab = $(this);
                    if (!showAll) {
                        visibleWidth += $tab.data('dropdownOuterWidth');
                        if (dropdownContainerWidth < visibleWidth || i === 0 && $tab.hasClass('active')) {
                            return false;
                        }
                    }
                    $tab.insertBefore(self.getElement('dropdown'));

                    self.turnToNavItem($tab);
                    updated = true;
                });
            }

            if (updated) {
                this.initElements();
                this.toggleDropdown();
                this.dropdownUpdateLabel();
            }
        },

        toggleDropdown: function() {
            if (this.getElement('hiddenTabs').length) {
                this.getElement('dropdown').show();
            } else {
                this.getElement('dropdown').hide();
            }
        },

        turnToDropdownItem: function($item) {
            $item.removeClass(this.options.tabClass)
                .addClass(this.options.dropdownItemClass)
                .find('> a')
                .removeClass(this.options.tabLinkClass)
                .addClass(this.options.dropdownItemLinkClass);
        },

        turnToNavItem: function($item) {
            $item.removeClass(this.options.dropdownItemClass)
                .addClass(this.options.tabClass)
                .find('> a')
                .removeClass(this.options.dropdownItemLinkClass)
                .addClass(this.options.tabLinkClass);
        },

        dropdownUpdateLabel: function() {
            var $dropdownToggleLabel = this.getElement('dropdownToggleLabel');
            var hiddenActive = this.getElement('hiddenTabs').find('a').filter('.active');
            var defaultLabel = $dropdownToggleLabel.data('dropdownDefaultLabel');
            var currentLabel = $dropdownToggleLabel.html();
            var neededLabel = hiddenActive.length > 0 ? hiddenActive.html() : defaultLabel;

            if (currentLabel !== neededLabel) {
                $dropdownToggleLabel.html(neededLabel);

                if (neededLabel !== defaultLabel) {
                    $dropdownToggleLabel.closest('a').addClass('active');
                } else {
                    $dropdownToggleLabel.closest('a').removeClass('active');
                }

                this.dropdownContainerWidth = 0;
                this.dropdownUpdate();
            }
        },

        /**
         * @returns {Number}
         */
        dropdownVisibleWidth: function() {
            var width = 0;
            this.getElement('visibleTabs').each(function() {
                width += $(this).data('dropdownOuterWidth');
            });
            return width;
        },

        /**
         * @returns {Number}
         */
        dropdownHiddenWidth: function() {
            var width = 0;
            this.getElement('hiddenTabs').each(function() {
                width += $(this).data('dropdownOuterWidth');
            });
            return width;
        },

        /**
         * Disposes the component
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);
            $(document).off('shown.bs.collapse', this.updateStateOfHiddenTabs);
            TabsComponent.__super__.dispose.apply(this, arguments);
        }
    });

    return TabsComponent;
});
