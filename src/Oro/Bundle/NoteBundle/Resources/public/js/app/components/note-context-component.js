define(function(require) {
    'use strict';

    var ActivityContextComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var widgetManager = require('oroui/js/widget-manager');
    var messenger = require('oroui/js/messenger');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroactivity/js/app/components/activity-context-activity-component');
    var ActivityContextActivityView = require('oronote/js/app/views/note-context-component-view');

    ActivityContextComponent = BaseComponent.extend({
        initView: function() {
            var items = typeof this.options.contextTargets === 'undefined' ? false : this.options.contextTargets;
            var editable = typeof this.options.editable === 'undefined' ? false : this.options.editable;
            this.contextsView = new ActivityContextActivityView({
                contextTargets: items,
                entityId: this.options.entityId,
                el: this.options._sourceElement,
                inputName: this.options.inputName,
                target: this.options.target,
                activityClass: this.options.activityClassAlias,
                editable: editable
            });
        }
    });

    return ActivityContextComponent;
});
