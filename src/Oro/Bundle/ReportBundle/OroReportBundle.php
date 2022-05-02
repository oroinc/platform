<?php

namespace Oro\Bundle\ReportBundle;

use Oro\Bundle\ReportBundle\DependencyInjection\Compiler\DbalConnectionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroReportBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DbalConnectionCompilerPass());
    }
}
