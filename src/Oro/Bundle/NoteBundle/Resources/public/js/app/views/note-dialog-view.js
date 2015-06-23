/*jslint browser:true, nomen:true*/
/*global define, alert*/
define(function (require) {
    'use strict';

    var NoteView,
        _ = require('underscore'),
        BaseView = require('oroui/js/app/views/base/view');

    NoteView = BaseView.extend({
        autoRender: true,
        listen: {
            'parentResize': 'onParentResize'
        },
        render: function () {
            this._deferredRender();
            this.initLayout().done(_.bind(function () {
                this.onParentResize();
                this._resolveDeferredRender();
            }, this));
        },

        onParentResize: function () {
            var editor = this.getComponentManager().get('oro_note_form_message');
            if (editor) {
                debugger;
            }
        }
    });

    return NoteView;
});
