Additional Doctrine Events
==========================

There are some additional doctrine events is triggered during execution of \Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.

**\Oro\Component\Testing\Doctrine\Events::ON_AFTER_TEST_TRANSACTION_ROLLBACK (onAfterTestTransactionRollback)**

This event is triggered when the transaction, which provides functional test isolation, is rolled back. This event can be useful  when you need to rollback changes made by test fixture if this changes affects not only the database (e.g., cache)

Use following code to subscribe to this event:

.. code-block:: php
   :linenos:

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

