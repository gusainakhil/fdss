# FDSS Login System Documentation

## Overview
The FDSS (Fire Detection & Suppression System) now includes a role-based login system with role-based access control and redirection.

## System Architecture

### Database Structure
User roles and credentials are stored in the `fdss_users` table with the following fields:
- `user_id` - Unique user identifier
- `username` - Unique login username
- `password_hash` - Bcrypt hashed password
- `role` - User role (see below)
- `status` - Active/Inactive status

### User Roles

1. **SUPER_ADMIN** - Full system access
   - Redirects to: `/admin/index.php`
   - Can manage users, settings, and view reports

2. **ADMIN** - Administrative access
   - Redirects to: `/admin/index.php`
   - Can manage users and view reports

3. **ORG_ADMIN** - Organization administrator
   - Redirects to: Main dashboard (`/index.php`)
   - Can manage organization-level resources

4. **ORG_USER** - Organization user
   - Redirects to: Main dashboard (`/index.php`)
   - Has limited access to organization resources

5. **AUDITOR** - Inspection auditor
   - Redirects to: Main dashboard (`/index.php`)
   - Can submit inspection reports

### Directory Structure

```
/fdds/
├── config/
│   ├── db.php           # Database connection
│   └── check_auth.php   # Session authentication check
├── admin/
│   ├── index.php        # Admin dashboard
│   ├── users.php        # User management
│   ├── systems.php      # System settings
│   ├── reports.php      # Reports page
│   ├── navbar.php       # Admin navigation bar
│   └── logout.php       # Logout handler
├── login.php            # Login page with role-based redirection
└── [Other app files]
```

## Login Flow

1. User enters username and password on `/login.php`
2. Credentials are validated against `fdss_users` table
3. Password is verified using bcrypt (`password_verify()`)
4. User status is checked (must be "Active")
5. Based on role:
   - **SUPER_ADMIN/ADMIN** → Redirect to `/admin/index.php`
   - **ORG_ADMIN/ORG_USER/AUDITOR** → Redirect to `/index.php`
   - **Invalid Role** → Show error: "Your role is not correct"
6. Session is created with user info

## Error Handling

### Error Messages Displayed:

1. **"Invalid username or password."**
   - User does not exist or password is incorrect

2. **"Your account is inactive."**
   - User status is set to "Inactive"

3. **"Your role is not authorized to access this system."**
   - Role is not in the allowed list

## Testing Credentials

From database:
- **Username:** beatle | **Password:** (hashed) | **Role:** ORG_ADMIN
- **Username:** kings | **Password:** (hashed) | **Role:** ORG_ADMIN
- **Username:** admin | **Password:** (hashed) | **Role:** ADMIN

Note: Password hashes are bcrypt encrypted. Use a password reset or update mechanism to change passwords.

## Session Management

- Session timeout: 30 minutes of inactivity
- Sessions are destroyed on logout
- Session security checks are performed on protected pages
- Session tokens are regenerated on login

## Protected Pages

Include this at the top of any page requiring authentication:

```php
<?php
require_once 'config/check_auth.php';
check_role(['ROLE_NAME']); // Optional: check for specific roles
?>
```

## Admin Features

The admin panel includes:

1. **Dashboard**
   - Total users count
   - Active stations
   - Registered trains
   - Pending inspections

2. **User Management**
   - View all users
   - Add new users
   - Edit user details
   - Deactivate users

3. **System Settings**
   - Configure system-wide settings
   - View database information
   - Manage system parameters

4. **Reports**
   - User activity reports
   - Inspection reports
   - Train & coach reports
   - Inventory reports

## Security Features

1. **Password Hashing:** Bcrypt encryption
2. **Session Security:** Time-based expiration
3. **SQL Injection Prevention:** Prepared statements
4. **XSS Prevention:** HTML escaping with `htmlspecialchars()`
5. **CSRF Protection:** Session-based validation
6. **Role-Based Access Control:** Automatic redirection based on role

## Adding New Users

1. Go to `/admin/users.php`
2. Click "Add New User"
3. Fill in user details
4. Select appropriate role
5. Click "Add User"

User will receive temporary password (to be changed on first login - future enhancement).

## Password Reset

To reset a user's password in the database:

```php
$new_password = password_hash('newpassword', PASSWORD_BCRYPT);
$query = "UPDATE fdss_users SET password_hash = ? WHERE user_id = ?";
```

## Future Enhancements

1. Password reset functionality
2. Email verification
3. Two-factor authentication
4. Login attempt throttling
5. Activity logging
6. Password expiration policies
7. User profile pages

## Support

For issues or questions, contact the system administrator.
