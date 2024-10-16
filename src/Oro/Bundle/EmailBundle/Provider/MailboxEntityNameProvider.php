<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a text representation of Mailbox entity.
 */
class MailboxEntityNameProvider implements EntityNameProviderInterface
{
    private const TRANSLATION_KEY = 'oro.email.mailbox.entity_label';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Mailbox) {
            return false;
        }

        if (self::SHORT === $format) {
            return $entity->getLabel();
        }

        return $entity->getLabel() . ' ' . $this->trans(self::TRANSLATION_KEY, $locale);
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, Mailbox::class, true)) {
            return false;
        }

        if (self::SHORT === $format) {
            return $alias . '.label';
        }

        return sprintf(
            'CONCAT(%s.label, \' \', %s)',
            $alias,
            (string)(new Expr())->literal($this->trans(self::TRANSLATION_KEY, $locale))
        );
    }

    private function trans(string $key, string|Localization|null $locale): string
    {
        if ($locale instanceof Localization) {
            $locale = $locale->getLanguageCode();
        }

        return $this->translator->trans($key, [], null, $locale);
    }
}
