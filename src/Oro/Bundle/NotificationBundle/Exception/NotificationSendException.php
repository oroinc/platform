<?php

namespace Oro\Bundle\NotificationBundle\Exception;

use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;

/**
 * This exception is raised if notification could not be send.
 */
class NotificationSendException extends \Exception
{
    /**
     * @param TemplateEmailNotificationInterface $notification
     */
    public function __construct(TemplateEmailNotificationInterface $notification)
    {
        $criteria = $notification->getTemplateCriteria();
        $message = sprintf(
            'Could not send notification of type "%s" for email template "%s"',
            \get_class($notification),
            $criteria->getName()
        );

        if ($criteria->getEntityName()) {
            $message = sprintf('%s for "%s" entity', $message, $criteria->getEntityName());
        } else {
            $message = sprintf('%s and without entity', $message);
        }

        parent::__construct($message);
    }
}
