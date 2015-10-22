<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\TemplateFixture;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;

class EntityFieldFixture implements TemplateFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($key)
    {
        return new FieldConfigModel();
    }

    /**
     * {@inheritdoc}
     */
    public function fillEntityData($key, $entity)
    {
        $entity
            ->setType('BigInt')
            ->setFieldName('csvfield');
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $entity = new FieldConfigModel();
        $this->fillEntityData(null, $entity);

        return new \ArrayIterator([$entity, $entity]);
    }
}
