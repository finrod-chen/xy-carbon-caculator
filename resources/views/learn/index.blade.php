@extends('layouts.app')

@section('title', '說明頁')

@section('content')
  <h1>說明頁</h1>
  <p class="hint">填報前先看這裡：每個排放源要填什麼、為什麼要填、怎麼換算。[R-xx] 連到參考資料頁，標示這個數字或規則的出處。</p>

  <div class="card">
    <ul style="margin:0;padding-left:18px">
      @foreach ($pages as $page)
        <li style="margin-bottom:6px">
          <a href="{{ route('learn.show', $page->slug) }}">{{ $page->title }}</a>
          @if ($page->updated)
            <span class="hint">（更新：{{ $page->updated }}）</span>
          @endif
        </li>
      @endforeach
    </ul>
  </div>

  <p><a class="btn ghost" href="{{ route('learn.references') }}">查看參考資料清單</a></p>
@endsection
