<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\UserBundle\DependencyInjection\OroUserExtension;

class OroUserExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $configuration;

    public function testLoadWithDefaults()
    {
        $this->createEmptyConfiguration();

        $this->assertParameter(86400, 'oro_user.reset.ttl');
    }

    public function testLoad()
    {
        $this->createFullConfiguration();

        $this->assertParameter(1800, 'oro_user.reset.ttl');
    }

    public function testPrepend()
    {
        $inputSecurityConfig = [
            'firewalls' => [
                'main' => ['main_config'],
                'first' => ['first_config'],
                'second' => ['second_config'],
            ]
        ];
        $expectedSecurityConfig = [
            'firewalls' => [
                'first' => ['first_config'],
                'second' => ['second_config'],
                'main' => ['main_config'],
            ]
        ];

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExtendedContainerBuilder $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Oro\Component\DependencyInjection\ExtendedContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->once())
            ->method('getExtensionConfig')
            ->with('security')
            ->willReturn([$inputSecurityConfig]);
        $containerBuilder->expects($this->once())
            ->method('setExtensionConfig')
            ->with('security', [$expectedSecurityConfig]);

        $extension = new OroUserExtension();
        $extension->prepend($containerBuilder);
    }

    protected function createEmptyConfiguration()
    {
        $this->configuration = new ContainerBuilder();

        $loader = new OroUserExtension();
        $config = $this->getEmptyConfig();

        $loader->load(array($config), $this->configuration);

        $this->assertTrue($this->configuration instanceof ContainerBuilder);
    }

    protected function createFullConfiguration()
    {
        $this->configuration = new ContainerBuilder();

        $loader = new OroUserExtension();
        $config = $this->getFullConfig();

        $loader->load(array($config), $this->configuration);

        $this->assertTrue($this->configuration instanceof ContainerBuilder);
    }

    /**
     * @return array
     */
    protected function getEmptyConfig()
    {
        $yaml   = '';
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    protected function getFullConfig()
    {
        $yaml = <<<EOF
reset:
    ttl: 1800
EOF;
        $parser = new Parser();

        return  $parser->parse($yaml);
    }

    /**
     * @param mixed  $value
     * @param string $key
     */
    protected function assertParameter($value, $key)
    {
        $this->assertEquals($value, $this->configuration->getParameter($key), sprintf('%s parameter is correct', $key));
    }

    protected function tearDown()
    {
        unset($this->configuration);
    }
}
