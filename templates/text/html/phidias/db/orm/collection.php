<?php

$output                           = new stdClass;

$output->records                  = $this->data->find()->toArray();
$count                            = count($output->records);

$output->pagination               = new stdClass;
$output->pagination->page         = $this->data->getPage();
$output->pagination->pageSize     = $this->data->getLimit();
$output->pagination->totalRecords = isset($this->data->total) ? $this->data->total : ( (0 < $count && $count < $output->pagination->pageSize) ? $count + ($output->pagination->page-1)*$output->pagination->pageSize : $this->data->count());

dump($output);
