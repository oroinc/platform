<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;

class ButtonProvider
{
    /** @var ButtonProviderExtensionInterface[] */
    protected $extensions;

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

        return $collection;
    }

    /**
     * @param ButtonSearchContext $searchContext
     * @return ButtonInterface[]
     */
    public function findAvailable(ButtonSearchContext $searchContext)
    {
        $storage = $this->match($searchContext)->filter(
            function (ButtonInterface $button, ButtonProviderExtensionInterface $extension) use ($searchContext) {
                return $extension->isAvailable($button, $searchContext);
            }
        );

        return $storage->toList();
    }

    /**
     * @param ButtonSearchContext $searchContext
     * @return ButtonInterface[]
     */
    public function findAll(ButtonSearchContext $searchContext)
    {
        $mapped = $this->match($searchContext)->map(
            function (ButtonInterface $button, ButtonProviderExtensionInterface $extension) use ($searchContext) {
                $newButton = clone $button;
                $newButton->getButtonContext()->setEnabled(
                    $extension->isAvailable($newButton, $searchContext)
                );

                return $newButton;
            }
        );

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
}
