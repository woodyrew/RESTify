<?php

include '../restme.inc.php';

$restme = new Restme();

$restme->get('example/serial/:id', 'get');
$restme->get('example/serial', 'get_all');
$restme->post('example/serial', 'add');
$restme->put('example/serial/:id', 'edit');
$restme->delete('example/serial/:id', 'remove');

$restme->response(); // Will be json encoded with appropriate headers
