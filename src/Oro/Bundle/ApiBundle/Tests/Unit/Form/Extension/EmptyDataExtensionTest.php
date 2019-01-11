<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ApiBundle\Form\Extension\EmptyDataExtension;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class EmptyDataExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityInstantiator */
    private $entityInstantiator;

    /** @var EmptyDataExtension */
    private $emptyDataExtension;

    protected function setUp()
    {
        $this->entityInstantiator = $this->createMock(EntityInstantiator::class);

        $this->emptyDataExtension = new EmptyDataExtension($this->entityInstantiator);
    }

    /**
     * @param array $options
     *
     * @return \Closure
     */
    private function expectBuildForm($options)
    {
        $emptyDataNormalizer = null;
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('setEmptyData')
            ->willReturnCallback(function ($value) use (&$emptyDataNormalizer) {
                $emptyDataNormalizer = $value;
            });
        $this->emptyDataExtension->buildForm($builder, $options);

        return $emptyDataNormalizer;
    }

    public function testGetExtendedType()
    {
        self::assertEquals(FormType::class, $this->emptyDataExtension->getExtendedType());
    }

    /**
     * @dataProvider scalarValueProvider
     */
    public function testEmptyDataNormalizerForScalarField($expected, $viewData, $emptyData)
    {
        $options = [
            'data_class' => null,
            'empty_data' => $emptyData
        ];

        $emptyDataNormalizer = $this->expectBuildForm($options);

        $form = $this->createMock(FormInterface::class);

        self::assertSame($expected, $emptyDataNormalizer($form, $viewData));
    }

    public function scalarValueProvider()
    {
        return [
            'null, null'                           => [
                'expected'  => null,
                'viewData'  => null,
                'emptyData' => null
            ],
            'null, null (closure)'                 => [
                'expected'  => null,
                'viewData'  => null,
                'emptyData' => function () {
                    return null;
                }
            ],
            'null, empty string'                   => [
                'expected'  => null,
                'viewData'  => null,
                'emptyData' => ''
            ],
            'null, empty string (closure)'         => [
                'expected'  => null,
                'viewData'  => null,
                'emptyData' => function () {
                    return '';
                }
            ],
            'empty string, null'                   => [
                'expected'  => '',
                'viewData'  => '',
                'emptyData' => null
            ],
            'empty string, null (closure)'         => [
                'expected'  => '',
                'viewData'  => '',
                'emptyData' => function () {
                    return null;
                }
            ],
            'empty string, empty string'           => [
                'expected'  => '',
                'viewData'  => '',
                'emptyData' => ''
            ],
            'empty string, empty string (closure)' => [
                'expected'  => '',
                'viewData'  => '',
                'emptyData' => function () {
                    return '';
                }
            ],
            'not empty value'                      => [
                'expected'  => 0,
                'viewData'  => '',
                'emptyData' => 0
            ],
            'not empty value (closure)'            => [
                'expected'  => 0,
                'viewData'  => '',
                'emptyData' => function () {
                    return 0;
                }
            ]
        ];
    }

    public function testEmptyDataNormalizerForCompoundField()
    {
        $options = [
            'data_class' => null,
            'empty_data' => function () {
                return [];
            }
        ];

        $emptyDataNormalizer = $this->expectBuildForm($options);

        $form = $this->createMock(FormInterface::class);

        self::assertSame([], $emptyDataNormalizer($form, ''));
    }

    public function testEmptyDataNormalizerForEmptyOptionalCompoundFieldWithDataClass()
    {
        $options = ['data_class' => 'Test\Class'];

        $emptyDataNormalizer = $this->expectBuildForm($options);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('isEmpty')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isRequired')
            ->willReturn(false);

        $this->entityInstantiator->expects(self::never())
            ->method('instantiate');

        self::assertNull($emptyDataNormalizer($form, ''));
    }

    public function testEmptyDataNormalizerForEmptyRequiredCompoundFieldWithDataClass()
    {
        $options = ['data_class' => 'Test\Class'];

        $emptyDataNormalizer = $this->expectBuildForm($options);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('isEmpty')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isRequired')
            ->willReturn(true);

        $object = new \stdClass();
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with('Test\Class')
            ->willReturn($object);

        self::assertSame($object, $emptyDataNormalizer($form, ''));
    }

    public function testEmptyDataNormalizerForNotEmptyCompoundFieldWithDataClass()
    {
        $options = ['data_class' => 'Test\Class'];

        $emptyDataNormalizer = $this->expectBuildForm($options);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('isEmpty')
            ->willReturn(false);

        $object = new \stdClass();
        $this->entityInstantiator->expects(self::once())
            ->method('instantiate')
            ->with('Test\Class')
            ->willReturn($object);

        self::assertSame($object, $emptyDataNormalizer($form, ''));
    }
}
