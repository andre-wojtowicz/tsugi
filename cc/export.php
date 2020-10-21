<?php

use \Tsugi\UI\Lessons;
use \Tsugi\Util\U;
use \Tsugi\Util\CC;
use \Tsugi\Util\CC_LTI;
use \Tsugi\Util\CC_WebLink;

require_once "../config.php";

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

// Check if this is a remote import from Canvas
if ( isset($_POST['ext_content_return_url']) ) {
    $return_url = $_POST['ext_content_return_url'];
    $return_url = U::add_url_parm($return_url, 'return_type', 'file');
    $return_url = U::add_url_parm($return_url, 'text', $CFG->servicename);

    $export_url = $CFG->wwwroot . '/cc/export?tsugi_lms=canvas';
    $export_url_youtube = U::add_url_parm($export_url, 'youtube', 'yes');

    $return_url_normal = U::add_url_parm($return_url, 'url', $export_url);
    $return_url_youtube = U::add_url_parm($return_url, 'url', $export_url_youtube);

    $OUTPUT->header();
    $OUTPUT->bodystart(false);
    echo("<p>Kurs: ".htmlentities($l->lessons->title)."</p>\n");
    echo("<p>".htmlentities($l->lessons->description)."</p>\n");
    echo("<p>Moduły: ".count($l->lessons->modules)."</p>\n");
    $resource_count = 0;
    $assignment_count = 0;
    foreach($l->lessons->modules as $module) {
        $resources = Lessons::getUrlResources($module);
        if ( ! $resources ) continue;
        $resource_count = $resource_count + count($resources);
        if ( isset($module->lti) ) {
            $assignment_count = $assignment_count + count($module->lti);
        }
    }
    echo("<p>Zasoby: $resource_count </p>\n");
    echo("<p>Zadania: $assignment_count </p>\n");
    echo("<center>\n");
    echo('<a href="'.$return_url_normal.'" role="button" class="btn btn-success">Importuj kurs</a>');
    if ( isset($CFG->youtube_url) ) {
            echo('<br/><a href="'.$return_url_youtube.'" role="button" class="btn btn-success">Import Course With Tracked YouTube URLs</a>');
    }
    echo("</center>\n");
    $OUTPUT->footer();
    return;
}

// Check to see if we are building a cartridge for a subset
$anchor_str = U::get($_GET, 'anchors', false);
$anchors = false;
if ( $anchor_str ) $anchors = explode(',', $anchor_str);
if ( ! is_array($anchors) || count($anchors) < 1 ) $anchors = false;
$anchor_count = 0;
if ( $anchors ) {
    foreach($l->lessons->modules as $module) {
        if ( in_array($module->anchor, $anchors) ) {
            $anchor_count++;
        }
    }
    if ( $anchor_count < 1 ) $anchors = false;
}

// here we go...
$service = strtolower($CFG->servicename);
$filename = tempnam(sys_get_temp_dir(), $CFG->servicename);
if ( isCli() ) $filename = 'cc.zip';
$zip = new ZipArchive();
if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    die("Cannot open $filename\n");
}

if ( ! isCli() ) {
    header( "Content-Type: application/x-zip" );
    header( "Content-Disposition: attachment; filename=\"".$service."_export.imscc\"" );
}

$tsugi_lms = U::get($_GET,'tsugi_lms', false);
$youtube = U::get($_GET,'youtube', false);
if ( $youtube != 'yes' ) $youtube = false;

$cc_dom = new CC();
$cc_dom->set_title($CFG->context_title.' import');
$top_module = false;
if ( $tsugi_lms == 'sakai' ) {
    $top_module = $cc_dom->add_module('Imported content');
}

// $cc_dom->set_description('Awesome MOOC to learn PHP, MySQL, and JavaScript.');

foreach($l->lessons->modules as $module) {
    if ( isCli() ) echo("title=$module->title\n");
    if ( $anchors && ! in_array($module->anchor, $anchors) ) continue;
    if ( $top_module ) {
        $sub_module = $cc_dom->add_sub_module($top_module,$module->title);
    } else {
        $sub_module = $cc_dom->add_module($module->title);
    }

    if ( isset($module->videos) ) {
        foreach($module->videos as $video ) {
            $title = 'Film: '.$video->title;
            if ( $youtube && isset($CFG->youtube_url) ) {
                $custom_arr = array();
                $endpoint = U::absolute_url($CFG->youtube_url);
                $endpoint = U::add_url_parm($endpoint, 'v', $video->youtube);
                $extensions = array('apphome' => $CFG->apphome);
                $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions);
            } else {
                $url = 'https://www.youtube.com/watch?v=' . $video->youtube;
                $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
            }
        }
    }

    // Old way
    if ( isset($module->slides) && is_string($module->slides) ) {
        $url = U::absolute_url($module->slides);
        $title = 'Slajdy: '.$module->title;
        $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
    }

    // Array way
    if ( isset($module->slides) && is_array($module->slides) ) {
        foreach($module->slides as $slide) {
            if ( is_string($slide) ) {
                $slide_title = basename($slide);
                $slide_href = $slide;
            } else {
                $slide_title = $slide->title ;
                $slide_href = $slide->href ;
            }
            $url = U::absolute_url($slide_href);
            $title = 'Slajdy: '.$slide_title;
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
        }
    }

    if ( isset($module->assignment) ) {
        $url = U::absolute_url($module->assignment);
        $title = 'Zadanie: '.$module->title;
        $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
    }

    if ( isset($module->solution) ) {
        $url = U::absolute_url($module->solution);
        $title = 'Rozwiązanie: '.$module->title;
        $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
    }

    if ( isset($module->references) ) {
        foreach($module->references as $reference ) {
            $title = 'Link: '.$reference->title;
            $url = U::absolute_url($reference->href);
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
        }
    }

    if ( isset($module->lti) ) {
        foreach($module->lti as $lti ) {
            $title = isset($lti->title) ? $lti->title : $module->title;
            $title = 'Narzędzie: '.$title;
            $custom_arr = array();
            if ( isset($lti->custom) ) {
                foreach($lti->custom as $custom) {
                    if ( isset($custom->value) ) {
                        $custom_arr[$custom->key] = $custom->value;
                    }
                    if ( isset($custom->json) ) {
                        $custom_arr[$custom->key] = json_encode($custom->json);
                    }
                }
            }
            $endpoint = U::absolute_url($lti->launch);
            // Sigh - some LMSs don't handle custom - sigh
            $endpoint = U::add_url_parm($endpoint, 'inherit', $lti->resource_link_id);
            $extensions = array('apphome' => $CFG->apphome);
            $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions);
        }
    }
}

$zip->addFromString('imsmanifest.xml',$cc_dom->saveXML());

$zip->close();

if ( isCli() ) {
    echo("\nCLI run: Left zip file on cc.zip\n\n");
    return;
}

// Make sure to delete the file even if the download stops
// http://stackoverflow.com/questions/2641667/deleting-a-file-after-user-download-it

ignore_user_abort(true);
readfile($filename);
unlink($filename);
error_log("Downloaded $filename");



