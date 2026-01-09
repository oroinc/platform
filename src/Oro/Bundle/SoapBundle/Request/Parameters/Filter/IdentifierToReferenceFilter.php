<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Filters request parameters by converting identifiers to entity references or instances.
 *
 * Converts entity identifiers to either lazy-loaded entity references or fully loaded
 * entity instances. Supports filtering by a specific field when provided, allowing
 * lookups by alternative identifiers beyond the primary key.
 */
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

    #[\Override]
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
