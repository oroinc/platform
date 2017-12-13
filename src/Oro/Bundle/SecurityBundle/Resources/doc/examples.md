Examples
========

We have:

 - 2 organizations: **Main Organization** and the **Second Organization**;
 - Main Organization contains one **BU A**;
 - Second Organization contains one **BU B**;
 - **BU B** contains one subordinate **BU C**;
 - user **John** was created in the Main Organization and **BU A**;
 - user **Mary** was created in the Main Organization and **BU A**;
 - user **Mike** was created in the Second Organization and **BU C**;
 - user **Robert** was created in the Second Organization and **BU B**;
 - user **Mark** was created in the Second Organization and **BU B**;
 
Users was assigned:

| User      | Main Organization     | Second Organization    |
| --------- |:---------------------:| :---------------------:|
| **John**  | BU A                  | BU C                   |
| **Mary**  | BU A                  | BU B                   |
| **Mike**  |    **No access**      | BU C                   |
| **Robert**| BU A                  | BU B                   |
| **Mark**  |    **No access**      |   **No business units**|

 
User ownersip type
---------
Ownreship type for accounts is **User**.

Each of users was created two Account records in different organizations:

| User      | Main Organization  | Second Organization  |
| --------- |:------------------:| :--------------------|
| **John**  | Account A          | Account E            |
| **Mary**  | Account B          | Account F            |
| **Mike**  | Account G          | Account C            |
| **Robert**| Account H          | Account D            |
| **Mark**  | Account I          | Account J            |

![User-ownership][1]

### John 

If user **John** will login into the **Main organization**, he should see the next data:

| Access level       | Data                                                  |
|:------------------ | :---------------------------------------------------- |
| **User**           | Account A                                             |
| **Business Unit**  | Account A, Account B, Account H                       |
| **Division**       | Account A, Account B, Account H                       |
| **Organization**   | Account A, Account B, Account H, Account G, Account I |

If user **John** will login into the **Second organization**, he should see the next data:

| Access level       | Data                                                  |
|:------------------ | :---------------------------------------------------- |
| **User**           | Account E                                             |
| **Business Unit**  | Account E, Account C                                  |
| **Division**       | Account E, Account C                                  |
| **Organization**   | Account E, Account C, Account D, Account F, Account J |


### Mary

If user **Mary** will login into the **Main organization**, she should see the next data:

| Access level       | Data                                                  |
|:------------------ | :---------------------------------------------------- |
| **User**           | Account B                                             |
| **Business Unit**  | Account B, Account A, Account H                       |
| **Division**       | Account B, Account A, Account H                       |
| **Organization**   | Account B, Account A, Account H, Account G, Account I |

If user **Mary** will login into the **Second organization**, she should see the next data:

| Access level       | Data                                                  |
|:------------------ | :---------------------------------------------------- |
| **User**           | Account F                                             |
| **Business Unit**  | Account F, Account D                                  |
| **Division**       | Account F, Account D, Account C, Account E            |
| **Organization**   | Account F, Account D, Account C, Account E, Account J |

### Mike

User **Mike** can not login into the **Main organization**.

If user **Mike** will login into the **Second organization**, he should see the next data:

| Access level       | Data                                                  |
|:------------------ | :---------------------------------------------------- |
| **User**           | Account C                                             |
| **Business Unit**  | Account C, Account E                                  |
| **Division**       | Account C, Account E                                  |
| **Organization**   | Account C, Account E, Account D, Account F, Account J |

### Robert

If user **Robert** will login into the **Main organization**, he should see the next data:

| Access level       | Data                                                  |
|:------------------ | :---------------------------------------------------- |
| **User**           | Account H                                             |
| **Business Unit**  | Account H, Account A, Account B                       |
| **Division**       | Account H, Account A, Account B                       |
| **Organization**   | Account H, Account A, Account B, Account G, Account I |

If user **Robert** will login into the **Second organization**, he should see the next data:

| Access level       | Data                                                  |
|:------------------ | :---------------------------------------------------- |
| **User**           | Account D                                             |
| **Business Unit**  | Account D, Account F, Account E                       |
| **Division**       | Account D, Account F, Account C, Account E            |
| **Organization**   | Account D, Account F, Account C, Account E, Account J |

### Mark

User **Mark** can not login into the **Main organization**.

If user **Mark** will login into the **Second organization**, he should see the next data:

