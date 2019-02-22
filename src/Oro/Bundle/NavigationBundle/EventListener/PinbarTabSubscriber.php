<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Oro\Bundle\NavigationBundle\Entity\NavigationItem;
use Oro\Bundle\NavigationBundle\Exception\LogicException;
use Oro\Bundle\NavigationBundle\Provider\PinbarTabTitleProviderInterface;
use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizerInterface;

/**
 * Doctrine subscriber which normalizes PinbarTab URL before it is persisted to database.
 */
class PinbarTabSubscriber implements EventSubscriber
{
    /** @var PinbarTabUrlNormalizerInterface */
    private $pinbarTabUrlNormalizer;

    /** @var PinbarTabTitleProviderInterface */
    private $pinbarTabTitleProvider;

    /** @var string */
    private $pinbarTabClassName;

    /**
     * @param PinbarTabUrlNormalizerInterface $pinbarTabUrlNormalizer
     * @param PinbarTabTitleProviderInterface $pinbarTabTitleProvider
     * @param string $pinbarTabClassName
     */
    public function __construct(
        PinbarTabUrlNormalizerInterface $pinbarTabUrlNormalizer,
        PinbarTabTitleProviderInterface $pinbarTabTitleProvider,
        string $pinbarTabClassName
    ) {
        $this->pinbarTabUrlNormalizer = $pinbarTabUrlNormalizer;
        $this->pinbarTabTitleProvider = $pinbarTabTitleProvider;
        $this->pinbarTabClassName = $pinbarTabClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::prePersist];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!is_a($entity, $this->pinbarTabClassName)) {
            return;
        }

        /** @var NavigationItem $navigationItem */
        $navigationItem = $entity->getItem();
        if (!$navigationItem) {
            throw new LogicException('PinbarTab does not contain NavigationItem');
        }

        $normalizedUrl = $this->pinbarTabUrlNormalizer->getNormalizedUrl($navigationItem->getUrl());
        $entity->setValues(['url' => $normalizedUrl]);

        [$title, $titleShort] = $this->pinbarTabTitleProvider->getTitles($navigationItem, $this->pinbarTabClassName);
        $entity->setTitle($title);
        $entity->setTitleShort($titleShort);
    }
}
