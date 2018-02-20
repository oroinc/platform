<?php

namespace Oro\Bundle\EmailBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;

class SendEmailPlaceholderFilter
{
    /** @var Email */
    protected $email;

    /**
     * @var ActivityManager
     */
    protected $activityManager;

    /**
     * @param ActivityManager $activityManager
     */
    public function __construct(ActivityManager $activityManager)
    {
        $this->email  = new Email();
        $this->activityManager = $activityManager;
    }

    /**
     * Check if send email action is applicable to entity as activity
     *
     * @param object|null $entity
     * @return bool
     */
    public function isApplicable($entity = null)
    {
        return
            is_object($entity) &&
            $this->email->supportActivityTarget(ClassUtils::getClass($entity)) &&
            $this->activityManager->hasActivityAssociation(
                ClassUtils::getClass($entity),
                ClassUtils::getClass($this->email)
            );
    }
}
