@extends('layouts.app')

@section('title', '臨時密碼')

@section('content')
  <div class="card" style="max-width:560px;margin:0 auto">
    <h1>臨時密碼</h1>
    <p class="hint">
      這組密碼只顯示這一次，離開本頁就查不到；請當面或透過安全管道交給
      @if ($name !== ''){{ $name }}（{{ $email }}）@else該使用者@endif。
    </p>
    <p><code style="font-size:1.25rem;padding:8px 12px;display:inline-block">{{ $password }}</code></p>
    <p class="hint">對方第一次登入後必須立即修改密碼，其他裝置的登入也會失效。</p>
    <p><a class="btn" href="{{ route('admin.users.index') }}">回到使用者清單</a></p>
  </div>
@endsection
