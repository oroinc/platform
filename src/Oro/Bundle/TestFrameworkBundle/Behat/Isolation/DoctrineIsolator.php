<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\OroAliceLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Exception\RuntimeException;

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

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $event->writeln('<info>Load fixtures</info>');

        $doctrine = $this->kernel->getContainer()->get('doctrine');
        $this->aliceLoader->setDoctrine($doctrine);
        $referenceRepository = $this->aliceLoader->getReferenceRepository();
        $referenceRepository->clear();

        foreach ($this->initializers as $initializer) {
            $initializer->init($doctrine, $referenceRepository);
        }

        $this->loadFixtures($event);
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
    private function getFixtureFiles(array $tags)
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
