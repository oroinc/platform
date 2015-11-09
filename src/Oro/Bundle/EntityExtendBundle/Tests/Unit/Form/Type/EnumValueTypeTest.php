<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueType;

class EnumValueTypeTest extends TypeTestCase
{
    /** @var EnumValueType */
    protected $type;

    /**
     * @var ConstraintValidatorInterface[]
     */
    protected $validators;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new EnumValueType();
    }

    protected function getExtensions()
    {
        $validator = new Validator(
            new LazyLoadingMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory(),
            new IdentityTranslator()
        );

        return [
            new PreloadedExtension(
                [],
                [
                    'form' => [
                        new FormTypeValidatorExtension($validator)
                    ]
                ]
            ),
            new ValidatorExtension($this->getValidator()),
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit(array $inputData, array $expectedData)
    {
        $form = $this->factory->create($this->type);
        $form->submit($inputData['form']);

        $this->assertEquals($expectedData['valid'], $form->isValid());
    }

    public function testSubmitValidDataForNewEnumValue()
    {
        $formData = [
            'label'      => 'Value 1',
            'is_default' => true,
            'priority'   => 1
        ];

        $form = $this->factory->create($this->type);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(
            [
                'id'         => null,
                'label'      => 'Value 1',
                'is_default' => true,
                'priority'   => '1'
            ],
            $form->getData()
        );

        $nameConstraints = $form->get('label')->getConfig()->getOption('constraints');
        $this->assertCount(2, $nameConstraints);

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\NotBlank',
            $nameConstraints[0]
        );

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\Length',
            $nameConstraints[1]
        );
        $this->assertEquals(255, $nameConstraints[1]->max);
    }

    public function testSubmitValidDataForExistingEnumValue()
    {
        $formData = [
            'id'         => 'val1',
            'label'      => 'Value 1',
            'is_default' => true,
            'priority'   => 1
        ];

        $form = $this->factory->create($this->type);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(
            [
                'id'         => 'val1',
                'label'      => 'Value 1',
                'is_default' => true,
                'priority'   => '1'
            ],
            $form->getData()
        );

        $nameConstraints = $form->get('label')->getConfig()->getOption('constraints');
        $this->assertCount(2, $nameConstraints);
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_enum_value',
            $this->type->getName()
        );
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'valid data' => [
                'input' => [
                    'form' => [
                        'label' => 'Label1',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                ],
                'expected' => [
                    'valid' => true,
                ],
            ],
            'empty label' => [
                'input' => [
                    'form' => [
                        'is_default' => true,
                        'priority' => 2,
                    ],
                ],
                'expected' => [
                    'valid' => false,
                ],
            ],
            'long label' => [
                'input' => [
                    'form' => [
                        'label' => str_repeat('l', 256),
                        'is_default' => true,
                        'priority' => 3,
                    ],
                ],
                'expected' => [
                    'valid' => false,
                ],
            ],
            'incorrect label and empty id' => [
                'input' => [
                    'form' => [
                        'label' => '!@#$',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                ],
                'expected' => [
                    'valid' => false,
                ],
            ],
            'correct label and not empty id' => [
                'input' => [
                    'form' => [
                        'id' => 11,
                        'label' => '!@#$',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                ],
                'expected' => [
                    'valid' => true,
                ],
            ],
        ];
    }

    /**
     * @param array                                    $data
     * @param \PHPUnit_Framework_MockObject_MockObject $form
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormEvent($data, $form = null)
    {
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        return $event;
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
            new LazyLoadingMetadataFactory($loader),
            $this->getConstraintValidatorFactory(),
            new IdentityTranslator()
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
