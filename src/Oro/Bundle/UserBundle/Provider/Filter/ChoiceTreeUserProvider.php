<?php

namespace Oro\Bundle\UserBundle\Provider\Filter;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides users to be displayed in datagrid filters.
 */
class ChoiceTreeUserProvider
{
    private ManagerRegistry $doctrine;
    private AclHelper $aclHelper;
    private DQLNameFormatter $dqlNameFormatter;

    public function __construct(ManagerRegistry $doctrine, AclHelper $aclHelper, DQLNameFormatter $dqlNameFormatter)
    {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
        $this->dqlNameFormatter = $dqlNameFormatter;
    }

    /**
     * @return array [['id' => user ID, 'name' => user name], ...]
     */
    public function getList(): array
    {
        $qb = $this->createListQb();

        return $this->aclHelper->apply($qb)->getArrayResult();
    }

    public function shouldBeLazy(): bool
    {
        $qb = $this->createListQb()
            ->select('COUNT(1)');

        return $this->aclHelper->apply($qb)->getSingleScalarResult() >= 500;
    }

    private function createListQb(): QueryBuilder
    {
        return $this->doctrine->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u.id')
            ->addSelect(sprintf('%s AS name', $this->dqlNameFormatter->getFormattedNameDQL('u', User::class)));
    }
}
