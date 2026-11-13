<!doctype html>
<html lang="zh-Hant">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', '碳盤查系統') · 喜躍生醫</title>
<style>
  :root { color-scheme: light; --ink:#1f2328; --muted:#6b7280; --line:#e5e7eb; --accent:#047857; --bg:#f8fafc; }
  * { box-sizing: border-box; }
  body { margin:0; font:15px/1.7 system-ui, -apple-system, "Noto Sans TC", sans-serif; color:var(--ink); background:var(--bg); }
  header { background:#fff; border-bottom:1px solid var(--line); }
  .bar { max-width:960px; margin:0 auto; padding:12px 16px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
  .brand { font-weight:600; }
  nav { display:flex; gap:12px; flex-wrap:wrap; }
  nav a { color:var(--muted); text-decoration:none; padding:4px 6px; border-radius:6px; }
  nav a:hover, nav a:focus-visible { color:var(--accent); background:#ecfdf5; }
  .spacer { flex:1; }
  main { max-width:960px; margin:0 auto; padding:24px 16px 48px; }
  h1 { font-size:20px; margin:0 0 16px; }
  h2 { font-size:16px; margin:24px 0 8px; }
  .card { background:#fff; border:1px solid var(--line); border-radius:10px; padding:16px; margin-bottom:16px; }
  table { width:100%; border-collapse:collapse; background:#fff; }
  th, td { text-align:left; padding:8px 10px; border-bottom:1px solid var(--line); font-size:14px; }
  th { color:var(--muted); font-weight:600; }
  .btn { display:inline-block; border:1px solid var(--accent); background:var(--accent); color:#fff; padding:6px 12px; border-radius:8px; font:inherit; cursor:pointer; text-decoration:none; }
  .btn.ghost { background:#fff; color:var(--accent); }
  .btn.danger { background:#b91c1c; border-color:#b91c1c; }
  .btn:focus-visible, a:focus-visible { outline:2px solid var(--accent); outline-offset:2px; }
  label { display:block; font-weight:600; margin:12px 0 4px; }
  input[type=text], input[type=email], input[type=password], select { width:100%; padding:8px 10px; border:1px solid var(--line); border-radius:8px; font:inherit; background:#fff; }
  .alert { border-radius:8px; padding:10px 12px; margin-bottom:16px; font-size:14px; }
  .alert.ok { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
  .alert.error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
  .hint { color:var(--muted); font-size:13px; }
  .tag { display:inline-block; font-size:12px; padding:1px 8px; border-radius:999px; background:#ecfdf5; color:#047857; }
  .tag.off { background:#f3f4f6; color:#6b7280; }
  footer { color:var(--muted); font-size:12px; text-align:center; padding:16px; }
  .row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
</style>
</head>
<body>
<header>
  <div class="bar">
    <span class="brand">喜躍生醫 · 碳盤查</span>
    <nav aria-label="主選單">
      @auth
        <a href="{{ route('home') }}">首頁</a>
        @if (auth()->user()->role->canAccessAdmin() || auth()->user()->role->isReadOnly())
          <a href="{{ route('admin.users.index') }}">使用者</a>
        @endif
      @endauth
    </nav>
    <span class="spacer"></span>
    @auth
      <span class="hint">{{ auth()->user()->name }}（{{ auth()->user()->role->label() }}）</span>
      <form method="post" action="{{ route('logout') }}">
        @csrf
        <button class="btn ghost" type="submit">登出</button>
      </form>
    @endauth
  </div>
</header>
<main>
  @if (session('status'))
    <div class="alert ok" role="status">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert error" role="alert">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif
  @yield('content')
</main>
<footer>
  系統版本 {{ config('carbon.app_version') }} · 資料以 Asia/Taipei 顯示
</footer>
</body>
</html>
