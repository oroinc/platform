Actions
=========

Table of Contents
-----------------
 - [Create Job](#create-job)

Create Job `@create_job`
------------------------------------

**Class:** Oro\Bundle\CronBundle\Action\CreateJobAction

**Alias:** create_job

**Description:** Creates Job (`JMS\JobQueueBundle\Entity\Job`)

**Options:**
 - `command` - (string) job command to be executed
 - `arguments` - (array, optional) arguments that be passed to job command
 - `allow_duplicates` - (boolean, optional, default = false) allow the same jobs to be added
 - `priority` - (integer, optional, default = 0) job priority
 - `queue` - (string, optional) job queue name
 - `commit` - (boolean, optional, default = false) immediately save job to database right after job created
 - `attribute` - (optional) property path where job entity instance should be placed in context


**Configuration Example**

The following example config demonstrates how to create new "Job" for command
   `oro:workflow:transit --workflow-item=15 --transition=test_transition`

```yml
#Resource/config/oro/actions.yml
operations:
    orob2b_somebundle_someoperation:
        ...
        actions:
            ...
            - '@create_job':
                command: 'oro:workflow:transit'
                arguments:
                    '--workflow-item': 15
                    '--transition': test_transition
                allow_duplicates: true
                commit: true
                priority: 5
                queue: 'my_queue'
```
