<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

interface EmailTemplateAwareInterface
{
    /**
     * Get email template instance
     *
     * @return EmailTemplate
     */
    public function getTemplate();

    /**
     * Get class name of the target entity
     *
     * @return string
     */
    public function getEntityName();
}
