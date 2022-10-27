import $ from 'jquery';
import error from 'oroui/js/error';

$(document).ajaxError(error.handle.bind(error));
