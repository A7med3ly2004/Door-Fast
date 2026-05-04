<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - غير مصرح لك | دور فاست</title>
    <link rel="icon" type="image/png" href="{{ asset('DF_logo_2026.PNG') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.10) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: pulse 4s ease-in-out infinite;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(187, 39, 44, 0.08) 0%, transparent 70%);
            bottom: 15%;
            left: 8%;
            animation: pulse 5s ease-in-out infinite reverse;
            pointer-events: none;
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 1; }
        }

        .container {
            text-align: center;
            padding: 40px 20px;
            max-width: 600px;
            position: relative;
            z-index: 1;
        }

        .error-code {
            font-size: clamp(100px, 20vw, 160px);
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -4px;
            margin-bottom: 8px;
            animation: fadeInDown 0.6s ease both;
        }

        .error-icon {
            font-size: 60px;
            margin-bottom: 20px;
            animation: fadeInDown 0.6s ease 0.1s both;
        }

        .error-title {
            font-size: 28px;
            font-weight: 800;
            color: #f1f5f9;
            margin-bottom: 14px;
            animation: fadeInUp 0.6s ease 0.2s both;
        }

        .error-message {
            font-size: 16px;
            color: #94a3b8;
            line-height: 1.9;
            margin-bottom: 40px;
            animation: fadeInUp 0.6s ease 0.3s both;
        }

        .divider {
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #bb272c);
            border-radius: 2px;
            margin: 0 auto 28px;
            animation: scaleIn 0.5s ease 0.25s both;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #fff;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 12px;
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            font-weight: 700;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease 0.4s both;
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
        }

        .btn-home:hover {
            background: linear-gradient(135deg, #b45309, #92400e);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(245, 158, 11, 0.45);
        }

        .btn-home svg {
            width: 20px;
            height: 20px;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scaleX(0); }
            to { opacity: 1; transform: scaleX(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">403</div>
        <div class="error-icon">🔒</div>
        <h1 class="error-title">غير مصرح لك بالوصول</h1>
        <div class="divider"></div>
        <p class="error-message">
            عذراً، ليس لديك صلاحية للوصول إلى هذه الصفحة.<br>
            إذا كنت تعتقد أن هذا خطأ، تواصل مع المسؤول أو عد للرئيسية.
        </p>
        <a href="/" class="btn-home">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            العودة للرئيسية
        </a>
    </div>
</body>
</html>
