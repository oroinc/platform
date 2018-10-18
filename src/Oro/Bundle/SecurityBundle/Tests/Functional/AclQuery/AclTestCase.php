<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\AclQuery;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * The base class for functional tests that test ACL protected queries.
 */
class AclTestCase extends WebTestCase
{
    use RolePermissionExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        parent::setUp();
    }

    /**
     * @param string $entityClass
     *
     * @return EntityRepository
     */
    protected function getEntityRepository(string $entityClass): EntityRepository
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository($entityClass);
    }

    /**
     * Applies ACL to the given query, execute the query and asserts that the result equals to the expected result.
     *
     * @param QueryBuilder $queryBuilder
     * @param array $expectedResult
     * @param array $options
     */
    protected function assertQuery(
        QueryBuilder $queryBuilder,
        array $expectedResult,
        array $options = []
    ): void {
        $aclHelper = $this->getContainer()->get('oro_security.acl_helper');
        $query = $aclHelper->apply($queryBuilder, 'VIEW', $options);
        $result = $query->getResult();
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Authenticates user.
     *
     * @param string $user
     * @param string $organization
     */
    protected function authenticateUser(string $user, string $organization = 'organization'): void
    {
        $container = $this->getContainer();
        $user = $this->getReference($user);
        $organization = $this->getReference($organization);
        $token = new UsernamePasswordOrganizationToken(
            $user,
            false,
            'main',
            $organization,
            $user->getRoles()
        );
        $container->get('security.token_storage')->setToken($token);
    }
}
