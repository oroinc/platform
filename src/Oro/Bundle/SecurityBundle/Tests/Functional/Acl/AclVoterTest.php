<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Acl;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class AclVoterTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testNotThrowDomainObject()
    {
        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $alwaysMatcher */
        $alwaysMatcher = $this->createMock(RequestMatcherInterface::class);
        $alwaysMatcher->expects($this->any())
            ->method('matches')
            ->willReturn(true);
        $accessMap = $this->getContainer()->get('security.access_map');
        $accessMap->add($alwaysMatcher, ['ROLE_ADMINISTRATOR']);

        $this->client->request('GET', $this->getUrl('oro_test_item_index'));

        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
    }
}
