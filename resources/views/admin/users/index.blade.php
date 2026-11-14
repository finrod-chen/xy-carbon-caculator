@extends('layouts.app')

@section('title', '使用者管理')

@section('content')
  <div class="row" style="justify-content:space-between;align-items:baseline">
    <h1>使用者管理</h1>
    @if ($canManage)
      <a class="btn" href="{{ route('admin.users.create') }}">新增使用者</a>
    @endif
  </div>

  <table>
    <thead>
      <tr><th>姓名</th><th>電子郵件</th><th>角色</th><th>部門</th><th>狀態</th>@if ($canManage)<th>操作</th>@endif</tr>
    </thead>
    <tbody>
      @forelse ($users as $user)
        <tr>
          <td>{{ $user->name }}</td>
          <td>{{ $user->email }}</td>
          <td>{{ $user->role->label() }}</td>
          <td>{{ $user->department?->name ?? '—' }}</td>
          <td>
            @if ($user->is_active)
              <span class="tag">啟用</span>
            @else
              <span class="tag off">停用</span>
            @endif
          </td>
          @if ($canManage)
            <td>
              <div class="row">
                <a class="btn ghost" href="{{ route('admin.users.edit', $user) }}">編輯</a>
                @if (! $user->is(auth()->user()))
                  <form method="post" action="{{ route($user->is_active ? 'admin.users.deactivate' : 'admin.users.reactivate', $user) }}">
                    @csrf
                    <button class="btn ghost" type="submit">{{ $user->is_active ? '停用' : '啟用' }}</button>
                  </form>
                  <form method="post" action="{{ route('admin.users.reset-password', $user) }}">
                    @csrf
                    <button class="btn ghost" type="submit">重設密碼</button>
                  </form>
                @endif
              </div>
            </td>
          @endif
        </tr>
      @empty
        <tr><td colspan="6" class="hint">尚無使用者。</td></tr>
      @endforelse
    </tbody>
  </table>

  <div style="margin-top:16px">{{ $users->links() }}</div>
@endsection
