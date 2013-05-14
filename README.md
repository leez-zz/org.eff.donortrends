CiviCRM-Donor-Trends-Extension
==============================
This is a CiviCRM extension that queries the CiviCRM database, displaying or producing CSV files from the results.

* OVERVIEW calculates numbers for new, lapsed, upgraded, downgraded and maintained donors for each target year.
* NEW, LAPSED, UPGRADED, DOWNGRADED, MAINTAINED include the breakdown of donors (+contact id, name, email, totals) that underly the numbers in overview.
* The 'Group' select is optional and limits results to donors within a certain group. 

definitions of donor groups:
* new: donor's earliest donation is in the target year
* lapsed: donor did not give in the target year, but gave in the previous year
* upgraded: donor gave more in the target year than in the previous year
* downgraded: donor gave less in the target year than in the previous year
* maintained: donor gave the same in the target year as in the previous year
