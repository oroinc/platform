define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');

    const EmailFolderTreeView = BaseView.extend({
        dataInputSelector: null,

        checkAllSelector: null,

        relatedCheckboxesSelector: null,

        requiredOptions: [
            'dataInputSelector',
            'checkAllSelector',
            'relatedCheckboxesSelector'
        ],

        /**
         * @inheritdoc
         */
        constructor: function EmailFolderTreeView(options) {
            EmailFolderTreeView.__super__.constructor.call(this, options);
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

            this.dataInputSelector = options.dataInputSelector;
            this.$el.closest('form').on('submit' + this.eventNamespace(), this._onSubmit.bind(this));

            this.checkAllSelector = options.checkAllSelector;
            this.relatedCheckboxesSelector = options.relatedCheckboxesSelector;
            this.$(this.checkAllSelector).on('change' + this.eventNamespace(), this._onCheckAllChange.bind(this));
            this.listenTo(mediator, 'serializeFolderCollection', this._serializeFolderCollection);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.closest('form').off(this.eventNamespace());
            this.$(this.checkAllSelector).off(this.eventNamespace());
            EmailFolderTreeView.__super__.dispose.call(this);
        },

        _inputData: function($root) {
            const data = {};
            const inputs = $root.find('> input[data-name]')
                .add($root.find('> label > input[data-name]:checked'));

            inputs.each(function() {
                const $input = $(this);
                data[$input.attr('data-name')] = $input.val();
            });
            data.subFolders = this._inputCollectionData($root.find('> .folder-sub-folders').children());

            return data;
        },

        _inputCollectionData: function($inputCollection) {
            return _.map(
                $inputCollection,
                function(recordEl) {
                    return this._inputData($(recordEl));
                },
                this
            );
        },

        _serializeFolderCollection: function() {
            const folders = this._inputCollectionData(this.$('.folder-list').children());
            this.$(this.dataInputSelector).val(JSON.stringify(folders));
        },

        _onSubmit: function() {
            this._serializeFolderCollection();
        },

        _onCheckAllChange: function(e) {
            this.$(this.relatedCheckboxesSelector).prop('checked', e.currentTarget.checked);
        }
    });

    return EmailFolderTreeView;
});
