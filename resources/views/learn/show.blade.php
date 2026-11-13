@extends('layouts.app')

@section('title', $page->title)

@section('content')
  <p class="hint"><a href="{{ route('learn.index') }}">說明頁</a> / {{ $page->title }}</p>
  <h1>{{ $page->title }}</h1>

  @if ($page->updated)
    <p class="hint">最後更新：{{ $page->updated }}@if ($page->owner) · 負責人：{{ $page->owner }}@endif</p>
  @endif

  <p><span class="tag off">內容待廠務確認</span></p>

  @if ($hasUnverified)
    <div class="alert error" role="alert">
      這一頁引用到尚未核對原文或超過 12 個月未複核的資料，數字與規則以正式公告為準。
    </div>
  @endif

  <div class="card">
    @if (count($page->headings) > 1)
      <details style="margin-bottom:12px">
        <summary class="hint" style="cursor:pointer">本頁目錄</summary>
        <ul style="margin:8px 0 0;padding-left:18px">
          @foreach ($page->headings as $heading)
            <li><a href="#{{ $heading['id'] }}">{{ $heading['text'] }}</a></li>
          @endforeach
        </ul>
      </details>
    @endif

    <div class="prose">
      {!! $page->html !!}
    </div>
  </div>

  @if ($references->isNotEmpty())
    <div class="card">
      <h2 style="margin-top:0">本頁引用</h2>
      <ul>
        @foreach ($references as $reference)
          <li>
            <a href="{{ route('learn.references') }}#{{ $reference->code }}">[{{ $reference->code }}]</a>
            {{ $reference->title }}
            <span class="tag {{ $reference->verification_status->needsWarning() ? 'off' : '' }}">{{ $reference->verification_status->label() }}</span>
          </li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row" style="justify-content:space-between;margin-top:24px">
    <div>
      @if ($previous)
        <a href="{{ route('learn.show', $previous->slug) }}">← {{ $previous->title }}</a>
      @endif
    </div>
    <div>
      @if ($next)
        <a href="{{ route('learn.show', $next->slug) }}">{{ $next->title }} →</a>
      @endif
    </div>
  </div>
@endsection
