define(function() {
    'use strict';
    return function(child, _super) {
        for (var p in _super) {
            if (_super.hasOwnProperty(p)) {
                child[p] = _super[p];
            }
        }
        function C() {
            this.constructor = child;
        }
        child.prototype = _super === null ? Object.create(_super) : (C.prototype = _super.prototype, new C());
    };
});
