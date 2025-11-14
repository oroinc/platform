import Select2Handler from 'oroform/js/app/views/validation-message-handler/select2-validation-message-handler-view';
import DateHandler from 'oroform/js/app/views/validation-message-handler/date-validation-message-handler-view';
import TimeHandler from 'oroform/js/app/views/validation-message-handler/time-validation-message-handler-view';
import DateTimeHandler from 'oroform/js/app/views/validation-message-handler/datetime-validation-message-handler-view';
import InputHandler from 'oroform/js/app/views/validation-message-handler/input-validation-message-handler-view';

const ValidationMessageHandlers = [
    Select2Handler,
    DateHandler,
    TimeHandler,
    DateTimeHandler,
    InputHandler
];

export default ValidationMessageHandlers;
