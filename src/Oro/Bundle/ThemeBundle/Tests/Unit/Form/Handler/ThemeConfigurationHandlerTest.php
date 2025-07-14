<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\Handler\ThemeConfigurationHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ThemeConfigurationHandlerTest extends TestCase
{
    private FormInterface&MockObject $form;
    private ManagerRegistry&MockObject $registry;
    private ObjectManager&MockObject $manager;
    private ThemeConfigurationHandler $handler;

    #[\Override]
    public function setUp(): void
    {
        $this->requestParameters = [
            'theme_configuration' => [
                'theme' => 'default'
            ],
            'reloadWithoutSaving' => true
        ];
        $this->request = new Request([], $this->requestParameters);
        $this->request->setMethod(Request::METHOD_POST);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->manager = $this->createMock(ObjectManager::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->handler = new ThemeConfigurationHandler($this->registry);
    }


    public function testProcessNotSupportedEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument data should be instance of ThemeConfiguration entity');

        $this->handler->process(new \stdClass(), $this->form, $this->request);
    }

    public function testProcessWithoutSaving(): void
    {
        $themeConfiguration = new ThemeConfiguration();

        $this->form->expects(self::never())
            ->method('isValid');

        $result = $this->handler->process($themeConfiguration, $this->form, $this->request);

        $this->assertFalse($result);
    }

    public function testProcessValidForm(): void
    {
        $this->request->request->set('reloadWithoutSaving', null);
        $themeConfiguration = new ThemeConfiguration();

        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(ThemeConfiguration::class)
            ->willReturn($this->manager);

        $this->manager->expects(self::once())
            ->method('persist')
            ->with($themeConfiguration);
        $this->manager->expects(self::once())
            ->method('flush');

        $result = $this->handler->process($themeConfiguration, $this->form, $this->request);

        $this->assertTrue($result);
    }

    public function testProcessNotApplicableRequestMethod(): void
    {
        $this->request->setMethod(Request::METHOD_GET);

        $themeConfiguration = new ThemeConfiguration();

        $this->form->expects(self::never())
            ->method('isValid');

        $result = $this->handler->process($themeConfiguration, $this->form, $this->request);

        $this->assertFalse($result);
    }

    public function testProcessNotValidForm(): void
    {
        unset($this->requestParameters['reloadWithoutSaving']);
        $this->request->request->replace($this->requestParameters);

        $themeConfiguration = new ThemeConfiguration();

        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->registry->expects(self::never())
            ->method('getManagerForClass');

        $result = $this->handler->process($themeConfiguration, $this->form, $this->request);

        $this->assertFalse($result);
    }
}
