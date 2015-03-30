/*global define, require*/
/*jslint nomen: true*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oro/filter/datetime-filter',
    'oroentity/js/field-choice',
    'oroquerydesigner/js/field-condition'
], function($, _, __, DateTimeFilter) {
    'use strict';

    $.widget('oroauditquerydesigner.dataAuditCondition', $.oroquerydesigner.fieldCondition, {
        options: {
            auditFields: {},
            changeStateTpl: _.template(
                '<div>' +
                    '<select>' +
                        '<option value="1">changed</option>' +
                        '<option value="2">changed to value</option>' +
                    '</select>' +
                    '<span class="active-filter">' +
                    '</span>' +
                '</div>'
            )
        },

        _create: function() {
            var auditFields = JSON.parse(this.options.auditFields);
            this.options.fieldChoice.dataFilter = function (entity, fields) {
                return _.filter(fields, function(field) {
                    return _.some(auditFields[entity], function (auditField) {
                        return field.name === auditField.name;
                    });
                });
            };

            this._superApply(arguments);

            this._on(this.$fieldChoice, {
                changed: function (e, fieldId) {
                    this._renderChangeStateChoice();
                }
            });
        },

        _renderChangeStateChoice: function () {
            if (this.$changeStateChoice) {
                return;
            }

            this.$changeStateChoice = $(this.options.changeStateTpl());
            this.$fieldChoice.after(this.$changeStateChoice);
            var $select = this.$changeStateChoice.find('select');
            $select.select2({
                minimumResultsForSearch: -1
            });

            var onChangeCb = {
                1: this._renderChangedChoice,
                2: this._renderChangedToValueChoice
            };
            onChangeCb[$select.val()].apply(this);

            $select.on('change', _.bind(function (e) {
                onChangeCb[e.val].apply(this);
            }, this));
        },

        _renderChangedChoice: function () {
            var filterOptions = _.findWhere(this.options.filters, {
                type: 'datetime'
            });

            if (!filterOptions) {
                throw new Error('Cannot find filter "datetime"');
            }

            var filter = new (DateTimeFilter.extend(filterOptions))();

            this.$changeStateChoice.find('.active-filter').html(filter.render().$el);
            this.$filterContainer.hide();
        },

        _renderChangedToValueChoice: function () {
            this.$filterContainer.show();
        }
    });

    return $;
});