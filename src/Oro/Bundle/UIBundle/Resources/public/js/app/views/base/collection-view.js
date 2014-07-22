/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'chaplin',
    './view'
], function (_, Chaplin, View) {
    'use strict';

    var BaseCollectionView;

    BaseCollectionView = Chaplin.CollectionView.extend({

        initialize: function (options) {
            _.extend(this, _.pick(options, ['fallbackSelector', 'loadingSelector', 'itemSelector', 'listSelector']));
            BaseCollectionView.__super__.initialize.apply(this, arguments);
        },

        // This class doesn’t inherit from the application-specific View class,
        // so we need to borrow the method from the View prototype:
        getTemplateFunction: View.prototype.getTemplateFunction,
        _ensureElement: View.prototype._ensureElement,
        _findRegionElem: View.prototype._findRegionElem
    });

    return BaseCollectionView;
});
