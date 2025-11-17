function EntityError(message) {
    this.message = message;
}

EntityError.prototype = Object.create(Error.prototype);
EntityError.prototype.constructor = EntityError;
EntityError.prototype.name = 'EntityError';

export default EntityError;
