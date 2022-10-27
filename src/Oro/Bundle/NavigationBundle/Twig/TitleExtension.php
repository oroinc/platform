<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to render page (navigation) titles:
 *   - oro_title_render
 *   - oro_title_render_short
 *   - oro_title_render_serialized
 *
 * Provides a Twig tag to work with page (navigation) titles:
 *   - oro_title_set
 */
class TitleExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private array $templateFileTitleDataStack = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_title_render', [$this, 'render']),
            new TwigFunction('oro_title_render_short', [$this, 'renderShort']),
            new TwigFunction('oro_title_render_serialized', [$this, 'renderSerialized']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return [
            new TitleSetTokenParser()
        ];
    }

    /**
     * Renders title
     *
     * @param string|null $titleData
     * @param string|null $menuName
     *
     * @return string
     */
    public function render($titleData = null, $menuName = null)
    {
        $route = $this->getCurrenRoute();

        return $this->getTitleService()
            ->loadByRoute($route, $menuName)
            ->setData($this->getTitleData())
            ->render([], $titleData, null, null, true);
    }

    /**
     * Renders short title
     *
     * @param string|null $titleData
     * @param string|null $menuName
     *
     * @return string
     */
    public function renderShort($titleData = null, $menuName = null)
    {
        $route = $this->getCurrenRoute();

        return $this->getTitleService()
            ->loadByRoute($route, $menuName)
            ->setData($this->getTitleData())
            ->render([], $titleData, null, null, true, true);
    }

    /**
     * Returns json serialized data
     *
     * @param string|null $menuName
     *
     * @return string
     */
    public function renderSerialized($menuName = null)
    {
        $route = $this->getCurrenRoute();

        return $this->getTitleService()
            ->loadByRoute($route, $menuName)
            ->setData($this->getTitleData())
            ->getSerialized();
    }

    /**
     * Set title options.
     *
     * Options of all calls from template files will be merged in reverse order and set to title service before
     * rendering. Options from children templates will override with parents. This approach is required to implement
     * extend behavior of oro_title_render_* functions in templates, because by default in Twig children templates
     * are executed first.
     *
     * @param array $options
     * @param string|null $templateScope
     * @return TitleExtension
     */
    public function set(array $options = [], $templateScope = null)
    {
        $this->addTitleData($options, $templateScope);
        return $this;
    }

    /**
     * @param array $options
     * @param string|null $templateScope
     */
    private function addTitleData(array $options = [], $templateScope = null)
    {
        if (!$templateScope) {
            $backtrace = debug_backtrace(false);
            if (!empty($backtrace[1]['file'])) {
                $templateScope = md5($backtrace[1]['file']);
            } else {
                $templateScope = md5(uniqid('twig_title', true)); // random string
            }
        }

        $this->templateFileTitleDataStack[$templateScope][] = $options;
    }

    private function getTitleData(): array
    {
        $result = [];
        if ($this->templateFileTitleDataStack) {
            $reversedTemplateFileTitleDataStack = array_reverse($this->templateFileTitleDataStack);
            foreach ($reversedTemplateFileTitleDataStack as $templateOptions) {
                foreach ($templateOptions as $options) {
                    $result[] = $options;
                }
            }
            if ($result) {
                $result = array_replace_recursive(...$result);
            }
        }

        return $result;
    }

    private function getCurrenRoute(): ?string
    {
        $request = $this->getRequest();

        return null !== $request ? $request->get('_route') : null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_navigation.title_service' => TitleServiceInterface::class,
            RequestStack::class,
        ];
    }

    private function getTitleService(): TitleServiceInterface
    {
        return $this->container->get('oro_navigation.title_service');
    }

    protected function getRequest(): ?Request
    {
        return $this->container->get(RequestStack::class)->getCurrentRequest();
    }
}
