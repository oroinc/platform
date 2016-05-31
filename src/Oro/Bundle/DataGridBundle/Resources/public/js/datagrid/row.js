define([
    'jquery',
    'underscore',
    'chaplin',
    'oroui/js/tools',
    './util'
], function($, _, Chaplin, tools, util) {
    'use strict';

    var Row;
    var document = window.document;

    /**
     * Grid row.
     *
     * Triggers events:
     *  - "clicked" when row is clicked
     *
     * @export  orodatagrid/js/datagrid/row
     * @class   orodatagrid.datagrid.Row
     * @extends Chaplin.CollectionView
     */
    Row = Chaplin.CollectionView.extend({
        tagName: 'tr',
        autoRender: false,
        animationDuration: 0,

        /** @property */
        events: {
            'mousedown': 'onMouseDown',
            'mouseleave': 'onMouseLeave',
            'mouseup': 'onMouseUp',
            'click': 'onClick'
        },

        DOUBLE_CLICK_WAIT_TIMEOUT: 170,

        template: null,

        themeOptions: {
            view: '',
            optionPrefix: 'row',
            className: 'grid-row',
            actionSelector: ''
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            // itemView function is called as new this.itemView
            // it is placed here to pass THIS within closure
            var _this = this;
            _.extend(this, _.pick(options, ['themeOptions', 'template', 'columns']));
            // let descendants override itemView
            if (!this.itemView) {
                this.itemView = function(options) {
                    var column = options.model;
                    var cellOptions = {
                        column: column,
                        model: _this.model,
                        themeOptions: {
                            className: 'grid-cell grid-body-cell'
                        }
                    };
                    if (column.get('name')) {
                        cellOptions.themeOptions.className += ' grid-body-cell-' + column.get('name');
                    }
                    var Cell = column.get('cell');
                    _this.columns.trigger('configureInitializeOptions', Cell, cellOptions);
                    var cell = new Cell(cellOptions);
                    if (column.has('align')) {
                        cell.$el.removeClass('align-left align-center align-right');
                        cell.$el.addClass('align-' + column.get('align'));
                    }
                    if (!_.isUndefined(cell.skipRowClick) && cell.skipRowClick) {
                        cell.$el.addClass('skip-row-click');
                    }

                    // use columns collection as event bus since there is no alternatives
                    _this.columns.trigger('afterMakeCell', _this, cell);

                    return cell;
                };
            }

            this.listenTo(this.model, 'backgrid:selected', this.onBackgridSelected);

            Row.__super__.initialize.apply(this, arguments);
            this.cells = this.subviews;
        },

        /**
         * Handles row "backgrid:selected" event
         *
         * @param model
         * @param isSelected
         */
        onBackgridSelected: function(model, isSelected) {
            this.$el.toggleClass('row-selected', isSelected);
        },

        className: function() {
            return this.model.get('row_class_name');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.clickTimeout) {
                clearTimeout(this.clickTimeout);
            }
            delete this.columns;
            delete this.cells;
            Row.__super__.dispose.call(this);
        },

        onMouseDown: function(e) {
            if (this.clickTimeout) {
                // if timeout is set, it means that user makes double click
                clearTimeout(this.clickTimeout);
                delete this.clickTimeout;
                // prevent second click handler launch
                this.mouseDownSelection = null;
                this.mouseDownTarget = null;
                // prevent text selection on double click
                if ($(e.target).closest('.prevent-text-selection-on-dblclick').length) {
                    e.preventDefault();
                }
                return;
            }
            // remember selection and target
            this.mouseDownSelection = this.getSelectedText();
            this.mouseDownTarget = $(e.target).closest('td');
            this.$el.addClass('mouse-down');
        },

        onMouseLeave: function(e) {
            this.$el.removeClass('mouse-down');
        },

        onMouseUp: function(e) {
            this.clickPermit = false;
            // remember selection and target
            var $target = this.$(e.target);
            var exclude;
            var allowed;
            if (this.themeOptions.actionSelector) {
                allowed = this.themeOptions.actionSelector;
                if (!$target.is(allowed) && !$target.parents(allowed).length) {
                    return;
                }
            } else {
                exclude = 'a, .dropdown, .skip-row-click';
                // if the target is an action element, skip toggling the email
                if ($target.is(exclude) || $target.parents(exclude).length) {
                    return;
                }
            }

            if (this.mouseDownSelection !== this.getSelectedText()) {
                return;
            }

            if (this.mouseDownTarget[0] !== $target.closest('td')[0]) {
                return;
            }

            this.clickPermit = true;
        },

        onClick: function(e) {
            var _this = this;
            var options = {};
            var clickFunction = function() {
                if (_this.disposed) {
                    return;
                }
                _this.trigger('clicked', _this, options);
                for (var i = 0; i < _this.subviews.length; i++) {
                    var cell = _this.subviews[i];
                    if (cell.listenRowClick && _.isFunction(cell.onRowClicked)) {
                        cell.onRowClicked(_this, e);
                    }
                }
                _this.$el.removeClass('mouse-down');
                delete _this.clickTimeout;
            };
            if (!this.clickPermit) {
                return;
            }
            e.preventDefault();
            if (tools.isTargetBlankEvent(e)) {
                options.target = '_blank';
                clickFunction();
                return;
            }
            this.clickTimeout = setTimeout(clickFunction, this.DOUBLE_CLICK_WAIT_TIMEOUT);
        },

        /**
         * Returns selected text is available
         *
         * @return {string}
         */
        getSelectedText: function() {
            var text = '';
            if (_.isFunction(window.getSelection)) {
                text = window.getSelection().toString();
            } else if (!_.isUndefined(document.selection) && document.selection.type === 'Text') {
                text = document.selection.createRange().text;
            }
            return text;
        },

        render: function() {
            if (this.template) {
                this.renderCustomTemplate();
            } else {
                Row.__super__.render.apply(this, arguments);
            }
            var state = {selected: false};
            this.model.trigger('backgrid:isSelected', this.model, state);
            this.$el.toggleClass('row-selected', state.selected);

            if (this.$el.data('layout') === 'separate') {
                var options = {};
                if (this.$el.data('layout-model')) {
                    options[this.$el.data('layout-model')] = this.model;
                }
                this.initLayout(options);
            }

            return this;
        },

        renderCustomTemplate: function() {
            var $checkbox;
            this.$el.html(this.template({
                model: this.model ? this.model.attributes : {},
                themeOptions: this.themeOptions ? this.themeOptions : {}
            }));
            $checkbox = this.$('[data-role=select-row]:checkbox');
            if ($checkbox.length) {
                this.listenTo(this.model, 'backgrid:select', function(model, checked) {
                    $checkbox.prop('checked', checked);
                });
                $checkbox.on('change' + this.eventNamespace(), _.bind(function(e) {
                    this.model.trigger('backgrid:selected', this.model, $checkbox.prop('checked'));
                }, this));
                $checkbox.on('click' + this.eventNamespace(), function(e) {
                    e.stopPropagation();
                });
            }
            return this;
        }
    });

    return Row;
});
