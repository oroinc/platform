import _ from 'underscore';
import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import Confirmation from 'oroui/js/delete-confirmation';

const AttributeFormOptionRowView = BaseView.extend({
    tagName: 'tr',

    events: {
        'click .delete-form-option': 'triggerRemove',
        'click .edit-form-option': 'triggerEdit'
    },

    options: {
        template: null,
        data: {
            label: null,
            property_path: null,
            required: false
        }
    },

    /**
     * @inheritdoc
     */
    constructor: function AttributeFormOptionRowView(options) {
        AttributeFormOptionRowView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        const template = this.options.template || $('#attribute-form-option-row-template').html();
        this.template = _.template(template);
    },

    update: function(data) {
        this.options.data = data;
        this.render();
    },

    triggerEdit: function(e) {
        e.preventDefault();
        this.trigger('editFormOption', this.options.data);
    },

    triggerRemove: function(e) {
        e.preventDefault();

        const confirm = new Confirmation({
            content: __('Are you sure you want to delete this field?')
        });
        confirm.on('ok', () => {
            this.trigger('removeFormOption', this.options.data);
        });
        confirm.open();
    },

    getTemplateData: function() {
        return this.options.data;
    }
});

export default AttributeFormOptionRowView;
