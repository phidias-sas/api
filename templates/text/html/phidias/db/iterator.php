<?php
$output           = new stdClass;
$output->elements = $this->data->toArray();
$output->total    = count($output->elements);

dump($output);
