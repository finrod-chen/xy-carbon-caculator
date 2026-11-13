@extends('layouts.app')

@section('title', '登入')

@section('content')
  <div class="card" style="max-width:420px;margin:0 auto">
    <h1>登入</h1>
    <p class="hint">帳號由系統管理員建立；忘記密碼請聯絡管理員重設。</p>
    <form method="post" action="{{ route('login') }}">
      @csrf
      <label for="email">電子郵件</label>
      <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
      <label for="password">密碼</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
      <div class="row" style="margin-top:12px">
        <label style="margin:0"><input type="checkbox" name="remember" value="1"> 記住我</label>
      </div>
      <button class="btn" type="submit" style="margin-top:16px;width:100%">登入</button>
    </form>
  </div>
@endsection
