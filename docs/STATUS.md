# 專案狀態（暫停中）

> 更新日期：2026-11-08。決定：公司仍在學習階段，專案暫停，待之後再評估。

## 目前有什麼

- 只有文件，**沒有任何程式碼**。
- 文件索引：[PLAN.md](PLAN.md)（規劃書）、[TECH_DESIGN.md](TECH_DESIGN.md)（技術設計）、[UI_DESIGN.md](UI_DESIGN.md) 與 [wireframes/index.html](wireframes/index.html)（介面與線稿）、[PHASE0.md](PHASE0.md)（階段 0 工作清單）、[HOSTING.md](HOSTING.md) 與 [DEPLOY.md](DEPLOY.md)（測試主機與部署）、[brand/](brand/)（暫用標誌）。

## 重新啟動前先做的事

1. **先訪談顧問**，確認他們交付什麼、底稿能否取得、是否接受系統匯出的資料、後續是否長期合作。
2. 同時評估購買現成的碳盤查平台。
3. 若決定自建：把範圍收斂為 MVP（登入與角色、每月填報、佐證、審核與鎖定、稽核紀錄、匯出為顧問底稿格式），SBTi 與 IFRS S2 模組延後。
4. 重新核對標準與法規：SBTi V2.0、IFRS S2 修正案、金管會藍圖、能源署電力係數等（規劃書第 11 節列出核對狀態，多項仍為「二手」或「待核對」）。
5. 指定排放係數維護者、說明頁文案撰寫與審稿人（廠務）。

## 已存在、需要留意的外部設定

| 項目 | 狀態 | 建議 |
|---|---|---|
| Cloudflare Tunnel `xiyue-esg-test` 與 DNS `esg.finrod.xyz` | 已建立；機器上的連接器仍在執行 | 暫停期間不需要就刪除或停用；網址目前只放測試頁，且尚未加 Access |
| Instabox 機器 `main` | 運行中（免費方案） | 暫停期間可直接停止，避免佔用唯一名額 |
| Instabox API 金鑰（給 Claude 唯讀檢查用） | 存在使用者的 Claude 環境 | 到 Instabox 後台撤銷 |
| Claude 環境的網路白名單 | 已加入 SBTi、IFRS、GHG Protocol、金管會、環境部、能源署、instabox.sh 網站 | 不需要時到環境設定移除 |
| PR #1 | 草稿，只含文件 | 可合併保存，或保持草稿 |
