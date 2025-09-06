#PanelCS2 PHP Agente python

    pip install Flask
    pip install psutil

    python app.py
    
<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/0c7ce722-14cc-48e7-afc1-b5c033d8a87f" />

Control Panel for CS2 Servers

<img width="622" height="477" alt="image" src="https://github.com/user-attachments/assets/ecf887e4-4a01-4520-81bc-c7359f2dd560" />

<img width="626" height="472" alt="image" src="https://github.com/user-attachments/assets/f89fc03a-b8fd-4f24-8507-4dc866022856" />


<img width="1911" height="636" alt="image" src="https://github.com/user-attachments/assets/0bf3690d-7c6a-4f8a-b459-12c389c4ac2f" />

C:\Python313\python.exe
C:\Apache24\htdocs\Counter-Strike_agent\app.py

C:\steamcmd

C:\cs2-ds\game\csgo





This project is a lightweight and secure control panel, designed to remotely manage Counter-Strike 2 (CS2) servers via RCON. The panel includes user authentication features, registration control, and a user-friendly interface to send commands to the server.

Core Features
Login and Registration System: Securely authenticate users using encrypted passwords (hashing). New user registration can be enabled or disabled by an administrator, with success and error messages in different languages.

Secure Access: The main page (index.php) is protected by a session system that requires a login. If the user is not authenticated, they are redirected to the login page.

RCON Connection: Send RCON commands to the CS2 server. The interface displays the server's response with icons that indicate the command status (success, error, information).

Integrated Database: The panel connects to a MySQL database to manage user information, such as usernames and passwords. The users table is created automatically if it does not exist.

Internationalization (i18n): The panel automatically detects the user's browser language and displays messages in Portuguese or English, using translation files (pt.json and en.json).

Responsive and Modern Design: The login page layout has been optimized to be compact and visually consistent with the rest of the panel.

Project Structure
index.php: The main panel page, protected by login.

login.php: The login page and authentication logic.

register.php: The page for new user registration, with activation/deactivation control.

db_connect.php: A configuration file that establishes the database connection via PDO and defines global settings such as registration activation.

rcon2.php: Script for communication with the CS2 server via RCON.

style.css: Stylesheet for the panel's visual design.

lang/: Folder containing the translation files (pt.json and en.json).

img/: Folder to store project images and icons.

Setup and Usage
Configure the Database: Edit the db_connect.php file with your MySQL credentials ($host, $db, $user, $pass).

Enable Registration: In the db_connect.php file, set $allow_registration = true; to allow new registrations. After the first registration, it is recommended to set the value back to false.

Access: Access login.php in your browser. 
