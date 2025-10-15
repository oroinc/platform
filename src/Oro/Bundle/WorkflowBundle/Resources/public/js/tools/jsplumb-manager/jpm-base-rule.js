import _ from 'underscore';
import Backbone from 'backbone';

function Rule(context) {
    this.context = context || this;
    this.root = null;
    this.items = [];
}
_.extend(Rule.prototype, {

    priority: 10,

    match: function() {
        return false;
    },

    apply: function() {
        return;
    }
});

Rule.extend = Backbone.Model.extend;

export default Rule;
