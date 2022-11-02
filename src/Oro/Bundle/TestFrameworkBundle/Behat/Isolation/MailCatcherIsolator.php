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

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return true;
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        return false;
    }

    public function getName()
    {
        return 'MailCatcher';
    }

    public function getTag()
    {
        return 'mail';
    }
}
