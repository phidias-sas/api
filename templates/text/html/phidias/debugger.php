<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<?php
use Phidias\Utilities\Debugger as Debug;

function printMessage($message, $depth = 0, $parent = null)
{
    $message->timer = $message->timestamp - Debug::getInitialTimestamp();
    $messageId      = "debug_message_".$depth.preg_replace('/[^0-9]/','',$message->timer).rand(1, 999999);
    $parentClass    = $parent !== null ? "son_of_".$parent : '';

?>

    <tr id="<?=$messageId?>" class="debugger_record debugger_type_<?=$message->type?> debugger_depth_<?=$depth?> <?=$parentClass?>">
        <td class="debugger_timestamp"><?= number_format($message->timer, 3) ?></td>
        <td class="debugger_message" style="padding-left: <?=$depth*20 + 10?>px">
            <span class="message"><?php if ($message->messages): ?><span class="handle">&#x25BC;</span><?php endif; ?><?=trim($message->text)?></span>
            <span class="file"><?=$message->file?>  Line: <?=$message->line?></span>
        </td>

        <td class="debugger_duration"><?php if ($message->duration): ?><?= number_format($message->duration*1000, 2) ?><?php endif; ?></td>
        <td class="debugger_memory"><?= number_format($message->memory/1048576, 2) ?></td>
    </tr>

<?php

    foreach ($message->messages as $submessage) {
        printMessage($submessage, $depth+1, $messageId);
    }

}
?>

<style type="text/css">
.phidias_debugger {
  margin-top: 60px;
  border-top: 1px dashed #999;
  background: #fff;
  font-family: Courier, Terminal, sans-serif;
  font-size: 0.9em;
}
.phidias_debugger a {
  text-decoration: none;
}
.phidias_debugger table.debugger_summary {
  margin: 10px;
}
.phidias_debugger table.debugger_summary td {
  padding-right: 20px;
}
.phidias_debugger table.debugger_summary thead {
  font-size: 11px;
}
.phidias_debugger ul.debugger_controls {
  list-style: none;
  margin: 15px;
  padding: 0;
}
.phidias_debugger ul.debugger_controls li {
  display: inline-block;
}
.phidias_debugger ul.debugger_controls li a {
  display: block;
  padding: 4px 10px;
  border: 1px solid #ddd;
  border-radius: 4px;

  font-weight: bold;
}

.phidias_debugger ul.debugger_controls li a.debugger_type_resource {
  color: #98dd00;
}

.phidias_debugger ul.debugger_controls li a.debugger_type_SQL {
  color: #fba01c;
}

.phidias_debugger ul.debugger_controls li a.debugger_type_include {
  color: #990000;
}

.phidias_debugger ul.debugger_controls li a:hover {
  color: #333;
}
.phidias_debugger ul.debugger_controls li.active a {
  font-weight: bold;
  color: #fba01c;
}
.phidias_debugger table.debugger_messages {
  width: 100%;
}
.phidias_debugger table.debugger_messages tr.debugger_record {
  cursor: pointer;
}
.phidias_debugger table.debugger_messages tr.debugger_record:hover {
  background: #eee;
}
.phidias_debugger table.debugger_messages thead td {
  font-size: 11px;
  color: #666;
  vertical-align: bottom;
  white-space: normal !important;
}
.phidias_debugger table.debugger_messages tbody td {
  padding-bottom: 5px;
  border-top: 1px solid #ddd;
}
.phidias_debugger table.debugger_messages td {
  padding: 3px;
  color: #222;
  vertical-align: top;
}
.phidias_debugger table.debugger_messages td.debugger_timestamp {
  color: #333;
  padding-right: 15px;
  text-align: right;
}
.phidias_debugger table.debugger_messages td.debugger_message {
  border-left: 6px solid #00b6f0;
  width: 100%;
  padding-left: 10px;
}
.phidias_debugger table.debugger_messages td.debugger_message .handle {
  font-size: 10px;
  color: #333;
}
.phidias_debugger table.debugger_messages td.debugger_message .message {
  display: block;
}
.phidias_debugger table.debugger_messages td.debugger_message .file {
  font-size: 11px;
  color: #00b6f0;
}
.phidias_debugger table.debugger_messages td.debugger_duration {
  white-space: nowrap;
  text-align: right;
  padding-right: 10px;
}
.phidias_debugger table.debugger_messages td.debugger_memory {
  white-space: nowrap;
  background: #eee !important;
  text-align: right;
}
.phidias_debugger table.debugger_messages tr.debugger_type_resource td.debugger_message {
  border-left-color: #98dd00;
}
.phidias_debugger table.debugger_messages tr.debugger_type_resource td.debugger_message .file {
  color: green;
}
.phidias_debugger table.debugger_messages tr.debugger_type_SQL td {
  background: #ff8;
}
.phidias_debugger table.debugger_messages tr.debugger_type_SQL td.debugger_message {
  border-left-color: #fba01c;
}
.phidias_debugger table.debugger_messages tr.debugger_type_SQL td.debugger_message .message {
  white-space: pre-wrap;
}

