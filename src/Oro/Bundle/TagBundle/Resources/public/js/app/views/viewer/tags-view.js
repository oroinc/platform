define(function(require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    /**
     * Tags view, able to handle tags array in model.
     *
     * Usage sample:
     * ```javascript
     * var tagsView = new TagsView({
     *     model: new Backbone.Model({
     *         tags: [
     *             {id: 1, name: 'tag1'},
     *             {id: 2, name: 'tag2'},
     *             // ...
     *         ]
     *     }),
     *     fieldName: 'tags', // should match tags field name in model
     *     autoRender: true
     * });
     * ```
     *
     * @class
     * @augments BaseView
     * @exports TagsView
     */
    var TagsView = BaseView.extend(/** @exports TagsView.prototype */{
        showDefault: true,

        template: require('tpl!orotag/templates/viewer/tags-view.html'),

        listen: {
            'change model': 'render'
        },

        /**
         * @inheritDoc
         */
        constructor: function TagsView() {
            TagsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.fieldName = options.fieldName;
            return TagsView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var tags = this.model.get(this.fieldName);
            tags = _.sortBy(tags, 'owner');
            return {
                tags: tags,
                showDefault: this.showDefault
            };
        }
    });

    return TagsView;
});
