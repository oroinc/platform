<?php

namespace Oro\Bundle\EmailBundle\Placeholder;

use Oro\Bundle\EmailBundle\Entity\Email;
use Doctrine\Common\Util\ClassUtils;

class SendEmailPlaceholderFilter
{
    /** @var Email */
    protected $email;

    public function __construct() {
        $this->email  = new Email();
    }

    /**
     * Check if send email action is applicable to entity as activity
     *
     * @param object|null $entity
     * @return bool
     */
    public function isApplicable($entity = null)
    {
        return is_object($entity) && $this->email->supportActivityTarget(ClassUtils::getClass($entity));
    }
}
