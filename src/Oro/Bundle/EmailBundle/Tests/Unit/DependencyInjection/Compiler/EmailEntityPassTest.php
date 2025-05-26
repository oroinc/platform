<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailEntityPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EmailEntityPassTest extends TestCase
{
    public function testProcess(): void
    {
        $entityProxyNamespace = 'namespace';
        $entityCacheDir = '/cache/dir';
        $compilerPass = new EmailEntityPass($entityProxyNamespace, $entityCacheDir);

        $container = new ContainerBuilder();
        $compilerPass->process($container);

        $this->assertEquals($entityCacheDir, $container->getParameter('oro_email.entity.cache_dir'));
        $this->assertEquals($entityProxyNamespace, $container->getParameter('oro_email.entity.cache_namespace'));
        $this->assertEquals('%sProxy', $container->getParameter('oro_email.entity.proxy_name_template'));
    }
}
