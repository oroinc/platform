define(function(require) {
    'use strict';

    var EmailFolderTreeView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailFolderTreeView = BaseView.extend({
        dataInputSelector: null,

        checkAllSelector: null,

        relatedCheckboxesSelector: null,

        requiredOptions: [
            'dataInputSelector',
            'checkAllSelector',
            'relatedCheckboxesSelector'
        ],

        /**
         * @inheritDoc
         */
        constructor: function EmailFolderTreeView() {
            EmailFolderTreeView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.each(this.requiredOptions, function(optionName) {
                if (!_.has(options, optionName)) {
                    throw new Error('Required option "' + optionName + '" not found.');
                }
            });

            this.dataInputSelector = options.dataInputSelector;
            this.$el.closest('form').on('submit' + this.eventNamespace(), _.bind(this._onSubmit, this));

            this.checkAllSelector = options.checkAllSelector;
            this.relatedCheckboxesSelector = options.relatedCheckboxesSelector;
            this.$(this.checkAllSelector).on('change' + this.eventNamespace(), _.bind(this._onCheckAllChange, this));
            this.listenTo(mediator, 'serializeFolderCollection', this._serializeFolderCollection);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.closest('form').off(this.eventNamespace());
            this.$(this.checkAllSelector).off(this.eventNamespace());
            EmailFolderTreeView.__super__.dispose.apply(this, arguments);
        },

        _inputData: function($root) {
            var data = {};
            var inputs = $root.find('> input[data-name]').add($root.find('> label > span > input[data-name]:checked'));

            inputs.each(function() {
                var $input = $(this);
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
            var folders = this._inputCollectionData(this.$('.folder-list').children());
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
