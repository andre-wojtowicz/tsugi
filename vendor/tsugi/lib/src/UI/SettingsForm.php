<?php

namespace Tsugi\UI;

/**
 * A series of routines used to generate and process the settings forms.
 */

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;

class SettingsForm {

    /**
     * Check for incoming settings post data
     *
     * @return boolean Returns true if there were settings to handle and false
     * if there was nothing done.  Generally the calling tool will redirect
     * when true is returned.
     *
     *     if ( SettingsForm::isSettingsPost() ) {
     *         // Do form validation if you like
     *         SettingsForm::handleSettingsPost();
     *         header( 'Location: '.U::addSession('index.php?howdysuppress=1') ) ;
     *         return;
     *     }
     */
    public static function isSettingsPost() {
        global $USER;
        if ( ! $USER ) return false;
        return ( isset($_POST['settings_internal_post']) && $USER->instructor );
    }

    /**
     * Handle incoming settings post data
     *
     * @return boolean Returns true if there were settings to handle and false
     * if there was nothing done.  Generally the calling tool will redirect
     * when true is returned.
     *
     *     if ( SettingsForm::handleSettingsPost() ) {
     *         header( 'Location: '.U::addSession('index.php?howdysuppress=1') ) ;
     *         return;
     *     }
     */
    public static function handleSettingsPost() {
        global $USER;
        if ( ! $USER ) return false;

        if ( isset($_POST['settings_internal_post']) && $USER->instructor ) {
            $newsettings = array();
            foreach ( $_POST as $k => $v ) {
                if ( $k == session_name() ) continue;
                if ( $k == 'settings_internal_post' ) continue;
                if ( strpos('_ignore',$k) > 0 ) continue;
                $newsettings[$k] = $v;
            }

            // Merge these with the existing settings
            Settings::linkUpdate($newsettings);
            return true;
        }
        return false;
    }

    /**
      * Emit a properly styled "settings" button
      *
      * This is just the button, using the pencil icon.  Wrap in a
      * span or div tag if you want to move it around
      */
    public static function buttonText($right = false)
    {
        global $LINK;
        if ( ! $LINK ) return;
        $retval = "";
        if ( $right ) $retval .= '<span style="position: fixed; right: 10px; top: 5px;">';
        $retval .= '<button type="button" '.self::attr().' class="btn btn-default">';
        $retval .= '<span class="glyphicon glyphicon-pencil"></span></button>'."\n";
        if ( $right ) $retval .= '</span>';
        return $retval;
    }

    /**
      * Emit a properly styled "settings" button
      *
      * This is just the button, using the pencil icon.  Wrap in a
      * span or div tag if you want to move it around
      */
    public static function button($right = false)
    {
        echo(self::buttonText($right));
    }


    /**
     * Emit a properly styled "settings" link
     *
     * This is just the link, using the pencil icon and label.
     */
    public static function link($right = false)
    {
        global $LINK;
        if ( ! $LINK ) return;
        if ($right) {
            $pos = "pull-right";
        } else {
            $pos = "";
        }

        echo '<button type="button" '.self::attr().' class="btn btn-link '.$pos.'");>';
        echo '<span class="fas fa-cog" aria-hidden="true"></span> '.__("Settings").'</button>'."\n";
    }

    /**
     * Return the attributes to add to a tag to connect to activate the settings modal
     */
    public static function attr()
    {
        return 'data-toggle="modal" data-target="#settings"';
    }


    public static function start() {
        global $USER, $OUTPUT, $LINK;
        if ( ! $USER ) return;
?>
<!-- Modal -->
<div id="settings" class="modal fade" role="dialog" style="display: none;">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span class="fa fa-close" aria-hidden="true"></span><span class="sr-only">Close</span></button>
        <h4 class="modal-title"><?=htmlentities($LINK->title)?> - Konfiguracja</h4>
      </div>
      <div class="modal-body">
      <?php if ( $USER->instructor ) { ?>
        <form method="post">
      <?php } ?>
            <img id="settings_spinner" src="<?php echo($OUTPUT->getSpinnerUrl()); ?>" style="display: none">
            <span id="save_fail" class="text-danger" style="display:none;"><?php _me('Nie można zapisać ustawień'); ?></span>
            <?php if ( $USER->instructor ) { ?>
            <input type="hidden" name="settings_internal_post" value="1"/>
            <?php }
    }

