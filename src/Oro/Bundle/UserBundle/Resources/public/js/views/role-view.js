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
            this.privileges = JSON.parse(this.$privileges.val());

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
            delete this.privileges;
            delete this.$fields;

            RoleView.__super__.dispose.call(this);
        },

        /**
         * onSubmit event listener
         */
        onSubmit: function(event) {
            var hasErrors = false;
            _.each(
                this.$fields,
                function(element) {
                    if (element.hasClass('error')) {
                        hasErrors = true;
                    }
                }
            );
            if (hasErrors) {
                return;
            }
            var $form = this.$form;
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

            data[formName + '[privileges]'] = JSON.stringify(this.privileges);
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

            $.each(this.privileges, function(scopeName, privileges) {
                $.each(privileges, function(key, privilege) {
                    if (privilege.identity.id === data.identityId) {
                        $.each(privilege.permissions, function(permissionName, permission) {
                            if (permission.name === data.permissionName) {
                                permission.accessLevel = data.accessLevel;
                            }
                        });
                    }
                });
            });
        }
    });

    return RoleView;
});
