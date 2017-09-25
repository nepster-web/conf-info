<?php

require(__DIR__ . '/vendor/autoload.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Schema as GraphQLSchema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Class Controller
 */
class Controller
{

    /**
     * @var array
     */
    private $objectTypes = [];

    /**
     *
     */
    public function entryPoint()
    {
        $query = '
            query($first: Int!) { 
                allUsers(first: $first) {
                    nodes {
                        id,
                        name,
                        position,
                    }
                } 
            }';
        $variables = ['first' => 10];


        $query = '
            query($id: Int!) { 
                User(id: $id) {
                    id,
                    name,
                    position,
                } 
            }';
        $variables = ['id' => 7];

        ////////////////////////////////////////////

        $result = GraphQL::executeAndReturnResult(
            $this->schema(),
            $query,
            [],
            null,
            $variables
        );
        $output = $result->toArray();

        echo json_encode($output);
    }





    private function schema()
    {
        $this->registerUsers();

        return new GraphQLSchema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'allUsers' => $this->queryAllUsers(),
                    'User' => $this->queryUser(),
                ],
            ]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => [
                    //'updateUser' => $this->mutationUpdateUser(),
                ],
            ]),
            'types' => [
                $this->objectTypes['UserObjectType'],
            ],
        ]);
    }

    /**
     * Init type of User
     */
    private function registerUsers()
    {
        $this->objectTypes['UserInterface'] = new InterfaceType([
            'name' => 'UserInterface',
            'fields' => function () use (&$interface) {
                return [
                    'id' => Type::nonNull(Type::string()),
                    'name' => Type::nonNull(Type::string()),
                    'position' => Type::nonNull(Type::string()),
                ];
            },
            'resolveType' => function ($obj) {
                return $this->objectTypes['UserObjectType'];
            },
        ]);

        $this->objectTypes['UserObjectType'] = new ObjectType([
            'name' => 'UserObjectType',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'name' => Type::nonNull(Type::string()),
                'position' => Type::nonNull(Type::string()),
            ],
            'interfaces' => [$this->objectTypes['UserInterface']]
        ]);
    }

    /**
     * @return array
     */
    private function queryAllUsers()
    {
        return [
            'type' => new ObjectType([
                'name' => 'AllUsers',
                'fields' => [
                    'nodes' => Type::listOf($this->objectTypes['UserInterface']),
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
    }

    /**
     * @return array
     */
    private function queryUser()
    {
        return [
            'type' => $this->objectTypes['UserInterface'],
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
    }
























    /**
     * Update setting
     * @return array
     */
    private function mutationUpdateSetting()
    {
        return [
            'type' => new ObjectType([
                'name' => 'updateSettingObjectType',
                'fields' => [
                    'Setting' => $this->objectTypes['SettingsObjectType'],
                    'result' => $this->getGraphQLResultObjectType('updateSetting'),
                ],
            ]),
            'args' => [
                'param' => Type::nonNull(Type::string()),
                'type' => Type::string(),
                'value' => Type::string(),
                'description' => Type::string(),
            ],
            'resolve' => (function (array $rootValue, array $args, $context, ResolveInfo $info) {

                echo 3;
                die();
            })
        ];
    }


    /**
     * @param string $prefix
     * @return array
     */
    private static function getGraphQLResultObjectType($prefix = '')
    {
        $prefix = ($prefix) ?: uniqid(true);

        return [
            'type' => (new ObjectType([
                'name' => $prefix . 'ResultObjectType',
                'fields' => [
                    'approval' => Type::boolean(),
                    'errors' => Type::listOf(
                        (new ObjectType([
                            'name' => $prefix . 'ErrorObjectType',
                            'fields' => [
                                'field' => Type::string(),
                                'message' => Type::string(),
                            ],
                        ]))
                    ),
                ]
            ]))
        ];
    }

}


//  header('Content-Type: application/json');
(new Controller())->entryPoint();