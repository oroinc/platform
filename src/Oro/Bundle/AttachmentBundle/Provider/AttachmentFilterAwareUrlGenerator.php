<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentHashProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * URL generator for files. Adds filterMd5 to parameters when filter is present.
 */
class AttachmentFilterAwareUrlGenerator implements UrlGeneratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var AttachmentHashProvider
     */
    private $attachmentHashProvider;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        AttachmentHashProvider $attachmentUrlProvider
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->attachmentHashProvider = $attachmentUrlProvider;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if (!empty($parameters['filter'])) {
            $parameters['filterMd5'] = $this->getFilterHash($parameters['filter']);
        }

        try {
            $url = (string)$this->urlGenerator->generate($name, $parameters, $referenceType);
            // Catches only InvalidParameterException because it is the only one that can be caused during normal
            // runtime, other exceptions should lead to direct fix.
        } catch (InvalidParameterException $e) {
            $url = '';
            $this->logger->warning(
                sprintf(
                    'Failed to generate file url by route "%s" with parameters: %s',
                    $name,
                    json_encode($parameters)
                ),
                ['e' => $e]
            );
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context): void
    {
        $this->urlGenerator->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): RequestContext
    {
        return $this->urlGenerator->getContext();
    }

    /**
     * Important: To maintain backward compatibility, to build hash, do not use 'post_processors' parameter unless the
     * system configuration has been changed.
     *
     * Default processors configuration (described in 'dimensions' or 'liip_imagine' configuration) have a higher
     * priority than system configuration. To maintain backward compatibility with previous versions,
     * need to check which processors configuration are used in filter and cover the following cases:
     * - Processors configuration are exists, it is necessary use them and ignore system configuration.
     * - Keep backward compatibility. If processor configuration does not exist, then not need
     *   to update(add 'post_processors' configuration to filter) hash, provided that the new system configuration
     *   has default value.
     * - If the system configuration has changed and is not equivalent to the prevent(default) configuration,
     *   then build a hash with the 'post_processors' parameter.
     */
    public function getFilterHash(string $filterName): string
    {
        return $this->attachmentHashProvider->getFilterConfigHash($filterName);
    }
}
