<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigFileType;
use Prophecy\Argument;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

class ConfigSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ConfigSubscriber
     */
    protected $subscriber;

    /**
     * @var FormEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->configManager = $this->prophesize(ConfigManager::class);
        $this->subscriber = new ConfigSubscriber($this->configManager->reveal());

        $this->event = $this->prophesize(FormEvent::class);
    }

    public function testPreSubmit()
    {
        $form = $this->prepareForm(new IntegerType());
        $this->event->getForm()->willReturn($form->reveal());

        $data = [
            'oro_user___level' => [
                'use_parent_scope_value' => true,
            ]
        ];

        $this->configManager->get('oro_user.level', true)->willReturn(20);

        $this->event->getData()->willReturn($data);
        $data['oro_user___level']['value'] = 20;
        $this->event->setData($data)->shouldBeCalled();

        $this->subscriber->preSubmit($this->event->reveal());
    }

    public function testPreSubmitConfigFileType()
    {
        $transformer = $this->prophesize(ConfigFileDataTransformer::class);

        $form = $this->prepareForm(new ConfigFileType($transformer->reveal()));
        $this->event->getForm()->willReturn($form->reveal());

        $data = [
            'oro_user___level' => [
                'use_parent_scope_value' => true,
            ]
        ];

        $this->configManager->get('oro_user.level', true)->willReturn(20);

        $this->event->getData()->willReturn($data);
        $data['oro_user___level']['value'] = [
            'file' => null,
            'emptyFile' => true
        ];
        $this->event->setData($data)->shouldBeCalled();

        $this->subscriber->preSubmit($this->event->reveal());
    }

    private function prepareForm($innerType)
    {
        $resolvedType = $this->prophesize(ResolvedFormTypeInterface::class);
        $resolvedType->getInnerType()->willReturn($innerType);

        $config = $this->prophesize(FormConfigInterface::class);
        $config->getType()->willReturn($resolvedType->reveal());

        $valueForm = $this->prophesize(FormInterface::class);
        $valueForm->getConfig()->willReturn($config->reveal());

        $childForm = $this->prophesize(FormInterface::class);
        $childForm->get('value')->willReturn($valueForm->reveal());

        $form = $this->prophesize(FormInterface::class);
        $form->get(Argument::any())->willReturn($childForm->reveal());

        return $form;
    }
}
