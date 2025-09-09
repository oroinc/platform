<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Model\Factory;

use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateFromArrayHydrator;
use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateRawDataParser;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;

/**
 * Creates an email template from raw data.
 */
class EmailTemplateFromRawDataFactory implements EmailTemplateFromRawDataFactoryInterface
{
    public function __construct(
        private readonly EmailTemplateRawDataParser $emailTemplateRawDataParser,
        private readonly EmailTemplateFromArrayHydrator $emailTemplateFromArrayHydrator,
        private readonly string $emailTemplateClass
    ) {
    }

    #[\Override]
    public function createFromRawData(string $rawData): EmailTemplateModel
    {
        $emailTemplate = new $this->emailTemplateClass();
        $arrayData = $this->emailTemplateRawDataParser->parseRawData($rawData);

        $this->emailTemplateFromArrayHydrator->hydrateFromArray($emailTemplate, $arrayData);

        return $emailTemplate;
    }
}
