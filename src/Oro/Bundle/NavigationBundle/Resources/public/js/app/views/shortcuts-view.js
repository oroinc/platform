define(function(require) {
    'use strict';

    var ShortcutsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');
    var routing = require('routing');
    require('bootstrap');

    ShortcutsView = BaseView.extend({
        autoRender: true,

        events: {
            'change': 'onChange',
            'focus [data-role="shortcut-search"]': 'onFocus',
            'hide.bs.dropdown': 'onDropdownHide',
            'click .nav-content': 'stopPropagation'
        },

        data: {},

        cache: {},

        sourceUrl: null,
        entityClass: '',
        entityId: 0,

        /**
         * @inheritDoc
         */
        constructor: function ShortcutsView() {
            ShortcutsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = options || {};

            var $input = this.getTypeaheadInput();
            this.sourceUrl = $input.data('source-url') ? $input.data('source-url') : null;
            this.entityClass = $input.data('entity-class') ? $input.data('entity-class') : null;
            this.entityId = $input.data('entity-id') ? $input.data('entity-id') : null;
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var $input = this.getTypeaheadInput();

            if (!$input.data('typeahead')) {
                this.initTypeahead();
            }

            this.initLayout();

            return this;
        },

        getTypeaheadInput: function() {
            return this.$('[data-role="shortcut-search"]');
        },

        initTypeahead: function() {
            var self = this;
            this.getTypeaheadInput().typeahead({
                source: _.bind(this.source, this),
                matcher: function(item) {
                    return item.key.toLowerCase().indexOf(this.query.toLowerCase()) !== -1;
                },
                sorter: function(items) {
                    var beginswith = [];
                    var caseSensitive = [];
                    var caseInsensitive = [];
                    var item;

                    while ((item = items.shift()) !== undefined) {
                        if (item.key.toLowerCase().indexOf(this.query.toLowerCase()) === 0) {
                            beginswith.push(item);
                        } else if (item.key.indexOf(this.query) !== -1) {
                            caseSensitive.push(item);
                        } else {
                            caseInsensitive.push(item);
                        }
                    }

                    return beginswith.concat(caseSensitive, caseInsensitive);
                },
                render: function(items) {
                    var that = this;
                    items = $(items).map(function(i, item) {
                        var view;

                        if (item.item.dialog) {
                            var config = item.item.dialog_config;
                            var options = {
                                'type': config.widget.type,
                                'multiple': config.widget.multiple,
                                'refresh-widget-alias': config.widget.refreshWidgetAlias,
                                'reload-grid-name': config.widget.reloadGridName,
                                'options': {
                                    alias: config.widget.options.alias,
                                    dialogOptions: {
                                        title: __(config.widget.options.dialogOptions.title),
                                        allowMaximize: config.widget.options.dialogOptions.allowMaximize,
                                        allowMinimize: config.widget.options.dialogOptions.allowMinimize,
                                        dblclick: config.widget.options.dialogOptions.dblclick,
                                        maximizedHeightDecreaseBy:
                                            config.widget.options.dialogOptions.maximizedHeightDecreaseBy,
                                        width: config.widget.options.dialogOptions.width
                                    }
                                },
                                'createOnEvent': 'click'
                            };
                            var dataUrl = routing.generate(config.dataUrl, {
                                entityClass: self.entityClass,
                                entityId: self.entityId
                            });

                            view = $(that.options.item).attr('data-value', item.key).data('isDialog', item.item.dialog);
                            view.find('a')
                                .attr('href', '#')
                                .attr('class', config.aCss)
                                .attr('data-url', dataUrl)
                                .attr('title', __(config.label))
                                .attr('data-page-component-module', 'oroui/js/app/components/widget-component')
                                .attr('data-page-component-options', JSON.stringify(options))
                                .html(that.highlighter(item.key));

                            if (config.iCss) {
                                view.prepend('<i class="' + config.iCss + ' hide-text">' + item.key + '</i>');
                            }
                        } else {
                            view = $(that.options.item).attr('data-value', item.key);
                            view.find('a').html(that.highlighter(item.key));
                        }

                        return view[0];
                    });

                    items.first().addClass('active');
                    this.$menu.html(items);
                    return this;
                },
                click: function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    if (!this.$menu.find('.active').data('isDialog')) {
                        this.select();
                        this.$element.focus();
                    }
                }
            });
        },

        source: function(query, process) {
            var self = this;
            if (_.isArray(this.sourceUrl)) {
                process(this.sourceUrl);
                this.render();
            } else if (!_.isUndefined(this.cache[query])) {
                process(this.cache[query]);
                this.render();
            } else {
                var url = routing.generate(this.sourceUrl, {query: query});
                $.get(url, _.bind(function(data) {
                    this.data = data;
                    var result = [];
                    _.each(data, function(item, key) {
                        result.push({
                            key: key,
                            item: item
                        });
                    });
                    this.cache[query] = result;
                    process(result);
                    self.render();
                }, this));
            }
        },

        onChange: function() {
            var $input = this.getTypeaheadInput();
            var key = $input.val();
            var dataItem = this.data[key];

            if (dataItem !== void 0) {
                $input.val('').inputWidget('refresh');

                if (!dataItem.dialog) {
                    $input.closest('form').attr('action', dataItem.url).submit();
                } else {
                    $input.parent().find('li.active > a').click();
                }
            }
        },

        onFocus: function() {
            this.getTypeaheadInput().typeahead('lookup');
        },

        onDropdownHide: function() {
            this.getTypeaheadInput().val('').inputWidget('refresh');
        },

        stopPropagation: function(e) {
            e.stopPropagation();
        }
    });

    return ShortcutsView;
});
