import _ from 'underscore';
import BaseComponent from 'oroui/js/app/components/base/component';
import EmailFolderTreeView from 'oroemail/js/app/views/email-folder-tree-view';

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

export default FolderTreeComponent;
