# EduPortal

EduPortal is a modern, web-based learning management system (LMS) designed to connect students, teachers, and administrators in a secure, easy-to-use platform. It allows users to manage classes, subjects, lessons, assignments, progress tracking, and more.

---

## Features

### Project Screenshots


![pic8](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic8.png)
![pic9](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic9.png)
![pic10](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic10.png)


- **Secure Authentication**: User registration and login with hashed passwords and session management.
- **Role-Based Access**: Supports students, teachers, and admin roles with different permissions.
- **Class & Subject Management**: Teachers/admins can create classes and subjects; students can enroll and track their progress.
- **Lesson & Resource Management**: Upload and organize lessons, documents, and resources per subject.
- **Assignments & Quizzes**: Assignments and quizzes with submission and grading support.
- **Progress Tracking**: Students can update and view their progress for each subject and lesson.
- **Calendar View**: Visual calendar for classes, subjects, and assignment due dates.
- **Announcements**: Admins can post announcements visible to all users.
- **Responsive UI**: Clean, mobile-friendly interface using Bootstrap 5.
- **YouTube Integration**: Search and embed YouTube lessons by topic or lecturer.
- **Online Study Library**: Curated list of external study resources and tools.

---

## Folder Structure

```
techStack/
├── frontend/
│   ├── classes.php
│   ├── class_catalog.php
│   ├── enroll.php
│   ├── uneroll.php
│   ├── home.php
│   ├── calendar.php
│   ├── subjects.php
│   ├── subject.php
│   ├── lessons.php
│   ├── lesson_view.php
│   ├── library.php
│   ├── lectures.php
│   ├── profile.php
│   ├── navbar.php
│   ├── logout.php
│   ├── register.php
│   ├── index.php
│   ├── ...
├── includes/
│   ├── db_connect.php
│   ├── db.php
│   ├── database.sql
│   ├── header.php
│   └── ...
├── assets/
│   ├── style.css
│   ├── script.js
│   └── images/
└── README.md
```

---

## Getting Started

### Prerequisites

- PHP 8.x
- MySQL/MariaDB
- Apache/Nginx (XAMPP recommended for local development)
- Composer (optional, for future package management)

### Installation

1. **Clone or Download the Repository**
   ```
   git clone https://github.com/yourusername/eduportal.git
   ```

2. **Import the Database**
   - Open `phpMyAdmin` or use the MySQL CLI.
   - Import the SQL file:
     ```
     source includes/database.sql
     ```
   - This will create the `eduportal` database and all required tables with sample data.

3. **Configure Database Connection**
   - Edit `includes/db_connect.php` and/or `includes/db.php` if your MySQL credentials differ from the default (`root`/no password).

4. **Set Up Web Server**
   - Place the project folder in your web server's root directory (e.g., `htdocs` for XAMPP).
   - Access the app at `http://localhost/techStack/index.php`.

5. **Register or Login**
   - Use the registration page to create a new student account, or log in with the sample admin/teacher/student accounts provided in the SQL.

---

## Default Accounts (Sample Data)

| Role    | Username   | Email                  | Password   |
|---------|------------|------------------------|------------|
| Admin   | admin      | admin@eduportal.com    | password   |
| Teacher | teacher1   | teacher1@eduportal.com | password   |
| Student | student1   | student1@eduportal.com | password   |

> **Note:** All sample passwords are `password` (hashed in the database).

---

## Customization

- **Add More Classes/Subjects:** Use the admin or teacher account to create new classes and subjects.
- **Change Branding:** Edit `assets/style.css` and `includes/header.php` for colors, logos, and layout.
- **Add More Features:** Extend with more modules (e.g., forums, messaging) as needed.

---

## Security Notes

- All user input is validated and sanitized.
- Passwords are hashed using PHP's `password_hash`.
- CSRF protection is implemented on all forms.
- Sessions are used for authentication and role management.

---

## Contributing

Pull requests are welcome! Please open an issue first to discuss major changes.

---

## License

This project is open-source and available under the [MIT License](LICENSE).

---

## Credits

- [Bootstrap 5](https://getbootstrap.com/)
- [Font Awesome](https://fontawesome.com/)
- [Google Fonts](https://fonts.google.com/)
- [YouTube Data API](https://developers.google.com/youtube/v3)
- All contributors and open-source libraries used.

---

## Screenshots

![EduPortal Dashboard](assets/images/screenshot-dashboard.png)
![Class Catalog](assets/images/screenshot-catalog.png)
![Calendar View](assets/images/screenshot-calendar.png)

---

## Support

For questions or support, please open an issue or contact the maintainer at [info@eduportal.com](mailto:info@eduportal.com).

---

## 📫 Contact

If you have any questions, suggestions, or feedback, feel free to reach out:

- 📧 Email: [likjosh123@gmail.com](mailto:likjosh123@gmail.com)  
- 🌐 Website: [https://likyjosh.likesyou.org](https://likyjos.likesyou.org)
- 🌐 Website: [https://likysolutions.vercel.app/](https://likysolutions.vercel.app/)

### other screen shots 
![pic](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic.png)
![pic](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic1.png)
![pic](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic2.png)
![pic](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic3.png)
![pic](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic4.png)
![pic](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic5.png)
![pic](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic6.png)
![pic](https://github.com/likyCoder/techstack/blob/main/techStack/assets/images/pic7.png)

