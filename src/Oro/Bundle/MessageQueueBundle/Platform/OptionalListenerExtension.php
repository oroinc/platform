<?php

namespace Oro\Bundle\MessageQueueBundle\Platform;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PlatformBundle\Provider\Console\OptionalListenersGlobalOptionsProvider;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Load disabled listeners from messages
 */
class OptionalListenerExtension extends AbstractExtension
{
    /** @var OptionalListenerManager */
    private $optionalListenerManager;

    public function __construct(OptionalListenerManager $optionalListenerManager)
    {
        $this->optionalListenerManager = $optionalListenerManager;
    }

    public function onPreReceived(Context $context)
    {
        $this->optionalListenerManager->enableListeners(
            $this->optionalListenerManager->getListeners()
        );

        $disabledListenersJson = $context
            ->getMessage()
            ->getProperty(OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS);

        if (!$disabledListenersJson) {
            return;
        }

        $disabledListeners = json_decode($disabledListenersJson);

        if (is_array($disabledListeners)) {
            foreach ($disabledListeners as $disabledListener) {
                try {
                    $this->optionalListenerManager->disableListener($disabledListener);
                } catch (\InvalidArgumentException $e) {
                }
            }
        }
    }
}
