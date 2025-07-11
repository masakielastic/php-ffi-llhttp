# PHP FFI llhttp

高性能なHTTPパーサーライブラリ「llhttp」のPHP FFIバインディング。オブジェクト指向でイベント駆動型のAPIを提供します。

## 特徴

- **高性能**: C言語で書かれたllhttpライブラリを使用
- **イベント駆動**: 解析の各段階でコールバックを実行
- **オブジェクト指向**: PHP らしい使いやすいAPI
- **メモリ効率**: ストリーミング処理対応
- **型安全**: PHP 8.0+ の型宣言を活用

## 要件

- PHP 8.0 以上
- FFI 拡張が有効
- llhttp ライブラリ (libllhttp.so)

## インストール

```bash
composer require masakielastic/php-ffi-llhttp
```

### llhttp ライブラリのインストール

```bash
# Ubuntu/Debian
sudo apt-get install libllhttp-dev

# または手動ビルド
git clone https://github.com/nodejs/llhttp.git
cd llhttp
npm run build
# build/libllhttp.so を適切な場所に配置
```

### ライブラリパスの設定

llhttpライブラリの場所は以下の方法で指定できます（優先度順）：

1. **環境変数 `LLHTTP_LIBRARY_PATH`**（推奨）
   ```bash
   export LLHTTP_LIBRARY_PATH=/path/to/libllhttp.so
   php your_script.php
   ```

2. **`LD_LIBRARY_PATH` 環境変数**
   ```bash
   export LD_LIBRARY_PATH=/path/to/lib:$LD_LIBRARY_PATH
   php your_script.php
   ```

3. **`PKG_CONFIG_PATH` からの自動検出**
   ```bash
   export PKG_CONFIG_PATH=/path/to/pkgconfig:$PKG_CONFIG_PATH
   ```

4. **相対パス**（開発時）
   - `./libllhttp.so`
   - `./build/libllhttp.so`
   - `../llhttp/build/libllhttp.so`

5. **システム標準パス**
   - `/usr/local/lib/libllhttp.so`
   - `/usr/lib/libllhttp.so`
   - その他標準的なライブラリディレクトリ

コード内で直接指定することも可能です：

```php
$parser = new Parser(Parser::TYPE_REQUEST, '/custom/path/to/libllhttp.so');
```

## 基本的な使い方

### HTTPリクエストの解析

```php
<?php
use Llhttp\Parser;
use Llhttp\Events;

$parser = new Parser(Parser::TYPE_REQUEST);

$parser->on(Events::MESSAGE_BEGIN, function () {
    echo "解析開始\n";
});

$parser->on(Events::URL, function (string $url) {
    echo "URL: {$url}\n";
});

$parser->on(Events::HEADERS_COMPLETE, function () use ($parser) {
    echo "ヘッダー解析完了\n";
    echo "メソッド: " . $parser->getMethodName() . "\n";
    foreach ($parser->getHeaders() as $name => $value) {
        echo "{$name}: {$value}\n";
    }
});

$parser->on(Events::BODY, function (string $chunk) {
    echo "ボディ: {$chunk}\n";
});

$parser->on(Events::MESSAGE_COMPLETE, function () {
    echo "解析完了\n";
});

$request = "GET /api/users HTTP/1.1\r\n" .
           "Host: example.com\r\n" .
           "Content-Length: 0\r\n" .
           "\r\n";

$parser->execute($request);
$parser->finish();
```

### HTTPレスポンスの解析

```php
<?php
use Llhttp\Parser;
use Llhttp\Events;

$parser = new Parser(Parser::TYPE_RESPONSE);

$parser->on(Events::STATUS, function (string $status) {
    echo "ステータス: {$status}\n";
});

$parser->on(Events::HEADERS_COMPLETE, function () use ($parser) {
    echo "ステータスコード: " . $parser->getStatusCode() . "\n";
});

$response = "HTTP/1.1 200 OK\r\n" .
            "Content-Type: application/json\r\n" .
            "\r\n" .
            '{"message": "success"}';

$parser->execute($response);
$parser->finish();
```

## イベント

利用可能なイベントは以下の通りです：

- `Events::MESSAGE_BEGIN` - 解析開始
- `Events::URL` - URL部分の解析 (リクエスト用)
- `Events::STATUS` - ステータス部分の解析 (レスポンス用)
- `Events::HEADER_FIELD` - ヘッダーフィールド名
- `Events::HEADER_VALUE` - ヘッダー値
- `Events::HEADERS_COMPLETE` - 全ヘッダーの解析完了
- `Events::BODY` - ボディデータの断片
- `Events::MESSAGE_COMPLETE` - メッセージ解析完了

## パーサーメソッド

```php
// パーサー情報
$parser->getType();           // Parser::TYPE_REQUEST または Parser::TYPE_RESPONSE
$parser->getHttpMajor();      // HTTP メジャーバージョン
$parser->getHttpMinor();      // HTTP マイナーバージョン

// リクエスト専用
$parser->getMethod();         // HTTPメソッド番号
$parser->getMethodName();     // HTTPメソッド名

// レスポンス専用
$parser->getStatusCode();     // ステータスコード

// ユーティリティ
$parser->shouldKeepAlive();   // Keep-Alive接続かどうか
$parser->messageNeedsEof();   // EOF が必要かどうか
$parser->getHeaders();        // 収集されたヘッダー

// 制御
$parser->pause();             // 解析を一時停止
$parser->resume();            // 解析を再開
$parser->reset();             // パーサーをリセット
```

## ストリーミング処理

大きなHTTPメッセージも効率的に処理できます：

```php
$parser = new Parser(Parser::TYPE_REQUEST);

$bodyData = '';
$parser->on(Events::BODY, function (string $chunk) use (&$bodyData) {
    $bodyData .= $chunk;
    
    // チャンクごとに処理することも可能
    echo "受信: " . strlen($chunk) . " バイト\n";
});

// データを分割して送信
$chunks = str_split($largeHttpMessage, 1024);
foreach ($chunks as $chunk) {
    $parser->execute($chunk);
}
$parser->finish();
```

## エラーハンドリング

```php
try {
    $parser->execute($invalidHttpData);
} catch (\Llhttp\Exception $e) {
    echo "パーサーエラー: " . $e->getMessage() . "\n";
    echo "エラーコード: " . $e->getLlhttpErrorCode() . "\n";
    
    if ($pos = $e->getErrorPosition()) {
        echo "エラー位置: {$pos}\n";
    }
}
```

## 開発

### テスト実行

```bash
composer test
```

### コード品質チェック

```bash
composer check  # lint + stan + test
composer lint   # コーディング規約チェック
composer stan   # 静的解析
```

## ライセンス

MIT License

## 貢献

プルリクエストやイシューの報告を歓迎します。

## 関連リンク

- [llhttp (Node.js HTTP parser)](https://github.com/nodejs/llhttp)
- [PHP FFI Documentation](https://www.php.net/manual/en/book.ffi.php)