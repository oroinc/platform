/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/app/views/base/view', 'routing', 'oroui/js/mediator', 'bootstrap'
    ], function ($, _, __, BaseView, routing, mediator) {
    'use strict';

    var ShortcutsView;
    /**
     * @export  oronavigation/js/app/views/shortcuts-view
     * @class   oronavigation.shortcuts.View
     * @extends BaseView
     */
    ShortcutsView = BaseView.extend({
        options: {
            el: '.shortcuts .input-large'
        },

        events: {
            'change': 'onChange'
        },

        data: {},

        cache: {},

        sourceUrl: null,
        entityClass: '',
        entityId: 0,

        initialize: function(options) {
            var self = this;
            this.options = _.defaults(options || {}, this.options);
            this.sourceUrl = this.$el.data('source-url') ? this.$el.data('source-url') : null;
            this.entityClass = this.$el.data('entity-class') ? this.$el.data('entity-class') : null;
            this.entityId = this.$el.data('entity-id') ? this.$el.data('entity-id') : null;

            this.$el.val('');

            this.$el.typeahead({
                source:_.bind(this.source, this),
                matcher: function (item) {
                    return ~item.key.toLowerCase().indexOf(this.query.toLowerCase())
                },
                sorter: function (items) {
                    var beginswith = []
                        , caseSensitive = []
                        , caseInsensitive = []
                        , item;

                    while (item = items.shift()) {
                        if (!item.key.toLowerCase().indexOf(this.query.toLowerCase())) {
                            beginswith.push(item)
                        } else if (~item.key.indexOf(this.query)) {
                            caseSensitive.push(item);
                        } else {
                            caseInsensitive.push(item);
                        }
                    }

                    return beginswith.concat(caseSensitive, caseInsensitive)
                },
                render: function (items) {
                    var that = this;
                    items = $(items).map(function (i, item) {
                        var view;

                        if (item.item.dialog) {
                            var config = item.item.dialog_config,
                                options = {
                                    "type": config.widget.type,
                                    "multiple":config.widget.multiple,
                                    "refresh-widget-alias": config.widget.refreshWidgetAlias,
                                    'reload-grid-name': config.widget.reloadGridName,
                                    "options":{
                                        "alias":config.widget.options.alias,
                                        "dialogOptions":{
                                            "title": __(config.widget.options.dialogOptions.title),
                                            "allowMaximize": config.widget.options.dialogOptions.allowMaximize,
                                            "allowMinimize":config.widget.options.dialogOptions.allowMinimize,
                                            "dblclick":config.widget.options.dialogOptions.dblclick,
                                            "maximizedHeightDecreaseBy":config.widget.options.dialogOptions.maximizedHeightDecreaseBy,
                                            "width":config.widget.options.dialogOptions.width
                                        }
                                    },
                                    "createOnEvent":"click"},
                                dataUrl = routing.generate(config.dataUrl,
                                    {
                                        entityClass: self.entityClass,
                                        entityId: self.entityId
                                    });

                            view = $(that.options.item).attr('data-value', item.key).attr('data-dialog', item.item.dialog);
                            view.find('a')
                                .attr('href', 'javascript: void(0);')
                                .attr('class', config.aCss)
                                .attr('data-url', dataUrl)
                                .attr('title', __(config.label))
                                .attr('data-page-component-module', 'oroui/js/app/components/widget-component')
                                .attr('data-page-component-options', JSON.stringify(options))
                                .html('<i class="'+config.iCss+' hide-text">'+item.key+'</i>' + that.highlighter(item.key));
                        } else {
                            view = $(that.options.item).attr('data-value', item.key);
                            view.find('a')  .html(that.highlighter(item.key));
                        }

                        return view[0];
                    });

                    items.first().addClass('active');
                    this.$menu.html(items);
                    return this
                }
                , 'click': function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    if (!this.$menu.find('.active').attr('data-dialog')) {
                        this.select();
                        this.$element.focus()
                    }
                }
            });
            this.$form = this.$el.closest('form');
            this.render();
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
                var url = routing.generate(this.sourceUrl, { 'query': query });
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
            var key = this.$el.val(),
                dataItem;
            this.$el.val('');
            if (!_.isUndefined(this.data[key])) {
                dataItem = this.data[key];
                if (!dataItem.dialog) {
                    this.$form.attr("action", dataItem.url).submit();
                } else {
                    this.$el.parent().find('li.active > a')[0].click();
                }
            }
        },

        render: function() {
            mediator.execute('layout:init', this.$el.closest('.shortcuts'), this);
            return this;
        }
    });

    return ShortcutsView;
});
