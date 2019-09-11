<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides attribute families for given entity class.
 */
class AttributeFamilyProvider
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var AclHelper */
    private $aclHelper;

    /** @var array */
    private $families = [];

    /**
     * @param ManagerRegistry $doctrine
     * @param AclHelper $aclHelper
     */
    public function __construct(ManagerRegistry $doctrine, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param string $entityClass
     * @return array
     */
    public function getAvailableAttributeFamilies(string $entityClass): array
    {
        if (!isset($this->families[$entityClass])) {
            $qb = $this->doctrine->getRepository(AttributeFamily::class)
                ->getFamiliesByEntityClassQueryBuilder($entityClass);
            /** @var AttributeFamily[] $families */
            $families = $this->aclHelper->apply($qb)->getResult();

            $this->families[$entityClass] = [];
            foreach ($families as $family) {
                $this->families[$entityClass][(string) $family] = $family->getId();
            }
        }

        return $this->families[$entityClass];
    }
}
