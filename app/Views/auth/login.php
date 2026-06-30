<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — PearlCare</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/public/assets/images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:#f0f4ff;--surface:#ffffff;--border:#dde3f0;--text:#111827;
            --text-muted:#6b7280;--primary:#2563eb;--primary-d:#1d4ed8;
            --danger-bg:#fef2f2;--danger:#dc2626;--radius:14px;
            --shadow:0 4px 24px rgba(37,99,235,.10);
        }
        [data-theme="dark"] {
            --bg:#0f1117;--surface:#181c26;--border:#272d3e;--text:#f1f5f9;
            --text-muted:#94a3b8;--primary:#3b82f6;--primary-d:#2563eb;
            --danger-bg:#450a0a;--danger:#ef4444;--shadow:0 4px 24px rgba(0,0,0,.4);
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;}
        .theme-btn{position:fixed;top:16px;right:16px;width:38px;height:38px;border-radius:50%;border:1px solid var(--border);background:var(--surface);color:var(--text-muted);cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;}
        .login-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:40px 36px;width:100%;max-width:420px;box-shadow:var(--shadow);}
        .logo-area{text-align:center;margin-bottom:28px;}
        .logo-icon{width:54px;height:54px;background:var(--primary);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:white;margin:0 auto 12px;}
        .logo-area h1{font-family:'Sora',sans-serif;font-size:1.4rem;color:var(--primary);}
        .logo-area p{font-size:.8rem;color:var(--text-muted);margin-top:3px;}
        .error-box{background:var(--danger-bg);color:var(--danger);border:1px solid var(--danger);border-radius:9px;padding:10px 14px;font-size:.875rem;margin-bottom:16px;}
        .form-group{margin-bottom:16px;}
        .form-group label{display:block;font-size:.8rem;font-weight:600;color:var(--text-muted);margin-bottom:5px;}
        .form-group input,.form-group select{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:9px;background:var(--bg);color:var(--text);font-size:.9rem;font-family:inherit;outline:none;transition:border-color .15s;}
        .form-group input:focus,.form-group select:focus{border-color:var(--primary);}
        .btn-login{width:100%;padding:11px;background:var(--primary);color:white;border:none;border-radius:9px;font-size:.95rem;font-weight:600;font-family:inherit;cursor:pointer;transition:background .15s;margin-top:6px;}
        .btn-login:hover{background:var(--primary-d);}
        .demo-hint{text-align:center;margin-top:18px;font-size:.78rem;color:var(--text-muted);}
        @media (max-width:480px){.theme-btn{top:12px;right:12px;} .login-card{padding:24px 18px;} .logo-area h1{font-size:1.25rem;} .logo-area p{font-size:.75rem;}}
    </style>
</head>
<body>
    <button class="theme-btn" id="themeToggle" title="Toggle dark/light mode">
        <?php echo $theme === 'dark' ? '☀️' : '🌙'; ?>
    </button>

    <div class="login-card">
        <div class="logo-area">
            <div class="logo-icon">✦</div>
            <h1>PearlCare</h1>
            <p>Clinical Excellence — Staff Portal</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo BASE_URL; ?>/login">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@kampalaskin.ug"
                    value="<?php echo e($_POST['email'] ?? ''); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="role">Login As</label>
                <select id="role" name="role" required>
                    <option value="">Select role...</option>
                    <?php foreach (['admin'=>'Admin','doctor'=>'Doctor','nurse'=>'Nurse','receptionist'=>'Receptionist','records'=>'Records'] as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo (($_POST['role'] ?? '') === $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <p class="demo-hint">Demo: <strong>admin@kampalaskin.ug</strong> / <strong>password</strong></p>
    </div>

    <script>
        document.getElementById('themeToggle').addEventListener('click', function() {
            var html = document.documentElement;
            var theme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', theme);
            document.cookie = 'theme=' + theme + ';path=/;max-age=31536000';
            this.textContent = theme === 'dark' ? '☀️' : '🌙';
        });
    </script>
</body>
</html>
