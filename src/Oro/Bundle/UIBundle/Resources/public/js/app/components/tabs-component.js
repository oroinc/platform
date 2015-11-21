define(function(require) {
    'use strict';

    var TabsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');

    TabsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            useDropdown: false,
            elements: {
                tabsContainer: ['el', 'ul:first'],
                tabs: ['tabsContainer', 'li.tab'],
                dropdown: ['tabsContainer', 'li.dropdown'],
                dropdownMenu: ['dropdown', 'ul.dropdown-menu'],
                dropdownToggle: ['dropdown', 'a.dropdown-toggle'],
                dropdownToggleLabel: ['dropdownToggle', 'span'],
                visibleTabs: ['tabsContainer', '>li.tab'],
                hiddenTabs: ['dropdownMenu', '>li.tab']
            }
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
        initialize: function(options) {
            TabsComponent.__super__.initialize.apply(this, arguments);

            this.options = $.extend(true, {}, this.options, options || {});
            this.$el = options._sourceElement;

            this.initElements();

            if (this.options.useDropdown) {
                this.dropdownInit();
            }
        },

        initElements: function() {
            var self = this;
            this.$elements = {
                el: this.$el
            };
            _.each(this.options.elements, function(element, name) {
                if (_.isArray(element)) {
                    self.$elements[name] = self.$elements[element[0]].find(element[1]);
                } else {
                    self.$elements[name] = $(element);
                }
            });
        },

        /**
         * @param {String} name
         * @returns {jQuery}
         */
        getElement: function(name) {
            return this.$elements[name];
        },

        dropdownInit: function() {
            this.getElement('dropdownToggleLabel').data(
                'dropdownDefaultLabel',
                this.getElement('dropdownToggleLabel').html()
            );
            this.dropdownInitTabs();

            this.dropdownUpdate();
            this.getElement('tabsContainer').css('overflow', 'visible');

            mediator.on('layout:reposition', this.dropdownUpdate, this);
        },

        dropdownInitTabs: function() {
            var self = this;
            this.getElement('tabs').each(function() {
                var $tab = $(this);
                $tab.data('dropdownOuterWidth', $tab.outerWidth(true));

                $tab.on('shown.bs.tab', function(e) {
                    //fix bug, 'active' class doesn't removed from dropdown tabs
                    $(e.relatedTarget).closest('li').removeClass('active');
                    self.dropdownUpdateLabel();
                });
            });
        },

        dropdownUpdate: function() {
            var self = this;
            var dropdownContainerWidth = this.getElement('tabsContainer').width();
            if (this.dropdownContainerWidth === dropdownContainerWidth) {
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
                    updated = true;
                });
            }

            if (updated) {
                this.initElements();
                if (this.getElement('hiddenTabs').length > 0) {
                    this.getElement('dropdown').show();
                } else {
                    this.getElement('dropdown').hide();
                }
                this.dropdownUpdateLabel();
            }
        },

        dropdownUpdateLabel: function() {
            var hiddenActive = this.getElement('hiddenTabs').filter('.active');
            var currentLabel = this.getElement('dropdownToggleLabel').html();
            var neededLabel = this.getElement('dropdownToggleLabel').data('dropdownDefaultLabel');
            if (hiddenActive.length > 0) {
                neededLabel = hiddenActive.find('a').html();
            }

            if (currentLabel !== neededLabel) {
                this.getElement('dropdownToggleLabel').html(neededLabel);
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
        }
    });

    return TabsComponent;
});
