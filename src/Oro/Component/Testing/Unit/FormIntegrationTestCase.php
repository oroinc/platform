<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase as BaseTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\CollectionValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base class for writing form integration tests as unit tests.
 */
class FormIntegrationTestCase extends BaseTestCase
{
    /** @var ConstraintValidatorInterface[] */
    private array $validators = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->validators = $this->getValidators();
    }

    /**
     * @return ConstraintValidatorInterface[] [alias => validator, ...]
     */
    protected function getValidators(): array
    {
        return [];
    }

    protected function getValidatorExtension(bool $loadMetadata = false): ValidatorExtension
    {
        return new ValidatorExtension($loadMetadata ? $this->getValidator() : Validation::createValidator());
    }

    protected function getValidator(): RecursiveValidator
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects($this->any())
            ->method('loadClassMetadata')
            ->willReturnCallback(function (ClassMetadata $meta) {
                $this->loadMetadata($meta);
            });

        return new RecursiveValidator(
            new ExecutionContextFactory($this->getTranslator()),
            new LazyLoadingMetadataFactory($loader),
            $this->getConstraintValidatorFactory()
        );
    }

    protected function assertFormIsValid(FormInterface $form): void
    {
        $this->assertTrue($form->isValid(), sprintf('%s form should be valid.', $form->getName()));
    }

    protected function assertFormIsNotValid(FormInterface $form): void
    {
        $this->assertFalse($form->isValid(), sprintf('%s form should not be valid.', $form->getName()));
    }

    protected function assertFormOptionEqual(mixed $expectedValue, string $optionName, FormInterface $form): void
    {
        $this->assertTrue(
            $form->getConfig()->hasOption($optionName),
            sprintf(
                'Failed asserting that %s option of %s not exists.',
                $optionName,
                $form->getName()
            )
        );
        $this->assertEquals(
            $expectedValue,
            $form->getConfig()->getOption($optionName),
            sprintf(
                'Failed asserting that %s option of %s form matches expected %s.',
                $optionName,
                $form->getName(),
                var_export($expectedValue, true)
            )
        );
    }

    protected function assertFormContainsField(string $expectedFieldName, FormInterface $form): void
    {
        $this->assertTrue(
            $form->offsetExists($expectedFieldName),
            sprintf(
                'Failed asserting that %s field exists at %s form.',
                $expectedFieldName,
                $form->getName()
            )
        );
    }

    protected function assertFormNotContainsField(string $expectedFieldName, FormInterface $form): void
    {
        $this->assertFalse(
            $form->offsetExists($expectedFieldName),
            sprintf(
                'Failed asserting that %s field not exists at %s form.',
                $expectedFieldName,
                $form->getName()
            )
        );
    }

    protected function loadMetadata(ClassMetadata $meta): void
    {
        $configFile = $this->getConfigFile($meta->name);
        if ($configFile) {
            $loader = new YamlFileLoader($configFile);
            $loader->loadClassMetadata($meta);
        }
    }

    protected function getConstraintValidatorFactory(): ConstraintValidatorFactoryInterface
    {
        $factory = $this->createMock(ConstraintValidatorFactoryInterface::class);
        $factory->expects($this->any())
            ->method('getInstance')
            ->willReturnCallback(function (Constraint $constraint) {
                $className = $constraint->validatedBy();
                if (!isset($this->validators[$className]) || CollectionValidator::class === $className) {
                    $this->validators[$className] = new $className();
                }

                return $this->validators[$className];
            });

        return $factory;
    }

    protected function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        return $translator;
    }

    protected function getConfigFile(string $class): ?string
    {
        $path = $this->getBundleRootPath($class);
        if ($path) {
            $path .= '/Resources/config/validation.yml';
            if (!is_readable($path)) {
                $path = null;
            }
        }

        return $path;
    }

    protected function getBundleRootPath(string $class): ?string
    {
        $path = null;
        $reflClass = new \ReflectionClass($class);
        $pos = strrpos($reflClass->getFileName(), 'Bundle');
        if (false !== $pos) {
            $path = substr($reflClass->getFileName(), 0, $pos) . 'Bundle';
        }

        return $path;
    }

    public static function assertDateTimeEquals(\DateTime $expected, \DateTime $actual): void
    {
        self::assertEquals($expected->format('c'), $actual->format('c'));
    }
}
