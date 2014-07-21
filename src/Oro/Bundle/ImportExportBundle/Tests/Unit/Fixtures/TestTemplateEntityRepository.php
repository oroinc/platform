<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManagerAwareInterface;

class TestTemplateEntityRepository implements
    TemplateEntityRepositoryInterface,
    TemplateManagerAwareInterface
{
    protected $entityRegistry;

    public function getEntityClass()
    {
        return 'Test\Entity';
    }

    public function getEntity($key)
    {
        return null;
    }

    public function fillEntityData($key, $entity)
    {
    }

    public function setTemplateManager(TemplateManager $entityRegistry)
    {
        $this->entityRegistry = $entityRegistry;
    }

    public function getTemplateManager()
    {
        return $this->entityRegistry;
    }
}
