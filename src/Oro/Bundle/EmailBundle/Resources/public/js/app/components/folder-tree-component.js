define([
    'underscore',
    'oroui/js/app/components/base/component',
    'oroemail/js/app/views/email-folder-tree-view',
    'oroui/js/app/views/checkbox-toggle-view'
],function(_, BaseComponent, EmailFolderTreeView, CheckBoxToggleView) {
    'use strict';

    return BaseComponent.extend({
        initialize: function(options) {
            if (!_.has(options, 'dataInputSelector')) {
                throw new Error('Required option "dataInputSelector" not found.');
            }

            new EmailFolderTreeView({
                el: options._sourceElement,
                dataInputSelector: options.dataInputSelector
            });

            new CheckBoxToggleView({
                el: $('#check-all'),
                relatedCheckboxesSelector: '#folder-list :checkbox'
            });
        }
    });
});
