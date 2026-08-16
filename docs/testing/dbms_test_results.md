# CampusFix DBMS Testing Results

## Sample Data Test
- 20 sample users inserted successfully.
- 20 realistic issue records inserted successfully.
- 8 issue confirmations inserted successfully.
- 8 issue assignments inserted successfully.

## Trigger Test
- Trigger: trg_issue_status_change
- Result: Passed
- Issue status changes automatically created records in issue_status_logs.
- 8 status log records were generated during sample data setup.

## Procedure Test
- Procedure: assign_issue_to_staff
- Result: Passed
- The procedure successfully assigned an issue to a staff member.
- The issue status changed from Pending to Assigned.
- The status trigger also recorded the change.
- Test changes were rolled back after verification.

## View Test
- View: vw_admin_issue_overview
- Result: Passed
- The view successfully displayed issue, category, reporter, location, priority, status, confirmation count and assigned staff information.

## Transaction Test
- Result: Passed
- A pending claim was updated to Approved.
- The related lost and found item was updated to Returned.
- ROLLBACK successfully prevented the test changes from becoming permanent.

## Investigation Query Test
- Result: Passed
- Unresolved issue category analysis returned results successfully.
- High and emergency priority location analysis returned results successfully.
- Staff workload analysis returned results successfully.
- Claim status analysis query executed successfully.

## Overall Result
All tested DBMS components operated successfully with the CampusFix sample dataset.