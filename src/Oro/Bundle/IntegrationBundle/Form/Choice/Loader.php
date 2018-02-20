<?php

namespace Oro\Bundle\IntegrationBundle\Form\Choice;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SecurityBundle\Form\ChoiceList\AclProtectedQueryBuilderLoader;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class Loader extends AclProtectedQueryBuilderLoader
{
    /**
     * @param AclHelper $aclHelper
     * @param EntityManager $em
     * @param array|null    $allowedTypes - integration types to include, null means that all types are allowed
     */
    public function __construct(AclHelper $aclHelper, EntityManager $em, array $allowedTypes = null)
    {
        $qb = $em->createQueryBuilder();
        $qb->select('i');
        $qb->from('OroIntegrationBundle:Channel', 'i');
        $qb->orderBy('i.name', 'ASC');

        if (null !== $allowedTypes) {
            $allowedTypes = is_array($allowedTypes) ? $allowedTypes : [$allowedTypes];
            $allowedTypes = array_unique($allowedTypes);

            if (!empty($allowedTypes)) {
                $qb->andWhere($qb->expr()->in('i.type', ':allowedTypes'))
                    ->setParameter('allowedTypes', $allowedTypes);
            } else {
                $qb->andWhere($qb->expr()->neq(true, true));
            }
        }

        parent::__construct($aclHelper, $qb);
    }
}
