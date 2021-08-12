@local @local_integrity
Feature: Test basic feature of Integrity plugin

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teachy    |
      | student  | Study     |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And I log in as "admin"
    And I set the following administration settings values:
      | integritystmt_forum_default_enabled | 1               |
      | integritystmt_forum_notice          | Statement text! |
    And I log out

  @javascript
  Scenario: Require students to agree, then check the they have to.
    # Add a forum to a course without the condition, and verify that they can start it as normal.
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Name        | Forum no agree required                                   |
      | Description | This forum does not require students to agree to anything |
    And I log out
    And I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Forum no agree required"
    Then I should not see "Academic integrity notice"
    And I should not see "Statement text!"

    # Add a forum to a course with the condition, and verify that the student is challenged.
    When I log out
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Name                               | Forum agree is required                            |
      | Description                        | This forum requires students to agree not to cheat |
      | Display academic integrity notice? | Yes                                                |
    And I log out
    And I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Forum agree is required"
    Then I should see "Academic integrity notice"
    And I should see "Statement text!"

    # Continuing without ticking is blocked.
    And I click on "Agree" "button" in the "Academic integrity notice" "dialogue"
    Then I should see "You must agree to continue."

    # Continuing with ticking is OK.
    When I set the field "I have read and agree to the above statement" to "1"
    And I press "Agree"
    Then I should see "Add a new discussion topic"

    # Test that statement is not displayed after agreement.
    When I log out
    And I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Forum agree is required"
    Then I should not see "Academic integrity notice"
    And I should not see "Statement text!"
    And I should see "Add a new discussion topic"

    # Test that admins can bypass statement.
    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Forum agree is required"
    Then I should not see "Academic integrity notice"
    And I should not see "Statement text!"
    And I should see "Add a new discussion topic"
