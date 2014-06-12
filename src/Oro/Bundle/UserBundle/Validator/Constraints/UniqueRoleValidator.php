<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\UserBundle\Entity\Role;

class UniqueRoleValidator extends ConstraintValidator
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Received Doctrine Entity manager as DI(validator as service)
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * Check if role already exist, added violation to "label" field
     */
    public function validate($object, Constraint $constraint)
    {
        //Don't duplicate violation
        if (count($this->context->getViolations()) > 0) {
            return;
        }
        
        $sameObject = $this->em->getRepository('OroUserBundle:Role')->findOneByRole(strtoupper(Role::PREFIX_ROLE . trim(preg_replace('/[^\w\-]/i', '_', $object->getLabel()))));
        
        if ($sameObject && $object->getId() != $sameObject->getId()) {
            $this->context->addViolationAt(
                'label',
                $constraint->message,
                array('{{ role }}' => '"' . $object->getLabel() . '"')
            );
        }
    }
}
