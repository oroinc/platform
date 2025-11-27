import _ from 'underscore';
import Backbone from 'backbone';
import Chaplin from 'chaplin';

const mediator = Backbone.mediator = Chaplin.mediator;

_.extend(mediator, Backbone.Events);
/**
 * Listen Id should be defined before Chaplin.mediator get sealed
 * on application start
 */
if (!mediator._listenId) {
    mediator._listenId = _.uniqueId('l');
    mediator._listeners = {};
}

/**
 * @export oroui/js/mediator
 * @name   oro.mediator
 */
export default mediator;
