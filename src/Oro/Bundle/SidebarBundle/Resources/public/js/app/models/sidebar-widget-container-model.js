import _ from 'underscore';
import loadModules from 'oroui/js/app/services/load-modules';
import BaseModel from 'oroui/js/app/models/base/model';
import mediator from 'oroui/js/mediator';
import constants from 'orosidebar/js/sidebar-constants';

const SidebarWidgetContainerModel = BaseModel.extend({
    defaults: {
        icon: '',
        iconClass: '',
        module: '',
        position: 0,
        title: '',
        settings: {},
        state: constants.WIDGET_MINIMIZED,
        widgetName: '',
        highlighted: false
    },

    /**
     * @inheritdoc
     */
    constructor: function SidebarWidgetContainerModel(attrs, options) {
        SidebarWidgetContainerModel.__super__.constructor.call(this, attrs, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function() {
        this.stateSnapshot = this.get('state');
        this.isDragged = false;
        this.loadModule();
    },

    loadModule: function() {
        if (!this.loadModulePromise) {
            this.loadModulePromise = loadModules(this.get('module'))
                .then(this.createController.bind(this))
                .catch(this.onWidgetLoadError.bind(this));
        }
        return this.loadModulePromise;
    },

    createController: function(Widget) {
        this.module = Widget;
        if (this.module.Component) {
            this.component = new this.module.Component({
                model: this
            });
        }
        return Widget;
    },

    onWidgetLoadError: function() {
        mediator.execute('showErrorMessage', 'Cannot load sidebar widget module "' + this.get('module') + '"');
    },

    /**
     * Toggles state of widget container between minimized and maximized
     */
    toggleState: function() {
        let state = this.get('state');

        state = (state === constants.WIDGET_MINIMIZED) ? constants.WIDGET_MAXIMIZED : constants.WIDGET_MINIMIZED;

        this.set('state', state);
        this.save();
    },

    /**
     * Toggles state of widget container from hover to previews and vise versa
     */
    toggleHoverState: function() {
        let state = this.get('state');

        if (state === constants.WIDGET_MAXIMIZED_HOVER) {
            state = this.stateSnapshot !== constants.WIDGET_MAXIMIZED_HOVER
                ? this.stateSnapshot : constants.WIDGET_MINIMIZED;
        } else {
            this.stateSnapshot = state;
            state = constants.WIDGET_MAXIMIZED_HOVER;
        }

        this.set('state', state);
        this.save();
    },

    removeHoverState: function() {
        if (this.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
            this.toggleHoverState();
        }
    },

    /**
     * Update from original data
     */
    update: function(widgetData) {
        this.set(_.omit(widgetData, 'settings', 'placement'));
    }
});

export default SidebarWidgetContainerModel;
