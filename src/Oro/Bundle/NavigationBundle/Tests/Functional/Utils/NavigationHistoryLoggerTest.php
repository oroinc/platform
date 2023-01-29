<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Utils;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Utils\NavigationHistoryLogger;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class NavigationHistoryLoggerTest extends WebTestCase
{
    /** @var NavigationHistoryLogger */
    private $navigationHistoryLogger;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->client->disableReboot();

        $this->navigationHistoryLogger = $this->getContainer()->get('oro_navigation.tests.navigation_history_logger');
        $this->tokenAccessor = $this->getContainer()->get('oro_security.token_accessor');

        // send some web request to initialize the security context
        $this->getClientInstance()->request('GET', $this->getUrl('oro_navigation_user_menu_index'));
        // check that the security context is initialized
        $this->assertNotNull($this->tokenAccessor->getUser(), 'No logged in user');
    }

    private function findNavigationHistoryItem(int $id): ?NavigationHistoryItem
    {
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repo */
        $repo = $doctrine->getRepository(NavigationHistoryItem::class);

        return $repo->find($id);
    }

    private function findLastNavigationHistoryItem(): ?NavigationHistoryItem
    {
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repo */
        $repo = $doctrine->getRepository(NavigationHistoryItem::class);

        return $repo->createQueryBuilder('h')
            ->orderBy('h.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return int
     */
    public function testLogRequestForNewNavigationHistoryItem()
    {
        $request = Request::create('/test/1');
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_route_params', ['_format' => 'html']);
        $request->query->set('id', 123);

        $visitedAt = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();
        $this->navigationHistoryLogger->logRequest($request);
        $visitedAtDelta = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp() - $visitedAt;

        $item = $this->findLastNavigationHistoryItem();
        $this->assertNotNull($item);
        $this->assertSame('/test/1', $item->getUrl());
        $this->assertSame('test_route', $item->getRoute());
        $this->assertSame(['_format' => 'html'], $item->getRouteParameters());
        $this->assertSame(1, $item->getVisitCount());
        $this->assertEqualsWithDelta($visitedAt, $item->getVisitedAt()->getTimestamp(), $visitedAtDelta);
        $this->assertSame($this->tokenAccessor->getUserId(), $item->getUser()->getId());
        $this->assertSame($this->tokenAccessor->getOrganizationId(), $item->getOrganization()->getId());
        $this->assertSame(123, $item->getEntityId());
        $this->assertStringStartsWith('{"template":', $item->getTitle());

        return $item->getId();
    }

    /**
     * @depends testLogRequestForNewNavigationHistoryItem
     */
    public function testLogRequestForExistingNavigationHistoryItem(int $itemId)
    {
        $request = Request::create('/test/1');
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_route_params', ['_format' => 'html']);
        $request->query->set('id', 123);

        $visitedAt = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();
        $this->navigationHistoryLogger->logRequest($request);
        $visitedAtDelta = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp() - $visitedAt;

        $item = $this->findLastNavigationHistoryItem();
        $this->assertNotNull($item);
        $this->assertSame($itemId, $item->getId());
        $this->assertSame('/test/1', $item->getUrl());
        $this->assertSame('test_route', $item->getRoute());
        $this->assertSame(['_format' => 'html'], $item->getRouteParameters());
        $this->assertSame(2, $item->getVisitCount());
        $this->assertEqualsWithDelta($visitedAt, $item->getVisitedAt()->getTimestamp(), $visitedAtDelta);
        $this->assertSame($this->tokenAccessor->getUserId(), $item->getUser()->getId());
        $this->assertSame($this->tokenAccessor->getOrganizationId(), $item->getOrganization()->getId());
        $this->assertSame(123, $item->getEntityId());
        $this->assertStringStartsWith('{"template":', $item->getTitle());

        return $item->getId();
    }
}
