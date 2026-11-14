@extends('layouts.app')

@section('title', '沒有權限')

@section('content')
  <div class="card" style="max-width:560px;margin:0 auto">
    <h1>沒有權限</h1>
    <p>這個頁面需要更高的權限。若你認為這是誤判，請聯絡系統管理員調整角色。</p>
    <a class="btn ghost" href="{{ route('home') }}">回到首頁</a>
  </div>
@endsection
