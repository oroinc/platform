Feature: Average strategy
  In order to get more accuracy dividing
  As a developer
  I need to simply change dividing average strategy

  Background: Prepare environment
    Given enabled Extension in behat.yml:
      """
      Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\BehatStatisticExtension:
        connection:
          dbname: test_behat_stats
          user: test
          password: null
          host: localhost
          driver: pdo_mysql
        average_strategy: AVG
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
    And mysql database exists
    When I run "behat --update-statistic-schema"
    Then it should pass with:
      """
      Schema was updated successfully
      """
    And table "feature_stat" has data:
      | id | path                        | time | git_branch | git_target | build_id |
      | 1  | Features/10_seconds.feature | 10   |            |            | 1        |
      | 2  | Features/20_seconds.feature | 20   |            |            | 1        |
      | 3  | Features/10_seconds.feature | 30   |            |            | 2        |
      | 4  | Features/20_seconds.feature | 40   |            |            | 2        |
      | 5  | Features/10_seconds.feature | 50   |            |            | 3        |
      | 6  | Features/20_seconds.feature | 60   |            |            | 3        |
      | 7  | Features/10_seconds.feature | 70   |            |            | 4        |
      | 8  | Features/20_seconds.feature | 80   |            |            | 4        |
      | 9  | Features/10_seconds.feature | 90   |            |            | 5        |
      | 10 | Features/20_seconds.feature | 100  |            |            | 5        |

  Scenario: AVG+STD not supported on sqlite
    Given I reconfigure StatisticExtension:
      """
      connection:
        driver: pdo_sqlite
        path: test.db
      average_strategy: AVG+STD
      """
    Given "test.db" sqlite database exists
    And I run "behat --update-statistic-schema"
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets"
    Then it should fail with:
      """
      There is no "AVG+STD" strategy for "sqlite" platform to inject into aware service(s)
      """

  Scenario: Simple strategy
    Given I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          measurable_features_1
              Features/20_seconds.feature - 60 sec
      AutoSuiteSet_1
          ping_pong_0
              Features/ping_pong.feature - 55 sec
      AutoSuiteSet_2
          measurable_features_0
              Features/10_seconds.feature - 50 sec
      """

  Scenario: AVG+STD strategy
    Given I reconfigure StatisticExtension:
      """
      connection:
          dbname: test_behat_stats
          user: test
          password: null
          host: localhost
          driver: pdo_mysql
      average_strategy: AVG+STD
      """
    When I run "behat  --suite-divider=1 --max_suite_set_execution_time=1 --available-suite-sets -vvv"
    Then it should pass with:
      """
      AutoSuiteSet_0
          measurable_features_1
              Features/20_seconds.feature - 88 sec
      AutoSuiteSet_1
          ping_pong_0
              Features/ping_pong.feature - 83 sec
      AutoSuiteSet_2
          measurable_features_0
              Features/10_seconds.feature - 78 sec
      """
