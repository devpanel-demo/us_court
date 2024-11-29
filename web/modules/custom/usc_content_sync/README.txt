USC JUDICIAL AND JOB VACANCIES IMPORTER

The vacancy information is provided via an external system which drops xml files into
a directory and a cron runs to import them. The xml processor imports each field into
its own array. Additionally, the xml file does not have UUID for us to trigger on, so
we create "archive_id" to stub out an ID and have hook_migrate_prepare_row() create
one using the date() command if one is not already assigned based on the directory
being imported. The archive nomenclature should show as "YYYY_MM" to follow suit with
the existing archive structure. The Node Title uses the archive id as well. The Judicial
Vacancies for a particular month, are grouped together via their archive_id and so there
are display decisions made using that grouping, e.g., to display summary data on the other
vacancy nodes.

The most current archive uses an override to change the title and have the latest archive
appear at a specific url. Those are landing pages that came over in the D7 migration so
we are creating views blocks that an admin can place via Gutenberg within a landing page.

The "vacancy_rows" source field in the migration is provided by hook_migrate_prepare_row().
This process takes all the individual arrays of each field and builds a master array that the
"migrate_child_entity_generate" plugin can use to add Paragraphs to each node and simplify
the import.

We are using pseudo fields to expose some of the fields from the Summary Vacancy type to the
other Vacancy types, e.g., Total Nominees Pending and Total Vacancies.

Job vacancies do not have an archive; they only exist in a current state at
/careers/search-judiciary-jobs.
