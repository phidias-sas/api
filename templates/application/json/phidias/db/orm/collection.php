<?php

$records  = $this->data->find()->toArray();
$count    = count($records);
$page     = $this->data->getPage();
$pageSize = $this->data->getLimit();
$total    = isset($this->data->total) ? $this->data->total : (  (0 < $count && $count < $pageSize) ? $count + ($page-1)*$pageSize : $this->data->count()  );

$this->header("X-Phidias-Collection-Page",      $page);
$this->header("X-Phidias-Collection-Page-Size", $pageSize);
$this->header("X-Phidias-Collection-Total",     $total);

$this->header("Access-Control-Expose-Headers", "X-Phidias-Collection-Page, X-Phidias-Collection-Page-Size, X-Phidias-Collection-Total");

echo json_encode($records, JSON_PRETTY_PRINT);
