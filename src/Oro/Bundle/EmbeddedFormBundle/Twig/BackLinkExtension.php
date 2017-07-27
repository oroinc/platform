<?php

namespace Oro\Bundle\EmbeddedFormBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class BackLinkExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return RouterInterface
     */
    protected function getRouter()
    {
        return $this->container->get('router');
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_embedded_form_back_link_extension';
    }

    /**
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('back_link', [$this, 'backLinkFilter']),
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
        list($placeholder, $linkText) = array_pad($matches, 2, '');
        if (!$linkText) {
            $linkText = 'oro.embeddedform.back_link_default_text';
        }

        $link = $this->getLink($id, $this->getTranslator()->trans($linkText));

        return str_replace($placeholder, $link, $string);
    }

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
}
