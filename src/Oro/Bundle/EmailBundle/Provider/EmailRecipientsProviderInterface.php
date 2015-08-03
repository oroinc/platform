<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Model\Email;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;

interface EmailRecipientsProviderInterface
{
    /**
     * @return Email[]
     */
    public function getRecipients(EmailRecipientsProviderArgs $args);

    /**
     * @return string
     */
    public function getSection();
}
