define([
    './header-cell/header-cell',
    'chaplin',
    './util'
], function(HeaderCell, Chaplin, util) {
    'use strict';

    var HeaderRow;

    HeaderRow = Chaplin.CollectionView.extend({
        tagName: 'tr',
        className: '',
        animationDuration: 0,

        /* Required fby current realization of grid.js, see header initialization code */
        autoRender: true,

        themeOptions: {
            optionPrefix: 'headerRow',
            className: 'grid-header-row'
        },

        initialize: function(options) {
            this.columns = options.columns;
            this.dataCollection = options.dataCollection;

            // itemView function is called as new this.itemView
            // it is placed here to pass THIS within closure
            var _this = this;
            _.extend(this, _.pick(options, ['themeOptions', 'template']));
            // let descendants override itemView
            if (!this.itemView) {
                this.itemView = function(options) {
                    var column = options.model;
                    var CurrentHeaderCell = column.get('headerCell') || options.headerCell || HeaderCell;
                    var cellOptions = {
                        column: column,
                        collection: _this.dataCollection,
                        themeOptions: {
                            className: 'grid-cell grid-header-cell'
                        }
                    };
                    if (column.get('name')) {
                        cellOptions.themeOptions.className += ' grid-header-cell-' + column.get('name');
                    }
                    _this.columns.trigger('configureInitializeOptions', CurrentHeaderCell, cellOptions);
                    return new CurrentHeaderCell(cellOptions);
                };
            }
            HeaderRow.__super__.initialize.apply(this, arguments);
            this.cells = this.subviews;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.cells;
            delete this.columns;
            delete this.dataCollection;
            HeaderRow.__super__.dispose.call(this);
        },

        render: function() {
            this._deferredRender();
            if (this.template) {
                this.renderCustomTemplate();
            }else {
                HeaderRow.__super__.render.apply(this, arguments);
            }
            this._resolveDeferredRender();

            return this;
        },

        renderCustomTemplate: function() {
            this.$el.html(this.template({
                themeOptions: this.themeOptions ? this.themeOptions : {},
                render: this.renderColumn.bind(this),
                attributes: this.renderColumnAttributes.bind(this)
            }));
            return this;
        },

        renderColumn: function(columnName) {
            var columnModel = _.find(this.columns.models, function(model) {
                return model.get('name') === columnName;
            });
            if (columnModel) {
                return this.renderItem(columnModel).$el.html();
            }
        },

        renderColumnAttributes: function(columnName, additionalAttrs) {
            var attributes = additionalAttrs || [];
            var columnModel = _.find(this.columns.models, function(model) {
                return model.get('name') === columnName;
            });
            if (columnModel) {
                var $element = this.renderItem(columnModel).$el;
                if($element.length){
                    if(attributes.class) {
                        var classes = attributes.class.split(' ');
                        for (var i in classes) {
                            $element.addClass(classes[i]);
                        }
                    }
                    attributes.class = $element.attr('class');
                }
            }

            var result = [];
            for(var k in attributes){
                result.push(k + '="' + attributes[k] + '"');
            }

            return result.join(' ');
        }
    });

    return HeaderRow;
});
