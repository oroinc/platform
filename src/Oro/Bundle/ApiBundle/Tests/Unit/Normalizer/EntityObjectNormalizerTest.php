<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\EntityDataTransformer;
use Oro\Bundle\ApiBundle\Normalizer\DateTimeNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizerRegistry;
use Oro\Bundle\ApiBundle\Normalizer\SearchItemNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;

class EntityObjectNormalizerTest extends OrmRelatedTestCase
{
    /** @var ObjectNormalizer */
    protected $objectNormalizer;

    protected function setUp()
    {
        parent::setUp();

        $normalizers = new ObjectNormalizerRegistry();
        $this->objectNormalizer = new ObjectNormalizer(
            $normalizers,
            $this->doctrineHelper,
            new EntityDataAccessor(),
            new EntityDataTransformer($this->getMock('Symfony\Component\DependencyInjection\ContainerInterface'))
        );

        $normalizers->addNormalizer(
            new DateTimeNormalizer()
        );
        $normalizers->addNormalizer(
            new SearchItemNormalizer()
        );
    }

    public function testNormalizeSearchItem()
    {
        $searchItem = new SearchResultItem(
            $this->em,
            'Test\Entity',
            123,
            'test_title'
        );

        $result = $this->objectNormalizer->normalizeObject($searchItem);

        $this->assertEquals(
            [
                'id'     => 123,
                'entity' => 'Test\Entity',
                'title'  => 'test_title'
            ],
            $result
        );
    }

    public function testNormalizeSimpleEntity()
    {
        $entity = new Entity\Group();
        $entity->setId(123);
        $entity->setName('test_name');

        $result = $this->objectNormalizer->normalizeObject($entity);

        $this->assertEquals(
            [
                'id'   => 123,
                'name' => 'test_name'
            ],
            $result
        );
    }

    public function testNormalizeEntityWithNullToOneRelations()
    {
        $product = new Entity\Product();
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

    public function testNormalizeEntityWithToOneRelations()
    {
        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductEntity()
        );

        $this->assertEquals(
            [
                'id'        => 123,
                'name'      => 'product_name',
                'updatedAt' => new \DateTime('2015-12-01 10:20:30', new \DateTimeZone('UTC')),
                'category'  => 'category_name',
                'owner'     => 456
            ],
            $result
        );
    }

    public function testNormalizeEntityWithNullToManyRelations()
    {
        $user = new Entity\User();
        $user->setId(123);
        $user->setName('user_name');

        $result = $this->objectNormalizer->normalizeObject($user);

        $this->assertEquals(
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

    public function testNormalizeEntityWithToManyRelations()
    {
        $result = $this->objectNormalizer->normalizeObject(
            $this->createProductEntity()->getOwner()
        );

        $this->assertEquals(
            [
                'id'       => 456,
                'name'     => 'user_name',
                'category' => 'owner_category_name',
                'groups'   => [11, 22],
                'products' => [123],
                'owner'    => null
            ],
            $result
        );
    }

    /**
     * @return Entity\Product
     */
    protected function createProductEntity()
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
