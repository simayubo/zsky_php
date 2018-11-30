function layeropen(src, title, width, height, skin, full){
    full = full||false;
    skin = 'layui-layer-molv';
    // skin = skin||'layui-layer-rim';
    // if (skin == 'no'){
    //     skin = false
    // }
    if (full){
        parent.layer.open({
            type: 2,
            title: title,
            maxmin: false,
            //shift:7,
            skin: skin, //加上边框
            area: [width, height],
            content: src
        });
    }else {
        layer.open({
            type: 2,
            title: title,
            maxmin: false,
            //shift:7,
            skin: skin, //加上边框
            area: [width, height],
            content: src
        });
    }
}

$('#more_search').click(function () {
    var flag = $("#more_search_html").is(":hidden");
    if(flag){
        $("#more_search_html").show();
    }else{
        $("#more_search_html").hide();
    }
});

