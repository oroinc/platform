define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const mediator = require('oroui/js/mediator');
    let config = require('module-config').default(module.id);

    config = _.extend({
        useDropdown: true,
        dropdownText: _.__('oro.ui.tab_view_more')
    }, config);

    const TabsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            useDropdown: config.useDropdown,
            elements: {
                tabsContainer: ['el', 'ul:first'],
                tabs: ['tabsContainer', 'li.tab, li.nav-item:not("[data-dropdown], .pull-right")'],
                pullRightTabs: ['tabsContainer', 'li.nav-item.pull-right'],
                dropdown: ['tabsContainer', 'li[data-dropdown]'],
                dropdownMenu: ['dropdown', 'ul.dropdown-menu'],
                dropdownToggle: ['dropdown', 'a.dropdown-toggle'],
                dropdownToggleLabel: ['dropdownToggle', '[data-dropdown-label]'],
                visibleTabs: ['tabsContainer', '>li.tab, >li.nav-item:not("[data-dropdown], .pull-right")'],
                hiddenTabs: ['dropdownMenu', '>li:not("[data-helper-element]")']
            },
            tabClass: 'nav-item',
            tabLinkClass: 'nav-link',
            dropdownItemClass: '',
            dropdownItemLinkClass: 'dropdown-item',
            dropdownTemplate: require('tpl-loader!oroui/templates/dropdown-control.html'),
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
        tabsContainerWidth: 0,

        /**
         * @inheritdoc
         */
        constructor: function TabsComponent(options) {
            this.updateStateOfHiddenTabs = this.updateStateOfHiddenTabs.bind(this);
            TabsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            TabsComponent.__super__.initialize.call(this, options);

            this.options = $.extend(true, {}, this.options, options || {});
            this.$el = options._sourceElement;

            if (this.options.useDropdown) {
                const $firstPullRightTab = this.$el.find(`${this.options.elements.pullRightTabs[1]}:first`);
                if ($firstPullRightTab.length) {
                    $firstPullRightTab.before(this.options.dropdownTemplate({
                        label: this.options.dropdownText
                    }));
                } else {
                    this.$el
                        .find(this.options.elements.tabsContainer[1])
                        .append(this.options.dropdownTemplate({
                            label: this.options.dropdownText
                        }));
                }
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

            $(document).on('shown.bs.collapse', this.updateStateOfHiddenTabs);
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
                const $tab = $(tab);

                $tab.data('dropdownOuterWidth', $tab.find(':first-child').outerWidth(true));
            });
        },

        dropdownUpdate: function() {
            if (this.disposed) {
                return;
            }
            const self = this;
            const $tabsContainer = this.getElement('tabsContainer');
            let tabsContainerWidth = $tabsContainer.width();
            if (!$tabsContainer.is(':visible') || this.tabsContainerWidth === tabsContainerWidth) {
                return;
            }
            this.tabsContainerWidth = tabsContainerWidth;

            let visibleWidth = this.dropdownVisibleWidth();
            const dropdownWidth = this.getElement('dropdown').outerWidth(true);
            const pullRightTabsWidth = this.getElement('pullRightTabs').find(':first-child').outerWidth(true) || 0;
            let updated = false;

            if (
                tabsContainerWidth < visibleWidth + pullRightTabsWidth || (
                    this.getElement('hiddenTabs').length > 0 &&
                    tabsContainerWidth < visibleWidth + dropdownWidth + pullRightTabsWidth
                )
            ) {
                tabsContainerWidth -= dropdownWidth + pullRightTabsWidth;

                $.each(this.getElement('visibleTabs').get().reverse(), function() {
                    const $tab = $(this);
                    visibleWidth -= $tab.data('dropdownOuterWidth');
                    $tab.prependTo(self.getElement('dropdownMenu'));

                    self.turnToDropdownItem($tab);

                    updated = true;
                    if (tabsContainerWidth >= visibleWidth) {
                        return false;
                    }
                });
            } else {
                let showAll = false;
                if (tabsContainerWidth >= visibleWidth + this.dropdownHiddenWidth() + pullRightTabsWidth) {
                    showAll = true;
                } else {
                    tabsContainerWidth -= dropdownWidth + pullRightTabsWidth;
                }

                this.getElement('hiddenTabs').each(function(i) {
                    const $tab = $(this);
                    if (!showAll) {
                        visibleWidth += $tab.data('dropdownOuterWidth');
                        if (tabsContainerWidth < visibleWidth || i === 0 && $tab.hasClass('active')) {
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
            const $dropdownToggleLabel = this.getElement('dropdownToggleLabel');
            const hiddenActive = this.getElement('hiddenTabs').find('a').filter('.active');
            const defaultLabel = $dropdownToggleLabel.data('dropdownDefaultLabel');
            const currentLabel = $dropdownToggleLabel.html();
            const neededLabel = hiddenActive.length > 0 ? hiddenActive.html() : defaultLabel;
            const roleAttr = hiddenActive.length > 0 ? hiddenActive.data('role') : null;

            if (currentLabel !== neededLabel) {
                $dropdownToggleLabel.html(neededLabel);
                $dropdownToggleLabel.attr('data-role', roleAttr);

                if (neededLabel !== defaultLabel) {
                    $dropdownToggleLabel.closest('a').addClass('active');
                } else {
                    $dropdownToggleLabel.closest('a').removeClass('active');
                }

                this.tabsContainerWidth = 0;
                this.dropdownUpdate();
            }
        },

        /**
         * @returns {Number}
         */
        dropdownVisibleWidth: function() {
            let width = 0;
            this.getElement('visibleTabs').each(function() {
                width += $(this).data('dropdownOuterWidth');
            });
            return width;
        },

        /**
         * @returns {Number}
         */
        dropdownHiddenWidth: function() {
            let width = 0;
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
            TabsComponent.__super__.dispose.call(this);
        }
    });

    return TabsComponent;
});
