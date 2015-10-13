function scrollStop() {
    $(window).scrollTop() == topValue && ($(".qwds-menu").show(), clearInterval(interval), interval = null)
}
function getData(a) {
    var e = $(".loading-imgS img"),
    s = $(".loading-imgS p"),
    o = $("#keyword").val(),
    t = $("#sortField").val(),
    l = $("#sortType").val(),
    r = $("#categoryValue").val(),
    i = $("#priceFrom").val(),
    c = $("#priceTo").val(),
    d = $("#brandValue").val(),
    n = $("#sourceValue").val(),
    v = $("#metaSearch").val(),
    p = window.B5M_URL.rootPath + "/s/search";
    $.ajax({
        url: p,
        type: "GET",
        data: {
            page: a,
            keyword: o,
            sortField: t,
            sortType: l,
            categoryValue: r,
            priceFrom: i,
            priceTo: c,
            brandValue: d,
            sourceValue: n,
            metaSearch: v
        },
        beforeSend: function() {
            e.show(),
            s.hide()
        },
        dataType: "jsonp",
        jsonp: "jsonpCallback",
        success: function(a) {
            $("#loader").hide();
            var o = a;
            if ("" == o) return $(".loading-imgS p").text("没有更多搜索结果了！"),
            e.hide(),
            s.show(),
            !1;
            var t = o.records;
            if (t.length > 0 && t.length < 8) flag = 0;
            else if (0 == t.length) return $(".loading-imgS p").text("没有更多搜索结果了！"),
            e.hide(),
            s.show(),
            !1;
            for (var l = 0; l < t.length; l++) {
                t[l] = t[l].res;
                var r = t[l].OriginalPicture.split(",")[0],
                i = 0;
                if (i = t[l].Price.split("-")[0], "" == i && (i = 0), "" != t[l].DocId) {
                    var c = "";
                    c = "韩国城" === t[l].Source ? "/item.html?tid=" + t[l].id: "/item.html?docid=" + t[l].DocId + "&bk=1",
                    "1" === $("#metaSearch").val() && (c = "/item.html?url=" + t[l].OriginalUrl);
                    var d = t[l].Picture + "/140x140";
                    t[l].Picture.indexOf("http://tfs01.b5mcdn.com/") < 0 && (d = t[l].Picture),
                    $("#dataList").append('<div class="s-item"><div><a href=' + window.B5M_URL.rootPath + c + ' class="s-img"><div class="goods-one-img"><img src="' + d + '" onerror="this.src=\'' + r + "'\" /></div></a></div><div><a href=" + window.B5M_URL.rootPath + c + '" class="s-text">' + t[l].Title + '</a></div><div class="source_wrap">' + t[l].Source + '</div><div class="am"><span class="price">&yen; ' + i + '</span><span class="sales">销量:' + (t[l].SalesAmount > 0 ? t[l].SalesAmount: 1) + "</span></div></div>")
                }
            }
            e.hide(),
            s.show()
        }
    })
}
interval = null,
topValue = 0,
$(function() {
    {
        var a = $(".sr-s"),
        e = $(".s-list");
        a.offset().top
    }
    "none" == $(".wap-topbar").css("display") && (a.css({
        position: "static"
    }), e.css({
        marginTop: "10px"
    }));
    var s = 1;
    $(document).ready(function() {
        var a = 0,
        e = 0;
        $(window).scroll(function() {
            var o = $(window).scrollTop();
            e = parseFloat($(window).height()) + parseFloat(o),
            $(document).height() - a <= e && getData(++s)
        })
    }),
    window.sortCallback = function(a, e) {
        "price" == a ? ($("#sortField").val("Price"), $("#sortType").val("desc" == e ? "desc": "asc")) : "hot" == a ? ($("#sortField").val("Score"), $("#sortType").val("desc")) : "sales" == a ? ($("#sortField").val("SalesAmount"), $("#sortType").val("desc")) : ($("#sortField").val(""), $("#sortType").val("")),
        $("#dataList").html(""),
        $("#loader").show(),
        getData(1)
    };
    var o = $(".sr-s");
    o.on("click", ".s-btn", 
    function() {
        var a = $(this),
        e = a.attr("data"),
        s = "desc";
        "price" == e && (a.hasClass("asc") && a.hasClass("selected") ? (a.removeClass("asc").addClass("desc"), s = "desc") : (a.removeClass("desc").addClass("asc"), s = "asc")),
        o.find(".s-btn").removeClass("selected"),
        a.addClass("selected"),
        window.sortCallback(e, s, this)
    });
    $(".precise-seach").find(".logo-items").on("click", "li", 
    function() {
        var a = $(this);
        a.addClass("logo-checked").siblings("li").removeClass("logo-checked"),
        $("#sourceValue").val(a.attr("data-id"));
        var e = $("#metaSearch"),
        s = $("#sourceValue");
        e.val(1),
        s.val() || s.val("suning.com"),
        s.appendTo($("#sForm")),
        e.appendTo($("#sForm")),
        $("#sForm").submit()
    }),
    $(".precise-seach").find(".ps_top").on("click", ".s-btn", 
    function() {
        var a = ($(this), $("#metaSearch")),
        e = $("#sourceValue");
        e.remove(),
        a.remove(),
        $("#sForm").submit()
    })
});