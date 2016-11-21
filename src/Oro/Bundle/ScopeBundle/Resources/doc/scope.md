Scopes
------

Scope in Oro applications arms you with additional abstraction layer and helps you get the missing information about the execution context in a standard and controllable way. With a scope approach your bundle can launch an alternative behavior and modify displayed data based on the information from the scope that indirectly matches current execution context.

For working example of using scopes in Oro application, please check out the *VisibilityBundle* and *AccountBundle* code.

* [How Scopes work](#how-scopes-work)
    * [Scope Manager](#scope-manager)
    * [Scope Criteria Providers](#scope-criteria-providers)
    * [Scope Type](#scope-type)
    * [Scope Model](#scope-model)
* [Configuring Scope Criteria Providers](#configuring-scope-criteria-providers)
* [Using Context](#using-context)
* [Scope Operations](#scope-operations)
* [Example: Using Related Scopes](#example-using-related-scopes)
* [Example: Using Scope Criteria](#example-using-scope-criteria)

How Scopes work
---------------
Sometimes in a bundle activities, you need to alter behavior or data based on the set of criteria that the bundle is not able to evaluate. Scope Manager gets you the missing details by polling dedicated Scope Criteria Providers. In the scope-consuming bundle, you can request information using one of the [Scope Operations](#scope-operations). As a first parameter, you usually pass the scope type (e.g. web_content in the following examples). Scope type helps Scope Manager find the scope-provider bundles who can deliver the information your bundle is missing. As the second parameter, you usually pass the context - information available to your bundle that is used as a scope filtering criteria. **Note:** Scope Manager evaluates the priority of the Scope Criteria Providers who are registered to deliver information for the requested scope type and scope criterion, and sorts the results based on the criterion priority. 

Scope Manager
-------------
Scope Manager is a service that provides an interface for collecting the scope items in Oro application. It is in charge of the following functions:
* Expose scope-related operations (find, findOrCreate, findDefaultScope, findRelatedScopes) to the scope-aware bundles and deliver requested scope(s) as a result. See [Scope Operations](#scope-operations) for more information.
* Create a collected scope in response to the findOrCreate operation (if the scope is not found).
* Call Scope Criteria Provider's getCriteriaForCurrentScope() method to get a portion of the scope information.

Scope Criteria Providers
------------------------
Scope Criteria Provider is a service that calculates the value for the scope criterion based on the provided context. Scope criteria help model a relationship between the scope and the scope-consuming context. In any bundle, you can create a [Scope Criteria Provider](#configuring-scope-criteria-providers) service and register it as scope provider for the specific scope type. This service shall deliver the scope criterion value to the Scope Manager, who, in turn, use the scope criteria to filter the scope instances or find the one matching to the provided context.

Scope Type
----------
Scope Type is a tag that groups scope providers that are used by particular scope consumers. One scope provider may be reused in several scope types. It may happen, that a particular scope criteria provider, like the one for Account Group, is not involved in the scope construction because it serves the scope-consumers with the different scope type (e.g. web_content). In this case, Scope Manager looks for the scope(s) that do(es) not prompt to evaluate this criterion. 

Scope Model
-----------
Scope model is a data structure for storing scope items. Every scope item has fields for every scope criterion registered by the scope criteria provider services. When the scope criterion is not involved in the scope (based on the scope type), the value of the field is NULL.

Add Scope Criterion
-------------------
To add criterion to the scope, extend Scope entity using migration, as shown int the following example:
```
class OroAccountBundleScopeRelations implements Migration, ExtendExtensionAwareInterface
{
    ...
    
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            OroScopeBundleInstaller::ORO_SCOPE,
            'account',
            OroAccountBundleInstaller::ORO_ACCOUNT_TABLE_NAME,
            'id',
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ],
            RelationType::MANY_TO_ONE
        );
    }
}

```

Configuring Scope Criteria Providers
------------------------------------
To extend a scope with a criterion that may be provided by your bundle:

1. Create a **Scope<your criterion name>CriteriaProvider** class and implement getCriteriaForCurrentScope() and getCriteriaField() methods, as shown in the following examples. 

```
class ScopeAccountCriteriaProvider extends AbstractScopeCriteriaProvider
{
    ...
    /**
     * @return array
     */
    public function getCriteriaForCurrentScope()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return [];
        }
        $loggedUser = $token->getUser();
        if (null !== $loggedUser && $loggedUser instanceof AccountUser) {
            return [self::ACCOUNT => $loggedUser->getAccount()];
        }

        return [];
    } 
      
    /**
     * @return string
     */
    public function getCriteriaField()
    {
        return static::ACCOUNT;
    }
}
```
2. In <your bundle>/Resources/config/service.yml, registered the newly created *<bundle>_scope_criteria_provider* with *oro_scope.provider* tag, like in the following example:

```
oro_customer.account_scope_criteria_provider:
    class: 'Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider'
    tags:
        - { name: oro_scope.provider, scopeType: web_content, priority: 30 }
```
**Note:** One CriteriaScopeProvider can be used in many scope types.

Using Context
-------------
When you need to find a scope based on the information that differs from the current context, you can pass the custom context (array or object) as a second parameter of *find* and *findOrCreate* method.

Scope Operations
----------------
Scope Manager exposes the following operations for the scope-consuming bundles:

Find scope by context (when the context is provided), or
find Scope by current data (when context is NULL)
```
$scopeManager->find($scopeType, $context = null)         
```

Find scope or create a new one if it is not found
```
$scopeManager->findOrCreate($scopeType, $context = null) 
```

Get the default scope (returns a scope with empty scope criteria)
```
$scopeManager->findDefaultScope() 
```

Get all scopes that match given context. When some scope criteria are not provided in context, the scopes are filtered by the available criteria.
```
$scopeManager->findRelatedScopes($scopeType, $context = null);
```

Example: Using related scopes
-----------------------------
For example, let's create the following scope criteria providers and register them for the *web_content* scope type. 

* ScopeAccountCriteriaProvider (priority:300)

* ScopeWebsiteCriteriaProvider (priority:100)

**Note:** The third ScopeAccountGroupCriteriaProvider is NOT involved in the scope type, so the scope will be filtered to have no AccountGroup criteria defined. 

The scope model has tree fields:
```
class Scope 
{
    protected $account;
    protected $accountGroup;
    protected $website;
    ...
}
```
and the existing scopes in Scope Repository are as follows:

|id|account_id|accountGroup|website_id|
|---|---|---|---|
|1|1||1|
|2|2||1|
|3|1||2|
|4|1|||
|5||1|1|
|6||1||

In order to fetch all scopes that match account with id equal to 1, you can use findRelatedScopes and pass *web_content* and 'account'=>1 in the parameters.
```
$context = ['account' => 1];
$scopeManager->findRelatedScopes('web_content', $context) 
```
We may or may not know what are other scope criteria that are available for this scope type. The Scope Manager fills in the blanks and adds *criterion IS NOT NULL* condition for any scope criterion we do not have in context. For our example, the Scope Manager's query looks like: 
```
WHERE account_id = 1 AND website_id IS NOT NULL AND accountGroup_id IS NULL;
```
where:
* **account_id** - is given in the context parameter,
* **website_id** - is not given, but is required based on the scope type, and
* **accountGroup_id** - should be missing (NULL) in the scope, as it does not participate in the scope type.

The resulting scopes delivered to the scope consumer by Scope Manager are:

|id|account_id|accountGroup|website_id|
|---|---|---|---|
|1|1||1|
|3|1||2|

Example: Using Scope Criteria
-----------------------------

When the slug URLs are linked to the scopes, in a many-to-many way, and we need to find a slug URL related to the scope with the highest priority, fitting best for the current context, this is what happens:

The scope criteria providers are already registered in the *service.yml* file:
```
oro_customer.account_scope_criteria_provider:
    class: 'Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider'
    tags:
        - { name: oro_scope.provider, scopeType: web_content, priority: 300 }
        
oro_customer.account_group_scope_criteria_provider:
    class: 'Oro\Bundle\CustomerBundle\Provider\ScopeAccountGroupCriteriaProvider'
    tags:
        - { name: oro_scope.provider, scopeType: web_content, priority: 200 } 
```

In this code example we build a query and modify it with the ScopeCriteria methods:
```
$qb->select('slug')
    ->from(Slug::class, 'slug')
    ->join('slug.scopes', 'scopes', Join::WITH)
    ->where($qb->expr()->eq('slug.url', ':url'))
    ->setParameter('url', $slugUrl)
    ->setMaxResults(1);

$scopeCriteria = $this->scopeManager->getCriteria('web_content');
$scopeCriteria->applyToJoinWithPriority($qb, 'scopes'); 
```
As you do not pass a context to the Scope Manager in the getCriteria method, the current context is used by default(e.g. a logged on customer is a part of Account with id=1, and this account is a part of AccountGroup with id=1.

The scopes applicable for the current context are:

|id|account_id|accountGroup|
|---|---|---|
|4|1||
|6||1|

Here is the resulting modified query:
```
SELECT slug.*
FROM oro_redirect_slug slug
INNER JOIN oro_slug_scope slug_to_scope ON slug.id = slug_to_scope.slug_id
INNER JOIN oro_scope scope ON scope.id = slug_to_scope.scope_id 
    AND (
        (scope.account_id = 1 OR scope.account_id IS NULL) 
        AND (scope.accountGroup_id = 1 OR scope.accountGroup_id IS NULL) 
        AND (scope.website_id IS NULL)
    )
WHERE slug.url = :url
ORDER BY scope.account_id DESC, scope.accountGroup_id DESC
LIMIT 1;
```
Now, let's add another scope criterion provider in a `WebsiteBundle` for the *web_content* scope type and see how the list of scopes and the modified query change. 

In the bundle's *service.yml* file we add:
```
oro_website.website_scope_criteria_provider:
    class: 'Oro\Bundle\WebsiteBundle\Provider\ScopeCriteriaProvider'
    tags:
        - { name: oro_scope.provider, scopeType: web_content, priority: 100 }
```
In current context, the website id is 1, and the scopes of the web_content type are:

|id|account_id|accountGroup|website_id|
|---|---|---|---|
|1|1||1|
|4|1|||
|5||1|1|
|6||1||

The updated query automatically canges to the following one:
```
SELECT slug.*
FROM oro_redirect_slug slug
INNER JOIN oro_slug_scope slug_to_scope ON slug.id = slug_to_scope.slug_id
INNER JOIN oro_scope scope ON scope.id = slug_to_scope.scope_id 
    AND (
        (scope.account_id = 1 OR scope.account_id IS NULL) 
        AND (scope.accountGroup_id = 1 OR scope.accountGroup_id IS NULL) 
        AND (scope.website_id 1 OR scope.website_id IS NULL)
    )
WHERE slug.url = :url 
ORDER BY scope.account_id DESC, scope.accountGroup_id DESC, scope.website_id DESC
LIMIT 1;'
```
