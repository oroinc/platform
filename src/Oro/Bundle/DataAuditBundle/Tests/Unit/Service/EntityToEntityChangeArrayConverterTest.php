<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\DataAuditBundle\Service\EntityToEntityChangeArrayConverter;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\EntityAdditionalFields;

class EntityToEntityChangeArrayConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityToEntityChangeArrayConverter */
    private $converter;

    protected function setUp()
    {
        $this->converter = new EntityToEntityChangeArrayConverter();
    }

    /**
     * @dataProvider entityConversionDataProvider
     * @param array $changeSet
     */
    public function testEntityConversionToArray(array $changeSet, array $expectedChangeSet)
    {
        $em = $this->getEntityManager();

        $expected = [
            'entity_class' => EntityAdditionalFields::class,
            'entity_id' => 1,
            'change_set' => $expectedChangeSet,
            'additional_fields' => []
        ];

        $converted = $this->converter->convertEntityToArray($em, new EntityAdditionalFields(), $changeSet);

        $this->assertEquals($expected, $converted);
    }

    /**
     * @dataProvider additionalFieldsDataProvider
     * @param array $fields
     * @param array $expectedFields
     */
    public function testAdditionalFieldsAddedIfEntityHasThem(array $fields, array $expectedFields)
    {
        $em = $this->getEntityManager();

        $converted = $this->converter->convertEntityToArray($em, new EntityAdditionalFields($fields), []);

        $this->assertArrayHasKey('additional_fields', $converted);
        $this->assertEquals($expectedFields, $converted['additional_fields']);
    }

    public function testEmptyAdditionalFieldsWhenEntityDoesNotHaveAny()
    {
        $em = $this->getEntityManager();

        $converted = $this->converter->convertEntityToArray($em, new EntityAdditionalFields(), []);

        $this->assertArrayHasKey('additional_fields', $converted);
        $this->assertEmpty($converted['additional_fields']);
    }

    public function testEmptyAdditionalFieldsWhenEntityDoesNotImplementInterface()
    {
        $em = $this->getEntityManager();

        $converted = $this->converter->convertEntityToArray($em, new \stdClass(), []);

        $this->assertArrayHasKey('additional_fields', $converted);
        $this->assertEmpty($converted['additional_fields']);
    }

    /**
     * @return array
     */
    public function additionalFieldsDataProvider()
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

    /**
     * @return array
     */
    public function entityConversionDataProvider()
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
        $property = $this->createMock(\ReflectionProperty::class);
        $property->expects($this->once())
            ->method('getValue')
            ->willReturn(1);

        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->expects($this->once())
            ->method('getSingleIdReflectionProperty')
            ->willReturn($property);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $em->expects($this->any())
            ->method('contains')
            ->willReturn(false);

        return $em;
    }
}
