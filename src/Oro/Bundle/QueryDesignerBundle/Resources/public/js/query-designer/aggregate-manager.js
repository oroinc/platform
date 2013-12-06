/* global define */
define(['jquery', 'underscore', 'backbone', 'oro/app'],
    function($, _, Backbone, app) {
        'use strict';

        /**
         * View that represents all query designer aggregates
         *
         * @export  oro/query-designer/aggregate-manager
         * @class   oro.queryDesigner.AggregateManager
         * @extends Backbone.View
         */
        return Backbone.View.extend({
            /** @property */
            optionTemplate: _.template('<option value="<%- name %>"><%- label %></option>'),

            /**
             * @property {Array}
             */
            aggregates: null,

            /**
             * @property {String}
             */
            activeAggregateName: null,

            /**
             * @property {Boolean}
             */
            isVisible: false,

            initialize: function()
            {
                this._getContainer().hide();

                var metadata = this.$el.closest('[data-metadata]').data('metadata');
                metadata = _.extend({aggregates: []}, metadata);
                this.aggregates = metadata.aggregates;

                Backbone.View.prototype.initialize.apply(this, arguments);
            },

            /**
             * Sets an aggregate conforms the given criteria as active
             *
             * @param {Object} criteria
             */
            setActiveAggregate: function (criteria) {
                var foundAggregateName = null;
                if (!_.isUndefined(criteria.aggregate)) {
                    // the criteria parameter represents an aggregate
                    foundAggregateName = criteria.aggregate;
                } else {
                    var foundAggregateMatchedBy = null;
                    _.each(this.aggregates, function(aggregate, aggregateName) {
                        var isApplicable = false;
                        if (!_.isEmpty(aggregate.applicable)) {
                            // if aggregate.applicable an array check if all items conforms the criteria
                            var matched = _.find(aggregate.applicable, function(applicable) {
                                var res = true;
                                _.each(applicable, function (val, key) {
                                    if (!_.has(criteria, key) || !app.isEqualsLoosely(val, criteria[key])) {
                                        res = false;
                                    }
                                });
                                return res;
                            });
                            if (!_.isUndefined(matched)) {
                                if (_.isNull(foundAggregateMatchedBy)
                                    // new rule is more exact
                                    || _.size(foundAggregateMatchedBy) < _.size(matched)
                                    // 'type' rule is most low level one, so any other rule can override it
                                    || (_.size(foundAggregateMatchedBy) == 1 && _.has(foundAggregateMatchedBy, 'type'))) {
                                    foundAggregateMatchedBy = matched;
                                    isApplicable = true;
                                }
                            }
                        }
                        if (isApplicable) {
                            foundAggregateName = aggregateName;
                        }
                    });
                }

                if (this.isVisible) {
                    if (_.isNull(foundAggregateName)) {
                        this._getContainer().hide();
                        this.isVisible = false;
                    }
                } else {
                    if (!_.isNull(foundAggregateName)) {
                        this._getContainer().show();
                        this.isVisible = true;
                    }
                }

                if (!_.isNull(foundAggregateName) && foundAggregateName !== this.activeAggregateName) {
                    var emptyItem = this.$el.find('option[value=""]');
                    this.$el.empty();
                    if (emptyItem.length > 0) {
                        this.$el.append(this.optionTemplate({name: '', label: emptyItem.text()}));
                    }
                    var content = '';
                    _.each(this.aggregates[foundAggregateName].function, function (val) {
                        content += this.optionTemplate(val);
                    }, this);
                    if (content != '') {
                        this.$el.append(content);
                    }
                    this.activeAggregateName = foundAggregateName;
                }

                if (_.isUndefined(criteria.data)) {
                    this.$el.val('');
                } else {
                    this.$el.val(criteria.data);
                }
            },

            /**
             * Returns translated string represents the given aggregate function
             *
             * @param {String} functionName
             * @return {String}
             */
            getAggregateFunctionLabel: function (functionName) {
                var result = functionName;
                _.every(this.aggregates, function(aggregate) {
                    var func = _.findWhere(aggregate.function, {name: functionName});
                    if (!_.isUndefined(func)) {
                        result = func.label;
                        return false;
                    }
                    return true;
                });
                return result;
            },

            /**
             * Returns a control which contains an aggregate selector and its label
             *
             * @return {jQuery}
             */
            _getContainer: function () {
                return this.$el.closest('.control-group');
            }
        });
    });
