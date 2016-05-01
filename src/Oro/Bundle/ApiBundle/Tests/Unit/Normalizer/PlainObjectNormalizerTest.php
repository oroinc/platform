<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\EntityDataTransformer;
use Oro\Bundle\ApiBundle\Normalizer\DateTimeNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity as Object;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class PlainObjectNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectNormalizer */
    protected $objectNormalizer;

    protected function setUp()
    {
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn(null);

        $normalizers = new ObjectNormalizerRegistry();
        $this->objectNormalizer = new ObjectNormalizer(
            $normalizers,
            new DoctrineHelper($doctrine),
            new EntityDataAccessor(),
            new EntityDataTransformer($this->getMock('Symfony\Component\DependencyInjection\ContainerInterface'))
        );

        $normalizers->addNormalizer(
            new DateTimeNormalizer()
        );
    }

    public function testNormalizeSimpleObject()
    {
        $object = new Object\Group();
        $object->setId(123);
        $object->setName('test_name');

        $result = $this->objectNormalizer->normalizeObject($object);

        $this->assertEquals(
            [
                'id'   => 123,
                'name' => 'test_name'
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullToOneRelations()
    {
        $product = new Object\Product();
        $product->setId(123);
        $product->setName('product_name');

        $result = $this->objectNormalizer->normalizeObject($product);

        $this->assertEquals(
            [
                'id'        => 123,
                'name'      => 'product_name',
                'updatedAt' => null,
                'category'  => null,
                'owner'     => null
            ],
            $result
        );
    }

    public function testNormalizeObjectWithToOneRelations()
    {
        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductObject()
        );

        $this->assertEquals(
            [
                'id'        => 123,
                'name'      => 'product_name',
                'updatedAt' => new \DateTime('2015-12-01 10:20:30', new \DateTimeZone('UTC')),
                'category'  => 'category_name',
                'owner'     => 'user_name'
            ],
            $result
        );
    }

    public function testNormalizeObjectWithNullToManyRelations()
    {
        $user = new Object\User();
        $user->setId(123);
        $user->setName('user_name');

        $result = $this->objectNormalizer->normalizeObject($user);

        $this->assertEquals(
            [
                'id'       => 123,
                'name'     => 'user_name',
                'category' => null,
                'groups'   => [],
                'products' => []
            ],
            $result
        );
    }

    public function testNormalizeObjectWithToManyRelations()
    {
        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductObject()->getOwner()
        );

        $this->assertEquals(
            [
                'id'       => 456,
                'name'     => 'user_name',
                'category' => 'owner_category_name',
                'groups'   => ['owner_group1', 'owner_group2'],
                'products' => ['product_name']
            ],
            $result
        );
    }

    /**
     * @return Object\Product
     */
    protected function createProductObject()
    {
        $product = new Object\Product();
        $product->setId(123);
        $product->setName('product_name');
        $product->setUpdatedAt(new \DateTime('2015-12-01 10:20:30', new \DateTimeZone('UTC')));

        $category = new Object\Category('category_name');
        $category->setLabel('category_label');
        $product->setCategory($category);

        $owner = new Object\User();
        $owner->setId(456);
        $owner->setName('user_name');
        $ownerCategory = new Object\Category('owner_category_name');
        $ownerCategory->setLabel('owner_category_label');
        $owner->setCategory($ownerCategory);
        $ownerGroup1 = new Object\Group();
        $ownerGroup1->setId(11);
        $ownerGroup1->setName('owner_group1');
        $owner->addGroup($ownerGroup1);
        $ownerGroup2 = new Object\Group();
        $ownerGroup2->setId(22);
        $ownerGroup2->setName('owner_group2');
        $owner->addGroup($ownerGroup2);
        $owner->addProduct($product);

        return $product;
    }
}
