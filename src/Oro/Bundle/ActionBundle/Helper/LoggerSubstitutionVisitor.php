<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerSubstitutionVisitor implements SubstitutionVisitorInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function visit($target, $replacement, $targetKey, $replacementKey)
    {
        $this->logger->debug(
            sprintf('Action substitution. "%s" substituted by "%s"', $targetKey, $replacementKey)
        );
    }
}
