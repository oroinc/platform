Feature: Filter configuration
  In order to reduce time for testing
  As a developer
  I need to have filtered list of suites according to executable features

  Background: Setup
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

  Scenario: Skip features by tag
    Given I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets --tags='~@ping-pong' -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          measurable_features_0
              Features/10_seconds.feature - 0 sec
          measurable_features_1
              Features/20_seconds.feature - 0 sec
      """
    But I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets --tags='@ping-pong' -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          ping_pong_0
              Features/ping_pong.feature - 0 sec
      """
