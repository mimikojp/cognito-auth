# cognito-auth

[![CircleCI](https://circleci.com/bb/adcateinc/cognito-auth.svg?style=svg&circle-token=32fbf5dd697356a52d9323971e77f0dcd5ac48be)](https://circleci.com/bb/adcateinc/cognito-auth)

Adcate社内ツールの認証関連を詰め込んだライブラリです。  
__v1.6.0現在、Laravel v6.x~v9.xに対応しています。__

---

## Requirements

- Laravel v6.x|v7.x|v8.x|v9.x
- gmp拡張

## Usage

1. `composer.json`にgithubリポジトリを追加してください
    ```json
    {
       "repositories": [
           {
               "type": "vcs",
               "url": "https://github.com/adcate/cognito-auth"
           }
       ]
    }
    ```
1. ライブラリを依存に追加してください
    ```
    $ composer require adcate/cognito-auth
    ```
1. 設定ファイルをコピーしてください
    ```
    $ php artisan vendor:publish --provider=Adcate\\CognitoAuth\\Provider\\ServiceProvider
    ```
1. 環境変数に以下の内必要な項目を追加してください

    key | description
    --- | ---
    COGNITO_GROUP | 所属を検証したいグループの名前 
    COGNITO_REGION | JWTの署名情報を参照したいユーザープールの所属リージョン
    COGNITO_REDIRECT_AUTHENTICATE | Cognitoからの認証コールバック後、APIがフロントエンドにリダイレクトさせるときに使用するURL
    COGNITO_REDIRECT_URI | Cognitoに登録したクライアントのリダイレクトURI
    COGNITO_OAUTH_API_BASE_URI | Cognitoドメインのプレフィックス
    COGNITO_USER_POOL_ID | 認証させたいユーザーを含んだユーザープールのID
    COGNITO_CLIENT_ID | Cognitoに登録したクライアントのID
    COGNITO_CLIENT_SECRET |  Cognitoに登録したクライアントのSecret


※ AutoDiscoveryによってServiceProviderの解決は自動で行われるため、 `config/app.php`への追記は不要です。

## Middlewares

adcate/cognito-authは以下のミドルウェアを提供します。

- VerifyToken
    - トークンの署名・発行者・有効期限・認可状態を検証します
- RenewToken
    - トークンの署名・発行者・認可状態を検証したうえで、  
      有効期限を過ぎていた場合アクセストークンの更新を行います

## Endpoints

ServiceProviderを読み込んでいる場合、以下のルートが自動で適用されます。

Path | Method | Description
--- | --- | ---
/auth/signIn | GET | Cognitoの認証エンドポイントにリダイレクトします
/auth/callback | GET | Cognitoからのコールバックを処理します
/auth/renew | GET | 認証トークンの更新を行います
/auth/signOut | GET | サーバー側で保持しているリフレッシュトークンを削除します

また、認証トークンのやり取りをするに当たりデフォルトでは以下の方法を採っています。

- cognito経由の初回認証時
    - URLフラグメントに付与
        - ex) `http://example.com/#access-token-string`
- アクセストークンの更新時
    - レスポンスの独自ヘッダーに付与
        - ex) `X-Renew-Authorization: renewed-access-token-string`

## Repositories

cognito-authでは取得した `jwks.json` やリフレッシュトークン等をデフォルトでキャッシュに保存します。

生存期間は以下の通りです。

Type | Lifetime | Overview
--- | --- | ---
State | 5minutes | OAuthのAuthorizationGrantFlowで用いるStateです
JWKSet | 12hours | トークンの署名を検証するための各種情報を保持しています
RefreshToken | 3days | リフレッシュトークンです

キャッシュ以外の方法で保持したい場合や、生存期間の変更などは、  
各リポジトリインターフェースを実装し、LaravelのDIコンテナに登録してください。
