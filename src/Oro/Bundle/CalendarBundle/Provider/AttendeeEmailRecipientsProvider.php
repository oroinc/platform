<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\EmailBundle\Model\CategorizedRecipient;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;

class AttendeeEmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /**
     * @param ManagerRegistry $registry
     * @param EmailRecipientsHelper $emailRecipientsHelper
     */
    public function __construct(ManagerRegistry $registry, EmailRecipientsHelper $emailRecipientsHelper)
    {
        $this->registry = $registry;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        return $this->emailRecipientsHelper->plainRecipientsFromResult(
            $this->getAttendeeRepository()->getEmailRecipients(
                $args->getOrganization(),
                $args->getQuery(),
                $args->getLimit()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSection()
    {
        return 'oro.calendar.autocomplete.attendees';
    }

    /**
     * @return AttendeeRepository
     */
    protected function getAttendeeRepository()
    {
        return $this->registry->getRepository('Oro\Bundle\CalendarBundle\Entity\Attendee');
    }
}
