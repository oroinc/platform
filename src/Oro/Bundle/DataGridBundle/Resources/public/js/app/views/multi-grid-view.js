import _ from 'underscore';
import $ from 'jquery';
import ActivityContextCollection from 'oroactivity/js/app/models/activity-context-collection';
import BaseView from 'oroui/js/app/views/base/view';
import WidgetManager from 'oroui/js/widget-manager';
import routing from 'routing';

/**
 * @exports MultiGridView
 */
const MultiGridView = BaseView.extend({
    /**
     * @inheritdoc
     */
    constructor: function MultiGridView(options) {
        MultiGridView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = options;
        this.template = _.template($('#multi-grid-item').html());
        this.collection = new ActivityContextCollection();
        this.initEvents();

        if (this.options.items) {
            _.first(this.options.items).first = true;
            _.each(_.rest(this.options.items), function(item) {
                item.first = false;
            });
            this.collection.add(this.options.items);
        }
    },

    add: function(model) {
        this.collection.add(model);
    },

    render: function() {
        if (this.collection.models.length === 0) {
            this.$el.hide();
        } else {
            this.$el.show();
        }
    },

    initEvents: function() {
        const self = this;
        const dropdown = this.$('.context-items-dropdown');
        const firstItem = this.$('.activity-context-current-item');
        this.collection.on('add', function(model) {
            let routeParams = self.options.params.grid_query || {params: {}};
            routeParams.params.class_name = model.get('className');
            routeParams.gridName = model.get('gridName');
            if (!_.isUndefined(self.options.params.routeParams)) {
                routeParams = _.extend(
                    {},
                    routeParams,
                    {params: self.options.params.routeParams}
                );
            }

            const gridUrl = routing.generate('oro_datagrid_widget', routeParams);

            const view = self.template({
                entity: model
            });
            const $view = $(view);

            if (model.attributes.first) {
                firstItem.html(model.attributes.label);
                self.currentTargetClass(model.attributes.className);
            }

            dropdown.append($view);
            dropdown.find('.dropdown-item:last').on('click', function() {
                self.currentTargetClass(model.attributes.className);
                dropdown.find('.dropdown-item').each(function() {
                    $(this).removeClass('active');
                });
                const item = $(this);
                firstItem.html(item.html());
                item.addClass('active');
                const gridWidgetName = self.options.gridWidgetName;
                WidgetManager.getWidgetInstanceByAlias(gridWidgetName, function(widget) {
                    widget.setUrl(gridUrl);
                    widget.render();
                });
            });
        });
    },

    /**
     * Getter/Setter for current target className
     *
     * @param {string=} value
     */
    currentTargetClass: function(value) {
        if (_.isUndefined(value)) {
            value = this._currentTargetClass;
        } else {
            this._currentTargetClass = value;
        }
        return value;
    }
});

export default MultiGridView;
