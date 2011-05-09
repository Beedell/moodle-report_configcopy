<?php
/**
 * Language strings for admin/report/configcopy plugin
 *
 * @copyright &copy; 2010 The Open University
 * @author j.beedell@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package configcopy
 */

// main report title
$string['configcopy'] = 'Config tools';

// form texts
$string['export'] = 'Export';
$string['exporttext'] = 'Download an xml file containing this site\'s current configuration.
    The following settings may be optionally included. If taking a backup of this site then
    select all, if the export is to be used to help set up another site then leave all unchecked:';
$string['import'] = 'Import';
$string['importfile'] = 'Import file';
$string['importtext'] = 'Import and update this site\'s configuration settings from an xml file.';
$string['introtext'] = 'This script enables import/export of all the main configuration settings for a
    site except for Authentication and Enrolment settings.
    This script does not import/export Roles, Courses or Categories or Users, and it does not install
    missing Modules or Blocks.
    It is recommended to take an export copy of your site as a backup, before importing data from another site.
    After importing data from another site please check all path and url settings (e.g. type \'path\' in the
    Site Administration search), the Debugging settings and Authentication and Enrolment settings.';
$string['title'] = 'Site configuration tools - an import and export facility';

// message texts
$string['exporterror'] = 'Sorry, there has been an error preparing the xml file for export.';
$string['importerror'] = 'Sorry, the following errors occured while trying to update this sites configuration: {$a}';
$string['importsuccess'] = 'Import successful.';
$string['importfileerror'] = 'Sorry, there is an issue uploading the xml file, please try again.';
$string['importxmlerror'] = 'Sorry, there is an issue reading the uploaded xml file, please check the file and try again.';