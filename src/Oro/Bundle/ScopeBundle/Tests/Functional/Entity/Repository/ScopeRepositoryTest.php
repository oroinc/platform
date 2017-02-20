<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\DataFixtures\LoadScopeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ScopeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadScopeData::class
        ]);
    }

    public function testFindByCriteria()
    {
        $criteria = new ScopeCriteria([], []);
        $scopes = $this->getRepository()->findByCriteria($criteria);
        $this->assertCount(1, $scopes);
    }

    public function testFindOneByCriteria()
    {
        $criteria = new ScopeCriteria([], []);
        $scope = $this->getRepository()->findOneByCriteria($criteria);
        $this->assertNotNull($scope);
    }

    public function testFindScalarByCriteria()
    {
        $criteria = new ScopeCriteria([], []);
        $ids = $this->getRepository()->findIdentifiersByCriteria($criteria);

        /** @var Scope $scope */
        $scope = $this->getReference(LoadScopeData::DEFAULT_SCOPE);
        $this->assertSame([$scope->getId()], $ids);
    }

    public function testFindIdentifiersByCriteriaWithPriority()
    {
        $criteria = new ScopeCriteria([], []);
        $ids = $this->getRepository()->findIdentifiersByCriteriaWithPriority($criteria);

        /** @var Scope $scope */
        $scope = $this->getReference(LoadScopeData::DEFAULT_SCOPE);
        $this->assertSame([$scope->getId()], $ids);
    }

    public function testFindMostSuitable()
    {
        $criteria = new ScopeCriteria([], []);
        $actualScope = $this->getRepository()->findMostSuitable($criteria);

        /** @var Scope $expectedScope */
        $expectedScope = $this->getReference(LoadScopeData::DEFAULT_SCOPE);
        $this->assertSame($expectedScope->getId(), $actualScope->getId());
    }

    /**
     * @return ScopeRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroScopeBundle:Scope');
    }
}
