define(
    ['backbone'],
    function (Backbone) {
        function Promise (mainObject) {
            this.mainObject = mainObject;
        }
        Promise.prototype = {
            resolved: false,

            /**
             * Allow to run callback when promise is resolved
             *
             * @param fn
             * @param ctx
             */
            whenResolved: function (fn, ctx) {
                if (this.resolved) {
                    fn.call(ctx || this, this.mainObject);
                    return;
                }
                this.on('resolve', fn, ctx);
            },

            setResolved: function () {
                this.resolved = true;
                this.trigger('resolve', this.mainObject);
                this.off('resolve');
            }
        }

        _.extend(Promise.prototype, Backbone.Events);

        return Promise;

    }
)