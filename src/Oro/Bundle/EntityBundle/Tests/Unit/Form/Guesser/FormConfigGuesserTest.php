<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Guesser;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Form\Guesser\FormConfigGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FormConfigGuesserTest extends TestCase
{
    private FormConfigGuesser $guesser;
    private ManagerRegistry&MockObject $managerRegistry;
    private ConfigProvider&MockObject $entityConfigProvider;
    private ConfigProvider&MockObject $formConfigProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);

        $this->guesser = new FormConfigGuesser(
            $this->managerRegistry,
            $this->entityConfigProvider,
            $this->formConfigProvider
        );
    }

    public function testGuessRequired(): void
    {
        $guess = $this->guesser->guessRequired('Test/Entity', 'testProperty');

        $this->assertValueGuess($guess, false, ValueGuess::LOW_CONFIDENCE);
    }

    public function testGuessMaxLength(): void
    {
        $guess = $this->guesser->guessMaxLength('Test/Entity', 'testProperty');

        $this->assertValueGuess($guess, null, ValueGuess::LOW_CONFIDENCE);
    }

    public function testGuessPattern(): void
    {
        $guess = $this->guesser->guessMaxLength('Test/Entity', 'testProperty');

        $this->assertValueGuess($guess, null, ValueGuess::LOW_CONFIDENCE);
    }

    public function testGuessNoEntityManager(): void
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn(null);

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessNoMetadata(): void
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $this->setEntityMetadata($class, null);

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessNoFormConfig(): void
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $this->setEntityMetadata($class, $this->createMock(ClassMetadata::class));

        $this->formConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(false);

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessNoFormType(): void
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $this->setEntityMetadata($class, $this->createMock(ClassMetadata::class));
        $this->setFormConfig($class, $property, []);

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessOnlyFormType(): void
    {
        $class = 'Test/Entity';
        $property = 'testProperty';
        $formType = 'test_form_type';

        $this->setEntityMetadata($class, $this->createMock(ClassMetadata::class));
        $this->setFormConfig($class, $property, ['form_type' => $formType]);

        $guess = $this->guesser->guessType($class, $property);

        $this->assertTypeGuess($guess, $formType, [], TypeGuess::HIGH_CONFIDENCE);
    }

    public function testGuessOnlyFormTypeWithLabel(): void
    {
        $class = 'Test/Entity';
        $property = 'testProperty';
        $formType = 'test_form_type';
        $label = 'Test Field';

        $this->setEntityMetadata($class, $this->createMock(ClassMetadata::class));
        $this->setFormConfig($class, $property, ['form_type' => $formType]);
        $this->setEntityConfig($class, $property, ['label' => $label]);

        $guess = $this->guesser->guessType($class, $property);

        $this->assertTypeGuess($guess, $formType, ['label' => $label], TypeGuess::HIGH_CONFIDENCE);
    }

    public function testGuessFormTypeWithOptions(): void
    {
        $class = 'Test/Entity';
        $property = 'testProperty';
        $formType = 'test_form_type';
        $formOptions = [
            'required' => false,
            'label'    => 'Test Field'
        ];

        $this->setEntityMetadata($class, $this->createMock(ClassMetadata::class));
        $this->setFormConfig($class, $property, ['form_type' => $formType, 'form_options' => $formOptions]);
        $this->setEntityConfig($class, $property, ['label' => 'Not used label']);

        $guess = $this->guesser->guessType($class, $property);

        $this->assertTypeGuess($guess, $formType, $formOptions, TypeGuess::HIGH_CONFIDENCE);
    }

    public function testGuessByAssociationClass(): void
    {
        $class = 'Test/Entity';
        $property = 'testProperty';
        $associationClass = 'Test/Association/Entity';
        $associationFormType = 'test_form_type';
        $associationFormOptions = [
            'required' => false,
            'label'    => 'Test Field'
        ];

        $sourceClassMetadata = $this->createMock(ClassMetadata::class);
        $sourceClassMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with($property)
            ->willReturn(true);
        $sourceClassMetadata->expects($this->any())
            ->method('isSingleValuedAssociation')
            ->with($property)
            ->willReturn(true);
        $sourceClassMetadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($property)
            ->willReturn($associationClass);
        $sourceEntityManager = $this->createMock(EntityManager::class);
        $sourceEntityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn($sourceClassMetadata);

        $associationEntityManager = $this->createMock(EntityManager::class);
        $associationEntityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($associationClass)
            ->willReturn($this->createMock(ClassMetadata::class));

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->withConsecutive([$class], [$associationClass])
            ->willReturnOnConsecutiveCalls($sourceEntityManager, $associationEntityManager);

        $sourceEntityConfig = new Config($this->createMock(ConfigIdInterface::class), []);
        $associationEntityConfig = new Config($this->createMock(ConfigIdInterface::class), [
            'form_type' => $associationFormType,
            'form_options' => $associationFormOptions
        ]);

        $this->formConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->withConsecutive([$class, $property], [$associationClass, null])
            ->willReturnOnConsecutiveCalls(true, true);
        $this->formConfigProvider->expects($this->any())
            ->method('getConfig')
            ->withConsecutive([$class, $property], [$associationClass, null])
            ->willReturnOnConsecutiveCalls($sourceEntityConfig, $associationEntityConfig);

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($sourceEntityConfig);

        $guess = $this->guesser->guessType($class, $property);

        $this->assertTypeGuess($guess, $associationFormType, $associationFormOptions, TypeGuess::HIGH_CONFIDENCE);
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

    private function setFormConfig(string $class, string $property, array $parameters): void
    {
        $config = new Config($this->createMock(ConfigIdInterface::class), $parameters);

        $this->formConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(true);
        $this->formConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($config);
    }

    private function setEntityConfig(string $class, string $property, array $parameters): void
    {
        $config = new Config($this->createMock(ConfigIdInterface::class), $parameters);

        $this->entityConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(true);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($config);
    }

    private function assertTypeGuess(TypeGuess $guess, string $type, array $options, int $confidence): void
    {
        $this->assertInstanceOf(TypeGuess::class, $guess);
        $this->assertEquals($type, $guess->getType());
        $this->assertEquals($options, $guess->getOptions());
        $this->assertEquals($confidence, $guess->getConfidence());
    }

    private function assertDefaultTypeGuess(TypeGuess $guess): void
    {
        $this->assertTypeGuess($guess, TextType::class, [], TypeGuess::LOW_CONFIDENCE);
    }

    private function assertValueGuess(ValueGuess $guess, $value, int $confidence): void
    {
        $this->assertInstanceOf(ValueGuess::class, $guess);
        $this->assertEquals($value, $guess->getValue());
        $this->assertEquals($confidence, $guess->getConfidence());
    }
}
