import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import routing from 'routing';
import dateTimeFormatter from 'orolocale/js/formatter/datetime';
import autolinker from 'autolinker';

const NoteView = BaseView.extend({
    options: {
        template: null
    },

    attributes: {
        'class': 'list-item'
    },

    events: {
        'click .item-edit-button': 'onEdit',
        'click .item-remove-button': 'onDelete',
        'click .accordion-toggle': 'onToggle'
    },

    listen: {
        'change model': '_onModelChanged'
    },

    /**
     * @inheritdoc
     */
    constructor: function NoteView(options) {
        NoteView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        this.collapsed = false;

        if (this.options.template) {
            this.template = _.template($(this.options.template).html());
        }
    },

    render: function() {
        NoteView.__super__.render.call(this);
        this._onRender();

        return this;
    },

    getTemplateData: function() {
        const data = NoteView.__super__.getTemplateData.call(this);

        data.collapsed = this.collapsed;
        data.createdAt = dateTimeFormatter.formatDateTime(data.createdAt);
        data.updatedAt = dateTimeFormatter.formatDateTime(data.updatedAt);
        data.createdBy_url = null;
        data.updatedBy_url = null;
        if (data.createdBy_id && data.createdBy_viewable) {
            data.createdBy_url = routing.generate('oro_user_view', {id: data.createdBy_id});
        }
        if (data.updatedBy_id && data.updatedBy_viewable) {
            data.updatedBy_url = routing.generate('oro_user_view', {id: data.updatedBy_id});
        }
        data.message = _.escape(data.message);
        data.brief_message = data.message.replace(new RegExp('\r?\n', 'g'), ' ');
        data.message = data.message.replace(new RegExp('\r?\n', 'g'), '<br />');
        data.message = autolinker.link(data.message, {className: 'no-hash'});
        if (data.brief_message.length > 200) {
            data.brief_message = data.brief_message.substr(0, 200);
        }
        data.brief_message = autolinker.link(data.brief_message, {className: 'no-hash'});

        return data;
    },

    onEdit: function() {
        this.model.collection.trigger('toEdit', this.model);
    },

    onDelete: function() {
        this.model.collection.trigger('toDelete', this.model);
    },

    onToggle: function(e) {
        e.preventDefault();
        this.toggle();
    },

    _onRender: function() {
        this.$('.accordion-toggle').toggleClass('collapsed', this.collapsed);
        this.$('.collapse').toggleClass('in', !this.collapsed);
    },

    /**
     * Collapses/expands view elements
     *
     * @param {boolean=} collapse
     */
    toggle: function(collapse) {
        this.collapsed = !_.isUndefined(collapse) ? collapse : !this.collapsed;
        this._onRender();
    },

    isCollapsed: function() {
        return this.$('.accordion-toggle').hasClass('collapsed');
    },

    _onModelChanged: function() {
        this.collapsed = this.isCollapsed();
        this.render();
    }
});

export default NoteView;
