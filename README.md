open-attendee-tracker
=====================

Open Attendee Tracker (dev code name) release date: 2013-10-08

This system allows vendors to track their booth attendees.

Process:

1. Vendor scans their personal barcode.

2. Scanner software takes them to a page that creates a session.

3. Vendor scans someone else's barcode.

4. Scanner software takes them to a page that records the attendee and displays their first name to the vendor.

5. Vendor repeats steps 3 and 4 for each attendee. Should the session timeout or be lost, they simply repeat steps 1 and 2.

6. After the conference is over, an admin processes the list of attendees seen by the vendor and emails all their information to the vendor.

Requirements
------------
* PostgreSQL database with three local users (eg. human, vendor, admin) and populated with the contents of 'schema.sql'
* The 'phpqrcode' library
* The 'fpdf' library

Installation
------------
* apt-get install libapache2-mod-php5 postgresql php5-pgsql php5-gd
* echo "CREATE DATABASE openattendee" | sudo -u postgres psql
* sudo -u postgres psql openattendee < schema.sql

To Do
-----
* ADMIN: CSS to make things look nicer

* HUMAN: CSS to make output appear larger on mobile devices

