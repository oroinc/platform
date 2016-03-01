define(function() {
    'use strict';
    function AbstractLocationDirective() {
    }
    AbstractLocationDirective.prototype.getRecommendedPosition = function() {
        throw new Error('That\'s abstract method');
    };
    return AbstractLocationDirective;
});
