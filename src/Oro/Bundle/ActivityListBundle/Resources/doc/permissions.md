# Permissions in ActivityList Bundle


Each activity entity must contain a provider (for example, EmailActivityListProvider) with the implemented ActivityListProviderInterface interface. The ActivityList::getActivityOwners method returns one or many ActivityOwner entities, which are connected to their activity list entity.
