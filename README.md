作って見たものの、クローラーについての理解が浅かったため利用していません。
実行コードや設定ファイルも公開していないので、DLしても実行できません。

## 開発

crawlerクラステスト
```
./vendor/bin/testrunner phpunit -p vendor/autoload.php -a crawler
```

scrapingクラステスト
```
./vendor/bin/testrunner phpunit -p vendor/autoload.php -a scraper
```

PHP_CodeSnifferによるコードチェック(PSR2準拠)
```
./vendor/bin/phpcs ./crawler/ --standard=PSR2
```

PHP_CodeSnifferによるコード修正
```
./vendor/bin/phpcbf ./crawler/
```