.phidias_debugger table.debugger_messages tr.debugger_type_SQL td.debugger_message .file {
  color: red;
}
.phidias_debugger table.debugger_messages tr.debugger_type_include td.debugger_message {
  border-left-color: #990000;
}
.phidias_debugger table.debugger_messages tr.debugger_type_include td.debugger_message .file {
  color: #777;
}
.phidias_debugger table.debugger_messages tr.debugger_type_error td.debugger_message {
  color: red;
  border-left-color: red;
}
.phidias_debugger table.debugger_messages tr.debugger_type_error td.debugger_message .file {
  color: red;
}

</style>

<script type="text/javascript">

$( function () {

    $('.debugger_controls a').on('click', function () {

        if ($(this).parent().hasClass('active')) {
            $('.debugger_record').show();
        } else {
            $('.debugger_record').hide();
            $('.'+$(this).attr('class')).show();
        }

        $(this).parent().toggleClass('active');

        return false;
    });

    $('tr.debugger_record').on('click', function () {
        $('.son_of_'+$(this).attr('id')).toggle();
        $(this).toggleClass('open');

        if ($(this).hasClass('open')) {
            $(this).find('.handle').html('&#x25BC;');
        } else {
            $(this).find('.handle').html('&#x25B6;');
        }

        return false;
    });

    $('tr.debugger_record').hide();
    $('tr.debugger_depth_0').show();
    $('tr.debugger_record .handle').html('&#x25B6;');

} );
</script>

<div class="phidias_debugger">

    <table class="debugger_summary">
        <thead>
            <tr>
                <td>Execution time</td>
                <td>Peak memory</td>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td><?= number_format($this->totalTime*1000,2) ?> ms.</td>
                <td><?= number_format($this->peakMemory/1048576, 2) ?> Mb.</td>
            </tr>
        </tbody>
    </table>

    <ul class="debugger_controls">
        <li class="debugger_SQL"><a href="#" class="debugger_type_resource">Resources</a></li>
        <li class="debugger_SQL"><a href="#" class="debugger_type_include">Includes</a></li>
        <li class="debugger_SQL"><a href="#" class="debugger_type_SQL">Queries</a></li>
    </ul>

    <table class="debugger_messages">
        <thead>
            <tr>
                <td>timer</td>
                <td></td>
                <td class="debugger_duration">duration [ms.]</td>
                <td class="debugger_memory">memory [Mb.]</td>
            </tr>
        </thead>

        <tbody>
            <?php
            foreach ($this->messages as $message) {
                printMessage($message);
            }
            ?>
        </tbody>
    </table>

    <table class="debugger_summary">
        <thead>
            <tr>
                <td>Execution time</td>
                <td>Peak memory</td>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td><?= number_format($this->totalTime*1000,2) ?> ms.</td>
                <td><?= number_format($this->peakMemory/1048576, 2) ?> Mb.</td>
            </tr>
        </tbody>
    </table>
</div>
