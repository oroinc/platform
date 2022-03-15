<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\TagBundle\Provider\SearchProvider;
use Oro\Bundle\TagBundle\Security\SecurityProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ID = 1;
    private const TEST_ENTITY_NAME = 'name';

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ObjectMapper|\PHPUnit\Framework\MockObject\MockObject */
    private $mapper;

    /** @var SecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $securityProvider;

    /** @var SearchProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->mapper = $this->createMock(ObjectMapper::class);
        $this->securityProvider = $this->createMock(SecurityProvider::class);
        $indexer = $this->createMock(Indexer::class);
        $configManager = $this->createMock(ConfigManager::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new SearchProvider(
            $this->entityManager,
            $this->mapper,
            $this->securityProvider,
            $indexer,
            $configManager,
            $translator
        );
    }

    public function testGetResults()
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())->method('getResult')
            ->willReturn(
                [
                    [
                        'entityName' => self::TEST_ENTITY_NAME,
                        'recordId'   => self::TEST_ID,
                    ]
                ]
            );

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('from')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('addGroupBy')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->securityProvider->expects($this->once())
            ->method('applyAcl')
            ->with($qb, 't');

        $this->mapper->expects($this->once())
            ->method('getEntityConfig')
            ->with(self::TEST_ENTITY_NAME)
            ->willReturn([]);

        $this->assertInstanceOf(Result::class, $this->provider->getResults(self::TEST_ID));
    }
}
