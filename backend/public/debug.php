<?php
header('Content-Type: application/json');
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set',
    'raw_input' => file_get_contents('php://input'),
    'post' => $_POST,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
]);
