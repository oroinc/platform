<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Contains methods to get the text representation of a mailbox.
 */
class MailboxNameHelper
{
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Gets the name of a mailbox.
     *
     * @param string      $originClass        The class name of the email origin.
     * @param string      $originMailboxName  The mailbox name from EmailOrigin entity
     * @param string|null $relatedMailboxName The name of a mailbox associated with EmailOrigin entity
     *
     * @return string
     */
    public function getMailboxName($originClass, $originMailboxName, $relatedMailboxName)
    {
        $result = $relatedMailboxName;
        if (!$result) {
            if (is_a($originClass, InternalEmailOrigin::class, true)) {
                $result = $originMailboxName;
            } else {
                $result = $this->translator->trans('oro.email.mailbox_name.personal');
            }
        }

        return $result;
    }

    /**
     * Extracts the name of a mailbox from the given email origin.
     *
     * @param EmailOrigin $origin
     *
     * @return string
     */
    public function getOriginMailboxName(EmailOrigin $origin)
    {
        $mailboxName = null;
        $mailbox = $origin->getMailbox();
        if (null !== $mailbox) {
            $mailboxName = $mailbox->getLabel();
        }

        return $this->getMailboxName(get_class($origin), $origin->getMailboxName(), $mailboxName);
    }
}
