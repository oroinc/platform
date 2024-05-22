<?php

namespace Oro\Bundle\ApiBundle\Tests\Behat\Context;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class ApiContext extends OroFeatureContext
{
    /**
     * @Given /^(?:|I )enable API$/
     */
    public function setConfigurationProperty(): void
    {
        $configManager = $this->getAppContainer()->get('oro_config.global');
        $configManager->set('oro_api.web_api', true);
        $configManager->flush();
    }

    /**
     * @Then /^(?:|I )wait for "(?P<name>([\w\s-]+))" OpenAPI specification status changed to "(?P<status>(\w+))"$/
     */
    public function waitForOpenApiSpecificationStatusChanged(string $name, string $status): void
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->getAppContainer()->get('doctrine');
        /** @var Connection $connection */
        $connection = $doctrine->getManagerForClass(OpenApiSpecification::class)->getConnection();

        $endTime = new \DateTime('+30 seconds');
        while (true) {
            $row = $connection->fetchAssociative(
                'SELECT e.status, e.published FROM oro_api_openapi_specification e WHERE e.name = ? LIMIT 1',
                [$name],
                [Types::STRING]
            );
            if ($row && $row['status'] === $status) {
                return;
            }

            usleep(100000);
            if (new \DateTime() >= $endTime) {
                break;
            }
        }
    }
}
