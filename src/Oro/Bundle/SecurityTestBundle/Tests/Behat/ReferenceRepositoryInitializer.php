<?php

namespace Oro\Bundle\SecurityTestBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        $entityConfigModelRepository = $doctrine->getManagerForClass(EntityConfigModel::class)
            ->getRepository(EntityConfigModel::class);
        /** @var EntityConfigModel $entityConfigModel */
        $entityConfigModel = $entityConfigModelRepository->findOneBy(['className' => Product::class]);

        $referenceRepository->set('entity_config_product', $entityConfigModel);

        $fieldConfigModelRepository = $doctrine->getManagerForClass(FieldConfigModel::class)
            ->getRepository(FieldConfigModel::class);
        foreach ($fieldConfigModelRepository->getAttributesByClass(Product::class) as $fieldConfigModel) {
            $referenceRepository->set('field_config_product_' . $fieldConfigModel->getFieldName(), $fieldConfigModel);
        }
    }
}
