<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Event\FormListener;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class FormListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testAddOwnerField(): void
    {
        $environment = $this->createMock(Environment::class);
        $newField = '<input>';
        $environment->expects(self::once())
            ->method('render')
            ->willReturn($newField);

        $formData = [
            'dataBlocks' => [
                [
                    'subblocks' => [
                        ['data' => ['someHTML']],
                    ],
                ],
            ],
        ];

        $formView = new FormView();
        $formView->children['owner'] = new FormView($formView);

        $event = new BeforeFormRenderEvent($formView, $formData, $environment, new \stdClass());

        $expectedFormData = $formData;
        array_unshift($expectedFormData['dataBlocks'][0]['subblocks'][0]['data'], $newField);

        $provider = $this->createMock(ConfigProvider::class);
        $provider->expects(self::any())
            ->method('hasConfig')
            ->willReturn(false);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::any())
            ->method('getProvider')
            ->willReturn($provider);

        $listener = new FormListener($configManager);
        $listener->addOwnerField($event);

        self::assertEquals($expectedFormData, $event->getFormData());
    }

    public function testAddOwnerFieldWhenNoOwner(): void
    {
        $environment = $this->createMock(Environment::class);
        $environment->expects(self::never())
            ->method('render');

        $formView = new FormView();
        $formData = ['dataBlocks' => []];
        $event = new BeforeFormRenderEvent($formView, $formData, $environment, new \stdClass());

        $listener = new FormListener($this->createMock(ConfigManager::class));
        $listener->addOwnerField($event);

        self::assertEquals($formData, $event->getFormData());
    }

    public function testAddOwnerFieldWhenOwnerIsRendered(): void
    {
        $environment = $this->createMock(Environment::class);
        $environment->expects(self::never())
            ->method('render');

        $formView = new FormView();
        $formView->children['owner'] = new FormView($formView);
        $formView->children['owner']->setRendered();

        $formData = ['dataBlocks' => []];
        $event = new BeforeFormRenderEvent($formView, $formData, $environment, new \stdClass());

        $listener = new FormListener($this->createMock(ConfigManager::class));
        $listener->addOwnerField($event);

        self::assertEquals($formData, $event->getFormData());
    }
}
