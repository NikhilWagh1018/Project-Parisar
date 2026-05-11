# Project-Parisar

A comprehensive web application for environmental auditing and sustainability management. Built with PHP, HTML, CSS, and JavaScript.

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Prerequisites](#prerequisites)
3. [Installation Guide](#installation-guide)
   - [Step 1: Install XAMPP](#step-1-install-xampp)
   - [Step 2: Clone the Repository](#step-2-clone-the-repository)
   - [Step 3: Set Up Database](#step-3-set-up-database)
   - [Step 4: Configure Database Connection](#step-4-configure-database-connection)
   - [Step 5: Run the Application](#step-5-run-the-application)
4. [Project Structure](#project-structure)
5. [Features](#features)
6. [Troubleshooting](#troubleshooting)
7. [Contributing](#contributing)
8. [License](#license)

---

## 🎯 Project Overview

**Project-Parisar** is a web-based application designed for segment auditing and environmental management. This project uses:
- **Backend**: PHP
- **Frontend**: HTML, CSS, JavaScript
- **Database**: MySQL
- **Server**: Apache (via XAMPP)

The application helps organizations manage and track their environmental segments and audit processes efficiently.

---

## ✅ Prerequisites

Before you start, make sure you have:
- **Windows/Mac/Linux** operating system
- **Administrator access** to your computer
- **Internet connection** to download XAMPP
- **Git** installed (optional, but recommended)
- **At least 500MB free disk space**

---

## 📥 Installation Guide

### Step 1: Install XAMPP

XAMPP is a free and open-source cross-platform web server solution stack that includes Apache, MySQL, PHP, and Perl.

#### For Windows:

1. **Download XAMPP**
   - Visit: https://www.apachefriends.org/
   - Click on "Download" button
   - Select the latest version (e.g., Windows 7.x or higher)

2. **Install XAMPP**
   - Open the downloaded `.exe` file
   - Click "Next" through the installation wizard
   - **Important**: When asked to select components, make sure these are checked:
     - ✓ Apache
     - ✓ MySQL
     - ✓ PHP
     - ✓ phpMyAdmin
   - Choose installation folder (default: `C:\xampp` is recommended)
   - Complete the installation

3. **Verify Installation**
   - Open XAMPP Control Panel
   - Click "Start" next to Apache
   - Click "Start" next to MySQL
   - Both should show green status indicators
   - Open browser and go to: `http://localhost`
   - You should see the XAMPP dashboard

#### For Mac:

1. **Download XAMPP**
   - Visit: https://www.apachefriends.org/
   - Select macOS version

2. **Install XAMPP**
   - Open the `.dmg` file
   - Drag XAMPP icon to Applications folder
   - Open Applications → XAMPP
   - Launch the manager

3. **Start Services**
   - Click "Start All" or start Apache and MySQL individually
   - Verify at: `http://localhost`

#### For Linux:

1. **Download and Install**
   ```bash
   # Download the installer
   wget https://www.apachefriends.org/xampp-files/X.X.X/xampp-linux-x64-installer.run
   
   # Make it executable
   chmod +x xampp-linux-*-installer.run
   
   # Run the installer
   sudo ./xampp-linux-*-installer.run
   ```

2. **Start Services**
   ```bash
   sudo /opt/lampp/manager-linux-x64.run
   ```

---

### Step 2: Clone the Repository

Now that XAMPP is installed, let's get the Project-Parisar code.

#### Option A: Using Git (Recommended)

1. **Open Command Prompt/Terminal**
   
   Windows:
   - Press `Windows Key + R`
   - Type `cmd` and press Enter
   
   Mac/Linux:
   - Open Terminal application

2. **Navigate to XAMPP htdocs folder**
   
   Windows:
   ```cmd
   cd C:\xampp\htdocs
   ```
   
   Mac:
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs
   ```
   
   Linux:
   ```bash
   cd /opt/lampp/htdocs
   ```

3. **Clone the repository**
   ```bash
   git clone https://github.com/NikhilWagh1018/Project-Parisar.git
   cd Project-Parisar
   ```

#### Option B: Manual Download (If you don't have Git)

1. Visit: https://github.com/NikhilWagh1018/Project-Parisar
2. Click green **"Code"** button
3. Click **"Download ZIP"**
4. Extract the ZIP file to `C:\xampp\htdocs\` (Windows) or equivalent folder
5. Rename the extracted folder to `Project-Parisar`

---

### Step 3: Set Up Database

#### Creating Database using phpMyAdmin:

1. **Open phpMyAdmin**
   - Make sure Apache and MySQL are running in XAMPP
   - Open your browser and go to: `http://localhost/phpmyadmin`
   - You should see the phpMyAdmin dashboard

2. **Create New Database**
   - Click on **"Databases"** tab at the top
   - In the **"Create database"** section, enter the database name: `parisar_db`
   - Select **Collation**: `utf8mb4_unicode_ci`
   - Click **"Create"**

3. **Import Database Structure** (if .sql file exists)
   - Click on the newly created `parisar_db` database
   - Click the **"Import"** tab
   - Click **"Choose File"** and select the `.sql` file from the project folder (usually named `database.sql` or `parisar_db.sql`)
   - Click **"Import"**

4. **Verify Tables Created**
   - On the left side, expand `parisar_db`
   - You should see various tables listed (e.g., users, segments, audits, etc.)

#### Creating Database using SQL Script (Alternative):

1. Open phpMyAdmin at `http://localhost/phpmyadmin`
2. Click on the **"SQL"** tab
3. Copy and paste your SQL database creation script
4. Click **"Go"**

---

### Step 4: Configure Database Connection

Now we need to tell the application how to connect to the database.

1. **Locate Configuration File**
   - Navigate to: `C:\xampp\htdocs\Project-Parisar\` (or your installation path)
   - Look for a file named:
     - `config.php`
     - `database.php`
     - `connection.php`
     - `.env`
     - Or any file with database configuration

2. **Edit Configuration File**
   - Open the file with a text editor (Notepad, VS Code, etc.)
   - Look for database connection details that look like this:
   
   ```php
   <?php
   // Database Configuration
   $servername = "localhost";      // MySQL server address
   $username = "root";             // MySQL username
   $password = "";                 // MySQL password (empty by default)
   $database = "parisar_db";       // Database name we created
   
   // Create connection
   $connection = new mysqli($servername, $username, $password, $database);
   
   // Check connection
   if ($connection->connect_error) {
       die("Connection failed: " . $connection->connect_error);
   }
   ?>
   ```

3. **Verify Settings**
   - **servername**: `localhost` ✓
   - **username**: `root` ✓
   - **password**: Leave empty (default) ✓
   - **database**: `parisar_db` ✓

4. **Save the File**
   - Press `Ctrl + S` (or `Cmd + S` on Mac)
   - Close the editor

---

### Step 5: Run the Application

Now let's start using the application!

#### Method 1: Using XAMPP Control Panel (Easiest)

1. **Ensure Services are Running**
   - Open XAMPP Control Panel
   - Verify Apache and MySQL show green status
   - If not, click "Start" next to each

2. **Open Application in Browser**
   - Open your web browser (Chrome, Firefox, Safari, etc.)
   - Go to: `http://localhost/Project-Parisar/`
   - You should see the application homepage

#### Method 2: Using Localhost Path

1. Go to: `http://localhost/Project-Parisar/index.php`
   - Or if there's a specific entry point file, replace `index.php` with that filename

2. If you see the application interface, congratulations! ✓

#### Method 3: Using Specific Port (if configured differently)

1. Go to: `http://localhost:8080/Project-Parisar/`
   - Replace `8080` with the port number if Apache is configured on a different port

---

## 📁 Project Structure

```
Project-Parisar/
├── segment-audit/              # Main application module
│   ├── css/                    # Stylesheet files
│   │   └── *.css
│   ├── js/                     # JavaScript files
│   │   └── *.js
│   ├── includes/               # PHP include files
│   │   ├── config.php
│   │   ├── database.php
│   │   └── functions.php
│   ├── views/                  # HTML templates
│   │   └── *.html / *.php
│   ├── controllers/            # Business logic
│   │   └── *.php
│   ├── models/                 # Data models
│   │   └── *.php
│   └── index.php               # Entry point
├── config.php                  # Main configuration
├── database.sql                # Database schema
├── README.md                   # This file
└── LICENSE                     # License information

```

---

## ✨ Features

- **Segment Management**: Create, read, update, and delete environmental segments
- **Audit Tracking**: Monitor and track audit processes
- **User Management**: Manage user accounts and permissions
- **Reporting**: Generate audit and segment reports
- **Dashboard**: Visual overview of audit status
- **Database Integration**: Secure data storage and retrieval

---

## 🐛 Troubleshooting

### Issue 1: "Cannot connect to database"

**Solution:**
1. Open XAMPP Control Panel
2. Verify MySQL is running (green indicator)
3. Click "Admin" next to MySQL to open phpMyAdmin
4. Confirm database `parisar_db` exists
5. Check `config.php` has correct settings:
   - servername: `localhost`
   - username: `root`
   - password: `` (empty)

### Issue 2: "Apache won't start"

**Solution:**
1. Check if port 80 is already in use
2. In XAMPP Control Panel, click "Config" next to Apache
3. Change port to `8080` if needed
4. Try starting Apache again

### Issue 3: "Page not found - 404 error"

**Solution:**
1. Verify folder is in correct location: `C:\xampp\htdocs\Project-Parisar\`
2. Check file permissions (Windows: Right-click → Properties → Security)
3. Clear browser cache (Ctrl + Shift + Delete)
4. Restart Apache and MySQL

### Issue 4: "Blank white page or no content showing"

**Solution:**
1. Check browser console for errors (F12 → Console tab)
2. Enable PHP error reporting in `config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
3. Check `error_log` file in XAMPP folder
4. Verify all required PHP extensions are loaded

### Issue 5: "Table doesn't exist or SQL errors"

**Solution:**
1. Open phpMyAdmin
2. Select `parisar_db` database
3. Go to "Import" tab
4. Re-import the `.sql` file from the project
5. Verify all tables appear in the left panel

### Issue 6: "CSS and JavaScript not loading (unstyled page)"

**Solution:**
1. Check browser developer tools (F12)
2. Look for 404 errors on CSS/JS files
3. Verify file paths in HTML are correct
4. Clear browser cache and reload (Ctrl + Shift + R)
5. Check that Apache is serving static files correctly

---

## 💡 Quick Tips for Beginners

### Stop/Start Services Properly
- Always use XAMPP Control Panel to start/stop services
- Don't force close applications
- Properly shut down before restarting computer

### Best Practices
- Keep backups of your database
- Use meaningful filenames and folder structures
- Comment your code for future reference
- Test features before deploying to production

### Database Backup
```bash
# Backup database
mysqldump -u root -p parisar_db > parisar_db_backup.sql

# Restore database
mysql -u root -p parisar_db < parisar_db_backup.sql
```

---

## 📚 Useful Resources

- **XAMPP Documentation**: https://www.apachefriends.org/docs.html
- **PHP Documentation**: https://www.php.net/docs.php
- **MySQL Documentation**: https://dev.mysql.com/doc/
- **phpMyAdmin Guide**: https://docs.phpmyadmin.net/
- **HTML/CSS/JavaScript**: https://www.w3schools.com

---

## 🤝 Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📝 License

This project is open source. Please check the LICENSE file for details.

---

## 👨‍💻 Author

**Project-Parisar** created by [NikhilWagh1018](https://github.com/NikhilWagh1018)

---

## 📧 Support

If you encounter any issues:
1. Check the [Troubleshooting](#troubleshooting) section
2. Open an Issue on GitHub
3. Check existing issues for similar problems
4. Include error messages and steps to reproduce

---

## 🎓 Learning Path

If you're new to web development, follow this learning path:

1. **Week 1**: Learn HTML basics
2. **Week 2**: Learn CSS styling
3. **Week 3**: Learn JavaScript fundamentals
4. **Week 4**: Learn PHP basics
5. **Week 5**: Learn MySQL database
6. **Week 6**: Combine all skills in Project-Parisar

---

**Happy Coding! 🚀**

Last Updated: May 11, 2026
