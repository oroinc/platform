<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;


use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\ApiBundle\Processor\Delete\LoadData;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class LoadDataTest extends OrmRelatedTestCase
{
    /** @var LoadData */
    protected $processor;

    /** @var DeleteContext */
    protected $context;

    public function setUp()
    {
        parent::setUp();
        $this->processor = new LoadData($this->doctrineHelper);
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new DeleteContext($configProvider, $metadataProvider);
    }

    public function testProcess()
    {
        $class = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $id = 12;

        $object = new Product();
        $object->setId($id);

        $this->context->setClassName($class);
        $this->context->setId($id);
        $this->doctrineHelper->getEntityRepositoryForClass($class)->data = [12 => $object];

        $this->processor->process($this->context);

        $this->assertSame($object, $this->context->getObject());
    }

    public function testProcessForCompositeIdEntity()
    {
        $class = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity';
        $id = 25;
        $title = 'test';
        $compositeId = ['id' => $id, 'title' => $title];

        $object = new CompositeKeyEntity();
        $object->setId($id);
        $object->setTitle($title);

        $this->context->setClassName($class);
        $this->context->setId($compositeId);
        $this->doctrineHelper->getEntityRepositoryForClass($class)->data = [implode('|', $compositeId) => $object];

        $this->processor->process($this->context);

        $this->assertSame($object, $this->context->getObject());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The entity identifier cannot be an array because the entity "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product" has single primary key.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessOnWrongIdForEntity()
    {
        $class = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $id = 12;
        $this->context->setClassName($class);
        $this->context->setId([$id]);

        $this->processor->process($this->context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The entity identifier must be an array because the entity "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity" has composite primary key.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWithNonArrayIdForCombinedKayEntity()
    {
        $class = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity';
        $id = 23;
        $this->context->setClassName($class);
        $this->context->setId($id);

        $this->processor->process($this->context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The entity identifier array must have the key "title" because the entity "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity" has composite primary key.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWithWrongIdForCombinedKayEntity()
    {
        $class = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity';
        $id = 23;
        $this->context->setClassName($class);
        $this->context->setId(['id' => $id]);

        $this->processor->process($this->context);
    }
}
