<?php

namespace Oro\Bundle\IntegrationBundle\Form\Choice;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;

class Loader extends ORMQueryBuilderLoader
{
    /**
     * @param EntityManager $em
     * @param array         $allowedTypes
     */
    public function __construct(EntityManager $em, array $allowedTypes)
    {
        $allowedTypes = is_array($allowedTypes) ? $allowedTypes : [$allowedTypes];
        $allowedTypes = array_unique($allowedTypes);

        $qb = $em->createQueryBuilder();
        $qb->select('i');
        $qb->from('OroIntegrationBundle:Channel', 'i');
        $qb->orderBy('i.name', 'ASC');

        if (!empty($allowedTypes)) {
            $qb->andWhere($qb->expr()->in('i.type', $allowedTypes));
        } else {
            $qb->andWhere('1 = 0');
        }

        parent::__construct($qb);
    }
}
