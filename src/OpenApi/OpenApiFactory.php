<?php

namespace ItechWorld\UserManagementBundle\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Parameter;

class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        // Ajouter manuellement tes routes
        $this->addGroupPermissionsRoutes($openApi);
        $this->addGroupsStatsRoutes($openApi);
        $this->addPermissionsStatsRoutes($openApi);
        $this->addUsernameRoutesToUser($openApi);
        $this->addUserPermissionsRoutes($openApi);

        return $openApi;
    }

    private function addGroupPermissionsRoutes(OpenApi $openApi): void
    {
        $paths = $openApi->getPaths();

        // Chercher si il y a déjà des routes Group (comme /api/groups)
        $existingGroupPaths = [];
        foreach ($paths->getPaths() as $path => $pathItem) {
            if (str_starts_with($path, '/api/groups')) {
                $existingGroupPaths[] = $path;
            }
        }

        // Si on trouve des routes Group existantes, on utilise le même tag
        $groupTag = !empty($existingGroupPaths) ? 'Group' : 'Group Permissions';

        // GET /api/groups/{id}/permissions
        $getOperation = new Operation(
            operationId: 'getGroupPermissions',
            tags: [$groupTag],  // Utilise le tag Group au lieu de Group Permissions
            summary: 'Recuperer les permissions d un groupe',
            description: 'Retourne la liste des permissions associees a un groupe specifique',
            parameters: [
                new Parameter(
                    name: 'id',
                    in: 'path',
                    required: true,
                    schema: ['type' => 'integer']
                )
            ],
            responses: [
                '200' => new Response(
                    description: 'Permissions du groupe recuperees avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'group_id' => ['type' => 'integer'],
                                    'group_name' => ['type' => 'string'],
                                    'display_name' => ['type' => 'string'],
                                    'permissions' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                                'code' => ['type' => 'string'],
                                                'resource' => ['type' => 'string'],
                                                'action' => ['type' => 'string'],
                                                'description' => ['type' => 'string']
                                            ]
                                        ]
                                    ],
                                    'is_system' => ['type' => 'boolean']
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse'),
                '404' => new Response(description: 'Groupe non trouve')
            ]
        );

        // PUT /api/groups/{id}/permissions
        $putOperation = new Operation(
            operationId: 'updateGroupPermissions',
            tags: [$groupTag],  // Utilise le tag Group
            summary: 'Mettre a jour les permissions d un groupe',
            description: 'Remplace toutes les permissions d un groupe',
            parameters: [
                new Parameter(
                    name: 'id',
                    in: 'path',
                    required: true,
                    schema: ['type' => 'integer']
                )
            ],
            requestBody: new RequestBody(
                description: 'Liste des IDs des permissions',
                content: new \ArrayObject([
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['permissions'],
                            'properties' => [
                                'permissions' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'integer']
                                ]
                            ]
                        ]
                    ]
                ])
            ),
            responses: [
                '200' => new Response(
                    description: 'Permissions mises a jour avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'message' => ['type' => 'string'],
                                    'group_id' => ['type' => 'integer'],
                                    'permissions_count' => ['type' => 'integer']
                                ]
                            ]
                        ]
                    ])
                ),
                '400' => new Response(description: 'Donnees invalides'),
                '403' => new Response(description: 'Acces refuse'),
                '404' => new Response(description: 'Groupe non trouve')
            ]
        );

        // POST /api/groups/{id}/permissions/{permissionId}
        $postOperation = new Operation(
            operationId: 'addPermissionToGroup',
            tags: [$groupTag],  // Utilise le tag Group
            summary: 'Ajouter une permission a un groupe',
            description: 'Ajoute une permission specifique a un groupe',
            parameters: [
                new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer']),
                new Parameter(name: 'permissionId', in: 'path', required: true, schema: ['type' => 'integer'])
            ],
            responses: [
                '200' => new Response(description: 'Permission ajoutee avec succes'),
                '404' => new Response(description: 'Permission non trouvee'),
                '409' => new Response(description: 'Le groupe a deja cette permission')
            ]
        );

        // DELETE /api/groups/{id}/permissions/{permissionId}
        $deleteOperation = new Operation(
            operationId: 'removePermissionFromGroup',
            tags: [$groupTag],  // Utilise le tag Group
            summary: 'Supprimer une permission d un groupe',
            description: 'Retire une permission specifique d un groupe',
            parameters: [
                new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer']),
                new Parameter(name: 'permissionId', in: 'path', required: true, schema: ['type' => 'integer'])
            ],
            responses: [
                '200' => new Response(description: 'Permission supprimee avec succes'),
                '403' => new Response(description: 'Suppression non autorisee'),
                '404' => new Response(description: 'Permission non trouvee')
            ]
        );

        // POST /api/groups/{id}/permissions/check
        $checkOperation = new Operation(
            operationId: 'checkGroupPermission',
            tags: [$groupTag],  // Utilise le tag Group
            summary: 'Verifier si un groupe a une permission',
            description: 'Verifie si un groupe possede une permission',
            parameters: [
                new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer'])
            ],
            requestBody: new RequestBody(
                description: 'Ressource et action a verifier',
                content: new \ArrayObject([
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['resource', 'action'],
                            'properties' => [
                                'resource' => ['type' => 'string'],
                                'action' => ['type' => 'string']
                            ]
                        ]
                    ]
                ])
            ),
            responses: [
                '200' => new Response(description: 'Verification effectuee avec succes'),
                '400' => new Response(description: 'Donnees invalides')
            ]
        );

        // Ajouter les PathItems
        $pathItem1 = new PathItem(get: $getOperation, put: $putOperation);
        $pathItem2 = new PathItem(post: $postOperation, delete: $deleteOperation);
        $pathItem3 = new PathItem(post: $checkOperation);

        $paths->addPath('/api/groups/{id}/permissions', $pathItem1);
        $paths->addPath('/api/groups/{id}/permissions/{permissionId}', $pathItem2);
        $paths->addPath('/api/groups/{id}/permissions/check', $pathItem3);
    }

    private function addGroupsStatsRoutes(OpenApi $openApi): void
    {
        $paths = $openApi->getPaths();

        // Chercher si il y a déjà des routes Group (comme /api/groups)
        $existingGroupPaths = [];
        foreach ($paths->getPaths() as $path => $pathItem) {
            if (str_starts_with($path, '/api/groups')) {
                $existingGroupPaths[] = $path;
            }
        }

        // Si on trouve des routes Group existantes, on utilise le même tag
        $groupTag = !empty($existingGroupPaths) ? 'Group' : 'Groups Stats';

        // GET /api/groups-stats/stats
        $statsOperation = new Operation(
            operationId: 'getGroupsStats',
            tags: [$groupTag],  // Utilise le tag Group au lieu de Groups Stats
            summary: 'Recuperer les statistiques des groupes',
            description: 'Retourne les statistiques generales des groupes et utilisateurs',
            responses: [
                '200' => new Response(
                    description: 'Statistiques recuperees avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'total_groups' => ['type' => 'integer', 'example' => 5],
                                    'total_users' => ['type' => 'integer', 'example' => 25],
                                    'groups_with_users' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                                'name' => ['type' => 'string'],
                                                'displayName' => ['type' => 'string'],
                                                'description' => ['type' => 'string'],
                                                'isSystem' => ['type' => 'boolean'],
                                                'user_count' => ['type' => 'integer']
                                            ]
                                        ]
                                    ],
                                    'groups_with_permissions' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                                'name' => ['type' => 'string'],
                                                'displayName' => ['type' => 'string'],
                                                'permissions_count' => ['type' => 'integer']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_VIEW_PERMISSIONS requise')
            ]
        );

        // GET /api/groups-stats/matrix
        $matrixOperation = new Operation(
            operationId: 'getGroupsPermissionsMatrix',
            tags: [$groupTag],  // Utilise le tag Group
            summary: 'Recuperer la matrice des permissions des groupes',
            description: 'Retourne une matrice montrant quelles permissions chaque groupe possede',
            responses: [
                '200' => new Response(
                    description: 'Matrice des permissions recuperee avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'permissions' => [
                                        'type' => 'array',
                                        'description' => 'Liste des permissions disponibles',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'code' => ['type' => 'string', 'example' => 'CAN_VIEW_USERS'],
                                                'resource' => ['type' => 'string', 'example' => 'User'],
                                                'action' => ['type' => 'string', 'example' => 'view'],
                                                'description' => [
                                                    'type' => 'string',
                                                    'example' => 'Peut voir les utilisateurs'
                                                ]
                                            ]
                                        ]
                                    ],
                                    'matrix' => [
                                        'type' => 'array',
                                        'description' => 'Matrice groupe/permissions',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'group' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'id' => ['type' => 'integer'],
                                                        'name' => ['type' => 'string'],
                                                        'displayName' => ['type' => 'string'],
                                                        'isSystem' => ['type' => 'boolean'],
                                                        'usersCount' => ['type' => 'integer']
                                                    ]
                                                ],
                                                'permissions' => [
                                                    'type' => 'object',
                                                    'description' => 'Objet avec code permission en cle et boolean en valeur',
                                                    'additionalProperties' => ['type' => 'boolean'],
                                                    'example' => [
                                                        'CAN_VIEW_USERS' => true,
                                                        'CAN_CREATE_USERS' => false,
                                                        'CAN_UPDATE_USERS' => true
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_VIEW_PERMISSIONS requise')
            ]
        );

        // GET /api/groups-stats/users-by-group
        $usersByGroupOperation = new Operation(
            operationId: 'getUsersByGroup',
            tags: [$groupTag],  // Utilise le tag Group
            summary: 'Recuperer les utilisateurs par groupe',
            description: 'Retourne la liste des utilisateurs regroupes par groupe',
            responses: [
                '200' => new Response(
                    description: 'Utilisateurs par groupe recuperes avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'groups' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'group' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'id' => ['type' => 'integer'],
                                                        'name' => ['type' => 'string'],
                                                        'displayName' => ['type' => 'string'],
                                                        'isSystem' => ['type' => 'boolean']
                                                    ]
                                                ],
                                                'users' => [
                                                    'type' => 'array',
                                                    'items' => [
                                                        'type' => 'object',
                                                        'properties' => [
                                                            'id' => ['type' => 'integer'],
                                                            'username' => ['type' => 'string'],
                                                            'firstName' => ['type' => 'string'],
                                                            'lastName' => ['type' => 'string']
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_VIEW_PERMISSIONS requise')
            ]
        );

        // Ajouter les PathItems
        $pathItem1 = new PathItem(get: $statsOperation);
        $pathItem2 = new PathItem(get: $matrixOperation);
        $pathItem3 = new PathItem(get: $usersByGroupOperation);

        $paths->addPath('/api/groups-stats/stats', $pathItem1);
        $paths->addPath('/api/groups-stats/matrix', $pathItem2);
        $paths->addPath('/api/groups-stats/users-by-group', $pathItem3);
    }

    private function addPermissionsStatsRoutes(OpenApi $openApi): void
    {
        $paths = $openApi->getPaths();

        // Chercher si il y a déjà des routes Permission (comme /api/permissions)
        $existingPermissionPaths = [];
        foreach ($paths->getPaths() as $path => $pathItem) {
            if (str_starts_with($path, '/api/permissions')) {
                $existingPermissionPaths[] = $path;
            }
        }

        // Si on trouve des routes Permission existantes, on utilise le même tag
        $permissionTag = !empty($existingPermissionPaths) ? 'Permission' : 'Permissions Stats';

        // GET /api/permissions-stats/stats
        $statsOperation = new Operation(
            operationId: 'getPermissionsStats',
            tags: [$permissionTag],  // Utilise le tag Permission au lieu de Permissions Stats
            summary: 'Recuperer les statistiques des permissions',
            description: 'Retourne les statistiques generales des permissions, ressources et utilisateurs',
            responses: [
                '200' => new Response(
                    description: 'Statistiques des permissions recuperees avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'total_resources' => ['type' => 'integer', 'example' => 8],
                                    'total_permissions' => ['type' => 'integer', 'example' => 32],
                                    'total_users' => ['type' => 'integer', 'example' => 25],
                                    'permissions_by_resource' => [
                                        'type' => 'array',
                                        'description' => 'Permissions regroupees par ressource',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'resource' => ['type' => 'string', 'example' => 'User'],
                                                'description' => [
                                                    'type' => 'string',
                                                    'example' => 'Gestion des utilisateurs'
                                                ],
                                                'permissions_count' => ['type' => 'integer', 'example' => 4],
                                                'permissions' => [
                                                    'type' => 'array',
                                                    'items' => [
                                                        'type' => 'object',
                                                        'properties' => [
                                                            'id' => ['type' => 'integer'],
                                                            'action' => ['type' => 'string', 'example' => 'view'],
                                                            'description' => [
                                                                'type' => 'string',
                                                                'example' => 'Peut voir les utilisateurs'
                                                            ],
                                                            'users_count' => ['type' => 'integer', 'example' => 5]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ],
                                    'top_users' => [
                                        'type' => 'array',
                                        'description' => 'Top 10 des utilisateurs avec le plus de permissions',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                                'username' => ['type' => 'string'],
                                                'firstName' => ['type' => 'string'],
                                                'lastName' => ['type' => 'string'],
                                                'permissions_count' => ['type' => 'integer']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_VIEW_PERMISSIONS requise')
            ]
        );

        // GET /api/permissions-stats/matrix
        $matrixOperation = new Operation(
            operationId: 'getPermissionsMatrix',
            tags: [$permissionTag],  // Utilise le tag Permission
            summary: 'Recuperer la matrice des permissions des utilisateurs',
            description: 'Retourne une matrice montrant quelles permissions chaque utilisateur possede',
            responses: [
                '200' => new Response(
                    description: 'Matrice des permissions utilisateurs recuperee avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'permissions' => [
                                        'type' => 'array',
                                        'description' => 'Liste des permissions disponibles',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'code' => ['type' => 'string', 'example' => 'CAN_VIEW_USERS'],
                                                'resource' => ['type' => 'string', 'example' => 'User'],
                                                'action' => ['type' => 'string', 'example' => 'view'],
                                                'description' => [
                                                    'type' => 'string',
                                                    'example' => 'Peut voir les utilisateurs'
                                                ]
                                            ]
                                        ]
                                    ],
                                    'matrix' => [
                                        'type' => 'array',
                                        'description' => 'Matrice utilisateur/permissions',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'user' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'id' => ['type' => 'integer'],
                                                        'username' => ['type' => 'string'],
                                                        'fullName' => ['type' => 'string', 'example' => 'John Doe'],
                                                        'roles' => [
                                                            'type' => 'array',
                                                            'items' => ['type' => 'string'],
                                                            'example' => ['ROLE_USER', 'ROLE_ADMIN']
                                                        ]
                                                    ]
                                                ],
                                                'permissions' => [
                                                    'type' => 'object',
                                                    'description' => 'Objet avec code permission en cle et boolean en valeur',
                                                    'additionalProperties' => ['type' => 'boolean'],
                                                    'example' => [
                                                        'CAN_VIEW_USERS' => true,
                                                        'CAN_CREATE_USERS' => false,
                                                        'CAN_UPDATE_USERS' => true,
                                                        'CAN_DELETE_USERS' => false
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_VIEW_PERMISSIONS requise')
            ]
        );

        // Ajouter les PathItems
        $pathItem1 = new PathItem(get: $statsOperation);
        $pathItem2 = new PathItem(get: $matrixOperation);

        $paths->addPath('/api/permissions-stats/stats', $pathItem1);
        $paths->addPath('/api/permissions-stats/matrix', $pathItem2);
    }

    private function addUsernameRoutesToUser(OpenApi $openApi): void
    {
        $paths = $openApi->getPaths();

        // GET /api/username/available - Verifier la disponibilite d'un username
        $availableOperation = new Operation(
            operationId: 'checkUsernameAvailable',
            tags: ['User'],  // IMPORTANT : On utilise le tag "User" pour que ça apparaisse sous User
            summary: 'Verifier la disponibilite d un nom d utilisateur',
            description: 'Verifie si un nom d utilisateur est disponible sans exposer de donnees utilisateur',
            parameters: [
                new Parameter(
                    name: 'username',
                    in: 'query',
                    required: true,
                    description: 'Le nom d utilisateur a verifier',
                    schema: ['type' => 'string', 'minLength' => 3, 'example' => 'john_doe']
                )
            ],
            responses: [
                '200' => new Response(
                    description: 'Verification effectuee avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'available' => [
                                        'type' => 'boolean',
                                        'description' => 'Indique si le nom d utilisateur est disponible',
                                        'example' => true
                                    ],
                                    'current' => [
                                        'type' => 'boolean',
                                        'description' => 'Indique si c est le nom d utilisateur actuel (optionnel)',
                                        'example' => false
                                    ],
                                    'error' => [
                                        'type' => 'string',
                                        'description' => 'Message d erreur en cas de probleme (optionnel)',
                                        'example' => 'Le nom d utilisateur doit contenir au moins 3 caracteres'
                                    ]
                                ],
                                'required' => ['available']
                            ]
                        ]
                    ])
                ),
                '400' => new Response(
                    description: 'Donnees invalides',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'available' => ['type' => 'boolean', 'example' => false],
                                    'error' => ['type' => 'string', 'example' => 'Le parametre username est requis']
                                ]
                            ]
                        ]
                    ])
                ),
                '401' => new Response(description: 'Authentification requise - ROLE_USER necessaire')
            ]
        );

        // Ajouter le PathItem
        $pathItem = new PathItem(get: $availableOperation);
        $paths->addPath('/api/username/available', $pathItem);
    }

    private function addUserPermissionsRoutes(OpenApi $openApi): void
    {
        $paths = $openApi->getPaths();

        // Chercher si il y a déjà des routes User (comme /api/users)
        $existingUserPaths = [];
        foreach ($paths->getPaths() as $path => $pathItem) {
            if (str_starts_with($path, '/api/users')) {
                $existingUserPaths[] = $path;
            }
        }

        // Si on trouve des routes User existantes, on utilise le même tag
        $userTag = !empty($existingUserPaths) ? 'User' : 'User Permissions';

        // GET /api/users/{id}/permissions
        $getOperation = new Operation(
            operationId: 'getUserPermissions',
            tags: [$userTag],  // Utilise le tag User
            summary: 'Recuperer les permissions d un utilisateur',
            description: 'Retourne la liste des permissions associees a un utilisateur specifique',
            parameters: [
                new Parameter(
                    name: 'id',
                    in: 'path',
                    required: true,
                    schema: ['type' => 'integer', 'description' => 'ID de l utilisateur']
                )
            ],
            responses: [
                '200' => new Response(
                    description: 'Permissions de l utilisateur recuperees avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'user_id' => ['type' => 'integer', 'example' => 1],
                                    'username' => ['type' => 'string', 'example' => 'john_doe'],
                                    'permissions' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer', 'example' => 1],
                                                'code' => ['type' => 'string', 'example' => 'CAN_VIEW_USERS'],
                                                'resource' => [
                                                    'type' => 'string',
                                                    'example' => 'User',
                                                    'nullable' => true
                                                ],
                                                'action' => ['type' => 'string', 'example' => 'view'],
                                                'description' => [
                                                    'type' => 'string',
                                                    'example' => 'Peut voir les utilisateurs'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_VIEW_USERS requise'),
                '404' => new Response(description: 'Utilisateur non trouve')
            ]
        );

        // PUT /api/users/{id}/permissions
        $putOperation = new Operation(
            operationId: 'updateUserPermissions',
            tags: [$userTag],  // Utilise le tag User
            summary: 'Mettre a jour les permissions d un utilisateur',
            description: 'Remplace toutes les permissions d un utilisateur par une nouvelle liste',
            parameters: [
                new Parameter(
                    name: 'id',
                    in: 'path',
                    required: true,
                    schema: ['type' => 'integer', 'description' => 'ID de l utilisateur']
                )
            ],
            requestBody: new RequestBody(
                description: 'Liste des IDs des permissions a associer a l utilisateur',
                content: new \ArrayObject([
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['permissions'],
                            'properties' => [
                                'permissions' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'integer'],
                                    'example' => [1, 2, 3, 5],
                                    'description' => 'Tableau des IDs des permissions'
                                ]
                            ]
                        ]
                    ]
                ])
            ),
            responses: [
                '200' => new Response(
                    description: 'Permissions mises a jour avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'message' => [
                                        'type' => 'string',
                                        'example' => 'Permissions mises a jour avec succes'
                                    ],
                                    'user_id' => ['type' => 'integer', 'example' => 1],
                                    'permissions_count' => ['type' => 'integer', 'example' => 4]
                                ]
                            ]
                        ]
                    ])
                ),
                '400' => new Response(
                    description: 'Donnees invalides',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'error' => [
                                        'type' => 'string',
                                        'example' => 'Le champ permissions est requis et doit etre un tableau'
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_UPDATE_USERS requise'),
                '404' => new Response(description: 'Utilisateur non trouve')
            ]
        );

        // POST /api/users/{id}/permissions/{permissionId}
        $postOperation = new Operation(
            operationId: 'addPermissionToUser',
            tags: [$userTag],  // Utilise le tag User
            summary: 'Ajouter une permission a un utilisateur',
            description: 'Ajoute une permission specifique a un utilisateur',
            parameters: [
                new Parameter(name: 'id', in: 'path', required: true, schema: [
                    'type' => 'integer',
                    'description' => 'ID de l utilisateur'
                ]),
                new Parameter(name: 'permissionId', in: 'path', required: true, schema: [
                    'type' => 'integer',
                    'description' => 'ID de la permission a ajouter'
                ])
            ],
            responses: [
                '200' => new Response(
                    description: 'Permission ajoutee a l utilisateur avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'message' => ['type' => 'string', 'example' => 'Permission ajoutee avec succes'],
                                    'permission' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => ['type' => 'integer', 'example' => 5],
                                            'code' => ['type' => 'string', 'example' => 'CAN_DELETE_USERS']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '404' => new Response(description: 'Permission ou utilisateur non trouve'),
                '409' => new Response(
                    description: 'Conflit - L utilisateur a deja cette permission',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'error' => [
                                        'type' => 'string',
                                        'example' => 'L utilisateur a deja cette permission'
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_UPDATE_USERS requise')
            ]
        );

        // DELETE /api/users/{id}/permissions/{permissionId}
        $deleteOperation = new Operation(
            operationId: 'removePermissionFromUser',
            tags: [$userTag],  // Utilise le tag User
            summary: 'Supprimer une permission d un utilisateur',
            description: 'Retire une permission specifique d un utilisateur',
            parameters: [
                new Parameter(name: 'id', in: 'path', required: true, schema: [
                    'type' => 'integer',
                    'description' => 'ID de l utilisateur'
                ]),
                new Parameter(name: 'permissionId', in: 'path', required: true, schema: [
                    'type' => 'integer',
                    'description' => 'ID de la permission a supprimer'
                ])
            ],
            responses: [
                '200' => new Response(
                    description: 'Permission supprimee de l utilisateur avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'message' => ['type' => 'string', 'example' => 'Permission supprimee avec succes']
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_UPDATE_USERS requise'),
                '404' => new Response(
                    description: 'Permission non trouvee ou utilisateur n a pas cette permission',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'error' => ['type' => 'string', 'example' => 'Permission non trouvee']
                                ]
                            ]
                        ]
                    ])
                )
            ]
        );

        // POST /api/users/{id}/permissions/check
        $checkOperation = new Operation(
            operationId: 'checkUserPermission',
            tags: [$userTag],  // Utilise le tag User
            summary: 'Verifier si un utilisateur a une permission',
            description: 'Verifie si un utilisateur possede une permission pour une ressource et une action donnees',
            parameters: [
                new Parameter(name: 'id', in: 'path', required: true, schema: [
                    'type' => 'integer',
                    'description' => 'ID de l utilisateur'
                ])
            ],
            requestBody: new RequestBody(
                description: 'Ressource et action a verifier',
                content: new \ArrayObject([
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['resource', 'action'],
                            'properties' => [
                                'resource' => [
                                    'type' => 'string',
                                    'example' => 'User',
                                    'description' => 'Nom de la ressource'
                                ],
                                'action' => [
                                    'type' => 'string',
                                    'example' => 'view',
                                    'description' => 'Action sur la ressource'
                                ]
                            ]
                        ]
                    ]
                ])
            ),
            responses: [
                '200' => new Response(
                    description: 'Verification effectuee avec succes',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'user_id' => ['type' => 'integer', 'example' => 1],
                                    'resource' => ['type' => 'string', 'example' => 'User'],
                                    'action' => ['type' => 'string', 'example' => 'view'],
                                    'has_permission' => ['type' => 'boolean', 'example' => true]
                                ]
                            ]
                        ]
                    ])
                ),
                '400' => new Response(
                    description: 'Donnees invalides',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'error' => [
                                        'type' => 'string',
                                        'example' => 'Les champs resource et action sont requis'
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                '403' => new Response(description: 'Acces refuse - Permission CAN_VIEW_USERS requise'),
                '404' => new Response(description: 'Utilisateur non trouve')
            ]
        );

        // Ajouter les PathItems
        $pathItem1 = new PathItem(get: $getOperation, put: $putOperation);
        $pathItem2 = new PathItem(post: $postOperation, delete: $deleteOperation);
        $pathItem3 = new PathItem(post: $checkOperation);

        $paths->addPath('/api/users/{id}/permissions', $pathItem1);
        $paths->addPath('/api/users/{id}/permissions/{permissionId}', $pathItem2);
        $paths->addPath('/api/users/{id}/permissions/check', $pathItem3);
    }
}
