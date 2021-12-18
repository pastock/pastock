# Pastock

做好玩的，有需求可以發 [issue](https://github.com/pastock/pastock/issues/new)，我有空再來寫。

## ETF 查詢與交集比對功能

使用下面指令可以查 ETF 的股票權重比例，如 0050：

```
php pastock etf 0050
```

ETF 可以帶多筆，這樣程式把持有股票做交集，如 0050 與 0056 交集：

```
php pastock etf 0050 0056
```

如果想確認股票是否有[財政部](https://www.mof.gov.tw/)持股，可以加 `--with-mof` 參數：

> 注意，因為持股名單多，所以比對會比較久。

```
php pastock etf --with-mof 0050 0056
```

## 參考資料

* https://openapi.twse.com.tw/
* https://github.com/mlouielu/twstock
* https://openapi.twse.com.tw/v1/opendata/t187ap14_L
