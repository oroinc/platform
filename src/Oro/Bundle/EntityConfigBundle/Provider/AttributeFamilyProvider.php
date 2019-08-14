<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;

/**
 * Provides attribute families for given entity class.
 */
class AttributeFamilyProvider
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    private $families = [];

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param string $entityClass
     * @return array
     */
    public function getAvailableAttributeFamilies(string $entityClass): array
    {
        if (!isset($this->families[$entityClass])) {
            /** @var AttributeFamily[] $families */
            $families = $this->doctrine->getRepository(AttributeFamily::class)
                ->findBy(['entityClass' => $entityClass]);

            $this->families[$entityClass] = [];
            foreach ($families as $family) {
                $this->families[$entityClass][(string) $family] = $family->getId();
            }
        }

        return $this->families[$entityClass];
    }
}
