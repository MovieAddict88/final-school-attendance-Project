<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Data Management System</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body {
            background-color: #f4f7f6;
            text-align: center;
            padding: 50px;
        }
        .portal-links {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .portal-link {
            display: inline-block;
            width: 200px;
            padding: 40px 20px;
            background-color: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            transition: transform 0.3s, background-color 0.3s;
        }
        .portal-link:hover {
            transform: translateY(-10px);
            background-color: #34495e;
        }
    </style>
</head>
<body>
    <header>
        <h1>Welcome to the School Data Management System</h1>
        <p>Your one-stop solution for managing school operations efficiently.</p>
    </header>

    <div class="portal-links">
        <a href="admin/" class="portal-link">Admin Portal</a>
        <a href="teacher/" class="portal-link">Teacher Portal</a>
        <a href="student/" class="portal-link">Student Portal</a>
        <a href="parent/" class="portal-link">Parent Portal</a>
    </div>
</body>
</html>