UPGRADE FROM 1.9 to 1.10
========================

####EntityConfigBundle:
- Entity config class metadata now allows any `route*` options, that can be used for CRUD routes configuration - as well as already existing `routeName`, `routeView` and `routeCreate` options.

####SecurityBundle
- `Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder` - removed all constants for masks and their groups.
- `Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder` - now allow custom Permissions (see [permissions.md](./src/Oro/Bundle/SecurityBundle/Resources/doc/permissions.md)
- `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension` - now allow custom Permissions (see [permissions.md](./src/Oro/Bundle/SecurityBundle/Resources/doc/permissions.md)
- `Oro\Bundle\SecurityBundle\Acl\Extension\MaskBuilder` - added new public methods: `hasMask(string $name)`, `getMask(string $name)`.
- `Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter` - added new public method - `setPermissionManager(PermissionManager $permissionManager)`.
- Constructor for `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension` changed. New arguments: `PermissionManager $permissionManager, AclGroupProviderInterface $groupProvider`
- Constructor for `Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder` changed. New arguments: `int $identity, array $permissions`
- Added command for loading permissions configuration `Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand` (`security:permission:configuration:load`) - this command added to install and update platform scripts.
- Added migration `Oro\Bundle\SecurityBundle\Migrations\Schema\LoadBasePermissionsQuery` for loading to DB base permissions ('VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'SHARE').
- Added migration `Oro\Bundle\SecurityBundle\Migrations\Schema\v1_1\UpdateAclEntriesMigrationQuery` for updating ACL Entries to use custom Permissions.
- Added `acl_permission` twig extension - allows get `Permission` by `AclPermission`.
- Added third parameter `$byCurrentGroup` to `Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface::getPermissions` for getting permissions only for current application group name. Updated same method in `Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension` and `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension`.
