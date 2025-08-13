# 🚀 Installation

To install the User Management Bundle, follow these steps:

1. **Install the bundle via Composer:**
   ```bash
   composer require itechworld/user-management-bundle
   ```
2. **Enable the bundle in your Symfony application:**
   Add the following line to your `config/bundles.php` file:
   ```php
   return [
       // ...
       ItechWorld\UserManagementBundle\ItechWorldUserManagementBundle::class => ['all' => true],
   ];
   ```
3. **Configure the bundle:**
   Add `CORS_ALLOW_ORIGIN` to your `.env.local` file if you want to allow CORS requests:
   ```dotenv
   CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
   ```
   Add Firewalls and Access Control to your `config/packages/security.yaml` file:
   ```yaml
    security:    
        providers:
            itech_world_user_management_provider:
                entity:
                    class: ItechWorld\UserManagementBundle\Entity\User
                    property: username
        
        firewalls:  
            api_login:
                pattern: ^/api/login
                stateless: true
                provider: itech_world_user_management_provider
                json_login:
                    check_path: /api/login
                    success_handler: lexik_jwt_authentication.handler.authentication_success
                    failure_handler: lexik_jwt_authentication.handler.authentication_failure
            api:
                pattern: ^/api
                stateless: true
                provider: itech_world_user_management_provider
                jwt: ~
        
        access_control:
            - { path: ^/api/login, roles: PUBLIC_ACCESS }
            - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
   ```
   Add routes to your `config/routes.yaml` file:
   ```yaml
   itech_world_user_management:
       resource: '@ItechWorldUserManagementBundle/config/routes.yaml'
       type: yaml
   ```
4. **Generate JWT keys:**
   Run the following command to generate the JWT keys:
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```
5. **Create the database schema:**
   Run the following command to create the database schema:
   ```bash
   php bin/console doctrine:schema:update --force
   ```
6. **Create an initial user:**
   You can create an initial user using the following command:
   ```bash
   php bin/console app:create-user
   ```
