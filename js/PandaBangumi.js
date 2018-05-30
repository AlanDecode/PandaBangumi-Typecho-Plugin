var PandaBangumi_loading_notice="<div class=\"bangumi_loading\"><div class=\"loading-anim\"><div class=\"border out\"><\/div><div class=\"border in\"><\/div><div class=\"border mid\"><\/div><div class=\"circle\"><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><\/div><div class=\"bangumi_loading_text\" style=\"margin-top:50px\"><center><h2 class=\"loading-text\">Loading...<\/h2><\/center><center><h4 class=\"loading-text\">跟我一起抖动可以使页面加载更快<\/h4><\/center><\/div><\/div><\/div>";

function PandaBangumi_getElementsClass(classnames)
{ 
    var classobj= new Array();//定义数组 
    var classint=0;//定义数组的下标 
    var tags=document.getElementsByTagName("*");//获取HTML的所有标签 
    for(var i in tags)
    {//对标签进行遍历 
        if(tags[i].nodeType==1)
        {//判断节点类型 
            if(tags[i].getAttribute("class") == classnames)//判断和需要CLASS名字相同的，并组成一个数组 
            { 
                classobj[classint]=tags[i]; 
                classint++; 
            } 
        }
    }
    return classobj;//返回组成的数组 
}

function PandaBangumi_UpdateStatusBar()
{
    var bgm_bars=PandaBangumi_getElementsClass("PandaBangumi-status-bar");
    for(var i=0;i<bgm_bars.length;i++)
    {  
        var status=bgm_bars[i].getAttribute("data1");
        var count=bgm_bars[i].getAttribute("data2");
        var w;
        if(count!=='Unknown')
        {
            w=Math.floor($(".PandaBangumi-content").width()*status/count);
        }
        else
        {
            w=Math.floor($(".PandaBangumi-content").width()); 
        }
        bgm_bars[i].setAttribute("style","width:"+String(w)+"px");
    }
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
    var h=Math.min(256,Math.floor($(".PandaBangumi_Board").width()*0.45));
    $(".PandaBangumi_Board").css("height",h+"px");
    $(".PandaBangumi_Board_Img_Box").css("width",$(".PandaBangumi_Board_Img_Box").height()+"px");
    var tp=Math.floor(($(".PandaBangumi_Board").height()-$(".PandaBangumi_Board_Img_Box").height())/2);
    $(".PandaBangumi_Board_Img_Box").css("left",tp+"px");
    $(".PandaBangumi_Board_Content").css("left",Math.floor(1.8*tp+$(".PandaBangumi_Board_Img_Box").width())+"px");
    $(".PandaBangumi_Board_title_cn").css("font-size",tp*2+"px");
    $(".PandaBangumi_Board_title").css("font-size",tp*1.3+"px");
    $(".PandaBangumi_Board_info").css("line-height",tp*1.6+"px")
    $(".PandaBangumi_Board_info").css("font-size",tp*1.3+"px");
    $(".PandaBangumi_Board_Info_Button").click(function(){
        if($(this).next().css("display")=="none")
        {
            $(this).next().fadeIn(400);
            $(this).html("×");
        }
        else if($(this).next().css("opacity")==1)
        {
            $(this).next().fadeOut(400);
            $(this).html("i");
        }
        
    })
}

function PandaBangumi_TurnPage(dPage)
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
                $("#PandaBangumi-pager-newer").click(function() {PandaBangumi_TurnPage(-1)});
                $("#PandaBangumi-pager-older").click(function() {PandaBangumi_TurnPage(1)});
            },
            error:function(){
                $('#PandaBangumi-collections-holder').empty().text('加载失败');
            }
        });
    },500)
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
                    $("#PandaBangumi-pager-newer").click(function() {PandaBangumi_TurnPage(-1)});
                    $("#PandaBangumi-pager-older").click(function() {PandaBangumi_TurnPage(1)});
                },
                error:function(){
                    $('#PandaBangumi-Content').empty().text('加载失败');
                }
            });
        },500)
    }

    $(".PandaBangumi_Board").html("<div class=\"bangumi_loading\"><div class=\"loading-anim\"><div class=\"border out\"><\/div><div class=\"border in\"><\/div><div class=\"border mid\"><\/div><div class=\"circle\"><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><span class=\"dot\"><\/span><\/div>");

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
