<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Client\EmailClient;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Isolators that purges MailCatcher before test
 */
class MailCatcherIsolator implements IsolatorInterface
{
    public function __construct(private EmailClient $emailClient)
    {
    }

    #[\Override]
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Purge MailCatcher storage</info>');
        try {
            $this->emailClient->purge([
                'timeout'         => 2,
                'connect_timeout' => 2,
            ]);
        } catch (ClientExceptionInterface $e) {
            $event->writeln('<error>MailCatcher: ' . $e->getMessage() . '</error>');
        }
    }

    #[\Override]
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    #[\Override]
    public function afterTest(AfterIsolatedTestEvent $event)
    {
    }

    #[\Override]
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    #[\Override]
    public function isApplicable(ContainerInterface $container)
    {
        return true;
    }

    #[\Override]
    public function restoreState(RestoreStateEvent $event)
    {
    }

    #[\Override]
    public function isOutdatedState()
    {
        return false;
    }

    #[\Override]
    public function getName()
    {
        return 'MailCatcher';
    }

    #[\Override]
    public function getTag()
    {
        return 'mail';
    }
}
