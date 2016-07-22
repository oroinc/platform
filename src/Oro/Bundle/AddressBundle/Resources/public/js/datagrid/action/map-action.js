define(function(require) {
    'use strict';

    var MapAction;
    var $ = require('jquery');
    var _ = require('underscore');
    var GoogleMaps = require('oroaddress/js/mapservice/googlemaps');
    var ModelAction = require('oro/datagrid/action/model-action');

    MapAction = ModelAction.extend({
        options: {
            mapView: GoogleMaps
        },

        dispatched: true,

        initialize: function(options) {
            MapAction.__super__.initialize.apply(this, arguments);

            this.$mapContainerFrame = $('<div class="map-popover__frame"/>');
            this.mapView = new this.options.mapView({
                el: this.$mapContainerFrame
            });

            this.datagrid.on('rendered', this.onGridRendered, this);
            this.datagrid.on('content:update', this.onGridRendered, this);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.$mapContainerFrame;
            this.datagrid.off(null, null, this);
            MapAction.__super__.dispose.apply(this, arguments);
        },

        onGridRendered: function() {
            if (!this.subviews.length) {
                return;
            }

            var $popoverTrigger = this.subviews[0].$el;

            $popoverTrigger.popover({
                placement: 'left',
                hideOnScroll: false,
                container: 'body',
                animation: false,
                html: true,
                closeButton: true,
                class: 'map-popover',
                content: this.$mapContainerFrame
            }).on('shown.bs.popover', _.bind(function() {
                this.mapView.updateMap(this.getAddress(), this.model.get('label'));

                $(document).on('mouseup', _.bind(function(e) {
                    var $map = this.mapView.$el;
                    if (!$map.is(e.target) && !$map.has(e.target).length) {
                        $popoverTrigger.popover('hide');
                    }
                }, this));
            }, this)).on('hidden.bs.popover', _.bind(function() {
                $(document).off('mouseup', null, this);
            }, this));
        },

        getAddress: function() {
            return this.model.get('countryName') + ', ' +
                this.model.get('city') + ', ' +
                this.model.get('street') + ' ' + (this.model.get('street2') || '');
        }
    });

    return MapAction;
});
