<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Behat\Context;

use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class AuditContext extends OroFeatureContext
{
    /**
     * Given should be 3 audit records for "oro_logger.email_notification_recipients" configuration option
     *
     * @Given /^should be (?P<count>\d+) audit records? for "(?P<option>[^"]+)" configuration option$/
     */
    public function shouldBeAuditRecordsForConfigurationOption(int $count, string $option): void
    {
        $repository = $this->getAppContainer()
            ->get('doctrine')
            ->getRepository(AuditField::class);

        $result = $this->spin(
            static fn (): bool => $count === $repository->count(['field' => $option]),
            10
        );

        self::assertTrue(
            $result,
            sprintf(
                'The count of audit records for the "%s" configuration option is not equal to the expected %s',
                $option,
                $count
            )
        );
    }
}
