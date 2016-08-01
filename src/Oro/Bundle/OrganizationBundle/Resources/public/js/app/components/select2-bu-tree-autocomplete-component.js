define(function(require) {
    'use strict';

    var BUTreeAutocompleteComponent;
    var Select2TreeAutocompleteComponent = require('oro/select2-tree-autocomplete-component');
    var mediator = require('oroui/js/mediator');

    BUTreeAutocompleteComponent = Select2TreeAutocompleteComponent.extend({
        organizations: {},

        initialize: function(options) {
            BUTreeAutocompleteComponent.__super__.initialize.apply(this, arguments);

            this.listenTo(mediator, 'changed:selectedOrganization', this.onChangeSelectedOrganization);
            this.listenTo(mediator, 'initOrganization', this.onInitOrganization);
        },

        preConfig: function(config) {
            config = BUTreeAutocompleteComponent.__super__.preConfig.apply(this, arguments);

            var that = this;
            config.ajax.data = function(query, page) {
                return {
                    page: page,
                    per_page: that.perPage,
                    name: config.autocomplete_alias,
                    query: that.makeQuery(query, config),
                    organizations: that.getOrganizations()
                };
            };

            return config;
        },

        getOrganizations: function() {
            var org = [];
            var i;

            for (i in this.organizations) {
                if (this.organizations.hasOwnProperty(i)) {
                    org.push(i);
                }
            }

            return org.join(',');
        },

        onInitOrganization: function(data) {
            for (var i in data.organizations) {
                if (data.organizations.hasOwnProperty(i)) {
                    this.organizations[data.organizations[i]] = data.organizations[i];
                }
            }
        },

        onChangeSelectedOrganization: function(data) {
            var that = this;

            if (data.add) {
                this.organizations[data.organizationId] = data.organizationId;
            } else {
                delete this.organizations[data.organizationId];

                var values = this.view.$el.val();
                values = values.split(',');

                var checkedOrganization = values.filter(function(value) {
                    var selectedValue = that.getValueFromSelectedData(parseInt(value));
                    if (selectedValue) {
                        return parseInt(selectedValue.organization_id) === data.organizationId;
                    }
                });

                for (var i in checkedOrganization) {
                    if (checkedOrganization.hasOwnProperty(i)) {
                        if (values.indexOf(checkedOrganization[i]) !== -1) {
                            values.splice(values.indexOf(checkedOrganization[i]), 1);
                        }
                    }
                }

                this.view.$el.val(values).trigger('change');
            }
        },

        getValueFromSelectedData: function(val) {
            var selectedData = this.view.$el.data('selected-data');

            return selectedData.filter(function(item) {
                return item.id === val;
            }).pop();
        }
    });

    return BUTreeAutocompleteComponent;
});
