<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ApiBundle\Form\Extension\EmptyDataExtension;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmptyDataExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityInstantiator;

    /** @var EmptyDataExtension */
    protected $emptyDataExtension;

    protected function setUp()
    {
        $this->entityInstantiator = $this->getMockBuilder(EntityInstantiator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->emptyDataExtension = new EmptyDataExtension($this->entityInstantiator);
    }

    public function testGetExtendedType()
    {
        self::assertEquals(FormType::class, $this->emptyDataExtension->getExtendedType());
    }

    /**
     * @dataProvider scalarValueProvider
     */
    public function testEmptyDataNormalizerForScalarField($value, $expected)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefault('data_class', null);
        $this->emptyDataExtension->configureOptions($optionsResolver);
        $options = $optionsResolver->resolve([]);

        $emptyDataNormalizer = $options['empty_data'];

        $form = $this->getMock(FormInterface::class);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects(self::once())
            ->method('getCompound')
            ->willReturn(false);

        self::assertSame($expected, $emptyDataNormalizer($form, $value));
    }

    public function scalarValueProvider()
    {
        return [
            [null, null],
            ['', ''],
        ];
    }

    public function testEmptyDataNormalizerForCompoundField()
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefault('data_class', null);
        $this->emptyDataExtension->configureOptions($optionsResolver);
        $options = $optionsResolver->resolve([]);

        $emptyDataNormalizer = $options['empty_data'];

        $form = $this->getMock(FormInterface::class);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects(self::once())
            ->method('getCompound')
            ->willReturn(true);

        self::assertSame([], $emptyDataNormalizer($form, ''));
    }

    public function testEmptyDataNormalizerForEmptyOptionalCompoundFieldWithDataClass()
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefault('data_class', null);
        $this->emptyDataExtension->configureOptions($optionsResolver);
        $options = $optionsResolver->resolve(['data_class' => 'Test\Class']);

        $emptyDataNormalizer = $options['empty_data'];

        $form = $this->getMock(FormInterface::class);
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
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefault('data_class', null);
        $this->emptyDataExtension->configureOptions($optionsResolver);
        $options = $optionsResolver->resolve(['data_class' => 'Test\Class']);

        $emptyDataNormalizer = $options['empty_data'];

        $form = $this->getMock(FormInterface::class);
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
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefault('data_class', null);
        $this->emptyDataExtension->configureOptions($optionsResolver);
        $options = $optionsResolver->resolve(['data_class' => 'Test\Class']);

        $emptyDataNormalizer = $options['empty_data'];

        $form = $this->getMock(FormInterface::class);
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
