define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const EmailFolderTreeView = require('oroemail/js/app/views/email-folder-tree-view');

    const FolderTreeComponent = BaseComponent.extend({
        emailFolderTreeView: null,

        requiredOptions: [
            'dataInputSelector',
            'checkAllSelector',
            'relatedCheckboxesSelector'
        ],

        /**
         * @inheritdoc
         */
        constructor: function FolderTreeComponent(options) {
            FolderTreeComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
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

    return FolderTreeComponent;
});
