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
            this.$el.on('change', 'input.bu:checkbox:checked', function () {
                var org_id = parseInt($(this).data('organization'));
                var $organization = $('input.org-id-' + org_id + ':checkbox:not(:checked)');
                $organization.prop('checked', true);

                if ($organization.length) {
                    me.inputData.organizations.push(org_id);
                }
            });
            this.$el.on('change', 'input.org:checkbox:not(:checked)', function () {
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

            var treeHandlers = {};
            _.each(options.tree, function(node) {
                var collapse = '#organization_' + node.id;
                treeHandlers[collapse] = _.partial(me._handleTreeShow, collapse, node);
            }, this);

            if (this.accordionEnabled) {
                var me = this;
                this.$el.on('show', '.collapse', function() {
                    var collapse = '#' + $(this).attr('id');
                    treeHandlers[collapse].call(me);
                });
            } else {
                _.each(treeHandlers, function(treeHandler) {
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

        _handleTreeShow: function(collapse, node) {
            var html = this.template({
                level: 1,
                children: node.children,
                selected: this.inputData.businessUnits,
                organization: node.id,
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
                this.$('.accordion-toggle:not(.collapse)').click();
                this.$('.accordion-inner').empty();
            }
        },

        _onSubmit: function() {
            this.$(this.dataInputSelector).val(JSON.stringify(this.inputData));
        }
    });

    return OrganizationStructureTreeView;
});
