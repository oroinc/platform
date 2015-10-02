define(function(require) {
    'use strict';

    var Select2ShareComponent;
    var Select2Component = require('oro/select2-component');
    var Select2ShareView = require('orosecurity/js/app/views/select2-share-view');

    Select2ShareComponent = Select2Component.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            Select2ShareComponent.__super__.initialize.call(this, options);

            this.init();
        },

        init: function() {
            this.initView();
        },

        initView: function() {
            this.select2ShareView = new Select2ShareView({});
        }
    });

    return Select2ShareComponent;
});
