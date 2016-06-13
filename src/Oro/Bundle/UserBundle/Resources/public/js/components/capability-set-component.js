define(function(require) {
    'use strict';

    var CapabilitySetComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseModel = require('oroui/js/app/models/base/model');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var CapabilityGroupView = require('orouser/js/views/capability-group-view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    CapabilitySetComponent = BaseComponent.extend({
        initialize: function(options) {
            var groups = _.map(_.groupBy(options.data, 'group'), function(group, key) {
                var label = _.result(_.result(options.categories, key), 'label');
                return new BaseModel({group: key, label: label, items: new BaseCollection(group)});
            });
            this.view = new BaseCollectionView({
                el: options._sourceElement,
                autoRender: true,
                animationDuration: 0,
                collection: new BaseCollection(groups),
                itemView: CapabilityGroupView
            });
        }
    });
    return CapabilitySetComponent;
});

