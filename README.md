CiviCRM-Donor-Trends-Extension
==============================
Queries the contribute tables and displays (in table or CSV) information about donor activity in relation to the previous year(s), categorizing donors as new, lapsed, upgraded, downgraded, or maintained.

* OVERVIEW report calculates numbers for new, lapsed, upgraded, downgraded and maintained donors for each target year.
* NEW, LAPSED, UPGRADED, DOWNGRADED, MAINTAINED reports include the breakdown of donors (+contact id, name, email, totals) that underly the numbers in overview.
* The 'Group' select is optional and limits results to donors within a certain group. 

definitions of donor groups:
* new: donor's earliest donation is in the target year
* lapsed: donor did not give in the target year, but gave in the previous year
* upgraded: donor gave more in the target year than in the previous year
* downgraded: donor gave less in the target year than in the previous year
* maintained: donor gave the same in the target year as in the previous year
