<?php

/**
 * Если нужно, запускаем сервер
 * php -S localhost:8000 simple2.php
 *
 * Пример запросов с помощью браузера
 * GET http://localhost:8000/simple2.php?query=query($first: Int!){allUsers(first: $first){nodes{id,name,position}}}&variables={"first": "7"}
 * GET http://localhost:8000/simple2.php?query=query($id: Int!){User(id: $id){id,name,position}}&variables={"id": "7"}
 *
 * POST http://localhost:8000/simple2.php
 * query=mutation ($id: Int!, $position: String!) {
 *   updateUser(id: $id, position: $position) {
 *     User {
 *       id,
 *       name,
 *       position
 *     }
 *     result {
 *       approval,
 *       errors {
 *       field,
 *         message
 *       }
 *     }
 *   }
 * }&variables={"id": "17", "position": "new Position"}
 */

require(__DIR__ . '/vendor/autoload.php');

use GraphQL\Schema;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\InterfaceType;


// ------------------------------------------
if (empty($_POST)) {
    $query = isset($_GET['query']) ? $_GET['query'] : null;
    $variables = isset($_GET['variables']) ? json_decode($_GET['variables'], true) : null;
} else {
    $query = isset($_POST['query']) ? $_POST['query'] : null;
    $variables = isset($_POST['variables']) ? json_decode($_POST['variables'], true) : null;
}
// ------------------------------------------


// ------------------------------------------
$userInterfaceType = new InterfaceType([
    'name' => 'UserInterface',
    'fields' => function () use (&$interface) {
        return [
            'id' => Type::nonNull(Type::string()),
            'name' => Type::nonNull(Type::string()),
            'position' => Type::nonNull(Type::string()),
        ];
    },
    'resolveType' => function ($obj) use (&$userObjectType) {
        return $userObjectType;
    },
]);

$userObjectType = new ObjectType([
    'name' => 'UserObjectType',
    'fields' => [
        'id' => Type::nonNull(Type::string()),
        'name' => Type::nonNull(Type::string()),
        'position' => Type::nonNull(Type::string()),
    ],
    'interfaces' => [$userInterfaceType]
]);
// ------------------------------------------


// ------------------------------------------
$queryAllUsers = [
    'type' => new ObjectType([
        'name' => 'allUsers',
        'fields' => [
            'nodes' => Type::listOf($userInterfaceType),
        ],
    ]),
    'args' => [
        'first' => Type::nonNull(Type::int()),
    ],
    'resolve' => function (array $rootValue, array $args, $context, ResolveInfo $info) {
        $result = [];
        for ($i = 1; $i <= 100; ++$i) {
            $result[] = [
                'id' => $i,
                'name' => 'User-' . $i,
                'position' => 'pos-' . $i,
            ];
        }
        $result = array_slice($result, 0, $args['first'], true);
        return ['nodes' => $result];
    },
];

$queryUser = [
    'type' => $userInterfaceType,
    'args' => [
        'id' => Type::nonNull(Type::int()),
    ],
    'resolve' => function (array $rootValue, array $args, $context, ResolveInfo $info) {
        for ($i = 1; $i <= 100; ++$i) {
            if ((int)$args['id'] === $i) {
                return [
                    'id' => $i,
                    'name' => 'User-' . $i,
                    'position' => 'pos-' . $i,
                ];
            }
        }
        return null;
    },
];

$mutationUpdateUser = [
    'type' => new ObjectType([
        'name' => 'updateUser',
        'fields' => [
            'User' => $userInterfaceType,
            'result' => [
                'type' => (new ObjectType([
                    'name' => 'UpdateUserResultObjectType',
                    'fields' => [
                        'approval' => Type::boolean(),
                        'errors' => Type::listOf(
                            (new ObjectType([
                                'name' => 'UpdateUserErrorObjectType',
                                'fields' => [
                                    'field' => Type::string(),
                                    'message' => Type::string(),
                                ],
                            ]))
                        ),
                    ]
                ]))
            ],
        ],
    ]),
    'args' => [
        'id' => Type::nonNull(Type::int()),
        'position' => Type::nonNull(Type::string()),
    ],
    'resolve' => (function ($entity, array $args, $context, ResolveInfo $info) {
        $fields = $info->getFieldSelection(2);

        // TODO Логика изменений данных пользователя

        return [
            'User' => [
                'id' => $args['id'],
                'name' => 'User-' . $args['id'],
                'position' => $args['position'],
            ],
            'result' => [
                'approval' => true,
                'errors' => null,
            ]
        ];
    })
];
// ------------------------------------------


// ------------------------------------------
$schema = new Schema([
    'query' => new ObjectType([
        'name' => 'Query',
        'fields' => [
            'allUsers' => $queryAllUsers,
            'User' => $queryUser,
        ],
    ]),
    'mutation' => new ObjectType([
        'name' => 'Mutation',
        'fields' => [
            'updateUser' => $mutationUpdateUser,
        ],
    ]),
    'types' => [
        $userObjectType,
    ],
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