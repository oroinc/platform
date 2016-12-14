<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\ConfigBundle\Config\ConfigDefinitionImmutableBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\ConfigValueBag;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;

use Oro\Bundle\EmailBundle\Provider\SmtpSettingsProvider;

class SmtpSettingsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SmtpSettingsProvider */
    protected $provider;

    /** @var ConfigManager */
    protected $manager;

    /** @var ConfigDefinitionImmutableBag */
    protected $bag;

    /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var GlobalScopeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $globalScopeManager;

    /**
     * @var array
     */
    protected $settings = [
        'oro_email' => [
            'smtp_settings_host' => [
                'value' => '',
                'type'  => 'scalar',
            ],
            'smtp_settings_port' => [
                'value' => null,
                'type'  => 'integer',
            ],
            'smtp_settings_encryption' => [
                'value' => null,
                'type'  => 'scalar',
            ],
            'smtp_settings_username' => [
                'value' => '',
                'type'  => 'scalar',
            ],
            'smtp_settings_password' => [
                'value' => '',
                'type'  => 'scalar',
            ],
        ],
    ];

    protected function setUp()
    {
        $this->bag = new ConfigDefinitionImmutableBag($this->settings);
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ConfigManager(
            'global',
            $this->bag,
            $this->dispatcher,
            new ConfigValueBag()
        );

        $this->globalScopeManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\GlobalScopeManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager->addManager('global', $this->globalScopeManager);

        $this->provider = new SmtpSettingsProvider($this->globalScopeManager);
    }

    public function testGetSmtpSettings()
    {
        $smtpSettings = $this->provider->getSmtpSettings();

        $this->assertInstanceOf(SmtpSettings::class, $smtpSettings);
    }

    public function testSetAndRetrieveSmtpSettings()
    {
        $this->provider->

        $smtpSettings = $this->provider->getSmtpSettings();

        $this->assertInstanceOf(SmtpSettings::class, $smtpSettings);
    }
}
