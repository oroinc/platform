<?php

namespace Oro\Bundle\IntegrationBundle\Form\Choice;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;

class Loader extends ORMQueryBuilderLoader
{
    /**
     * @param EntityManager $em
     * @param array|null    $allowedTypes - integration types to include, null means that all types are allowed
     */
    public function __construct(EntityManager $em, array $allowedTypes = null)
    {
        $qb = $em->createQueryBuilder();
        $qb->select('i');
        $qb->from('OroIntegrationBundle:Channel', 'i');
        $qb->orderBy('i.name', 'ASC');

        if (null !== $allowedTypes) {
            $allowedTypes = is_array($allowedTypes) ? $allowedTypes : [$allowedTypes];
            $allowedTypes = array_unique($allowedTypes);

            if (!empty($allowedTypes)) {
                $qb->andWhere($qb->expr()->in('i.type', $allowedTypes));
            } else {
                $qb->andWhere('1 = 0');
            }
        }

        parent::__construct($qb);
    }
}
