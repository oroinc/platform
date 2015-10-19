<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\TemplateFixture;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;

class EntityFieldFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return  'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->getEntityData('Example Entity Field');
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new FieldConfigModel();
    }

    /**
     * @param string  $key
     * @param FieldConfigModel $entity
     */
    public function fillEntityData($key, $entity)
    {
        $entity
            ->setType('BigInt')
            ->setFieldName('csvfield')
            ->setCreated(new \DateTime())
            ->setUpdated(new \DateTime())
            ->setEntity(new EntityConfigModel($this->getEntityClass()))
        ;
    }
}
