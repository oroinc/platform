<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Guesser;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Form\Guesser\DoctrineTypeGuesser;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\TypeGuess;

class DoctrineTypeGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var DoctrineTypeGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);

        $this->guesser = new DoctrineTypeGuesser(
            $this->managerRegistry,
            $this->entityConfigProvider
        );
    }

    public function testAddDoctrineTypeMapping()
    {
        $doctrineType = 'doctrine_type';
        $formType = 'test_form_type';
        $formOptions = ['form' => 'options'];
        $expectedMappings = [$doctrineType => ['type' => $formType, 'options' => $formOptions]];

        $this->guesser->addDoctrineTypeMapping($doctrineType, $formType, $formOptions);

        self::assertEquals(
            $expectedMappings,
            ReflectionUtil::getPropertyValue($this->guesser, 'doctrineTypeMappings')
        );
    }

    public function testGuessNoMetadata()
    {
        $class = 'Test\Entity';
        $property = 'testProperty';

        $this->setEntityMetadata($class, null);

        $this->assertDefaultGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessFieldWithoutAssociation()
    {
        $class = 'Test\Entity';
        $firstField = 'firstField';
        $secondField = 'secondField';

        $doctrineType = 'string';
        $formType = 'text';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($this->isType('string'))
            ->willReturn(false);
        $metadata->expects($this->any())
            ->method('getTypeOfField')
            ->with($this->isType('string'))
            ->willReturnMap([[$firstField, $doctrineType], [$secondField, 'object']]);
        $this->setEntityMetadata($class, $metadata);

        $this->guesser->addDoctrineTypeMapping($doctrineType, $formType);

        $guess = $this->guesser->guessType($class, $firstField);
        $this->assertGuess($guess, $formType, [], TypeGuess::HIGH_CONFIDENCE);

        $this->assertDefaultGuess($this->guesser->guessType($class, $secondField));
    }

    public function testGuessFieldSingleAssociation()
    {
        $class = 'Test\Entity';
        $property = 'testProperty';
        $associationClass = 'Test\Association\Class';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($property)
            ->willReturn(true);
        $metadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($property)
            ->willReturn($associationClass);
        $metadata->expects($this->any())
            ->method('isCollectionValuedAssociation')
            ->with($property)
            ->willReturn(false);
        $this->setEntityMetadata($class, $metadata);

        $this->assertGuess(
            $this->guesser->guessType($class, $property),
            EntityType::class,
            ['class' => $associationClass, 'multiple' => false],
            TypeGuess::HIGH_CONFIDENCE
        );
    }

    public function testGuessFieldCollectionAssociation()
    {
        $class = 'Test\Entity';
        $property = 'testProperty';
        $associationClass = 'Test\Association\Class';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($property)
            ->willReturn(true);
        $metadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($property)
            ->willReturn($associationClass);
        $metadata->expects($this->any())
            ->method('isCollectionValuedAssociation')
            ->with($property)
            ->willReturn(true);
        $this->setEntityMetadata($class, $metadata);

        $this->assertGuess(
            $this->guesser->guessType($class, $property),
            EntityType::class,
            ['class' => $associationClass, 'multiple' => true],
            TypeGuess::HIGH_CONFIDENCE
        );
    }

    private function setEntityMetadata(string $class, ?ClassMetadata $metadata): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn($metadata);

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn($entityManager);
    }

    private function assertGuess(TypeGuess $guess, string $type, array $options, int $confidence): void
    {
        $this->assertInstanceOf(TypeGuess::class, $guess);
        $this->assertEquals($type, $guess->getType());
        $this->assertEquals($options, $guess->getOptions());
        $this->assertEquals($confidence, $guess->getConfidence());
    }

    private function assertDefaultGuess(TypeGuess $guess): void
    {
        $this->assertGuess($guess, TextType::class, [], TypeGuess::LOW_CONFIDENCE);
    }
}
