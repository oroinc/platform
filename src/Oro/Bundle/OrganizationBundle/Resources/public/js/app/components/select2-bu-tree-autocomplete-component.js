define(function(require) {
    'use strict';

    const Select2TreeAutocompleteComponent = require('oro/select2-tree-autocomplete-component');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const Select2BuTreeAutocompleteView = require('oroorganization/js/app/views/select2-bu-tree-autocomplete-view');
    const tools = require('oroui/js/tools');

    const BUTreeAutocompleteComponent = Select2TreeAutocompleteComponent.extend({
        ViewType: Select2BuTreeAutocompleteView,

        organizations: {},

        /**
         * @inheritdoc
         */
        constructor: function BUTreeAutocompleteComponent(options) {
            BUTreeAutocompleteComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            BUTreeAutocompleteComponent.__super__.initialize.call(this, options);

            this.listenTo(mediator, 'changed:selectedOrganization', this.onChangeSelectedOrganization);
            this.listenTo(mediator, 'initOrganization', this.onInitOrganization);
        },

        preConfig: function(config) {
            BUTreeAutocompleteComponent.__super__.preConfig.call(this, config);

            const that = this;
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
            const org = [];
            let i;

            for (i in this.organizations) {
                if (this.organizations.hasOwnProperty(i)) {
                    org.push(i);
                }
            }

            return org.join(',');
        },

        onInitOrganization: function(data) {
            for (const i in data.organizations) {
                if (data.organizations.hasOwnProperty(i)) {
                    this.organizations[data.organizations[i]] = data.organizations[i];
                }
            }
        },

        onChangeSelectedOrganization: function(data) {
            if (data.add) {
                this.organizations[data.organizationId] = data.organizationId;
            } else {
                delete this.organizations[data.organizationId];

                let values = this.view.$el.val();
                values = values.split(',');

                const checkedOrganization = values.filter(value => {
                    const selectedValue = this.getValueFromSelectedData(parseInt(value));
                    if (selectedValue) {
                        return parseInt(selectedValue.organization_id) === parseInt(data.organizationId);
                    }
                });

                for (const i in checkedOrganization) {
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
            const selectedData = tools.ensureArray(this.view.$el.data('selected-data'));

            return _.find(selectedData, function(item) {
                return item.id === val;
            });
        }
    });

    return BUTreeAutocompleteComponent;
});
