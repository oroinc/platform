<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityFieldExtensionTest extends WebTestCase
{
    use EntityExtendTransportTrait;

    private EntityFieldExtension $entityFieldExtension;

    public function setUp(): void
    {
        self::bootKernel();
        $this->entityFieldExtension = new EntityFieldExtension();
    }

    public function testGetPropertyDoesNotExists(): void
    {
        $transport = $this->createTransport(TestExtendedEntity::class);
        $transport->setName('undefined_property');

        $this->entityFieldExtension->get($transport);
        self::assertFalse($transport->isProcessed());
        self::assertNull($transport->getResult());
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGetPropertyExists(
        object $object,
        string $name,
        bool $isProcessed,
        mixed $result
    ): void {
        $transport = $this->createTransport($object);
        $transport->setName($name);

        $this->entityFieldExtension->get($transport);
        self::assertSame($isProcessed, $transport->isProcessed());
        self::assertEquals($result, $transport->getResult());
    }


    public function getDataProvider(): array
    {
        $testExtendEntity1 = new TestExtendedEntity();
        $testName = 'TestName';
        $testExtendEntity1->getStorage()->offsetSet('name', $testName);
        return [
            'get extended property default' => [
                'class' => new TestExtendedEntity(),
                'name' => 'name',
                'isProcessed' => true,
                'result' => null
            ],
            'get extended property with data' => [
                'class' => $testExtendEntity1,
                'name' => 'name',
                'isProcessed' => true,
                'result' => $testName
            ],
            'get real property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'id',
                'isProcessed' => true,
                'result' => null
            ],
            'get relation biM2OOwners property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'biM2OOwners',
                'isProcessed' => true,
                'result' => new ArrayCollection()
            ],
            'get relation biM2MOwners property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'biM2MOwners',
                'isProcessed' => true,
                'result' => new ArrayCollection()
            ],
            'get relation biO2MOwner property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'biO2MOwner',
                'isProcessed' => true,
                'result' => null
            ],
            'get target testentity5_uniO2MTargets property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'testentity5_uniO2MTargets',
                'isProcessed' => true,
                'result' => null
            ],
            'get undefined property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'undefined_property',
                'isProcessed' => false,
                'result' => null
            ],
        ];
    }

    /**
     * @dataProvider setDataProvider
     */
    public function testSet(
        object $object,
        string $name,
        mixed $value,
        bool $isProcessed,
        mixed $result
    ): void {
        $transport = $this->createTransport($object);
        $transport->setName($name);
        $transport->setValue($value);

        $this->entityFieldExtension->set($transport);
        self::assertSame($isProcessed, $transport->isProcessed());
        self::assertEquals($result, $transport->getStorage()->offsetGet($name));
    }

    public function setDataProvider(): array
    {
        return [
            'set extended property with data' => [
                'class' => new TestExtendedEntity(),
                'name' => 'name',
                'value' => 'TestVal1',
                'isProcessed' => true,
                'result' => 'TestVal1'
            ],
            'set extended property default' => [
                'class' => new TestExtendedEntity(),
                'name' => 'name',
                'value' => null,
                'isProcessed' => true,
                'result' => null
            ],
            'set relation biM2OOwners property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'biM2OOwners',
                'value' => null,
                'isProcessed' => true,
                'result' => new ArrayCollection()
            ],
            'set relation biM2MOwners property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'biM2MOwners',
                'value' => null,
                'isProcessed' => true,
                'result' => new ArrayCollection()
            ],
            'set relation biO2MOwner property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'biO2MOwner',
                'value' => 'TestOwner',
                'isProcessed' => true,
                'result' => 'TestOwner'
            ],
            'set target testentity5_uniO2MTargets property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'testentity5_uniO2MTargets',
                'value' => 'testentity5Target',
                'isProcessed' => true,
                'result' => 'testentity5Target'
            ],
        ];
    }

    public function testSetUndefinedProperty(): void
    {
        // real property
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('id');
        $transport->setValue(null);

        $this->entityFieldExtension->set($transport);
        self::assertFalse($transport->isProcessed());

        // undefined property
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('undefined property');
        $transport->setValue(null);

        $this->entityFieldExtension->set($transport);
        self::assertFalse($transport->isProcessed());
    }

    /**
     * @dataProvider callDataProvider
     */
    public function testCallGet(
        object $object,
        string $name,
        array $arguments,
        bool $isProcessed,
        mixed $result
    ): void {
        $transport = $this->createTransport($object);
        $transport->setName($name);
        $transport->setArguments($arguments);

        $this->entityFieldExtension->call($transport);
        self::assertSame($isProcessed, $transport->isProcessed());
        self::assertEquals($result, $transport->getResult());
    }

    public function callDataProvider(): array
    {
        return [
            'call get extended property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'getName',
                'arguments' => [],
                'isProcessed' => true,
                'result' => null
            ],
            'call get serialized data' => [
                'class' => new TestExtendedEntity(),
                'name' => 'getSerializedData',
                'arguments' => [],
                'isProcessed' => true,
                'result' => null
            ],
            'call get extend relation property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'getBiM2MOwners',
                'arguments' => [],
                'isProcessed' => true,
                'result' => new ArrayCollection()
            ],
            'call get extend target property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'getTestentity5UniO2MTargets',
                'arguments' => [],
                'isProcessed' => true,
                'result' => null
            ],
        ];
    }

    /**
     * @dataProvider callSetDataProvider
     */
    public function testCallSet(
        object $object,
        string $name,
        mixed $value,
        bool $isProcessed,
        mixed $result
    ): void {
        $transport = $this->createTransport($object);
        $transport->setName($name);
        $transport->setArguments([$value]);

        $this->entityFieldExtension->call($transport);
        self::assertSame($isProcessed, $transport->isProcessed());
        self::assertEquals($result['value'], $transport->getStorage()->offsetGet($result['key']));
    }

    public function callSetDataProvider(): array
    {
        return [
            'call set extended property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'setName',
                'value' => 'TestName1',
                'isProcessed' => true,
                'result' => [
                    'key' => 'name',
                    'value' => 'TestName1'
                ]
            ],
            'call set serialized data' => [
                'class' => new TestExtendedEntity(),
                'name' => 'setSerializedData',
                'value' => ['TestData'],
                'isProcessed' => true,
                'result' => [
                    'key' => 'serialized_data',
                    'value' => ['TestData']
                ]
            ],
            'call set extend relation property' => [
                'class' => new TestExtendedEntity(),
                'name' => 'setBiM2MOwners',
                'value' => new ArrayCollection(),
                'isProcessed' => true,
                'result' => [
                    'key' => 'biM2MOwners',
                    'value' => new ArrayCollection()
                ]
            ],
        ];
    }

    /**
     * @dataProvider callRemoveDataProvider
     */
    public function testCallRemove(
        object $object,
        string $name,
        mixed $value,
        bool $isProcessed,
    ): void {
        $transport = $this->createTransport($object);
        $transport->setName($name);
        $transport->setArguments([$value]);

        $this->entityFieldExtension->call($transport);
        self::assertSame($isProcessed, $transport->isProcessed());
    }

    public function callRemoveDataProvider(): array
    {
        return [
            'call remove extended BiM2OOwner' => [
                'class' => new TestExtendedEntity(),
                'name' => 'removeBiM2OOwner',
                'value' => new ArrayCollection(),
                'isProcessed' => true,
            ],
            'call remove extend BiM2MOwner' => [
                'class' => new TestExtendedEntity(),
                'name' => 'removeBiM2MOwner',
                'value' => new ArrayCollection(),
                'isProcessed' => true,
            ],
            'call remove undefined method' => [
                'class' => new TestExtendedEntity(),
                'name' => 'removeUndefinedMethod',
                'value' => new ArrayCollection(),
                'isProcessed' => false,
            ],
        ];
    }

    public function testIssetRealProperty(): void
    {
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('id');

        $this->entityFieldExtension->isset($transport);
        self::assertFalse($transport->isProcessed());
        self::assertNull($transport->getResult());
    }

    public function testIssetExtendedProperty(): void
    {
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('name');

        $this->entityFieldExtension->isset($transport);
        self::assertTrue($transport->isProcessed());
        self::assertTrue($transport->getResult());
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testMethodExists(
        string $class,
        string $name,
        bool $isProcessed,
        mixed $result
    ): void {
        $transport = $this->createTransport($class);
        $transport->setName($name);

        $this->entityFieldExtension->methodExists($transport);
        self::assertSame($isProcessed, $transport->isProcessed());
        self::assertSame($result, $transport->getResult());
    }

    public function methodsDataProvider(): array
    {
        return [
            'check if exists remove extended BiM2OOwner' => [
                'class' => TestExtendedEntity::class,
                'name' => 'removeBiM2OOwner',
                'isProcessed' => true,
                'result' => true,
            ],
            'check if exists remove extend BiM2MOwner' => [
                'class' => TestExtendedEntity::class,
                'name' => 'removeBiM2MOwner',
                'isProcessed' => true,
                'result' => true,
            ],
            'check if exists remove undefined method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'removeUndefinedMethod',
                'isProcessed' => false,
                'result' => null,
            ],
            'check if exists setName method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'setName',
                'isProcessed' => true,
                'result' => true,
            ],
            'check if exists setSerializedData method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'setSerializedData',
                'isProcessed' => true,
                'result' => true,
            ],
            'check if exists getBiM2MOwners method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getBiM2MOwners',
                'isProcessed' => true,
                'result' => true,
            ],
            'check if exists addBiM2OOwner method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'addBiM2OOwner',
                'isProcessed' => true,
                'result' => true,
            ],
            'check if exists getDefaultBiM2OOwners method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getDefaultBiM2OOwners',
                'isProcessed' => true,
                'result' => true,
            ],
            'check if exists real id method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getId',
                'isProcessed' => false,
                'result' => null,
            ],
            'check if exists undefined method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getUndefined',
                'isProcessed' => false,
                'result' => null,
            ],
        ];
    }

    public function testGetObjectVarCheck(): void
    {
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('name');
        $transport->setObjectVars(['name' => 'defaultTestValue1']);

        $this->entityFieldExtension->get($transport);
        self::assertTrue($transport->isProcessed());
        self::assertSame('defaultTestValue1', $transport->getResult());
    }
}
