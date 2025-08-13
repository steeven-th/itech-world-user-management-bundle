# Dynamic Permissions System

## Overview

The permissions system provides granular access rights management based on three main concepts:
- **Resources**: Business entities of the system (USERS, GROUPS, PERMISSIONS)
- **Actions**: Possible operations (CREATE, READ, UPDATE, DELETE, MANAGE)
- **Users**: People with specific permissions

## Architecture

### Entities
- `Resource`: Represents a system resource (e.g.: USERS, GROUPS)
- `Permission`: Combines a resource and an action (e.g.: USERS_CREATE)
- `User`: ManyToMany relationship with permissions

### API Endpoints
- `/api/resources` - Resource management (ROLE_ADMIN only)
- `/api/permissions` - Permission management (ROLE_ADMIN only)
- `/api/users/{id}/permissions` - User permission management
- `/api/permissions/stats` - Permission statistics
- `/api/permissions/matrix` - Permission matrix view

## Security

- Access restricted to `ROLE_ADMIN` users
- Server-side validation of all operations
- Native CSRF protection
- Audit trail of modifications (timestamps)

## CLI Commands

```bash
# Initialize basic permissions
bin/console app:init-permissions

# Create an admin user
bin/console app:create-user
```
