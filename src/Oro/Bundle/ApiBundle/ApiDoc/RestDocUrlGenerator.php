<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The default implementation of API view URL generator.
 */
class RestDocUrlGenerator implements RestDocUrlGeneratorInterface
{
    public const ROUTE = 'nelmio_api_doc_index';

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var string[] */
    private $views;

    /** @var string|null */
    private $defaultView;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param string[]              $views
     * @param string|null           $defaultView
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, array $views, ?string $defaultView)
    {
        $this->urlGenerator = $urlGenerator;
        $this->views = $views;
        $this->defaultView = $defaultView;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $view): string
    {
        if (!\in_array($view, $this->views, true)) {
            throw new \InvalidArgumentException(\sprintf('Undefined API view: %s.', $view));
        }

        $parameters = [];
        if (!$this->isDefaultView($view)) {
            $parameters['view'] = $view;
        }

        return $this->urlGenerator->generate(self::ROUTE, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param string $view
     *
     * @return bool
     */
    private function isDefaultView(string $view): bool
    {
        return $this->defaultView && $view === $this->defaultView;
    }
}
