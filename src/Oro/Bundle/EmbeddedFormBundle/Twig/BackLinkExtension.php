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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            RouterInterface::class,
            TranslatorInterface::class
        ];
    }

    private function getRouter(): RouterInterface
    {
        return $this->container->get(RouterInterface::class);
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }
}
