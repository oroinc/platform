export default function(child, _super) {
    for (const p in _super) {
        if (_super.hasOwnProperty(p)) {
            child[p] = _super[p];
        }
    }
    function C() {
        this.constructor = child;
    }
    child.prototype = _super === null ? Object.create(_super) : (C.prototype = _super.prototype, new C());
};
