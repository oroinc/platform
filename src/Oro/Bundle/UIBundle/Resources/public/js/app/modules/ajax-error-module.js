import $ from 'jquery';
import error from 'oroui/js/error';

$(document).on('ajaxError', error.handle.bind(error));
