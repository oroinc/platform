<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProductType;

class LoadRenamedFieldsTestData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $productType1 = new TestProductType();
        $productType1->setName('type1');
        $manager->persist($productType1);

        $productType2 = new TestProductType();
        $productType2->setName('type2');
        $manager->persist($productType2);

        $product1 = new TestProduct();
        $product1->setName('product 1');
        $product1->setProductType($productType1);
        $manager->persist($product1);

        $product2 = new TestProduct();
        $product2->setName('product 2');
        $product2->setProductType($productType2);
        $manager->persist($product2);

        $manager->flush();

        $this->setReference('test_product1', $product1);
        $this->setReference('test_product2', $product2);
    }
}