| Access level       | Data                                                  |
|:------------------ | :---------------------------------------------------- |
| **User**           | Account J                                             |
| **Business Unit**  | Account J                                             |
| **Division**       | Account J                                             |
| **Organization**   | Account J, Account F, Account E, Account C, Account D |
   
Business Unit ownersip type
---------

Ownreship type for accounts is **Business Unit**.

We cannot set User access level. Minimal access level is Business unit.
 
We have the next data:

- Account A was created in the Main Organization and BU A;
- Account B was created in the Main Organization and BU A;
- Account C was created in the Second organization and BU C;
- Account D was created in the Second organization and BU B;
- Account E was created in the Second organization and BU B;

![Business-unit-ownership][2]

### John 

If user **John** will login into the **Main organization**, he should see the next data:

| Access level       | Data                 |
|:------------------ | :------------------- |
| **Business Unit**  | Account A, Account B |
| **Division**       | Account A, Account B |
| **Organization**   | Account A, Account B |

If user **John** will login into the **Second organization**, he should see the next data:

| Access level       | Data                            |
|:------------------ | :------------------------------ |
| **Business Unit**  | Account C                       |
| **Division**       | Account C                       |
| **Organization**   | Account C, Account D, Account E |

### Mary 

If user **Mary** will login into the **Main organization**, she should see the next data:

| Access level       | Data                 |
|:------------------ | :------------------- |
| **Business Unit**  | Account A, Account B |
| **Division**       | Account A, Account B |
| **Organization**   | Account A, Account B |

If user **John** will login into the **Second organization**, he should see the next data:

| Access level       | Data                            |
|:------------------ | :------------------------------ |
| **Business Unit**  | Account D, Account E            |
| **Division**       | Account D, Account E, Account C |
| **Organization**   | Account D, Account E, Account C |

### Mike 

User **Mike** can not login into the **Main organization**.

If user **Mike** will login into the **Second organization**, he should see the next data:

| Access level       | Data                            |
|:------------------ | :------------------------------ |
| **Business Unit**  | Account C                       |
| **Division**       | Account C                       |
| **Organization**   | Account C, Account D, Account E |

### Robert 

If user **Robert** will login into the **Main organization**, he should see the next data:

| Access level       | Data                 |
|:------------------ | :------------------- |
| **Business Unit**  | Account A, Account B |
| **Division**       | Account A, Account B |
| **Organization**   | Account A, Account B |

If user **Robert** will login into the **Second organization**, he should see the next data:

| Access level       | Data                 |
|:------------------ | :------------------- |
| **Business Unit**  | Account A, Account B |
| **Division**       | Account A, Account B |
| **Organization**   | Account A, Account B |

| Access level       | Data                            |
|:------------------ | :------------------------------ |
| **Business Unit**  | Account D, Account E            |
| **Division**       | Account C, Account D, Account E |
| **Organization**   | Account C, Account D, Account E |

Column1 | Column2 | Column3
---|---|---
Text | Text | Text
Text2 | Text2 | Text2

### Mark

User **Mark** can not login into the **Main organization**.

If user **Mark** will login into the **Second organization**, he should see the next data:

| Access level       | Data                            |
|:------------------ | :------------------------------ |
| **Business Unit**  | -                               |
| **Division**       | -                               |
| **Organization**   | Account C, Account D, Account E |


| Access level       | Data                            |
|:------------------ | :------------------------------ |
| **Business Unit**  | -                               |
| **Division**       | -                               |
| **Organization**   | Account C, Account D, Account E |

   
Organization Ownership type
---------------------------

Ownreship type for accounts is **Organization**.

We cannot set User, Business unit and Division access level. Minimal access level is Organization.
 
We have the next data:

- Account A was created in the Main Organization;
- Account B was created in the Main Organization;
- Account C was created in the Second organization;
- Account D was created in the Second organization;
- Account E was created in the Second organization;

![Organization-ownership][3]

### John, Mary, Robert

If this users will login into the **Main organization**, they should see next data:

- in Organization access level: Account A, Account B


If this users will login into the **Second organization**, they should see next data:

- in Organization access level: Account C, Account D, Account E


### Mike, Mark

This users does can not login into the First Organization.

If this users will login into the **Second organization**, they should see next data:

- in Organization access level: Account C, Account D, Account E
 

  [1]: img/User-ownership.png
  [2]: img/BusinessUnit-ownership.png
  [3]: img/Organization-ownership.png
