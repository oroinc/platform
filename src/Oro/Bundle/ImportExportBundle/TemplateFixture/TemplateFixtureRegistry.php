<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

class TemplateFixtureRegistry
{
    /**
     * @var TemplateFixtureInterface[]
     */
    protected $entityFixtures = array();

    /**
     * @param string $entityClass
     * @param TemplateFixtureInterface $fixture
     */
    public function addEntityFixture($entityClass, TemplateFixtureInterface $fixture)
    {
        $this->entityFixtures[$entityClass] = $fixture;
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function hasEntityFixture($entityClass)
    {
        return array_key_exists($entityClass, $this->entityFixtures);
    }

    /**
     * @param string $entityClass
     * @return null|TemplateFixtureInterface
     */
    public function getEntityFixture($entityClass)
    {
        if ($this->hasEntityFixture($entityClass)) {
            return $this->entityFixtures[$entityClass];
        }

        return null;
    }
}
