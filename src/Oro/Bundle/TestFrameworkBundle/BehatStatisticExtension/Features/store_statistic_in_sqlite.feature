Feature: Store statistic in sqlite
  In order to to store and process behat tests execution time
  As a develper
  I shold have statistic in persistent layer as sqlite

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
    And "test.db" sqlite database exists
    When I run "behat --update-statistic-schema"
    Then it should pass with:
      """
      Schema was updated successfully
      """

  Scenario: Store statistic
    Given environment variables:
      | CHANGE_BRANCH_TEST | ticket/BAP-1902_store_statistic |
      | CHANGE_TARGET_TEST | maintenance/2.4                 |
      | BUILD_ID_TEST      | 35815                           |
    And enabled suites in behat.yml:
      """
      default:
          contexts:
              - Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\TestApp\FeatureContext
          paths:
              - Features/1_second.feature
              - Features/2_seconds.feature
      """
    When I run "behat -f statistic -o std"
    Then it should pass
    And "feature_stat" table should contains records:
      | id | path                       | time | git_branch                      | git_target      | build_id |
      | 1  | Features/1_second.feature  | 1    | ticket/BAP-1902_store_statistic | maintenance/2.4 | 35815    |
      | 2  | Features/2_seconds.feature | 2    | ticket/BAP-1902_store_statistic | maintenance/2.4 | 35815    |
