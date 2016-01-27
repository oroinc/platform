define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view'
], function($, _, BaseView) {
    'use strict';

    var EmailFolderTreeView = BaseView.extend({
        dataInputSelector: null,

        initialize: function(options) {
            if (!_.has(options, 'dataInputSelector')) {
                throw new Error('Required option "dataInputSelector" not found.');
            }

            this.dataInputSelector = options.dataInputSelector;
            this.$el.closest('form').on('submit'  + this.eventNamespace(), _.bind(this._onSubmit, this));
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
            var folders = this._inputCollectionData(this.$el.find('#folder-list').children());
            $(this.dataInputSelector).val(JSON.stringify(folders));
        }
    });

    return EmailFolderTreeView;
});
