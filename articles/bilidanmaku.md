---
layout: page
permalink: "bilidanmaku.html"
title:  "B站弹幕“匿名”？"
---

## 事前唠个叨

<del>发布完上一个BiliDown之后简直就是狂欢，微博粉丝蜂拥而至，每天的乐趣就是看我又涨了多少粉。</del>

然后就在我的新粉丝里发现了麦曜，简介里写着苦逼程序猿，所以必定点进去看了。

不看不知道，一看吓一跳，原来这货已经把弹幕所谓的“匿名”给破解了！不用说，第一时间拉进我们的邪教~

同时我再组里也说了这件事，TYP和Beining姐都比较兴奋，于是我们当天就开始研究，第二天我就编了个Python版本的小程序，然后第三天TYP的js网页端也新鲜出炉了！

那么不扯这些废话了，继续往下看。

## 酷诶，咋做的？

`以下由 *用英文装逼又懒得要死的Beining* 撰写英文版，SuperFashi翻译。`

让我们来用人话说明白。

每一个b站的视频都有一个独特的aid，就是我们所谓的“av号”。

一个aid下面可以有很多分p视频，那么每一个aid下面的视频，就会被赋予一个独立的ID，也就是cid。

我们只要调用View的api，就可以用aid和分p号换取对应的cid。

操作详见[b站api](http://www.fuckbilibili.com/biliapi.html)

然后弹幕的XML文件就可以拿到了，在```http://comment.bilibili.com/{cid}.xml```。但是我们不能拿到历史弹幕。

比如其中的一行弹幕长这个样子：
{% highlight html %}
<d p="12.456999778748,1,25,16777215,1444811244,0,550e9706,1278188533">第二</d>
{% endhighlight %}

用中文说就是：

{% highlight html %}
<d p="时间,模式,字体大小,颜色,时间戳,弹幕池,用户ID的CRC32b加密,弹幕ID">content</d>
{% endhighlight %}

我们可以直接暴力破解这些CRC32b加密过的原内容；或者，我们的方法是生成一个从0-50000000的加密彩虹表并存到数据库内，这样将可以奇迹般地提高访问速度和服务器压力。

用户ID叫做mid。

那么用户的空间就是 ```http://space.bilibili.com/{mid}```。

#瞬 间 爆 炸

### python示例源码如下：

{% highlight python %}
#!/usr/bin/env python3
#Modified by SuperFashi

import sys
import gzip
import json
import hashlib
import re
import urllib.parse
import urllib.request
import xml.dom.minidom as minidom
import zlib
import random

USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.99 Safari/537.36'
APPKEY = '85eb6835b0a1034e'
APPSEC = '2ad42749773c441109bdc0191257a664'

def GetBilibiliUrl(url, findstr):
    regex_match = re.findall('http:/*[^/]+/video/av(\\d+)(/|/index.html|/index_(\\d+).html)?(\\?|#|$)',url)
    if not regex_match:
        return 'error2'
    aid = regex_match[0][0]
    pid = regex_match[0][2] or '1'

    cid_args = {'type': 'json', 'id': aid, 'page': pid}
    resp_cid = urlfetch('http://api.bilibili.com/view?'+GetSign(cid_args,APPKEY,APPSEC))
    resp_cid = dict(json.loads(resp_cid.decode('utf-8', 'replace')))
    cid = resp_cid.get('cid')

    resp_media = urlfetch('http://comment.bilibili.com/'+str(cid)+'.xml')
    dom = minidom.parseString(resp_media.decode('utf-8', 'replace'))
    inside = dom.getElementsByTagName("i")[0]
    chats = inside.getElementsByTagName("d")
    for chat in chats:
        if chat.childNodes[0].nodeValue.find(findstr)<0:
            continue
        dialogue = chat.childNodes[0].nodeValue
        timesec = float(chat.attributes["p"].value.split(',')[0])
        timesecaf = int(timesec) % 60
        timemin = int((int(timesec) - timesecaf) / 60)
        if len(str(timesecaf)) < 2:
            timesecaf = "0" + str(timesecaf)
        if len(str(timemin)) < 2:
            timemin = "0" + str(timemin)
        gethash = urlfetch('http://biliquery.typcn.com/api/user/hash/'+chat.attributes["p"].value.split(',')[6].lower())
        accu = dict(json.loads(gethash.decode('utf-8', 'replace')))
        iserror = accu.get('error')
        if iserror != 1:
            mid = accu.get('data')
            mid = mid[0]
            mid = mid.get('id')
        else:
            print('Not Found!')
            continue
        name = dict(json.loads(urlfetch('http://api.bilibili.com/userinfo?mid='+str(mid)).decode('utf-8', 'replace'))).get('name')
        print(name+'  '+str(mid)+'  '+dialogue+'  '+str(timemin)+':'+str(timesecaf))
    return
    
def GetSign(params,appkey,AppSecret=None):
    params['appkey']=appkey;
    data = "";
    paras = sorted(params)
    paras.sort();
    for para in paras:
        if data != "":
            data += "&";
        data += para + "=" + str(params[para]);
    if AppSecret == None:
        return data
    m = hashlib.md5()
    m.update((data+AppSecret).encode('utf-8'))
    return data+'&sign='+m.hexdigest()
    
def urlfetch(url):
    ip = random.randint(1,255)
    select = random.randint(1,2)
    if select == 1:
        ip = '220.181.111.' + str(ip)
    else:
        ip = '59.152.193.' + str(ip)
    req_headers = {'Accept-Encoding': 'gzip, deflate', 'User-Agent': USER_AGENT, 'Client-IP': ip, 'X-Forwarded-For': ip, 'Cookie': 'DedeUserID=8926815; DedeUserID__ckMd5=7a15e38c8988dd51; SESSDATA=f3723f8c%2C1445522963%2Ce07d220f;'}
    req = urllib.request.Request(url=url, headers=req_headers)
    response = urllib.request.urlopen(req, timeout=120)
    content_encoding = response.info().get('Content-Encoding')
    if content_encoding == 'gzip':
        data = gzip.GzipFile(fileobj=response).read()
    elif content_encoding == 'deflate':
        decompressobj = zlib.decompressobj(-zlib.MAX_WBITS)
        data = decompressobj.decompress(response.read())+decompressobj.flush()
    else:
        data = response.read()
    return data

if __name__ == '__main__':
    if len(sys.argv) == 1:
        print('输入视频播放地址')
        exit()
    if len(sys.argv) == 2:
        print('输入要查找的字符')
        exit()
    media_urls = GetBilibiliUrl(sys.argv[1], sys.argv[2])
{% endhighlight %}

##用法

{% highlight bash %}
~ $ python3 danmakuDecrypt.py http://www.bilibili.com/video/av12450 23333
{% endhighlight %}

大家可以在[这里](/script/danmakuDecrypt.py)下载到Python版源码

或者直接去 [Github](https://github.com/fuckbilibili/Danmaku-De-annoymous) 上找到js和Python的源码。

***

什么？上面的看不懂？那就直接拿来用吧！

## [匿名弹幕解密器](http://danmu.fuckbilibili.com)
