define(function() {
    'use strict';

    /**
     * @param {Node} node - instance of Node from ExpressionLanguage library
     * @param {ASTNodeWrapper} parent - instance of ASTNodeWrapper that contain parent Node
     */
    function ASTNodeWrapper(node, parent) {
        this.parent = parent || null;
        this.originNode = node;
        this.children = node.nodes.map(function(child) {
            return new ASTNodeWrapper(child, this);
        }.bind(this));
    }

    ASTNodeWrapper.prototype = {
        constructor: ASTNodeWrapper,

        /** @type {ASTNodeWrapper} */
        parent: null,

        /** @type {Node} */
        originNode: null,

        /** @type {Array.<ASTNodeWrapper>} */
        children: null,

        /**
         * @param {string} name - name of attribute of original node
         * @return {string|number}
         */
        attr: function(name) {
            return this.originNode.attrs[name];
        },

        /**
         * @param {number} index
         * @return {ASTNodeWrapper}
         */
        child: function(index) {
            return this.children[index];
        },

        /**
         * Checks if its original node is instance of the constructor
         *
         * @param {Function} constructor
         * @return {boolean}
         */
        instanceOf: function(constructor) {
            return this.originNode instanceof constructor;
        },

        /**
         * Goes through its tree and collect all nodes that returns true from the callback
         *
         * @param {function(ASTNodeWrapper):boolean} callback - function that gets instance of ASTNodeWrapper and has to return true of false
         * @return {Array.<ASTNodeWrapper>}
         */
        findAll: function(callback) {
            var results = [];
            if (callback(this)) {
                results.push(this);
            }
            for (var i = 0; i < this.children.length; i++) {
                results = results.concat(this.children[i].findAll(callback));
            }
            return results;
        },

        /**
         * Goes through its tree and collect all nodes that contains original node that are instances of the constructor
         *
         * @param {Function} constructor
         * @return {Array.<ASTNodeWrapper>}
         */
        findInstancesOf: function(constructor) {
            return this.findAll(function(node) {
                return node.instanceOf(constructor);
            });
        }
    };

    return ASTNodeWrapper;
});
