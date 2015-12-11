UPGRADE FROM 1.8 to 1.9
=======================

####ActivityListBundle
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setUpdatedBy` instead. 
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getUpdatedBy` instead.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getDate` removed. Use `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getCreatedAt` and `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getUpdatedAt` instead
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::isDateUpdatable` removed. It is not needed.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface::getOwner` added.
####EntityConfigBundle
- Removed `optionSet` field type deprecated since v1.4. Existing options sets are converted to `Select` or `Multi-Select` automatically during the Platform update.

####NoteBundle
 - Added parameter `DoctrineHelper $doctrineHelper` to constructor of `\Oro\Bundle\NoteBundle\Placeholder\PlaceholderFilter`

####SecurityBundle
- `Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface` and its implementation `Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactory` were introduced to encapsulate creation of `UsernamePasswordOrganizationToken` in `Oro\Bundle\SecurityBundle\Authentication\Provider\UsernamePasswordOrganizationAuthenticationProvider` and `Oro\Bundle\SecurityBundle\Http\Firewall\OrganizationBasicAuthenticationListener`
- `Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactoryInterface` and its implementation `Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactory` were introduced to encapsulate creation of `OrganizationRememberMeToken` in `Oro\Bundle\SecurityBundle\Authentication\Provider\UsernamePasswordOrganizationAuthenticationProvider`

####SSOBundle
- `Oro\Bundle\SSOBundle\Security\OAuthTokenFactoryInterface` and its implementation `Oro\Bundle\SSOBundle\Security\OAuthTokenFactory` were introduced to encapsulate creation of `OAuthToken` in `Oro\Bundle\SSOBundle\Security\OAuthProvider`

####UserBundle
- `Oro\Bundle\UserBundle\Security\WsseTokenFactoryInterface` and its implementation `Oro\Bundle\UserBundle\Security\WsseTokenFactory` were introduced to encapsulate creation of `WsseToken` in `Oro\Bundle\UserBundle\Security\WsseAuthProvider`
