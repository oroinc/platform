<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Model\Factory;

use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;

/**
 * Creates an email template from raw data.
 */
interface EmailTemplateFromRawDataFactoryInterface
{
    public function createFromRawData(string $rawData): EmailTemplateModel;
}
