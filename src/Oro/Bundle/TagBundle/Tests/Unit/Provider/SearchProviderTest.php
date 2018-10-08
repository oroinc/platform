<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Provider;

use Oro\Bundle\TagBundle\Provider\SearchProvider;

class SearchProviderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ID = 1;
    const TEST_ENTITY_NAME = 'name';

    /** @var SearchProvider */
    protected $provider;

    /** @var  \PHPUnit\Framework\MockObject\MockObject */
    protected $mapper;

    /** @var  \PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var  \PHPUnit\Framework\MockObject\MockObject */
    protected $securityProvider;

    protected function setUp()
    {
        $this->entityManager    = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->mapper           = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\ObjectMapper')
            ->disableOriginalConstructor()->getMock();
        $this->securityProvider = $this->getMockBuilder('Oro\Bundle\TagBundle\Security\SecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $indexer                = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager          = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $translator             = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider         = new SearchProvider(
            $this->entityManager,
            $this->mapper,
            $this->securityProvider,
            $indexer,
            $configManager,
            $translator
        );
    }

    protected function tearDown()
    {
        unset($this->entityManager);
        unset($this->mapper);
        unset($this->provider);
    }

    public function testGetResults()
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())->method('getResult')
            ->will(
                $this->returnValue(
                    [
                        [
                            'entityName' => self::TEST_ENTITY_NAME,
                            'recordId'   => self::TEST_ID,
                        ]
                    ]
                )
            );

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method('select')
            ->will($this->returnSelf());
        $qb->expects($this->once())->method('from')
            ->will($this->returnSelf());
        $qb->expects($this->once())->method('where')
            ->will($this->returnSelf());
        $qb->expects($this->exactly(2))->method('addGroupBy')
            ->will($this->returnSelf());
        $qb->expects($this->once())->method('setParameter')
            ->will($this->returnSelf());
        $qb->expects($this->once())->method('getQuery')
            ->will($this->returnValue($query));

        $this->entityManager->expects($this->once())->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->securityProvider->expects($this->once())
            ->method('applyAcl')
            ->with($qb, 't');

        $this->mapper->expects($this->once())->method('getEntityConfig')->with(self::TEST_ENTITY_NAME)->willReturn([]);

        $this->assertInstanceOf('Oro\Bundle\SearchBundle\Query\Result', $this->provider->getResults(self::TEST_ID));
    }
}
