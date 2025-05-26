<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ApiBundle\Form\Extension\EmptyDataExtension;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class EmptyDataExtensionTest extends TestCase
{
    private EntityInstantiator&MockObject $entityInstantiator;
    private EmptyDataExtension $emptyDataExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityInstantiator = $this->createMock(EntityInstantiator::class);

        $this->emptyDataExtension = new EmptyDataExtension($this->entityInstantiator);
    }

    private function expectBuildForm(array $options): \Closure
    {
        $emptyDataNormalizer = null;
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('setEmptyData')
            ->willReturnCallback(function ($value) use (&$emptyDataNormalizer, $builder) {
                $emptyDataNormalizer = $value;

                return $builder;
            });
        $this->emptyDataExtension->buildForm($builder, $options);

        return $emptyDataNormalizer;
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([FormType::class], EmptyDataExtension::getExtendedTypes());
    }

    /**
     * @dataProvider scalarValueProvider
     */
    public function testEmptyDataNormalizerForScalarField(
        string|int|null $expected,
        ?string $viewData,
        mixed $emptyData
    ): void {
        $options = [
            'data_class' => null,
            'empty_data' => $emptyData
        ];

        $emptyDataNormalizer = $this->expectBuildForm($options);

        $form = $this->createMock(FormInterface::class);

        self::assertSame($expected, $emptyDataNormalizer($form, $viewData));
    }

    public function scalarValueProvider(): array
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

    public function testEmptyDataNormalizerForCompoundField(): void
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

    public function testEmptyDataNormalizerForEmptyOptionalCompoundFieldWithDataClass(): void
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

    public function testEmptyDataNormalizerForEmptyRequiredCompoundFieldWithDataClass(): void
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

    public function testEmptyDataNormalizerForNotEmptyCompoundFieldWithDataClass(): void
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

    public function testEmptyDataNormalizerForNotEmptyCompoundFieldWithDataClassAndExistingEmptyDataNormalizer(): void
    {
        $object = new \stdClass();
        $options = [
            'data_class' => 'Test\Class',
            'empty_data' => function () use ($object) {
                return $object;
            }
        ];

        $emptyDataNormalizer = $this->expectBuildForm($options);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('isEmpty')
            ->willReturn(false);

        $this->entityInstantiator->expects(self::never())
            ->method('instantiate');

        self::assertSame($object, $emptyDataNormalizer($form, ''));
    }
}
