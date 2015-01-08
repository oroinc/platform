define({
    /**
     * Getter/setter for components
     *
     * @param {string} name
     * @param {BaseComponent=} component to set
     */
    pageComponent: function (name, component) {
        if (!this.pageComponents) {
            this.pageComponents = {};
        }
        if (name && component) {
            this.removePageComponent(name);
            this.pageComponents[name] = component;
            component.on('dispose', _.bind(function () {
                this.removePageComponent(name);
            }, this));
            return component;
        } else {
            return this.pageComponents[name];
        }
    },

    /**
     * @param {string} name component name to remove
     */
    removePageComponent: function (name) {
        if (!this.pageComponents) {
            this.pageComponents = {};
        }
        var toRemove = this.pageComponents[name];
        if (toRemove) {
            delete this.pageComponents[name];
            toRemove.dispose();
        }
    },

    /**
     * Destroys all linked page components
     */
    disposePageComponents: function () {
        if (!this.pageComponents) {
            return;
        }
        for (var name in this.pageComponents) {
            this.removePageComponent(name);
        }
        delete this.pageComponents;
    }
});
