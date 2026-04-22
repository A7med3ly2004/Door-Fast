<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" href="{{ asset('DF_logo_2026.PNG') }}">
  <title>DoorFast — تسجيل الدخول</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Cairo', sans-serif;
      background: #eef5fdff;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      overflow-x: hidden;
    }

    .card {
      background: #ffffff;
      border-top-width: 5px;
      border-top-style: solid;
      border-top-color: #f8c624;
      box-shadow: 0px 10px 30px 0px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      padding: 40px;
      width: 100%;
      max-width: 400px;
    }

    .logo {
      text-align: center;
      margin-bottom: 32px;
    }

    .logo-icon {
      margin-bottom: 12px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .logo-img {
      max-width: 120px;
      height: auto;
      object-fit: contain;
    }

    .logo-sub {
      font-size: 14px;
      color: #4a5568;
      margin-top: 4px;
      font-weight: 600;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: #4a5568;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      background: #ffffff;
      border: 2px solid #e2e8f0;
      border-radius: 6px;
      color: #1e293b;
      font-family: 'Cairo', sans-serif;
      font-size: 16px;
      /* 16px minimum to prevent iOS auto-zoom */
      min-height: 48px;
      /* Recommended minimum touch target size */
      padding: 10px 16px;
      outline: none;
      transition: all 0.2s ease;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color: #ba282e;
      box-shadow: 0 0 0 3px rgba(186, 40, 46, 0.1);
    }

    input::placeholder {
      color: #94a3b8;
    }

    .btn {
      width: 100%;
      min-height: 48px;
      /* Touch target size */
      padding: 12px;
      background: #ba282e;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-family: 'Cairo', sans-serif;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 8px;
      transition: background 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn:hover {
      background: #831c20;
    }

    .btn:disabled {
      background: #e2e8f0;
      color: #94a3b8;
      cursor: not-allowed;
    }

    .error-msg {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.3);
      border-radius: 6px;
      padding: 12px 14px;
      color: #ba282e;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 20px;
      display: none;
    }

    .remember {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: #4a5568;
      margin-bottom: 24px;
    }

    .remember input[type="checkbox"] {
      width: 20px;
      height: 20px;
      accent-color: #ba282e;
      cursor: pointer;
    }

    .remember label {
      text-transform: none;
      letter-spacing: 0;
      margin: 0;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
      body {
        padding: 16px;
      }

      .card {
        padding: 30px 20px;
        border-radius: 12px;
        box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.08);
      }

      .logo {
        margin-bottom: 24px;
      }

      .logo-img {
        max-width: 100px;
      }

      .btn {
        margin-bottom: 10px;
      }
    }
  </style>
</head>

<body>
  <div class="card">
    <div class="logo">
      <div class="logo-icon">
        <img src="{{ asset('DF_logo_2026.PNG') }}" alt="DoorFast Logo" class="logo-img">
      </div>
      <div class="logo-sub">نظام إدارة التوصيل</div>
    </div>

    <div class="error-msg" id="errorMsg"></div>

    <div class="form-group">
      <label>اسم المستخدم</label>
      <input type="text" id="username" placeholder="أدخل اسم المستخدم" autofocus>
    </div>

    <div class="form-group">
      <label>كلمة المرور</label>
      <input type="password" id="password" placeholder="أدخل كلمة المرور">
    </div>

    <div class="remember">
      <input type="checkbox" id="remember">
      <label for="remember" style="text-transform:none;letter-spacing:0;margin:0;cursor:pointer;">تذكرني</label>
    </div>

    <button class="btn" id="loginBtn" onclick="doLogin()">دخول</button>
  </div>

  <script>
    document.getElementById('password').addEventListener('keypress', function (e) {
      if (e.key === 'Enter') doLogin();
    });

    async function doLogin() {
      const btn = document.getElementById('loginBtn');
      const errorMsg = document.getElementById('errorMsg');
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const remember = document.getElementById('remember').checked;

      if (!username || !password) {
        showError('يرجى إدخال اسم المستخدم وكلمة المرور');
        return;
      }

      btn.disabled = true;
      btn.textContent = 'جاري الدخول...';
      errorMsg.style.display = 'none';

      try {
        const res = await fetch('/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({ username, password, remember }),
        });

        const data = await res.json();

        if (data.success) {
          window.location.href = data.redirect;
        } else {
          showError(data.message);
        }
      } catch {
        showError('حدث خطأ، يرجى المحاولة مرة أخرى');
      } finally {
        btn.disabled = false;
        btn.textContent = 'دخول';
      }
    }

    function showError(msg) {
      const el = document.getElementById('errorMsg');
      el.textContent = msg;
      el.style.display = 'block';
    }
  </script>
</body>

</html>