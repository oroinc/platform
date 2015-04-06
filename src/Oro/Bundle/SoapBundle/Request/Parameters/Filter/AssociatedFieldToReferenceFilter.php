<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

class AssociatedFieldToReferenceFilter implements ParameterFilterInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $entityFQCN;

    /** @var string */
    protected $association;

    /** @var null|string */
    private $field;

    /**
     * @param ManagerRegistry $registry
     * @param string          $entityFQCN
     * @param string          $association
     * @param null|string     $field
     */
    public function __construct(ManagerRegistry $registry, $entityFQCN, $association, $field)
    {
        $this->registry    = $registry;
        $this->entityFQCN  = $entityFQCN;
        $this->association = $association;
        $this->field       = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($rawValue, $operator)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManager();
        $repository = $em->getRepository($this->entityFQCN);
        $query      = $repository->createQueryBuilder('e')
            ->select('e.id')
            ->join('e.' . $this->association, 'ae')
            ->where('ae.' . $this->field . ' = :value')
            ->setParameter('value', $rawValue)
            ->getQuery();

        $result = $query->getResult();
        if (empty($result)) {
            $result = [['id' => 0]];
        }

        return $result;
    }
}
