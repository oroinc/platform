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

        _inputData: function($input) {
            var data = {};
            $input.find('> input[data-name]:not(input[type=checkbox]:not(:checked))').each(function() {
                var $input = $(this);
                data[$input.attr('data-name')] = $input.val();
            });
            data.subFolders = this._inputCollectionData($input.find('> .folder-sub-folders').children());

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
