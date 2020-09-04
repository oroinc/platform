<?php

namespace Oro\Bundle\UIBundle\Consumption\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\Routing\RequestContext;

/**
 * Updates the consumer state with the current message processor and message.
 */
class ConsumptionExtension extends AbstractExtension
{
    /** @var RequestContext */
    private $context;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param RequestContext $context
     * @param ConfigManager $configManager
     */
    public function __construct(RequestContext $context, ConfigManager $configManager)
    {
        $this->context = $context;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context): void
    {
        $url = $this->configManager->get('oro_ui.application_url');

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme) {
            $this->context->setScheme($scheme);
        }

        $host = parse_url($url, PHP_URL_HOST);
        if ($host) {
            $this->context->setHost($host);
        }

        $port = parse_url($url, PHP_URL_PORT);
        if ($port) {
            if ($scheme && $scheme === 'https') {
                $this->context->setHttpsPort($port);
            } else {
                $this->context->setHttpPort($port);
            }
        }

        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $this->context->setBaseUrl($path);
        }
    }
}
