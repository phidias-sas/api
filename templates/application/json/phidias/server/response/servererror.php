<?php
if ($this->data instanceof Exception) {
    $error = $this->data;
} else {
    $error = $this;
}
?>
{
    "error": <?=json_encode(get_class($error))?>,
    "message": <?=json_encode($error->getMessage(), JSON_PRETTY_PRINT)?>,
    "data": <?=json_encode($error->getData(), JSON_PRETTY_PRINT)?>
}