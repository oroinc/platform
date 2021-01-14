<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;

class LoadSearchProductData extends AbstractFixture
{
    /** @var array */
    const PRODUCTS = [
        ['name' => '220 Lumen Rechargeable Headlamp'],
        ['name' => '500-watt Work Light'],
        ['name' => 'Basic Women’s 4-Pocket Black Scrub Set'],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::PRODUCTS as $key => $productData) {
            $product = new Product();
            $product->setName($productData['name']);

            $manager->persist($product);

            $this->addReference(sprintf('test_product_%d', $key + 1), $product);
        }

        $manager->flush();
    }
}
