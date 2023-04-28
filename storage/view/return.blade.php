<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title>支付结果</title>
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
        }
        html, body {
            width: 100%;
            height: 100%;
            background-color: #fff;
        }
        .page {
            padding: 60px 30px 30px;
            text-align: center;
        }
        .pay-result {
            display: none;
            text-align: center;
        }
        .pay-result .title {
            font-size: 18px;
        }
        .pay-result .icon {
            margin: 20px auto 0;
            width: 100px;
            height: 100px;
        }
        .pay-result .icon img {
            width: 100%;
            height: 100%;
        }
        .button {
            margin: 40px auto 0;
            width: 240px;
            color: #333;
            line-height: 42px;
            border-radius: 6px;
            border: 1px solid #999;
            background-color: #fff;
        }
        .button:active {
            background-color: #f6f6f6;
        }
    </style>
</head>
<body>
<div class="page">
    <div style="min-height: 50vh;">
        <div id="paySuccess" class="pay-result">
            <div class="title">支付成功</div>
            <div class="icon">
                <img src="data:image/png;base64,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" />
            </div>
        </div>
        <div id="payFail" class="pay-result">
            <div class="title">支付失败</div>
            <div class="icon">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAMAAAD04JH5AAAAkFBMVEUAAAD2bm73bm7ycHD2bm71bm72bm72bm74b2/3bm72bm72bm72bm72bm72bm72b2/2bm72bm73bW3zbGz2bW33bW33bm73b2/2b2/3bm73bW31bm71bm73bW33bW32bm72b2/2b2/1bm71bGz2bm71bm73bm72aGj2bm71bm76cnL3bm72bGzzamr2b2/2bm7eyrbFAAAAL3RSTlMAzvYKsYDJtE0g08Sr42yOdOwUEKcwu5g23tlnYTy/oCgcnSXwSrcF5nsYf1YpV1QdtZQAAAQISURBVHjaxNhpk6IwEAbgN0RUEJBDvHXwHOfa/v//bqszU6YoZE0YYJ+v45i2u9OEoBHvEi/2Q391OzvO+bbyh/tFfPHQC/l59B16yPEXF4lO5fGVnhhEOTqydIflH5yEazGZiHWYlFMydJcdr74aTbdLCWhyuZ2OVt3FMB4QY/7xM0ONbDwVuhbj9pd3ZtsvPHHazhwdQqvLz6MDjByieXsheAtSQjeHhfwjJGXh4VeikNg6TmEpjdfEwgjN7UbERCTRgIwEsdEODb0RS2I0FifE3tBEPiJ2LPALxZHYKIe1jQp+MsYvjScqjRtYeiE2RQumxF5gJVBzZ4NWbBw1RmBBlV/s0JKdUN8HYz5/fpaiNanKaAhDZ8Py29cURlTFXKCDCM4wELa/vt5XPp4SnazPXDWS8MTcbs/aRxAY5ClAR9ynv27T3fp6KG5QK0+6XJ/N+LmQ/3MAihQdSsW9EWue/84Ondo59eeDnS5QN3Sb7WoLMEXnpnVFiPj8gR7wCSVChccjeIwejPnBWD2tL/j8h17wOXHxKKykQC+K5EGy+f0rRk9ifmurJkCgN6KSgoHqzN5EOgU6AWuJ3sg1p8CyA7rsgiXvzBQ9SsPSQP5Qp7BeuaW5z/dPOXqV813XK3QF5ugZHz7/QKcjQs94J850BZwDjLjBzJWoJfnvMHJwiBzvXo4ZjBATBWoUgpj58XBzT8bWtHWZf8JDJ58UFya2fES/T6GTcdBMeHjAE/QtgIkvnr6qbvyN5llThh4qvCFZBQD/54dfzI+Crr6MPlSaite3KQGOP6VfmB/FpCDGJu8oeZ8QY0LCyCc3wU8mMpgpfB1BCmipXt8vYCbjD3/fR6xg6qRzsM9wl+317z/B1ErdmXgGL+3VVmPz7L7+nHR72l2FeboH7SO4Sijyaru+fkW5IFa9aOGgyz2Sav2RbowDLPAoitUmWMJIteEHr8DroLQ1LCzV+wG3j4SVdK8jAPT6+xRWJP+PehTCVLXpgqDUlHYcoiGPgQS2sitVXDPYSoh83owh7JQbTzektZBH0I2fSfbkgEoGEvbWRDecG72T6ebX28Ge4Mtbp/G1REB3QeOrCuf/B/C3ejvGARAEgihq4x24w97/fkYLjZ2GwBvojQUKuzv/8y3gHyH/DflBxI9ifhnx65gXJLwk40UpL8t9Y8JbM96c8vacDyj8iIYPqfiYzg8q+aiWD6uvPaht1nrG9TmBBY9sfGjFYzsfXPLo1ofXPL73AANHODzE4jEeDjJ5lMvDbBzn80CjRzo91OqxXg82e7Tbw+0e7/eCg1c8vOTiNR8vOgWoXl5269D99lv3W114DFA+A6TXc7X6p/1Wez8/QXz+8PbV1e8E+X2A/n8AAMRt4khwjLYAAAAASUVORK5CYII=" />
            </div>
        </div>
    </div>
    <button id="back" class="button" type="button">返回</button>
</div>
<!-- uni 的 SDK -->
<script type="text/javascript" src="https://js.cdn.aliyun.dcloud.net.cn/dev/uni-app/uni.webview.1.5.2.js"></script>
<script type="text/javascript">
    function GetQueryString(name){
        var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if(r!=null)return  unescape(r[2]); return null;
    }
    // GetQueryString('phone')

    window.onload = function() {

        var status = {{$status}};
        if(status == 1) {
            document.getElementById('paySuccess').style.display = 'block';
        }else{
            document.getElementById('payFail').style.display = 'block';
        }

    }

    document.getElementById('back').addEventListener('click', function() {
        uni.postMessage({
            event: 'back'
        });
    })

    // 待触发 `UniAppJSBridgeReady` 事件后，即可调用 uni 的 API。
    /* document.addEventListener('UniAppJSBridgeReady', function() {

    }); */
</script>
</body>
</html>
