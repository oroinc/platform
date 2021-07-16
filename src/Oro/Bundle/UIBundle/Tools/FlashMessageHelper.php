<?php

namespace Oro\Bundle\UIBundle\Tools;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function __construct(Session $session, TranslatorInterface $translator, HtmlTagHelper $htmlTagHelper)
    {
        $this->session = $session;
        $this->translator = $translator;
        $this->htmlTagHelper = $htmlTagHelper;
    }

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
