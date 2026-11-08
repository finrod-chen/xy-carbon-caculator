# 雲端測試主機規劃（Instabox + Cloudflare）

> 狀態：Cloudflare 端已建立，機器端尚未開通；應用程式尚未開發。
> 更新日期：2026-11-08。對應 [PHASE0.md](PHASE0.md) 第 8 節與 [TECH_DESIGN.md](TECH_DESIGN.md) 第 2、10 節。
> Instabox 內容依 2026-11-08 閱讀的 [官方文件](https://instabox.sh/docs) 整理；方案與限制可能變動，以官方為準。

## 1. 現況

| 項目 | 內容 |
|---|---|
| 主機 | Instabox 雲端機器：4 vCPU／8 GiB 記憶體／20 GiB 磁碟 |
| 網址 | `https://esg.finrod.xyz`（Cloudflare 代理） |
| 路徑 | 使用者 → Cloudflare（TLS、WAF、可加 Access）→ Cloudflare Tunnel → 機器內 `http://localhost:80` |
| 資料 | 僅放假資料 |
| 維運 | 使用者本人 |
| CI | 已在 GitHub 連接 CI（Instabox 的 GitHub 整合） |

**Cloudflare 端已完成（2026-11-08）**
- 建立雲端管理的 Tunnel：名稱 `xiyue-esg-test`，ID `16da11f7-969a-4395-adc3-249db3d477ac`，狀態目前為「未連線」（機器端還沒有執行連接器）。
- 入口規則：`esg.finrod.xyz` → `http://localhost:80`，其餘一律回 404。
- DNS：`esg.finrod.xyz` CNAME 指向該 Tunnel，已啟用 Cloudflare 代理。新增前已確認此名稱與 Tunnel 名稱原本不存在，未動到任何既有記錄。
- Tunnel 的連線金鑰我**沒有取出、也沒有輸出**。要在機器上啟動連接器時，請你自己從 Cloudflare 後台取得（見第 6 節）。

在機器端連接器啟動前，開網址會看到 Cloudflare 的錯誤頁（1033），這是預期行為，沒有任何內容外露。

## 2. 使用原則

1. 測試站只放假資料，不放真實的能源、採購、財務或員工資料。公司在 IPO 籌備期，真實資料等正式環境確認後再上。
2. 初始管理員為陳思蒝；信箱於安裝時輸入，不寫進文件或程式碼。測試帳號用假名。
3. 密碼、金鑰、Tunnel 金鑰、API 金鑰不貼在對話、不寫入儲存庫。
4. 機器可隨時重建：設定由儲存庫的腳本還原。

## 3. 架構

```
瀏覽器 ─HTTPS→ Cloudflare ─Tunnel→ cloudflared（機器內，systemd 服務）
                                      │
                                      ▼
                        nginx + php-fpm 容器（Laravel，:80）
                                      │
                        資料卷：/data/…（SQLite、私有儲存、備份）
```

- **TLS 由 Cloudflare 處理**，機器內只跑 HTTP，不需要在機器上申請憑證，因此 HOSTING 先前規劃的 Caddy 改為不需要，反向代理用 nginx 即可。
- **機器不對外開放任何埠**，也不使用 Instabox 的「公開連接埠」功能。這樣沒有人能繞過 Cloudflare 直接連到網站。
- 管理機器用 Instabox 的 CLI 或 SSH 閘道，不需要自己開 22 埠。
- 資料與備份放在 `/data`（Instabox 的持久磁碟，家目錄為 `/data/home`）。
- 機器範本使用預裝 Docker 與 Compose 的版本（例如預設的 `agent-browser`）。

## 4. 重要限制：免費方案的自動暫停

你的規格（4 vCPU／8 GiB／20 GiB）與 Instabox **免費方案**一致（我推測你用的是免費方案，請確認）。依官方文件，免費方案：

| 項目 | 限制 | 對測試站的影響 |
|---|---|---|
| 閒置暫停 | 固定 30 分鐘沒有活動就暫停 | 沒人用一陣子，網站就停了 |
| 最長連續運行 | 每次最長約 8 小時（採動態延長，最晚 8 小時），即使有人在用也會暫停 | 一天內至少會被暫停一次 |
| 暫停後的冷卻 | 因達到最長運行而暫停後，會有 5 到 20 分鐘的冷卻，連續發生會拉長到 1 小時以上甚至 2 小時以上 | 冷卻期間不論 SSH、公開網址或指令都無法喚醒 |
| 同時運行 | 免費方案同時只能有 1 台運行中 | 測試站與 CI 暫時機器可能互相排隊（需向 Instabox 確認 CI 機器是否計入） |
| 喚醒方式 | 對「公開連接埠」的請求、SSH、指令可喚醒；**經由 Cloudflare Tunnel 進來的請求不會喚醒機器** | 機器暫停後，`esg.finrod.xyz` 會顯示錯誤頁，必須由你手動喚醒 |
| 位置 | 無法指定區域 | 假資料可接受；正式環境另案 |

**結論**：免費方案適合「驗收前由你喚醒、驗收完就讓它暫停」的使用方式，不適合讓同事隨時開網址看。如果希望隨時可用，需要升級付費方案（可關閉閒置暫停，最長運行時間預設不限），費用請見 [Instabox 定價](https://instabox.sh/pricing)。

### 選項

| 選項 | 作法 | 取捨 |
|---|---|---|
| **A. 先用免費方案，手動喚醒（階段 0 建議）** | 驗收前執行喚醒指令，使用完讓它自動暫停 | 零成本；網址不是隨時可用；暫停後由你處理 |
| B. 升級付費方案 | 關閉閒置暫停、最長運行設為不限 | 網址隨時可用；有月費與用量費 |
| C. 改用 Instabox「公開連接埠」加 Cloudflare Worker 轉送 | 請求會自動喚醒機器 | 有冷卻期仍無法喚醒；且公開連接埠網址存在，需用私有權杖保護；架構較複雜，不建議 |

我建議階段 0 先採選項 A，確認需求後再決定是否升級。

### 暫停後如何恢復
- 閒置暫停：執行喚醒指令即可，程序與記憶體保留，連接器與網站會自動恢復。
- 因最長運行而暫停：需等冷卻結束再喚醒，文件說明冷卻剩餘時間可在機器狀態查到。
- 停止（非暫停）後程序會結束：因此連接器與網站都必須設為**開機自動啟動**（連接器用 systemd 服務；容器設 `restart: unless-stopped`）。

## 5. 還沒驗證的風險

| 風險 | 說明 | 如何驗證 |
|---|---|---|
| 對外 7844 埠 | Cloudflare 連接器需要對外連到 7844 埠（TCP 或 UDP）。Instabox 文件沒有說明是否允許，且有自動濫用偵測（含對外網路流量的檢查） | 機器開通後先測試對外連線，連不上就不能用 Tunnel，改走選項 C |
| CI 與測試站搶同一個運行名額 | 免費方案同時只有 1 台運行中 | 實際觀察 CI 是否排隊；必要時避免在驗收時段跑 CI |
| 20 GiB 磁碟 | 預設根碟約 16 GiB，資料碟 `/data` 為 20 GiB，且免費方案固定不可擴充 | 先量測 Docker 映像與資料大小；用 `/data` 存放映像與資料 |
| 方案內容變動 | 免費方案的限制可能調整 | 開通前再讀一次官方文件 |

## 6. 機器端開通步驟（由你執行）

> 請在機器內執行；`instabox ssh <機器名>` 進入。以下步驟依 Cloudflare 官方文件整理。**Tunnel 金鑰只在機器上使用，不貼給我、不寫入儲存庫。**

1. **確認對外連線**（驗證第 5 節的風險）：
   ```bash
   timeout 5 bash -c '</dev/tcp/region1.v2.argotunnel.com/7844' && echo "7844 可連" || echo "7844 不通"
   ```
   不通就停下，回報我，改走其他方案。
2. **安裝 cloudflared**（Debian）：
   ```bash
   sudo mkdir -p --mode=0755 /usr/share/keyrings
   curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg | sudo tee /usr/share/keyrings/cloudflare-main.gpg >/dev/null
   echo "deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared any main" | sudo tee /etc/apt/sources.list.d/cloudflared.list
   sudo apt-get update && sudo apt-get install -y cloudflared
   ```
3. **取得連線金鑰並啟動服務**：到 Cloudflare 後台 Zero Trust → Networks → Tunnels，點選 `xiyue-esg-test`，在「Install connector」取得金鑰，然後在機器上執行（把 `<金鑰>` 換成實際值）：
   ```bash
   sudo cloudflared service install <金鑰>
   ```
4. **確認連線**：後台的 Tunnel 狀態變為「Healthy」。
5. **暫時用簡單網頁測試**（不是正式應用）：
   ```bash
   mkdir -p ~/hello && echo "esg test ok" > ~/hello/index.html && cd ~/hello && sudo python3 -m http.server 80
   ```
   開 `https://esg.finrod.xyz`，應看到 `esg test ok`。測完以 Ctrl-C 結束。
6. 回報我：7844 是否可連、Tunnel 是否 Healthy、測試頁是否顯示。我再繼續。

## 7. 存取控管（建議在放任何東西之前做）

網站目前一旦連線就是公開網址。建議用 **Cloudflare Access** 在 Cloudflare 邊緣先擋一層，只有受邀的人才能進到登入頁：

- 建立一個 Access 應用程式保護 `esg.finrod.xyz`，原則為「電子郵件在允許清單內」，以單次密碼信驗證。
- 清單先放你自己，驗收時再加同事。
- 好處：不需要 IP 白名單；網站本身的登入失敗與節流不會被外界掃描；Tunnel 沒有其他入口可繞過。
- 需要你提供**允許進入的信箱清單**，我才能建立（我不會自行假設）。

## 8. 部署方式

我連不到你的機器（沒有 SSH 私鑰，也不應該持有）。部署由機器端執行，採用 Instabox 的方式：

| 方式 | 作法 |
|---|---|
| **A. 手動（階段 0 採用）** | 你 `instabox ssh` 進機器，執行儲存庫內的部署腳本：拉取指定版本、備份資料、建置、遷移、健康檢查，失敗自動回滾 |
| B. 從 CI 部署 | GitHub Actions 的某個步驟呼叫 Instabox CLI 對測試機器執行同一個部署腳本。需要在 GitHub 設定 Instabox 的 API 金鑰作為機密。若機器因暫停而無法喚醒，部署步驟會失敗並提示 |

另外，**測試與品質檢查（Pint、Larastan、Pest）放在 CI**：Instabox 可讓 GitHub Actions 的工作在它的暫時機器上執行（`runs-on: instabox-medium`）。階段 0 的 CI 工作流程會使用這個標籤；若 CI 因免費方案名額排隊，改回 GitHub 預設的執行環境。

我要知道部署結果的方式：你貼出腳本輸出；或接受我在測試環境放一個只回「正常或異常」的健康檢查網址，並請你把 `esg.finrod.xyz` 加入我這邊的網路白名單（目前這個環境連不到它）。

## 9. 備份與還原

- **應用層**：每日以 `VACUUM INTO` 產生資料庫副本，連同私有儲存目錄打包，保留 30 份（PHASE0 WP0.10），存放於 `/data`。
- **平台層**：Instabox 有快照功能（免費方案最多 5 個）。重大變更前手動存一個快照。
- **異地**：備份定期複製到你另一處的儲存位置（例如你現有的 NAS）。測試站可後補；正式環境必做。
- **還原演練**：階段 0 驗收時須實際做一次完整還原並記錄步驟與耗時。
- 注意：磁碟僅 20 GiB 且不可擴充，備份保留份數與大小要配合；超過時優先清理舊備份。

## 10. 營運

- 維運人：使用者本人。
- 系統更新：開啟自動安全更新，每月檢視。
- 日誌：輪替，保留 30 天，不記錄密碼與個資。
- 健康檢查：只回報正常或異常，不揭露版本與內部資訊。
- 稽核鏈驗證每月一次。
- 測試環境頁尾顯示「測試環境，僅限展示資料」橫幅。

## 11. 風險彙整

| 風險 | 對策 |
|---|---|
| 免費方案自動暫停，網址時好時壞 | 選項 A：驗收前手動喚醒；或升級付費 |
| 7844 埠被擋，Tunnel 無法使用 | 先驗證；不行就改架構（選項 C）並更新本文 |
| 被當成真實資料存放處 | 第 2 節原則與橫幅 |
| 網址被掃描攻擊 | Cloudflare Access 加應用程式登入節流與稽核 |
| 機器重啟後服務沒起來 | systemd 與容器重啟政策；開機自動啟動檢查納入驗收 |
| 磁碟滿 | 控制備份份數；每日檢查磁碟使用率 |
| Tunnel 金鑰外洩 | 只在機器與 Cloudflare 後台；懷疑外洩就在後台輪換並重新安裝 |

## 12. 需要你決定或提供

1. 你用的 Instabox 是**免費方案**嗎？階段 0 先採選項 A（手動喚醒）可以嗎？還是要直接升級付費？
2. Access 的**允許信箱清單**：先放哪些人？
3. 部署採 A（手動腳本）還是之後做 B（CI 部署）？
4. 是否同意測試環境放健康檢查網址，並把 `esg.finrod.xyz` 加入我的網路白名單？
5. 機器的名稱（`instabox ls` 看到的名稱），以及用的範本，方便我寫對指令。
