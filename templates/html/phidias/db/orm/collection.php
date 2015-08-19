<pre><?php

$output                           = new stdClass;

$output->records                  = $data->find()->toArray();
$count                            = count($output->records);

$output->pagination               = new stdClass;
$output->pagination->page         = $data->getPage();
$output->pagination->pageSize     = $data->getLimit();
$output->pagination->totalRecords = isset($data->total) ? $data->total : ( (0 < $count && $count < $output->pagination->pageSize) ? $count + ($output->pagination->page-1)*$output->pagination->pageSize : $data->count());

print_r($output);
