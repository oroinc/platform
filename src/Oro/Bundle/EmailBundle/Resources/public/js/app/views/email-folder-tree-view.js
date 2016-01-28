define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view'
], function($, _, BaseView) {
    'use strict';

    var EmailFolderTreeView = BaseView.extend({
        dataInputSelector: null,

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
            this.$el.closest('form').on('submit'  + this.eventNamespace(), _.bind(this._onSubmit, this));

            this.relatedCheckboxesSelector = options.relatedCheckboxesSelector;
            this.$(options.checkAllSelector).on('change' + this.eventNamespace(), _.bind(this._onCheckAllChange, this));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.closest('form').off(this.eventNamespace());
            EmailFolderTreeView.__super__.dispose.apply(this, arguments);
        },

        _inputData: function($input) {
            var data = {};
            $input.find('input[data-name]:not(input[type=checkbox]:not(:checked))').each(function() {
                var $input = $(this);
                data[$input.attr('data-name')] = $input.val();
            });

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

        _onSubmit: function() {
            var folders = this._inputCollectionData(this.$('.folder-list').children());
            this.$(this.dataInputSelector).val(JSON.stringify(folders));
        },

        _onCheckAllChange: function(e) {
            this.$(this.relatedCheckboxesSelector).prop('checked', e.currentTarget.checked);
        }
    });

    return EmailFolderTreeView;
});
