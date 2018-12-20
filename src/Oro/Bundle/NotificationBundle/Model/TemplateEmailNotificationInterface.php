<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;

/**
 * Provides a way to get email template conditions and recipient objects to receive the correct templates
 */
interface TemplateEmailNotificationInterface
{
    /**
     * @return EmailTemplateCriteria
     */
    public function getTemplateCriteria(): EmailTemplateCriteria;

    /**
     * @return iterable|EmailHolderInterface[]
     */
    public function getRecipients(): iterable;

    /**
     * @return object
     */
    public function getEntity();
}
