class Interface {
    /**
     * @param {Object.<string, function>} methods object with dummy methods, names and arguments number are checked
     */
    constructor(methods = {}) {
        this.methods = methods;
    }

    /**
     * Check passed prototype or an instance to have expected methods
     *
     * @param {object} obj prototype or an instance
     */
    expectToBeImplementedBy(obj) {
        const missingMethods = Object.keys(this.methods)
            .filter(name => typeof obj[name] !== 'function' || obj[name].length !== this.methods[name].length)
            .map(name => '`' + name + '`');
        if (missingMethods.length !== 0) {
            let message;
            if (missingMethods.length > 1) {
                message = `Methods ${missingMethods.join(', ')} are required.`;
            } else {
                message = `Method ${missingMethods[0]} is required.`;
            }
            throw new TypeError(message);
        }
    }
}

export default Interface;
