<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type\AbstractConfigTypeTestCase;
use Oro\Bundle\ImportExportBundle\Form\Type\IdentityConfigChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class IdentityConfigChoiceTypeTest extends AbstractConfigTypeTestCase
{
    /** @var IdentityConfigChoiceType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new IdentityConfigChoiceType($this->typeHelper);
    }

    /**
     * @dataProvider configureOptionsProvider
     * @param ConfigIdInterface $configId
     * @param boolean $immutable
     * @param array $options
     * @param array $expectedOptions
     */
    public function testConfigureOptions($configId, $immutable, array $options, array $expectedOptions)
    {
        $this->doTestConfigureOptions($this->type, $configId, $immutable, $options, $expectedOptions);
    }

    public function testValueShouldBeReadOnly()
    {
        $resolvedOptions = $this->resolveOptions();

        $this->assertTrue($resolvedOptions['disabled']);
    }

    public function testDefaultsChoices()
    {
        $expectedChoices = [
            'oro.importexport.entity_config.identity.no' => IdentityConfigChoiceType::CHOICE_NO,
            'oro.importexport.entity_config.identity.only_when_not_empty' =>
                IdentityConfigChoiceType::CHOICE_WHEN_NOT_EMPTY,
            'oro.importexport.entity_config.identity.always' => IdentityConfigChoiceType::CHOICE_ALWAYS
        ];
        $resolvedOptions = $this->resolveOptions();

        $this->assertEquals($expectedChoices, $resolvedOptions['choices']);
    }

    /**
     * @return array
     */
    private function resolveOptions()
    {
        $this->typeHelper->expects($this->never())
            ->method('getFieldName');
        $this->typeHelper->expects($this->never())
            ->method('isImmutable');

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options = [
            'config_id' => new FieldConfigId('importexport', 'Test\Entity', 'id')
        ];

        $resolvedOptions = $resolver->resolve($options);

        return $resolvedOptions;
    }

    public function testPreSetDataListener()
    {
        $options = [
            'config_id' => new FieldConfigId('importexport', 'Test\Entity', 'id')
        ];

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->once())
            ->method('getOptions')
            ->willReturn($options);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $event = new FormEvent($form, IdentityConfigChoiceType::CHOICE_NO);

        $this->type->onPreSetData($event);

        $this->assertEquals(IdentityConfigChoiceType::CHOICE_ALWAYS, $event->getData());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_importexport_identity_config_choice', $this->type->getName());
    }
}
