define(function(require) {
    'use strict';

    var SegmentChoiceMock;
    var BaseView = require('oroui/js/app/views/base/view');

    SegmentChoiceMock = BaseView.extend({
        data: null,

        value: '',

        entity: null,

        constructor: function SegmentChoiceMock() {
            SegmentChoiceMock.lastCreatedInstance = this;
            spyOn(this, 'setValue').and.callThrough();
            spyOn(this, 'setData').and.callThrough();
            SegmentChoiceMock.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            this.entity = options.entity;
            SegmentChoiceMock.__super__.initialize.call(this, options);
        },

        getValue: function() {
            return this.value;
        },

        setValue: function(value) {
            this.value = value;
            this.trigger('change', {id: value});
        },

        setData: function(data) {
            this.data = data;
            this.value = data.id;
        },

        getData: function() {
            return this.data;
        }
    });

    return SegmentChoiceMock;
});
