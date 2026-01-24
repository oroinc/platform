<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the contract for test isolators that manage test environment state.
 *
 * Isolators handle the setup, teardown, and restoration of the test environment across
 * test execution. They can be applied selectively based on container configuration and
 * support tagging for selective isolation skipping.
 */
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
