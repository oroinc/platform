Form Handler Events
-------------------

Form handler `Oro\Bundle\FormBundle\Model\UpdateHandler` triggers events that allow developer to modify processing data
or even stop form processing.

There are two type of events:

- **FormProcessEvent** (`Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent`) - triggered during form processing,
can stop further form processing via `interruptFormProcess` method.
- **AfterFormProcessEvent** (`Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent`) - triggered after form 
processing during saving of form data.

And there are four events triggered in form handler:

- **BEFORE_FORM_DATA_SET** (`Oro\Bundle\FormBundle\Event\FormHandler\Events::BEFORE_FORM_DATA_SET`) - uses 
FormProcessEvent, triggered in the very beginning of form processing right before input data set to form instance.
- **BEFORE_FORM_SUBMIT** (`Oro\Bundle\FormBundle\Event\FormHandler\Events::BEFORE_FORM_SUBMIT`) - uses FormProcessEvent,
triggered only for valid form methods (POST or PUT) before request submitting to form instance.
- **BEFORE_FLUSH** (`Oro\Bundle\FormBundle\Event\FormHandler\Events::BEFORE_FLUSH`) - uses AfterFormProcessEvent,
triggered right before flushing of object manager.
- **AFTER_FLUSH** (`Oro\Bundle\FormBundle\Event\FormHandler\Events::AFTER_FLUSH`) - uses AfterFormProcessEvent,
triggered right after flushing of object manager.

If developer wants to use custom form handler then he should trigger these events in custom handler 
to support form processing consistency.
