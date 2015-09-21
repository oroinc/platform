define(function(require) {
    'use strict';

    var CommentComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var TemplateView = require('oroui/js/app/views/base/template-view');

    CommentComponent = BaseComponent.extend({

        initialize: function(options) {
            this.options = options || {};

            this.view = new TemplateView({
                autoRender: true,
                el: options._sourceElement,
                data: options.data,
                template: require('tpl!../../../../templates/text-editor.html'),
                events: {
                    'click [data-action]': _.bind(this.rethrowAction, this)
                }
            });

            CommentComponent.__super__.initialize.apply(this, arguments);
        },

        rethrowAction: function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            _.this.trigger($(e.target).attr('data-action') + 'Action');
        }
    });

    return CommentComponent;
});
