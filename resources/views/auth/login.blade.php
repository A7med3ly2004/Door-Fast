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
      background: #0e1525;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .card {
      background: #1a2035;
      border: 1px solid #2a3a55;
      border-radius: 16px;
      padding: 40px;
      width: 100%;
      max-width: 400px;
    }

    .logo {
      text-align: center;
      margin-bottom: 32px;
    }

    .logo-icon {
      width: 56px;
      height: 56px;
      background: #2563eb;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 12px;
    }

    .logo-text {
      font-size: 22px;
      font-weight: 800;
      color: #fff;
    }

    .logo-sub {
      font-size: 13px;
      color: #4a5568;
      margin-top: 4px;
    }

    .form-group {
      margin-bottom: 16px;
    }

    label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: #4a5568;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }

    input {
      width: 100%;
      background: #0e1525;
      border: 1px solid #2a3a55;
      border-radius: 9px;
      color: #e2e8f0;
      font-family: 'Cairo', sans-serif;
      font-size: 14px;
      padding: 11px 14px;
      outline: none;
      transition: border-color 0.2s;
    }

    input:focus {
      border-color: #2563eb;
    }

    input::placeholder {
      color: #4a5568;
    }

    .btn {
      width: 100%;
      padding: 12px;
      background: #2563eb;
      color: #fff;
      border: none;
      border-radius: 9px;
      font-family: 'Cairo', sans-serif;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 8px;
      transition: background 0.2s;
    }

    .btn:hover {
      background: #1d4ed8;
    }

    .btn:disabled {
      background: #2a3a55;
      cursor: not-allowed;
    }

    .error-msg {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.3);
      border-radius: 9px;
      padding: 10px 14px;
      color: #ef4444;
      font-size: 13px;
      margin-bottom: 16px;
      display: none;
    }

    .remember {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: #94a3b8;
      margin-bottom: 16px;
    }

    .remember input[type=checkbox] {
      width: 16px;
      height: 16px;
      accent-color: #2563eb;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <div class="card">
    <div class="logo">
      <div class="logo-icon">🚀</div>
      <div class="logo-text">DoorFast</div>
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