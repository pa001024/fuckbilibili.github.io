---
layout: page
permalink: "bilidown.html"
title:  "BilibiliDownload"
videojs: yes
---
<head>
  <style type="text/css">
    .video-js-responsive-container.vjs-hd {
    padding-top: 56.25%;
    }
    .video-js-responsive-container.vjs-sd {
        padding-top: 75%;
    }
    .video-js-responsive-container {
        width: 100%;
        position: relative;
    }
    .video-js-responsive-container .video-js {
        height: 100% !important; 
        width: 100% !important;
        position: absolute;
        top: 0;
        left: 0;
    }
  </style>
</head>

## 事前唠个叨

其实也不是都是我自己写的，源码参照了Vespa的，Key使用了Beining的，版权问题采用了Zac的解决方法

我就是建了个网站，让b站的用户们好下载视频而已~

## 先看个片子

b站审核肯定不通过，而且采用了b站一贯的不要脸方法，挂在那里让你以为还有机会会过……

放tm狗屁，怎么可能？！

不用b站我们又不是没有自己的服务器：

<div class="video-js-responsive-container vjs-hd">
<video id="my-video" class="video-js vjs-big-play-centered" controls preload="auto" data-setup='{"language":"zh-CN"}'>
  <source src="cdn-tlo.b0.upaiyun.com/fuckbilibili/5s5b7h3ek5uaxl8az2942100.mp4" type='video/mp4'>
  <p class="vjs-no-js">
  To view this video please enable JavaScript, and consider upgrading to a web browser that
  <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
  </p>
</video>
</div>
<script src="//cdnjs-com.b0.upaiyun.com/ajax/libs/video.js/5.0.0/lang/zh-CN.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/video.js/5.0.0/video.min.js"></script>

    其实一开始叫我配音我是拒绝的，因为你不能叫我配我就配，第一我要……

### 主源码如下：

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
import xml.dom.minidom
import zlib
import random

USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.99 Safari/537.36'
APPKEY = '85eb6835b0a1034e'
APPSEC = '2ad42749773c441109bdc0191257a664'

def GetBilibiliUrl(url):
    overseas=False
    url_get_media = 'http://interface.bilibili.com/playurl?' if not overseas else 'http://interface.bilibili.com/v_cdn_play?'
    regex_match = re.findall('http:/*[^/]+/video/av(\\d+)(/|/index.html|/index_(\\d+).html)?(\\?|#|$)',url)
    if not regex_match:
        return 'error2'
    aid = regex_match[0][0]
    pid = regex_match[0][2] or '1'
    cid_args = {'type': 'json', 'id': aid, 'page': pid}

    resp_cid = urlfetch('http://api.bilibili.com/view?'+GetSign(cid_args,APPKEY,APPSEC))
    resp_cid = dict(json.loads(resp_cid.decode('utf-8', 'replace')))
    cid = resp_cid.get('cid')
    media_args = {'otype': 'json', 'cid': cid, 'type': 'flv', 'quality': 4, 'appkey': APPKEY}
    resp_media = urlfetch(url_get_media+ChangeFuck(media_args))
    resp_media = dict(json.loads(resp_media.decode('utf-8', 'replace')))
    result = resp_media.get('result')
    if (result is 'error'):
        return 'error'
    media_urls = resp_media.get('durl')
    media_urls = media_urls[0]
    media_urls = media_urls.get('url')
    return media_urls
    
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

def ChangeFuck(params):
    data = "";
    paras = params;
    for para in paras:
        if data != "":
            data += "&";
        data += para + "=" + str(params[para]);
    return data
    
def urlfetch(url):
    ip = random.randint(1,255)
    select = random.randint(1,2)
    if select == 1:
        ip = '220.181.111.' + str(ip)
    else:
        ip = '59.152.193.' + str(ip)
    req_headers = {'Accept-Encoding': 'gzip, deflate', 'User-Agent': USER_AGENT, 'Client-IP': ip, 'X-Forwarded-For': ip}
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
    else:
        media_urls = GetBilibiliUrl(sys.argv[1])
        print(media_urls)
{% endhighlight %}

大家如果有域名的话可以搞个100个这样的网站出来，想自建的可以在 [Github](https://github.com/fuckbilibili/BilibiliDownload) 上找到完整的源码，clone就可以直接用。

##用法

{% highlight bash %}
~ $ python3 biliDownLoad.py http://www.bilibili.com/video/av12450/
{% endhighlight %}

***

就是这样，点开导航栏中的 *SuperFashi* 页面，你会发现更多有趣的东西~
