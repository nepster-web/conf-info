<?php

/**
 * Если нужно, запускаем сервер
 * php -S localhost:8000 simple1.php
 * ---------------------------------
 *
 * Пример запроса с помощью CURL
 * curl http://localhost:8080 -d '{"query": "query { echo(message: \"Hello World\") }" }'
 *
 * Пример запроса с помощью браузера
 * http://localhost/simple1.php?query=query{echo(message:"Hello World")}
 */

require(__DIR__ . '/vendor/autoload.php');

use GraphQL\Schema;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;


// ------------------------------------------
$rawInput = file_get_contents('php://input');

if (empty($rawInput)) {
    $query = isset($_GET['query']) ? $_GET['query'] : null;
    $variables = isset($_GET['variables']) ? $_GET['variables'] : null;
} else {
    $input = json_decode($rawInput, true);
    $query = isset($input['query']) ? $input['query'] : null;
    $variables = isset($input['variables']) ? $input['variables'] : null;
}
// ------------------------------------------


// ------------------------------------------
$queryType = new ObjectType([
    'name' => 'Query',
    'fields' => [
        'echo' => [
            'type' => Type::string(),
            'args' => [
                'message' => Type::nonNull(Type::string()),
            ],
            'resolve' => function ($root, $args) {
                return $root['prefix'] . $args['message'];
            }
        ],
    ],
]);
// ------------------------------------------


// ------------------------------------------
$schema = new Schema([
    'query' => $queryType
]);
// ------------------------------------------


// ------------------------------------------
try {
    $rootValue = ['prefix' => 'You said: '];
    $result = GraphQL::executeAndReturnResult($schema, $query, $rootValue, null, $variables);
    $output = $result->toArray();
} catch (\Exception $e) {
    $output = [
        'errors' => [
            [
                'message' => $e->getMessage()
            ]
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($output);
// ------------------------------------------