    /**
     * Finish the form output
     */
    public static function end() {
        global $USER;
        if ( ! $USER ) return;
?>
        <?php if ( $USER->instructor ) { ?>
        <button type="button" id="settings_save" onclick="submit();" class="btn btn-primary"><?= _m("Zapisz ustawienia") ?></button>
        </form>
        <?php } ?>
      </div>
    </div>

  </div>
</div>
<?php
    }

    /**
     * Handle a settings selector box
     */
    public static function select($name, $default=false, $fields)
    {
        global $USER;
        if ( ! $USER ) return;
        $oldsettings = Settings::linkGetAll();
        if ( ! $USER->instructor ) {
            $configured = false;
            foreach ( $fields as $k => $v ) {
                $index = $k;
                $display = $v;
                if ( is_int($index) ) $index = $display; // No keys
                if ( ! is_string($display) ) $display = $index;
                if ( isset($oldsettings[$name]) && $k == $oldsettings[$name] ) {
                    $configured = $display;
                }
            }
            if ( $configured === false ) {
                echo('<p>'._m('Ustawienie').' '.htmlent_utf8($name).' '._m('nie jest skonfigurowane').'</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' '._m('ma ustawioną wartość').' '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        if ( $default === false ) $default = _m('Please Select');
        echo('<div class="form-group"><select class="form-control" name="'.$name.'">');
        echo('<option value="0">'.$default.'</option>');
        foreach ( $fields as $k => $v ) {
            $index = $k;
            $display = $v;
            if ( is_int($index) ) $index = $display; // No keys
            if ( ! is_string($display) ) $display = $index;
            echo('<option value="'.$index.'"');
            if ( isset($oldsettings[$name]) && $index == $oldsettings[$name] ) {
                echo(' selected');
            }
            echo('>'.$display.'</option>'."\n");
        }
        echo('</select></div>');
    }

    /**
     * Handle a settings text box
     */
    public static function text($name, $title=false)
    {
        global $USER;
        if ( ! $USER ) return false;

        $oldsettings = Settings::linkGetAll();
        $configured = isset($oldsettings[$name]) ? $oldsettings[$name] : false;
        if ( $title === false ) $title = $name;
        if ( ! $USER->instructor ) {
            if ( $configured === false || strlen($configured) < 1 ) {
                echo('<p>'._m('Setting').' '.htmlent_utf8($name).' '._m('is not set').'</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' '._m('is set to').' '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        ?>
        <div class="form-group">
            <label for="<?=$name?>"><?=htmlent_utf8($title)?></label>
            <input type="text" class="form-control" id="<?=$name?>" name="<?=$name?>" value="<?=htmlent_utf8($configured)?>">
        </div>
        <?php
    }

    /**
     * Handle a settings textarea box
     */
    public static function textarea($name, $title=false)
    {
        global $USER;
        if ( ! $USER ) return false;
        $oldsettings = Settings::linkGetAll();
        $configured = isset($oldsettings[$name]) ? $oldsettings[$name] : false;
        if ( $title === false ) $title = $name;
        if ( ! $USER->instructor ) {
            if ( $configured === false ) {
                echo('<p>'._m('Setting').' '.htmlent_utf8($name).' '._m('is not set').'</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' '._m('is set to').' '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        ?>
        <div class="form-group">
            <label for="<?=$name?>"><?=htmlent_utf8($title)?></label>
            <textarea class="form-control" rows="5" id="<?=$name?>" name="<?=$name?>"><?=htmlent_utf8($configured)?></textarea>
        </div>
        <?php
    }

    /**
     * Handle a settings checkbox
     */
    public static function checkbox($name, $title=false)
    {
        global $USER;
        if ( ! $USER ) return false;
        $oldsettings = Settings::linkGetAll();
        $configured = isset($oldsettings[$name]) ? $oldsettings[$name] : false;
        if ( $title === false ) $title = $name;
        if ( ! $USER->instructor ) {
            if ( $configured === false ) {
                echo('<p>'._m('Setting').' '.htmlent_utf8($name).' '._m('is not set').'</p>');
            } else {
                echo('<p>'.htmlent_utf8(ucwords($name)).' is set to '.htmlent_utf8($configured).'</p>');
            }
            return;
        }

        // Instructor view
        ?>
        <div class="checkbox">
            <label><input type="checkbox" value="1" name="<?=$name?>"
            <?php
        if ( $configured == 1 ) {
            echo(' checked ');
            echo("onclick=\"if(this.checked) document.getElementById('");
            echo($name);
            echo(".mirror').name = '");
            echo($name);
            echo(".ignore'; else document.getElementById('");
            echo($name);
            echo(".mirror').name = '");
            echo($name);
            echo("';\"");
        }
            ?>
                ><?=htmlent_utf8($title)?></label>
        </div>
        <?php
        if ( $configured == 1 ) {
            echo("<input type=\"hidden\" name=\"");
            echo($name);
            echo(".ignore\" id=\"");
            echo($name);
            echo(".mirror\" value=\"0\" />");
        }
    }
    /**
      * Get the due data data in an object
      */

    public static function getDueDate() {
        $retval = new \stdClass();
        $retval->penaltyinfo = false;  // Details about the penalty irrespective of the current date
        $retval->message = false;
        $retval->penalty = 0;
        $retval->dayspastdue = 0;
        $retval->percent = 0;
        $retval->duedate = false;
        $retval->duedatestr = false;

        $duedatestr = Settings::linkGet('due');
        if ( $duedatestr === false ) return $retval;
        $duedate = strtotime($duedatestr);

        $diff = -1;
        $penalty = false;

        date_default_timezone_set('Europe/Warsaw'); // Lets be generous
        $new_time_zone = Settings::linkGet('timezone');
        if ( $new_time_zone && in_array($new_time_zone, timezone_identifiers_list())) {
            date_default_timezone_set($new_time_zone);
        }

        if ( $duedate === false ) return $retval;

        $penalty_time = Settings::linkGet('penalty_time') ? Settings::linkGet('penalty_time') + 0 : 24*60*60;
        $penalty_cost = Settings::linkGet('penalty_cost') ? Settings::linkGet('penalty_cost') + 0.0 : 0.2;

        $retval->penaltyinfo = sprintf(_m("Po upływie terminu oddania zadania Twój wynik zostanie zredukowany o %f procent, a każde dodatkowo % po upływie terminu dodatkowo zredukuje Twój wynik o dodatkowe %f procent."),
                htmlent_utf8($penalty_cost*100), htmlent_utf8(self::getDueDateDelta($penalty_time)),
                htmlent_utf8($penalty_cost*100) );

        //  If it is just a date - add nearly an entire day of time...
        if ( strlen($duedatestr) <= 10 ) $duedate = $duedate + 24*60*60 - 1;
        $diff = time() - $duedate;

        $retval->duedate = $duedate;
        $retval->duedatestr = $duedatestr;
        // Should be a percentage off between 0.0 and 1.0
        if ( $diff > 0 ) {
            $penalty_exact = $diff / $penalty_time;
            $penalties = intval($penalty_exact) + 1;
            $penalty = $penalties * $penalty_cost;
            if ( $penalty < 0 ) $penalty = 0;
            if ( $penalty > 1 ) $penalty = 1;
            $retval->penalty = $penalty;
            $retval->dayspastdue = $diff / (24*60*60);
            $retval->percent = intval($penalty * 100);
            $retval->message = sprintf(
                _m("Obecnie minęło %s od terminu oddania zadania (%s), zatem kara za oddanie po terminie wynosi %f procent."),
                self::getDueDateDelta($diff), htmlentities($duedatestr),$retval->percent);
        }
        return $retval;
    }

    /**
     * Show a due date delta in reasonable units
     */
    public static function getDueDateDelta($time)
    {
        if ( $time < 600 ) {
            $delta = $time . ' sek.';
        } else if ($time < 3600) {
            $delta = sprintf("%0.0f",($time/60.0)) . ' ' . _m('min.');
        } else if ($time <= 86400 ) {
            $delta = sprintf("%0.2f",($time/3600.0)) . ' ' . _m('godz.');
        } else {
            $delta = sprintf("%0.2f",($time/86400.0)) . ' ' . _m('dni');
        }
        return $delta;
    }

    /**
     * Emit the text and form fields to support due dates
     */
    public static function dueDate()
    {
        global $USER;
        if ( ! $USER ) return false;
        $due = Settings::linkGet('due', '');
        $timezone = Settings::linkGet('timezone', 'Europe/Warsaw');
        if ( ! in_array($timezone, timezone_identifiers_list()) ) $timezone = 'Europe/Warsaw';
        $time = Settings::linkGet('penalty_time', 86400);
        $cost = Settings::linkGet('penalty_cost', 0.2);

        if ( ! $USER->instructor ) {
            if ( strlen($due) < 1 ) {
                echo("<p>"._m("There is currently no due date/time for this assignment.")."</p>\n");
                return;
            }
            $dueDate = self::getDueDate();
            echo("<p>"._m("Due date: ").htmlent_utf8($due)."</p>\n");
            echo("<p>".$dueDate->penaltyinfo."</p>\n");
            if ( $dueDate->message ) {
                echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
            }
            return;
        }
?>
        <label for="due">
            <?= _m("Wprowadź termin wykonania zadania w formacie ISO 8601 (np. 2020-01-30T20:30) lub pozostaw to pole puste, dzięki czemu nie będzie terminu wykonania zadania. Możesz pominąć to ustawienie, tak aby umożliwić oddanie zadania w dowolnym czasie.") ?><br/>
        <input type="text" class="form-control" value="<?php echo(htmlspec_utf8($due)); ?>" name="due"></label>
        <label for="timezone">
            <?= _m("Wprowadź poprawną strefę czasową w formacie PHP, np. 'Europe/Warsaw' (wartość domyślna). Jeśli uczysz w wielu strefach czasowych na całym świecie, to 'UTC' lub 'Pacific/Honolulu' będą dobrym wyborem.") ?><br/>
        <select name="timezone" class="form-control">
<?php
            foreach(timezone_identifiers_list() as $tz ) {
                echo('<option value="'.htmlspec_utf8($tz).'" ');
                if ( $tz == $timezone ) echo(' selected="yes" ');
                echo('>'.htmlentities($tz)."</option>\n");
            }
?>
        </select>
        </label>
            <p><?= _m("Kolejne dwa pola określają 'całkowitą karę' za spóźnienie. Należy zdefiniować jednostkę czasu (w sekundach) oraz procentową karę za każdą jednostkę. Kara jest naliczana za każdą pełną lub częściową jednostkę czasu po terminie. Na przykład, aby odjąć 20% za dzień spóźnienia, ustawiamy jednostkę czasu na 86400 (24*60*60), a karę na 0.2.") ?>
            </p>
        <label for="penalty_time"><?= _m("Wprowadź poniżej w sekundach jednostkę czasu dla kary za spóźnienie.") ?><br/>
        <input type="text" class="form-control" value="<?php echo(htmlspec_utf8($time)); ?>" name="penalty_time"></label>
        <label for="penalty_cost"><?= _m("Wprowadź poniżej procentową karę za spóźnienie jako liczbę rzeczywistą pomiędzy 0.0 a 1.0.") ?><br/>
        <input type="text" class="form-control" value="<?php echo(htmlspec_utf8($cost)); ?>" name="penalty_cost"></label>
<?php
    }

    /**
     * Emit the text and form fields to support the done option
     */
    public static function done()
    {
        global $USER;
        if ( ! $USER ) return false;
/*
        return; // Deprecated
        if ( ! $USER->instructor ) return;
        $done = Settings::linkGet('done', '');
?>
        <label for="done">
            This option allows you to control the existance and behavior of a "Done" button for this tool.
            If you leave this blank the tool will assume it is in an iFrame and will not show a Done button.
            If you put a URL here, a Done button will be shown and when pressed the tool will navigate to
            the specified URL.  If you expect to launch this tool in a popup, enter "_close" here and
            the tool will close its window when Done is pressed.<br/>
        <input type="text" class="form-control" value="<?php echo(htmlspec_utf8($done)); ?>" name="done"></label>
<?php
*/
    }

}
