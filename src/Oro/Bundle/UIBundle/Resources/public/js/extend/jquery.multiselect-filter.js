define(function(require) {
    'use strict';

    var $ = require('jquery');
    require('jquery-ui');
    require('jquery.multiselect.filter');

    /**
     * Fixed issue with event's namespaces on document element, caused memory leak
     */

    $.widget('ech.multiselectfilter', $.ech.multiselectfilter, {
        _handler: function(e) {
            if (this.cache) {
                this._super(e);
            }
        },

        updateCache: function() {
            if (this.instance.labels) {
                this._super();
            }
        },

        _destroy: function() {
            $(this.element).unbind();
            this._super();
        }
    });
});
