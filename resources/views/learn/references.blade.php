@extends('layouts.app')

@section('title', '參考資料')

@section('content')
  <p class="hint"><a href="{{ route('learn.index') }}">說明頁</a> / 參考資料</p>
  <h1>參考資料</h1>
  <p class="hint">核對狀態：原文＝已讀過官方文件；二手＝僅見新聞或顧問摘要；待核對＝尚未開啟。上線前須把二手與待核對補成原文。</p>

  <table>
    <thead>
      <tr><th>編號</th><th>名稱</th><th>發布單位</th><th>狀態</th><th>核對日</th></tr>
    </thead>
    <tbody>
      @foreach ($references as $reference)
        <tr id="{{ $reference->code }}">
          <td>{{ $reference->code }}</td>
          <td>
            @if ($reference->url)
              <a href="{{ $reference->url }}" rel="noreferrer noopener" target="_blank">{{ $reference->title }}</a>
            @else
              {{ $reference->title }}
            @endif
          </td>
          <td>{{ $reference->publisher }}</td>
          <td><span class="tag {{ $reference->verification_status->needsWarning() ? 'off' : '' }}">{{ $reference->verification_status->label() }}</span></td>
          <td>{{ $reference->verified_on?->format('Y-m-d') ?? '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endsection
