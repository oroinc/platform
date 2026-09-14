<?php

declare(strict_types=1);

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Controller;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class SegmentControllerAclTest extends WebTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadSegmentData::class]);
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
            Segment::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );

        $this->client->request(Request::METHOD_GET, $this->getCloneUrl());

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    private function getCloneUrl(): string
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);

        return $this->getUrl('oro_segment_clone', ['id' => $segment->getId()]);
    }
}
