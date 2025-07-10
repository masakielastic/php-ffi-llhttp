llhttp の FFI バインディング

オブジェクト指向＋イベント駆動型

$parser = new Llhttp\Ffi\Parser(Llhttp\Parser::TYPE_REQUEST);
$parser->on('messageBegin', function() { /* 開始 */ });
$parser->on('header', function(string $name, string $value) { /* ヘッダ１行 */ });
$parser->on('headersComplete', function(array $headers) { /* 全ヘッダ */ });
$parser->on('body', function(string $chunk) { /* ボディ断片 */ });
$parser->on('messageComplete', function() { /* 完了 */ });

// データを与えてパース
$parser->execute($rawData);
メリット

PHP 的に親しみやすい。

PSR-7 リクエスト／レスポンスオブジェクトの生成と連携しやすい。

ポイント

Parser::TYPE_REQUEST／TYPE_RESPONSE を指定

ステート管理用に pause()／resume() メソッドを用意

エラー時は例外（Llhttp\Exception）を投げる

