<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            background-color: #f8f8f8;
            border-radius: 5px;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }

        .header {
            background-color: #2c3e50;
            border-radius: 5px 5px 0 0;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        .logo {
            max-height: 50px;
            max-width: 100%;
        }

        .content {
            background-color: #ffffff;
            border-radius: 0 0 5px 5px;
            padding: 40px;
            text-align: center;
        }

        .verification-code {
            display: inline-block;
            font-size: 24px;
            color: #333;
            background-color: #f1f1f1;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="https://www.talksmart.cc">
            <img class="logo" src="" alt="智能语音对话">
        </a>
    </div>
    <div class="content">
        <p>您的验证码为：</p>
        <div class="verification-code">{{ $code }}</div>
    </div>
</div>
</body>
</html>
