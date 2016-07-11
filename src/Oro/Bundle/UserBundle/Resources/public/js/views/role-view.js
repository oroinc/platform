define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view'
], function($, _, mediator, BaseView) {
    'use strict';

    var RoleView;

    /**
     * @export orouser/js/views/role-view
     */
    RoleView = BaseView.extend({
        options: {
            elSelector: '',
            formName: '',
            formSelector: '',
            privilegesSelector: '',
            appendUsersSelector: '',
            removeUsersSelector: '',
            fields: ''
        },
        privileges: null,

        $form: null,
        $privileges: null,
        $appendUsers: null,
        $removeUsers: null,
        $fields: null,

        events: {
            'click': 'onSubmit'
        },

        listen: {
            'securityAccessLevelsComponent:link:click mediator': 'onAccessLevelsLinkClicked'
        },

        /**
         * Initialize
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = $(this.options.elSelector);
            this.$form = $(this.options.formSelector);
            this.$privileges = $(this.options.privilegesSelector);
            this.$appendUsers = $(this.options.appendUsersSelector);
            this.$removeUsers = $(this.options.removeUsersSelector);

            var fields = {};
            _.each(
                this.options.fields,
                function(selector, name) {
                    fields[name] = $(selector);
                }
            );
            this.$fields = fields;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.$form;
            delete this.$privileges;
            delete this.$appendUsers;
            delete this.$removeUsers;
            delete this.$fields;

            RoleView.__super__.dispose.call(this);
        },

        /**
         * onSubmit event listener
         */
        onSubmit: function(event) {
            var $form = this.$form;
            var hasError = false;
            _.each(this.$fields, function(field) {
                hasError = hasError || !field.valid();
            });
            if (hasError) {
                return;
            }
            if ($form.data('nohash') && !$form.data('sent')) {
                $form.data('sent', true);
                return;
            }
            if ($form.data('sent')) {
                return;
            }

            $form.data('sent', true);

            var action = $form.attr('action');
            var method = $form.attr('method');
            var url = (typeof action === 'string') ? $.trim(action) : '';
            url = url || window.location.href || '';
            if (url) {
                // clean url (don't include hash value)
                url = (url.match(/^([^#]+)/) || [])[1];
            }

            var data = this.getData();
            var dataAction = $(event.target).attr('data-action');
            if (dataAction) {
                data.input_action = dataAction;
            }

            var options = {
                url: url,
                type: method || 'GET',
                data: $.param(data)
            };
            mediator.execute('submitPage', options);
        },

        /**
         * @returns {Object}
         */
        getData: function() {
            var data = {};

            var formName = this.options.formName;
            _.each(
                this.$fields,
                function(element, name) {
                    data[formName + '[' + name + ']'] = element.val();
                }
            );

            data[formName + '[privileges]'] = this.$privileges.val();
            data[formName + '[appendUsers]'] = this.$appendUsers.val();
            data[formName + '[removeUsers]'] = this.$removeUsers.val();

            return data;
        },

        /**
         * onClick event listener
         */
        onAccessLevelsLinkClicked: function(data) {
            if (this.disposed) {
                return;
            }
            var obj = JSON.parse(this.$privileges.val());

            var knownIdentities = _.reduce(obj, function(memo, group) {
                memo = _.reduce(group, function(memo, item) {
                    memo[item.identity.id] = item;
                    return memo;
                }, memo);
                return memo;
            }, {});

            var privelege;
            if (data.identityId in knownIdentities) {
                privelege = knownIdentities[data.identityId];
            } else {
                // create privelege
                privelege = {
                    identity: {
                        id: data.identityId
                    },
                    permissions: {}
                };
                if (data.permissionName === 'EXECUTE') {
                    obj.action[Object.keys(knownIdentities).length] = privelege;
                } else {
                    obj.entity[Object.keys(knownIdentities).length] = privelege;
                }
            }

            if (!(data.permissionName in privelege.permissions)) {
                privelege.permissions[data.permissionName] = {
                    name: data.permissionName
                };
            }
            var permission = privelege.permissions[data.permissionName];
            permission.accessLevel = data.accessLevel;

            this.$privileges.val(JSON.stringify(obj)).trigger('change');
        }
    });

    return RoleView;
});
