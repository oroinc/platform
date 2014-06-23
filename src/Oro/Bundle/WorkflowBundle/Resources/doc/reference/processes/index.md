Processes Documentation
==============================

Table of Contents
-----------------
 - [What is Processes?](#what-is-workflow)
 - [Main Entities](#main-entities)
 - [How it works?](#how-it-works)
 - [REST API](#rest-api)
 - [Configuration](#configuration)

What is Processes?
------------------

Processes provide possibility for automate tasks related to entity management. They are used main doctrine events
to perform described tasks at the right time. Each process can be performed immediately or after some timeout.
Processes used JMS Job bundle to provide possibility of delayed startup tasks.

Main Entities
-------------

Processes consist three entities.

* **Definition** - main entity that contain information about specific process. The most important information
is the essence of what is entity related to the process and what the actions must be performed with this entity in the
scope of process. Also important option is value of execution order, that affects in what order jobs will be performed,
when several process are subscribed on the same event of the same entity. Process can be enabled or disabled.
Other fields of the process definition contain information about name process, when this process has been created and
when it has been updated last time.

* **Trigger** - entity provides information about the conditions under which will be run the related process and when
this process will be performed. More details about these conditions: first and main condition stored in the event field
and can takes values like ``create``, ``update`` or ``delete`` for each event that can be happen with entity, respectively;
second condition refines entity field name and only after value of this field has been changed,
process will be performed (used for ``update`` event only). Also trigger contains information about when process
should be performed - immediately or after some delay (and size of this delay in the seconds).

* **Job** - entity that contain information special to perform process when it should be started after some delay
(in that case will be created job in JMS). According to event will be saved different data:
    - ``create`` event - will be saved identity of the specific entity;
    - ``update`` event - will be saved identity of the specific entity and old and new states of the updated field;
    - ``delete`` event - will be saved all entity data (scalar values and managed by doctrine objects, without references).
Also each job entity contains relation on the trigger, that was creating this job and entity hash (full class name
of the related entity and identity of the specific entity). This entity hash is needed to provide possibility to easy
and fast find all registered jobs for the same specific entity.


How it works?
-------------

Each of process definition related to the some entities (by full class name) and for each of definitions can be related
some number of triggers.

When user performs some action with entity which was related with some enabled process definition,
then for this process will be analyzed all existing triggers and depending on the trigger conditions
some triggers will be performed (or no one of them).

In the result of the performed trigger will be performed process actions and that actions can be performed immediately,
then process job not be created or after some delay, then will be created new process job and placed to the JMS queue.
If same entity has several process definitions, then them will be triggered in ascending value of the
execution order field and jobs will be placed in the JMS queue in right order.

When specific entity item will be deleted, then all useless job processes, like that was created after
triggered ``update`` or ``create`` event, will be deleted.

**Attention:** performing of the action that was described in the process definition can provoke triggers
of other process (or even same process). Please create your condition so that avoid recursion.
This problem will be fixed in future.

REST API
--------

OroWorkflowBundle provides REST API the allows to activate or deactivate some process.

Activation URL attributes:
* **route:** ``oro_workflow_api_rest_process_activate``
* **parameter:** workflowDefinition - name of the appropriate process

Deactivation URL attributes:
* **route:** ``oro_workflow_api_rest_process_deactivate``
* **parameter:** workflowDefinition - name of the appropriate process

Configuration
-------------

All processes are described in configuration. Look at example of simple process configuration that performs some action
with Contact entity.

```
definitions:                                                 # list of definitions
    contact_definition:                                      # name of process definition
        label: 'Contact Definition'                          # label of the process definition
        enabled: true                                        # this definition is enabled
        entity: OroCRM\Bundle\ContactBundle\Entity\Contact   # related entity
        order: 20                                            # place in order
        actions_configuration:                               # list of actions, what will be performed
            - @find_entity:                                  # find entity
                conditions:                                  # list of conditions
                    @empty: $assignedTo                      # if field $assignedTo is empty
                parameters:                                  # list of parameters
                    class: Oro\Bundle\UserBundle\Entity\User # $assignedTo entity full class name
                    attribute: $assignedTo                   # name of attribute from entity from previous item
                    where:                                   # where conditions
                        username: 'admin'                    # take field where username is 'admin'
triggers:                                                    # list of triggers
    contact_definition:                                      # name of trigger
        -
            event: create                                    # event on which the trigger performed
        -
            event: update
            field: assignedTo                                # field after update whom the trigger performed
            queued: true                                     # this process must be executed in queue
            time_shift: 60                                   # this process must be executed with delay in 60 seconds
```

This configuration describes Process that related to the ``Contact`` entity and all times when any contact will be
created or updated with empty ``Assigned To`` field, then this filed will be filled by value
of the current administrator. In other words - contact will be assigned to current administrator.

To to implement this behavior with this process definition related two triggers.
First of them will be performed immediately after the contact will be created, second - provoking creation of the
new process job and then job will be placed to JMS queue, where job will be performed after delay in ``60`` seconds.

**Note:** If you want to test this process configuration in real application, you can put this configuration in file
``Oro/Bundle/WorkflowBundle/Resources/config/process.yml``, reload definitions using console command
``app/console oro:process:configuration:load``
after that you can go to ``Contact`` view page and test it.