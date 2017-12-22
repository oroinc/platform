Feature: Predict feature time
  In order to divide suites by equal chunks
  There are should be algorithm to predict feature time

  Background: Prepare environment
    Given enabled Extension in behat.yml:
      """
      Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\BehatStatisticExtension:
        connection:
          driver: pdo_sqlite
          path: test.db
        criteria:
          branch_name: CHANGE_BRANCH_TEST
          target_branch: CHANGE_TARGET_TEST
          build_id: BUILD_ID_TEST
          single_branch_name: BRANCH_NAME_TEST
      """
    And enabled suites in behat.yml:
      """
      ping_pong:
          contexts:
              - Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\TestApp\PingPongContext
          paths: [ Features/ping_pong.feature]
      measurable_features:
          contexts:
              - Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\TestApp\FeatureContext
          paths:
              - Features/10_seconds.feature
              - Features/20_seconds.feature
      """
    And "test.db" sqlite database exists
    When I run "behat --update-statistic-schema"
    Then it should pass with:
      """
      Schema was updated successfully
      """
    And table "feature_stat" has data:
      | id | path                        | time | git_branch                      | git_target       | build_id |
      | 1  | Features/10_seconds.feature | 10   |                                 |                  | 1        |
      | 2  | Features/20_seconds.feature | 20   |                                 |                  | 1        |
      | 3  | Features/10_seconds.feature | 30   | master                          |                  | 2        |
      | 4  | Features/20_seconds.feature | 40   | master                          |                  | 2        |
      | 5  | Features/10_seconds.feature | 50   | maintenance_branch              |                  | 3        |
      | 6  | Features/20_seconds.feature | 60   | maintenance_branch              |                  | 3        |
      | 7  | Features/10_seconds.feature | 70   | feature_branch                  |                  | 4        |
      | 8  | Features/20_seconds.feature | 80   | feature_branch                  |                  | 4        |
      | 9  | Features/10_seconds.feature | 90   | pr_branch                       | master           | 5        |
      | 10 | Features/20_seconds.feature | 100  | pr_branch                       | master           | 5        |

  Scenario: Simple average
    Given environment variables:
      | CHANGE_BRANCH_TEST ||
      | CHANGE_TARGET_TEST ||
      | BUILD_ID_TEST      | 6 |
      | BRANCH_NAME_TEST   ||
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          ping_pong_0
              Features/ping_pong.feature - 45 sec
      AutoSuiteSet_1
          measurable_features_1
              Features/20_seconds.feature - 40 sec
      AutoSuiteSet_2
          measurable_features_0
              Features/10_seconds.feature - 30 sec
      """

  Scenario: Master branch the same as simple average
    Given environment variables:
      | CHANGE_BRANCH_TEST ||
      | CHANGE_TARGET_TEST ||
      | BUILD_ID_TEST      | 6      |
      | BRANCH_NAME_TEST   | master |
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          ping_pong_0
              Features/ping_pong.feature - 42 sec
      AutoSuiteSet_1
          measurable_features_1
              Features/20_seconds.feature - 40 sec
      AutoSuiteSet_2
          measurable_features_0
              Features/10_seconds.feature - 30 sec
      """

  Scenario: PR first build - Statistic from target branch
    Given environment variables:
      | CHANGE_BRANCH_TEST | fix/tests          |
      | CHANGE_TARGET_TEST | maintenance_branch |
      | BUILD_ID_TEST      | 1                  |
      | BRANCH_NAME_TEST   ||
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          measurable_features_1
              Features/20_seconds.feature - 60 sec
      AutoSuiteSet_1
          measurable_features_0
              Features/10_seconds.feature - 50 sec
      AutoSuiteSet_2
          ping_pong_0
              Features/ping_pong.feature - 48 sec
      """

  Scenario: PR second build - Statistic is already exist
    Given environment variables:
      | CHANGE_BRANCH_TEST | pr_branch |
      | CHANGE_TARGET_TEST | master    |
      | BUILD_ID_TEST      | 6         |
      | BRANCH_NAME_TEST   ||
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          measurable_features_1
              Features/20_seconds.feature - 100 sec
      AutoSuiteSet_1
          measurable_features_0
              Features/10_seconds.feature - 90 sec
      AutoSuiteSet_2
          ping_pong_0
              Features/ping_pong.feature - 63 sec
      """

  Scenario: Second branch build - Statistic is already exist
    Given environment variables:
      | CHANGE_BRANCH_TEST ||
      | CHANGE_TARGET_TEST ||
      | BUILD_ID_TEST      | 6              |
      | BRANCH_NAME_TEST   | feature_branch |
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          measurable_features_1
              Features/20_seconds.feature - 80 sec
      AutoSuiteSet_1
          measurable_features_0
              Features/10_seconds.feature - 70 sec
      AutoSuiteSet_2
          ping_pong_0
              Features/ping_pong.feature - 55 sec
      """
