<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Normalizer\ConfigNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\DateTimeNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityDataAccessor;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\EntityDataTransformer;
use Oro\Component\EntitySerializer\SerializationHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PlainObjectNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectNormalizer */
    private $objectNormalizer;

    protected function setUp()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn(null);

        $normalizers = new ObjectNormalizerRegistry();
        $this->objectNormalizer = new ObjectNormalizer(
            $normalizers,
            new DoctrineHelper($doctrine),
            new SerializationHelper(
                new EntityDataTransformer($this->createMock(ContainerInterface::class))
            ),
            new EntityDataAccessor(),
            new ConfigNormalizer(),
            new DataNormalizer()
        );

        $normalizers->addNormalizer(
            new DateTimeNormalizer()
        );
    }

    public function testNormalizeSimpleObject()
    {
        $object = new Entity\Group();
        $object->setId(123);
        $object->setName('test_name');

        $result = $this->objectNormalizer->normalizeObject($object);

        self::assertEquals(
            [
                'id'   => 123,
                'name' => 'test_name'
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullToOneRelations()
    {
        $product = new Entity\Product();
        $product->setId(123);
        $product->setName('product_name');

        $result = $this->objectNormalizer->normalizeObject($product);

        self::assertEquals(
            [
                'id'            => 123,
                'name'          => 'product_name',
                'updatedAt'     => null,
                'category'      => null,
                'owner'         => null,
                'price'         => null,
                'priceValue'    => null,
                'priceCurrency' => null
            ],
            $result
        );
    }

    public function testNormalizeObjectWithToOneRelations()
    {
        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductObject()
        );

        self::assertEquals(
            [
                'id'            => 123,
                'name'          => 'product_name',
                'updatedAt'     => new \DateTime('2015-12-01 10:20:30', new \DateTimeZone('UTC')),
                'category'      => 'category_name',
                'owner'         => 'user_name',
                'price'         => null,
                'priceValue'    => null,
                'priceCurrency' => null
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullToManyRelations()
    {
        $user = new Entity\User();
        $user->setId(123);
        $user->setName('user_name');

        $result = $this->objectNormalizer->normalizeObject($user);

        self::assertEquals(
            [
                'id'       => 123,
                'name'     => 'user_name',
                'category' => null,
                'groups'   => [],
                'products' => [],
                'owner'    => null
            ],
            $result
        );
    }

    public function testNormalizeObjectWithToManyRelations()
    {
        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductObject()->getOwner()
        );

        self::assertEquals(
            [
                'id'       => 456,
                'name'     => 'user_name',
                'category' => 'owner_category_name',
                'groups'   => ['owner_group1', 'owner_group2'],
                'products' => ['product_name'],
                'owner'    => null
            ],
            $result
        );
    }

    /**
     * @return Entity\Product
     */
    protected function createProductObject()
    {
        $product = new Entity\Product();
        $product->setId(123);
        $product->setName('product_name');
        $product->setUpdatedAt(new \DateTime('2015-12-01 10:20:30', new \DateTimeZone('UTC')));

        $category = new Entity\Category('category_name');
        $category->setLabel('category_label');
        $product->setCategory($category);

        $owner = new Entity\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $ownerCategory = new Entity\Category('owner_category_name');
        $ownerCategory->setLabel('owner_category_label');
        $owner->setCategory($ownerCategory);
        $ownerGroup1 = new Entity\Group();
        $ownerGroup1->setId(11);
        $ownerGroup1->setName('owner_group1');
        $owner->addGroup($ownerGroup1);
        $ownerGroup2 = new Entity\Group();
        $ownerGroup2->setId(22);
        $ownerGroup2->setName('owner_group2');
        $owner->addGroup($ownerGroup2);
        $owner->addProduct($product);

        return $product;
    }
}
