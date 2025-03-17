<?php

namespace Oro\Bundle\IntegrationBundle\Form\Choice;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Form\ChoiceList\AclProtectedQueryBuilderLoader;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * The query builder loader for integration channels.
 */
class Loader extends AclProtectedQueryBuilderLoader
{
    public function __construct(AclHelper $aclHelper, EntityManagerInterface $em, ?array $allowedTypes = null)
    {
        $qb = $em->createQueryBuilder()
            ->select('i')
            ->from(Channel::class, 'i')
            ->orderBy('i.name', 'ASC');

        if (null !== $allowedTypes) {
            if (!\is_array($allowedTypes)) {
                $allowedTypes = [$allowedTypes];
            }
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
