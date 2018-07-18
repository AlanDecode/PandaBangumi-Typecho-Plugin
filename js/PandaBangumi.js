var PandaBangumi_loading_notice="<div class=\"PandaBangumi-loading\">Loading...<br>ヾ(≧∇≦*)ゝ</div>";
console.log('%c PandaBangumi 1.1 %c https://imalan.cn/archives/128/ ', 'color: #fadfa3; background: #23b7e5; padding:5px 0;', 'background: #1c2b36; padding:5px 0;');

function PandaBangumi_UpdateStatusBar()
{
    $(".PandaBangumi-status-bar").each(function(){
        var status=$(this).attr("data1");
        var count=$(this).attr("data2");
        var w;
        if (count !== 'Unknown') {
            w = Math.floor($(".PandaBangumi-content").width() * status / count);
        }
        else {
            w = Math.floor($(".PandaBangumi-content").width());
        }
        $(this).css("width",String(w)+"px");
    })
}

function PandaBangumi_UpdateAll()
{
    if(document.getElementById("PandaBangumi-Content"))
    {
        if(document.getElementById("PandaBangumi-Content").offsetWidth>620)
        {
            $(".PandaBangumi-item").css("width","48%");
        }
        else
        {
            $(".PandaBangumi-item").css("width","100%");
        }

        PandaBangumi_UpdateStatusBar();
    }
}

function PandaBangumi_UpdateBoards()
{
    var h=Math.min(230,Math.floor($(".PandaBangumi_Board").width()*0.47));
    $(".PandaBangumi_Board").css("height",h+"px");
    $(".PandaBangumi_Board_Img_Box").css("width",$(".PandaBangumi_Board_Img_Box").height()+"px");
    var tp=Math.floor(($(".PandaBangumi_Board").height()-$(".PandaBangumi_Board_Img_Box").height())/2);
    $(".PandaBangumi_Board_Img_Box").css("left",tp+"px");
    $(".PandaBangumi_Board_Content").css("left",Math.floor(1.8*tp+$(".PandaBangumi_Board_Img_Box").width())+"px");
    $(".PandaBangumi_Board_title_cn").css("font-size",tp*3.5+"px");
    $(".PandaBangumi_Board_title").css("font-size",tp*2.3+"px");
    $(".PandaBangumi_Board_info").css("line-height",tp*2.8+"px")
    $(".PandaBangumi_Board_info").css("font-size",tp*2.3+"px");

    $(".PandaBangumi_Board_Img_Box").click(function () {
        $(this).next().fadeIn(250);
    })
    $(".PandaBangumi_Board_Summary").click(function () {
        $(this).fadeOut(250);
    });
    $(".PandaBangumi_Board_Summary").hover(function(){},function () {
        $(this).fadeOut(250);
    });
    $(".PandaBangumi_Board_Content").click(function(){
        $(this).prev().fadeIn(250);
    })
}

function PandaBangumi_turnPage(dPage)
{
    var newPage=parseInt(document.getElementById("PandaBangumi-collections").getAttribute("pagenum"))+dPage;
    
    $('#PandaBangumi-collections-holder').empty().append(PandaBangumi_loading_notice);

    setTimeout(function(){
        jQuery.ajax({
            type: 'GET',
            url: '/index.php/PandaBangumi?page='+newPage+'&onlycollection=1',
            success: function(res) {
                $('#PandaBangumi-collections-holder').empty().append(res);
                PandaBangumi_UpdateAll();
            },
            error:function(){
                $('#PandaBangumi-collections-holder').empty().text('加载失败');
            }
        });
    },500)
}

PandaBangumi_refresh=function()
{
    if(document.getElementById("PandaBangumi-Content"))
    {
        $(".PandaBangumi-Content").html(PandaBangumi_loading_notice);
        
        setTimeout(function(){
            jQuery.ajax({
                type: 'GET',
                url: '/index.php/PandaBangumi?page=1&onlycollection=0&forcerefresh=true',
                success: function(res) {
                    $('#PandaBangumi-Content').empty().append(res);
                    PandaBangumi_UpdateAll();
                },
                error:function(){
                    $('#PandaBangumi-Content').empty().text('加载失败');
                }
            });
        },500)
    }
}

PandaBangumi_initBGM=function()
{
    if(document.getElementById("PandaBangumi-Content"))
    {
        $(".PandaBangumi-Content").html(PandaBangumi_loading_notice);
        
        setTimeout(function(){
            jQuery.ajax({
                type: 'GET',
                url: '/index.php/PandaBangumi?page=1&onlycollection=0',
                success: function(res) {
                    $('#PandaBangumi-Content').empty().append(res);
                    PandaBangumi_UpdateAll();
                },
                error:function(){
                    $('#PandaBangumi-Content').empty().text('加载失败');
                }
            });
        },500)
    }

    $(".PandaBangumi_Board").html(PandaBangumi_loading_notice);

    $(".PandaBangumi_Board").each(function(){
        var obj=$(this);
        jQuery.ajax({
            type:'GET',
            url:'/index.php/PandaBangumi?getboard=1&id='+$(this).attr("data"),
            success:function(res){
                obj.empty().append(res);
                PandaBangumi_UpdateBoards();
            },
            error:function(){
                obj.empty().append('加载失败，真是悲伤……');
            }
        });
    });
}

$(document).ready(PandaBangumi_initBGM);
$(window).resize(PandaBangumi_UpdateAll);
$(window).resize(PandaBangumi_UpdateBoards);
