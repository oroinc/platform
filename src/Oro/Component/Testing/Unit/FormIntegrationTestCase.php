<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase as BaseTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class FormIntegrationTestCase extends BaseTestCase
{
    /**
     * @var ConstraintValidatorInterface[]
     */
    protected $validators;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->validators = $this->getValidators();
    }

    /**
     * @return array
     */
    protected function getValidators()
    {
        return [];
    }

    /**
     * @param bool $loadMetadata
     * @return ValidatorExtension
     */
    protected function getValidatorExtension($loadMetadata = false)
    {
        return new ValidatorExtension($loadMetadata ? $this->getValidator() : Validation::createValidator());
    }

    /**
     * @return RecursiveValidator
     */
    protected function getValidator()
    {
        /* @var $loader \PHPUnit\Framework\MockObject\MockObject|LoaderInterface */
        $loader = $this->createMock('Symfony\Component\Validator\Mapping\Loader\LoaderInterface');
        $loader
            ->expects($this->any())
            ->method('loadClassMetadata')
            ->will($this->returnCallback(function (ClassMetadata $meta) {
                $this->loadMetadata($meta);
            }));

        $validator = new RecursiveValidator(
            new ExecutionContextFactory($this->getTranslator()),
            new LazyLoadingMetadataFactory($loader),
            $this->getConstraintValidatorFactory()
        );

        return $validator;
    }

    /**
     * @param FormInterface $form
     */
    protected function assertFormIsValid(FormInterface $form)
    {
        $formName = $form->getName();
        $this->assertTrue($form->isValid(), "{$formName} form should be valid.");
    }

    /**
     * @param FormInterface $form
     */
    protected function assertFormIsNotValid(FormInterface $form)
    {
        $formName = $form->getName();
        $this->assertFalse($form->isValid(), "{$formName} form shouldn't be valid.");
    }


    /**
     * @param mixed         $expectedValue
     * @param string        $optionName
     * @param FormInterface $form
     */
    protected function assertFormOptionEqual($expectedValue, $optionName, FormInterface $form)
    {
        $formName = $form->getName();
        $value = var_export($expectedValue, true);
        $this->assertEquals(
            $expectedValue,
            $form->getConfig()->getOption($optionName),
            "Failed asserting that {$optionName} option of {$formName} form matches expected {$value}."
        );
    }

    /**
     * @param               $expectedFieldName
     * @param FormInterface $form
     */
    protected function assertFormContainsField($expectedFieldName, FormInterface $form)
    {
        $formName = $form->getName();
        $this->assertTrue(
            $form->offsetExists($expectedFieldName),
            "Failed asserting that {$expectedFieldName} field exists at {$formName} form."
        );
    }

    /**
     * @param               $expectedFieldName
     * @param FormInterface $form
     */
    protected function assertFormNotContainsField($expectedFieldName, FormInterface $form)
    {
        $formName = $form->getName();
        $this->assertFalse(
            $form->offsetExists($expectedFieldName),
            "Failed asserting that {$expectedFieldName} field not exists at {$formName} form."
        );
    }

    /**
     * @param ClassMetadata $meta
     */
    protected function loadMetadata(ClassMetadata $meta)
    {
        if (false !== ($configFile = $this->getConfigFile($meta->name))) {
            $loader = new YamlFileLoader($configFile);
            $loader->loadClassMetadata($meta);
        }
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConstraintValidatorFactoryInterface
     */
    protected function getConstraintValidatorFactory()
    {
        /* @var $factory \PHPUnit\Framework\MockObject\MockObject|ConstraintValidatorFactoryInterface */
        $factory = $this->createMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');

        $factory->expects($this->any())
            ->method('getInstance')
            ->will($this->returnCallback(function (Constraint $constraint) {
                $className = $constraint->validatedBy();

                if (!isset($this->validators[$className])
                    || $className === 'Symfony\Component\Validator\Constraints\CollectionValidator'
                ) {
                    $this->validators[$className] = new $className();
                }

                return $this->validators[$className];
            }))
        ;

        return $factory;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected function getTranslator()
    {
        /* @var $translator \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');

        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }))
        ;
        $translator->expects($this->any())
            ->method('transChoice')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }))
        ;

        return $translator;
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getConfigFile($class)
    {
        if (false !== ($path = $this->getBundleRootPath($class))) {
            $path .= '/Resources/config/validation.yml';

            if (!is_readable($path)) {
                $path = false;
            }
        }

        return $path;
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getBundleRootPath($class)
    {
        $rclass = new \ReflectionClass($class);

        $path = false;

        if (false !== ($pos = strrpos($rclass->getFileName(), 'Bundle'))) {
            $path = substr($rclass->getFileName(), 0, $pos) . 'Bundle';
        }

        return $path;
    }

    /**
     * @param \DateTime $expected
     * @param \DateTime $actual
     */
    public static function assertDateTimeEquals(\DateTime $expected, \DateTime $actual)
    {
        self::assertEquals($expected->format('c'), $actual->format('c'));
    }
}
