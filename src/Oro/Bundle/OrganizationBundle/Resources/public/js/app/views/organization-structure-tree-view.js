define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view'
], function($, _, BaseView) {
    'use strict';

    var OrganizationStructureTreeView = BaseView.extend({
        dataInputSelector: null,
        template: null,
        accordionEnabled: true,

        requiredOptions: [
            'dataInputSelector',
            'tree',
            'selectedBusinessUnits',
            'selectedOrganizations',
            'accordionEnabled'
        ],

        inputData: {
            businessUnits: [],
            organizations: []
        },

        treeHandlers: {},

        initialize: function(options) {
            OrganizationStructureTreeView.__super__.initialize.apply(this, arguments);
            _.each(this.requiredOptions, function(optionName) {
                if (!_.has(options, optionName)) {
                    throw new Error('Required option "' + optionName + '" not found.');
                }
            });

            this.dataInputSelector = options.dataInputSelector;
            this.inputData.businessUnits = options.selectedBusinessUnits;
            this.inputData.organizations = options.selectedOrganizations;
            this.accordionEnabled = options.accordionEnabled;
            this.template = _.template($('#organization-children-template').html());

            var me = this;
            this.$el.on('change', 'input.bu:checkbox:checked', function() {
                var organization = parseInt($(this).data('organization'));
                var $organization = $('input.org-id-' + organization + ':checkbox:not(:checked)');
                $organization.prop('checked', true);

                if ($organization.length) {
                    me.inputData.organizations.push(organization);
                }
            });
            this.$el.on('change', 'input.org:checkbox:not(:checked)', function() {
                var organization = parseInt($(this).val());
                var $businessUnits = $('input.bu:checkbox:checked[data-organization="' + organization + '"]');
                $businessUnits.prop('checked', false);

                var excludedBusinessUnits = _.chain($businessUnits)
                    .map(_.property('value'))
                    .map(Number)
                    .value();

                me.inputData.businessUnits = _.partial(_.without, me.inputData.businessUnits)
                    .apply(this, excludedBusinessUnits);
            });

            this.$el.closest('form')
                .on('submit' + this.eventNamespace(), _.bind(this._onSubmit, this));

            this.$el.closest('form')
                .find('button[type=submit]')
                .on('click' + this.eventNamespace(), _.bind(this._preSubmit, this));

            _.each(options.tree, function(node) {
                if (!node.children.length) {
                    return;
                }

                var collapse = '#organization_' + node.id;
                this.treeHandlers[collapse] = _.partial(me._handleTreeShow, collapse, node);
                this._createTreeHandlers(node, '#businessUnit_');
            }, this);

            if (this.accordionEnabled) {
                this.$el.on('show', '.collapse', function(e) {
                    e.stopPropagation();
                    var collapse = '#' + $(this).attr('id');
                    me.treeHandlers[collapse].call(me);
                });
            } else {
                _.each(this.treeHandlers, function(treeHandler) {
                    treeHandler.call(this);
                }, this);
            }

            this.$el.on(
                'change',
                'input[data-name="organization"]',
                _.bind(this._createCheckboxHandler('organizations'), this)
            );
            this.$el.on(
                'change',
                'input[data-name="businessUnit"]',
                _.bind(this._createCheckboxHandler('businessUnits'), this)
            );
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.closest('form').off(this.eventNamespace());
            this.$el.closest('form').find('button[type=submit]').off(this.eventNamespace());
            OrganizationStructureTreeView.__super__.dispose.apply(this, arguments);
        },

        _createTreeHandlers: function(node, collapsePrefix) {
            if (!node.children) {
                return;
            }

            _.each(node.children, function(businessUnit) {
                if (!businessUnit.children || !businessUnit.children.length) {
                    return;
                }

                var collapse = collapsePrefix + businessUnit.id;
                this.treeHandlers[collapse] = _.partial(this._handleTreeShow, collapse, businessUnit);
                this._createTreeHandlers(businessUnit, collapsePrefix);
            }, this);
        },

        _handleTreeShow: function(collapse, node) {
            var html = this.template({
                children: node.children,
                selected: this.inputData.businessUnits,
                organization: node.organization || node.id,
                accordionEnabled: this.accordionEnabled,
                render: this.template
            });

            this.$(collapse + ' .accordion-inner').html(html);
        },

        _createCheckboxHandler: function(dataName) {
            return function(e) {
                var el = e.target;
                var value = parseInt(el.value);

                if (el.checked) {
                    if (this.inputData[dataName].indexOf(value) === -1) {
                        this.inputData[dataName].push(value);
                    }
                } else {
                    this.inputData[dataName] = _.without(this.inputData[dataName], value);
                }
            };
        },

        /**
         * Make sending of the form way faster when having way too much checkboxes
         * (tested with 10 000)
         */
        _preSubmit: function() {
            if (this.accordionEnabled) {
                this.$('.accordion-toggle:not(.collapsed)').click();
                this.$('.accordion-inner').empty();
            }
        },

        _onSubmit: function() {
            this.$(this.dataInputSelector).val(JSON.stringify(this.inputData));
        }
    });

    return OrganizationStructureTreeView;
});
