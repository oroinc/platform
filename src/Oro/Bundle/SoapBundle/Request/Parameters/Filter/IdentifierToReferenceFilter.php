<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

class IdentifierToReferenceFilter implements ParameterFilterInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $entityFQCN;

    /**
     * @param ManagerRegistry $registry
     * @param string          $entityFQCN
     */
    public function __construct(ManagerRegistry $registry, $entityFQCN)
    {
        $this->registry   = $registry;
        $this->entityFQCN = $entityFQCN;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($rawValue, $operator)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass($this->entityFQCN);

        return $em->getReference($this->entityFQCN, $rawValue);
    }
}
