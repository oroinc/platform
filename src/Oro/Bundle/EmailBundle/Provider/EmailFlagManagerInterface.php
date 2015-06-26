<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * Interface EmailFlagManagerInterface
 * @package Oro\Bundle\EmailBundle\Provider
 */
interface EmailFlagManagerInterface
{
    /**
     * Set flags for message by EmailFolder and  Email
     *
     * @param EmailFolder $folder
     * @param Email $email
     * @param $flags
     *
     * @return void
     */
    public function setFlags(EmailFolder $folder, Email $email, $flags);

    /**
     * Set flag UNSEEN for message by EmailFolder and Email
     *
     * @param EmailFolder $folder
     * @param Email $email
     *
     * @return void
     */
    public function setUnseen(EmailFolder $folder, Email $email);

    /**
     * Set flag SEEN for message by EmailFolder and Email
     *
     * @param EmailFolder $folder
     * @param Email $email
     *
     * @return void
     */
    public function setSeen(EmailFolder $folder, Email $email);
}
