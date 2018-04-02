<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Provider\Event\OnButtonsMatched;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ButtonProvider
{
    /** @var ButtonProviderExtensionInterface[] */
    protected $extensions;

    /** @var LoggerInterface */
    protected $logger;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ButtonProviderExtensionInterface $extension
     */
    public function addExtension(ButtonProviderExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
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

        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(OnButtonsMatched::NAME, new OnButtonsMatched($collection));
        }

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

    /**
     * @param ArrayCollection $errors
     */
    protected function processErrors(ArrayCollection $errors)
    {
        foreach ($errors as $error) {
            $this->logger->error($error['message'], $error['parameters']);
        }
    }
}
