import BaseView from 'oroui/js/app/views/base/view';
import descriptionTemplate from 'tpl-loader!oroapi/templates/openapi-view-description.html';

const OpenApiViewFieldView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'viewSelector'
    ]),

    /**
     * The HTML template to render a description of a selected view
     * @type {string}
     */
    descriptionTemplate: descriptionTemplate,

    /**
     * The selector for "View" field
     * @type {string}
     */
    viewSelector: void 0,

    events() {
        return {
            [`change ${this.viewSelector}`]: 'updateViewDescription'
        };
    },

    /**
     * @inheritdoc
     */
    constructor: function OpenApiViewFieldView(options) {
        OpenApiViewFieldView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function() {
        this.updateViewDescription();
    },

    updateViewDescription(event) {
        const $view = this.$(this.viewSelector);
        const $descriptionContainer = $view.closest('.controls').find('.description-container');
        const description = $view.find(':selected').data('description');
        const template = this.getTemplateFunction('descriptionTemplate');
        $descriptionContainer.html(template({description}));
        $descriptionContainer.show();
    }
});

export default OpenApiViewFieldView;
