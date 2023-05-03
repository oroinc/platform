define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const routing = require('routing');
    const urlHelper = require('orodatagrid/js/url-helper');
    require('bootstrap');

    const ShortcutsView = BaseView.extend({
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
         * @inheritdoc
         */
        constructor: function ShortcutsView(options) {
            ShortcutsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = options || {};

            const $input = this.getTypeaheadInput();
            this.sourceUrl = $input.data('source-url') ? $input.data('source-url') : null;
            this.entityClass = $input.data('entity-class') ? $input.data('entity-class') : null;
            this.entityId = $input.data('entity-id') ? $input.data('entity-id') : null;
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const $input = this.getTypeaheadInput();

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
            const self = this;
            this.getTypeaheadInput().typeahead({
                source: this.source.bind(this),
                matcher: function(item) {
                    return item.key.toLowerCase().indexOf(this.query.toLowerCase()) !== -1;
                },
                sorter: function(items) {
                    const beginswith = [];
                    const caseSensitive = [];
                    const caseInsensitive = [];
                    let item;

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
                    items = $(items).map((i, item) => {
                        let view;
                        const secureItem = _.escape(item.key);

                        if (item.item.dialog) {
                            const config = item.item.dialog_config;
                            const options = {
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
                            const dataUrl = routing.generate(config.dataUrl, {
                                entityClass: self.entityClass,
                                entityId: self.entityId
                            });

                            view = $(this.options.item).attr('data-value', item.key).data('isDialog', item.item.dialog);
                            view.find('a')
                                .attr('href', '#')
                                .attr('class', config.aCss)
                                .attr('data-url', dataUrl)
                                .attr('title', __(config.label))
                                .attr('data-page-component-module', 'oroui/js/app/components/widget-component')
                                .attr('data-page-component-options', JSON.stringify(options))
                                .html(this.highlighter(secureItem));

                            if (config.iCss) {
                                view.prepend('<i class="' + config.iCss + ' hide-text">' + secureItem + '</i>');
                            }
                        } else {
                            view = $(this.options.item).attr('data-value', item.key);
                            view.find('a').html(this.highlighter(secureItem));
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
            const self = this;
            if (_.isArray(this.sourceUrl)) {
                process(this.sourceUrl);
                this.render();
            } else if (!_.isUndefined(this.cache[query])) {
                process(this.cache[query]);
                this.render();
            } else {
                const url = routing.generate(this.sourceUrl, {query: urlHelper.encodeURI(query)});
                $.get(url, data => {
                    this.data = data;
                    const result = [];
                    _.each(data, function(item, key) {
                        result.push({
                            key: key,
                            item: item
                        });
                    });
                    this.cache[query] = result;
                    process(result);
                    self.render();
                });
            }
        },

        onChange: function() {
            const $input = this.getTypeaheadInput();
            const key = $input.val();
            const dataItem = this.data[key];

            if (dataItem !== void 0) {
                $input.val('').inputWidget('refresh');

                if (!dataItem.dialog) {
                    mediator.execute('redirectTo', {url: dataItem.url}, {redirect: true});
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
