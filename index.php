<?php
/**
 * This was originally created to help developers with setting up development
 * environments that are as similar as possible to some other site.
 * Allows copying of a sites configuration settings so that new sites may be 
 * set up with the majority of settings the same as the originating site. Also
 * useful for backup purposes. It is a plugin to admin/report.
 *
 * @copyright &copy; 2010 The Open University
 * @author j.beedell@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package configcopy
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');

// A list of admin_settingpage settings that the user could include or not in an export.
// The idea is to include all settings if making a backup, and exclude these if porting to another 
// site as these may lead to issues on the new site.
// Format $key = setting section name, $value = array of setting unique names found in that section.
// Please add to this list as required (session settings could be added here, as could all url and path settings).
$includeoptions = array(
    // frontpagesettings may not be required on a dev site, just tick the checkbox if really wanted.
    'frontpagesettings' => array('fullname', 'shortname', 'defaultfrontpageroleid'),
    // role settings will not be the same unless we are just taking a site backup
    'optionalsubsystems' => array('progresstrackedroles'),
    'userpolicies' => array('notloggedinroleid', 'guestroleid', 'defaultuserroleid', 'creatornewroleid'),
    'gradessettings' => array('gradebookroles'),
    'enrolsettingscohort' => array('roleid'),
    'enrolsettingsmanual' => array('roleid'),
    'enrolsettingsself' => array('roleid'),
    'sitepolicies' => array('profileroles'),
    'coursecontact' => array('coursecontact')
);

$msg = optional_param('msg', '', PARAM_ALPHA);
$errorstr = optional_param('e', '', PARAM_TEXT);

class configcopyform extends moodleform {
    function definition () {
        $mform =& $this->_form;
        $mform->addElement('header', 'headerexport', get_string('export','report_configcopy'));
        $mform->addElement('static', 'export', '', get_string('exporttext','report_configcopy'));
        $include = $this->_customdata;
        foreach ($include as $key => $array) {
            $mform->addElement('checkbox', 'include['.$key.']', $array['settingkey'], $array['settingname']);
            // Note the default is not to include these settings unless the user actively ticks.
        }
        $mform->addElement('submit', 'exportflag', get_string('export','report_configcopy'));
        $mform->addElement('header', 'headerimport', get_string('import','report_configcopy'));
        $mform->addElement('static', 'import', '', get_string('importtext','report_configcopy'));
        $mform->addElement('filepicker', 'importfile', get_string('importfile','report_configcopy'));
        $mform->addElement('submit', 'importflag', get_string('import','report_configcopy'));
    }
}

// While admin_externalpage_setup checks for permission to view reports
// this script should only be available to the main site admins, so they
// can control the content of, and who gets access to the exported xml file
// from a live site. It is suggested that a live site admin could make an 
// export xml file that is kept separately for developers to access as required.
require_login(false, false);
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

admin_externalpage_setup('reportconfigcopy');

$includearray = array();
$adminroot = admin_get_root(); //get all settings
foreach ($includeoptions as $key => $value) {
    $page = $adminroot->locate($key);
    if (is_a($page, 'admin_settingpage')) {
        foreach ($page->settings as $settingkey => $setting) {
            if (in_array($settingkey, $value)) {
                $includearray[$setting->get_full_name()] = array('settingkey'=>$settingkey,
                                                                'settingname'=>$setting->visiblename);
            }
        }
    }
}

$mform = new configcopyform(null, $includearray);

if ($data = $mform->get_data()) {

    if (!empty($data->importflag)) {
        $destinationfolder = $CFG->dataroot.'/temp/config_'.time();
        if ($filecontents = $mform->get_file_content('importfile')) {
            $msg = set_config_from_file($filecontents);
        } else {
            $msg = 'importfileerror';
        }
        remove_dir($destinationfolder); //tidy up
    }

    if (!empty($data->exportflag)) {
        // Prepare list of settings to exclude.
        // Auth settings are on an external page so cannot be included in the include options display above.
        // Auth hard coded here as their inclusion could prevent a develper accessing their new dev.
        $exclude = array(
            's__registerauth',
            's__alternateloginurl',//Could redirect to the originating site if included.
            's__alternateregistrationurl',
            's__forgottenpasswordurl'
        );
        // Convert included options into those we need to exclude.
        foreach ($includearray as $key => $value) {
            if (!isset($data->include[$key])) {
                $exclude[] = $key;
            }
        }
        $xmlstring = create_xmlstring($exclude);
        send_file($xmlstring, 'config.xml', 0, 0, true, true, 'application/xml');
        // Should not get here. Possibly useful in the future if create_xmlstring were changed to throw error.
        $msg = 'exporterror';
    }

    redirect($CFG->wwwroot.'/'.$CFG->admin.'/report/configcopy/index.php?msg='.$msg);

} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('title','report_configcopy'));
    echo $OUTPUT->box(get_string('introtext','report_configcopy'));
    if ($msg) {
        if ($errorstr) {
            notify(get_string($msg,'report_configcopy',$errorstr));
        } else {
            notify(get_string($msg,'report_configcopy'));
        }
    }
    $mform->display();
    echo $OUTPUT->footer();
}

/**
 * Creates xml string containing representing site settings.
 * @param array $exclude a simple array of setting fullnames to be excluded from the output
 * @return string the xml document
 */
