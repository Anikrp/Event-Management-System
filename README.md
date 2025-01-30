# Event Management System

A comprehensive web-based event management system built with PHP and MySQL. This system allows organizations to efficiently manage events, handle attendee registrations, and generate insightful reports.

## 🚀 Features

- **User Management**
  - Secure authentication (Login/Registration)
  - User profile management
  - Role-based access control
- **Event Operations**
  - Create, view, update, and delete events
  - Event categorization and tagging
  - Rich text event descriptions
- **Attendee Management**
  - Easy registration process
  - Attendee tracking
  - Email notifications
- **Dashboard & Analytics**
  - Intuitive dashboard interface
  - Advanced filtering and sorting
  - Pagination for better performance
  - Comprehensive event reports
  - CSV export functionality

## 🔧 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- Web browser with JavaScript enabled
- Composer (for dependency management)
- XAMPP/WAMP/MAMP (recommended for local development)

## 📥 Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/event-management-system.git
   cd event-management-system
   ```

2. **Database Setup**
   - Create a new MySQL database
   - Import the database schema:
     ```bash
     mysql -u your_username -p your_database_name < database/schema.sql
     ```
   - Copy `config/database.example.php` to `config/database.php`
   - Update database credentials in `config/database.php`

3. **Configure Environment**
   - Ensure the `uploads` directory has write permissions:
     ```bash
     chmod 755 uploads/
     ```
   - Configure your web server to point to the project directory

4. **Install Dependencies**
   ```bash
   composer install
   ```

## 🚦 Usage

1. **Access the Application**
   - Open your web browser and navigate to the project URL
   - For local development: `http://localhost/event-management-system`

2. **Initial Setup**
   - Register a new admin account
   - Log in using your credentials
   - Start creating and managing events

3. **Key Operations**
   - Create new events via the dashboard
   - Manage attendee registrations
   - Generate and export reports
   - Update user profile and settings

## 📁 Project Structure

```
event-management-system/
├── api/              # API endpoints
├── assets/           # Static resources (CSS, JS, images)
│   ├── css/         # Stylesheet files
│   ├── js/          # JavaScript files
│   └── images/      # Image assets
├── config/          # Configuration files
├── database/        # Database schema and migrations
├── includes/        # PHP classes and helper functions
├── uploads/         # User uploaded files
└── views/           # PHP view templates
```

## 🔒 Security Features

- Secure password hashing with bcrypt
- Protection against SQL injection using prepared statements
- XSS prevention through input sanitization
- CSRF token validation
- Secure session management
- Rate limiting for API endpoints

## 🐛 Troubleshooting

Common issues and solutions:

1. **Database Connection Failed**
   - Verify database credentials in `config/database.php`
   - Ensure MySQL service is running

2. **Upload Errors**
   - Check `uploads` directory permissions
   - Verify PHP file upload settings in `php.ini`

3. **Blank Page**
   - Enable PHP error reporting in development
   - Check PHP error logs

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📧 Support

For support and queries, please create an issue in the GitHub repository or contact the development team.
