import mediator from 'oroui/js/mediator';
import pageStateChecker from 'oronavigation/js/app/services/page-state-checker';

mediator.setHandler('isPageStateChanged', pageStateChecker.isStateChanged);
