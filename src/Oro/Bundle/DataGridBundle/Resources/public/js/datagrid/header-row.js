define([
    './header-cell/header-cell',
    'chaplin',
    '../app/components/column-renderer-component',
    './util'
], function(HeaderCell, Chaplin, ColumnRendererComponent, util) {
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

            this.columnRenderer = new ColumnRendererComponent(options);

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
            var self = this;
            this.$el.html(this.template({
                themeOptions: this.themeOptions ? this.themeOptions : {},
                render: function(columnName) {
                    var columnModel = _.find(self.columns.models, function(model) {
                        return model.get('name') === columnName;
                    });
                    if (columnModel) {
                        return self.columnRenderer.getHtml(self.renderItem(columnModel).$el);
                    }
                    return '';
                },
                attributes: function(columnName, additionalAttributes) {
                    var attributes = additionalAttributes || {};
                    var columnModel = _.find(self.columns.models, function(model) {
                        return model.get('name') === columnName;
                    });
                    if (columnModel) {
                        return self.columnRenderer.getRawAttributes(self.renderItem(columnModel).$el, attributes);
                    }
                    return '';
                }
            }));
            return this;
        }
    });

    return HeaderRow;
});
