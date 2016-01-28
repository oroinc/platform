define([
    'underscore',
    'oroui/js/app/components/base/component',
    'oroemail/js/app/views/email-folder-tree-view'
],function(_, BaseComponent, EmailFolderTreeView) {
    'use strict';

    return BaseComponent.extend({
        emailFolderTreeView: null,

        requiredOptions: [
            'dataInputSelector',
            'checkAllSelector',
            'relatedCheckboxesSelector'
        ],

        initialize: function(options) {
            _.each(this.requiredOptions, function(optionName) {
                if (!_.has(options, optionName)) {
                    throw new Error('Required option "' + optionName + '" not found.');
                }
            });

            this.emailFolderTreeView = new EmailFolderTreeView({
                el: options._sourceElement,
                dataInputSelector: options.dataInputSelector,
                checkAllSelector: options.checkAllSelector,
                relatedCheckboxesSelector: options.relatedCheckboxesSelector
            });
        }
    });
});
