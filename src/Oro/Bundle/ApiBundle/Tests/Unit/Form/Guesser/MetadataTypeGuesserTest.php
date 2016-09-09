<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Guesser;

use Symfony\Component\Form\Guess\TypeGuess;

use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

class MetadataTypeGuesserTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS    = 'Test\Entity';
    const TEST_PROPERTY = 'testField';

    /** @var MetadataTypeGuesser */
    protected $typeGuesser;

    protected function setUp()
    {
        $this->typeGuesser = new MetadataTypeGuesser(
            [
                'integer'  => ['integer', []],
                'datetime' => ['test_datetime', ['model_timezone' => 'UTC', 'view_timezone' => 'UTC']],
            ]
        );
        $this->typeGuesser->setMetadataAccessor(null);
    }

    /**
     * @param EntityMetadata|null $metadata
     *
     * @return MetadataAccessorInterface
     */
    protected function getMetadataAccessor(EntityMetadata $metadata = null)
    {
        $metadataAccessor = $this->getMock('Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface');
        if (null === $metadata) {
            $metadataAccessor->expects($this->once())
                ->method('getMetadata')
                ->willReturn(null);
        } else {
            $metadataAccessor->expects($this->once())
                ->method('getMetadata')
                ->with($metadata->getClassName())
                ->willReturn($metadata);
        }

        return $metadataAccessor;
    }

    /**
     * @param string $fieldName
     * @param string $dataType
     *
     * @return FieldMetadata
     */
    protected function createFieldMetadata($fieldName, $dataType)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($dataType);
        
        return $fieldMetadata;
    }

    /**
     * @param string $associationName
     * @param string $targetClass
     * @param bool   $isCollection
     * @param string $dataType
     *
     * @return AssociationMetadata
     */
    protected function createAssociationMetadata($associationName, $targetClass, $isCollection, $dataType)
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsCollection($isCollection);
        $associationMetadata->setDataType($dataType);

        return $associationMetadata;
    }

    public function testGuessRequired()
    {
        $this->assertNull($this->typeGuesser->guessRequired(self::TEST_CLASS, self::TEST_PROPERTY));
    }

    public function testGuessMaxLength()
    {
        $this->assertNull($this->typeGuesser->guessMaxLength(self::TEST_CLASS, self::TEST_PROPERTY));
    }

    public function testGuessPattern()
    {
        $this->assertNull($this->typeGuesser->guessPattern(self::TEST_CLASS, self::TEST_PROPERTY));
    }

    public function testGuessTypeWithoutMetadataAccessor()
    {
        $this->assertEquals(
            new TypeGuess('text', [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeWithoutMetadata()
    {
        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor(null));
        $this->assertEquals(
            new TypeGuess('text', [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForUndefinedField()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
            new TypeGuess('text', [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForFormTypeWithoutOptions()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $metadata->addField($this->createFieldMetadata(self::TEST_PROPERTY, 'integer'));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
            new TypeGuess('integer', [], TypeGuess::HIGH_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForFormTypeWithOptions()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $metadata->addField($this->createFieldMetadata(self::TEST_PROPERTY, 'datetime'));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
            new TypeGuess(
                'test_datetime',
                ['model_timezone' => 'UTC', 'view_timezone' => 'UTC'],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToOneAssociation()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'integer'
        );
        $metadata->addAssociation($associationMetadata);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
            new TypeGuess(
                'oro_api_entity',
                ['metadata' => $associationMetadata],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToManyAssociation()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'integer'
        );
        $metadata->addAssociation($associationMetadata);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
            new TypeGuess(
                'oro_api_entity',
                ['metadata' => $associationMetadata],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }
}
