<pre><?php
$output           = new stdClass;
$output->elements = $this->data->toArray();
$output->total    = count($output->elements);

print_r($output);
