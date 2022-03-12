<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Provider\SystemVariablesProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SystemVariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dateTimeFormatter;

    /** @var SystemVariablesProvider */
    private $provider;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->dateTimeFormatter = $this->createMock(DateTimeFormatterInterface::class);

        $this->provider = new SystemVariablesProvider(
            $translator,
            $this->configManager,
            $this->dateTimeFormatter
        );
    }

    public function testGetVariableDefinitions()
    {
        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                'currentDateTime' => ['type' => 'string', 'label' => 'oro.email.emailtemplate.current_datetime'],
                'currentDate'  => ['type' => 'string', 'label' => 'oro.email.emailtemplate.current_date'],
                'currentTime'  => ['type' => 'string', 'label' => 'oro.email.emailtemplate.current_time'],
                'appURL'       => ['type' => 'string', 'label' => 'oro.email.emailtemplate.app_url'],
            ],
            $result
        );
    }

    public function testGetVariableValues()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_ui.application_name', false, false, null, ''],
                ['oro_ui.application_title', false, false, null, ''],
                ['oro_ui.application_url', false, false, null, 'http://localhost'],
            ]);
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($this->isInstanceOf(\DateTime::class))
            ->willReturn('datetime');
        $this->dateTimeFormatter->expects($this->once())
            ->method('formatDate')
            ->with($this->isInstanceOf(\DateTime::class))
            ->willReturn('date');
        $this->dateTimeFormatter->expects($this->once())
            ->method('formatTime')
            ->with($this->isInstanceOf(\DateTime::class))
            ->willReturn('time');

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'appURL'       => 'http://localhost',
                'currentDateTime'  => 'datetime',
                'currentDate'  => 'date',
                'currentTime'  => 'time',
            ],
            $result
        );
    }
}
