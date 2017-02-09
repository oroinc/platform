define(function(require) {
    'use strict';

    var MoveFormView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    MoveFormView = BaseView.extend({
        autoRender: true,

        optionNames: _.extend(['choices', 'targetValidationFail'], BaseView.prototype.options),

        choices: null,

        choicesParent: null,

        targetValidationFail: 'oro.ui.jstree.move_to_target_error',

        $target: null,

        $source: null,

        events: {
            'submit': 'onSubmit',
            'change [data-name="field__target"]': 'onTargetChange',
            'change [data-name="field__source"]': 'onSourceChange'
        },

        initialize: function() {
            this.$target = this.$el.find('[data-name="field__target"]');
            this.$source = this.$el.find('[data-name="field__source"]');

            this.configureValidator();

            this.collectChoicesParent();

            return MoveFormView.__super__.initialize.apply(this, arguments);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            _.each(['choices', 'choicesParent', '$target', '$source'], function(key) {
                delete this[key];
            }, this);

            return MoveFormView.__super__.dispose.apply(this, arguments);
        },

        configureValidator: function() {
            var validation = this.$target.data('validation') || {};

            validation.Callback = {
                callback: _.bind(this.validateTarget, this),
                message: this.targetValidationFail
            };

            this.$target.data('validation', validation);
        },

        collectChoicesParent: function() {
            this.choicesParent = {};
            _.each(this.choices, function(choice) {
                _.each(choice.children, function(child) {
                    this.choicesParent[child.id] = choice.id;
                }, this);
            }, this);
        },

        render: function() {
            this.disableTargetOptions();
            this.disableSourceOptions();
        },

        onTargetChange: function() {
            this.disableSourceOptions();
        },

        onSourceChange: function() {
            this.disableTargetOptions();
        },

        disableSourceOptions: function() {
            var target = this.$target.val();
            this.disableOptions(this.$source, this.collectAllParents(target));
        },

        disableTargetOptions: function() {
            this.disableOptions(this.$target, this.collectDisabledTargetOptions());
        },

        collectDisabledTargetOptions: function() {
            var source = this.$source.val();
            var children = [];
            if (!source) {
                return;
            }

            _.each(source, function(choice) {
                children = this.collectAllChildren(choice, children);
            }, this);

            return children;
        },

        collectAllParents: function(choice, parents) {
            parents = parents || [];
            if (!choice) {
                return parents;
            }
            parents.push(choice);

            var parent = this.choicesParent[choice] || null;
            if (parent) {
                parents = this.collectAllParents(parent, parents);
            }
            return parents;
        },

        collectAllChildren: function(choice, children) {
            children.push(choice);
            var choiceChildren = _.keys(this.choices[choice].children);
            if (choiceChildren) {
                _.each(choiceChildren, function(child) {
                    children = this.collectAllChildren(child, children);
                }, this);
            }
            return children;
        },

        disableOptions: function($select, options) {
            options = _.invert(options);

            $select.find('option').each(function() {
                this.disabled = options[this.value] !== undefined;
            });
        },

        onSubmit: function(e) {
            if (!this.validate()) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        },

        validateTarget: function(value, element, params) {
            var disabled = this.collectDisabledTargetOptions();
            return _.indexOf(disabled, this.$target.val()) === -1;
        },

        validate: function() {
            return this.$el.validate().form();
        }
    });

    return MoveFormView;
});
