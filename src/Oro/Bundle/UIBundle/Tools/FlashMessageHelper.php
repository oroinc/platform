<?php

namespace Oro\Bundle\UIBundle\Tools;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class that helps to add the flash message, sanitizing it in advance
 */
class FlashMessageHelper
{
    public function __construct(
        protected RequestStack $requestStack,
        protected TranslatorInterface $translator,
        protected HtmlTagHelper $htmlTagHelper
    ) {
    }

    public function addFlashMessage(string $type, string $message, array $params, string $domain = null)
    {
        $message = $this->translator->trans($message, $params, $domain);

        $this->requestStack->getSession()->getFlashBag()
            ->add(
                $type,
                $this->htmlTagHelper->sanitize($message)
            );
    }
}
