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
     * @param EmailFolder $folder
     * @param Email $email
     * @param $flags
     *
     * @return void
     */
    public function setFlags(EmailFolder $folder, Email $email, $flags);

    /**
     * @param EmailFolder $folder
     * @param Email $email
     *
     * @return void
     */
    public function setFlagUnseen(EmailFolder $folder, Email $email);

    /**
     * @param EmailFolder $folder
     * @param Email $email
     * @return void
     */
    public function setFlagSeen(EmailFolder $folder, Email $email);
}
