<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase as BaseTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator;

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
     * @return Validator
     */
    protected function getValidator()
    {
        /* @var $loader \PHPUnit_Framework_MockObject_MockObject|LoaderInterface */
        $loader = $this->getMock('Symfony\Component\Validator\Mapping\Loader\LoaderInterface');
        $loader
            ->expects($this->any())
            ->method('loadClassMetadata')
            ->will($this->returnCallback(function (ClassMetadata $meta) {
                $this->loadMetadata($meta);
            }));

        $validator = new Validator(
            new ClassMetadataFactory($loader),
            $this->getConstraintValidatorFactory(),
            $this->getTranslator()
        );

        return $validator;
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
     * @return \PHPUnit_Framework_MockObject_MockObject|ConstraintValidatorFactoryInterface
     */
    protected function getConstraintValidatorFactory()
    {
        /* @var $factory \PHPUnit_Framework_MockObject_MockObject|ConstraintValidatorFactoryInterface */
        $factory = $this->getMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');

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
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected function getTranslator()
    {
        /* @var $translator \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

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
}
