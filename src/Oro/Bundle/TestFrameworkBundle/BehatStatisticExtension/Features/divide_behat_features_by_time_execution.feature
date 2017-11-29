Feature: Divide behat features by time execution
  In order to divide features into equal parts
  As a developer
  I should have such ability by using the dedicated command option

  Background: Setup statistic extension
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

  Scenario: Divide master branch features without ping-pong feature
    Given table "feature_stat" has data:
      | id | path                        | time | git_branch                      | git_target       | build_id |
      | 1  | Features/10_seconds.feature | 10   | master                          |                  |          |
      | 2  | Features/20_seconds.feature | 20   | master                          |                  |          |
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=30 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          measurable_features_1
              Features/20_seconds.feature - 20 sec
          measurable_features_0
              Features/10_seconds.feature - 10 sec
      AutoSuiteSet_1
          ping_pong_0
              Features/ping_pong.feature - 15 sec
      """

  Scenario: Divide feature branch by master statistics
    Given table "feature_stat" has data:
      | id | path                        | time | git_branch                      | git_target       | build_id |
      | 1  | Features/10_seconds.feature | 10   | master                          |                  |          |
      | 2  | Features/20_seconds.feature | 20   | master                          |                  |          |
    And environment variables:
      | CHANGE_BRANCH_TEST | ticket/BAP-1902_store_statistic |
      | CHANGE_TARGET_TEST | maintenance/2.4                 |
      | BUILD_ID_TEST      | 35815                           |
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=30 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          measurable_features_1
              Features/20_seconds.feature - 20 sec
          measurable_features_0
              Features/10_seconds.feature - 10 sec
      AutoSuiteSet_1
          ping_pong_0
              Features/ping_pong.feature - 15 sec
      """

  Scenario: Divide feature branch by its own statistics
    Given table "feature_stat" has data:
      | id | path                        | time | git_branch                      | git_target      | build_id |
      | 1  | Features/10_seconds.feature | 10   | master                          |                 |          |
      | 2  | Features/20_seconds.feature | 20   | master                          |                 |          |
      | 3  | Features/10_seconds.feature | 20   | ticket/BAP-1902_store_statistic | maintenance/2.4 | 35814    |
      | 4  | Features/20_seconds.feature | 30   | ticket/BAP-1902_store_statistic | maintenance/2.4 | 35814    |
    And environment variables:
      | CHANGE_BRANCH_TEST | ticket/BAP-1902_store_statistic |
      | CHANGE_TARGET_TEST | maintenance/2.4                 |
      | BUILD_ID_TEST      | 35815                           |
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=30 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          measurable_features_1
              Features/20_seconds.feature - 30 sec
      AutoSuiteSet_1
          ping_pong_0
              Features/ping_pong.feature - 21 sec
      AutoSuiteSet_2
          measurable_features_0
              Features/10_seconds.feature - 20 sec
      """


