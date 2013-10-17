OVERVIEW:

get-MSM-stats.php is a PHP script that collects wireless clients association data
from an HP MSM series wireless controller.

For each MSM radio managed by the controller it will collect and output one line
to a CSV file each time the script is run. The following data is collected per radio:

Date and Time
AP Name
Radio1 Associations
Radio2 Associatons
Total Associations
Number of 'a' Clients
Number of 'b' Clients
Number of 'g' Clients
Number of 'bg' Clients
Number of 'n' Clients

Note that the first line of the output CSV file will contains 'headers' indicating the name of each column.


REQUIREMENTS:
- PHP v5.3 or better, with SNMP modules enabled.
- An HP MSM series controller, running firmware v5.7 or better

Note: this scripts has only been tested in a Linux environment, with a MSM 765zl controller running v5.7.x and v6.0.x.

INSTALLATION:

- Copy all files to a directory somewhere, on an OS with PHP installed, and SNMP access to your controller.
- Copy or rename the "MSM-config.inc.php.default" file to "MSM-config.inc.php"
- Modify the above file as needed with your SNMP community and controller IP
- Test run with php from command line
- If all okay, automate running at an interval as needed, using cron, "Scheduled Tasks", etc.

WARRANTY & LICENSE:

This script is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License,
or (at your option) any later version.

This script is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this script.  If not, see <http://www.gnu.org/licenses/>.

Copyright  2013 onwards, Johan Reinalda  < johan at reinalda dot net >


