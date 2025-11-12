# Mjengo Challenge System Documentation

## Overview

Mjengo Challenge is a web-based platform designed to facilitate construction savings challenges in Tanzania. The system allows users to participate in group savings programs where they make regular payments to collectively save for construction materials. The platform supports multiple languages (English and Swahili), user management, challenge administration, payment tracking, and material purchasing.

## System Architecture

### Technology Stack
- **Backend**: PHP 7.x+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Server**: Apache/XAMPP
- **Additional Libraries**: Font Awesome, Google Fonts

### Core Components
1. **Database Layer** (`core/db.php`): PDO-based MySQL connection
2. **Configuration** (`config.php`): Site-wide settings and helper functions
3. **Language System** (`core/language.php`): Multi-language support
4. **Authentication** (`core/login.php`, `core/register.php`): User login/registration
5. **API Layer** (`api.php`): RESTful API for external integrations

## Database Schema

The system uses the following main tables:

### Users Table
- Stores user information including username, email, phone, NIDA ID, and role (admin/user)

### Challenges Table
- Contains challenge details: name, description, daily amount, participant limits, dates

### Participants Table
- Links users to challenges with queue positions and status

### Payments Table
- Records all payments made by participants

### Materials Table
- Catalog of available construction materials with pricing

### Feedback Table
- User feedback and ratings with admin replies

### Additional Tables
- `daily_winners`: Tracks daily winners
- `direct_purchases`: Direct material purchases
- `lipa_kidogo_payments`: Installment payment plans

## System Flow

### 1. User Registration and Authentication

**Flow:**
1. User visits `index.php` (homepage)
2. Clicks "Login" or "Register"
3. For registration: Fills form with personal details, NIDA ID
4. System validates input and creates account
5. For login: Enters credentials, system authenticates via `core/login.php`
6. Successful login sets session variables

**Key Files:**
- `core/register.php`: Handles registration logic
- `core/login.php`: Handles authentication
- `logout.php`: Clears session

### 2. Challenge Participation Flow

**User Perspective:**
1. Browse available challenges on `challenges.php`
2. View challenge details on `challenge_details.php`
3. Join challenge via `join_challenge.php`
4. Make daily payments through `process_payment.php`
5. Track progress on `dashboard.php`

**Admin Perspective:**
1. Create new challenges in `admin.php`
2. Manage participants via `challenge_members.php`
3. Generate reports with `generate_payments_report.php`

**Payment Flow:**
1. User initiates payment on dashboard
2. System redirects to `payment_gateway.php`
3. Payment processed via external gateway
4. Success/failure handled by `payment_success.php`
5. Payment status updated in database

### 3. Material Purchasing Flow

**Direct Purchase:**
1. User browses materials on homepage or dedicated section
2. Selects material and quantity
3. Proceeds to `direct_purchase.php`
4. Fills delivery details
5. Completes payment via `process_direct_purchase.php`

**Lipa Kidogo (Installments):**
1. User selects material for installment plan
2. System creates payment schedule in `lipa_kidogo.php`
3. User makes regular payments via `process_lipa_kidogo.php`
4. System tracks overdue payments and sends reminders

### 4. Admin Management Flow

**Dashboard (`admin.php`):**
- View statistics (users, challenges, payments, materials)
- Manage challenges (create, edit, delete)
- Manage materials (add, edit, delete)
- User management (view, edit roles)
- Generate reports

**Key Admin Operations:**
- Challenge management: CRUD operations on challenges
- Material management: Add/edit materials with image uploads
- User management: Change roles, view activity
- Feedback management: View and reply to user feedback

### 5. Feedback and Communication Flow

1. Users submit feedback via homepage form
2. Feedback stored in database with ratings
3. Admins view feedback in admin panel
4. Admins can reply to feedback
5. Email notifications sent via `core/email.php`

## API Integration

The system provides a RESTful API (`api.php`) for external integrations:

### Endpoints
- `GET/POST /api.php/challenges` - Challenge management
- `GET/POST /api.php/materials` - Material catalog
- `GET /api.php/users` - User management (admin only)
- `GET/POST /api.php/payments` - Payment operations
- `GET/POST/PUT /api.php/feedback` - Feedback management
- `GET /api.php/stats` - System statistics (admin only)

### Authentication
- Uses Bearer token authentication
- Admin endpoints require admin session

## Language and Localization

The system supports English and Swahili:

- Language files: `languages/en.php`, `languages/sw.php`
- Language switching handled in `config.php`
- Translation function `__()` used throughout templates

## Security Features

- Input sanitization using `sanitize()` function
- Session-based authentication
- Admin role checking with `isAdmin()`
- CSRF protection on forms
- Password hashing with bcrypt

## File Structure

```
mjengo/
├── config.php              # Main configuration
├── index.php               # Homepage
├── admin.php               # Admin dashboard
├── dashboard.php           # User dashboard
├── challenges.php          # Challenge listing
├── api.php                 # REST API
├── core/                   # Core functionality
│   ├── db.php             # Database connection
│   ├── language.php       # Translation system
│   ├── login.php          # Login logic
│   ├── register.php       # Registration logic
│   └── email.php          # Email functionality
├── database/               # Database scripts
│   └── mjengo_challenge.sql # Schema
├── languages/              # Translation files
├── assets/                 # Static assets
└── uploads/                # User uploads
```

## Installation and Setup

1. Install XAMPP or similar PHP/MySQL environment
2. Clone repository to htdocs folder
3. Import `database/mjengo_challenge.sql` to MySQL
4. Update database credentials in `core/db.php`
5. Access via `http://localhost/mjengo`
6. Login with admin credentials (username: admin, password: admin123)

## Maintenance and Monitoring

- Regular database backups recommended
- Monitor payment gateway integrations
- Check server logs for errors
- Update dependencies and security patches
- Monitor user feedback for system improvements

## Future Enhancements

- Mobile app development
- Advanced reporting and analytics
- Integration with local payment providers
- Automated payment reminders
- Multi-currency support
- Advanced user dashboard features
