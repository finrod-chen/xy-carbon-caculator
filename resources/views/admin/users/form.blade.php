@extends('layouts.app')

@section('title', $user->exists ? '編輯使用者' : '新增使用者')

@section('content')
  <h1>{{ $user->exists ? '編輯使用者' : '新增使用者' }}</h1>
  <div class="card" style="max-width:560px">
    <form method="post" action="{{ $action }}">
      @csrf
      @if ($method !== 'POST')
        @method($method)
      @endif

      <label for="name">姓名</label>
      <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>

      <label for="email">電子郵件</label>
      <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>

      <label for="role">角色</label>
      <select id="role" name="role" required>
        @foreach ($roles as $role)
          <option value="{{ $role->value }}" @selected(old('role', $user->role?->value ?? 'contributor') === $role->value)>{{ $role->label() }}</option>
        @endforeach
      </select>

      <label for="department_id">部門</label>
      <select id="department_id" name="department_id">
        <option value="">（未指派）</option>
        @foreach ($departments as $department)
          <option value="{{ $department->id }}" @selected((string) old('department_id', $user->department_id) === (string) $department->id)>{{ $department->name }}</option>
        @endforeach
      </select>

      <p class="hint">新帳號會建立一組臨時密碼，首次登入必須修改。</p>

      <div class="row" style="margin-top:16px">
        <button class="btn" type="submit">儲存</button>
        <a class="btn ghost" href="{{ route('admin.users.index') }}">返回</a>
      </div>
    </form>
  </div>
@endsection
