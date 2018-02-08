# Additional Doctrine events

There are some additional doctrine events is triggered during execution of \Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.

## \Oro\Component\Testing\Doctrine\Events::ON_AFTER_TEST_TRANSACTION_ROLLBACK (onAfterTestTransactionRollback)

This event is triggered, when the transaction, which provides functional test isolation, is rolled back.
This event can be useful in case, when you need to rollback some changes, made by test fixture, in case this changes affects not only the database (e.g. cache)

Use following code to subscribe on this event:
```php
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Oro\Component\Testing\Doctrine\Events;

class LoadSomeData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        // load data....
        
        $manager->getConnection()
            ->getEventManager()
            ->addEventListener(Events::ON_AFTER_TEST_TRANSACTION_ROLLBACK, $this);    
    }
    
    /**
     * Will be executed when (if) this fixture will be rolled back
     */
    public function onAfterTestTransactionRollback(ConnectionEventArgs $args)
    {
        // do something (e.g. clear some caches)....
    }    
}
``` 
