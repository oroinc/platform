<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * URL generator for attachments. Adds filterMd5 to parameters when filter is present.
 */
class AttachmentFilterAwareUrlGenerator implements UrlGeneratorInterface
{
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
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        if (!empty($parameters['filter'])) {
            $parameters['filterMd5'] = $this->getFilterHash($parameters['filter']);
        }

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->urlGenerator->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
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
