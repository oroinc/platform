<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigFileType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

class ConfigSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|MockObject */
    protected $configManager;

    /** @var ConfigSubscriber */
    protected $subscriber;

    /** @var FormEvent|MockObject */
    protected $event;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->subscriber = new ConfigSubscriber($this->configManager);

        $this->event = $this->createMock(FormEvent::class);
    }

    public function testPreSubmit()
    {
        $data = ['oro_user___level' => ['use_parent_scope_value' => true]];

        $form = $this->prepareForm(new IntegerType());
        $this->event->method('getForm')->willReturn($form);
        $this->event->method('getData')->willReturn($data);
        $this->configManager->expects(static::once())->method('get')->with('oro_user.level', true)->willReturn(20);
        $this->event->expects(static::once())
            ->method('setData')
            ->with(\array_merge_recursive($data, [
                'oro_user___level' => ['value' => 20]
            ]));

        $this->subscriber->preSubmit($this->event);
    }

    public function testPreSubmitConfigFileType()
    {
        $data = ['oro_user___level' => ['use_parent_scope_value' => true,]];

        $transformer = $this->createMock(ConfigFileDataTransformer::class);
        $form = $this->prepareForm(new ConfigFileType($transformer));
        $this->event->method('getForm')->willReturn($form);
        $this->event->method('getData')->willReturn($data);
        $this->event->expects(static::once())
            ->method('setData')
            ->with(\array_merge_recursive($data, [
                'oro_user___level' => ['value' => [
                    'file' => null,
                    'emptyFile' => true
                ]]
            ]));

        $this->subscriber->preSubmit($this->event);
    }

    private function prepareForm($innerType)
    {
        $resolvedType = $this->createMock(ResolvedFormTypeInterface::class);
        $resolvedType->method('getInnerType')->willReturn($innerType);

        $config = $this->createMock(FormConfigInterface::class);
        $config->method('getType')->willReturn($resolvedType);

        $valueForm = $this->createMock(FormInterface::class);
        $valueForm->method('getConfig')->willReturn($config);

        $childForm = $this->createMock(FormInterface::class);
        $childForm->method('get')->with('value')->willReturn($valueForm);

        $form = $this->createMock(FormInterface::class);
        $form->method('get')->willReturn($childForm);

        return $form;
    }
}
