<?php

namespace Oro\Bundle\FormBundle\Event\FormHandler;

final class Events
{
    /**
     * This event fired before form data submit.
     */
    const BEFORE_FORM_SUBMIT = 'oro.form.update_handler.before_form_submit';

    /**
     * This event fired before form data set.
     */
    const BEFORE_FORM_DATA_SET = 'oro.form.update_handler.before_form_data_set';

    /**
     * This event fired after form submit, validate and persist to entity manager, but before flush.
     */
    const BEFORE_FLUSH = 'oro.form.update_handler.before_entity_flush';

    /**
     * This event fired after form submit, validate, persist and flush.
     */
    const AFTER_FLUSH = 'oro.form.update_handler.after_entity_flush';
}
