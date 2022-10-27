<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Environment;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;

class TestButtonProviderExtension implements ButtonProviderExtensionInterface
{
    /** @var ButtonProviderExtensionInterface|null */
    private $decoratedExtension;

    public function setDecoratedExtension(ButtonProviderExtensionInterface $decoratedExtension = null)
    {
        $this->decoratedExtension = $decoratedExtension;
    }

    /**
     * {@inheritDoc}
     */
    public function find(ButtonSearchContext $buttonSearchContext)
    {
        if (null === $this->decoratedExtension) {
            return [];
        }

        return $this->decoratedExtension->find($buttonSearchContext);
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(
        ButtonInterface $button,
        ButtonSearchContext $buttonSearchContext,
        Collection $errors = null
    ) {
        if (null === $this->decoratedExtension) {
            return false;
        }

        return $this->decoratedExtension->isAvailable($button, $buttonSearchContext, $errors);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(ButtonInterface $button)
    {
        return null !== $this->decoratedExtension && $this->decoratedExtension->supports($button);
    }
}
