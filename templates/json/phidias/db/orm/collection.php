<?php

$records  = $data->find()->toArray();
$count    = count($records);
$page     = $data->getPage();
$pageSize = $data->getLimit();
$total    = isset($data->total) ? $data->total : (  (0 < $count && $count < $pageSize) ? $count + ($page-1)*$pageSize : $data->count()  );

$response->header("X-Phidias-Collection-Page",      $page);
$response->header("X-Phidias-Collection-Page-Size", $pageSize);
$response->header("X-Phidias-Collection-Total",     $total);

echo json_encode($records, JSON_PRETTY_PRINT);
