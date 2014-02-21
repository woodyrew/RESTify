<?php

include '../restme.inc.php';

$restme = new Restme();

$restme->get('book/serial/:id', 'get');
$restme->get('book/serial', 'get_all');
$restme->post('book/serial', 'add');
$restme->put('book/serial/:id', 'edit');
$restme->delete('book/serial/:id', 'remove');

$restme->response(); // Will be json encoded with appropriate headers
