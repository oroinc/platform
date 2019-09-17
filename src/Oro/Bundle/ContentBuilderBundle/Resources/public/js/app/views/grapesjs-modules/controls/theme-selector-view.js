define(function(require) {
    'use strict';

    var ThemeSelector;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('tpl!orocontentbuilder/templates/grapesjs-dropdown-action.html');

    ThemeSelector = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'editor', 'themes'
        ]),

        autoRender: true,

        themes: [],

        template: template,

        className: 'gjs-select-control',

        currentTheme: null,

        events: {
            'click .dropdown-item': 'onClick',
            'input [name="theme-filter"]': 'onInput'
        },

        constructor: function ThemeSelector() {
            ThemeSelector.__super__.constructor.apply(this, arguments);
        },

        initialize: function() {
            this.setCurrentTheme();

            ThemeSelector.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            var data = this.getTemplateData();
            var template = this.getTemplateFunction();
            var html = template(data);
            this.$el.html(html);

            this.$el.inputWidget('seekAndCreate');
        },

        getTemplateData: function() {
            var options = _.reduce(this.themes, function(options, theme) {
                options[theme.name] = theme.label;
                return options;
            }, {});

            return {
                currentTheme: this.currentTheme.label,
                options: options
            };
        },

        filterItems: function(str) {
            this.$el.find('[data-role="filterable-item"]').each(function(index, el) {
                $(el).toggle($(el).text().toLowerCase().indexOf(str.toLowerCase()) !== -1);
            });
        },

        setCurrentTheme: function(key) {
            if (key) {
                _.each(this.themes, function(theme) {
                    theme.active = theme.name === key;
                }, this);
            }

            this.currentTheme = _.find(this.themes, function(theme) {
                return theme.active;
            });
        },

        onClick: function(e) {
            if (key === this.currentTheme) {
                return;
            }

            var key = $(e.target).data('key');
            this.setCurrentTheme(key);

            this.editor.trigger('changeTheme', $(e.target).data('key'));

            this.render();
        },

        onInput: function(e) {
            this.filterItems(e.target.value)
        }
    });

    return ThemeSelector;
});
