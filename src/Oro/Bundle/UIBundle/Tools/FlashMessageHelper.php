<?php

namespace Oro\Bundle\UIBundle\Tools;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class that helps to add the flash message, sanitizing it in advance
 */
class FlashMessageHelper
{
    /** @var Session */
    protected $session;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /**
     * @param Session $session
     * @param TranslatorInterface $translator
     * @param HtmlTagHelper $htmlTagHelper
     */
    public function __construct(Session $session, TranslatorInterface $translator, HtmlTagHelper $htmlTagHelper)
    {
        $this->session = $session;
        $this->translator = $translator;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * @param string $type
     * @param string $message
     * @param array $params
     * @param string|null $domain
     */
    public function addFlashMessage(string $type, string $message, array $params, string $domain = null)
    {
        $message = $this->translator->trans($message, $params, $domain);

        $this->session->getFlashBag()
            ->add(
                $type,
                $this->htmlTagHelper->sanitize($message)
            );
    }
}
