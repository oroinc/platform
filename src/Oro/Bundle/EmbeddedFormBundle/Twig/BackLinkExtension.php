<?php

namespace Oro\Bundle\EmbeddedFormBundle\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig filter to generate a back link:
 *   - back_link
 */
class BackLinkExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return RouterInterface
     */
    protected function getRouter()
    {
        return $this->container->get(RouterInterface::class);
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get(TranslatorInterface::class);
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('back_link', [$this, 'backLinkFilter']),
        ];
    }

    /**
     * @param string $string
     * @param string $id
     * @return string
     */
    public function backLinkFilter($string, $id = null)
    {
        $backLinkRegexp = '/{back_link(?:\|([^}]+))?}/';
        preg_match($backLinkRegexp, $string, $matches);
        [$placeholder, $linkText] = array_pad($matches, 2, '');
        if (!$linkText) {
            $linkText = 'oro.embeddedform.back_link_default_text';
        }

        $link = $this->getLink($id, $this->getTranslator()->trans($linkText));

        return str_replace($placeholder, $link, $string);
    }

    /**
     * @param string $id
     * @param string $linkText
     * @return string
     */
    private function getLink($id, $linkText)
    {
        if (empty($id)) {
            return sprintf(
                '<a href="#" onclick="window.location.reload(true); return false;">%s</a>',
                $linkText
            );
        }

        $url = $this->getRouter()->generate('oro_embedded_form_submit', ['id' => $id]);

        return sprintf('<a href="%s">%s</a>', $url, $linkText);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            RouterInterface::class,
            TranslatorInterface::class,
        ];
    }
}
