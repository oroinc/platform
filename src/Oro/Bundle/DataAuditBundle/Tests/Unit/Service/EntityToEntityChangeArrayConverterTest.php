<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\DataAuditBundle\Provider\AuditFieldTypeProvider;
use Oro\Bundle\DataAuditBundle\Service\EntityToEntityChangeArrayConverter;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\EntityAdditionalFields;

class EntityToEntityChangeArrayConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuditFieldTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var EntityToEntityChangeArrayConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(AuditFieldTypeProvider::class);
        $this->provider->expects($this->any())
            ->method('getFieldType')
            ->willReturn(AuditFieldTypeRegistry::TYPE_STRING);

        $this->converter = new EntityToEntityChangeArrayConverter();
        $this->converter->setAuditFieldTypeProvider(new AuditFieldTypeProvider());
    }

    /**
     * @dataProvider entityConversionDataProvider
     */
    public function testEntityConversionToArray(array $changeSet, array $expectedChangeSet)
    {
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->any())
            ->method('hasMetadataFor')
            ->willReturn(false);
        $metadata = $this->createMock(ClassMetadata::class);

        $em = $this->getEntityManager();
        $em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $expected = [
            'entity_class' => EntityAdditionalFields::class,
            'entity_id' => '0',
        ];

        if ($expectedChangeSet) {
            $expected['change_set'] = $expectedChangeSet;
        }

        $converted = $this->converter->convertNamedEntityToArray($em, new EntityAdditionalFields(), $changeSet);

        $this->assertEquals($expected, $converted);
    }

    /**
     * @dataProvider additionalFieldsDataProvider
     */
    public function testAdditionalFieldsAddedIfEntityHasThem(array $fields, array $expectedFields)
    {
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->any())
            ->method('hasMetadataFor')
            ->willReturn(false);

        $em = $this->getEntityManager();
        $em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $converted = $this->converter->convertNamedEntityToArray($em, new EntityAdditionalFields($fields), []);

        $this->assertArrayHasKey('additional_fields', $converted);
        $this->assertEquals($expectedFields, $converted['additional_fields']);
    }

    public function testConvertCollection()
    {
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->any())
            ->method('hasMetadataFor')
            ->willReturn(true);
        $metadata = $this->createMock(ClassMetadata::class);

        $em = $this->getEntityManager();
        $em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $field = new \stdClass();
        $field->prop = 'value';

        $converted = $this->converter->convertNamedEntityToArray(
            $em,
            new EntityAdditionalFields(),
            [
                'collection' => [
                    null,
                    new ArrayCollection([$field]),
                ],
            ]
        );

        $this->assertArrayHasKey('change_set', $converted);
        $this->assertEquals(
            [
                'collection' => [
                    null,
                    [
                        'entity_class' => ArrayCollection::class,
                        'entity_id' => '0',
                    ],
                ],
            ],
            $converted['change_set']
        );
    }

    public function testEmptyAdditionalFieldsWhenEntityDoesNotHaveAny()
    {
        $em = $this->getEntityManager();

        $converted = $this->converter->convertNamedEntityToArray($em, new EntityAdditionalFields(), []);

        $this->assertArrayNotHasKey('additional_fields', $converted);
    }

    public function testEmptyAdditionalFieldsWhenEntityDoesNotImplementInterface()
    {
        $em = $this->getEntityManager();

        $converted = $this->converter->convertNamedEntityToArray($em, new \stdClass(), []);

        $this->assertArrayNotHasKey('additional_fields', $converted);
    }

    public function additionalFieldsDataProvider(): array
    {
        $dateTime = new \DateTime('2017-11-10 10:00:00', new \DateTimeZone('Europe/London'));
        $resource = fopen(__FILE__, 'rb');
        if (false === $resource) {
            $this->fail('Unable to open resource');
        }

        return [
            [['integer' => 123], ['integer' => 123]],
            [['float' => 1.1], ['float' => 1.1]],
            [['boolean' => true], ['boolean' => true]],
            [['null' => null], ['null' => null]],
            [['string' => 'string'], ['string' => 'string']],
            [['array' => ['value' => 123]], ['array' => ['value' => 123]]],
            [['object' => new \stdClass()], ['object' => null]],
            [['resource' => $resource], ['resource' => null]],
            [['date' => $dateTime], ['date' => '2017-11-10T10:00:00+0000']],
        ];
    }

    public function entityConversionDataProvider(): array
    {
        $dateTime = new \DateTime('2017-11-10 10:00:00', new \DateTimeZone('Europe/London'));
        $resource = fopen(__FILE__, 'rb');
        if (false === $resource) {
            $this->fail('Unable to open resource');
        }

        return [
            [['integer' => [null, 123]], ['integer' => [null, 123]]],
            [['float' => [null, 1.1]], ['float' => [null, 1.1]]],
            [['boolean' => [null, true]], ['boolean' => [null, true]]],
            [['null' => [123, null]], ['null' => [123, null]]],
            [['string' => [null, 'string']], ['string' => [null, 'string']]],
            [['array' => [null, [123]]], ['array' => [null, [123]]]],
            [['object' => [null, new \stdClass()]], []],
            [['resource' => [null, $resource]], []],
            [['date' => [$dateTime, null]], ['date' => ['2017-11-10T10:00:00+0000', null]]],
        ];
    }

    /**
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEntityManager()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('contains')
            ->willReturn(false);

        $uow = $this->createMock(UnitOfWork::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->any())
            ->method('getSingleIdentifierValue')
            ->willReturn('0');

        return $em;
    }
}
