<?php

namespace Oro\Bundle\CalendarBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CalendarBundle\DependencyInjection\Compiler\CalendarProviderPass;
use Oro\Bundle\CalendarBundle\DependencyInjection\Compiler\RecurrenceStrategyCompilerPass;
use Oro\Bundle\CalendarBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;

class OroCalendarBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CalendarProviderPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new RecurrenceStrategyCompilerPass());
    }
}
