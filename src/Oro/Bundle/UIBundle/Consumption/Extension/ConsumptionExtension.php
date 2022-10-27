<?php

namespace Oro\Bundle\UIBundle\Consumption\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Updates the current request context with application url from the system configuration for the required topics.
 */
class ConsumptionExtension extends AbstractExtension
{
    private RequestContext $context;
    private ConfigManager $configManager;
    private array $topicNames = [];

    public function __construct(RequestContext $context, ConfigManager $configManager)
    {
        $this->context = $context;
        $this->configManager = $configManager;
    }

    public function addTopicName(string $topicName): void
    {
        $this->topicNames[] = $topicName;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context): void
    {
        if (!$this->isApplicable($context->getMessage())) {
            return;
        }

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

    private function isApplicable(?MessageInterface $message): bool
    {
        return $message && \in_array($message->getProperty(Config::PARAMETER_TOPIC_NAME), $this->topicNames, true);
    }
}
