<?php

namespace Oro\Bundle\UserBundle;

use Oro\Bundle\UserBundle\DependencyInjection\Compiler\AddLoginFormToCaptchaProtectedPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroUserBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AddLoginFormToCaptchaProtectedPass());
    }
}
