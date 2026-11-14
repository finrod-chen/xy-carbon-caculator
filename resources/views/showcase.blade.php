@extends('layouts.app')

@section('title', '元件展示')

@section('content')
  <h1>元件展示</h1>
  <p class="hint">僅在 local 與 testing 環境可見，用來做視覺驗收與回歸比對（PHASE0 WP0.6）。</p>

  <x-ui.card title="按鈕">
    <div class="row">
      <x-ui.button>主要按鈕</x-ui.button>
      <x-ui.button variant="ghost">次要按鈕</x-ui.button>
      <x-ui.button variant="danger">危險動作</x-ui.button>
      <x-ui.button href="{{ route('home') }}" variant="ghost">連結樣式</x-ui.button>
      <x-ui.button disabled>停用狀態</x-ui.button>
    </div>
  </x-ui.card>

  <x-ui.card title="狀態標籤">
    <div class="row">
      <x-ui.tag label="啟用" />
      <x-ui.tag tone="off" label="停用" />
      <x-ui.tag label="原文" />
      <x-ui.tag tone="off" label="待核對" />
    </div>
    <p class="hint">每個狀態都有文字，不只靠顏色。</p>
  </x-ui.card>

  <x-ui.card title="提示條">
    <x-ui.alert tone="ok">這是一則成功訊息。</x-ui.alert>
    <x-ui.alert tone="error">這是一則錯誤訊息，會以 role="alert" 朗讀。</x-ui.alert>
    <x-ui.alert tone="info">這是一則說明訊息。</x-ui.alert>
  </x-ui.card>

  <x-ui.card title="表單欄位">
    <div style="max-width:360px">
      <x-ui.field name="demo_email" label="電子郵件" type="email" hint="用來登入系統，不會對外公開。" />
      <x-ui.field name="demo_name" label="姓名" value="王小明" />
    </div>
  </x-ui.card>

  <x-ui.card title="空狀態">
    <x-ui.empty title="還沒有資料" description="建立第一筆資料後就會出現在這裡。" />
  </x-ui.card>

  <x-ui.card title="表格">
    <table>
      <thead><tr><th>項目</th><th>數值</th><th>狀態</th></tr></thead>
      <tbody>
        <tr><td>用電（度）</td><td>12,400</td><td><x-ui.tag label="已核准" /></td></tr>
        <tr><td>冷媒填充（kg）</td><td>3.2</td><td><x-ui.tag tone="off" label="估算" /></td></tr>
      </tbody>
    </table>
  </x-ui.card>
@endsection
