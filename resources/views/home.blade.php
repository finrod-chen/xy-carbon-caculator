@extends('layouts.app')

@section('title', '首頁')

@section('content')
  <h1>您好，{{ $user->name }}</h1>
  <div class="card">
    <p>目前登入角色：<span class="tag">{{ $user->role->label() }}</span></p>
    <p class="hint">階段 0 只提供帳號與授權基礎。填報、審核、Dashboard、目標與報告會依 PHASE0 的後續工作包接上。</p>
  </div>

  <div class="card">
    <h2 style="margin-top:0">可使用的功能</h2>
    <ul>
      @if ($user->role->canAccessAdmin())
        <li><a href="{{ route('admin.users.index') }}">使用者管理</a>：建立帳號、指派角色與部門、停用、重設臨時密碼。</li>
      @endif
      @if ($user->role->isReadOnly())
        <li><a href="{{ route('admin.users.index') }}">使用者清單（唯讀）</a></li>
      @endif
      <li>修改自己的密碼：<a href="{{ route('password.edit') }}">前往</a></li>
    </ul>
  </div>
@endsection
