# プロジェクトアーキテクチャ設計

## 概要

llhttp FFI バインディングのアーキテクチャ設計。調査結果に基づいてクラス構造とインターフェースを定義する。

## クラス構造

### コアレイヤー

#### 1. `Llhttp\Ffi\Binding`
**役割**: llhttp C ライブラリの直接的なFFIラッパー
```php
class Binding
{
    private FFI $ffi;
    
    public static function getInstance(): self
    public function initParser(int $type): FFI\CData  // llhttp_t*
    public function createSettings(): FFI\CData      // llhttp_settings_t*
    public function execute(FFI\CData $parser, string $data): int
    public function resume(FFI\CData $parser): void
    public function getErrorCode(FFI\CData $parser): int
    public function getErrorMessage(int $errno): string
}
```

#### 2. `Llhttp\Ffi\CallbackManager`
**役割**: C コールバックとPHPクロージャーの橋渡し
```php
class CallbackManager
{
    private array $callbacks = [];
    private array $cCallbacks = [];
    
    public function setCallback(string $event, callable $callback): void
    public function getCCallback(string $event): FFI\CData
    public function handleCallback(string $event, ...$args): int
}
```

### パーサーレイヤー

#### 3. `Llhttp\Parser`
**役割**: メインのパーサークラス（ユーザー向けAPI）
```php
class Parser
{
    public const TYPE_REQUEST = 0;
    public const TYPE_RESPONSE = 1;
    
    private Binding $binding;
    private CallbackManager $callbackManager;
    private FFI\CData $parser;
    private FFI\CData $settings;
    private bool $paused = false;
    
    public function __construct(int $type)
    public function on(string $event, callable $callback): self
    public function execute(string $data): void
    public function pause(): void
    public function resume(): void
    public function reset(): void
    public function getHttpMajor(): int
    public function getHttpMinor(): int
    public function getMethod(): int
    public function getStatusCode(): int
}
```

#### 4. `Llhttp\Events`
**役割**: イベント名の定数定義
```php
class Events
{
    public const MESSAGE_BEGIN = 'messageBegin';
    public const HEADER_FIELD = 'header_field';
    public const HEADER_VALUE = 'header_value';
    public const HEADERS_COMPLETE = 'headersComplete';
    public const BODY = 'body';
    public const MESSAGE_COMPLETE = 'messageComplete';
}
```

### エラーハンドリング

#### 5. `Llhttp\Exception`
**役割**: パーサー固有の例外
```php
class Exception extends \Exception
{
    private int $llhttpErrorCode;
    private ?string $errorPosition;
    
    public function __construct(string $message, int $llhttpErrorCode = 0, ?string $errorPosition = null)
    public function getLlhttpErrorCode(): int
    public function getErrorPosition(): ?string
}
```

#### 6. `Llhttp\ErrorCodes`
**役割**: llhttp エラーコードの定数
```php
class ErrorCodes
{
    public const HPE_OK = 0;
    public const HPE_INTERNAL = 1;
    public const HPE_STRICT = 2;
    // ... その他のエラーコード
}
```

## FFI 定義構造

### FFI 定義ファイル (`src/Ffi/llhttp.h`)
```c
// llhttp の必要な構造体と関数定義
typedef struct llhttp__internal_t llhttp_t;
typedef struct llhttp_settings_s llhttp_settings_t;

typedef int (*llhttp_data_cb)(llhttp_t*, const char *at, size_t length);
typedef int (*llhttp_cb)(llhttp_t*);

struct llhttp_settings_s {
    llhttp_cb on_message_begin;
    llhttp_data_cb on_url;
    llhttp_data_cb on_status;
    llhttp_data_cb on_header_field;
    llhttp_data_cb on_header_value;
    llhttp_cb on_headers_complete;
    llhttp_data_cb on_body;
    llhttp_cb on_message_complete;
};

void llhttp_init(llhttp_t* parser, int type, const llhttp_settings_t* settings);
int llhttp_execute(llhttp_t* parser, const char* data, size_t len);
void llhttp_resume(llhttp_t* parser);
int llhttp_finish(llhttp_t* parser);
// ... その他の関数
```

## メモリ管理戦略

### 1. パーサーライフサイクル
- `Parser` コンストラクタで `llhttp_t` と `llhttp_settings_t` を作成
- PHP のガベージコレクションで自動管理
- デストラクタで明示的にクリーンアップ

### 2. コールバック管理
- PHPクロージャーを `CallbackManager` で保持
- C コールバック関数ポインタも同時に管理
- パーサー削除時に全て解放

### 3. データハンドリング
- C から渡されるデータは即座にPHP文字列に変換
- ポインタ参照は保持しない

## エラーハンドリング戦略

### 1. C エラーの変換
```php
private function checkError(int $result): void
{
    if ($result !== 0) {
        $errorCode = $this->binding->getErrorCode($this->parser);
        $errorMessage = $this->binding->getErrorMessage($errorCode);
        throw new Exception($errorMessage, $errorCode);
    }
}
```

### 2. コールバックエラー
- コールバック内で例外が発生した場合は `HPE_USER` を返す
- 例外情報を保存して後で再throwする

### 3. FFI エラー
- FFI 関連のエラーは適切にラップする
- ライブラリロードエラーは初期化時に検出

## 設定とプリロード

### プリロード対応
```php
// preload/llhttp.php
FFI::load(__DIR__ . '/../src/Ffi/llhttp.h');
```

### 環境設定
- 開発環境: CLI での動的ロード
- 本番環境: プリロード推奨
- 共有ライブラリパスの設定可能

## パフォーマンス考慮事項

### 1. FFI オーバーヘッド最小化
- 構造体アクセスを最小限に
- バッチ処理での効率化
- コールバック頻度の最適化

### 2. メモリ使用量
- 不要なデータコピー回避
- ストリーミング処理対応
- 大きなHTTPメッセージの分割処理

## 拡張性

### 1. PSR-7 統合
```php
// 将来的な拡張
class Psr7Builder
{
    public static function buildRequest(Parser $parser, array $headers, string $body): RequestInterface
    public static function buildResponse(Parser $parser, array $headers, string $body): ResponseInterface
}
```

### 2. 非同期対応
- ReactPHP ストリーム対応
- Swoole 統合の可能性

この設計により、高性能でかつ PHP らしい使いやすいHTTPパーサーを実現する。