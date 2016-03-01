<?php
namespace Oro\Bundle\UserBundle\Datagrid;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Used in grid of roles to provide permissions for actions on level of each role in the grid.
 */
class RoleGridHelper
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Returns callback for configuration of grid/actions visibility per row
     *
     * @return callable
     */
    public function getActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            $role = $record->getRootEntity();
            return [
                'update' => $this->authorizationChecker->isGranted('EDIT', $role),
                'delete' => $this->authorizationChecker->isGranted('DELETE', $role),
            ];
        };
    }
}
