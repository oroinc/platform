<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Doctrine\ORM\EntityManager;

class BusinessUnitGridService
{
    /** @var EntityManager */
    protected $em;

    /** @var array */
    protected $choices;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Return filter choices for owner grid column
     *
     * @return array
     */
    public function getOwnerChoices()
    {
        return $this->getChoices('name', 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit');
    }

    /**
     * @param string $field
     * @param string $entity
     * @param string $alias
     *
     * @return array
     */
    protected function getChoices($field, $entity, $alias = 'bu')
    {
        $key = $entity . '|' . $field;
        if (!isset($this->choices[$key])) {
            $choices = $this->em
                ->getRepository('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
                ->getGridFilterChoices($field, $entity, $alias);
            $this->choices[$key] = array_flip($choices);
        }

        return $this->choices[$key];
    }
}
