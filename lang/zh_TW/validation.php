<?php

// 繁體中文驗證訊息（WP0.4）。只列系統實際會用到的鍵，其餘沿用框架預設。
return [
    'accepted' => '必須接受 :attribute。',
    'boolean' => ':attribute 必須是 true 或 false。',
    'confirmed' => ':attribute 與確認欄位不一致。',
    'current_password' => '目前的密碼不正確。',
    'date' => ':attribute 不是有效日期。',
    'email' => ':attribute 必須是有效的電子郵件地址。',
    'exists' => '所選的 :attribute 不存在。',
    'in' => '所選的 :attribute 無效。',
    'integer' => ':attribute 必須是整數。',
    'max' => [
        'array' => ':attribute 不可超過 :max 個項目。',
        'file' => ':attribute 不可大於 :max KB。',
        'numeric' => ':attribute 不可大於 :max。',
        'string' => ':attribute 不可超過 :max 個字元。',
    ],
    'mimes' => ':attribute 必須是 :values 格式的檔案。',
    'min' => [
        'array' => ':attribute 至少要有 :min 個項目。',
        'file' => ':attribute 不可小於 :min KB。',
        'numeric' => ':attribute 不可小於 :min。',
        'string' => ':attribute 至少要 :min 個字元。',
    ],
    'numeric' => ':attribute 必須是數字。',
    'password' => [
        'letters' => ':attribute 至少要包含一個字母。',
        'mixed' => ':attribute 至少要包含一個大寫與一個小寫字母。',
        'numbers' => ':attribute 至少要包含一個數字。',
        'symbols' => ':attribute 至少要包含一個符號。',
        'uncompromised' => '這個 :attribute 曾在資料外洩事件中出現，請換一組。',
    ],
    'required' => ':attribute 為必填。',
    'string' => ':attribute 必須是文字。',
    'unique' => ':attribute 已經被使用。',
    'uploaded' => ':attribute 上傳失敗。',
    'url' => ':attribute 必須是有效的網址。',

    'attributes' => [
        'name' => '姓名',
        'email' => '電子郵件',
        'password' => '密碼',
        'current_password' => '目前密碼',
        'role' => '角色',
        'department_id' => '部門',
        'title' => '標題',
        'publisher' => '出版者',
        'code' => '編號',
    ],
];
