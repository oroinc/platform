<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

class MailboxEntityNameProvider implements EntityNameProviderInterface
{
    const CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\Mailbox';

    /** @var Translator */
    private $translator;

    /**
     * @param Translator $translator
     */
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
                return sprintf(
                    'CONCAT(%s.label, \' %s\')',
                    $alias,
                    $this->translator->trans('oro.email.mailbox.entity_label', [], null, $locale)
                );
            }
        }

        return false;
    }
}
