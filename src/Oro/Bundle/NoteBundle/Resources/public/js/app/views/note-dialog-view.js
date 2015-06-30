/* jslint browser:true, nomen:true */
/* global define */
define(function (require) {
    'use strict';

    var NoteView,
        _ = require('underscore'),
        BaseView = require('oroui/js/app/views/base/view');

    NoteView = BaseView.extend({
        autoRender: true,
        listen: {
            'component:parentResize': 'onParentResize'
        },

        initialize: function (options) {
            this.editorComponentName = options.editorComponentName;
            NoteView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            this._deferredRender();
            this.initLayout().done(_.bind(function () {
                this.onParentResize();
                this._resolveDeferredRender();
            }, this));
        },

        onParentResize: function () {
            var editor = this.pageComponent(this.editorComponentName);
            if (!editor) {
                throw new Error('Could not find message editor');
            }
            editor.view.setHeight(this.$el.closest('.ui-widget-content').innerHeight());
        }
    });

    return NoteView;
});
