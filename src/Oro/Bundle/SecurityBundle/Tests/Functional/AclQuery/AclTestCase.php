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
    protected function getEntityRepository($entityClass)
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
        $queryBuilder,
        $expectedResult,
        $options = []
    ) {
        $aclHelper = $this->getContainer()->get('oro_security.acl_helper');

        $checkRelations = true;
        if (array_key_exists('checkRelations', $options)) {
            $checkRelations = $options['checkRelations'];
            unset($options['checkRelations']);
        }
        $checkRootEntity = true;
        if (array_key_exists('checkRootEntity', $options)) {
            $checkRootEntity = $options['checkRootEntity'];
            unset($options['checkRootEntity']);
        }

        if ($options) {
            throw new \InvalidArgumentException('Unsupported options specified.');
        }

        $aclHelper->setCheckRootEntity($checkRootEntity);
        $query = $aclHelper->apply($queryBuilder, 'VIEW', $checkRelations);

        $result = $query->getResult();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Authenticates user.
     *
     * @param string $user
     * @param string $organization
     */
    protected function authenticateUser($user, $organization = 'organization')
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
