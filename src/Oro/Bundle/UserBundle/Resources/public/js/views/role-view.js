define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view'
], function($, _, mediator, BaseView) {
    'use strict';

    /**
     * @export orouser/js/views/role-view
     */
    const RoleView = BaseView.extend({
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
            click: 'onSubmit'
        },

        listen: {
            'securityAccessLevelsComponent:link:click mediator': 'onAccessLevelsLinkClicked'
        },

        /**
         * @inheritdoc
         */
        constructor: function RoleView(options) {
            RoleView.__super__.constructor.call(this, options);
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

            const fields = {};
            _.each(
                this.options.fields,
                function(selector, name) {
                    fields[name] = $(selector);
                }
            );
            this.$fields = fields;
        },

        /**
         * @inheritdoc
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
            const $form = this.$form;
            let hasError = false;
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

            const action = $form.attr('action');
            const method = $form.attr('method');
            let url = (typeof action === 'string') ? action.trim() : '';
            url = url || window.location.href || '';
            if (url) {
                url += '?input_action=' + $(event.target).attr('data-action');

                // clean url (don't include hash value)
                url = (url.match(/^([^#]+)/) || [])[1];
            }

            const data = this.getData();

            const dataAction = $(event.target).attr('data-action');
            if (dataAction) {
                data.input_action = dataAction;
            }

            const options = {
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
            const data = {};

            const formName = this.options.formName;
            _.each(
                this.$fields,
                function(element, name) {
                    let value = element.val();

                    if (element.attr('type') === 'checkbox') {
                        value = element.is(':checked') ? 1 : 0;

                        if (value === 0) { // do not send the value of checkbox,
                            return; // it will be set as false in the backend
                        }
                    }

                    data[formName + '[' + name + ']'] = value;
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
            const obj = JSON.parse(this.$privileges.val());

            const knownIdentities = _.reduce(obj, function(memo, group) {
                memo = _.reduce(group, function(memo, item) {
                    memo[item.identity.id] = item;
                    return memo;
                }, memo);
                return memo;
            }, {});

            let identity = data.identityId;
            const splittedIdentity = identity.split('::');
            let field;
            if (typeof splittedIdentity[1] !== 'undefined') {
                identity = splittedIdentity[0];
                field = splittedIdentity[1];
            }

            let privelege;
            let fieldPrivelege;
            if (identity in knownIdentities) {
                privelege = knownIdentities[identity];
            } else {
                // create privelege
                privelege = {
                    identity: {
                        id: identity
                    },
                    permissions: {}
                };
                if (data.permissionName === 'EXECUTE') {
                    obj.action[Object.keys(knownIdentities).length] = privelege;
                } else {
                    obj.entity[Object.keys(knownIdentities).length] = privelege;
                }
            }

            if (!field) {
                if (!(data.permissionName in privelege.permissions)) {
                    privelege.permissions[data.permissionName] = {
                        name: data.permissionName
                    };
                }
                const permission = privelege.permissions[data.permissionName];
                permission.accessLevel = data.accessLevel;
            } else {
                const knownFieldIdentities = _.reduce(privelege.fields, function(memo, item) {
                    memo[item.identity.id] = item;
                    return memo;
                }, {});

                fieldPrivelege = knownFieldIdentities[data.identityId];
                const fieldPermission = fieldPrivelege.permissions[data.permissionName];
                fieldPermission.accessLevel = data.accessLevel;
            }

            this.$privileges.val(JSON.stringify(obj)).trigger('change');
        }
    });

    return RoleView;
});
