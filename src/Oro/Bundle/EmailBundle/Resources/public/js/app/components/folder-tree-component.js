define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var EmailFolderTreeView = require('oroemail/js/app/views/email-folder-tree-view');

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
