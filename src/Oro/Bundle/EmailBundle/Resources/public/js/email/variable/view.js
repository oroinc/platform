/*global define*/
define(['jquery', 'underscore', 'backbone', 'orotranslation/js/translator'
    ], function ($, _, Backbone, __) {
    'use strict';

    /**
     * @export  oroemail/js/email/variable/view
     * @class   oro.email.variable.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        events: {
            'click ul li a': 'addVariable'
        },
        target: null,
        lastElement: null,

        /**
         * Constructor
         *
         * @param options {Object}
         */
        initialize: function (options) {
            this.target = options.target;

            this.listenTo(this.model, 'sync', this.render);
            this.target.on('change', _.bind(this.selectionChanged, this));

            $('input[name*="subject"], textarea[name*="content"]')
                .on('blur', _.bind(this._updateElementsMetaData, this));

            // set default to content
            this.lastElement = $('textarea[name*="content"]');

            this.render();
        },

        /**
         * onChange event listener
         *
         * @param e {Object}
         */
        selectionChanged: function (e) {
            var entityName = $(e.currentTarget).val();
            this.model.set('entityName', entityName.split('\\').join('_'));
            this.model.fetch();
        },

        /**
         * Renders target element
         *
         * @returns {*}
         */
        render: function () {
            var userVars   = this.model.get('user'),
                entityVars = this.model.get('entity'),
                $el        = $(this.el);

            if (_.isEmpty(userVars) && _.isEmpty(entityVars)) {
                $el.parent().hide();
            } else {
                var html = _.template(this.options.template.html(), {
                    userVars: this.model.get('user'),
                    entityVars: this.model.get('entity'),
                    'notice': __('Click to insert variable.')
                });

                $el.html(html);
                $el.parent().show();

                $('input[name*="subject"], textarea[name*="content"]')
                    .bind("dragenter dragover", function(e){
                        e.preventDefault();
                        e.stopPropagation();
                    })
                    .bind("dragleave dragexit", function(e){
                        e.preventDefault();
                        e.stopPropagation();
                    });
            }

            return this;
        },

        /**
         * Add variable to last element
         *
         * @param e
         * @returns {*}
         */
        addVariable: function (e) {
            if (!_.isNull(this.lastElement) && this.lastElement.is(':visible')) {
                this.lastElement.val(this.lastElement.val() + $(e.currentTarget).html());
            }

            return this;
        },

        /**
         * Update elements metadata
         *
         * @param e
         * @private
         * @returns {*}
         */
        _updateElementsMetaData: function (e) {
            this.lastElement = $(e.currentTarget);

            return this;
        }
    });
});
