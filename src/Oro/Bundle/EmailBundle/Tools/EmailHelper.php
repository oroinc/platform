<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class EmailHelper
{
    /**
     * @var ServiceLink
     */
    protected $securityFacadeLink;

    /**
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(ServiceLink $securityFacadeLink)
    {
        $this->securityFacadeLink = $securityFacadeLink;
    }

    /**
     * @param Email $entity
     * @return bool
     */
    public function isEmailViewGranted(Email $entity)
    {
        return $this->isEmailActionGranted('VIEW', $entity);
    }

    /**
     * @param Email $entity
     * @return bool
     */
    public function isEmailEditGranted(Email $entity)
    {
        return $this->isEmailActionGranted('EDIT', $entity);
    }

    /**
     * @param string $action
     * @param Email $entity
     * @return bool
     */
    public function isEmailActionGranted($action, Email $entity)
    {
        $isGranted = false;
        foreach ($entity->getEmailUsers() as $emailUser) {
            if ($this->securityFacadeLink->getService()->isGranted($action, $emailUser)) {
                $isGranted = true;
                break;
            }
        }

        return $isGranted;
    }
}
