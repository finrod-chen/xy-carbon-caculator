@extends('layouts.app')

@section('title', '修改密碼')

@section('content')
  <div class="card" style="max-width:480px;margin:0 auto">
    <h1>修改密碼</h1>
    <p class="hint">密碼至少 12 碼，需包含英文字母與數字，且不可與目前密碼相同。</p>
    <form method="post" action="{{ route('password.update') }}">
      @csrf
      @method('PUT')
      <label for="current_password">目前密碼</label>
      <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
      <label for="password">新密碼</label>
      <input id="password" name="password" type="password" required autocomplete="new-password">
      <label for="password_confirmation">再次輸入新密碼</label>
      <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
      <button class="btn" type="submit" style="margin-top:16px">更新密碼</button>
    </form>
  </div>
@endsection
