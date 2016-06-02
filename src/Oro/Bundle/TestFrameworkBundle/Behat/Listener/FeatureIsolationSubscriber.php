<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Dumper\DbDumperInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\ReferenceRepository;
use Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\KernelServiceFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FeatureIsolationSubscriber implements EventSubscriberInterface
{
    /** @var  DbDumperInterface  */
    protected $dbDumper;

    /** @var FixtureLoader */
    protected $fixtureLoader;

    /** @var KernelServiceFactory */
    protected $kernelServiceFactory;

    /** @var ReferenceRepository  */
    protected $referenceRepository;

    /**
     * @param DbDumperInterface $dbDumper
     * @param FixtureLoader $fixtureLoader
     * @param KernelServiceFactory $kernelServiceFactory
     * @param ReferenceRepository $referenceRepository
     */
    public function __construct(
        DbDumperInterface $dbDumper,
        FixtureLoader $fixtureLoader,
        KernelServiceFactory $kernelServiceFactory,
        ReferenceRepository $referenceRepository
    ) {
        $this->dbDumper = $dbDumper;
        $this->fixtureLoader = $fixtureLoader;
        $this->kernelServiceFactory = $kernelServiceFactory;
        $this->referenceRepository = $referenceRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeSuiteTested::BEFORE => ['injectSuite', 5],
            BeforeFeatureTested::BEFORE  => ['beforeFeature', 100],
            AfterFeatureTested::AFTER  => ['afterFeature', -100],
        ];
    }

    /**
     * @param BeforeSuiteTested $event
     */
    public function injectSuite(BeforeSuiteTested $event)
    {
        $this->fixtureLoader->setSuite($event->getSuite());
    }

    /**
     * @param BeforeFeatureTested $event
     */
    public function beforeFeature(BeforeFeatureTested $event)
    {
        $this->bootKernel();
        $this->initDependencies();
        $this->loadFixtures($event->getFeature()->getTags());
    }

    /**
     * @param AfterFeatureTested $event
     */
    public function afterFeature(AfterFeatureTested $event)
    {
        $this->clearDependencies();
        $this->dbRestore();
        $this->shutdownKernel();
    }

    public function bootKernel()
    {
        $this->kernelServiceFactory->boot();
    }

    public function initDependencies()
    {
        $this->referenceRepository->init();
        $this->fixtureLoader->initReferences($this->referenceRepository);
    }

    /**
     * @param array $tags
     */
    public function loadFixtures(array $tags)
    {
        $fixturesTags = array_filter($tags, function($tag) { return strpos($tag, 'fixture-') === 0; });

        if (0 === count($fixturesTags)) {
            return;
        }

        foreach ($fixturesTags as $tag) {
            $filename = substr($tag, 8);
            $this->fixtureLoader->loadFixtureFile($filename);
        }
    }

    public function clearDependencies()
    {
        $this->referenceRepository->clear();
        $this->fixtureLoader->clearReferences();
    }

    public function dbRestore()
    {
        $this->dbDumper->restoreDb();
    }

    public function shutdownKernel()
    {
        $this->kernelServiceFactory->shutdown();
    }
}
