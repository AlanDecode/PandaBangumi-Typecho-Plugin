console.log('%c PandaBangumi 2.0 %c https://blog.imalan.cn/archives/128/ ', 'color: #fadfa3; background: #23b7e5; padding:5px 0;', 'background: #1c2b36; padding:5px 0;');

var bgmCur;

function loadMoreBgm(){
    $('.loader').html('<div class="dot"></div><div class="dot"></div><div class="dot"></div>');
    $.getJSON('/PandaBangumi?from='+String(bgmCur),function(data){
        $('.loader').html('加载更多');
        if(data.length<1) $('.loader').html('没有了');
        $.each(data,function(i,item){
            let name_cn=item.name_cn ? item.name_cn : item.name;
            var status;
            var total;
            if(!item.count || item.count==null) {
                status=100;
                total='未知';
            }
            else {
                status=item.status/item.count*100;
                total=String(item.count);
            };
            let html=`<a class="bgm-item" data-id="`+item.id+`" href="`+item.url+`" target="_blank">
                        <div class="bgm-item-thumb" style="background-image:url(`+item.img+`)"></div>
                        <div class="bgm-item-info">
                            <span class="bgm-item-title main">`+item.name+`</span>
                            <span class="bgm-item-title">`+name_cn+`</span>
                            <!--span class="bgm-item-air">`+item.air_date+` `+item.air_weekday+`</span-->
                            <div class="bgm-item-statusBar-container">
                                <div class="bgm-item-statusBar" style="width:`+String(status)+`%"></div>
                                进度：`+String(item.status)+` / `+total+`
                            </div>
                        </div>
                    </a>`;
            $("#bgm-collection").append(html);
            bgmCur++;
        })
    })
}

function initCollection(){
    if($("#bgm-collection").length<1) return;
    $("#bgm-collection").after(`<div class="loader" onclick="loadMoreBgm();"></div>`);
    bgmCur=0;
    loadMoreBgm();
}

$(document).ready(function(){
    initCollection();
})