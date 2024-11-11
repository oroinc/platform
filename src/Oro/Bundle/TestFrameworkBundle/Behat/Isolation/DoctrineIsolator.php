<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Oro\Bundle\TestFrameworkBundle\Behat\Exception\SkippTestExecutionException;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener\RestrictFlushInitializerListener;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AliceFixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Resolver\AliceFixtureReferenceResolver;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * - Disables all optional listeners (except required listeners),
 * - loads data fixtures,
 * - and enables listeners back after completion.
 * It increases the performance of data fixtures load because many listeners
 * are not required during data loading (like data audit listener)
 */
class DoctrineIsolator implements IsolatorInterface
{
    /** @var ReferenceRepositoryInitializerInterface[] */
    protected $initializers = [];

    protected array $requiredListeners = [];

    public function __construct(
        protected KernelInterface $kernel,
        protected FixtureLoader $fixtureLoader,
        protected AliceFixtureLoader $aliceLoader,
        protected AliceFixtureReferenceResolver $fixtureReferenceResolver
    ) {
    }

    public function addInitializer(ReferenceRepositoryInitializerInterface $initializer): void
    {
        $this->initializers[] = $initializer;
    }

    public function setRequiredListeners(array $requiredListeners): void
    {
        $this->requiredListeners = $requiredListeners;
    }

    public function initReferences(): void
    {
        $doctrine = $this->kernel->getContainer()->get('doctrine');

        $referenceRepository = $this->aliceLoader->getReferenceRepository();
        $referenceRepository->clear();

        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $restrictListener = new RestrictFlushInitializerListener();
        $em->getEventManager()->addEventListener([Events::preFlush], $restrictListener);

        foreach ($this->initializers as $initializer) {
            if ($initializer instanceof ContainerAwareInterface) {
                $initializer->setContainer($this->kernel->getContainer());
            }
            $initializer->init($doctrine, $referenceRepository);
        }

        $em->getEventManager()->removeEventListener([Events::preFlush], $restrictListener);
    }

    #[\Override]
    public function start(BeforeStartTestsEvent $event): void
    {
    }

    #[\Override]
    public function beforeTest(BeforeIsolatedTestEvent $event): void
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

    #[\Override]
    public function afterTest(AfterIsolatedTestEvent $event): void
    {
        $this->kernel->getContainer()->get('doctrine')->getManager()->clear();
        $this->kernel->getContainer()->get('doctrine')->resetManager();
        $this->clearAliceIncompleteObjectsState();
    }

    #[\Override]
    public function terminate(AfterFinishTestsEvent $event): void
    {
    }

    #[\Override]
    public function isApplicable(ContainerInterface $container): bool
    {
        return true;
    }

    #[\Override]
    public function restoreState(RestoreStateEvent $event): void
    {
    }

    #[\Override]
    public function isOutdatedState(): bool
    {
        return false;
    }

    #[\Override]
    public function getName(): string
    {
        return 'Doctrine';
    }

    #[\Override]
    public function getTag(): string
    {
        return 'doctrine';
    }

    public function getFixtureFiles(array $tags): array
    {
        if (!$tags) {
            return [];
        }

        return array_filter(array_map(
            function ($tag) {
                return str_starts_with($tag, 'fixture-')
                    ? substr($tag, 8)
                    : null;
            },
            $tags
        ));
    }

    private function loadFixtures(BeforeIsolatedTestEvent $event): void
    {
        $fixtureFiles = $this->getFixtureFiles($event->getTags());

        foreach ($fixtureFiles as $fixtureFile) {
            try {
                $this->fixtureLoader->loadFixtureFile($fixtureFile);
            } catch (\Exception $e) {
                throw new RuntimeException(
                    sprintf(
                        'Exception while loading "%s" fixture file with message: "%s"',
                        $fixtureFile,
                        $e->getMessage()
                    ),
                    0,
                    $e
                );
            }
        }
        usleep(300000);
    }

    private function clearAliceIncompleteObjectsState(): void
    {
        $this->fixtureReferenceResolver->clear();
        foreach ($this->fixtureReferenceResolver->getInstances() as $resolver) {
            $resolver->clear();
        }
    }
}
