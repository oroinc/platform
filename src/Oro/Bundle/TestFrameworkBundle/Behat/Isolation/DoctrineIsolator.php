<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\OroAliceLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener\RestrictFlushInitializerListener;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Disables all optional listeners (except list of required listeners) before loading data fixtures and enables them
 * again after loading data completed. It will increase performance of data fixtures, because many listeners don't
 * required during data loading (like data audit listener).
 */
class DoctrineIsolator implements IsolatorInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    /**
     * @var ReferenceRepositoryInitializerInterface[]
     */
    protected $initializers = [];

    /**
     * @var OroAliceLoader
     */
    protected $aliceLoader;

    /**
     * @var array
     */
    protected $requiredListeners = [];

    /**
     * @param KernelInterface $kernel
     * @param FixtureLoader $fixtureLoader
     * @param OroAliceLoader $aliceLoader
     */
    public function __construct(
        KernelInterface $kernel,
        FixtureLoader $fixtureLoader,
        OroAliceLoader $aliceLoader
    ) {
        $this->kernel = $kernel;
        $this->fixtureLoader = $fixtureLoader;
        $this->aliceLoader = $aliceLoader;
    }

    /**
     * @param ReferenceRepositoryInitializerInterface $initializer
     */
    public function addInitializer(ReferenceRepositoryInitializerInterface $initializer)
    {
        $this->initializers[] = $initializer;
    }

    /**
     * @param array $requiredListeners
     */
    public function setRequiredListeners(array $requiredListeners)
    {
        $this->requiredListeners = $requiredListeners;
    }

    public function initReferences()
    {
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        $this->aliceLoader->setDoctrine($doctrine);

        $referenceRepository = $this->aliceLoader->getReferenceRepository();
        $referenceRepository->clear();

        /** @var EntityManager $em */
        $em = $doctrine->getManager();
        $restrictListener = new RestrictFlushInitializerListener();
        $em->getEventManager()->addEventListener([Events::preFlush], $restrictListener);

        foreach ($this->initializers as $initializer) {
            $initializer->init($doctrine, $referenceRepository);
        }

        $em->getEventManager()->removeEventListener([Events::preFlush], $restrictListener);
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $manager = $this->kernel->getContainer()->get('oro_platform.optional_listeners.manager');
        $listenersToDisable = array_filter($manager->getListeners(), function ($listener) {
            return !in_array($listener, $this->requiredListeners, true);
        });

        if ($listenersToDisable) {
            $event->writeln('<info>Disabling optional listeners:</info>');
            foreach ($listenersToDisable as $listener) {
                $manager->disableListener($listener);
                $event->writeln(sprintf('<comment>  => %s</comment>', $listener));
            }
        }

        $event->writeln('<info>Load fixtures</info>');

        $this->initReferences();
        $this->loadFixtures($event);

        if ($listenersToDisable) {
            $event->writeln('<info>Enabling optional listeners</info>');
            $manager->enableListeners($listenersToDisable);
        }
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $this->kernel->getContainer()->get('doctrine')->getManager()->clear();
        $this->kernel->getContainer()->get('doctrine')->resetManager();
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

    /** {@inheritdoc} */
    public function getName()
    {
        return 'Doctrine';
    }

    /** {@inheritdoc} */
    public function getTag()
    {
        return 'doctrine';
    }

    /**
     * @param array $tags
     * @return array
     */
    public function getFixtureFiles(array $tags)
    {
        if (!$tags) {
            return [];
        }

        $fixturesFileNames = array_filter(
            array_map(
                function ($tag) {
                    if (strpos($tag, 'fixture-') === 0) {
                        return substr($tag, 8);
                    }

                    return null;
                },
                $tags
            )
        );

        return $fixturesFileNames;
    }

    /**
     * @param BeforeIsolatedTestEvent $event
     */
    private function loadFixtures(BeforeIsolatedTestEvent $event)
    {
        $fixtureFiles = $this->getFixtureFiles($event->getTags());

        foreach ($fixtureFiles as $fixtureFile) {
            try {
                $this->fixtureLoader->loadFixtureFile($fixtureFile);
            } catch (\Exception $e) {
                throw new RuntimeException(
                    sprintf('Exception while loading "%s" fixture file', $fixtureFile),
                    0,
                    $e
                );
            }
        }
    }
}
