import $ from 'jquery';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';

const RequiredPropertyView = BaseView.extend({
    /**
     * @inheritdoc
     */
    events: {
        change: 'onChange'
    },

    /**
     * @inheritdoc
     */
    autoRender: true,

    /**
     * @property {jQuery.Element}
     */
    $form: null,

    /**
     * @inheritdoc
     */
    constructor: function RequiredPropertyView(options) {
        RequiredPropertyView.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        RequiredPropertyView.__super__.initialize.call(this, options);

        this.$form = this.$el.closest('form');
        this.elementName = this.$el.attr('name');
    },

    render: function() {
        this.$el.inputWidget('create');
    },

    /**
     * @param {Object} e
     */
    onChange(e) {
        e.preventDefault();

        const headerId = mediator.execute('retrieveOption', 'headerId');
        const loadingMaskView = new LoadingMaskView({container: this._getContainerToUpdate()});

        $.ajax({
            url: this.$form.attr('action'),
            type: this.$form.attr('method'),
            headers: _.object([headerId], [true]),
            data: _.extend(this.$form.serializeArray(), [{
                name: this._getFormElementFullName('partialSubmit'),
                value: true
            }]),
            beforeSend: function() {
                loadingMaskView.show();
            },
            success: (function(response) {
                // Updates scope container.
                this._getContainerToUpdate()
                    .trigger('content:remove')
                    .replaceWith(this._getContainerToUpdate(response.content));

                this._getContainerToUpdate()
                    .trigger('content:changed')
                    .inputWidget('seekAndCreate');

                // Updates fieldName as new name might be generated.
                this._getFieldNameToUpdate()
                    .trigger('content:remove')
                    .replaceWith(this._getFieldNameToUpdate(response.content));

                this._getFieldNameToUpdate()
                    .trigger('content:changed');
            }).bind(this),
            complete: function() {
                loadingMaskView.hide();
            }
        });
    },

    /**
     * @param {String} name
     */
    _getFormElementFullName: function(name) {
        return this.$form.attr('name') + '[' + name + ']';
    },

    /**
     * @param {String} [parent]
     */
    _getContainerToUpdate: function(parent) {
        return $(parent || 'body')
            .find('[name="' + this.elementName + '"]')
            .closest('.control-group')
            .parent();
    },

    /**
     * @param {String} [parent]
     */
    _getFieldNameToUpdate: function(parent) {
        return $(parent || 'body')
            .find('[name="' + this._getFormElementFullName('fieldName') + '"]');
    }
});

export default RequiredPropertyView;
