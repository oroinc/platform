define(function(require, exports, module) {
    'use strict';

    var HighlighterFavicon;
    var config = require('module-config').default(module.id);
    var _ = require('underscore');
    var BaseClass = require('oroui/js/base-class');

    var defaults = {
        faviconSelector: 'link[rel*="icon"]',
        faviconSize: 16,
        circleRadius: 4,
        circleColor: '#FF0000'
    };

    HighlighterFavicon = BaseClass.extend({
        /**
         * @type {HTMLLinkElement}
         */
        favicon: null,

        /**
         * @type {HTMLLinkElement}
         */
        tempFavicon: null,

        /**
         * @inheritDoc
         */
        constructor: function HighlighterFavicon(options) {
            HighlighterFavicon.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var names = _.keys(defaults);
            _.extend(this, defaults, _.pick(config, names), _.pick(options, names));
            this.favicon = document.querySelector(this.faviconSelector);

            HighlighterFavicon.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.unhighlight();

            delete this.tempFavicon;
            delete this.favicon;

            HighlighterFavicon.__super__.dispose.call(this);
        },

        highlight: function() {
            if (!this.favicon || !document.head.contains(this.favicon)) {
                return;
            }

            var img = new Image();
            img.src = this.favicon.href;
            img.onload = function() {
                if (this.disposed || !this.favicon.parentNode) {
                    return;
                }

                var faviconSize = this.faviconSize;
                var circleRadius = this.circleRadius;
                var circleColor = this.circleColor;

                var canvas = document.createElement('canvas');
                canvas.width = faviconSize;
                canvas.height = faviconSize;

                var context = canvas.getContext('2d');

                // Draw Original Favicon as Background
                context.drawImage(img, 0, 0, faviconSize, faviconSize);

                // Draw Notification Circle
                context.beginPath();
                context.arc( canvas.width - circleRadius, canvas.height - circleRadius, circleRadius, 0, 2 * Math.PI);
                context.fillStyle = circleColor;
                context.fill();

                // create temp favicon
                this.tempFavicon = document.createElement('link');
                this.tempFavicon.type = 'image/x-icon';
                this.tempFavicon.rel = 'shortcut icon';
                this.tempFavicon.href = canvas.toDataURL('image/png')
                    .replace('image/png', 'image/x-icon');

                // Replace favicon
                this.favicon.parentNode.replaceChild(this.tempFavicon, this.favicon);
            }.bind(this);
        },

        unhighlight: function() {
            if (this.tempFavicon && document.head.contains(this.tempFavicon)) {
                this.tempFavicon.parentNode.replaceChild(this.favicon, this.tempFavicon);
            }
        }
    });

    return HighlighterFavicon;
});
