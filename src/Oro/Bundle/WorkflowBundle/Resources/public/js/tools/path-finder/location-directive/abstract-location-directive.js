function AbstractLocationDirective() {
}
AbstractLocationDirective.prototype.getRecommendedPosition = function() {
    throw new Error('That\'s abstract method');
};
export default AbstractLocationDirective;
