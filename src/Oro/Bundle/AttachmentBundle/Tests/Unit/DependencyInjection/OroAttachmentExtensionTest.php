<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroAttachmentExtensionTest extends \PHPUnit\Framework\TestCase
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
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
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
