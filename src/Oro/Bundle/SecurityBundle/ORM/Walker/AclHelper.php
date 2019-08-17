<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleExecutor;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This class adds the AccessRuleWalker tree walker with context to query to add ACL restrictions.
 *
 * @see \Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker
 */
class AclHelper
{
    public const CHECK_ROOT_ENTITY = 'checkRootEntity';
    public const CHECK_RELATIONS   = 'checkRelations';

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AccessRuleExecutor */
    protected $accessRuleExecutor;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param AccessRuleExecutor    $accessRuleExecutor
     */
    public function __construct(TokenStorageInterface $tokenStorage, AccessRuleExecutor $accessRuleExecutor)
    {
        $this->tokenStorage = $tokenStorage;
        $this->accessRuleExecutor = $accessRuleExecutor;
    }

    /**
     * Protects a query by ACL.
     *
     * @param Query|QueryBuilder $query      The query to be protected
     * @param string             $permission The permission to apply
     * @param array              $options    Additional options for access rules.
     *                                       The following options are implemented out-of-the-box:
     *                                       * "checkRootEntity" determined whether the root entity should be
     *                                         protected; default value is TRUE
     *                                       * "checkRelations" determined whether entities associated with
     *                                         the root entity should be protected; default value is TRUE
     *                                       To find all possible options see classes that implement
     *                                       Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface
     *
     * @return Query
     */
    public function apply($query, string $permission = 'VIEW', array $options = [])
    {
        $token = $this->tokenStorage->getToken();
        $userId = null;
        $userClass = null;
        $organizationId = null;
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $userId = $user->getId();
                $userClass = ClassUtils::getClass($user);
            }
            if ($token instanceof OrganizationContextTokenInterface) {
                $organizationId = $token->getOrganizationContext()->getId();
            }
        }
        $context = new AccessRuleWalkerContext(
            $this->accessRuleExecutor,
            $permission,
            $userClass,
            $userId,
            $organizationId
        );
        foreach ($options as $optionName => $value) {
            $context->setOption($optionName, $value);
        }

        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        QueryUtil::addTreeWalker($query, AccessRuleWalker::class);
        $query->setHint(AccessRuleWalker::CONTEXT, $context);

        return $query;
    }
}
