define(function(require) {
    'use strict';

    var PageLayoutView;
    var $ = require('jquery');
    var tools = require('oroui/js/tools');
    var Chaplin = require('chaplin');
    var mediator = require('oroui/js/mediator');
    var formToAjaxOptions = require('oroui/js/tools/form-to-ajax-options');
    var utils = Chaplin.utils;

    PageLayoutView = Chaplin.Layout.extend({
        events: {
            'submit form': 'onSubmit',
            'click.action.data-api [data-action=page-refresh]': 'onRefreshClick'
        },

        listen: {
            'page:beforeChange mediator': 'removeErrorClass',
            'page:error mediator': 'addErrorClass'
        },

        /**
         * @inheritDoc
         */
        constructor: function PageLayoutView() {
            PageLayoutView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this.$el.attr({'data-layout': 'separate'});
            this.initLayout();
        },

        /**
         * Added raw argument. Removed Internet Explorer < 9 workaround
         *
         * @param {string} subtitle
         * @param {boolean=} raw
         * @returns {string}
         * @override
         */
        adjustTitle: function(subtitle, raw) {
            var title;
            if (!raw) {
                if (!subtitle) {
                    subtitle = '';
                }
                title = this.settings.titleTemplate({
                    title: this.title,
                    subtitle: subtitle
                });
            } else {
                title = subtitle;
            }
            // removed Internet Explorer < 9 workaround
            document.title = title;
            return title;
        },

        /**
         * Fixes issue when correspondent over options regions are not taken into account
         * @override
         */
        registerGlobalRegions: function(instance) {
            var name;
            var selector;
            var version;
            var _i;
            var _len;
            var _ref = utils.getAllPropertyVersions(instance, 'regions');

            if (instance.hasOwnProperty('regions')) {
                _ref.push(instance.regions);
            }

            for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                version = _ref[_i];
                for (name in version) {
                    if (!version.hasOwnProperty(name)) {
                        continue;
                    }
                    selector = version[name];
                    this.registerGlobalRegion(instance, name, selector);
                }
            }
        },

        /**
         * Fixes issues
         *  - empty hashes (like '#')
         *  - routing full url (containing protocol and host)
         *  - stops application's navigation if it's an error page
         *  - process links with redirect options
         * @override
         */
        openLink: function(event) {
            var href;
            var el = event.currentTarget;
            var $el = $(el);

            if (
                utils.modifierKeyPressed(event) ||
                event.isDefaultPrevented() ||
                $el.parents('.sf-toolbar').length ||
                tools.isErrorPage()
            ) {
                return;
            }

            if (el.nodeName === 'A' && el.getAttribute('href')) {
                href = el.getAttribute('href');
                // prevent click by empty hashes
                if (href === '#') {
                    event.preventDefault();
                    return;
                }
                // fixes issue of routing full url, makes url relative
                if (href.indexOf(':\/\/') !== -1 && el.host === location.host) {
                    el.setAttribute('href', el.pathname + el.search + el.hash);
                }
            }

            // not link to same page and not javascript code link
            if (
                href &&
                !Chaplin.mediator.execute('compareUrl', href) &&
                href.substr(0, 11) !== 'javascript:'
            ) {
                var payload = {prevented: false, target: el};
                Chaplin.mediator.publish('openLink:before', payload);
                if (payload.prevented !== false) {
                    event.preventDefault();
                    return;
                }
            }

            href = el.getAttribute('href') || el.getAttribute('data-href') || null;
            if (!(href !== null && href !== void 0) || href === '' || href.charAt(0) === '#') {
                return;
            }
            var skipRouting = this.settings.skipRouting;
            switch (typeof skipRouting) {
                case 'function':
                    if (!skipRouting(href, el)) {
                        return;
                    }
                    break;
                case 'string':
                    if (utils.matchesSelector(el, skipRouting)) {
                        return;
                    }
            }
            if (this.isExternalLink(el)) {
                if (this.settings.openExternalToBlank) {
                    event.preventDefault();
                    this.openWindow(href);
                }
                return;
            }

            // now it's possible to pass redirect options over elements data-options attribute
            var options = $el.data('options') || {};
            utils.redirectTo({url: href}, options);
            event.preventDefault();
        },

        removeErrorClass: function() {
            this.$el.removeClass('error-page');
        },

        addErrorClass: function() {
            this.$el.addClass('error-page');
        },

        onSubmit: function(event) {
            var data;
            var options;

            if (event.isDefaultPrevented()) {
                return;
            }

            var $form = $(event.target);
            if ($form.data('nohash') && !$form.data('sent')) {
                $form.data('sent', true);
                return;
            }
            event.preventDefault();
            if ($form.data('sent')) {
                return;
            }

            $form.data('sent', true);

            var url = $form.attr('action');
            var method = $form.attr('method') || 'GET';

            if (url && method.toUpperCase() === 'GET') {
                data = $form.serialize();
                if (data) {
                    url += (url.indexOf('?') === -1 ? '?' : '&') + data;
                }
                mediator.execute('redirectTo', {url: url});
                $form.removeData('sent');
            } else {
                options = formToAjaxOptions($form, {
                    complete: function() {
                        $form.removeData('sent');
                    }
                });
                mediator.execute('submitPage', options);
            }
        },

        onRefreshClick: function() {
            mediator.execute('refreshPage');
        }
    });

    return PageLayoutView;
});
