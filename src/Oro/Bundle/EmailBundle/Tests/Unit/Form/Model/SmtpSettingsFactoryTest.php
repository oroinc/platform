<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Model;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

class SmtpSettingsFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testWithoutRequestValues()
    {
        $smtpSettings = new SmtpSettings();

        $this->assertEquals($smtpSettings, SmtpSettingsFactory::createFromRequest(new Request()));
    }

    /**
     * @dataProvider validParametersDataProvider
     *
     * @param string $uri
     * @param string $method
     * @param array  $parameters
     */
    public function testWithValidRequestValues($uri, $method, $parameters)
    {
        $request = Request::create($uri, $method, $parameters);

        $smtpSettings = new SmtpSettings();
        $factorySmtpSettings = SmtpSettingsFactory::createFromRequest($request);

        $this->assertNotSame($smtpSettings->getHost(), $factorySmtpSettings->getHost());
        $this->assertNotSame($smtpSettings->getPort(), $factorySmtpSettings->getPort());
        $this->assertNotSame($smtpSettings->getEncryption(), $factorySmtpSettings->getEncryption());
        $this->assertNotSame($smtpSettings->getUsername(), $factorySmtpSettings->getUsername());
        $this->assertNotSame($smtpSettings->getPassword(), $factorySmtpSettings->getPassword());
        $this->assertTrue($factorySmtpSettings->isEligible());
    }

    /**
     * @dataProvider partialParametersDataProvider
     *
     * @param string $uri
     * @param string $method
     * @param array  $parameters
     */
    public function testWithPartialValidRequestValues($uri, $method, $parameters)
    {
        $request = Request::create($uri, $method, $parameters);

        $smtpSettings = new SmtpSettings();
        $factorySmtpSettings = SmtpSettingsFactory::createFromRequest($request);

        $this->assertNotSame($smtpSettings->getHost(), $factorySmtpSettings->getHost());
        $this->assertNotSame($smtpSettings->getPort(), $factorySmtpSettings->getPort());
        $this->assertNotSame($smtpSettings->getEncryption(), $factorySmtpSettings->getEncryption());
        $this->assertSame($smtpSettings->getUsername(), $factorySmtpSettings->getUsername());
        $this->assertSame($smtpSettings->getPassword(), $factorySmtpSettings->getPassword());
        $this->assertTrue($factorySmtpSettings->isEligible());
    }

    /**
     * @dataProvider invalidParametersDataProvider
     *
     * @param string $uri
     * @param string $method
     * @param array  $parameters
     */
    public function testWithInvalidRequestValues($uri, $method, $parameters)
    {
        $request = Request::create($uri, $method, $parameters);
        $factorySmtpSettings = SmtpSettingsFactory::createFromRequest($request);

        $this->assertFalse($factorySmtpSettings->isEligible());
    }

    public function validParametersDataProvider()
    {
        return [
            [
                'uri' => 'http://localhost',
                'method' => 'GET',
                'parameters' => [
                    'email_configuration[oro_email___smtp_settings_host][value]' => 'smtp.orocrm.com',
                    'email_configuration[oro_email___smtp_settings_port][value]' => '465',
                    'email_configuration[oro_email___smtp_settings_encryption][value]' => 'ssl',
                    'email_configuration[oro_email___smtp_settings_username][value]' => 'some_user',
                    'email_configuration[oro_email___smtp_settings_password][value]' => 'some_pass',
                ]
            ],
            [
                'uri' => 'http://localhost',
                'method' => 'POST',
                'parameters' => [
                    'email_configuration[oro_email___smtp_settings_host][value]' => 'smtp.orocrm.com',
                    'email_configuration[oro_email___smtp_settings_port][value]' => '587',
                    'email_configuration[oro_email___smtp_settings_encryption][value]' => 'tls',
                    'email_configuration[oro_email___smtp_settings_username][value]' => 'some_user',
                    'email_configuration[oro_email___smtp_settings_password][value]' => 'some_pass',
                ]
            ],
        ];
    }

    public function partialParametersDataProvider()
    {
        return [
            [
                'uri' => 'http://localhost',
                'method' => 'GET',
                'parameters' => [
                    'email_configuration[oro_email___smtp_settings_host][value]' => 'smtp.orocrm.com',
                    'email_configuration[oro_email___smtp_settings_port][value]' => '465',
                    'email_configuration[oro_email___smtp_settings_encryption][value]' => '',
                ]
            ],
            [
                'uri' => 'http://localhost',
                'method' => 'POST',
                'parameters' => [
                    'email_configuration[oro_email___smtp_settings_host][value]' => 'smtp.orocrm.com',
                    'email_configuration[oro_email___smtp_settings_port][value]' => '587',
                    'email_configuration[oro_email___smtp_settings_encryption][value]' => 'tls',
                ]
            ],
        ];
    }

    public function invalidParametersDataProvider()
    {
        return [
            [
                'uri' => 'http://localhost',
                'method' => 'GET',
                'parameters' => [
                    'email_configuration[oro_email___smtp_settings_host][value]' => '',
                    'email_configuration[oro_email___smtp_settings_port][value]' => '',
                    'email_configuration[oro_email___smtp_settings_encryption][value]' => '',
                ]
            ],
            [
                'uri' => 'http://localhost',
                'method' => 'POST',
                'parameters' => [
                    'email_configuration[oro_email___smtp_settings_host][value]' => '',
                    'email_configuration[oro_email___smtp_settings_port][value]' => '',
                    'email_configuration[oro_email___smtp_settings_encryption][value]' => '',
                ]
            ],
        ];
    }
}
