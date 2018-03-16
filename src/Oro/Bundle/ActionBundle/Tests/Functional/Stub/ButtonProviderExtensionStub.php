<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;

class ButtonProviderExtensionStub implements ButtonProviderExtensionInterface
{
    /** @var callable */
    private $isAvailableCallback;

    /** @var callable */
    private $supportsCallback;

    /** @var callable */
    private $findCallback;

    /**
     * @param callable|null $find
     * @param callable|null $isAvailable
     * @param callable|null $supports
     */
    public function __construct(callable $find = null, callable $isAvailable = null, callable $supports = null)
    {
        $this->findCallback = $find ?: function () {
            return [new ButtonStub()];
        };
        $this->isAvailableCallback = $isAvailable ?: function () {
            return true;
        };
        $this->supportsCallback = $supports ?: function (ButtonInterface $button) {
            return $button instanceof ButtonStub;
        };
    }

    /**
     * {@inheritdoc}
     */
    public function find(ButtonSearchContext $buttonSearchContext)
    {
        return call_user_func($this->findCallback, $buttonSearchContext);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(
        ButtonInterface $button,
        ButtonSearchContext $buttonSearchContext,
        Collection $errors = null
    ) {
        return call_user_func($this->isAvailableCallback, $button, $buttonSearchContext, $errors);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ButtonInterface $button)
    {
        return call_user_func($this->supportsCallback, $button);
    }
}
