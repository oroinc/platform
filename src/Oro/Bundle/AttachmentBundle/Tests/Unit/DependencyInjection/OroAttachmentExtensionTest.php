<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\CumulativeResourceManager;

class OroAttachmentExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $configuration;

    public function testLoad()
    {
        CumulativeResourceManager::getInstance()->clear();

        $extension = new OroAttachmentExtension();
        $configs = array();
        $isCalled = false;
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->any())
            ->method('setParameter')
            ->will(
                $this->returnCallback(
                    function ($name, $value) use (&$isCalled) {
                        if ($name == 'oro_attachment.files' && is_array($value)) {
                            $isCalled = true;
                        }
                    }
                )
            );

        $extension->load($configs, $container);
        $this->assertTrue($isCalled);
    }
}
