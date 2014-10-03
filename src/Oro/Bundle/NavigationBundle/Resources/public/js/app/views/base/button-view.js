/*jslint browser:true, eqeq:true*/
/*global define, window*/
define([
    'oroui/js/mediator',
    'oroui/js/app/views/base/page-region-view'
], function (mediator, PageRegionView) {
    'use strict';

    var ButtonView, document;

    document = window.document;

    ButtonView = PageRegionView.extend({
        pageItems: ['showPinButton', 'titleShort', 'titleSerialized'],

        events: {
            'click': 'onToggle'
        },

        listen: {
            'add collection': 'updateState',
            'remove collection': 'updateState',
            'reset collection': 'updateState'
        },

        render: function () {
            var data, titleSerialized, titleShort;

            this.updateState();

            data = this.getTemplateData();
            if (!data) {
                return;
            }

            if (data.showPinButton) {
                titleShort = data.titleShort;
                this.$el.show();
                /**
                 * Setting serialized titles for pinbar button
                 */
                if (data.titleSerialized) {
                    titleSerialized = JSON.parse(data.titleSerialized);
                    this.$el.data('title', titleSerialized);
                }
                this.$el.data('title-rendered-short', titleShort);
            } else {
                this.$el.hide();
            }
        },

        updateState: function () {
            var model;
            model = this.collection.getCurrentModel();
            this.$el.toggleClass('gold-icon', model != null);
        },

        onToggle: function () {
            var model, attrs, Model;
            model = this.collection.getCurrentModel();
            if (model) {
                this.collection.trigger('toRemove', model);
            } else {
                Model = this.collection.model;
                attrs = this.getItemAttrs();
                model = new Model(attrs);
                this.collection.trigger('toAdd', model);
            }
        },

        getItemAttrs: function () {
            var attrs, title;
            title = this.$el.data('title');
            attrs = {
                url: mediator.execute('currentUrl'),
                title_rendered: document.title,
                title_rendered_short: this.$el.data('title-rendered-short') || document.title,
                title: title ? JSON.stringify(title) : '{"template": "' + document.title + '"}'
            };
            return attrs;
        }
    });

    return ButtonView;
});
