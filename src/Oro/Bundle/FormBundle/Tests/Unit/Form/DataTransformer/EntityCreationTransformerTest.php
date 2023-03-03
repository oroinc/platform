<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreationTransformer;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\TestCreationEntity;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntityCreationTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityCreationTransformer */
    private $transformer;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);

        $meta = $this->createMock(ClassMetadata::class);
        $meta->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->with(TestCreationEntity::class)
            ->willReturn($meta);

        $this->transformer = new EntityCreationTransformer($this->em, TestCreationEntity::class);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(
        $value,
        ?TestCreationEntity $expected,
        ?string $valuePath = 'value',
        bool $allowEmptyProperty = false,
        string $newEntityPropertyName = 'name',
        \Exception $exception = null,
        bool $loadEntity = false
    ) {
        $this->transformer->setValuePath($valuePath);
        $this->transformer->setAllowEmptyProperty($allowEmptyProperty);
        $this->transformer->setNewEntityPropertyName($newEntityPropertyName);
        if (null !== $exception) {
            $this->expectException(get_class($exception));
            $this->expectExceptionMessage($exception->getMessage());
        }
        if ($loadEntity) {
            $repo = $this->createMock(EntityRepository::class);
            $repo->expects($this->once())
                ->method('find')
                ->with($expected->getId())
                ->willReturn($expected);
            $this->em->expects($this->once())
                ->method('getRepository')
                ->with(TestCreationEntity::class)
                ->willReturn($repo);
        }
        $entity = $this->transformer->reverseTransform($value);

        $this->assertEquals($expected, $entity);
    }

    public function reverseTransformDataProvider(): array
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
                json_encode(['id' => 15], JSON_THROW_ON_ERROR),
                new TestCreationEntity(15),
                'value',
                false,
                'name',
                null,
                true
            ],
            'load entity: id as scalar' => [
                json_encode(['id' => 15], JSON_THROW_ON_ERROR),
                new TestCreationEntity(15),
                'value',
                false,
                'name',
                null,
                true
            ],
            'valuePath property is empty and allowEmptyProperty property is false' => [
                json_encode(['id' => null, 'value' => 'test'], JSON_THROW_ON_ERROR),
                null,
                null,
                false,
                'name',
                new InvalidConfigurationException(
                    'Property "valuePath" should be not empty or property "allowEmptyProperty" should be true.'
                )
            ],
            'invalid valuePath and allowEmptyProperty property is false' => [
                json_encode(['id' => null, 'value' => 'test'], JSON_THROW_ON_ERROR),
                null,
                'invalid_path',
                false,
                'name',
                new InvalidConfigurationException(
                    'No data provided for new entity property.'
                )
            ],
            'create empty entity' => [
                json_encode(['id' => null], JSON_THROW_ON_ERROR),
                new TestCreationEntity(),
                'id',
                true
            ],
            'create entity with value' => [
                json_encode(['id' => null, 'value' => 'test'], JSON_THROW_ON_ERROR),
                new TestCreationEntity(null, 'test'),
            ],
            'create entity with value from array' => [
                ['value' => 'test'],
                new TestCreationEntity(null, 'test'),
            ],
        ];
    }

    public function testReverseTransformUnexpectedType()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('json encoded string, array or scalar value');

        $this->transformer->reverseTransform(new \stdClass());
    }
}
