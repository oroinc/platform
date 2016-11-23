<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\ReferenceRepositoryInitializer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FixturesSubscriber implements EventSubscriberInterface
{
    /** @var FixtureLoader */
    protected $fixtureLoader;

    /** @var ReferenceRepositoryInitializer  */
    protected $referenceRepositoryInitializer;

    /**
     * @param FixtureLoader $fixtureLoader
     * @param ReferenceRepositoryInitializer $referenceRepositoryInitializer
     */
    public function __construct(
        FixtureLoader $fixtureLoader,
        ReferenceRepositoryInitializer $referenceRepositoryInitializer
    ) {
        $this->fixtureLoader = $fixtureLoader;
        $this->referenceRepositoryInitializer = $referenceRepositoryInitializer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [BeforeFeatureTested::BEFORE  => ['beforeFeature', 100]];
    }

    /**
     * @param BeforeFeatureTested $event
     */
    public function beforeFeature(BeforeFeatureTested $event)
    {
        $this->initDependencies();
        $this->loadFixtures($event->getFeature()->getTags());
    }

    protected function initDependencies()
    {
        $this->referenceRepositoryInitializer->init();
    }

    /**
     * @param array $tags
     */
    protected function loadFixtures(array $tags)
    {
        $fixturesTags = array_filter($tags, function ($tag) {
            return strpos($tag, 'fixture-') === 0;
        });

        if (0 === count($fixturesTags)) {
            return;
        }

        foreach ($fixturesTags as $tag) {
            $filename = substr($tag, 8);
            $this->fixtureLoader->loadFixtureFile($filename);
        }
    }
}
