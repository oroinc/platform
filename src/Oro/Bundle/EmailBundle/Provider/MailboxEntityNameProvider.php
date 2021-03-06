<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

class MailboxEntityNameProvider implements EntityNameProviderInterface
{
    const CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\Mailbox';

    /** @var Translator */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($entity instanceof Mailbox) {
            if ($format === self::SHORT) {
                return $entity->getLabel();
            } else {
                return $entity->getLabel() . ' ' . $this->translator->trans(
                    'oro.email.mailbox.entity_label',
                    [],
                    null,
                    $locale
                );
            }
        }

        return false;
    }

    /**
     * Returns a DQL expression that can be used to get a text representation of the given type of entities.
     *
     * @param string      $format    The representation format, for example full, short, etc.
     * @param string|null $locale    The representation locale.
     * @param string      $className The FQCN of the entity
     * @param string      $alias     The alias in SELECT or JOIN statement
     *
     * @return string A DQL expression or FALSE if this provider cannot return reliable result
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($className === self::CLASS_NAME) {
            if ($format === self::SHORT) {
                return $alias . 'label';
            } else {
                $expr = new Expr();

                return sprintf(
                    'CONCAT(%s.label, \' \', %s)',
                    $alias,
                    (string)$expr->literal(
                        $this->translator->trans('oro.email.mailbox.entity_label', [], null, $locale)
                    )
                );
            }
        }

        return false;
    }
}
