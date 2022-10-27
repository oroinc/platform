<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a text representation of Mailbox entity.
 */
class MailboxEntityNameProvider implements EntityNameProviderInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Mailbox) {
            return false;
        }

        if (self::SHORT === $format) {
            return $entity->getLabel();
        }

        return
            $entity->getLabel()
            . ' '
            . $this->translator->trans('oro.email.mailbox.entity_label', [], null, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (Mailbox::class !== $className) {
            return false;
        }

        if (self::SHORT === $format) {
            return $alias . '.label';
        }

        return sprintf(
            'CONCAT(%s.label, \' \', %s)',
            $alias,
            (string)(new Expr())->literal(
                $this->translator->trans('oro.email.mailbox.entity_label', [], null, $locale)
            )
        );
    }
}
