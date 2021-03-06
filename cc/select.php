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

$OUTPUT->header();
$OUTPUT->bodystart(false);
echo("<p>Kurs: ".htmlentities($l->lessons->title)."</p>\n");
echo("<p>".htmlentities($l->lessons->description)."</p>\n");
echo("<p>Ten kurs posiada: ".count($l->lessons->modules)." modułów</p>\n");
?>
<p>Możesz pobrać wszystkie moduły w jednym kartridżu (pliku) lub możesz pobrać dowolną kombinację poniższych modułów.</p>
<p>
<a href="export" class="btn btn-primary">Pobierz wszystkie moduły</a>
<?php     if ( isset($CFG->youtube_url) ) { ?>
<a href="export?youtube=yes" class="btn btn-primary">Download all modules with YouTube tracking</a>
<?php } ?>
</li>
</p>
<hr/>
<p>Wybierz z poniższych modułów:</p>
<?php
$resource_count = 0;
$assignment_count = 0;
echo('<form id="void">'."\n");
foreach($l->lessons->modules as $module) {
    echo('<input type="checkbox" name="'.$module->anchor.'" value="'.$module->anchor.'">'."\n");
    echo(htmlentities($module->title));
    $resources = Lessons::getUrlResources($module);
    if ( ! $resources ) continue;
    echo("<ul>\n");
    echo("<li>Zasoby w tym module: ".count($resources)."</li>\n");
    $resource_count = $resource_count + count($resources);
    if ( isset($module->lti) ) {
        echo("<li>Zadania w tym module: ".count($module->lti)."</li>\n");
        $assignment_count = $assignment_count + count($module->lti);
    }
    echo("</ul>\n");
}
?>
<input type="submit" value="Pobierz wybrane moduły" class="btn btn-primary" onclick="myfunc(''); return false;"/>
<?php     if ( isset($CFG->youtube_url) ) { ?>
<input type="submit" class="btn btn-primary" value="Download selected modules with YouTube tracking" onclick="myfunc('yes'); return false;"/>
<?php } ?>
</form>
<form id="real" action="export">
<input id="youtube" type="hidden" name="youtube"/>
<input id="res" type="hidden" name="anchors" value=""/>
</form>
<?php

$OUTPUT->footerStart();
?>
<script>
// https://stackoverflow.com/questions/13830276/how-to-append-multiple-values-to-a-single-parameter-in-html-form
function myfunc(youtube){
    $('#void input[type="checkbox"]').each(function(id,elem){
         console.log(this);
         if ( ! $(this).is(':checked') ) return;
         b = $("#res").val();
         if(b.length > 0){
            $("#res").val( b + ',' + $(this).val() );
        } else {
            $("#res").val( $(this).val() );
        }

    });
    var stuff = $("#res").val();
    if ( stuff.length < 1 ) {
        alert('<?= _m("Wybierz co najmniej jeden moduł") ?>');
    } else {
        if ( youtube == 'yes' ) {
            $("#youtube").val('yes');
        } else {
            $("#youtube").val('');
        }
        $("#real").submit();
    }
}
</script>
<?php
$OUTPUT->footerEnd();
