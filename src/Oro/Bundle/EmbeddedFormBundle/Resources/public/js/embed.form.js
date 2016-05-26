/*jshint -W098*/
var ORO = (function(ORO) {
    'use strict';

    var IframeEmbeddedForm = function(container, options) {
        this.container = document.getElementById(container);
        this.options = options || {};
    };

    IframeEmbeddedForm.prototype = {
        show: function() {
            var iframe = document.createElement('iframe');
            iframe.src = this.options.src;
            iframe.width = this.options.width;
            iframe.height = this.options.height;
            iframe.frameBorder = this.options.frameBorder;

            this.container.innerHTML = '';
            this.container.appendChild(iframe);
        }
    };

    var XHREmbeddedForm = function(container, options) {
        this.container = document.getElementById(container);
        this.options = options || {};
    };

    XHREmbeddedForm.prototype = {
        ajax: function(url, settings) {
            var context = this;
            var xhr = new XMLHttpRequest();

            xhr.addEventListener('error', function(e) {
                settings.error.apply(context, [xhr, xhr.statusText]);
            });

            xhr.addEventListener('load', function(e) {
                if (this.status >= 200 && this.status < 400) {
                    settings.success.apply(context, [this.response, xhr.statusText, xhr]);
                } else {
                    settings.error.apply(context, [xhr, xhr.statusText]);
                }
            });

            xhr.open(settings.method, url, true);

            if (settings.hasOwnProperty('data')) {
                xhr.send(settings.data);
            } else {
                xhr.send();
            }

            return xhr;
        },

        show: function() {
            this.ajax(this.options.url, {
                method: 'GET',
                success: this.renderForm,
                error: function(xhr, statusText) {
                    console.error(statusText);
                }
            });
        },

        renderForm: function(data) {
            this.container.innerHTML = data;
            this.container
                .querySelector('form')
                .addEventListener('submit', this.onSubmit.bind(this));
        },

        onSubmit: function(e) {
            e.preventDefault();

            this.ajax(this.options.url, {
                method: 'POST',
                data: new FormData(e.target),
                success: this.renderForm,
                error: function(xhr, statusText) {
                    console.error(statusText);
                }
            });
        },
    };

    ORO.EmbedForm = function(options) {
        if (options.hasOwnProperty('iframe')) {
            var embeddedForm = new IframeEmbeddedForm(options.container, options.iframe);
            embeddedForm.show();

            return;
        }

        if (options.hasOwnProperty('xhr')) {
            var embeddedForm = new XHREmbeddedForm(options.container, options.xhr);
            embeddedForm.show();

            return;
        }

        throw new Error('Invalid embedded form options');
    };

    return ORO;
})(ORO || {});
