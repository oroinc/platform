import _ from 'underscore';

export default {
    organizationId: null,

    setOrganizationId: function(organizationId) {
        this.organizationId = organizationId;
    },

    getOrganizationId: function() {
        if (null !== this.organizationId) {
            return this.organizationId;
        }

        const urlParts = this._getCurrentUrl().split('?');
        if (urlParts.length !== 2) {
            return;
        }

        return _.chain(urlParts[1].split('&'))
            .map(function(queryPart) {
                return queryPart.match(/_sa_org_id.*=(\d+)/);
            })
            .find(function(res) {
                return res && res.length === 2;
            })
            .last()
            .value();
    },
    _getCurrentUrl: function() {
        return window.location.href;
    }
};
