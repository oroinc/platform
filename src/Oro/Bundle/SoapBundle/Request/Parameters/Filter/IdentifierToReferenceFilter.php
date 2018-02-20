<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

class IdentifierToReferenceFilter implements ParameterFilterInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $entityFQCN;

    /** @var null|string */
    private $field;

    /**
     * @param ManagerRegistry $registry
     * @param string          $entityFQCN
     * @param null|string     $field
     */
    public function __construct(ManagerRegistry $registry, $entityFQCN, $field = null)
    {
        $this->registry   = $registry;
        $this->entityFQCN = $entityFQCN;
        $this->field      = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($rawValue, $operator)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass($this->entityFQCN);

        if (null === $this->field) {
            return $em->getReference($this->entityFQCN, $rawValue);
        } else {
            return $em->getRepository($this->entityFQCN)->findOneBy([$this->field => $rawValue]);
        }
    }
}
