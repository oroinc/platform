define(
    ['oroui/js/widget-manager', 'oroui/js/messenger', 'oroui/js/mediator', 'orotranslation/js/translator'],
    function(widgetManager, messenger, mediator, __) {
        'use strict';

        return function(options) {
            widgetManager.getWidgetInstance(
                options._wid,
                function(widget) {
                    if (options.data) {
                        if (!options.message) {
                            options.message = __('oro.ui.widget_form_component.save_flash_success');
                        }

                        messenger.notificationFlashMessage('success', options.message);
                        mediator.trigger('widget_success:' + widget.getAlias(), options);
                        mediator.trigger('widget_success:' + widget.getWid(), options);

                        widget.trigger('formSave', options.data);
                    } else {
                        if (options.formError) {
                            widget.trigger('formSaveError');
                        }
                    }

                    if (options.reloadLayout) {
                        mediator.trigger('layout:adjustReloaded');
                    }

                    if (!options.preventRemove && !widget.disposed) {
                        widget.remove();
                    }
                }
            );
        };
    }
);
