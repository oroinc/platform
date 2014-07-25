/*jslint browser:true, nomen:true*/
/*global define*/
define(['jquery', 'underscore', 'backbone', 'orotranslation/js/translator'
    ], function ($, _, Backbone, __) {
    'use strict';

    var document = window.document;

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
            this.options = _.defaults(options || {}, this.options);
            this.target = options.target;

            this.listenTo(this.model, 'sync', this.render);
            this.target.on('change', _.bind(this.selectionChanged, this));
            this.fields = $('input[name*="subject"], textarea[name*="content"]');
            this.fields.on('blur', _.bind(this._updateElementsMetaData, this));

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
            var entityName = $(e.currentTarget).val().split('\\').join('_');
            this.model.setEntityName(entityName);
            this.model.fetch();
        },

        /**
         * Renders target element
         *
         * @returns {*}
         */
        render: function () {
            var html,
                vars   = this.model.attributes,
                $el    = $(this.el);

            if (_.isEmpty(vars)) {
                $el.parent().hide();
            } else {
                html = _.template(this.options.template.html(), {vars:  vars});

                $el.html(html);
                $el.parent().show();

                $el.find('ul li a').draggable({helper: 'clone'});
                this.fields
                    .droppable({
                        drop: function Drop(event, ui) {
                            var variable = ui.draggable.text(),
                                textarea = $(this);

                            textarea.val(textarea.val() + variable);
                        }
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
            var field;
            field = this.fields.filter(document.activeElement);

            if (!field.length && this.lastElement && this.lastElement.is(':visible')) {
                field = this.lastElement;
            }

            if (field) {
                field.val(field.val() + $(e.currentTarget).html());
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
