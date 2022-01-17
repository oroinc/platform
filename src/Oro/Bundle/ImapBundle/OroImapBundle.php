<?php

namespace Oro\Bundle\ImapBundle;

use Oro\Bundle\ImapBundle\DependencyInjection\Compiler\CredentialsNotificationSenderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * OroImapBundle bundle class
 */
class OroImapBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CredentialsNotificationSenderPass());
    }
}
