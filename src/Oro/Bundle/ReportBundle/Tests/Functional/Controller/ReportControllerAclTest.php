<?php

declare(strict_types=1);

namespace Oro\Bundle\ReportBundle\Tests\Functional\Controller;

use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Tests\Functional\DataFixtures\LoadReportsData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ReportControllerAclTest extends WebTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadReportsData::class]);
    }

    public function testCloneWhenViewIsGranted(): void
    {
        $this->client->request(Request::METHOD_GET, $this->getCloneUrl());

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testCloneWhenViewIsDenied(): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            Report::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );

        $this->client->request(Request::METHOD_GET, $this->getCloneUrl());

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    private function getCloneUrl(): string
    {
        $report = self::getContainer()->get('doctrine')
            ->getRepository(Report::class)
            ->findOneBy(['name' => LoadReportsData::REPORTS[0]['name']]);
        self::assertInstanceOf(Report::class, $report);

        return $this->getUrl('oro_report_clone', ['id' => $report->getId()]);
    }
}
