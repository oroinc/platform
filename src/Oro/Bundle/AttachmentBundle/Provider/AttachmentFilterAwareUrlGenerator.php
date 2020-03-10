<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
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
     * @var FilterConfiguration
     */
    private $filterConfiguration;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param FilterConfiguration $filterConfiguration
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, FilterConfiguration $filterConfiguration)
    {
        $this->urlGenerator = $urlGenerator;
        $this->filterConfiguration = $filterConfiguration;
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
            $url = (string) $this->urlGenerator->generate($name, $parameters, $referenceType);
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
     * @param string $filterName
     * @return string
     */
    public function getFilterHash(string $filterName): string
    {
        $filterConfig = $this->filterConfiguration->get($filterName);

        return md5(json_encode($filterConfig));
    }
}
