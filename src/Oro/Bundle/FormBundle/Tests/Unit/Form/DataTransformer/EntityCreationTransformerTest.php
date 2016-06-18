<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\Exception\InvalidConfigurationException;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreationTransformer;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\TestCreationEntity;

class EntityCreationTransformerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\TestCreationEntity';

    /** @var EntityCreationTransformer */
    protected $transformer;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    protected function setUp()
    {
        $meta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $meta
            ->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em
            ->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($meta);

        $this->transformer = new EntityCreationTransformer($this->em, self::TEST_ENTITY_CLASS);
    }

    /**
     * @dataProvider testReverseTransformDataProvider
     *
     * @param            $value
     * @param            $expected
     * @param string     $valuePath
     * @param bool       $allowEmptyProperty
     * @param string     $newEntityPropertyName
     * @param \Exception $exception
     * @param bool       $loadEntity
     */
    public function testReverseTransform(
        $value,
        $expected,
        $valuePath = 'value',
        $allowEmptyProperty = false,
        $newEntityPropertyName = 'name',
        \Exception $exception = null,
        $loadEntity = false
    ) {
        $this->transformer->setValuePath($valuePath);
        $this->transformer->setAllowEmptyProperty($allowEmptyProperty);
        $this->transformer->setNewEntityPropertyName($newEntityPropertyName);
        if (null !== $exception) {
            $this->setExpectedException(get_class($exception), $exception->getMessage());
        }
        if ($loadEntity) {
            /** @var TestCreationEntity $expected */
            $this->setLoadEntityExpectations($expected, $expected->getId());
        }
        $entity = $this->transformer->reverseTransform($value);

        $this->assertEquals($expected, $entity);
    }

    public function testReverseTransformDataProvider()
    {
        return [
            'no value 1' => [null, null],
            'no value 2' => ['', null],
            'no json data and not scalar and nod valid array' => [
                [1],
                null,
                'value',
                false,
                'name',
                new InvalidConfigurationException('No data provided for new entity property.')
            ],
            'load entity: id from json' => [
                json_encode(['id' => 15]),
                new TestCreationEntity(15),
                'value',
                false,
                'name',
                null,
                true
            ],
            'load entity: id as scalar' => [
                json_encode(['id' => 15]),
                new TestCreationEntity(15),
                'value',
                false,
                'name',
                null,
                true
            ],
            'valuePath property is empty and allowEmptyProperty property is false' => [
                json_encode(['id' => null, 'value' => 'test']),
                null,
                null,
                false,
                'name',
                new InvalidConfigurationException(
                    'Property "valuePath" should be not empty or property "allowEmptyProperty" should be true.'
                )
            ],
            'invalid valuePath and allowEmptyProperty property is false' => [
                json_encode(['id' => null, 'value' => 'test']),
                null,
                'invalid_path',
                false,
                'name',
                new InvalidConfigurationException(
                    'No data provided for new entity property.'
                )
            ],
            'create empty entity' => [
                json_encode(['id' => null]),
                new TestCreationEntity(),
                'value',
                true
            ],
            'create entity with value' => [
                json_encode(['id' => null, 'value' => 'test']),
                new TestCreationEntity(null, 'test'),
            ],
            'create entity with value from array' => [
                ['value' => 'test'],
                new TestCreationEntity(null, 'test'),
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage json encoded string, array or scalar value
     */
    public function testReverseTransformUnexpectedType()
    {
        $this->transformer->reverseTransform(new \stdClass);
    }

    protected function setLoadEntityExpectations($entity, $id)
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo
            ->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($entity);
        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($repo);
    }
}
