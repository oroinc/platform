class ASTNodeWrapper {
    /**
     * @param {Node} node - instance of Node from ExpressionLanguage library
     * @param {ASTNodeWrapper?} parent - instance of ASTNodeWrapper that contain parent Node
     */
    constructor(node, parent = null) {
        /** @type {ASTNodeWrapper} */
        this.parent = parent;
        /** @type {Node} */
        this.originNode = node;
        /** @type {Array.<ASTNodeWrapper>} */
        this.children = node.nodes.map(child => new ASTNodeWrapper(child, this));
    }

    /**
     * Returns original expression language node
     *
     * @return {Node}
     */
    origin() {
        return this.originNode;
    }

    /**
     * @param {string} name - name of attribute of original node
     * @return {string|number}
     */
    attr(name) {
        return this.originNode.attrs[name];
    }

    /**
     * @param {number} index
     * @return {ASTNodeWrapper}
     */
    child(index) {
        return this.children[index];
    }

    /**
     * Checks if its original node is instance of received constructors
     *
     * @param {...Function} constructors
     * @return {boolean}
     */
    instanceOf(...constructors) {
        return constructors.some(constructor => this.originNode instanceof constructor);
    }

    /**
     * Goes through its tree and collect all nodes that returns true from the callback
     *
     * @param {function(ASTNodeWrapper):boolean} callback - function that gets instance of ASTNodeWrapper and has to return true of false
     * @return {Array.<ASTNodeWrapper>}
     */
    findAll(callback) {
        let results = [];
        if (callback(this)) {
            results.push(this);
        }
        for (let i = 0; i < this.children.length; i++) {
            results = results.concat(this.children[i].findAll(callback));
        }
        return results;
    }

    /**
     * Goes through its tree and collect all nodes that contains original node that are instances of the constructor
     *
     * @param {Function} constructor
     * @return {Array.<ASTNodeWrapper>}
     */
    findInstancesOf(constructor) {
        return this.findAll(node => node.instanceOf(constructor));
    }
}

export default ASTNodeWrapper;
