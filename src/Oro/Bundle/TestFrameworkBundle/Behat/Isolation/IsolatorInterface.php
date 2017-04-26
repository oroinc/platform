<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface IsolatorInterface
{
    /**
     * @param BeforeStartTestsEvent $event
     * @return void
     */
    public function start(BeforeStartTestsEvent $event);

    /**
     * @param BeforeIsolatedTestEvent $event
     * @return void
     */
    public function beforeTest(BeforeIsolatedTestEvent $event);

    /**
     * @param AfterIsolatedTestEvent $event
     * @return void
     */
    public function afterTest(AfterIsolatedTestEvent $event);

    /**
     * @param AfterFinishTestsEvent $event
     * @return void
     */
    public function terminate(AfterFinishTestsEvent $event);

    /**
     * @param ContainerInterface $container
     * @return bool
     */
    public function isApplicable(ContainerInterface $container);

    /**
     * Restore initial state
     * @param RestoreStateEvent $event
     */
    public function restoreState(RestoreStateEvent $event);

    /**
     * @return bool
     */
    public function isOutdatedState();

    /**
     * @return string
     */
    public function getName();

    /**
     * Return tag for Isolator that can be used for skip isolation
     * @return string
     */
    public function getTag();
}
