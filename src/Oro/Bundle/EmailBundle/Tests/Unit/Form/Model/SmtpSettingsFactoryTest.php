<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Model;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Symfony\Component\HttpFoundation\Request;

class SmtpSettingsFactoryTest extends \PHPUnit\Framework\TestCase
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
                    'host' => 'smtp.orocrm.com',
                    'port' => '465',
                    'encryption' => 'ssl',
                    'username' => 'some_user',
                    'password' => 'some_pass',
                ]
            ],
            [
                'uri' => 'http://localhost',
                'method' => 'POST',
                'parameters' => [
                    'host' => 'smtp.orocrm.com',
                    'port' => '587',
                    'encryption' => 'tls',
                    'username' => 'some_user',
                    'password' => 'some_pass',
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
                    'host' => 'smtp.orocrm.com',
                    'port' => '465',
                    'encryption' => '',
                ]
            ],
            [
                'uri' => 'http://localhost',
                'method' => 'POST',
                'parameters' => [
                    'host' => 'smtp.orocrm.com',
                    'port' => '587',
                    'encryption' => 'tls',
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
                    'host' => '',
                    'port' => '',
                    'encryption' => '',
                ]
            ],
            [
                'uri' => 'http://localhost',
                'method' => 'POST',
                'parameters' => [
                    'host' => '',
                    'port' => '',
                    'encryption' => '',
                ]
            ],
        ];
    }
}
