/* global google */
define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');
    const __ = require('orotranslation/js/translator');
    const localeSettings = require('orolocale/js/locale-settings');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const BaseView = require('oroui/js/app/views/base/view');
    const messenger = require('oroui/js/messenger');
    const $ = Backbone.$;

    /**
     * @export  oroaddress/js/mapservice/googlemaps
     * @class   oroaddress.mapservice.Googlemaps
     * @extends BaseView
     */
    const GoogleMapsView = BaseView.extend({
        options: {
            mapOptions: {
                zoom: 17,
                mapTypeControl: true,
                panControl: false,
                zoomControl: true
            },
            apiVersion: '3.exp',
            apiKey: null,
            showWeather: true
        },

        mapLocationCache: {},

        mapsLoadExecuted: false,

        errorMessage: null,

        geocoder: null,

        mapRespondingTimeout: 2000,

        loadingMask: null,

        /**
         * @inheritdoc
         */
        constructor: function GoogleMapsView(options) {
            GoogleMapsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {},
                _.pick(localeSettings.settings, ['apiKey']),
                this.options
            );

            this.$viewContainer = $('<div class="map-visual-container"/>')
                .appendTo(this.$el);

            this.$mapContainer = $('<div class="map-visual"/>')
                .appendTo(this.$viewContainer);

            this.loadingMask = new LoadingMaskView({container: this.$viewContainer});

            if (options.address) {
                this.updateMap(options.address.address, options.address.label);
            } else {
                this.mapLocationUnknown();
            }
        },

        _initMapOptions: function() {
            if (_.isUndefined(this.options.mapOptions.mapTypeControlOptions)) {
                this.options.mapOptions.mapTypeControlOptions = {
                    style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                };
            }
            if (_.isUndefined(this.options.mapOptions.zoomControlOptions)) {
                this.options.mapOptions.zoomControlOptions = {
                    style: google.maps.ZoomControlStyle.SMALL
                };
            }
            if (_.isUndefined(this.options.mapOptions.mapTypeId)) {
                this.options.mapOptions.mapTypeId = google.maps.MapTypeId.ROADMAP;
            }
        },

        _initMap: function(location) {
            let weatherLayer;
            let cloudLayer;
            this.removeErrorMessage();
            this._initMapOptions();
            this.map = new google.maps.Map(
                this.$mapContainer[0],
                _.extend({}, this.options.mapOptions, {center: location})
            );

            this.mapLocationMarker = new google.maps.Marker({
                draggable: false,
                map: this.map,
                position: location
            });

            if (this.options.showWeather) {
                const temperatureUnitKey = localeSettings.settings.unit.temperature.toUpperCase();
                const windSpeedUnitKey = localeSettings.settings.unit.wind_speed.toUpperCase();
                weatherLayer = new google.maps.weather.WeatherLayer({
                    temperatureUnits: google.maps.weather.TemperatureUnit[temperatureUnitKey],
                    windSpeedUnits: google.maps.weather.WindSpeedUnit[windSpeedUnitKey]
                });
                weatherLayer.setMap(this.map);

                cloudLayer = new google.maps.weather.CloudLayer();
                cloudLayer.setMap(this.map);
            }

            this.loadingMask.hide();
        },

        isEmptyFunction: function(func) {
            return typeof func === 'function' &&
                /^function[^{]*[{]\s*[}]\s*$/.test(
                    Function.prototype.toString.call(func));
        },

        loadGoogleMaps: function() {
            const data = {};

            if (this.options.apiKey) {
                data.key = this.options.apiKey;
            } else {
                this.mapsLoadExecuted = false;
                this.addErrorMessage();
                this.loadingMask.hide();
                return;
            }

            if (this.options.showWeather) {
                data.libraries = 'weather';
            }

            $.ajax({
                url: `${location.protocol}//maps.googleapis.com/maps/api/js`,
                data,
                dataType: 'script',
                cache: true,
                success: () => {
                    this.onGoogleMapsInit();
                    this.mapsLoadExecuted = false;
                },
                errorHandlerMessage: false,
                error: this.mapLocationUnknown.bind(this)
            });
        },

        updateMap: function(address, label) {
            let timeoutId;

            this.loadingMask.show();
            this.removeErrorMessage();

            // Load google maps js
            if (!this.hasGoogleMaps()) {
                if (this.mapsLoadExecuted) {
                    return;
                }

                this.mapsLoadExecuted = true;
                this.requestedLocation = {
                    address: address,
                    label: label
                };
                this.loadGoogleMaps();
                return;
            }

            if (this.mapLocationCache.hasOwnProperty(address)) {
                this.updateMapLocation(this.mapLocationCache[address], label);
            } else {
                if (this.isEmptyFunction(this.getGeocoder().geocode)) {
                    return this.checkRenderMap();
                }
                this.getGeocoder().geocode({address: address}, (results, status) => {
                    clearTimeout(timeoutId);
                    if (status === google.maps.GeocoderStatus.OK) {
                        this.mapLocationCache[address] = results[0].geometry.location;
                        // Move location marker and map center to new coordinates
                        this.updateMapLocation(results[0].geometry.location, label);
                    } else {
                        this.mapLocationUnknown();
                    }
                });

                timeoutId = _.delay(this.checkRenderMap.bind(this), this.mapRespondingTimeout);
            }
        },

        onGoogleMapsInit: function() {
            if (!_.isUndefined(this.requestedLocation)) {
                this.updateMap(this.requestedLocation.address, this.requestedLocation.label);
                delete this.requestedLocation;
            }
        },

        checkRenderMap: function() {
            if (this.mapsLoadExecuted) {
                return false;
            }

            if (this.$mapContainer.is(':empty')) {
                this.addErrorMessage();
                this.loadingMask.hide();
                return true;
            }

            return false;
        },

        hasGoogleMaps: function() {
            return !_.isUndefined(window.google) && google.hasOwnProperty('maps');
        },

        mapLocationUnknown: function() {
            this.$mapContainer.hide();
            this.addErrorMessage(__('map.unknown.location'));
            this.loadingMask.hide();
        },

        mapLocationKnown: function() {
            this.$mapContainer.show();
        },

        updateMapLocation: function(location, label) {
            this.mapLocationKnown();
            if (location && (!this.location || location.toString() !== this.location.toString())) {
                this._initMap(location);
                this.map.setCenter(location);
                this.mapLocationMarker.setPosition(location);
                this.mapLocationMarker.setTitle(label);
                this.location = location;
                this.trigger('mapRendered');
            } else {
                this.loadingMask.hide();
            }
        },

        getGeocoder: function() {
            if (_.isUndefined(this.geocoder) || _.isNull(this.geocoder)) {
                this.geocoder = new google.maps.Geocoder();
            }
            return this.geocoder;
        },

        addErrorMessage: function(message, type) {
            this.removeErrorMessage();
            this.errorMessage = messenger.notificationFlashMessage(
                type || 'warning',
                message || __('map.unknown.unavailable'),
                {
                    container: this.$viewContainer,
                    hideCloseButton: true,
                    insertMethod: 'prependTo'
                }
            );
        },

        removeErrorMessage: function() {
            if (_.isNull(this.errorMessage)) {
                return;
            }
            messenger.clear(this.errorMessage.namespace, {
                container: this.$viewContainer
            });

            delete this.errorMessage;
        }
    });

    return GoogleMapsView;
});
