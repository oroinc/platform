<?php
namespace Oro\Bundle\EmbeddedFormBundle\Twig;

use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class BackLinkExtension extends \Twig_Extension
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Router $router
     * @param TranslatorInterface $translator
     */
    public function __construct(Router $router, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->translator = $translator;
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
    public function backLinkFilter($string, $id)
    {
        $url = $this->router->generate('oro_embedded_form_submit', ['id' => $id]);
        $linkText = $this->translator->trans('oro.embedded_form.back_link_text');
        $link = sprintf('<a href="%s">%s</a>', $url, $linkText);

        return str_replace('{back_link}', $link, $string);
    }
}
