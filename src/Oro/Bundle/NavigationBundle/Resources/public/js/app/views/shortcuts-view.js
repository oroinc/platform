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
            el: '.shortcuts .input-large',
            source: null
        },

        events: {
            'change': 'onChange'
        },

        data: {},

        cache: {},

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el.val('');
            this.options.source = this.$el.data('source') ? this.$el.data('source') : null;

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
                        if (item.item.dialog) {
                            var config = item.item.dialog_config;

                            var options = {
                                    "type": config.widget.type,
                                    "multiple":config.widget.multiple,
                                    "refresh-widget-alias": config.widget.refreshWidgetAlias,
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
                                    "createOnEvent":"click"}
                            ;

                            i = $(that.options.item).attr('data-value', item.key);
                            i.find('a')
                                .attr('href', 'javascript: void(0);')
                                .attr('class', config.aCss)
                                .attr('data-url', routing.generate(config.dataUrl))
                                .attr('title', __(config.label))
                                .attr('data-page-component-module', 'oroui/js/app/components/widget-component')
                                .attr('data-page-component-options', JSON.stringify(options))
                                .html('<i class="'+config.iCss+' hide-text">'+item.key+'</i>' + that.highlighter(item.key));
                        } else {
                            i = $(that.options.item).attr('data-value', item.key);
                            i.find('a').html(that.highlighter(item.key));
                        }

                        return i[0];
                    });

                    items.first().addClass('active');
                    this.$menu.html(items);
                    return this
                }
            });
            this.$form = this.$el.closest('form');
            this.render();
        },

        source: function(query, process) {
            var self = this;
            if (_.isArray(this.options.source)) {
                process(this.options.source);
                this.render();
            } else if (!_.isUndefined(this.cache[query])) {
                process(this.cache[query]);
                this.render();
            } else {
                var url = routing.generate(this.options.source, { 'query': query });
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
