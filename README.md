# 画像アップロードAPI

認証と検証機能を備えた安全なPHPベースのREST API for画像アップロード。

## 機能

- 🔐 **APIキー認証** - 安全なアクセス制御
- 📁 **複数の画像形式対応** - JPEG、PNG、GIF、WebP対応
- 🛡️ **セキュリティ検証** - ファイル形式とサイズの検証
- 📏 **ファイルサイズ制限** - 設定可能な最大ファイルサイズ（デフォルト：5MB）
- 🌐 **CORS対応** - クロスオリジンリソース共有が有効
- 📊 **詳細なレスポンス** - 画像の寸法を含むメタデータを返す
- 🔒 **安全なファイル処理** - ランダムなファイル名生成と適切なパーミッション

## 要件

- PHP 7.4以上
- PHP拡張機能: `fileinfo`、`gd`（画像検証用）
- Webサーバー（Apache/Nginx）
- アップロードディレクトリの書き込み権限

## インストール

1. **APIファイルをクローンまたはダウンロード** してWebサーバーに配置
2. **設定ファイルを作成** (`config.php`):
   ```php
   <?php
   define('API_KEY', 'your-secret-api-key-here');
   ?>
   ```
3. **適切な権限を設定**:
   ```bash
   chmod 755 /path/to/api/directory
   chmod 644 *.php
   ```
4. **アップロードディレクトリ** (`imgs/`) がWebサーバーによって書き込み可能であることを確認

## APIエンドポイント

```
POST /path/to/your/api/upload.php
```

## 認証

APIは`Authorization`ヘッダーを使用したAPIキー認証を使用します：

```
Authorization: your-api-key-here
```

またはBearerトークン形式：
```
Authorization: Bearer your-api-key-here
```

## リクエスト形式

- **メソッド**: `POST`
- **Content-Type**: `application/octet-stream` または `image/*`
- **ボディ**: 生の画像データ（バイナリ）

## レスポンス形式

### 成功レスポンス (200)
```json
{
  "status": "success",
  "message": "Image uploaded successfully",
  "url": "https://example.com/ImgAPI/imgs/a1b2c3d4e5f6g7h8.jpg",
  "filename": "a1b2c3d4e5f6g7h8.jpg",
  "size": 245760,
  "type": "image/jpeg",
  "dimensions": {
    "width": 1920,
    "height": 1080
  }
}
```

### エラーレスポンス
```json
{
  "status": "error",
  "message": "エラーの詳細説明"
}
```

## HTTPステータスコード

| コード | 説明 |
|--------|------|
| 200 | 成功 |
| 400 | 不正なリクエスト（APIキーなし、画像データなし、無効な形式） |
| 403 | 禁止（無効なAPIキー） |
| 405 | 許可されていないメソッド |
| 413 | ペイロードが大きすぎます |
| 500 | 内部サーバーエラー |

## 使用例

### JavaScript (Fetch API)
```javascript
const uploadImage = async (imageFile, apiKey) => {
  try {
    const response = await fetch('/path/to/api/upload.php', {
      method: 'POST',
      headers: {
        'Authorization': apiKey,
        'Content-Type': 'application/octet-stream'
      },
      body: imageFile
    });

    const result = await response.json();
    
    if (result.status === 'success') {
      console.log('アップロード成功:', result.url);
      return result;
    } else {
      console.error('アップロードエラー:', result.message);
    }
  } catch (error) {
    console.error('ネットワークエラー:', error);
  }
};

// 使用方法
const fileInput = document.getElementById('fileInput');
fileInput.addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (file) {
    const result = await uploadImage(file, 'your-api-key');
  }
});
```

### cURL
```bash
curl -X POST \
  -H "Authorization: your-api-key" \
  -H "Content-Type: application/octet-stream" \
  --data-binary @image.jpg \
  https://example.com/path/to/api/upload.php
```

### Python
```python
import requests

def upload_image(file_path, api_key, api_url):
    headers = {
        'Authorization': api_key,
        'Content-Type': 'application/octet-stream'
    }
    
    with open(file_path, 'rb') as f:
        response = requests.post(api_url, headers=headers, data=f)
    
    return response.json()

# 使用例
result = upload_image('image.jpg', 'your-api-key', 'https://example.com/path/to/api/upload.php')
print(result)
```

## 設定

### ファイルサイズ制限の変更
`upload.php`の`$maxFileSize`変数を編集：
```php
$maxFileSize = 10 * 1024 * 1024; // 10MBに変更
```

### 対応形式の変更
`$allowedMimeTypes`配列を編集：
```php
$allowedMimeTypes = ['image/jpeg', 'image/png']; // JPEGとPNGのみ
```

### アップロードディレクトリの変更
`$imgDir`変数を編集：
```php
$imgDir = 'uploads/images/'; // 新しいディレクトリ
```

## セキュリティ考慮事項

- ✅ **強力なAPIキーを使用** - 長くてランダムな文字列
- ✅ **HTTPS使用を推奨** - 本番環境では必須
- ✅ **定期的なログ監視** - 不審なアクティビティをチェック
- ✅ **レート制限の実装を検討** - 大量リクエストの防止
- ✅ **古いファイルの定期削除** - ストレージ管理

## トラブルシューティング

### よくある問題

1. **「Failed to create upload directory」エラー**
   - アップロードディレクトリの書き込み権限を確認
   - `chmod 755 imgs/` を実行

2. **「Invalid image format」エラー**
   - ファイルが対応形式（JPEG、PNG、GIF、WebP）であることを確認
   - ファイルが破損していないことを確認

3. **「File size exceeds limit」エラー**
   - ファイルサイズを確認（デフォルト5MB制限）
   - 必要に応じて制限値を調整

4. **「Invalid API Key」エラー**
   - APIキーが正しいことを確認
   - `config.php`ファイルが正しく設定されていることを確認

## ライセンス

MIT License - 詳細はLICENSEファイルを参照してください。

## 貢献

バグ報告や機能要求は、GitHubのIssuesまでお願いします。

## サポート

技術的な質問やサポートが必要な場合は、[連絡先情報]までお問い合わせください。