function create_xmlstring($exclude) {
    global $CFG, $DB;
    $xmlwriter = new XMLWriter();
    $xmlwriter->openMemory();
    $xmlwriter->setIndent(true);
    $xmlwriter->startDocument('1.0', 'utf-8', 'yes');
    $xmlwriter->startElement('configcopy');

    $xmlwriter->startElement('this_file_identity');
        // Write some file identity as a header - not really required, might be useful later.
        $site = get_site();
        $xmlwriter->writeElement('original_site_fullname', $site->fullname);
        $xmlwriter->writeElement('original_site_shortname', $site->shortname);
        $xmlwriter->writeElement('original_site_moodle_version', $CFG->version);
        $xmlwriter->writeElement('original_site_moodle_release', $CFG->release);
        $xmlwriter->writeElement('date_file_created', time());
    $xmlwriter->endElement();

    $configs = configcopy_get_settings($exclude);
    if (!empty($configs)) {
        $xmlwriter->startElement('configs');
        foreach ($configs as $key => $value) {
            $xmlwriter->startElement('item');
                $xmlwriter->writeElement('name', $key);
                if (is_array($value)) {
                    $xmlwriter->writeElement('value', serialize($value));
                    $xmlwriter->writeElement('serialized', 1);
                } else {
                    $xmlwriter->writeElement('value', $value);
                    $xmlwriter->writeElement('serialized', 0);
                }
            $xmlwriter->endElement();
        }
        $xmlwriter->endElement();
    }

    // Note only record the hidden modules/blocks in the xml.
    if ($modules = $DB->get_records('modules', array('visible'=>0))) {
        $xmlwriter->startElement('hiddenmodules');
        foreach ($modules as $module) {
            $xmlwriter->writeElement('modname', $module->name);
        }
        $xmlwriter->endElement();
    }

    if ($blocks = $DB->get_records('block', array('visible'=>0))) {
        $xmlwriter->startElement('hiddenblocks');
        foreach ($blocks as $block) {
            $xmlwriter->writeElement('blockname', $block->name);
        }
        $xmlwriter->endElement();
    }

    $xmlwriter->endElement();
    return $xmlwriter->outputMemory();
}

/**
 * Save configuration settings from an xml file to database.
 * @param string $strxml full path to the xml file - must be a readable file
 * @return string success or error code
 */
function set_config_from_file($strxml) {
    global $DB;

    $adminroot =& admin_get_root();
    if ($xml = simplexml_load_string($strxml)) {

        // Note no checks on whether this is correct file - this is for admin use!
        $version = $xml->this_file_identity->original_site_moodle_version;

        $newconfigs = array();
        foreach ($xml->configs->item as $item) {
            // Cope with some errors importing from previous versions of Moodle.
            if (2010102500 > $version) {
                if ('s__notifyloginfailures' == $item->name 
                    || 's__courserequestnotify' == $item->name 
                    || 's__emoticons' == $item->name)
                {
                    continue;
                }
            }
            if ('1' == (string)$item->serialized) {
                $value = unserialize((string)$item->value);
            } else {
                $value = (string)$item->value;
            }
            $newconfigs["{$item->name}"] = $value;
        }
        admin_write_settings($newconfigs);

        if ($modules = $xml->hiddenmodules) {
            // Make all modules/blocks visible, then hide those in the import.
            $DB->set_field('modules', 'visible', 1);
            foreach ($xml->hiddenmodules->modname as $modname) {
                if ($DB->get_record('modules', array('name'=>$modname))) {
                    $DB->set_field('modules', 'visible', 0, array('name'=>$modname));
                }
            }
        }

        if ($blocks = $xml->hiddenblocks) {
            $DB->set_field('block', 'visible', 1);
            foreach ($xml->hiddenblocks->blockname as $blockname) {
                if ($DB->get_record('block', array('name'=>$blockname))) {
                    $DB->set_field('block', 'visible', 0, array('name'=>$blockname));
                }
            }
        }

    } else {
        return 'importxmlerror';
    }

    if (!empty($adminroot->errors)) {
        $err = '';
        foreach ($adminroot->errors as $key => $value) {
            $err .= $key.' - '.$adminroot->errors[$key]->error.' ';
        }
        return 'importerror&amp;e='.$err;
    }
    return 'importsuccess';
}

/**
 * Iterative function creating an array of configuration setting names and values.
 * (based on adminlib.php's admin_find_write_settings()).
 * @param array $exclude a simple array of setting fullnames to not be included in the output
 * @param object $node
 * @return array
 */
function configcopy_get_settings($exclude, $node=NULL) {
    $return = array();
    if (is_null($node)) {
        $node = admin_get_root();
    }
    if (is_a($node, 'admin_category')) {
        $entries = array_keys($node->children);
        foreach ($entries as $entry) {
            $return = array_merge($return, configcopy_get_settings($exclude, $node->children[$entry]));
        }
    } else if (is_a($node, 'admin_settingpage')) {
        foreach ($node->settings as $setting) {
            $fullname = $setting->get_full_name();
            // Skip any settings that should not be included.
            if (in_array($fullname, $exclude)) {
                continue;
            }
            $return[$fullname] = $setting->get_setting();
        }
    }
    return $return;
}