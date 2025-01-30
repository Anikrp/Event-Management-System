# Event Management System

A streamlined web-based event management system built with PHP and MySQL. This system enables users to efficiently create and manage events, handle attendee registrations, and generate event reports.

## 🎯 Objective

To provide a simple yet effective event management solution that focuses on core functionalities:
- User authentication and security
- Event creation and management
- Attendee registration with capacity control
- Event dashboard with advanced filtering
- Report generation capabilities

## 🚀 Core Features

- **User Authentication**
  - Secure login and registration system
  - Password hashing for security
  - Session-based authentication
  
- **Event Management**
  - Create new events with detailed information
  - View event listings and details
  - Update existing event information
  - Delete unwanted events
  - Event details include:
    - Event name
    - Description
    - Date and time
    - Location
    - Maximum capacity
    
- **Attendee Management**
  - Simple registration form
  - Automatic capacity tracking
  - Registration validation
  - Prevents overbooking
  
- **Dashboard Features**
  - Paginated event display
  - Sort events by various criteria
  - Filter events by:
    - Date
    - Status
    - Capacity
  - Real-time capacity tracking
  
- **Reporting System**
  - Generate attendee lists
  - Export reports to CSV format
  - Filter report data
  - Admin-only access to reports

## 🔧 Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- Web browser with JavaScript enabled

## 📥 Quick Setup

1. **Database Configuration**
   ```bash
   # Import database schema
   mysql -u your_username -p your_database_name < database/schema.sql
   ```

2. **Application Setup**
   - Configure database connection in `config/database.php`
   - Ensure proper directory permissions
   - Set up your web server configuration

3. **First Time Access**
   - Register an account
   - Log in to access the dashboard
   - Start creating events

## 📁 System Structure

```
event-management-system/
├── assets/           # CSS and JavaScript files
├── config/          # Configuration files
├── database/        # Database schema
├── includes/        # PHP helper functions
└── views/           # PHP view templates
```

## 💡 Key Features Usage

1. **Event Creation**
   - Log in to your account
   - Navigate to "Create Event"
   - Fill in event details
   - Set maximum capacity
   - Save event

2. **Managing Registrations**
   - View registered attendees
   - Track available capacity
   - Export attendee lists

3. **Generating Reports**
   - Access admin dashboard
   - Select event for reporting
   - Choose export format (CSV)
   - Download report

## 🔒 Security Measures

- Secure password hashing
- SQL injection prevention
- Input validation
- Session security
- CSRF protection

## 🐛 Common Issues

1. **Registration Issues**
   - Check event capacity
   - Verify registration deadlines
   - Ensure all required fields are filled

2. **Database Connection**
   - Verify database credentials
   - Check MySQL service status

## 📝 License

MIT License

## 📞 Support

For technical support or queries, please create an issue in the repository.
