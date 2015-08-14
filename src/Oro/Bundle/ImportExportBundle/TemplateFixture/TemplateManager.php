<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

class TemplateManager
{
    /**
     * @var bool
     */
    protected $isFrozen;

    /**
     * @var TemplateEntityRegistry
     */
    protected $entityRegistry;

    /**
     * @var TemplateEntityRepositoryInterface[]
     */
    protected $repositories = [];

    /**
     * @param TemplateEntityRegistry $entityRegistry
     */
    public function __construct(TemplateEntityRegistry $entityRegistry)
    {
        $this->entityRegistry = $entityRegistry;
    }

    /**
     * @return TemplateEntityRegistry
     */
    public function getEntityRegistry()
    {
        return $this->entityRegistry;
    }

    /**
     * @param TemplateEntityRepositoryInterface $repository
     *
     * @throws LogicException
     */
    public function addEntityRepository(TemplateEntityRepositoryInterface $repository)
    {
        if ($this->isFrozen) {
            throw new LogicException(
                sprintf(
                    'The repository "%s" cannot be added to the frozen registry.',
                    get_class($repository)
                )
            );
        }

        $this->repositories[] = $repository;
    }

    /**
     * @param string $entityClass
     *
     * @return bool
     */
    public function hasEntityRepository($entityClass)
    {
        $this->ensureInitialized();

        return isset($this->repositories[$entityClass]);
    }

    /**
     * @param string $entityClass
     *
     * @return TemplateEntityRepositoryInterface
     *
     * @throws InvalidConfigurationException
     */
    public function getEntityRepository($entityClass)
    {
        if (!$this->hasEntityRepository($entityClass)) {
            // use a fixture which returns an empty entity if more concrete fixture is not registered
            $fixture = new EmptyFixture($entityClass);
            $fixture->setTemplateManager($this);
            $this->repositories[$entityClass] = $fixture;
        }

        return $this->repositories[$entityClass];
    }

    /**
     * @param string $entityClass
     *
     * @return bool
     */
    public function hasEntityFixture($entityClass)
    {
        return $this->hasEntityRepository($entityClass)
            ? $this->repositories[$entityClass] instanceof TemplateFixtureInterface
            : false;
    }

    /**
     * @param string $entityClass
     *
     * @return TemplateFixtureInterface|null
     *
     * @throws InvalidConfigurationException
     */
    public function getEntityFixture($entityClass)
    {
        if (!$this->hasEntityFixture($entityClass)) {
            // use a fixture which returns an empty entity if more concrete fixture is not registered
            $fixture = new EmptyFixture($entityClass);
            $fixture->setTemplateManager($this);
            $this->repositories[$entityClass] = $fixture;
        }

        return $this->repositories[$entityClass];
    }

    /**
     * Make sure that the fixtures were initialized
     */
    protected function ensureInitialized()
    {
        if (!$this->isFrozen) {
            $this->isFrozen = true;

            $repositories = [];
            foreach ($this->repositories as $repository) {
                if ($repository instanceof TemplateManagerAwareInterface) {
                    $repository->setTemplateManager($this);
                }
                $repositories[$repository->getEntityClass()] = $repository;
            }

            $this->repositories = $repositories;
        }
    }
}
