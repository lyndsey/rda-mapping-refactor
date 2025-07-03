RDASA Module
============

* Adds page to import industries to taxonomies.
  path: '/admin/RDA_administration/import_industry_csv'

* Adds page to import projects:
  path: '/admin/RDA_administration/import_project_csv'

* Adds a page to import councils to the taxonomy
  path: '/admin/RDA_administration/import_council_csv'

An admin block
  path: '/admin/RDA_administration'


* When a project is saved with an address it converts the address to a Lat/Long location in the database. 

* When a map page is displayed it is sent the data for the Projects on that page

* The module contains some files needed by MapBox to display the maps.

* The import scripts add to the messenger about any issues during imports.

* The import scripts add to the logger with "rdasa_project_import" about any issues during imports.

