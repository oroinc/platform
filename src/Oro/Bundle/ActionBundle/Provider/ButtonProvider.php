<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Provider\Event\OnButtonsMatched;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The registry of action buttons.
 */
class ButtonProvider
{
    /** @var iterable|ButtonProviderExtensionInterface[] */
    private $extensions;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param iterable|ButtonProviderExtensionInterface[] $extensions
     * @param EventDispatcherInterface                    $eventDispatcher
     * @param LoggerInterface                             $logger
     */
    public function __construct(
        iterable $extensions,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->extensions = $extensions;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @param ButtonSearchContext $searchContext
     * @return ButtonsCollection
     */
    public function match(ButtonSearchContext $searchContext)
    {
        $collection = new ButtonsCollection();

        foreach ($this->extensions as $extension) {
            $collection->consume($extension, $searchContext);
        }

        $this->eventDispatcher->dispatch(new OnButtonsMatched($collection), OnButtonsMatched::NAME);

        return $collection;
    }

    /**
     * @param ButtonSearchContext $searchContext
     * @return ButtonInterface[]
     */
    public function findAvailable(ButtonSearchContext $searchContext)
    {
        $errors = new ArrayCollection();

        $storage = $this->match($searchContext)->filter(
            function (
                ButtonInterface $button,
                ButtonProviderExtensionInterface $extension
            ) use (
                $searchContext,
                $errors
            ) {
                return $extension->isAvailable($button, $searchContext, $errors);
            }
        );

        $this->processErrors($errors);

        return $storage->toList();
    }

    /**
     * @param ButtonSearchContext $searchContext
     * @return ButtonInterface[]
     */
    public function findAll(ButtonSearchContext $searchContext)
    {
        $errors = new ArrayCollection();

        $mapped = $this->match($searchContext)->map(
            function (
                ButtonInterface $button,
                ButtonProviderExtensionInterface $extension
            ) use (
                $searchContext,
                $errors
            ) {
                $newButton = clone $button;
                $newButton->getButtonContext()->setEnabled(
                    $extension->isAvailable($newButton, $searchContext, $errors)
                );

                return $newButton;
            }
        );

        $this->processErrors($errors);

        return $mapped->toList();
    }

    /**
     * @param ButtonSearchContext $searchContext
     *
     * @return bool
     */
    public function hasButtons(ButtonSearchContext $searchContext)
    {
        foreach ($this->extensions as $extension) {
            if (count($extension->find($searchContext))) {
                return true;
            }
        }

        return false;
    }

    private function processErrors(ArrayCollection $errors)
    {
        foreach ($errors as $error) {
            $this->logger->error($error['message'], $error['parameters']);
        }
    }
}
