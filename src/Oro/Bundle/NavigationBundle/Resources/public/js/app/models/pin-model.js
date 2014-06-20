/*jslint nomen:true*/
/*global define*/
define([
    './base/model'
], function (BaseModel) {
    'use strict';

    var PinModel;

    PinModel = BaseModel.extend({
        defaults: {
            title: '',
            url: null,
            position: null,
            type: 'pinbar'
        }
    });

    return PinModel;
});
