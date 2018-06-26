<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Guesser;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Form\Guesser\FormConfigGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

class FormConfigGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormConfigGuesser */
    protected $guesser;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $managerRegistry;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $formConfigProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->managerRegistry,
            $this->entityConfigProvider,
            $this->formConfigProvider,
            $this->guesser
        );
    }

    public function testGuessRequired()
    {
        $guess = $this->guesser->guessRequired('Test/Entity', 'testProperty');

        $this->assertValueGuess($guess, false, ValueGuess::LOW_CONFIDENCE);
    }

    public function testGuessMaxLength()
    {
        $guess = $this->guesser->guessMaxLength('Test/Entity', 'testProperty');

        $this->assertValueGuess($guess, null, ValueGuess::LOW_CONFIDENCE);
    }

    public function testGuessPattern()
    {
        $guess = $this->guesser->guessMaxLength('Test/Entity', 'testProperty');

        $this->assertValueGuess($guess, null, ValueGuess::LOW_CONFIDENCE);
    }

    public function testGuessNoEntityManager()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn(null);

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessNoMetadata()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $this->setEntityMetadata($class, null);

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessNoFormConfig()
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

    public function testGuessNoFormType()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $this->setEntityMetadata($class, $this->createMock(ClassMetadata::class));
        $this->setFormConfig($class, $property, []);

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessOnlyFormType()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';
        $formType = 'test_form_type';

        $this->setEntityMetadata($class, $this->createMock(ClassMetadata::class));
        $this->setFormConfig($class, $property, ['form_type' => $formType]);

        $guess = $this->guesser->guessType($class, $property);

        $this->assertTypeGuess($guess, $formType, [], TypeGuess::HIGH_CONFIDENCE);
    }

    public function testGuessOnlyFormTypeWithLabel()
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

    public function testGuessFormTypeWithOptions()
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

    public function testGuessByAssociationClass()
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
            ->willReturn($this->getMockForAbstractClass(ClassMetadata::class));

        $this->managerRegistry->method('getManagerForClass')
            ->withConsecutive([$class], [$associationClass])
            ->willReturnOnConsecutiveCalls($sourceEntityManager, $associationEntityManager);

        /** @var \PHPUnit\Framework\MockObject\MockObject|Config $sourceEntityConfig */
        $sourceEntityConfig = $this->createPartialMock(Config::class, []);
        $sourceEntityConfig->setValues([]);
        /** @var \PHPUnit\Framework\MockObject\MockObject|Config $associationEntityConfig */
        $associationEntityConfig = $this->createPartialMock(Config::class, []);
        $associationEntityConfig->setValues([
            'form_type' => $associationFormType,
            'form_options' => $associationFormOptions
        ]);

        $this->formConfigProvider->method('hasConfig')
            ->withConsecutive([$class, $property], [$associationClass, null])
            ->willReturnOnConsecutiveCalls(true, true);
        $this->formConfigProvider->method('getConfig')
            ->withConsecutive([$class, $property], [$associationClass, null])
            ->willReturnOnConsecutiveCalls($sourceEntityConfig, $associationEntityConfig);

        $this->entityConfigProvider->method('getConfig')
            ->with($class, $property)
            ->willReturn($sourceEntityConfig);

        $guess = $this->guesser->guessType($class, $property);

        $this->assertTypeGuess($guess, $associationFormType, $associationFormOptions, TypeGuess::HIGH_CONFIDENCE);
    }

    /**
     * @param string $class
     * @param mixed  $metadata
     */
    protected function setEntityMetadata($class, $metadata)
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

    /**
     * @param string $class
     * @param string $property
     * @param array  $parameters
     */
    protected function setFormConfig($class, $property, array $parameters)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Config $config */
        $config = $this->createPartialMock(Config::class, []);
        $config->setValues($parameters);

        $this->formConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(true);
        $this->formConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($config);
    }

    /**
     * @param string $class
     * @param string $property
     * @param array  $parameters
     */
    protected function setEntityConfig($class, $property, array $parameters)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Config $config */
        $config = $this->createPartialMock(Config::class, []);
        $config->setValues($parameters);

        $this->entityConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(true);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($config);
    }

    /**
     * @param TypeGuess $guess
     * @param string    $type
     * @param array     $options
     * @param           $confidence
     */
    protected function assertTypeGuess($guess, $type, array $options, $confidence)
    {
        $this->assertInstanceOf('Symfony\Component\Form\Guess\TypeGuess', $guess);
        $this->assertEquals($type, $guess->getType());
        $this->assertEquals($options, $guess->getOptions());
        $this->assertEquals($confidence, $guess->getConfidence());
    }

    /**
     * @param TypeGuess $guess
     */
    protected function assertDefaultTypeGuess($guess)
    {
        $this->assertTypeGuess($guess, TextType::class, [], TypeGuess::LOW_CONFIDENCE);
    }

    /**
     * @param ValueGuess $guess
     * @param mixed      $value
     * @param            $confidence
     */
    protected function assertValueGuess($guess, $value, $confidence)
    {
        $this->assertInstanceOf('Symfony\Component\Form\Guess\ValueGuess', $guess);
        $this->assertEquals($value, $guess->getValue());
        $this->assertEquals($confidence, $guess->getConfidence());
    }
}
