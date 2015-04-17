<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

/**
 * Factory is intended to provide flexible way to extend form models
 *
 * Class Factory
 * @package Oro\Bundle\EmailBundle\Form\Model
 */
class Factory
{
    /**
     * @return Email
     */
    public function getEmail()
    {
        return new Email();
    }

    /**
     * @return EmailAttachment
     */
    public function getEmailAttachment()
    {
        return new EmailAttachment();
    }
}
