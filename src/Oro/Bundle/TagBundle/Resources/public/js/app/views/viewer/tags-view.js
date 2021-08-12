define(function(require) {
    'use strict';
    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const colorUtil = require('oroui/js/tools/color-util');

    /**
     * Tags view, able to handle tags array in model.
     *
     * Usage sample:
     * ```javascript
     * const tagsView = new TagsView({
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
    const TagsView = BaseView.extend(/** @exports TagsView.prototype */{
        showDefault: true,

        template: require('tpl-loader!orotag/templates/viewer/tags-view.html'),

        listen: {
            'change model': 'render'
        },

        /**
         * @inheritdoc
         */
        constructor: function TagsView(options) {
            TagsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.fieldName = options.fieldName;
            return TagsView.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            let tags = this.model.get(this.fieldName).map(function(tag) {
                if (tag.backgroundColor && tag.backgroundColor.length > 0) {
                    tag = _.extend({
                        className: 'tags-container__tag-entry--custom-color',
                        style: 'background-color:' + tag.backgroundColor +
                            ';color:' + colorUtil.getContrastColor(tag.backgroundColor)
                    }, tag);
                }
                return tag;
            });
            tags = _.sortBy(tags, 'owner');
            return {
                tags: tags,
                showDefault: this.showDefault
            };
        }
    });

    return TagsView;
});
