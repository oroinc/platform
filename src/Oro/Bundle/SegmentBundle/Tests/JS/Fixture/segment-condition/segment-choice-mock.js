define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const SegmentChoiceMock = BaseView.extend({
        data: null,

        value: '',

        entity: null,

        constructor: function SegmentChoiceMock(...args) {
            SegmentChoiceMock.lastCreatedInstance = this;
            spyOn(this, 'setValue').and.callThrough();
            spyOn(this, 'setData').and.callThrough();
            SegmentChoiceMock.__super__.constructor.apply(this, args);
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
