<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

/**
 * The template fixture is used for the cases when more concrete fixture for some entity type
 * is not registered in {@see TemplateManager}
 */
class EmptyFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /** @var string */
    protected $entityClass;

    /**
     * @param $entityClass
     */
    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->getEntityData('default');
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new $this->entityClass();
    }

    /**
     * {@inheritdoc}
     */
    public function fillEntityData($key, $entity)
    {
        // keep entity not filled
    }
}
