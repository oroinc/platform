<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class FieldFilter
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * @param array|object $entity
     * @param string       $className
     *
     * @return array
     */
    public function filterRestrictedFields(&$entity, $className)
    {
        //$this->securityFacade->getToken()->ge
        foreach ($entity as $fieldName => $value) {
            $isGranted = $this->securityFacade->isGranted('VIEW', new FieldVote('Entity:' . $className, $fieldName));
            if (!$isGranted) {
                $this->propertyAccessor->remove($entity, $fieldName);
            }
        }
    }
}
