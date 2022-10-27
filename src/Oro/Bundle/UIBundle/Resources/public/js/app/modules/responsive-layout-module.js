import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import responsiveLayout from 'oroui/js/responsive-layout';

mediator.setHandler('responsive-layout:update', _.debounce(responsiveLayout.update));
