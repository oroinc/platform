require(['jquery', 'orotranslation/js/translator', 'oro/dialog-widget', 'ready!app'],
    function ($, __, DialogWidget) {
        $('.entity-identity-label').on('click', function (e) {
            e.preventDefault();

            var widget = new DialogWidget({
                'url': this.href,
                'title': __('oro.security.field_acl_window.label'),
                'stateEnabled': false,
                'incrementalPosition': false,
                'dialogOptions': {
                    'width': 650,
                    'autoResize': true,
                    'modal': true,
                    'minHeight': 100
                }
            });
            widget.render();
        });
    });
